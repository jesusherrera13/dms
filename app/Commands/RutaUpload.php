<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\Logica\Ruta\RutaSincronizar;

class RutaUpload extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'ruta:upload';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Subida de Rutas a DMS';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $sincronizar = new RutaSincronizar();
        
        try {
            $sincronizar->upload();
        }
        catch(\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
