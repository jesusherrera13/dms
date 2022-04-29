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

        $query = DB::table('preventa_planning_detalles as detalles')
                    ->leftJoin('preventa_plannings as planning', 'planning.id','detalles.fk_planning')
                    //->leftJoin('preventa_despacho_rutas AS despacho', 'despacho.fk_planning','planning.id')
                    ->leftJoin('ventas_clientes AS cli', 'cli.id', 'detalles.fk_cliente')
                    ->leftJoin('ventas_vendedores as ven', 'ven.id', 'cli.fk_vendedor')
                    ->leftJoin('coral_erp.system_usuarios as usr','usr.id','ven.fk_user')
                    ->leftJoin('erp_sucursales as suc','suc.id','ven.fk_sucursal')
                    ->leftJoin('system_acl_user_rol as usr_rol','usr_rol.fk_user','usr.id')
                    // 'ven.id','cli.fk_vendedor'
                    ->leftJoin('ventas_rutas AS ruta', 'ruta.id', 'cli.fk_ruta')
                    ->leftJoin('ventas_clientes_canal AS canal', 'canal.id', 'cli.fk_canal')
                    ->leftJoin('ventas_clientes_subcanal AS subcanal', 'subcanal.id', 'cli.fk_subcanal')
                    ->select(
                        "planning.id",
                        DB::raw("concat(planning.id,'_',detalles.fk_cliente) as cdVisitInstance"),
                        // 'planning.fk_sucursal as cdRegion',
                        // DB::raw("'ZONE' as cdRegion"),
                        DB::raw("CONCAT(usr.username,'_',ven.fk_sucursal) as cdRegion"),
                        'detalles.fk_cliente as cdStore',
                        'usr.username as cdUser',
                        // DB::raw("SUBSTR(despacho.fecha,1,10) as dtStart"),
                        DB::raw("
                            (
                                select 
                                SUBSTR(despacho.fecha,1,10)
                                from preventa_despacho_rutas as despacho
                                where despacho.fk_planning = planning.id
                                order by despacho.fecha desc
                                limit 1
                            ) as dtStart
                        "),
                        DB::raw("
                            (
                                select 
                                despacho.id
                                from preventa_despacho_rutas as despacho
                                where despacho.fk_planning = planning.id
                                order by despacho.fecha desc
                                limit 1
                            ) as despacho_id
                        "),
                        /* DB::raw("
                            IF(
                                despacho.estado='TERMINADO','FINAL',
                                IF(
                                    despacho.estado IN ('NUEVO','AUTORIZADO','EN_RUTA'),'PLAN',
                                    'CANC'
                                )
                            ) AS cdStatus"
                        ), */
                    )
                    ->where('cli.fk_vendedor', '>', 0)
                    ->where('ven.fk_sucursal', '>', 0)
                    ->whereNotNull('suc.nombre')
                    ->where('usr_rol.fk_rol', 4)
                    ->whereRaw("substr(usr.nombre,1,3) in ('MXL', 'TIJ', 'MZT','CLN')")
                    // ->whereRaw("despacho.fecha >=(select date_format(now(),'%Y-%m-01'))")
                    ->whereRaw("
                            (
                                select 
								SUBSTR(despacho.fecha,1,10)
								from preventa_despacho_rutas as despacho
								where despacho.fk_planning = planning.id
								order by despacho.fecha desc
								limit 1
							) >=(select date_format(now(),'%Y-%m-01'))
                    ")
                    ->where("cli.estado", "ACTIVO")
                    ->where("ven.estado", "ACTIVO")
                    ->orderBy("planning.id")
                    ->orderBy("detalles.fk_cliente")
                    // ->where("planning.id", 20)
                    ;
                    // ->limit(1)

        // dd($query->toSql());

        $data = $query->get();

        // dd($data);

        foreach($data as $row) {
            // echo $row->id."|".$row->despacho_id."\n<br>";
            $query = DB::table("preventa_despacho_rutas")
                    ->select(
                        DB::raw("substr(fecha,1,10) as dtStart"),
                        DB::raw("
                            if(
                                estado='TERMINADO','FINAL',
                                if(
                                    estado IN ('NUEVO','AUTORIZADO','EN_RUTA'),'PLAN',
                                    'CANC'
                                )
                            ) AS cdStatus
                        "),
                    )
                    ->where("id", $row->despacho_id);
            
            // dd($query->toSql());
            $data_ = $query->get();

            // dd($data_);
            $row->cdStatus = $data_[0]->cdStatus;
            // dd($row);
            

            $line = $row->cdVisitInstance;
            $line.= "\t".$row->cdRegion."\t".$row->cdStore."\t".$row->cdUser."\t".$row->dtStart."\t".$row->cdStatus."\n";
            
            fwrite($file, $line);
        }

        fclose($file);
        // die();

        try {

            echo "\nExportando `$file_name`"; 

            $connection = ssh2_connect(env('FTP_IP'), 22);
            ssh2_auth_password($connection, env('FTP_USER'), env('FTP_PASSWORD'));

            $sftp = ssh2_sftp($connection);

            $resFile = fopen("ssh2.sftp://{$sftp}/Import/Inbox/".$file_name, 'w');
            $srcFile = fopen($file_full_name, 'r');
            
            $writtenBytes = stream_copy_to_stream($srcFile, $resFile);

            fclose($resFile);
            fclose($srcFile);

            echo "\n\texportado exitosamente\n";
        }
        catch(Exception $e) {

            $msg = $e->getMessage();
            $this->error($msg);
        }
    }
}