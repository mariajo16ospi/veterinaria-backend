<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Factory;

class AuthController extends Controller
{
    protected $database;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(base_path('storage/app/firebase/firebase-credentials.json'))
            ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));

        $this->database = $factory->createDatabase();
    }

    public function login(Request $request)
    {
        Log::info('=== INICIO LOGIN ===');
        Log::info('Request:', $request->all());

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Buscar usuario por email
            $snapshot = $this->database->getReference('usuarios')->getSnapshot();
            $usuarios = $snapshot->getValue();

            if (!$usuarios) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            $usuarioEncontrado = null;
            $usuarioId = null;

            foreach ($usuarios as $id => $usuario) {
                if (isset($usuario['email']) && $usuario['email'] === $request->email) {
                    $usuarioEncontrado = $usuario;
                    $usuarioId = $id;
                    break;
                }
            }

            if (!$usuarioEncontrado) {
                Log::warning('Usuario no encontrado: ' . $request->email);
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            Log::info('Usuario encontrado:', ['id' => $usuarioId, 'rol' => $usuarioEncontrado['rol'] ?? 'sin rol']);

            // Verificar contraseña
            if (!Hash::check($request->password, $usuarioEncontrado['password'])) {
                Log::warning('Contraseña incorrecta');
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            // Verificar estado activo
            if (isset($usuarioEncontrado['estado']) && $usuarioEncontrado['estado'] !== 'activo') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario inactivo. Contacta al administrador.'
                ], 403);
            }

            $rol = $usuarioEncontrado['rol'] ?? 'cliente';

            // Preparar datos base
            $responseData = [
                'usuario_id' => $usuarioId,
                'email' => $usuarioEncontrado['email'],
                'nombre' => $usuarioEncontrado['nombre'] ?? '',
                'apellido' => $usuarioEncontrado['apellido'] ?? '',
                'nombre_completo' => trim(($usuarioEncontrado['nombre'] ?? '') . ' ' . ($usuarioEncontrado['apellido'] ?? '')),
                'rol' => $rol,
                'estado' => $usuarioEncontrado['estado'] ?? 'activo',
                'telefono' => $usuarioEncontrado['telefono'] ?? '',
            ];

            // Buscar datos específicos según el rol
            $rolId = null;

            switch ($rol) {
                case 'cliente':
                    Log::info('Buscando datos de cliente...');
                    $clientesSnapshot = $this->database->getReference('clientes')->getSnapshot();
                    $clientes = $clientesSnapshot->getValue();

                    if ($clientes) {
                        foreach ($clientes as $key => $cliente) {
                            if (isset($cliente['usuario_id']) && $cliente['usuario_id'] === $usuarioId) {
                                $rolId = $key;
                                Log::info('Cliente encontrado con ID: ' . $rolId);
                                break;
                            }
                        }
                    }

                    if (!$rolId) {
                        Log::warning('No se encontró documento de cliente para usuario_id: ' . $usuarioId);
                    }

                    $responseData['cliente_id'] = $rolId;
                    $responseData['id'] = $rolId ?? $usuarioId;
                    break;

                case 'veterinario':
                    Log::info('Buscando datos de veterinario...');
                    $veterinariosSnapshot = $this->database->getReference('veterinarios')->getSnapshot();
                    $veterinarios = $veterinariosSnapshot->getValue();

                    if ($veterinarios) {
                        foreach ($veterinarios as $key => $vet) {
                            if (isset($vet['usuario_id']) && $vet['usuario_id'] === $usuarioId) {
                                $rolId = $key;
                                Log::info('Veterinario encontrado con ID: ' . $rolId);

                                $responseData['especialidad'] = $vet['especialidad'] ?? '';
                                $responseData['licencia'] = $vet['licencia'] ?? '';
                                break;
                            }
                        }
                    }

                    $responseData['veterinario_id'] = $rolId;
                    $responseData['id'] = $usuarioId;
                    break;

                case 'recepcionista':
                    Log::info('Buscando datos de recepcionista...');
                    $recepcionistasSnapshot = $this->database->getReference('recepcionistas')->getSnapshot();
                    $recepcionistas = $recepcionistasSnapshot->getValue();

                    if ($recepcionistas) {
                        foreach ($recepcionistas as $key => $recep) {
                            if (isset($recep['usuario_id']) && $recep['usuario_id'] === $usuarioId) {
                                $rolId = $key;
                                Log::info('Recepcionista encontrado con ID: ' . $rolId);
                                break;
                            }
                        }
                    }

                    $responseData['recepcionista_id'] = $rolId;
                    $responseData['id'] = $usuarioId;
                    break;

                case 'administrador':
                    Log::info('Usuario es administrador');
                    $responseData['id'] = $usuarioId;
                    break;

                default:
                    Log::warning('Rol desconocido: ' . $rol);
                    $responseData['id'] = $usuarioId;
                    break;
            }

            // Generar token simple
            $token = base64_encode($usuarioId . ':' . time());

            Log::info('Login exitoso. Respuesta:', $responseData);
            Log::info('=== FIN LOGIN ===');

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => $responseData,
                'token' => $token
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en login: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error en el servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso'
        ]);
    }
}
