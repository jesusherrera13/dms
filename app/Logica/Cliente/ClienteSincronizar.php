<?php

namespace App\Logica\Cliente;

use Illuminate\Support\Facades\DB;

class ClienteSincronizar {

    public function __construct() {
    }

    function upload() {

        /* 
        SELECT ven.fk_sucursal,cli.* FROM erp_id411_intmex_tester.ventas_clientes as cli
        left join erp_id411_intmex_tester.ventas_vendedores as ven on ven.id=cli.fk_vendedor
        where cli.localidad like '%california%' and ven.fk_sucursal=1;

        SELECT ven.fk_sucursal,cli.* FROM erp_id411_intmex_tester.ventas_clientes as cli
        left join erp_id411_intmex_tester.ventas_vendedores as ven on ven.id=cli.fk_vendedor
        where cli.localidad like '%california%' and ven.fk_sucursal=3; 
        */
        /* 


        // dd($data);

        foreach($data as $row) {

            // dd($row->nombre);

            // $data_ = DB::table('ventas_clientes')->where('localidad', 'like', '%'.$row->nombre.'%')->get();
            // $data_ = DB::table('ventas_clientes')->where('localidad', $row->nombre)->get();
            
            DB::table('ventas_clientes')->where('localidad', $row->nombre)->update(['id_ciudad' => $row->id_ciudad]);
            foreach($data_ as $row_) {
                // dd($row->id_ciudad);
                // DB::table('ventas_clientes')->where('id', $row_->id)->update(['id_ciudad' => $row->id_ciudad]);
            }

            // $data_ = DB::where('nombre', 'like', '%'.$row->nombre.'%')->update();

            // dd($data_);
            
        }


        die("xxx"); */

        if(!file_exists(env('PATH_UPLOAD'))) {
            mkdir(env('PATH_UPLOAD'), 0777);
        }

        $file_name = env('INTERFAZ_CLIENTE')."_".date('Ymdhis').".txt";
        $file_full_name = getcwd()."/".env('PATH_UPLOAD')."/".$file_name;

        $file =  fopen($file_full_name,"a");

        $data = DB::table('ventas_clientes as cli')
                    ->leftJoin('ventas_vendedores as ven','ven.id','cli.fk_vendedor')
                    ->leftJoin('coral_erp.system_usuarios as usr','usr.id','ven.fk_user')
                    ->leftJoin('erp_sucursales as suc','suc.id','ven.fk_sucursal')
                    ->leftJoin('system_ubicacion_municipios as mun','mun.id','suc.fk_municipio')
                    ->select(
                        'cli.clave as cdStore',
                        DB::raw("IF(cli.estado='ACTIVO', 'ACT','DEACT') as cdStatus"),
                        DB::raw("'LATAM' as cdStoreBrand"),
                        DB::raw("NULL as dsName"), //
                        DB::raw("NULL as dsCorporateName"), //
                        DB::raw("1 as cdClass1"),
                        DB::raw("NULL as cdClass2"),
                        // DB::raw("cli.id_municipio as cdCity"), // Estado y municipio del cliente
                        // DB::raw("cli.id_ciudad as cdCity"), // Solicitan código de ciudad, solo llegamos hasta municipio
                        DB::raw("
                            if(
                                ven.fk_sucursal=1,'14002',
                                if(
                                    ven.fk_sucursal=2,'25007',
                                    if(
                                        ven.fk_sucursal=3,'25007',
                                        if(
                                            ven.fk_sucursal=4,'25012',
                                            NULL
                                        )
                                    )
                                )
                            ) cdCity"
                        ),
                        DB::raw("CONCAT(ven.fk_sucursal,'_',usr.username) as cdRegion"), // Obtenerla de las sucursales
                        // 'cli.localidad as nmAddress',
                        DB::raw("NULL as nmAddress"),
                        DB::raw("NULL as nrAddress"),
                        DB::raw("NULL as dsAddressComplement"),
                        DB::raw("NULL as nrZipCode"),
                        DB::raw("NULL as nmNeighborhood"),
                    )
                    // ->limit(1)
                    ->get();
        
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