<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\DatabaseException;

class NotificacionController extends Controller
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

    /**
     * Listar todas las notificaciones
     */
    public function index()
    {
        try {
            $notificaciones = $this->database->getReference('notificaciones')->getValue();

            return response()->json([
                'success' => true,
                'data' => $notificaciones ?? []
            ]);
        } catch (DatabaseException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Crear una nueva notificación
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['leido'] = false;
            $data['created_at'] = now()->toIso8601String();

            $newNotif = $this->database->getReference('notificaciones')->push($data);

            return response()->json([
                'success' => true,
                'message' => 'Notificación creada exitosamente',
                'id' => $newNotif->getKey()
            ], 201);
        } catch (DatabaseException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mostrar una notificación específica
     */
    public function show($id)
    {
        try {
            $snapshot = $this->database->getReference("notificaciones/{$id}")->getValue();

            if (!$snapshot) {
                return response()->json(['success' => false, 'message' => 'Notificación no encontrada'], 404);
            }

            return response()->json(['success' => true, 'data' => $snapshot]);
        } catch (DatabaseException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Marcar una notificación como leída
     */
    public function markAsRead($id)
    {
        try {
            $ref = $this->database->getReference("notificaciones/{$id}");

            if (!$ref->getValue()) {
                return response()->json(['success' => false, 'message' => 'Notificación no encontrada'], 404);
            }

            $ref->update([
                'leido' => true,
                'updated_at' => now()->toIso8601String()
            ]);

            return response()->json(['success' => true, 'message' => 'Notificación marcada como leída']);
        } catch (DatabaseException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener notificaciones de un usuario específico
     */
    public function getByUsuario($usuarioId)
    {
        try {
            $notificaciones = $this->database->getReference('notificaciones')->getValue();

            // Filtrar por usuario_id
            $filtradas = [];
            if ($notificaciones) {
                foreach ($notificaciones as $id => $notif) {
                    if (isset($notif['usuario_id']) && $notif['usuario_id'] == $usuarioId) {
                        $filtradas[] = array_merge(['id' => $id], $notif);
                    }
                }
            }

            return response()->json(['success' => true, 'data' => $filtradas]);
        } catch (DatabaseException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar una notificación
     */
    public function destroy($id)
    {
        try {
            $ref = $this->database->getReference("notificaciones/{$id}");

            if (!$ref->getValue()) {
                return response()->json(['success' => false, 'message' => 'Notificación no encontrada'], 404);
            }

            $ref->remove();

            return response()->json(['success' => true, 'message' => 'Notificación eliminada exitosamente']);
        } catch (DatabaseException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
