<?php

namespace App\Logica\Ruta;

use Illuminate\Support\Facades\DB;

class RutaSincronizar {

    public function __construct() {
    }

    function upload() {

        if(!file_exists(env('PATH_UPLOAD'))) {
            mkdir(env('PATH_UPLOAD'), 0777);
        }

        $file_name = env('INTERFAZ_RUTA')."_".date('Ymdhis').".txt";
        $file_full_name = getcwd()."/".env('PATH_UPLOAD')."/".$file_name;

        $file =  fopen($file_full_name,"a");

        $query = DB::table('ventas_rutas')
                    ->select(
                        'id as cdRoute','nombre as nmRoute',DB::raw("'ACT' as cdStatus"),
                    )
                    ->where('nombre', '!=', '');
                    // ->limit(1)
        
        // dd($query->toSql());
        $data = $query->get();
        
        foreach($data as $row) {

            $line = $row->cdRoute."\t".$row->nmRoute."\t".$row->cdStatus."\n";
            
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