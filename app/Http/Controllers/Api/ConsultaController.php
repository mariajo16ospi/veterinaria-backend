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

        // Usar Realtime Database en lugar de Firestore
        $this->database = $factory->createDatabase();
    }

     // Listar todas las consultas de una cita
    public function index($citaId)
    {
        try {
            $consultas = $this->database->getReference("citas/{$citaId}/consultas")->getValue();

            return response()->json([
                'success' => true,
                'data' => $consultas ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Crear una consulta
    public function store(Request $request, $citaId)
    {
        try {
            $data = $request->all();
            $data['created_at'] = now()->toIso8601String();

            $newConsulta = $this->database
                ->getReference("citas/{$citaId}/consultas")
                ->push($data);

            return response()->json([
                'success' => true,
                'message' => 'Consulta creada exitosamente',
                'id' => $newConsulta->getKey()
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Mostrar una consulta
    public function show($citaId, $consultaId)
    {
        try {
            $consulta = $this->database
                ->getReference("citas/{$citaId}/consultas/{$consultaId}")
                ->getValue();

            if (!$consulta) {
                return response()->json(['success' => false, 'message' => 'Consulta no encontrada'], 404);
            }

            return response()->json(['success' => true, 'data' => $consulta]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Actualizar una consulta
    public function update(Request $request, $citaId, $consultaId)
    {
        try {
            $data = $request->all();
            $data['updated_at'] = now()->toIso8601String();

            $this->database
                ->getReference("citas/{$citaId}/consultas/{$consultaId}")
                ->update($data);

            return response()->json(['success' => true, 'message' => 'Consulta actualizada exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    //Eliminar una consulta
    public function destroy($citaId, $consultaId)
    {
        try {
            $this->database
                ->getReference("citas/{$citaId}/consultas/{$consultaId}")
                ->remove();

            return response()->json(['success' => true, 'message' => 'Consulta eliminada exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
