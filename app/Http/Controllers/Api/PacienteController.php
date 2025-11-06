<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\DatabaseException;

class PacienteController extends Controller
{
    protected $database;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(base_path('storage/app/firebase/firebase-credentials.json'))
            ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));

        $this->database = $factory->createDatabase();
    }

    /**
     * Muestra todos los pacientes de un cliente específico
     */
    public function index($clienteId)
    {
        try {
            Log::info('📋 Listando pacientes para cliente: ' . $clienteId);

            $reference = $this->database->getReference('clientes/' . $clienteId . '/pacientes');
            $snapshot = $reference->getValue();

            Log::info('📦 Snapshot obtenido:', ['data' => $snapshot ? 'con datos' : 'vacío']);

            if (!$snapshot) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No hay pacientes registrados'
                ], 200);
            }

            $pacientes = [];
            foreach ($snapshot as $key => $value) {
                $pacientes[] = array_merge(['id' => $key], $value);
            }

            Log::info('✅ Total pacientes encontrados: ' . count($pacientes));

            return response()->json([
                'success' => true,
                'data' => $pacientes
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error al listar pacientes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error al obtener los pacientes'
            ], 500);
        }
    }

    /**
     * Crea un nuevo paciente para un cliente
     */
    public function store(Request $request, $clienteId)
    {
        try {
            Log::info('➕ Creando paciente para cliente: ' . $clienteId);
            Log::info('📝 Datos recibidos:', $request->all());

            $data = $request->validate([
                'nombre_mascota' => 'required|string',
                'especie' => 'required|string',
                'raza' => 'nullable|string',
                'color' => 'nullable|string',
                'sexo' => 'required|string',
                'fecha_nacimiento' => 'required|string',
            ]);

            $data['cliente_id'] = $clienteId;
            $data['created_at'] = now()->toIso8601String();

            $newPaciente = $this->database
                ->getReference("clientes/{$clienteId}/pacientes")
                ->push($data);

            $pacienteId = $newPaciente->getKey();

            Log::info('✅ Paciente creado con ID: ' . $pacienteId);

            return response()->json([
                'success' => true,
                'message' => 'Paciente creado exitosamente',
                'data' => array_merge(['id' => $pacienteId], $data)
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Datos inválidos'
            ], 422);
        } catch (DatabaseException $e) {
            Log::error('❌ Error al crear paciente: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el paciente'
            ], 500);
        }
    }

    /**
     * Muestra un paciente específico de un cliente
     */
    public function show($clienteId, $pacienteId)
    {
        try {
            $snapshot = $this->database
                ->getReference("clientes/{$clienteId}/pacientes/{$pacienteId}")
                ->getValue();

            if (!$snapshot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paciente no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => array_merge(['id' => $pacienteId], $snapshot)
            ], 200);

        } catch (DatabaseException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualiza los datos de un paciente
     */
    public function update(Request $request, $clienteId, $pacienteId)
    {
        try {
            Log::info('✏️ Actualizando paciente: ' . $pacienteId . ' del cliente: ' . $clienteId);

            $reference = $this->database->getReference("clientes/{$clienteId}/pacientes/{$pacienteId}");

            if (!$reference->getValue()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paciente no encontrado'
                ], 404);
            }

            $data = $request->validate([
                'nombre_mascota' => 'required|string',
                'especie' => 'required|string',
                'raza' => 'nullable|string',
                'color' => 'nullable|string',
                'sexo' => 'required|string',
                'fecha_nacimiento' => 'required|string',
            ]);

            $data['cliente_id'] = $clienteId;
            $data['updated_at'] = now()->toIso8601String();

            $reference->update($data);

            Log::info('✅ Paciente actualizado correctamente');

            return response()->json([
                'success' => true,
                'message' => 'Paciente actualizado exitosamente',
                'data' => array_merge(['id' => $pacienteId], $data)
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Datos inválidos'
            ], 422);
        } catch (DatabaseException $e) {
            Log::error('❌ Error al actualizar paciente: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el paciente'
            ], 500);
        }
    }

    /**
     * Elimina un paciente de un cliente
     */
    public function destroy($clienteId, $pacienteId)
    {
        try {
            Log::info('🗑️ Eliminando paciente: ' . $pacienteId . ' del cliente: ' . $clienteId);

            $reference = $this->database->getReference("clientes/{$clienteId}/pacientes/{$pacienteId}");

            if (!$reference->getValue()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paciente no encontrado'
                ], 404);
            }

            $reference->remove();

            Log::info('✅ Paciente eliminado correctamente');

            return response()->json([
                'success' => true,
                'message' => 'Paciente eliminado exitosamente'
            ], 200);

        } catch (DatabaseException $e) {
            Log::error('❌ Error al eliminar paciente: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el paciente'
            ], 500);
        }
    }
}
