<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\DatabaseException;

// use App\Models\Paciente;
// use Illuminate\Support\Facades\Hash;

class PacienteController extends Controller
{
    protected $database;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(base_path('storage/app/firebase/firebase-credentials.json'))
            ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));

        // Usar Realtime Database en lugar de Firestore
        $this->database = $factory->createDatabase();
    }

     /**
     * Muestra todos los pacientes de un dueño específico
     */
    public function index($duenoId)
    {
        try {
            $reference = $this->database->getReference("duenos/{$duenoId}/pacientes");
            $pacientes = $reference->getValue();

            return response()->json([
                'success' => true,
                'data' => $pacientes ?? []
            ]);
        } catch (DatabaseException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Crea un nuevo paciente para un dueño
     */
    public function store(Request $request, $duenoId)
    {
        try {
            $data = $request->all();
            $data['created_at'] = now()->toIso8601String();

            $newPaciente = $this->database
                ->getReference("duenos/{$duenoId}/pacientes")
                ->push($data);

            return response()->json([
                'success' => true,
                'message' => 'Paciente creado exitosamente',
                'id' => $newPaciente->getKey()
            ], 201);
        } catch (DatabaseException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Muestra un paciente específico de un dueño
     */
    public function show($duenoId, $pacienteId)
    {
        try {
            $snapshot = $this->database
                ->getReference("duenos/{$duenoId}/pacientes/{$pacienteId}")
                ->getValue();

            if (!$snapshot) {
                return response()->json(['success' => false, 'message' => 'Paciente no encontrado'], 404);
            }

            return response()->json(['success' => true, 'data' => $snapshot]);
        } catch (DatabaseException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Actualiza los datos de un paciente
     */
    public function update(Request $request, $duenoId, $pacienteId)
    {
        try {
            $reference = $this->database->getReference("duenos/{$duenoId}/pacientes/{$pacienteId}");

            if (!$reference->getValue()) {
                return response()->json(['success' => false, 'message' => 'Paciente no encontrado'], 404);
            }

            $data = $request->all();
            $data['updated_at'] = now()->toIso8601String();

            $reference->update($data);

            return response()->json(['success' => true, 'message' => 'Paciente actualizado exitosamente']);
        } catch (DatabaseException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /*Elimina un paciente de un dueño*/
    public function destroy($duenoId, $pacienteId)
    {
        try {
            $reference = $this->database->getReference("duenos/{$duenoId}/pacientes/{$pacienteId}");

            if (!$reference->getValue()) {
                return response()->json(['success' => false, 'message' => 'Paciente no encontrado'], 404);
            }

            $reference->remove();

            return response()->json(['success' => true, 'message' => 'Paciente eliminado exitosamente']);
        } catch (DatabaseException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
