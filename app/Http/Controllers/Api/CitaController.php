<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        // Usar Realtime Database en lugar de Firestore
        $this->database = $factory->createDatabase();
    }

   // Listar todas las citas
    public function index()
    {
        try {
            $citas = $this->database->getReference('citas')->getValue();

            return response()->json([
                'success' => true,
                'data' => $citas ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    //Crear una cita
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['created_at'] = now()->toIso8601String();

            $newCita = $this->database
                ->getReference('citas')
                ->push($data);

            return response()->json([
                'success' => true,
                'message' => 'Cita creada exitosamente',
                'id' => $newCita->getKey()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    //Mostrar una cita específica
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

            return response()->json([
                'success' => true,
                'data' => $cita
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
            $data = $request->all();
            $data['updated_at'] = now()->toIso8601String();

            $ref = $this->database->getReference("citas/{$id}");
            $exists = $ref->getValue();

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cita no encontrada'
                ], 404);
            }

            $ref->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Cita actualizada exitosamente'
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
}
