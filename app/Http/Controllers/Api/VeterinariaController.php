<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Contract\Firestore;
use App\Models\Usuario;
use App\Models\Veterinario;
use App\Models\Recepcionista;
use App\Models\Cliente;
use App\Models\Paciente;
use App\Models\Historial;
use App\Models\Cita;
use App\Models\Consulta;
use App\Models\Examen;
use App\Models\Medicamento;
use App\Models\Notificacion;
use Illuminate\Support\Facades\Hash;

class VeterinariaController extends Controller
{
    // protected $firestore;
    // protected $database;

    // public function __construct()
    // {
    //     $factory = (new Factory)->withServiceAccount(config('firebase.credentials'));
    //     $this->firestore = $factory->createFirestore();
    //     $this->database = $this->firestore->database();
    // }

    // // ========================================
    // // USUARIOS
    // // ========================================

    // public function getUsuarios()
    // {
    //     try {
    //         $usuarios = $this->database->collection('usuarios')->documents();
    //         $data = [];

    //         foreach ($usuarios as $usuario) {
    //             if ($usuario->exists()) {
    //                 $data[] = array_merge(['id' => $usuario->id()], $usuario->data());
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'data' => $data
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al obtener usuarios',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function getUsuario($id)
    // {
    //     try {
    //         $usuario = $this->database->collection('usuarios')->document($id)->snapshot();

    //         if (!$usuario->exists()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Usuario no encontrado'
    //             ], 404);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'data' => array_merge(['id' => $usuario->id()], $usuario->data())
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al obtener usuario',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function createUsuario(Request $request)
    // {
    //     try {
    //         $validator = Usuario::validate($request->all());

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         $data = $request->all();
    //         $data['password'] = Hash::make($data['password']);
    //         $data['created_at'] = now()->toIso8601String();

    //         $docRef = $this->database->collection('usuarios')->add($data);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Usuario creado exitosamente',
    //             'id' => $docRef->id()
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al crear usuario',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function updateUsuario(Request $request, $id)
    // {
    //     try {
    //         $validator = Usuario::validateUpdate($request->all());

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         $docRef = $this->database->collection('usuarios')->document($id);

    //         if (!$docRef->snapshot()->exists()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Usuario no encontrado'
    //             ], 404);
    //         }

    //         $data = $request->all();
    //         if (isset($data['password'])) {
    //             $data['password'] = Hash::make($data['password']);
    //         }
    //         $data['updated_at'] = now()->toIso8601String();

    //         $docRef->update($data);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Usuario actualizado exitosamente'
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al actualizar usuario',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function deleteUsuario($id)
    // {
    //     try {
    //         $docRef = $this->database->collection('usuarios')->document($id);

    //         if (!$docRef->snapshot()->exists()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Usuario no encontrado'
    //             ], 404);
    //         }

    //         $docRef->delete();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Usuario eliminado exitosamente'
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al eliminar usuario',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // // ========================================
    // // VETERINARIOS
    // // ========================================

    // public function getVeterinarios()
    // {
    //     try {
    //         $veterinarios = $this->database->collection('veterinarios')->documents();
    //         $data = [];

    //         foreach ($veterinarios as $vet) {
    //             if ($vet->exists()) {
    //                 $vetData = array_merge(['id' => $vet->id()], $vet->data());

    //                 // Obtener información del usuario asociado
    //                 if (isset($vetData['usuario_id'])) {
    //                     $usuario = $this->database->collection('usuarios')
    //                         ->document($vetData['usuario_id'])
    //                         ->snapshot();

    //                     if ($usuario->exists()) {
    //                         $vetData['usuario'] = $usuario->data();
    //                     }
    //                 }

    //                 $data[] = $vetData;
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'data' => $data
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al obtener veterinarios',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function createVeterinario(Request $request)
    // {
    //     try {
    //         $validator = Veterinario::validate($request->all());

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         $data = $request->all();
    //         $data['created_at'] = now()->toIso8601String();

    //         $docRef = $this->database->collection('veterinarios')->add($data);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Veterinario creado exitosamente',
    //             'id' => $docRef->id()
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al crear veterinario',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // // ========================================
    // // RECEPCIONISTAS
    // // ========================================

    // public function createRecepcionista(Request $request)
    // {
    //     try {
    //         $validator = Recepcionista::validate($request->all());

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         $data = $request->all();
    //         $data['created_at'] = now()->toIso8601String();

    //         $docRef = $this->database->collection('recepcionistas')->add($data);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Recepcionista creado exitosamente',
    //             'id' => $docRef->id()
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al crear recepcionista',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // // ========================================
    // // DUEÑOS
    // // ========================================

    // public function getDuenos()
    // {
    //     try {
    //         $duenos = $this->database->collection('duenos')->documents();
    //         $data = [];

    //         foreach ($duenos as $dueno) {
    //             if ($dueno->exists()) {
    //                 $duenoData = array_merge(['id' => $dueno->id()], $dueno->data());

    //                 // Obtener información del usuario asociado
    //                 if (isset($duenoData['usuario_id'])) {
    //                     $usuario = $this->database->collection('usuarios')
    //                         ->document($duenoData['usuario_id'])
    //                         ->snapshot();

    //                     if ($usuario->exists()) {
    //                         $duenoData['usuario'] = $usuario->data();
    //                     }
    //                 }

    //                 $data[] = $duenoData;
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'data' => $data
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al obtener dueños',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function getDueno($id)
    // {
    //     try {
    //         $dueno = $this->database->collection('duenos')->document($id)->snapshot();

    //         if (!$dueno->exists()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Dueño no encontrado'
    //             ], 404);
    //         }

    //         $duenoData = array_merge(['id' => $dueno->id()], $dueno->data());

    //         // Obtener información del usuario asociado
    //         if (isset($duenoData['usuario_id'])) {
    //             $usuario = $this->database->collection('usuarios')
    //                 ->document($duenoData['usuario_id'])
    //                 ->snapshot();

    //             if ($usuario->exists()) {
    //                 $duenoData['usuario'] = $usuario->data();
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'data' => $duenoData
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al obtener dueño',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function createDueno(Request $request)
    // {
    //     try {
    //         $validator = Dueno::validate($request->all());

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         $data = $request->all();
    //         $data['created_at'] = now()->toIso8601String();

    //         $docRef = $this->database->collection('duenos')->add($data);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Dueño creado exitosamente',
    //             'id' => $docRef->id()
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al crear dueño',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // // ========================================
    // // PACIENTES (Subcolección de Dueños)
    // // ========================================

    // public function getPacientesByDueno($duenoId)
    // {
    //     try {
    //         $pacientes = $this->database->collection('duenos')
    //             ->document($duenoId)
    //             ->collection('pacientes')
    //             ->documents();

    //         $data = [];
    //         foreach ($pacientes as $paciente) {
    //             if ($paciente->exists()) {
    //                 $data[] = array_merge(['id' => $paciente->id()], $paciente->data());
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'data' => $data
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al obtener pacientes',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function getPaciente($duenoId, $pacienteId)
    // {
    //     try {
    //         $paciente = $this->database->collection('duenos')
    //             ->document($duenoId)
    //             ->collection('pacientes')
    //             ->document($pacienteId)
    //             ->snapshot();

    //         if (!$paciente->exists()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Paciente no encontrado'
    //             ], 404);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'data' => array_merge(['id' => $paciente->id()], $paciente->data())
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al obtener paciente',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function createPaciente(Request $request, $duenoId)
    // {
    //     try {
    //         $validator = Paciente::validate($request->all());

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         $data = $request->all();
    //         $data['dueno_id'] = $duenoId;
    //         $data['created_at'] = now()->toIso8601String();

    //         $docRef = $this->database->collection('duenos')
    //             ->document($duenoId)
    //             ->collection('pacientes')
    //             ->add($data);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Paciente creado exitosamente',
    //             'id' => $docRef->id()
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al crear paciente',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // // ========================================
    // // HISTORIAL (Subcolección de Pacientes)
    // // ========================================

    // public function getHistorialByPaciente($duenoId, $pacienteId)
    // {
    //     try {
    //         $historiales = $this->database->collection('duenos')
    //             ->document($duenoId)
    //             ->collection('pacientes')
    //             ->document($pacienteId)
    //             ->collection('historiales')
    //             ->orderBy('fecha', 'DESC')
    //             ->documents();

    //         $data = [];
    //         foreach ($historiales as $historial) {
    //             if ($historial->exists()) {
    //                 $data[] = array_merge(['id' => $historial->id()], $historial->data());
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'data' => $data
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al obtener historial',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function createHistorial(Request $request, $duenoId, $pacienteId)
    // {
    //     try {
    //         $validator = Historial::validate($request->all());

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         $data = $request->all();
    //         $data['paciente_id'] = $pacienteId;
    //         $data['created_at'] = now()->toIso8601String();

    //         $docRef = $this->database->collection('duenos')
    //             ->document($duenoId)
    //             ->collection('pacientes')
    //             ->document($pacienteId)
    //             ->collection('historiales')
    //             ->add($data);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Historial creado exitosamente',
    //             'id' => $docRef->id()
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al crear historial',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // // ========================================
    // // CITAS
    // // ========================================

    // public function getCitas()
    // {
    //     try {
    //         $citas = $this->database->collection('citas')
    //             ->orderBy('fecha', 'DESC')
    //             ->documents();

    //         $data = [];
    //         foreach ($citas as $cita) {
    //             if ($cita->exists()) {
    //                 $citaData = array_merge(['id' => $cita->id()], $cita->data());

    //                 // Obtener información del veterinario
    //                 if (isset($citaData['veterinario_id'])) {
    //                     $vet = $this->database->collection('veterinarios')
    //                         ->document($citaData['veterinario_id'])
    //                         ->snapshot();

    //                     if ($vet->exists()) {
    //                         $citaData['veterinario'] = $vet->data();
    //                     }
    //                 }

    //                 $data[] = $citaData;
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'data' => $data
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al obtener citas',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function getCita($id)
    // {
    //     try {
    //         $cita = $this->database->collection('citas')->document($id)->snapshot();

    //         if (!$cita->exists()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Cita no encontrada'
    //             ], 404);
    //         }

    //         $citaData = array_merge(['id' => $cita->id()], $cita->data());

    //         // Obtener información del veterinario
    //         if (isset($citaData['veterinario_id'])) {
    //             $vet = $this->database->collection('veterinarios')
    //                 ->document($citaData['veterinario_id'])
    //                 ->snapshot();

    //             if ($vet->exists()) {
    //                 $citaData['veterinario'] = $vet->data();
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'data' => $citaData
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al obtener cita',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function createCita(Request $request)
    // {
    //     try {
    //         $validator = Cita::validate($request->all());

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         $data = $request->all();
    //         $data['created_at'] = now()->toIso8601String();

    //         $docRef = $this->database->collection('citas')->add($data);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Cita creada exitosamente',
    //             'id' => $docRef->id()
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al crear cita',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function updateCita(Request $request, $id)
    // {
    //     try {
    //         $docRef = $this->database->collection('citas')->document($id);

    //         if (!$docRef->snapshot()->exists()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Cita no encontrada'
    //             ], 404);
    //         }

    //         $data = $request->all();
    //         $data['updated_at'] = now()->toIso8601String();

    //         $docRef->update($data);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Cita actualizada exitosamente'
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al actualizar cita',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // // ========================================
    // // CONSULTAS (Subcolección de Citas)
    // // ========================================

    // public function getConsultasByCita($citaId)
    // {
    //     try {
    //         $consultas = $this->database->collection('citas')
    //             ->document($citaId)
    //             ->collection('consultas')
    //             ->documents();

    //         $data = [];
    //         foreach ($consultas as $consulta) {
    //             if ($consulta->exists()) {
    //                 $data[] = array_merge(['id' => $consulta->id()], $consulta->data());
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'data' => $data
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al obtener consultas',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function createConsulta(Request $request, $citaId)
    // {
    //     try {
    //         $validator = Consulta::validate($request->all());

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         $data = $request->all();
    //         $data['cita_id'] = $citaId;
    //         $data['created_at'] = now()->toIso8601String();

    //         $docRef = $this->database->collection('citas')
    //             ->document($citaId)
    //             ->collection('consultas')
    //             ->add($data);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Consulta creada exitosamente',
    //             'id' => $docRef->id()
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al crear consulta',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // // ========================================
    // // EXÁMENES (Subcolección de Consultas)
    // // ========================================

    // public function getExamenesByConsulta($citaId, $consultaId)
    // {
    //     try {
    //         $examenes = $this->database->collection('citas')
    //             ->document($citaId)
    //             ->collection('consultas')
    //             ->document($consultaId)
    //             ->collection('examenes')
    //             ->documents();

    //         $data = [];
    //         foreach ($examenes as $examen) {
    //             if ($examen->exists()) {
    //                 $data[] = array_merge(['id' => $examen->id()], $examen->data());
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'data' => $data
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al obtener exámenes',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function createExamen(Request $request, $citaId, $consultaId)
    // {
    //     try {
    //         $validator = Examen::validate($request->all());

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         $data = $request->all();
    //         $data['consulta_id'] = $consultaId;
    //         $data['created_at'] = now()->toIso8601String();

    //         $docRef = $this->database->collection('citas')
    //             ->document($citaId)
    //             ->collection('consultas')
    //             ->document($consultaId)
    //             ->collection('examenes')
    //             ->add($data);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Examen creado exitosamente',
    //             'id' => $docRef->id()
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al crear examen',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // // ========================================
    // // MEDICAMENTOS (Subcolección de Consultas)
    // // ========================================

    // public function getMedicamentosByConsulta($citaId, $consultaId)
    // {
    //     try {
    //         $medicamentos = $this->database->collection('citas')
    //             ->document($citaId)
    //             ->collection('consultas')
    //             ->document($consultaId)
    //             ->collection('medicamentos')
    //             ->documents();

    //         $data = [];
    //         foreach ($medicamentos as $medicamento) {
    //             if ($medicamento->exists()) {
    //                 $data[] = array_merge(['id' => $medicamento->id()], $medicamento->data());
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'data' => $data
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al obtener medicamentos',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function createMedicamento(Request $request, $citaId, $consultaId)
    // {
    //     try {
    //         $validator = Medicamento::validate($request->all());

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         $data = $request->all();
    //         $data['consulta_id'] = $consultaId;
    //         $data['created_at'] = now()->toIso8601String();

    //         $docRef = $this->database->collection('citas')
    //             ->document($citaId)
    //             ->collection('consultas')
    //             ->document($consultaId)
    //             ->collection('medicamentos')
    //             ->add($data);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Medicamento creado exitosamente',
    //             'id' => $docRef->id()
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al crear medicamento',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // // ========================================
    // // NOTIFICACIONES
    // // ========================================

    // public function createNotificacion(Request $request)
    // {
    //     try {
    //         $validator = Notificacion::validate($request->all());

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         $data = $request->all();
    //         $data['created_at'] = now()->toIso8601String();

    //         $docRef = $this->database->collection('notificaciones')->add($data);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Notificación creada exitosamente',
    //             'id' => $docRef->id()
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al crear notificación',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function getNotificacionesByUsuario($usuarioId)
    // {
    //     try {
    //         $notificaciones = $this->database->collection('notificaciones')
    //             ->where('usuario_id', '=', $usuarioId)
    //             ->orderBy('fecha', 'DESC')
    //             ->documents();

    //         $data = [];
    //         foreach ($notificaciones as $notif) {
    //             if ($notif->exists()) {
    //                 $data[] = array_merge(['id' => $notif->id()], $notif->data());
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'data' => $data
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al obtener notificaciones',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function marcarNotificacionLeida($id)
    // {
    //     try {
    //         $docRef = $this->database->collection('notificaciones')->document($id);

    //         if (!$docRef->snapshot()->exists()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Notificación no encontrada'
    //             ], 404);
    //         }

    //         $docRef->update([
    //             'leido' => true,
    //             'updated_at' => now()->toIso8601String()
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Notificación marcada como leída'
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al marcar notificación como leída',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }
}
