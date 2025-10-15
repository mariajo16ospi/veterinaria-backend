<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\DatabaseException;

// use App\Models\Recepcionista;
// use Illuminate\Support\Facades\Hash;

class RecepcionistaController extends Controller
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

    public function index()
    {
        try {
            $reference = $this->database->getReference('recepcionistas');
            $recepcionistas = $reference->getValue();

            return response()->json([
                'success' => true,
                'data' => $recepcionistas ?? []
            ]);
        } catch (DatabaseException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['created_at'] = now()->toIso8601String();

            $newRecepcionista = $this->database
                ->getReference('recepcionistas')
                ->push($data);

            return response()->json([
                'success' => true,
                'message' => 'Recepcionista creado exitosamente',
                'id' => $newRecepcionista->getKey()
            ], 201);
        } catch (DatabaseException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $snapshot = $this->database->getReference("recepcionistas/{$id}")->getValue();

            if (!$snapshot) {
                return response()->json(['success' => false, 'message' => 'Recepcionista no encontrado'], 404);
            }

            return response()->json(['success' => true, 'data' => $snapshot]);
        } catch (DatabaseException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $reference = $this->database->getReference("recepcionistas/{$id}");

            if (!$reference->getValue()) {
                return response()->json(['success' => false, 'message' => 'Recepcionista no encontrado'], 404);
            }

            $reference->remove();

            return response()->json(['success' => true, 'message' => 'Recepcionista eliminado exitosamente']);
        } catch (DatabaseException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
