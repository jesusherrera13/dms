<?php

namespace App\Logica\Inventario;

use Illuminate\Support\Facades\DB;

class InventarioSincronizar {

    public function __construct() {
    }

    function upload() {

        if(!file_exists(env('PATH_UPLOAD'))) {
            mkdir(env('PATH_UPLOAD'), 0777);
        }

        $file_name = env('INTERFAZ_INVENTARIO')."_".date('Ymdhis').".txt";
        $file_full_name = getcwd()."/".env('PATH_UPLOAD')."/".$file_name;

        $file =  fopen($file_full_name,"a");

        /* 
        $query = DB::table('ventas_productos')
                    ->leftJoin("inventarios_almacenes_productos as almprod", "almprod.fk_producto","prod.id")
                    ->leftJoin("inventarios_almacenes as alm", function($join) {
                        
                        $join->on("alm.id", "almprod.fk_almacen");
                        $join->on("almprod.tipo", "TIENDA");
                    })
                    ->leftJoin("erp_sucursales as suc", "suc.id","alm.fk_sucursal")
                    ->select(
                        "prod.id as cdProduct",
                        "almprod.fk_almacen as cdWarehouse",
                        "almprod.existencia as vlBeginningQuantity"
                    )
                    ->whereNotNull("alm.id")
                    ->where("prod.id_grupo", 10); 
        */


        $query = DB::table("erp_sucursales as suc")
                    // ->leftJoin("inventarios_almacenes as alm", "alm.fk_sucursal","prod.id")
                    ->leftJoin("inventarios_almacenes as alm", function($join) {
                        $join->on("alm.fk_sucursal", "suc.id");
                        $join->where("alm.tipo", "TIENDA");
                    })
                    ->leftJoin("inventarios_almacenes_productos as almprod", "almprod.fk_almacen","alm.id")
                    ->leftJoin("ventas_productos as prod", "prod.id","almprod.fk_producto")
                    ->select(
                        "prod.id",
                        "prod.clave as cdProduct",
                        "almprod.fk_almacen as cdWarehouse",
                        "almprod.existencia as vlBeginningQuantity",
                        /* 
                        DB::raw("
                        (
                            select round(invdet.saldo*invdet.precio_con_impuestos,2)
                            from erp_id411_intmex_tester.inventarios_movimiento_detalle as invdet
                            where invdet.fecha>=concat(substr(curdate(),1,7),'-01 00:00:00') and invdet.fk_almacen=alm.id and invdet.fk_producto=almprod.fk_producto
                            order by invdet.fecha
                            limit 1
                        ) as vlBeginningMonthAmount
                        ") 
                        */
                    )
                    ->whereNotNull("alm.id")
                    ->where("prod.id_grupo", 10)
                    // ->where("alm.id", 9) // PRUEBAS COMENTAR
                    // ->where("almprod.fk_producto", 844) // PRUEBAS COMENTAR
                    // ->limit(9)
                    ;
        
        // dd($query->toSql());
        $data = $query->get();

        // dd($data);
        
        foreach($data as $row) {

            // echo $row->cdWarehouse."|".$row->id."\n<br>";
            
            $query = DB::table("inventarios_movimiento_detalle as det")
                        ->select(
                            DB::raw("round(ifnull(det.saldo*det.precio_con_impuestos,0),2) as vlBeginningMonthAmount")
                        )
                        ->whereRaw("det.fecha>=concat(substr(curdate(),1,7),'-01 00:00:00')")
                        ->where("det.fk_almacen", $row->cdWarehouse)
                        ->where("det.fk_producto", $row->id)
                        ->orderBy("det.fecha")
                        ->limit(1);
            // dd($query->toSql());
            $data_ = $query->get();
            // dd($data_);
            $row->vlBeginningMonthAmount = empty($data_[0]) ? "NULL" : $data_[0]->vlBeginningMonthAmount;

            $query = DB::table("inventarios_movimiento_detalle as det")
                        ->select(
                            DB::raw("round(ifnull(sum(det.entrada),0),2) as vlReceivedQuantity")
                        )
                        ->whereRaw("det.fecha>=concat(substr(curdate(),1,7),'-01 00:00:00')")
                        ->where("det.fk_almacen", $row->cdWarehouse)
                        ->where("det.fk_producto", $row->id)
                        // ->orderBy("det.fecha")
                        // ->limit(1)
                        ;
            // dd($query->toSql());
            $data_ = $query->get();
            // dd($data_);
            // if($data_) $row->vlReceivedQuantity = $data_[0]->vlReceivedQuantity;
            // $row->vlReceivedQuantity = $data_ ? $data_[0]->vlReceivedQuantity : 'NULL';
            $row->vlReceivedQuantity = empty($data_[0]) ? 'NULL' : $data_[0]->vlReceivedQuantity;

            // echo $data_[0]->vlReceivedQuantity."\n<br>";

            $query = DB::table("inventarios_movimiento_detalle as det")
                        ->select(
                            DB::raw("round(ifnull(sum(det.salida),2),2) as vlDispatchedQuantity")
                        )
                        ->whereRaw("det.fecha>=concat(substr(curdate(),1,7),'-01 00:00:00')")
                        ->where("det.fk_almacen", $row->cdWarehouse)
                        ->where("det.fk_producto", $row->id)
                        // ->orderBy("det.fecha")
                        // ->limit(1)
                        ;
            // dd($query->toSql());
            $data_ = $query->get();
            // dd($data_);
            // if($data_) $row->vlDispatchedQuantity = $data_[0]->vlDispatchedQuantity;
            // $row->vlDispatchedQuantity = $data_ ? $data_[0]->vlDispatchedQuantity : 'NULL';
            $row->vlDispatchedQuantity = empty($data_[0]) ? 'NULL' : $data_[0]->vlDispatchedQuantity;
            // dd($row);

            $query = DB::table("inventarios_movimiento_detalle as det")
                        ->select(
                            DB::raw("ifnull(substr(det.fecha,1,10),'NULL') as dtStock")
                        )
                        ->whereRaw("det.fecha>=concat(substr(curdate(),1,7),'-01 00:00:00')")
                        ->where("det.fk_almacen", $row->cdWarehouse)
                        ->where("det.fk_producto", $row->id)
                        ->orderBy("det.fecha", "desc")
                        ->limit(1);
            // dd($query->toSql());
            $data_ = $query->get();
            // dd($data_);
            // if($data_) $row->dtStock = $data_[0]->dtStock;
            // $row->dtStock = $data_ ? $data_[0]->dtStock : 'NULL';
            $row->dtStock = empty($data_[0]) ? 'NULL' : $data_[0]->dtStock;

            // print_r($row);

            $line = $row->cdProduct."\t".$row->cdWarehouse."\t".$row->vlBeginningQuantity."\t".$row->vlBeginningMonthAmount;
            $line.= "\t".$row->vlReceivedQuantity."\t".$row->vlDispatchedQuantity."\t".$row->dtStock."\n";
            
            fwrite($file, $line);
            echo "*";
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