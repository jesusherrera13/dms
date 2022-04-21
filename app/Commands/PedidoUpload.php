<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\Logica\Pedido\PedidoSincronizar;

class PedidoUpload extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'pedido:upload';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Subida de pedidos a DMS';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $sincronizar = new PedidoSincronizar();
        
        try {
            $sincronizar->upload();
        }
        catch(\Exception $e){
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
