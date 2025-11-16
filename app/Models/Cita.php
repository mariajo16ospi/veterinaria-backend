<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Cita
{
    public static function validate(array $data)
    {
        return Validator::make($data, [
            'estado' => 'required|in:pendiente,confirmada,en_atencion,completada,cancelada,no_asistio',
            'fecha' => 'required|date',
            'hora' => 'required|string',
            'forma_pago' => 'required|in:efectivo,tarjeta,transferencia,pasarela,otro',
            'cliente_id' => 'required|string',
            'ownerUid' => 'sometimes|string', 
            'mascota_id' => 'required|string',
            'motivo' => 'required|string|max:500',
            'tipo_servicio' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0',
            'veterinario_id' => 'required|string',
            'pagado' => 'sometimes|boolean',
            'fecha_pago' => 'sometimes|nullable|date',
            'recordatorio_enviado' => 'sometimes|boolean',
            'requiere_recordatorio' => 'sometimes|boolean',
        ]);
    }

    public static function validateUpdate(array $data)
    {
        return Validator::make($data, [
            'estado' => 'sometimes|in:pendiente,confirmada,en_atencion,completada,cancelada,no_asistio',
            'fecha' => 'sometimes|date',
            'hora' => 'sometimes|string',
            'forma_pago' => 'sometimes|in:efectivo,tarjeta,transferencia,pasarela,otro',
            'cliente_id' => 'sometimes|string',
            'ownerUid' => 'sometimes|string',
            'mascota_id' => 'sometimes|string',
            'motivo' => 'sometimes|string|max:500',
            'tipo_servicio' => 'sometimes|string|max:255',
            'valor' => 'sometimes|numeric|min:0',
            'veterinario_id' => 'sometimes|string',
            'pagado' => 'sometimes|boolean',
            'fecha_pago' => 'sometimes|nullable|date',
            'motivo_cancelacion' => 'sometimes|nullable|string|max:500',
            'fecha_cancelacion' => 'sometimes|nullable|string',
            'recordatorio_enviado' => 'sometimes|boolean',
            'requiere_recordatorio' => 'sometimes|boolean',
        ]);
    }
}
