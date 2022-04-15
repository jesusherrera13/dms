<?php

namespace App\Logica\Visita;

use Illuminate\Support\Facades\DB;

class VisitaSincronizar {

    public function __construct() {
    }

    function upload() {

        if(!file_exists(env('PATH_UPLOAD'))) {
            mkdir(env('PATH_UPLOAD'), 0777);
        }

        $file_name = env('INTERFAZ_VISITA')."_".date('Ymdhis').".txt";
        $file_full_name = getcwd()."/".env('PATH_UPLOAD')."/".$file_name;

        $file =  fopen($file_full_name,"a");

        $data = DB::table('preventa_planning_detalles as detalles')
                    ->leftJoin('preventa_plannings AS planning', 'planning.id','detalles.fk_planning')
                    ->leftJoin('preventa_despacho_rutas AS despacho', 'despacho.fk_planning','planning.id')
                    ->leftJoin('ventas_vendedores AS vendedor', 'vendedor.fk_user', 'planning.fk_usuario')
                    ->leftJoin('ventas_clientes AS cliente', 'cliente.id', 'detalles.fk_cliente')
                    ->leftJoin('ventas_rutas AS ruta', 'ruta.id', 'cliente.fk_ruta')
                    ->leftJoin('ventas_clientes_canal AS canal', 'canal.id', 'cliente.fk_canal')
                    ->leftJoin('ventas_clientes_subcanal AS subcanal', 'subcanal.id', 'cliente.fk_subcanal')
                    ->leftJoin('coral_erp.system_usuarios as usr','usr.id','planning.fk_usuario')
                    ->select(
                        'planning.id as cdVisitInstance',
                        'planning.fk_sucursal as cdRegion',
                        'detalles.fk_cliente as cdStore',
                        'usr.username as cdUser',
                        'despacho.fecha as dtStart',
                        DB::raw("
                            IF(
                                despacho.estado='TERMINADO','FINAL',
                                IF(
                                    despacho.estado IN ('NUEVO','AUTORIZADO','EN_RUTA'),'PLAN',
                                    'CANC'
                                )
                            ) AS cdStatus"
                        ),
                    )
                    ->whereRaw("despacho.fecha >=(select date_format(now(),'%Y-%m-01'))")
                    // ->limit(1)
                    ->get();

                    /* 
                    SELECT planning.fk_usuario, vendedor.nombre AS nombre_vendedor, detalles.fk_cliente,
                    cliente.nombre AS nombre_cliente, cliente.empresa, cliente.colonia, COUNT(*)
                    AS total_visitas, ruta.nombre AS nombre_ruta, despacho.fecha
                    FROM preventa_planning_detalles AS detalles
                    LEFT JOIN preventa_plannings AS planning ON planning.id = detalles.fk_planning
                    LEFT JOIN preventa_despacho_rutas AS despacho ON despacho.fk_planning = planning.id
                    LEFT JOIN ventas_vendedores AS vendedor ON vendedor.fk_user = planning.fk_usuario
                    LEFT JOIN ventas_clientes AS cliente ON cliente.id = detalles.fk_cliente
                    LEFT JOIN ventas_rutas AS ruta ON ruta.id = cliente.fk_ruta
                    LEFT JOIN ventas_clientes_canal AS canal ON canal.id = cliente.fk_canal
                 LEFT JOIN ventas_clientes_subcanal AS subcanal ON subcanal.id = cliente.fk_subcanal
                    WHERE despacho.fecha BETWEEN '$fechaInicio 00:00:00' AND '$fechaFin 23:59:59' $filtros $sucursal 
                    */
        
        foreach($data as $row) {

            $line = $row->cdVisitInstance."\t".$row->cdRegion."\t".$row->cdStore."\t".$row->cdUser."\t".$row->dtStart."\t".$row->cdStatus."\n";
            
            fwrite($file, $line);
        }

        fclose($file);
        // die();

        try {

            $connection = ssh2_connect(env('FTP_IP'), 22);
            ssh2_auth_password($connection, env('FTP_USER'), env('FTP_PASSWORD'));

            $sftp = ssh2_sftp($connection);

            $resFile = fopen("ssh2.sftp://{$sftp}/Import/Inbox/".$file_name, 'w');
            $srcFile = fopen($file_full_name, 'r');
            
            $writtenBytes = stream_copy_to_stream($srcFile, $resFile);

            fclose($resFile);
            fclose($srcFile);

            echo $file_name." exportado exitosamente\n";
        }
        catch(Exception $e) {

            $msg = $e->getMessage();
            $this->error($msg);
        }
    }
}