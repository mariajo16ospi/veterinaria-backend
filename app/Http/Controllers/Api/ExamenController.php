<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;

class ExamenController extends Controller
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

    public function index($citaId, $consultaId)
    {
        try {
            $examenesRef = $this->database
                ->getReference("citas/{$citaId}/consultas/{$consultaId}/examenes")
                ->getValue();

            if (!$examenesRef) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $data = [];
            foreach ($examenesRef as $id => $examen) {
                $data[] = array_merge(['id' => $id], $examen);
            }

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request, $citaId, $consultaId)
    {
        try {
            $data = $request->all();
            $data['created_at'] = now()->toIso8601String();

            $newExamen = $this->database
                ->getReference("citas/{$citaId}/consultas/{$consultaId}/examenes")
                ->push($data);

            return response()->json([
                'success' => true,
                'message' => 'Examen creado exitosamente',
                'id' => $newExamen->getKey()
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($citaId, $consultaId, $examenId)
    {
        try {
            $examen = $this->database
                ->getReference("citas/{$citaId}/consultas/{$consultaId}/examenes/{$examenId}")
                ->getValue();

            if (!$examen) {
                return response()->json(['success' => false, 'message' => 'Examen no encontrado'], 404);
            }

            return response()->json(['success' => true, 'data' => array_merge(['id' => $examenId], $examen)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $citaId, $consultaId, $examenId)
    {
        try {
            $data = $request->all();
            $data['updated_at'] = now()->toIso8601String();

            $this->database
                ->getReference("citas/{$citaId}/consultas/{$consultaId}/examenes/{$examenId}")
                ->update($data);

            return response()->json(['success' => true, 'message' => 'Examen actualizado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($citaId, $consultaId, $examenId)
    {
        try {
            $this->database
                ->getReference("citas/{$citaId}/consultas/{$consultaId}/examenes/{$examenId}")
                ->remove();

            return response()->json(['success' => true, 'message' => 'Examen eliminado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
