<?php

namespace App\Logica\Cliente;

use Illuminate\Support\Facades\DB;

class ClienteSincronizar {

    public function __construct() {
    }

    function upload() {

        if(!file_exists(env('PATH_UPLOAD'))) {
            mkdir(env('PATH_UPLOAD'), 0777);
        }

        $file_name = env('INTERFAZ_CLIENTE')."_".date('Ymdhis').".txt";
        $file_full_name = getcwd()."/".env('PATH_UPLOAD')."/".$file_name;

        $file =  fopen($file_full_name,"a");

        $query = DB::table('ventas_clientes as cli')
                    ->leftJoin('ventas_vendedores as ven','ven.id','cli.fk_vendedor')
                    ->leftJoin('coral_erp.system_usuarios as usr','usr.id','ven.fk_user')
                    ->leftJoin('erp_sucursales as suc','suc.id','ven.fk_sucursal')
                    ->leftJoin('system_ubicacion_municipios as mun','mun.id','suc.fk_municipio')
                    ->leftJoin('system_acl_user_rol as usr_rol','usr_rol.fk_user','usr.id')
                    
                    ->select(
                        'cli.id as cdStore',
                        DB::raw("IF(cli.estado='ACTIVO', 'ACT','DEACT') as cdStatus"),
                        DB::raw("'LATAM' as cdStoreBrand"),
                        DB::raw("'NULL' as dsName"), //
                        DB::raw("'NULL' as dsCorporateName"), //
                        DB::raw("1 as cdClass1"),
                        DB::raw("11 as cdClass2"),
                        // DB::raw("cli.id_municipio as cdCity"), // Estado y municipio del cliente
                        // DB::raw("cli.id_ciudad as cdCity"), // Solicitan código de ciudad, solo llegamos hasta municipio
                        
                        // MX|14|14002	Mexicali	MX|14	Baja California
                        // MX|25|25007	Culiac�n	MX|25	Sinaloa
                        // MX|14|14005	Tijuana	MX|14	Baja California
                        // MX|25|25012	Mazatl�n	MX|25	Sinaloa

                        DB::raw("
                            if(
                                ven.fk_sucursal=1,'14002',
                                if(
                                    ven.fk_sucursal=2,'25007',
                                    if(
                                        ven.fk_sucursal=3,'14005',
                                        if(
                                            ven.fk_sucursal=4,'25012',
                                            '14002'
                                        )
                                    )
                                )
                            ) cdCity"
                        ),
                        DB::raw("CONCAT(usr.username,'_',ven.fk_sucursal) as cdRegion"), // Obtenerla de las sucursales
                        // 'cli.localidad as nmAddress',
                        DB::raw("'NULL' as nmAddress"),
                        DB::raw("'NULL' as nrAddress"),
                        DB::raw("'NULL' as dsAddressComplement"),
                        DB::raw("'NULL' as nrZipCode"),
                        DB::raw("'NULL' as nmNeighborhood"),
                    )
                    ->where('cli.fk_vendedor', '>', 0)
                    ->where('ven.fk_sucursal', '>', 0)
                    ->whereNotNull('suc.nombre')
                    ->where('usr_rol.fk_rol', 4)
                    ->whereRaw("substr(usr.nombre,1,3) in ('MXL', 'TIJ', 'MZT','CLN')")
                    ;
                    // ->limit(1)
        // dd($query->toSql());
        $data = $query->get();
        
        foreach($data as $row) {

            $line = $row->cdStore."\t".$row->cdStatus."\t".$row->cdStoreBrand."\t".$row->dsName."\t".$row->dsCorporateName."\t".$row->cdClass1;
            $line.= "\t".$row->cdClass2."\t".$row->cdCity."\t".$row->cdRegion."\t".$row->nmAddress."\t".$row->nrAddress;
            $line.= "\t".$row->dsAddressComplement."\t".$row->nrZipCode."\t".$row->nmNeighborhood."\n";
            
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