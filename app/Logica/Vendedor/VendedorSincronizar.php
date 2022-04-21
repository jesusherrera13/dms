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

        $query = DB::table('erp_sucursales')
                    ->select(
                        'id as cdRegion',
                        // DB::raw("REPLACE(REPLACE(nombre,'Comercializadora ',''),' ','_') as cdRegion"),
                        DB::raw("'TERRI' as cdRegionType"),
                        DB::raw("REPLACE(nombre,'Comercializadora ','') as dsRegion"),DB::raw("'ACT' as cdRegionStatus"),DB::raw("NULL as cdParentRegion"),
                        DB::raw("NULL as cdUser"),DB::raw("NULL as nmFirstName"),DB::raw("NULL as nmLastName"),
                        DB::raw("NULL as nrPhone1"),DB::raw("NULL as nrPhone2"),DB::raw("NULL as Email"),DB::raw("NULL as cdUserStatus")
                    );

        // dd($query->toSql());
        
        $data = $query->get();
        
        foreach($data as $row) {

            $line = $row->cdRegion."\t".$row->cdRegionType."\t".$row->dsRegion."\t".$row->cdRegionStatus."\t".$row->cdParentRegion."\t".$row->cdUser;
            $line.= "\t".$row->nmFirstName."\t".$row->nmLastName."\t".$row->nrPhone1."\t".$row->nrPhone2."\t".$row->Email."\t".$row->cdUserStatus."\n";
            
            fwrite($file, $line);

            
            $query_ = DB::table('system_acl_user_rol as usr_rol')
                ->leftJoin('coral_erp.system_usuarios as usr','usr.id','usr_rol.fk_user')
                ->leftJoin('system_acl_rol as rol','rol.id','usr_rol.fk_rol')
                ->leftJoin('ventas_vendedores as ven','ven.fk_user','usr.id')
                ->select(
                    // usr_rol.fk_user,usr_rol.fk_rol,usr.username,usr.nombre,ven.bolsa_acumulada,usr_rol.fk_sucursal,suc.nombre as sucursal
                    'usr.username as cdRegion',
                    DB::raw("'ZONE' as cdRegionType"),
                    'usr.nombre as dsRegion',
                    DB::raw("'ACT' as cdRegionStatus"),
                    DB::raw("'$row->cdRegion' as cdParentRegion"),
                    'usr.username as cdUser',
                    'usr.nombre as nmFirstName',
                    'usr.apellidoss as nmLastName',
                    DB::raw("'NULL' as nrPhone1"),
                    DB::raw("'NULL' as nrPhone2"),'usr.Email',
                    DB::raw("IF(ven.estado='ACTIVO','ACT','DEACT') as cdUserStatus")
                )
                ->where('ven.fk_sucursal', $row->cdRegion)
                ->where('usr_rol.fk_rol', 4)
                ->whereRaw("substr(usr.nombre,1,3) in ('MXL', 'TIJ', 'MZT','CLN')");
            
            $data_ = $query_->get();
            
            foreach($data_ as $row_) {

                $line = substr($row_->cdRegion.'_'.$row_->cdParentRegion, 0, 30);
                $line.= "\t".$row_->cdRegionType."\t".substr($row->dsRegion." - ".$row_->dsRegion, 0, 30);
                $line.= "\t".$row_->cdRegionStatus."\t".$row_->cdParentRegion."\t".$row_->cdUser;
                $line.= "\t".$row_->nmFirstName."\t".$row_->nmLastName."\t".$row_->nrPhone1."\t".$row_->nrPhone2."\t".$row_->Email."\t".$row_->cdUserStatus."\n";
                
                fwrite($file, $line);
            }
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