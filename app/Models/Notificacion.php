<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;


class Notificacion
{
    public static function validate(array $data)
    {
        return Validator::make($data, [
            'fecha' => 'required|date',
            'leido' => 'required|boolean',
            'mensaje' => 'required|string',
            'usuario_id' => 'required|string',
        ]);
    }
}
