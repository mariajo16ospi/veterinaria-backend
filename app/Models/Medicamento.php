<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;


/* (Subcolección de Consulta)*/
class Medicamento
{
    public static function validate(array $data)
    {
        return Validator::make($data, [
            'dosis' => 'required|string|max:100',
            'duracion' => 'required|string|max:100',
            'frecuencia' => 'required|string|max:100',
            'nombre' => 'required|string|max:255',
            'consulta_id' => 'required|string',
        ]);
    }
}
