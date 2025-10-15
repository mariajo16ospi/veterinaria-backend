<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
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
            $snapshot = $this->database->getReference('usuarios')->getSnapshot();
            $usuarios = $snapshot->getValue();

            if (!$usuarios) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $data = [];
            foreach ($usuarios as $id => $usuario) {
                $data[] = array_merge(['id' => $id], $usuario);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /*Crear un nuevo usuario*/
    public function store(Request $request)
    {
        $validator = Usuario::validate($request->all());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            $data['password'] = Hash::make($data['password']);
            $data['created_at'] = now()->toIso8601String();

            $newRef = $this->database->getReference('usuarios')->push($data);

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'id' => $newRef->getKey()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /*Obtener un usuario por ID*/
    public function show($id)
    {
        try {
            $snapshot = $this->database->getReference('usuarios/' . $id)->getSnapshot();
            $usuario = $snapshot->getValue();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => array_merge(['id' => $id], $usuario)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /*Actualizar un usuario*/
    public function update(Request $request, $id)
    {
        $validator = Usuario::validateUpdate($request->all());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $ref = $this->database->getReference('usuarios/' . $id);
            $snapshot = $ref->getSnapshot();

            if (!$snapshot->getValue()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $data = $request->all();
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            $data['updated_at'] = now()->toIso8601String();

            $ref->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /* Eliminar un usuario*/
    public function destroy($id)
    {
        try {
            $ref = $this->database->getReference('usuarios/' . $id);
            $snapshot = $ref->getSnapshot();

            if (!$snapshot->getValue()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $ref->remove();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
