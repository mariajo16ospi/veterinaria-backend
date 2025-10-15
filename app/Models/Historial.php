<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;


/*(Subcolección de Paciente)*/
class Historial
{
    public static function validate(array $data)
    {
        return Validator::make($data, [
            'detalle' => 'required|string',
            'fecha' => 'required|date',
            'paciente_id' => 'required|string',
        ]);
    }
}
