<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;

class ConsultaController extends Controller
{
    protected $database;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(base_path('storage/app/firebase/firebase-credentials.json'))
            ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));

        $this->database = $factory->createDatabase();
    }

    // Método auxiliar para crear entrada en historial clínico
    private function agregarAHistorialClinico($consulta, $consultaId, $citaId)
    {
        try {
            $clienteId = $consulta['cliente_id'] ?? null;
            $mascotaId = $consulta['mascota_id'] ?? null;

            if (!$clienteId || !$mascotaId) {
                \Log::warning("No se pudo agregar a historial: faltan cliente_id o mascota_id");
                return;
            }

            // Obtener información adicional de la cita si es necesario
            $cita = $this->database->getReference("citas/{$citaId}")->getValue();
            $veterinario = null;

            if (isset($consulta['veterinario_id'])) {
                $veterinario = $this->database
                    ->getReference("usuarios/{$consulta['veterinario_id']}")
                    ->getValue();
            }

            // Construir el detalle del historial
            $detalle = $this->construirDetalleHistorial($consulta, $cita, $veterinario);

            // Datos para el historial
            $historialData = [
                'fecha' => $consulta['fecha_consulta'] ?? date('Y-m-d'),
                'hora' => $consulta['hora_consulta'] ?? date('H:i'),
                'tipo' => 'consulta',
                'consulta_id' => $consultaId,
                'cita_id' => $citaId,
                'veterinario_id' => $consulta['veterinario_id'] ?? '',
                'veterinario_nombre' => $veterinario['nombre'] ?? 'No especificado',
                'motivo' => $consulta['motivo_consulta'] ?? '',
                'diagnostico' => $consulta['diagnostico_presuntivo'] ?? '',
                'tratamiento' => $consulta['tratamiento'] ?? '',
                'detalle' => $detalle,
                'temperatura' => $consulta['temperatura'] ?? null,
                'peso' => $consulta['peso'] ?? null,
                'created_at' => now()->toIso8601String(),
                'paciente_id' => $mascotaId
            ];

            // Guardar en el historial
            $this->database
                ->getReference("clientes/{$clienteId}/pacientes/{$mascotaId}/historiales")
                ->push($historialData);

            \Log::info("Historial creado exitosamente para mascota {$mascotaId}");

        } catch (\Exception $e) {
            \Log::error("Error al agregar a historial clínico: " . $e->getMessage());
        }
    }

    // Construir detalle legible del historial
    private function construirDetalleHistorial($consulta, $cita, $veterinario)
    {
        $detalle = "CONSULTA MÉDICA\n\n";

        // Fecha y hora
        $detalle .= "Fecha: " . ($consulta['fecha_consulta'] ?? 'No especificada') . "\n";
        $detalle .= "Hora: " . ($consulta['hora_consulta'] ?? 'No especificada') . "\n";
        $detalle .= "Veterinario: " . ($veterinario['nombre'] ?? 'No especificado') . "\n\n";

        // Motivo
        $detalle .= "MOTIVO DE CONSULTA:\n";
        $detalle .= $consulta['motivo_consulta'] ?? 'No especificado';
        $detalle .= "\n\n";

        // Síntomas
        if (!empty($consulta['sintomas'])) {
            $detalle .= "SÍNTOMAS Y ANAMNESIS:\n";
            $detalle .= $consulta['sintomas'] . "\n\n";
        }

        // Signos vitales
        if (!empty($consulta['temperatura']) || !empty($consulta['peso']) ||
            !empty($consulta['frecuencia_cardiaca']) || !empty($consulta['frecuencia_respiratoria'])) {
            $detalle .= "SIGNOS VITALES:\n";

            if (!empty($consulta['temperatura'])) {
                $detalle .= "- Temperatura: " . $consulta['temperatura'] . "°C\n";
            }
            if (!empty($consulta['peso'])) {
                $detalle .= "- Peso: " . $consulta['peso'] . " kg\n";
            }
            if (!empty($consulta['frecuencia_cardiaca'])) {
                $detalle .= "- Frecuencia Cardíaca: " . $consulta['frecuencia_cardiaca'] . " lpm\n";
            }
            if (!empty($consulta['frecuencia_respiratoria'])) {
                $detalle .= "- Frecuencia Respiratoria: " . $consulta['frecuencia_respiratoria'] . " rpm\n";
            }
            $detalle .= "\n";
        }

        // Examen físico
        if (!empty($consulta['examen_fisico'])) {
            $detalle .= "EXAMEN FÍSICO:\n";
            $detalle .= $consulta['examen_fisico'] . "\n\n";
        }

        // Diagnóstico
        $detalle .= "DIAGNÓSTICO:\n";
        $detalle .= $consulta['diagnostico_presuntivo'] ?? 'No especificado';
        $detalle .= "\n\n";

        // Tratamiento
        $detalle .= "TRATAMIENTO:\n";
        $detalle .= $consulta['tratamiento'] ?? 'No especificado';
        $detalle .= "\n\n";

        // Observaciones
        if (!empty($consulta['observaciones'])) {
            $detalle .= "OBSERVACIONES:\n";
            $detalle .= $consulta['observaciones'] . "\n\n";
        }

        // Recomendaciones
        if (!empty($consulta['recomendaciones'])) {
            $detalle .= "RECOMENDACIONES:\n";
            $detalle .= $consulta['recomendaciones'] . "\n\n";
        }

        // Próxima cita
        if (!empty($consulta['proxima_cita'])) {
            $detalle .= "PRÓXIMA CITA: " . $consulta['proxima_cita'] . "\n";
        }

        return $detalle;
    }

    // Listar todas las consultas de una cita
    public function index($citaId)
    {
        try {
            $consultas = $this->database->getReference("citas/{$citaId}/consultas")->getValue();

            if (!$consultas) {
                return response()->json(['success' => true, 'data' => []]);
            }

            // Convertir a array con IDs
            $consultasArray = [];
            foreach ($consultas as $key => $consulta) {
                $consulta['id'] = $key;
                $consultasArray[] = $consulta;
            }

            return response()->json([
                'success' => true,
                'data' => $consultasArray
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Crear una consulta
    public function store(Request $request, $citaId)
    {
        try {
            // Verificar que la cita existe
            $cita = $this->database->getReference("citas/{$citaId}")->getValue();

            if (!$cita) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cita no encontrada'
                ], 404);
            }

            $data = $request->all();
            $data['created_at'] = now()->toIso8601String();

            // Crear la consulta
            $newConsulta = $this->database
                ->getReference("citas/{$citaId}/consultas")
                ->push($data);

            $consultaId = $newConsulta->getKey();

            // ✨ AGREGAR A HISTORIAL CLÍNICO AUTOMÁTICAMENTE
            $this->agregarAHistorialClinico($data, $consultaId, $citaId);

            // Actualizar el estado de la cita a 'completada'
            $this->database
                ->getReference("citas/{$citaId}")
                ->update([
                    'estado' => 'completada',
                    'updated_at' => now()->toIso8601String()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Consulta creada exitosamente y agregada al historial clínico',
                'id' => $consultaId,
                'data' => array_merge(['id' => $consultaId], $data)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Mostrar una consulta
    public function show($citaId, $consultaId)
    {
        try {
            $cita = $this->database->getReference("citas/{$citaId}")->getValue();

            if (!$cita) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cita no encontrada'
                ], 404);
            }

            $consulta = $this->database
                ->getReference("citas/{$citaId}/consultas/{$consultaId}")
                ->getValue();

            if (!$consulta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Consulta no encontrada'
                ], 404);
            }

            $consulta['id'] = $consultaId;

            return response()->json([
                'success' => true,
                'data' => $consulta
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Actualizar una consulta
    public function update(Request $request, $citaId, $consultaId)
    {
        try {
            $cita = $this->database->getReference("citas/{$citaId}")->getValue();

            if (!$cita) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cita no encontrada'
                ], 404);
            }

            $ref = $this->database->getReference("citas/{$citaId}/consultas/{$consultaId}");
            $exists = $ref->getValue();

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Consulta no encontrada'
                ], 404);
            }

            $data = $request->all();
            $data['updated_at'] = now()->toIso8601String();

            $ref->update($data);

            // Actualizar también el historial clínico si existe
            $this->actualizarHistorialClinico($exists, $data, $consultaId, $citaId);

            return response()->json([
                'success' => true,
                'message' => 'Consulta actualizada exitosamente',
                'data' => array_merge($exists, $data)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Actualizar historial clínico cuando se actualiza una consulta
    private function actualizarHistorialClinico($consultaOriginal, $dataActualizada, $consultaId, $citaId)
    {
        try {
            $clienteId = $consultaOriginal['cliente_id'] ?? null;
            $mascotaId = $consultaOriginal['mascota_id'] ?? null;

            if (!$clienteId || !$mascotaId) {
                return;
            }

            // Buscar el registro del historial que corresponde a esta consulta
            $historiales = $this->database
                ->getReference("clientes/{$clienteId}/pacientes/{$mascotaId}/historiales")
                ->getValue();

            if ($historiales) {
                foreach ($historiales as $historialId => $historial) {
                    if (isset($historial['consulta_id']) && $historial['consulta_id'] === $consultaId) {
                        // Actualizar el historial
                        $consultaMerged = array_merge($consultaOriginal, $dataActualizada);

                        $cita = $this->database->getReference("citas/{$citaId}")->getValue();
                        $veterinario = null;

                        if (isset($consultaMerged['veterinario_id'])) {
                            $veterinario = $this->database
                                ->getReference("usuarios/{$consultaMerged['veterinario_id']}")
                                ->getValue();
                        }

                        $detalle = $this->construirDetalleHistorial($consultaMerged, $cita, $veterinario);

                        $this->database
                            ->getReference("clientes/{$clienteId}/pacientes/{$mascotaId}/historiales/{$historialId}")
                            ->update([
                                'detalle' => $detalle,
                                'diagnostico' => $consultaMerged['diagnostico_presuntivo'] ?? '',
                                'tratamiento' => $consultaMerged['tratamiento'] ?? '',
                                'updated_at' => now()->toIso8601String()
                            ]);

                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error("Error al actualizar historial clínico: " . $e->getMessage());
        }
    }

    // Eliminar una consulta
    public function destroy($citaId, $consultaId)
    {
        try {
            $cita = $this->database->getReference("citas/{$citaId}")->getValue();

            if (!$cita) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cita no encontrada'
                ], 404);
            }

            $ref = $this->database->getReference("citas/{$citaId}/consultas/{$consultaId}");
            $consulta = $ref->getValue();

            if (!$consulta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Consulta no encontrada'
                ], 404);
            }

            // Eliminar también del historial clínico
            $this->eliminarDeHistorialClinico($consulta, $consultaId);

            $ref->remove();

            return response()->json([
                'success' => true,
                'message' => 'Consulta eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Eliminar del historial clínico
    private function eliminarDeHistorialClinico($consulta, $consultaId)
    {
        try {
            $clienteId = $consulta['cliente_id'] ?? null;
            $mascotaId = $consulta['mascota_id'] ?? null;

            if (!$clienteId || !$mascotaId) {
                return;
            }

            $historiales = $this->database
                ->getReference("clientes/{$clienteId}/pacientes/{$mascotaId}/historiales")
                ->getValue();

            if ($historiales) {
                foreach ($historiales as $historialId => $historial) {
                    if (isset($historial['consulta_id']) && $historial['consulta_id'] === $consultaId) {
                        $this->database
                            ->getReference("clientes/{$clienteId}/pacientes/{$mascotaId}/historiales/{$historialId}")
                            ->remove();
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error("Error al eliminar de historial clínico: " . $e->getMessage());
        }
    }
}
