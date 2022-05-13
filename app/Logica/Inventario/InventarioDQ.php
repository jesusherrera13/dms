<?php

namespace App\Logica\Inventario;

use Illuminate\Support\Facades\DB;

class InventarioDQ {

    public function __construct() {
    }

    function upload() {

        if(!file_exists(env('PATH_UPLOAD'))) {
            mkdir(env('PATH_UPLOAD'), 0777);
        }

        $file_name = env('INTERFAZ_INVENTARIO_DQ')."_".date('Ymdhis').".txt";
        $file_full_name = getcwd()."/".env('PATH_UPLOAD')."/".$file_name;

        $file =  fopen($file_full_name,"a");
        
        /* $query = DB::table("erp_sucursales as suc")
                    // ->leftJoin("inventarios_almacenes as alm", "alm.fk_sucursal","prod.id")
                    ->leftJoin("inventarios_almacenes as alm", function($join) {
                        $join->on("alm.fk_sucursal", "suc.id");
                        $join->where("alm.tipo", "TIENDA");
                    })
                    ->leftJoin("inventarios_movimiento_detalle as det", "det.fk_almacen","alm.id")
                    ->leftJoin("ventas_productos as prod", "prod.id","det.fk_producto")
                    ->select(
                        DB::raw("curdate() as dtMonthYear"),
                        "prod.clave as cdProduct",
                        "det.fk_almacen as cdWarehouse",
                        DB::raw("
                            ifnull(round(det.saldo*prod.precio_con_impuestos),0) as vlClosedInventary
                            "
                        ),
                        DB::raw("round(det.saldo) as vlCloseMonthQuantity"),
                        "prod.id",
                    )
                    ->whereNotNull("alm.id")
                    ->where("prod.id_grupo", 10)
                    ->whereRaw("det.fecha<=concat(curdate(),' 23:23:59')")
                    // ->whereRaw("det.fecha<=concat(date_sub(curdate(),interval 1 month),' 00:00:00')")
                    ->orderBy("alm.nombre")
                    // ->where("alm.id", 9) // PRUEBAS COMENTAR
                    // ->where("almprod.fk_producto", 844) // PRUEBAS COMENTAR
                    ->limit(1)
                    ;
        
        dd($query->toSql());

        $query = DB::table("inventarios_movimiento_detalle as det")
                    // ->leftJoin("ventas_productos as prod", "prod.id", "det.fk_producto")
                    ->select(
                        DB::raw("
                            if(
                                ifnull(det.saldo*".$producto->precio_con_impuestos.",0) > 0,
                                round(ifnull(det.saldo*".$producto->precio_con_impuestos.",0),2),
                                round(".$producto->precio_con_impuestos.",2)
                            ) as vlBeginningMonthAmount"
                        )
                    )
                    // ->whereRaw("det.fecha<=concat(substr(curdate(),1,7),'-31 00:00:00')")
                    ->whereRaw("det.fecha<=concat(date_sub(curdate(),interval 1 month),' 00:00:00')")
                    ->where("det.fk_almacen", $producto->cdWarehouse)
                    ->where("det.fk_producto", $producto->id)
                    // ->where("prod.precio_con_impuestos",">",0)
                    ->orderBy("det.fecha")
                    ->limit(1);


        dd($query->toSql());
        $data = $query->get(); 

        dd($data);
       

        foreach($data as $sucursal) {

            
            
            fwrite($file, $line);
        } */

        $productos = DB::table("ventas_productos")
                    ->select(
                        "clave as cdProduct",
                        "id",
                        "precio_con_impuestos",
                        DB::raw("if(estado='ACTIVO','ACT','DEACT') as cdStatusX"),
                    )
                    ->where("id_grupo", 10)
                    ->where("precio_con_impuestos",">",0)
                    // ->limit(1)
                    ->get();
        // dd($productos);

        $query = DB::table("erp_sucursales as suc")
                    ->select("suc.id as fk_sucursal","alm.id as cdWarehouse")
                    // ->leftJoin("inventarios_almacenes as alm", "alm.fk_sucursal","prod.id")
                    ->leftJoin("inventarios_almacenes as alm", function($join) {
                        $join->on("alm.fk_sucursal", "suc.id");
                        $join->where("alm.tipo", "TIENDA");
                    })
                    ->whereNotNull("alm.id")
                    // ->where("prod.id_grupo", 10)
                    ->orderBy("alm.nombre")
                    ->where("alm.id", 9) // PRUEBAS COMENTAR
                    // ->where("almprod.fk_producto", 844) // PRUEBAS COMENTAR
                    // ->limit(9)
                    ;
        
        $data = $query->get(); 
        // dd($data);
        
        foreach($data as $sucursal) {

            foreach($productos as $producto) {

                $producto->cdWarehouse = $sucursal->cdWarehouse;
                // dd($producto);
                
                // echo $sucursal->fk_sucursal."|".$producto->cdProduct."\n<br>";
                // echo "cdWarehouse: ".$sucursal->cdWarehouse."\n<br>";
                // echo "fk_almacen: ".$sucursal->cdWarehouse."\n<br>";
                // echo "id: ".$producto->cdWarehouse."\n<br>";
                // echo "id: ".$producto->id."\n<br>";

                $query = DB::table("inventarios_movimiento_detalle as det")
                    // ->leftJoin("ventas_productos as prod", "prod.id", "det.fk_producto")
                    ->select(
                        DB::raw("date_sub(last_day(curdate()),interval 1 month) as dtMonthYear"),
                        // "prod.clave as cdProduct",
                        "det.fk_almacen as cdWarehouse",
                        DB::raw("
                            ifnull(round(det.saldo*".$producto->precio_con_impuestos."),0) as vlClosedInventary
                            "
                        ),
                        DB::raw("ifnull(round(det.saldo),0) as vlCloseMonthQuantity"),
                    )
                    // ->whereRaw("det.fecha>='2022-04-01 00:00:00'")
                    // ->whereRaw("det.fecha>=concat(substr(curdate(),1,7),'-01 00:00:00')")
                    // ->whereRaw("det.fecha<=concat(curdate(),' 23:23:59')")
                    ->whereRaw("det.fecha<=concat('2022-04-30 00:00:00 23:23:59')")
                    ->where("det.fk_almacen", $producto->cdWarehouse)
                    ->where("det.fk_producto", $producto->id)
                    // ->where("prod.precio_con_impuestos",">",0)
                    ->orderBy("det.fecha","desc")
                    ->limit(1);
                // ->leftJoin("inventarios_almacenes_productos as almprod", "almprod.fk_almacen","alm.id")
                
                // dd($query->toSql());
                $data_ = $query->get();

                if(empty($data_[0])) continue;

                $producto->dtMonthYear = $data_[0]->dtMonthYear;
                $producto->vlClosedInventary = $data_[0]->vlClosedInventary;
                $producto->vlCloseMonthQuantity = $data_[0]->vlCloseMonthQuantity;

                // dd($producto);
                

                $line = $producto->dtMonthYear."\t".$producto->cdProduct."\t".$producto->cdWarehouse."\t".$producto->vlClosedInventary;
                $line.= "\t".$producto->vlCloseMonthQuantity."\n"; 
                
                fwrite($file, $line);
            }
        }

        fclose($file);
        // die();

        try {

            echo "Interfaz `".env('INTERFAZ_INVENTARIO_DQ')."`\n";
            echo "\n\t`$file_name`"; 

            $connection = ssh2_connect(env('FTP_IP'), 22);
            ssh2_auth_password($connection, env('FTP_USER'), env('FTP_PASSWORD'));

            $sftp = ssh2_sftp($connection);

            $resFile = fopen("ssh2.sftp://{$sftp}/Import/Inbox/".$file_name, 'w');
            $srcFile = fopen($file_full_name, 'r');
            
            $writtenBytes = stream_copy_to_stream($srcFile, $resFile);

            fclose($resFile);
            fclose($srcFile);

            echo "\n\nFin de la exportación\n";
        }
        catch(Exception $e) {

            $msg = $e->getMessage();
            $this->error($msg);
        }
    }
}