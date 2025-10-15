<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;


class Cita
{
    public static function validate(array $data)
    {
        return Validator::make($data, [
            'estado' => 'required|in:pendiente,confirmada,completada,cancelada',
            'fecha' => 'required|date',
            'forma_pago' => 'required|in:efectivo,tarjeta,transferencia,pasarela',
            'ownerUid' => 'required|string',
            'mascota_id' => 'required|string',
            'tipo_servicio' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0',
            'veterinario_id' => 'required|string',
        ]);
    }
}
