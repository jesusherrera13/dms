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

        $data = DB::table('ventas_vendedores')->get();
        $file_name = env('INTERFAZ_VENDEDOR')."_".date('Ymdhis').".txt";
        $file_full_name = getcwd()."/".env('PATH_UPLOAD')."/".$file_name;

        if(!file_exists($file_full_name)) {
            $file =  fopen($file_full_name,"a");
        }

        $file =  fopen($file_full_name,"a");
        
        foreach($data as $row) {
            $line = $row->id."\t".$row->nombre."\n";
            fwrite($file, $line);
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