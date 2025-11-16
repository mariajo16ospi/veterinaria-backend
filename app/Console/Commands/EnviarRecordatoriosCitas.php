<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Api\CitaController;
use App\Services\WhatsAppService;

class EnviarRecordatoriosCitas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'citas:enviar-recordatorios';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía recordatorios de WhatsApp para citas que están a 1 hora de comenzar';

    protected $citaController;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CitaController $citaController)
    {
        parent::__construct();
        $this->citaController = $citaController;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando envío de recordatorios de citas...');

        try {
            $response = $this->citaController->enviarRecordatorios();
            $data = json_decode($response->getContent(), true);

            if ($data['success']) {
                $this->info($data['message']);
                return 0;
            } else {
                $this->error('Error: ' . $data['message']);
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('Error al enviar recordatorios: ' . $e->getMessage());
            return 1;
        }
    }
}