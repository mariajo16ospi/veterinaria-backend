<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use MercadoPago\Exceptions\MPApiException;

class PagoController extends Controller
{
    protected $firebase;
    protected $database;

    public function __construct()
    {
        try {
            Log::info('Inicializando PagoController');

            // Verificar que las credenciales de Mercado Pago existan
            $accessToken = config('services.mercadopago.access_token');
            $publicKey = config('services.mercadopago.public_key');

            if (empty($accessToken)) {
                Log::error(' MERCADOPAGO_ACCESS_TOKEN no está configurado en .env');
                throw new Exception('Mercado Pago Access Token no configurado');
            }

            if (empty($publicKey)) {
                Log::error(' MERCADOPAGO_PUBLIC_KEY no está configurado en .env');
                throw new Exception('Mercado Pago Public Key no configurado');
            }

            // Verificar formato del Access Token
            if (!str_starts_with($accessToken, 'APP_USR-')) {
                Log::warning(' Access Token no tiene el formato esperado (debe comenzar con APP_USR-)');
            }

            // Verificar que el SDK de Mercado Pago esté instalado
            if (!class_exists('MercadoPago\MercadoPagoConfig')) {
                Log::error(' SDK de Mercado Pago no está instalado');
                throw new Exception('SDK de Mercado Pago no encontrado. Ejecuta: composer require mercadopago/dx-php');
            }

            // Configurar Mercado Pago
            \MercadoPago\MercadoPagoConfig::setAccessToken($accessToken);

            // Activar logs de Mercado Pago en desarrollo
            if (config('app.debug')) {
                \MercadoPago\MercadoPagoConfig::setRuntimeEnviroment(\MercadoPago\MercadoPagoConfig::LOCAL);
            }

            Log::info(' Mercado Pago configurado correctamente');

        } catch (Exception $e) {
            Log::error(' Error en constructor de PagoController: ' . $e->getMessage());
        }
    }

    /**
     * Inicializar Firebase solo cuando se necesite
     */
    private function initFirebase()
    {
        if ($this->database !== null) {
            return;
        }

        try {
            Log::info(' Inicializando Firebase...');

            $firebaseCredentialsPath = storage_path('app/firebase/firebase-credentials.json');

            if (!file_exists($firebaseCredentialsPath)) {
                $firebaseCredentialsPath = base_path('firebase-credentials.json');

                if (!file_exists($firebaseCredentialsPath)) {
                    throw new Exception('Credenciales de Firebase no encontradas');
                }
            }

            Log::info(' Archivo de credenciales encontrado: ' . $firebaseCredentialsPath);

            $databaseUrl = config('firebase.database_url');
            if (empty($databaseUrl)) {
                throw new Exception('FIREBASE_DATABASE_URL no configurada en .env');
            }

            $this->firebase = (new \Kreait\Firebase\Factory)
                ->withServiceAccount($firebaseCredentialsPath)
                ->withDatabaseUri($databaseUrl);

            $this->database = $this->firebase->createDatabase();
            Log::info(' Firebase inicializado correctamente');

        } catch (Exception $e) {
            Log::error(' Error al inicializar Firebase: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test endpoint - Verificar configuración
     */
    public function test()
    {
        try {
            $accessToken = config('services.mercadopago.access_token');
            $publicKey = config('services.mercadopago.public_key');

            $checks = [
                'mercadopago_sdk_installed' => class_exists('MercadoPago\MercadoPagoConfig'),
                'has_public_key' => !empty($publicKey),
                'has_access_token' => !empty($accessToken),
                'public_key_prefix' => substr($publicKey ?? '', 0, 8),
                'access_token_prefix' => substr($accessToken ?? '', 0, 8),
                'public_key_length' => strlen($publicKey ?? ''),
                'access_token_length' => strlen($accessToken ?? ''),
                'access_token_format_ok' => str_starts_with($accessToken ?? '', 'APP_USR-'),
            ];

            Log::info('Test ejecutado', $checks);

            return response()->json([
                'success' => true,
                'message' => 'API de pagos funcionando',
                'timestamp' => now()->toISOString(),
                'checks' => $checks
            ]);

        } catch (Exception $e) {
            Log::error(' Error en test: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener la public key para el frontend
     */
    public function getPublicKey()
    {
        try {
            Log::info(' Solicitud de public key recibida');

            $publicKey = config('services.mercadopago.public_key');

            if (empty($publicKey)) {
                Log::error(' Public key no configurada');
                return response()->json([
                    'success' => false,
                    'message' => 'Public key no configurada. Verifica tu archivo .env'
                ], 500);
            }

            Log::info(' Public key enviada - Primeros 10 caracteres: ' . substr($publicKey, 0, 10) . '...');

            return response()->json([
                'success' => true,
                'public_key' => $publicKey
            ]);

        } catch (Exception $e) {
            Log::error(' Error al obtener public key: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la public key',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear preferencia de pago
     */
    public function crearPreferencia(Request $request)
{
    try {
        Log::info(' Inicio de creacion de preferencia de pago');
        Log::info(' Datos recibidos:', $request->all());

        // Validación
        $validated = $request->validate([
            'cita_id' => 'required|string',
            'monto' => 'required|numeric|min:1000',
            'email_cliente' => 'required|email',
            'nombre_cliente' => 'required|string',
            'descripcion' => 'required|string',
        ]);

        Log::info(' Validación exitosa');

        // Redondear monto para COP
        $monto = round(floatval($validated['monto']));

        // Buscar cita en Firebase
        $citaRef = null;
        try {
            $this->initFirebase();

            $citaRef = $this->database->getReference('citas/' . $validated['cita_id']);
            $cita = $citaRef->getValue();

            if (!$cita) {
                Log::warning(' Cita no encontrada');
                return response()->json([
                    'success' => false,
                    'message' => 'Cita no encontrada'
                ], 404);
            }

            if (isset($cita['estado_pago']) && $cita['estado_pago'] === 'pagado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta cita ya ha sido pagada'
                ], 400);
            }

        } catch (Exception $e) {
            Log::warning(' Firebase no disponible: ' . $e->getMessage());
        }

        // Crear cliente Mercado Pago
        $client = new \MercadoPago\Client\Preference\PreferenceClient();

        Log::info('Creando preferencia en Mercado Pago...');


        $preference = $client->create([
            "items" => [
                [
                    "title" => $validated['descripcion'],
                    "quantity" => 1,
                    "unit_price" => $monto
                ]
            ],
            "external_reference" => $validated['cita_id'],
            "notification_url" => "https://onita-unrallied-erin.ngrok-free.dev/api/v1/pagos/webhook",
            "back_urls" => [
                "success" => "https://onita-unrallied-erin.ngrok-free.dev/cliente/citas/pago/success",
                "failure" => "https://onita-unrallied-erin.ngrok-free.dev/cliente/citas/pago/failure",
                "pending" => "https://onita-unrallied-erin.ngrok-free.dev/cliente/citas/pago/pending"
            ],
            "auto_return" => "approved",
        ]);


        Log::info(' Preferencia creada correctamente');
        Log::info(" ID: {$preference->id}");
        Log::info(" Init Point: {$preference->init_point}");

        // Actualizar cita en Firebase
        if ($citaRef !== null) {
            $citaRef->update([
                'mercadopago_preference_id' => $preference->id,
                'estado_pago' => 'pendiente',
                'updated_at' => now()->toISOString()
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'preference_id' => $preference->id,
                'init_point' => $preference->init_point
            ]
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error de validación',
            'errors' => $e->errors()
        ], 422);

    } catch (Exception $e) {
        Log::error(' ERROR AL CREAR PREFERENCIA: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Error al crear la preferencia de pago',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Webhook de Mercado Pago
     */
    public function webhook(Request $request)
    {
        try {
            Log::info(' WEBHOOK RECIBIDO ');
            Log::info(' Datos:', $request->all());

            $type = $request->input('type');

            if ($type === 'payment') {
                $paymentId = $request->input('data.id');

                if (!$paymentId) {
                    Log::warning(' Webhook sin payment ID');
                    return response()->json(['status' => 'ok'], 200);
                }

                Log::info(' Procesando pago: ' . $paymentId);

                try {
                    $this->initFirebase();
                } catch (Exception $e) {
                    Log::error(' Error al inicializar Firebase en webhook: ' . $e->getMessage());
                    return response()->json(['status' => 'error', 'message' => 'Firebase no disponible'], 500);
                }

                $client = new \MercadoPago\Client\Payment\PaymentClient();
                $payment = $client->get($paymentId);

                Log::info(' Información del pago obtenida', [
                    'status' => $payment->status,
                    'amount' => $payment->transaction_amount,
                ]);

                $citaId = $payment->external_reference;

                if ($citaId && $this->database !== null) {
                    $citaRef = $this->database->getReference('citas/' . $citaId);
                    $cita = $citaRef->getValue();

                    if ($cita) {
                        $datosActualizacion = [
                            'mercadopago_payment_id' => strval($paymentId),
                            'mercadopago_status' => $payment->status,
                            'updated_at' => now()->toISOString()
                        ];

                        if ($payment->status === 'approved') {
                            $datosActualizacion['estado_pago'] = 'pagado';
                            $datosActualizacion['fecha_pago'] = $payment->date_approved ?? now()->toISOString();
                            Log::info(" Pago aprobado para cita: {$citaId}");
                        }

                        $citaRef->update($datosActualizacion);
                        Log::info(' Cita actualizada en Firebase');
                    }
                }

                return response()->json(['status' => 'ok'], 200);
            }

            return response()->json(['status' => 'ignored'], 200);

        } catch (Exception $e) {
            Log::error(' Error en webhook:', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Error processing webhook'], 500);
        }
    }

    /**
     * Verificar estado de un pago
     */
    public function verificar($paymentId)
    {
        try {
            \Log::info("Verificando pago con ID: $paymentId");

            $client = new \MercadoPago\SDK();
            $client->setAccessToken(config('services.mercadopago.access_token'));

            // Obtener el pago real
            $payment = $client->payment()->get($paymentId);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pago no encontrado'
                ], 404);
            }

            \Log::info("Pago obtenido:", (array) $payment);

            // Respuesta a Angular
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $payment->id,
                    'status' => $payment->status,
                    'status_detail' => $payment->status_detail,
                    'amount' => $payment->transaction_amount,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error("Error verificando pago: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al verificar el pago'
            ], 500);
        }
    }

}
