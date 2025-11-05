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
        ]);
    }
}
