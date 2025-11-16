<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $client;
    protected $from;

    public function __construct()
    {
        $accountSid = env('TWILIO_ACCOUNT_SID');
        $authToken = env('TWILIO_AUTH_TOKEN');
        $this->from = env('TWILIO_WHATSAPP_FROM'); // formato: whatsapp:+14155238886

        $this->client = new Client($accountSid, $authToken);
    }

    /**
     * Enviar notificación de cita agendada
     */
    public function enviarNotificacionCitaAgendada($telefono, $datosCita)
    {
        try {
            $mensaje = $this->construirMensajeCitaAgendada($datosCita);
            return $this->enviarMensaje($telefono, $mensaje);
        } catch (\Exception $e) {
            Log::error('Error enviando notificación de cita agendada: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar recordatorio de cita (1 hora antes)
     */
    public function enviarRecordatorioCita($telefono, $datosCita)
    {
        try {
            $mensaje = $this->construirMensajeRecordatorio($datosCita);
            return $this->enviarMensaje($telefono, $mensaje);
        } catch (\Exception $e) {
            Log::error('Error enviando recordatorio de cita: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar notificación de cita cancelada
     */
    public function enviarNotificacionCitaCancelada($telefono, $datosCita)
    {
        try {
            $mensaje = $this->construirMensajeCitaCancelada($datosCita);
            return $this->enviarMensaje($telefono, $mensaje);
        } catch (\Exception $e) {
            Log::error('Error enviando notificación de cita cancelada: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Método principal para enviar mensajes de WhatsApp
     */
    protected function enviarMensaje($telefono, $mensaje)
    {
        // Formatear el número de teléfono
        $telefonoFormateado = $this->formatearTelefono($telefono);

        if (!$telefonoFormateado) {
            Log::error('Número de teléfono inválido: ' . $telefono);
            return false;
        }

        try {
            $message = $this->client->messages->create(
                "whatsapp:{$telefonoFormateado}", // To
                [
                    'from' => $this->from,
                    'body' => $mensaje
                ]
            );

            Log::info('Mensaje de WhatsApp enviado exitosamente', [
                'sid' => $message->sid,
                'to' => $telefonoFormateado
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error al enviar mensaje de WhatsApp: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Construir mensaje de cita agendada
     */
    protected function construirMensajeCitaAgendada($datos)
    {
        $fecha = date('d/m/Y', strtotime($datos['fecha']));
        $hora = date('h:i A', strtotime($datos['hora']));

        $mensaje = " *Cita Veterinaria Confirmada* \n\n";
        $mensaje .= "¡Hola! Tu cita ha sido agendada exitosamente.\n\n";
        $mensaje .= " *Detalles de la cita:*\n";
        $mensaje .= "• Mascota: {$datos['mascota']}\n";
        $mensaje .= "• Fecha: {$fecha}\n";
        $mensaje .= "• Hora: {$hora}\n";
        $mensaje .= "• Servicio: {$datos['tipo_servicio']}\n";
        $mensaje .= "• Veterinario: {$datos['veterinario']}\n";

        if (isset($datos['valor']) && $datos['valor'] > 0) {
            $mensaje .= "• Valor: $" . number_format($datos['valor'], 0, ',', '.') . "\n";
        }

        $mensaje .= "\n Recibirás un recordatorio 1 hora antes de tu cita.\n";
        $mensaje .= "\n¡Esperamos verte pronto! ";

        return $mensaje;
    }

    /**
     * Construir mensaje de recordatorio
     */
    protected function construirMensajeRecordatorio($datos)
    {
        $fecha = date('d/m/Y', strtotime($datos['fecha']));
        $hora = date('h:i A', strtotime($datos['hora']));

        $mensaje = " *Recordatorio de Cita* \n\n";
        $mensaje .= "¡Hola! Te recordamos que tienes una cita en *1 hora*.\n\n";
        $mensaje .= " *Detalles:*\n";
        $mensaje .= "• Mascota: {$datos['mascota']}\n";
        $mensaje .= "• Hora: {$hora}\n";
        $mensaje .= "• Servicio: {$datos['tipo_servicio']}\n";
        $mensaje .= "• Veterinario: {$datos['veterinario']}\n\n";
        $mensaje .= "¡Te esperamos! ";

        return $mensaje;
    }

    /**
     * Construir mensaje de cita cancelada
     */
    protected function construirMensajeCitaCancelada($datos)
    {
        $fecha = date('d/m/Y', strtotime($datos['fecha']));
        $hora = date('h:i A', strtotime($datos['hora']));

        $mensaje = " *Cita Cancelada* \n\n";
        $mensaje .= "Tu cita ha sido cancelada.\n\n";
        $mensaje .= " *Cita cancelada:*\n";
        $mensaje .= "• Mascota: {$datos['mascota']}\n";
        $mensaje .= "• Fecha: {$fecha}\n";
        $mensaje .= "• Hora: {$hora}\n\n";
        $mensaje .= "Si deseas agendar una nueva cita, contáctanos.\n";

        return $mensaje;
    }

    /**
     * Formatear número de teléfono al formato internacional
     */
    protected function formatearTelefono($telefono)
    {
        // Eliminar espacios y caracteres especiales
        $telefono = preg_replace('/[^0-9+]/', '', $telefono);

        // Si ya tiene el formato internacional (+57), retornar
        if (strpos($telefono, '+') === 0) {
            return $telefono;
        }

        // Si comienza con 57 (sin +), agregar el +
        if (strpos($telefono, '57') === 0) {
            return '+' . $telefono;
        }

        // Si es un número local colombiano (10 dígitos), agregar +57
        if (strlen($telefono) === 10) {
            return '+57' . $telefono;
        }

        // Si no cumple ningún formato, retornar null
        return null;
    }

    /**
     * Verificar si un número es válido para WhatsApp
     */
    public function verificarNumeroValido($telefono)
    {
        $telefonoFormateado = $this->formatearTelefono($telefono);
        return $telefonoFormateado !== null;
    }
}
