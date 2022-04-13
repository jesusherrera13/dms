<?php

namespace App\Logica\Vendedor;

use Illuminate\Support\Facades\DB;

class VendedorSincronizar {

    public function __construct() {
    }

    function upload() {

        if(!file_exists(env('PATH_UPLOAD'))) {
            mkdir(env('PATH_UPLOAD'), 0777);
        }

        $file_name = env('INTERFAZ_VENDEDOR')."_".date('Ymdhis').".txt";
        $file_full_name = getcwd()."/".env('PATH_UPLOAD')."/".$file_name;

        $file =  fopen($file_full_name,"a");

        $data = DB::table('erp_sucursales')
                    ->select(
                        'id',
                        DB::raw("REPLACE(nombre,' ','') as cdRegion"),DB::raw("'TERRI' as cdRegionType"),
                        'nombre as dsRegion',DB::raw("'ACT' as cdRegionStatus"),DB::raw("NULL as cdParentRegion"),
                        DB::raw("NULL as cdUser"),DB::raw("NULL as nmFirstName"),DB::raw("NULL as nmLastName"),
                        DB::raw("NULL as nrPhone1"),DB::raw("NULL as nrPhone2"),DB::raw("NULL as Email"),DB::raw("NULL as cdUserStatus")
                    )
                    ->get();
        
        foreach($data as $row) {

            $line = $row->cdRegion."\t".$row->cdRegionType."\t".$row->dsRegion."\t".$row->cdRegionStatus."\t".$row->cdParentRegion."\t".$row->cdUser;
            $line.= "\t".$row->nmFirstName."\t".$row->nmLastName."\t".$row->nrPhone1."\t".$row->nrPhone2."\t".$row->Email."\t".$row->cdUserStatus."\n";
            
            fwrite($file, $line);

            $data_ = DB::table('erp_id411_intmex_tester.ventas_vendedores as ven')
                ->leftJoin('coral_erp.system_usuarios as usr','usr.email','ven.email')
                ->select(
                    'usr.username as cdRegion',DB::raw("'ZONE' as cdRegionType"),'ven.nombre as dsRegion',DB::raw("'ACT' as cdRegionStatus"),
                    DB::raw("'$row->cdRegion' as cdParentRegion"),'usr.username as cdUser','usr.nombre as nmFirstName','usr.apellidoss as nmLastName',
                    DB::raw("NULL as nrPhone1"),DB::raw("NULL as nrPhone2"),'usr.Email',DB::raw("'ACT' as cdUserStatus")
                )
                ->where('ven.fk_sucursal', $row->id)
                ->whereNotNull('usr.email')
                ->get();
            
                foreach($data_ as $row_) {

                    $line = $row_->cdRegion."\t".$row_->cdRegionType."\t".$row_->dsRegion."\t".$row_->cdRegionStatus."\t".$row_->cdParentRegion."\t".$row_->cdUser;
                    $line.= "\t".$row_->nmFirstName."\t".$row_->nmLastName."\t".$row_->nrPhone1."\t".$row_->nrPhone2."\t".$row_->Email."\t".$row_->cdUserStatus."\n";
                    
                    fwrite($file, $line);
                }
        }

        fclose($file);

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