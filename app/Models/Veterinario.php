<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;


class Veterinario
{
     public static function validate(array $data)
    {
        return Validator::make($data, [
            'especialidad' => 'required|string|max:255',
            'licencia' => 'required|string|max:100',
            'usuario_id' => 'required|string',
        ]);
    }
}
