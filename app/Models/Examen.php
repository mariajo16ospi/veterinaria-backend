<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;


/*(Subcolección de Consulta)*/
class Examen
{
    public static function validate(array $data)
    {
        return Validator::make($data, [
            'fecha' => 'required|date',
            'resultados' => 'required|string',
            'tipo_examen' => 'required|string|max:255',
            'consulta_id' => 'required|string',
        ]);
    }
}
