<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;

class HistorialController extends Controller
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

    public function index($duenoId, $pacienteId)
    {
        try {
            $historiales = $this->database
                ->getReference("duenos/$duenoId/pacientes/$pacienteId/historiales")
                ->getValue();

            return response()->json([
                'success' => true,
                'data' => $historiales ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request, $duenoId, $pacienteId)
    {
        try {
            $data = $request->all();
            $data['paciente_id'] = $pacienteId;
            $data['created_at'] = now()->toIso8601String();

            $ref = $this->database
                ->getReference("duenos/$duenoId/pacientes/$pacienteId/historiales")
                ->push($data);

            return response()->json([
                'success' => true,
                'message' => 'Historial creado exitosamente',
                'id' => $ref->getKey()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($duenoId, $pacienteId, $historialId)
    {
        try {
            $historial = $this->database
                ->getReference("duenos/$duenoId/pacientes/$pacienteId/historiales/$historialId")
                ->getValue();

            if (!$historial) {
                return response()->json([
                    'success' => false,
                    'message' => 'Historial no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $historial
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($duenoId, $pacienteId, $historialId)
    {
        try {
            $this->database
                ->getReference("duenos/$duenoId/pacientes/$pacienteId/historiales/$historialId")
                ->remove();

            return response()->json([
                'success' => true,
                'message' => 'Historial eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
