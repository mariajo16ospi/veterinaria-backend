<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Paciente
{
    public static function validate(array $data)
    {
        return Validator::make($data, [
            'color' => 'required|string|max:100',
            'especie' => 'required|string|max:100',
            'fecha_nacimiento' => 'required|date',
            'nombre_mascota' => 'required|string|max:255',
            'raza' => 'required|string|max:100',
            'sexo' => 'required|in:Macho,Hembra',
            'cliente_id' => 'required|string',
            'imagen_url' => 'nullable|string', // ← Nuevo campo para la imagen
        ]);
    }
    
    public static function validateUpdate(array $data)
    {
        return Validator::make($data, [
            'color' => 'sometimes|required|string|max:100',
            'especie' => 'sometimes|required|string|max:100',
            'fecha_nacimiento' => 'sometimes|required|date',
            'nombre_mascota' => 'sometimes|required|string|max:255',
            'raza' => 'sometimes|required|string|max:100',
            'sexo' => 'sometimes|required|in:Macho,Hembra',
            'cliente_id' => 'sometimes|required|string',
            'imagen_url' => 'nullable|string',
        ]);
    }
}