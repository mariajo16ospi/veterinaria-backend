<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;

class MedicamentoController extends Controller
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
            $medicamentos = $this->database
                ->getReference("citas/$citaId/consultas/$consultaId/medicamentos")
                ->getValue();

            return response()->json(['success' => true, 'data' => $medicamentos ?? []]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request, $citaId, $consultaId)
    {
        try {
            $data = $request->all();
            $data['created_at'] = now()->toIso8601String();

            $ref = $this->database
                ->getReference("citas/$citaId/consultas/$consultaId/medicamentos")
                ->push($data);

            return response()->json([
                'success' => true,
                'message' => 'Medicamento creado exitosamente',
                'id' => $ref->getKey()
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($citaId, $consultaId, $medicamentoId)
    {
        try {
            $medicamento = $this->database
                ->getReference("citas/$citaId/consultas/$consultaId/medicamentos/$medicamentoId")
                ->getValue();

            if (!$medicamento) {
                return response()->json(['success' => false, 'message' => 'Medicamento no encontrado'], 404);
            }

            return response()->json(['success' => true, 'data' => $medicamento]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $citaId, $consultaId, $medicamentoId)
    {
        try {
            $data = $request->all();
            $data['updated_at'] = now()->toIso8601String();

            $this->database
                ->getReference("citas/$citaId/consultas/$consultaId/medicamentos/$medicamentoId")
                ->update($data);

            return response()->json(['success' => true, 'message' => 'Medicamento actualizado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($citaId, $consultaId, $medicamentoId)
    {
        try {
            $this->database
                ->getReference("citas/$citaId/consultas/$consultaId/medicamentos/$medicamentoId")
                ->remove();

            return response()->json(['success' => true, 'message' => 'Medicamento eliminado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
