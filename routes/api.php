<?php

use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\VeterinarioController;
use App\Http\Controllers\Api\RecepcionistaController;
use App\Http\Controllers\Api\DuenoController;
use App\Http\Controllers\Api\PacienteController;
use App\Http\Controllers\Api\HistorialController;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\ConsultaController;
use App\Http\Controllers\Api\ExamenController;
use App\Http\Controllers\Api\MedicamentoController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\AuthController;



Route::prefix('v1')->group(function () {

    // AUTENTICACIÓN
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::prefix('usuarios')->group(function () {
        Route::get('/', [UsuarioController::class, 'index']);
        Route::get('/{id}', [UsuarioController::class, 'show']);
        Route::post('/', [UsuarioController::class, 'store']);
        Route::put('/{id}', [UsuarioController::class, 'update']);
        Route::delete('/{id}', [UsuarioController::class, 'destroy']);
    });

    //  USUARIOS
    Route::prefix('usuarios')->group(function () {
        Route::get('/', [UsuarioController::class, 'index']); // GET /api/v1/usuarios
        Route::get('/{id}', [UsuarioController::class, 'show']);// GET /api/v1/usuarios/{id}
        Route::post('/', [UsuarioController::class, 'store']);// POST /api/v1/usuarios
        Route::put('/{id}', [UsuarioController::class, 'update']);// PUT /api/v1/usuarios/{id}
        Route::delete('/{id}', [UsuarioController::class, 'destroy']);// DELETE /api/v1/usuarios/{id}
    });

    // VETERINARIOS
    Route::prefix('veterinarios')->group(function () {
        Route::get('/', [VeterinarioController::class, 'index']);// GET /api/v1/veterinarios
        Route::get('/{id}', [VeterinarioController::class, 'show']);// GET /api/v1/veterinarios/{id}
        Route::post('/', [VeterinarioController::class, 'store']);// POST /api/v1/veterinarios
        Route::put('/{id}', [VeterinarioController::class, 'update']);// PUT /api/v1/veterinarios/{id}
        Route::delete('/{id}', [VeterinarioController::class, 'destroy']); // DELETE /api/v1/veterinarios/{id}
    });

    // RECEPCIONISTAS
    Route::prefix('recepcionistas')->group(function () {
        Route::get('/', [RecepcionistaController::class, 'index']);// GET /api/v1/recepcionistas
        Route::get('/{id}', [RecepcionistaController::class, 'show']);// GET /api/v1/recepcionistas/{id}
        Route::post('/', [RecepcionistaController::class, 'store']);// POST /api/v1/recepcionistas
        Route::delete('/{id}', [RecepcionistaController::class, 'destroy']);// DELETE /api/v1/recepcionistas/{id}
    });

    // DUEÑOS
    Route::prefix('duenos')->group(function () {
        Route::get('/', [DuenoController::class, 'index']);// GET /api/v1/duenos
        Route::get('/{id}', [DuenoController::class, 'show']);// GET /api/v1/duenos/{id}
        Route::post('/', [DuenoController::class, 'store']);// POST /api/v1/duenos
        Route::put('/{id}', [DuenoController::class, 'update']);// PUT /api/v1/duenos/{id}
        Route::delete('/{id}', [DuenoController::class, 'destroy']);// DELETE /api/v1/duenos/{id}

        //  PACIENTES (Subcolección de Dueños)
        Route::prefix('{duenoId}/pacientes')->group(function () {
            Route::get('/', [PacienteController::class, 'index']);// GET /api/v1/duenos/{duenoId}/pacientes
            Route::get('/{pacienteId}', [PacienteController::class, 'show']);// GET /api/v1/duenos/{duenoId}/pacientes/{pacienteId}
            Route::post('/', [PacienteController::class, 'store']);// POST /api/v1/duenos/{duenoId}/pacientes
            Route::put('/{pacienteId}', [PacienteController::class, 'update']);// PUT /api/v1/duenos/{duenoId}/pacientes/{pacienteId}
            Route::delete('/{pacienteId}', [PacienteController::class, 'destroy']);// DELETE /api/v1/duenos/{duenoId}/pacientes/{pacienteId}

            //  HISTORIAL (Subcolección de Pacientes)
            Route::prefix('{pacienteId}/historiales')->group(function () {
                Route::get('/', [HistorialController::class, 'index']);// GET /api/v1/duenos/{duenoId}/pacientes/{pacienteId}/historiales
                Route::get('/{historialId}', [HistorialController::class, 'show']);// GET /api/v1/duenos/{duenoId}/pacientes/{pacienteId}/historiales/{historialId}
                Route::post('/', [HistorialController::class, 'store']);// POST /api/v1/duenos/{duenoId}/pacientes/{pacienteId}/historiales
                Route::delete('/{historialId}', [HistorialController::class, 'destroy']);// DELETE /api/v1/duenos/{duenoId}/pacientes/{pacienteId}/historiales/{historialId}
            });
        });
    });

    //  CITAS
    Route::prefix('citas')->group(function () {
        Route::get('/', [CitaController::class, 'index']);// GET /api/v1/citas
        Route::get('/{id}', [CitaController::class, 'show']);// GET /api/v1/citas/{id}
        Route::post('/', [CitaController::class, 'store']);// POST /api/v1/citas
        Route::put('/{id}', [CitaController::class, 'update']);// PUT /api/v1/citas/{id}
        Route::delete('/{id}', [CitaController::class, 'destroy']);// DELETE /api/v1/citas/{id}

        //  CONSULTAS (Subcolección de Citas)
        Route::prefix('{citaId}/consultas')->group(function () {
            Route::get('/', [ConsultaController::class, 'index']);// GET /api/v1/citas/{citaId}/consultas
            Route::get('/{consultaId}', [ConsultaController::class, 'show']);// GET /api/v1/citas/{citaId}/consultas/{consultaId}
            Route::post('/', [ConsultaController::class, 'store']);// POST /api/v1/citas/{citaId}/consultas
            Route::put('/{consultaId}', [ConsultaController::class, 'update']);// PUT /api/v1/citas/{citaId}/consultas/{consultaId}
            Route::delete('/{consultaId}', [ConsultaController::class, 'destroy']);// DELETE /api/v1/citas/{citaId}/consultas/{consultaId}

            //  EXÁMENES (Subcolección de Consultas)
            Route::prefix('{consultaId}/examenes')->group(function () {
                Route::get('/', [ExamenController::class, 'index']);// GET /api/v1/citas/{citaId}/consultas/{consultaId}/examenes
                Route::get('/{examenId}', [ExamenController::class, 'show']);// GET /api/v1/citas/{citaId}/consultas/{consultaId}/examenes/{examenId}
                Route::post('/', [ExamenController::class, 'store']);// POST /api/v1/citas/{citaId}/consultas/{consultaId}/examenes
                Route::put('/{examenId}', [ExamenController::class, 'update']);// PUT /api/v1/citas/{citaId}/consultas/{consultaId}/examenes/{examenId}
                Route::delete('/{examenId}', [ExamenController::class, 'destroy']);// DELETE /api/v1/citas/{citaId}/consultas/{consultaId}/examenes/{examenId}
            });

            //  MEDICAMENTOS (Subcolección de Consultas)
            Route::prefix('{consultaId}/medicamentos')->group(function () {
                Route::get('/', [MedicamentoController::class, 'index']);// GET /api/v1/citas/{citaId}/consultas/{consultaId}/medicamentos
                Route::get('/{medicamentoId}', [MedicamentoController::class, 'show']);// GET /api/v1/citas/{citaId}/consultas/{consultaId}/medicamentos/{medicamentoId}
                Route::post('/', [MedicamentoController::class, 'store']);// POST /api/v1/citas/{citaId}/consultas/{consultaId}/medicamentos
                Route::put('/{medicamentoId}', [MedicamentoController::class, 'update']);// PUT /api/v1/citas/{citaId}/consultas/{consultaId}/medicamentos/{medicamentoId}
                Route::delete('/{medicamentoId}', [MedicamentoController::class, 'destroy']);// DELETE /api/v1/citas/{citaId}/consultas/{consultaId}/medicamentos/{medicamentoId}
            });
        });
    });

    //  NOTIFICACIONES
    Route::prefix('notificaciones')->group(function () {
        Route::get('/', [NotificacionController::class, 'index']);// GET /api/v1/notificaciones
        Route::get('/{id}', [NotificacionController::class, 'show']);// GET /api/v1/notificaciones/{id}
        Route::post('/', [NotificacionController::class, 'store']);// POST /api/v1/notificaciones
        Route::put('/{id}/marcar-leida', [NotificacionController::class, 'markAsRead']);// PUT /api/v1/notificaciones/{id}/marcar-leida
        Route::get('/usuario/{usuarioId}', [NotificacionController::class, 'getByUsuario']);// GET /api/v1/notificaciones/usuario/{usuarioId}
        Route::delete('/{id}', [NotificacionController::class, 'destroy']);// DELETE /api/v1/notificaciones/{id}
    });
});
