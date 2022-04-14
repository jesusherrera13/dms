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

        $data = DB::table('ventas_clientes as cli')
                    ->leftJoin('erp_id411_intmex_tester.ventas_vendedores as ven','ven.id','cli.fk_vendedor')
                    ->leftJoin('coral_erp.system_usuarios as usr','usr.id','ven.fk_user')
                    ->select(
                        'cli.clave as cdStore',DB::raw("IF(cli.estado='ACTIVO', 'ACT','DEACT') as cdStatus"),
                        DB::raw("'LATAM' as cdStoreBrand"),DB::raw("SUBSTR(cli.nombre,1,30) as dsName"),
                        DB::raw("SUBSTR(cli.nombre,1,30) as dsCorporateName"),
                        DB::raw("NULL as cdClass1"),
                        DB::raw("NULL as cdClass2"),
                        DB::raw("NULL as cdCity"),
                        // DB::raw("NULL as cdRegion"),
                        DB::raw("usr.username as cdRegion"),
                        'cli.localidad as nmAddress',
                        DB::raw("NULL as nrAddress"),
                        DB::raw("NULL as dsAddressComplement"),
                        DB::raw("NULL as nrZipCode"),
                        DB::raw("NULL as nmNeighborhood"),
                        // ,
                        // DB::raw("REPLACE(REPLACE(nombre,'Comercializadora ',''),' ','_') as cdRegion"),DB::raw("'TERRI' as cdRegionType"),
                        // DB::raw("REPLACE(nombre,'Comercializadora ','') as dsRegion"),DB::raw("'ACT' as cdRegionStatus"),DB::raw("NULL as cdParentRegion"),
                        // DB::raw("NULL as cdUser"),DB::raw("NULL as nmFirstName"),DB::raw("NULL as nmLastName"),
                        // DB::raw("NULL as nrPhone1"),DB::raw("NULL as nrPhone2"),DB::raw("NULL as Email"),DB::raw("NULL as cdUserStatus")
                    )
                    // ->limit(1)
                    ->get();
        // dd($data);

        
        foreach($data as $row) {

            $line = $row->cdStore."\t".$row->cdStatus."\t".$row->cdStoreBrand."\t".$row->dsName."\t".$row->dsCorporateName."\t".$row->cdClass1;
            $line.= "\t".$row->cdClass2."\t".$row->cdCity."\t".$row->cdRegion."\t".$row->nmAddress."\t".$row->nrAddress;
            $line.= "\t".$row->dsAddressComplement."\t".$row->nrZipCode."\t".$row->nmNeighborhood."\n";
            
            fwrite($file, $line);
        }

        fclose($file);
        die();

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