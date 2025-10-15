<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;


/* (Subcolección de Cita)*/
class Consulta
{
    public static function validate(array $data)
    {
        return Validator::make($data, [
            'diagnostico' => 'required|string',
            'observaciones' => 'nullable|string',
            'tratamiento' => 'required|string',
            'cita_id' => 'required|string',
        ]);
    }
}
