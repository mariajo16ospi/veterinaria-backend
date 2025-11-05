<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;

// use App\Models\Cliente;
// use Illuminate\Support\Facades\Hash;

class ClienteController extends Controller
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
            $cliente = $this->database->getReference('clientes')->getValue() ?? [];

            $data = [];
            foreach ($clientes as $id => $cliente) {
                $data[] = array_merge(['id' => $id], $cliente);
            }

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['created_at'] = now()->toIso8601String();

            $newCliente = $this->database->getReference('clientes')->push($data);

            return response()->json([
                'success' => true,
                'message' => 'Dueño creado exitosamente',
                'id' => $newCliente->getKey(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $cliente = $this->database->getReference("clientes/{$id}")->getValue();

            if (!$cliente) {
                return response()->json(['success' => false, 'message' => 'Dueño no encontrado'], 404);
            }

            return response()->json(['success' => true, 'data' => array_merge(['id' => $id], $cliente)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $ref = $this->database->getReference("clientes/{$id}");

            if (!$ref->getValue()) {
                return response()->json(['success' => false, 'message' => 'Dueño no encontrado'], 404);
            }

            $data = $request->all();
            $data['updated_at'] = now()->toIso8601String();

            $ref->update($data);

            return response()->json(['success' => true, 'message' => 'Dueño actualizado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $ref = $this->database->getReference("clientes/{$id}");

            if (!$ref->getValue()) {
                return response()->json(['success' => false, 'message' => 'Dueño no encontrado'], 404);
            }

            $ref->remove();

            return response()->json(['success' => true, 'message' => 'Dueño eliminado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
