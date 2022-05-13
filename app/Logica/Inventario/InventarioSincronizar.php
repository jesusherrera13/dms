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
                    )
                    ->whereNotNull("alm.id")
                    ->where("prod.id_grupo", 10)
                    ->orderBy("alm.nombre")
                    // ->where("alm.id", 9) // PRUEBAS COMENTAR
                    // ->where("almprod.fk_producto", 844) // PRUEBAS COMENTAR
                    // ->limit(9)
                    ;
        
        // dd($query->toSql());
        $data = $query->get(); 
        */

        $productos = DB::table("ventas_productos")
                    ->select(
                        "clave as cdProduct",
                        DB::raw("NULL as cdWarehouse"),
                        DB::raw("'0' as vlBeginningMonthAmount"),
                        DB::raw("'0' as vlBeginningMonthQuantity"),
                        DB::raw("'0' as vlReceivedQuantity"),
                        DB::raw("'0' as vlDispatchedQuantity"),
                        DB::raw("NULL as dtStock"),
                        DB::raw("NULL as dtStatus"),
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
                // dd($producto);
                
                // echo $sucursal->fk_sucursal."|".$producto->cdProduct."\n<br>";
                // echo "cdWarehouse: ".$sucursal->cdWarehouse."\n<br>";
                // echo "fk_almacen: ".$sucursal->cdWarehouse."\n<br>";
                // echo "id: ".$producto->id."\n<br>";

                $producto->cdWarehouse = $sucursal->cdWarehouse;
                // $producto->cdWarehouse = $sucursal->cdWarehouse;
                // dd($producto);

                $query = DB::table("inventarios_almacenes_productos as almprod")
                            ->select(
                                /* DB::raw("
                                    if(
                                        almprod.existencia=0,
                                        round(".$producto->precio_con_impuestos."),
                                        round(almprod.existencia*".$producto->precio_con_impuestos.")
                                    ) as vlBeginningMonthAmount"
                                ), */
                                DB::raw("substr(fecha_ultimo_movimiento,1,10) as dtStock")
                            )
                            ->where("almprod.fk_almacen", $sucursal->cdWarehouse)
                            ->where("almprod.fk_producto", $producto->id);
                // ->leftJoin("inventarios_almacenes_productos as almprod", "almprod.fk_almacen","alm.id")
                
                // dd($query->toSql());
                $data_ = $query->get();
                // dd($data_);

                if(empty($data_[0])) continue;

                // $producto->vlBeginningMonthAmount = $data_[0]->vlBeginningMonthAmount;
                $producto->dtStock = $data_[0]->dtStock;

                // echo $producto->cdWarehouse."|".$producto->id."\n<br>";
                $query = DB::table("inventarios_movimiento_detalle as det")
                    // ->leftJoin("ventas_productos as prod", "prod.id", "det.fk_producto")
                    ->select(
                        DB::raw("round(det.saldo) as vlBeginningMonthQuantity"),
                        DB::raw("
                            if(
                                det.saldo=0,
                                round(".$producto->precio_con_impuestos."),
                                round(det.saldo*".$producto->precio_con_impuestos.")
                            ) as vlBeginningMonthAmount
                            "
                        )
                    )
                    ->whereRaw("det.fecha>='2022-04-01 00:00:00'")
                    // ->whereRaw("det.fecha>=concat(substr(curdate(),1,7),'-01 00:00:00')")
                    ->where("det.fk_almacen", $producto->cdWarehouse)
                    ->where("det.fk_producto", $producto->id)
                    // ->where("prod.precio_con_impuestos",">",0)
                    ->orderBy("det.fecha")
                    ->limit(1);
                // ->leftJoin("inventarios_almacenes_productos as almprod", "almprod.fk_almacen","alm.id")
                
                // dd($query->toSql());
                $data_ = $query->get();


                if(empty($data_[0])) {
                    $producto->vlBeginningMonthAmount = 0; 
                    $producto->vlBeginningMonthQuantity = 0; 
                    $producto->cdStatus = 'DEACT';
                }
                else {
                    $producto->vlBeginningMonthAmount = $data_[0]->vlBeginningMonthAmount;
                    $producto->vlBeginningMonthQuantity = $data_[0]->vlBeginningMonthQuantity;
                    $producto->cdStatus = 'ACT';
                }

                $query = DB::table("inventarios_movimiento_detalle as det")
                        ->select(
                            DB::raw("round(ifnull(sum(det.entrada),0)) as vlReceivedQuantity")
                        )
                        // ->whereRaw("det.fecha>=concat(substr(curdate(),1,7),'-01 00:00:00')")
                        ->whereRaw("det.fecha>='2022-04-01 00:00:00'")
                        ->where("det.fk_almacen", $producto->cdWarehouse)
                        ->where("det.fk_producto", $producto->id)
                        // ->orderBy("det.fecha")
                        // ->limit(1)
                        ;
                // dd($query->toSql());
                $data_ = $query->get();

                $producto->vlReceivedQuantity = empty($data_[0]) ? 0 : $data_[0]->vlReceivedQuantity;


                $query = DB::table("inventarios_movimiento_detalle as det")
                        ->select(
                            DB::raw("round(ifnull(sum(det.salida),0)) as vlDispatchedQuantity")
                        )
                        ->whereRaw("det.fecha>='2022-04-01 00:00:00'")
                        ->where("det.fk_almacen", $producto->cdWarehouse)
                        ->where("det.fk_producto", $producto->id)
                        // ->orderBy("det.fecha")
                        // ->limit(1)
                        ;
                        // dd($query->toSql());
                        $data_ = $query->get();
                            // if($producto->id == 80) dd($query->toSql());
                $data_ = $query->get();

                $producto->vlDispatchedQuantity = empty($data_[0]) ? 0 : $data_[0]->vlDispatchedQuantity;

                if(!$producto->cdProduct) continue;
                if($producto->vlBeginningMonthAmount <= 0 && $producto->vlBeginningMonthQuantity <= 0 && $producto->vlReceivedQuantity <= 0 && $producto->vlDispatchedQuantity <= 0) continue;
                /* 
                // dd($producto); */
                $line = $producto->cdProduct."\t".$producto->cdWarehouse."\t".$producto->vlBeginningMonthAmount;
                $line.= "\t".$producto->vlBeginningMonthQuantity;
                $line.= "\t".$producto->vlReceivedQuantity."\t".$producto->vlDispatchedQuantity."\t".$producto->dtStock; 
                $line.= "\t".$producto->cdStatus."\n"; 
                
                fwrite($file, $line);
            }
            
            fwrite($file, $line);
        }

        fclose($file);
        // die();

        try {

            echo "Interfaz `".env('INTERFAZ_INVENTARIO')."`\n";
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