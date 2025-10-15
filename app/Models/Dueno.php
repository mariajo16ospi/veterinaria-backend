<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;


class Dueno
{
    public static function validate(array $data)
    {
        return Validator::make($data, [
            'barrio' => 'required|string|max:255',
            'ciudad' => 'required|string|max:255',
            'direccion' => 'required|string|max:500',
            'usuario_id' => 'required|string',
        ]);
    }
}
