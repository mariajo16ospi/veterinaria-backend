<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Usuario
{
    public static function validate(array $data)
    {
        return Validator::make($data, [
            'email' => 'required|email|max:255',
            'estado' => 'required|in:activo,inactivo',
            'nombre_completo' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'rol' => 'required|in:veterinario,recepcionista,cliente,administrador',
            'telefono' => 'required|string|max:20',
        ]);
    }

    public static function validateUpdate(array $data)
    {
        return Validator::make($data, [
            'email' => 'sometimes|email|max:255',
            'estado' => 'sometimes|in:activo,inactivo',
            'nombre_completo' => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:6',
            'rol' => 'sometimes|in:veterinario,recepcionista,cliente,administrador',
            'telefono' => 'sometimes|string|max:20',
        ]);
    }
}
