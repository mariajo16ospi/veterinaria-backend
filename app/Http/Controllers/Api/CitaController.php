<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;

class CitaController extends Controller
{
    protected $database;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(base_path('storage/app/firebase/firebase-credentials.json'))
            ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));

        $this->database = $factory->createDatabase();
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

    // Crear una cita
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['created_at'] = now()->toIso8601String();
            $data['estado'] = $data['estado'] ?? 'pendiente';

            $newCita = $this->database
                ->getReference('citas')
                ->push($data);

            $citaId = $newCita->getKey();
            $citaEnriquecida = $this->enrichCitaData($data, $citaId);

            return response()->json([
                'success' => true,
                'message' => 'Cita creada exitosamente',
                'id' => $citaId,
                'data' => $citaEnriquecida
            ], 201);
        } catch (\Exception $e) {
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

            $data = $request->all();
            $data['updated_at'] = now()->toIso8601String();

            $ref->update($data);

            $citaActualizada = array_merge($exists, $data);
            $citaEnriquecida = $this->enrichCitaData($citaActualizada, $id);

            return response()->json([
                'success' => true,
                'message' => 'Cita actualizada exitosamente',
                'data' => $citaEnriquecida
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
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
}
