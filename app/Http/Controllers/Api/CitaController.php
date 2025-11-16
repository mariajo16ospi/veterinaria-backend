<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class CitaController extends Controller
{
    protected $database;
    protected $whatsappService;

    // ← MODIFICADO: Inyectar WhatsAppService
    public function __construct(WhatsAppService $whatsappService)
    {
        $factory = (new Factory)
            ->withServiceAccount(base_path('storage/app/firebase/firebase-credentials.json'))
            ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));

        $this->database = $factory->createDatabase();
        $this->whatsappService = $whatsappService;
    }

    // Método auxiliar para enriquecer citas con datos de cliente y mascota
    private function enrichCitaData($cita, $citaId)
    {
        $cita['id'] = $citaId;

        // Obtener datos del cliente
        $clienteId = $cita['cliente_id'] ?? $cita['ownerUid'] ?? null;
        if ($clienteId) {
            try {
                // Buscar en usuarios
                $usuario = $this->database->getReference("usuarios/{$clienteId}")->getValue();
                if ($usuario) {
                    $cita['nombre_cliente'] = $usuario['nombre'] ?? $usuario['name'] ?? 'Cliente sin nombre';
                    $cita['telefono_cliente'] = $usuario['telefono'] ?? '';
                    $cita['email_cliente'] = $usuario['email'] ?? '';
                }
            } catch (\Exception $e) {
                \Log::error("Error obteniendo cliente {$clienteId}: " . $e->getMessage());
            }
        }

        // Obtener datos de la mascota
        $mascotaId = $cita['mascota_id'] ?? null;
        if ($mascotaId && $clienteId) {
            try {
                $mascota = $this->database
                    ->getReference("clientes/{$clienteId}/pacientes/{$mascotaId}")
                    ->getValue();

                if ($mascota) {
                    $cita['nombre_mascota'] = $mascota['nombre_mascota'] ?? 'Mascota sin nombre';
                    $cita['especie'] = $mascota['especie'] ?? '';
                    $cita['raza'] = $mascota['raza'] ?? '';
                    $cita['sexo'] = $mascota['sexo'] ?? '';
                } else {
                    // Si no se encuentra en la ruta de cliente, buscar en todos los clientes
                    $cita['nombre_mascota'] = $this->buscarNombreMascota($mascotaId);
                }
            } catch (\Exception $e) {
                \Log::error("Error obteniendo mascota {$mascotaId}: " . $e->getMessage());
                $cita['nombre_mascota'] = 'Mascota sin nombre';
            }
        }

        return $cita;
    }

    // Método auxiliar para buscar el nombre de una mascota en todos los clientes
    private function buscarNombreMascota($mascotaId)
    {
        try {
            $clientes = $this->database->getReference('clientes')->getValue();

            if ($clientes) {
                foreach ($clientes as $clienteId => $cliente) {
                    $pacientes = $this->database
                        ->getReference("clientes/{$clienteId}/pacientes")
                        ->getValue();

                    if ($pacientes && isset($pacientes[$mascotaId])) {
                        return $pacientes[$mascotaId]['nombre_mascota'] ?? 'Mascota sin nombre';
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error("Error buscando mascota {$mascotaId}: " . $e->getMessage());
        }

        return 'Mascota sin nombre';
    }

    // Listar todas las citas con filtros opcionales
    public function index(Request $request)
    {
        try {
            $citas = $this->database->getReference('citas')->getValue();

            if (!$citas) {
                return response()->json(['success' => true, 'data' => []]);
            }

            // Convertir objeto a array y enriquecer con datos
            $citasArray = [];
            foreach ($citas as $key => $cita) {
                $citaEnriquecida = $this->enrichCitaData($cita, $key);
                $citasArray[] = $citaEnriquecida;
            }

            // Aplicar filtros si existen
            if ($request->has('veterinario_id')) {
                $citasArray = array_filter($citasArray, function($cita) use ($request) {
                    return ($cita['veterinario_id'] ?? '') === $request->veterinario_id;
                });
            }

            if ($request->has('cliente_id')) {
                $citasArray = array_filter($citasArray, function($cita) use ($request) {
                    return ($cita['cliente_id'] ?? '') === $request->cliente_id ||
                           ($cita['ownerUid'] ?? '') === $request->cliente_id;
                });
            }

            if ($request->has('fecha')) {
                $citasArray = array_filter($citasArray, function($cita) use ($request) {
                    return ($cita['fecha'] ?? '') === $request->fecha;
                });
            }

            if ($request->has('estado')) {
                $citasArray = array_filter($citasArray, function($cita) use ($request) {
                    return ($cita['estado'] ?? '') === $request->estado;
                });
            }

            // Ordenar por fecha y hora (más recientes primero)
            usort($citasArray, function($a, $b) {
                $fechaA = ($a['fecha'] ?? '') . ' ' . ($a['hora'] ?? '00:00');
                $fechaB = ($b['fecha'] ?? '') . ' ' . ($b['hora'] ?? '00:00');
                return strcmp($fechaB, $fechaA);
            });

            return response()->json([
                'success' => true,
                'data' => array_values($citasArray)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    //  Crear una cita con notificación de WhatsApp
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['created_at'] = now()->toIso8601String();
            $data['estado'] = $data['estado'] ?? 'pendiente';

            // Crear la cita en Firebase
            $newCita = $this->database
                ->getReference('citas')
                ->push($data);

            $citaId = $newCita->getKey();
            $citaEnriquecida = $this->enrichCitaData($data, $citaId);

            //   Enviar notificación de WhatsApp
            try {
                $datosNotificacion = $this->obtenerDatosParaNotificacion($citaId, $data);

                if ($datosNotificacion && isset($datosNotificacion['telefono'])) {
                    Log::info('Enviando WhatsApp de confirmación', [
                        'cita_id' => $citaId,
                        'telefono' => $datosNotificacion['telefono']
                    ]);

                    $this->whatsappService->enviarNotificacionCitaAgendada(
                        $datosNotificacion['telefono'],
                        $datosNotificacion
                    );
                } else {
                    Log::warning('No se pudo enviar WhatsApp: teléfono no encontrado', [
                        'cita_id' => $citaId,
                        'cliente_id' => $data['cliente_id'] ?? null
                    ]);
                }
            } catch (\Exception $e) {
                // No fallar la creación de la cita si falla el WhatsApp
                Log::error(' Error al enviar WhatsApp: ' . $e->getMessage());
            }

            // Programar recordatorio
            $this->programarRecordatorio($citaId, $data);

            return response()->json([
                'success' => true,
                'message' => 'Cita creada exitosamente. Se ha enviado una notificación por WhatsApp.',
                'id' => $citaId,
                'data' => $citaEnriquecida
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear cita: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Mostrar una cita específica
    public function show($id)
    {
        try {
            $cita = $this->database->getReference("citas/{$id}")->getValue();

            if (!$cita) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cita no encontrada'
                ], 404);
            }

            $citaEnriquecida = $this->enrichCitaData($cita, $id);

            return response()->json([
                'success' => true,
                'data' => $citaEnriquecida
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Actualizar una cita
    public function update(Request $request, $id)
    {
        try {
            $ref = $this->database->getReference("citas/{$id}");
            $exists = $ref->getValue();

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cita no encontrada'
                ], 404);
            }

            // Usar validateUpdate para permitir actualizaciones parciales
            $validator = \App\Models\Cita::validateUpdate($request->all());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();
            $data['updated_at'] = now()->toIso8601String();

            // Si se está marcando como pagada, agregar fecha de pago automáticamente
            if (isset($data['pagado']) && $data['pagado'] === true && !isset($data['fecha_pago'])) {
                $data['fecha_pago'] = now()->toDateString();
            }

            // Si se marca como no pagada, limpiar fecha de pago
            if (isset($data['pagado']) && $data['pagado'] === false) {
                $data['fecha_pago'] = null;
            }

            $ref->update($data);

            $citaActualizada = array_merge($exists, $data);
            $citaEnriquecida = $this->enrichCitaData($citaActualizada, $id);

            // Log para debugging
            \Log::info('Cita actualizada', [
                'cita_id' => $id,
                'pagado' => $data['pagado'] ?? null,
                'fecha_pago' => $data['fecha_pago'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cita actualizada exitosamente',
                'data' => $citaEnriquecida
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar cita: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cita',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Eliminar una cita
    public function destroy($id)
    {
        try {
            $ref = $this->database->getReference("citas/{$id}");
            $exists = $ref->getValue();

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cita no encontrada'
                ], 404);
            }

            $ref->remove();

            return response()->json([
                'success' => true,
                'message' => 'Cita eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Cancelar una cita con notificación
    public function cancelar(Request $request, $id)
    {
        try {
            $ref = $this->database->getReference("citas/{$id}");
            $cita = $ref->getValue();

            if (!$cita) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cita no encontrada'
                ], 404);
            }

            // Actualizar estado
            $ref->update([
                'estado' => 'cancelada',
                'motivo_cancelacion' => $request->input('motivo', 'Cancelada por el cliente'),
                'fecha_cancelacion' => now()->toIso8601String()
            ]);

            // Enviar notificación de cancelación
            try {
                $datosNotificacion = $this->obtenerDatosParaNotificacion($id, $cita);

                if ($datosNotificacion && isset($datosNotificacion['telefono'])) {
                    Log::info('📱 Enviando WhatsApp de cancelación', [
                        'cita_id' => $id,
                        'telefono' => $datosNotificacion['telefono']
                    ]);

                    $this->whatsappService->enviarNotificacionCitaCancelada(
                        $datosNotificacion['telefono'],
                        $datosNotificacion
                    );
                }
            } catch (\Exception $e) {
                Log::error('Error al enviar WhatsApp de cancelación: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Cita cancelada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error(' Error al cancelar cita: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la cita',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ✨ NUEVO: Obtener datos completos para notificación
    protected function obtenerDatosParaNotificacion($citaId, $citaData)
    {
        try {
            $clienteId = $citaData['cliente_id'] ?? $citaData['ownerUid'] ?? null;

            if (!$clienteId) {
                Log::warning('No se encontró cliente_id en la cita', ['cita_id' => $citaId]);
                return null;
            }

            // Obtener datos del cliente
            $cliente = $this->database->getReference("usuarios/{$clienteId}")->getValue();

            if (!$cliente) {
                Log::warning('Cliente no encontrado', ['cliente_id' => $clienteId]);
                return null;
            }

            // Verificar que tenga teléfono
            $telefono = $cliente['telefono'] ?? null;

            if (!$telefono) {
                Log::warning('Cliente sin teléfono', [
                    'cliente_id' => $clienteId,
                    'nombre' => $cliente['nombre_completo'] ?? 'Sin nombre'
                ]);
                return null;
            }

            // Obtener datos de la mascota
            $mascotaId = $citaData['mascota_id'] ?? null;
            $nombreMascota = 'Mascota';

            if ($mascotaId) {
                $mascota = $this->database
                    ->getReference("clientes/{$clienteId}/pacientes/{$mascotaId}")
                    ->getValue();

                if ($mascota) {
                    $nombreMascota = $mascota['nombre_mascota'] ?? 'Mascota';
                }
            }

            // Obtener datos del veterinario
            $veterinarioId = $citaData['veterinario_id'] ?? null;
            $nombreVeterinario = 'Veterinario';

            if ($veterinarioId) {
                $veterinario = $this->database->getReference("usuarios/{$veterinarioId}")->getValue();
                if ($veterinario) {
                    $nombreVeterinario = $veterinario['nombre_completo'] ?? 'Veterinario';
                }
            }

            return [
                'telefono' => $telefono,
                'cliente' => $cliente['nombre_completo'] ?? 'Cliente',
                'mascota' => $nombreMascota,
                'fecha' => $citaData['fecha'] ?? date('Y-m-d'),
                'hora' => $citaData['hora'] ?? '00:00',
                'tipo_servicio' => $this->formatearTipoServicio($citaData['tipo_servicio'] ?? ''),
                'veterinario' => $nombreVeterinario,
                'valor' => $citaData['valor'] ?? 0,
            ];

        } catch (\Exception $e) {
            Log::error(' Error al obtener datos para notificación: ' . $e->getMessage());
            return null;
        }
    }

    // Programar recordatorio
    protected function programarRecordatorio($citaId, $citaData)
    {
        try {
            $this->database->getReference("citas/{$citaId}")
                ->update([
                    'recordatorio_enviado' => false,
                    'requiere_recordatorio' => true
                ]);

            Log::info(' Recordatorio programado', ['cita_id' => $citaId]);
        } catch (\Exception $e) {
            Log::error(' Error al programar recordatorio: ' . $e->getMessage());
        }
    }

    // Obtener citas que requieren recordatorio
    public function obtenerCitasParaRecordatorio()
    {
        try {
            $snapshot = $this->database->getReference('citas')->getSnapshot();
            $citas = $snapshot->getValue() ?? [];

            $citasParaRecordar = [];
            $ahora = now();

            foreach ($citas as $id => $cita) {
                // Solo citas confirmadas o pendientes
                if (!in_array($cita['estado'] ?? '', ['pendiente', 'confirmada'])) {
                    continue;
                }

                // Verificar si ya se envió el recordatorio
                if (isset($cita['recordatorio_enviado']) && $cita['recordatorio_enviado'] === true) {
                    continue;
                }

                // Calcular si la cita es en aproximadamente 1 hora
                $fechaHoraCita = \Carbon\Carbon::parse($cita['fecha'] . ' ' . $cita['hora']);
                $diferenciaMinutos = $fechaHoraCita->diffInMinutes($ahora, false);

                // Si la cita es entre 55 y 65 minutos en el futuro
                if ($diferenciaMinutos >= -65 && $diferenciaMinutos <= -55) {
                    $citasParaRecordar[] = [
                        'id' => $id,
                        'data' => $cita
                    ];
                }
            }

            return $citasParaRecordar;

        } catch (\Exception $e) {
            Log::error('Error al obtener citas para recordatorio: ' . $e->getMessage());
            return [];
        }
    }

    // Enviar recordatorios programados
    public function enviarRecordatorios()
    {
        try {
            $citasParaRecordar = $this->obtenerCitasParaRecordatorio();
            $enviados = 0;

            Log::info(" Procesando recordatorios: " . count($citasParaRecordar) . " citas encontradas");

            foreach ($citasParaRecordar as $cita) {
                $datosNotificacion = $this->obtenerDatosParaNotificacion($cita['id'], $cita['data']);

                if ($datosNotificacion && isset($datosNotificacion['telefono'])) {
                    $resultado = $this->whatsappService->enviarRecordatorioCita(
                        $datosNotificacion['telefono'],
                        $datosNotificacion
                    );

                    if ($resultado) {
                        // Marcar recordatorio como enviado
                        $this->database->getReference("citas/{$cita['id']}")
                            ->update(['recordatorio_enviado' => true]);
                        $enviados++;

                        Log::info("Recordatorio enviado", [
                            'cita_id' => $cita['id'],
                            'telefono' => $datosNotificacion['telefono']
                        ]);
                    }
                }
            }

            Log::info("Recordatorios enviados: {$enviados} de " . count($citasParaRecordar));

            return response()->json([
                'success' => true,
                'message' => "{$enviados} recordatorios enviados exitosamente",
                'total_procesados' => count($citasParaRecordar)
            ]);

        } catch (\Exception $e) {
            Log::error(' Error al enviar recordatorios: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar recordatorios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Formatear tipo de servicio
    protected function formatearTipoServicio($tipo)
    {
        $tipos = [
            'consulta_general' => 'Consulta General',
            'vacunacion' => 'Vacunación',
            'cirugia' => 'Cirugía',
            'emergencia' => 'Emergencia',
            'control' => 'Control',
            'desparasitacion' => 'Desparasitación',
            'estetica' => 'Estética',
            'otro' => 'Otro'
        ];

        return $tipos[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo));
    }

    // Obtener citas del día para un veterinario
    public function citasDelDia(Request $request)
    {
        try {
            $veterinarioId = $request->query('veterinario_id');
            $fecha = $request->query('fecha', date('Y-m-d'));

            if (!$veterinarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'veterinario_id es requerido'
                ], 400);
            }

            $citas = $this->database->getReference('citas')->getValue();

            if (!$citas) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $citasDelDia = [];
            foreach ($citas as $key => $cita) {
                if (($cita['veterinario_id'] ?? '') === $veterinarioId &&
                    ($cita['fecha'] ?? '') === $fecha) {
                    $citaEnriquecida = $this->enrichCitaData($cita, $key);
                    $citasDelDia[] = $citaEnriquecida;
                }
            }

            // Ordenar por hora
            usort($citasDelDia, function($a, $b) {
                return strcmp($a['hora'] ?? '00:00', $b['hora'] ?? '00:00');
            });

            return response()->json([
                'success' => true,
                'data' => $citasDelDia
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Obtener estadísticas para veterinario
    public function estadisticasVeterinario(Request $request)
    {
        try {
            $veterinarioId = $request->query('veterinario_id');

            if (!$veterinarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'veterinario_id es requerido'
                ], 400);
            }

            $citas = $this->database->getReference('citas')->getValue();

            if (!$citas) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total' => 0,
                        'hoy' => 0,
                        'pendientes' => 0,
                        'completadas' => 0,
                        'confirmadas' => 0
                    ]
                ]);
            }

            $hoy = date('Y-m-d');
            $stats = [
                'total' => 0,
                'hoy' => 0,
                'pendientes' => 0,
                'completadas' => 0,
                'confirmadas' => 0
            ];

            foreach ($citas as $cita) {
                if (($cita['veterinario_id'] ?? '') === $veterinarioId) {
                    $stats['total']++;

                    if (($cita['fecha'] ?? '') === $hoy) {
                        $stats['hoy']++;
                    }

                    $estado = $cita['estado'] ?? '';
                    if ($estado === 'pendiente') $stats['pendientes']++;
                    if ($estado === 'completada') $stats['completadas']++;
                    if ($estado === 'confirmada') $stats['confirmadas']++;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    //  Obtener horarios disponibles
    public function horariosDisponibles(Request $request)
    {
        try {
            $fecha = $request->query('fecha');
            $veterinarioId = $request->query('veterinario_id');

            if (!$fecha || !$veterinarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'fecha y veterinario_id son requeridos'
                ], 400);
            }

            $citas = $this->database->getReference('citas')->getValue();
            $horariosOcupados = [];

            if ($citas) {
                foreach ($citas as $cita) {
                    if (($cita['fecha'] ?? '') === $fecha &&
                        ($cita['veterinario_id'] ?? '') === $veterinarioId &&
                        in_array($cita['estado'] ?? '', ['pendiente', 'confirmada', 'en_atencion'])) {
                        $horariosOcupados[] = $cita['hora'];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'horariosOcupados' => array_values(array_unique($horariosOcupados))
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
