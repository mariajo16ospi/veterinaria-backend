<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\Veterinario;


class VeterinarioController extends Controller
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

    // Listar todos los veterinarios
    public function index()
    {
        try {
            $snapshot = $this->database->getReference('veterinarios')->getSnapshot();
            $veterinarios = $snapshot->getValue();

            if (!$veterinarios) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $data = [];
            foreach ($veterinarios as $id => $veterinario) {
                $data[] = array_merge(['id' => $id], $veterinario);
            }

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Crear un nuevo veterinario
    public function store(Request $request)
    {
        $validator = Veterinario::validate($request->all());

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $data = $request->all();

            // Si tiene contraseña
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $data['created_at'] = now()->toIso8601String();

            $newRef = $this->database->getReference('veterinarios')->push($data);

            return response()->json([
                'success' => true,
                'message' => 'Veterinario creado exitosamente',
                'id' => $newRef->getKey()
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Mostrar un veterinario por ID
    public function show($id)
    {
        try {
            $snapshot = $this->database->getReference('veterinarios/' . $id)->getSnapshot();
            $veterinario = $snapshot->getValue();

            if (!$veterinario) {
                return response()->json(['success' => false, 'message' => 'Veterinario no encontrado'], 404);
            }

            return response()->json(['success' => true, 'data' => array_merge(['id' => $id], $veterinario)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Actualizar un veterinario
    public function update(Request $request, $id)
    {
        $validator = Veterinario::validateUpdate($request->all());

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $ref = $this->database->getReference('veterinarios/' . $id);
            $snapshot = $ref->getSnapshot();

            if (!$snapshot->getValue()) {
                return response()->json(['success' => false, 'message' => 'Veterinario no encontrado'], 404);
            }

            $data = $request->all();
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $data['updated_at'] = now()->toIso8601String();

            $ref->update($data);

            return response()->json(['success' => true, 'message' => 'Veterinario actualizado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Eliminar un veterinario
    public function destroy($id)
    {
        try {
            $ref = $this->database->getReference('veterinarios/' . $id);
            $snapshot = $ref->getSnapshot();

            if (!$snapshot->getValue()) {
                return response()->json(['success' => false, 'message' => 'Veterinario no encontrado'], 404);
            }

            $ref->remove();

            return response()->json(['success' => true, 'message' => 'Veterinario eliminado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
