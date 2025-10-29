<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Factory;

class AuthController extends Controller
{
    protected $database;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(base_path('storage/app/firebase/firebase-credentials.json'))
            ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));

        $this->database = $factory->createDatabase();
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Buscar usuario por email
            $snapshot = $this->database->getReference('usuarios')->getSnapshot();
            $usuarios = $snapshot->getValue();

            if (!$usuarios) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            $usuarioEncontrado = null;
            $userId = null;

            foreach ($usuarios as $id => $usuario) {
                if (isset($usuario['email']) && $usuario['email'] === $request->email) {
                    $usuarioEncontrado = $usuario;
                    $userId = $id;
                    break;
                }
            }

            if (!$usuarioEncontrado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            // Verificar contraseña
            if (!Hash::check($request->password, $usuarioEncontrado['password'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            // Verificar estado activo
            if (isset($usuarioEncontrado['estado']) && $usuarioEncontrado['estado'] !== 'activo') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario inactivo. Contacta al administrador.'
                ], 403);
            }

            // Eliminar password de la respuesta
            unset($usuarioEncontrado['password']);

            // Generar token simple (en producción usa JWT)
            $token = base64_encode($userId . ':' . time());

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => [
                    'user' => array_merge(['id' => $userId], $usuarioEncontrado),
                    'token' => $token
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en el servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso'
        ]);
    }
}
