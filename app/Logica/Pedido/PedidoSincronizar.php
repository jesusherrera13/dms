<?php

namespace App\Logica\Pedido;

use Illuminate\Support\Facades\DB;

class PedidoSincronizar {

    public function __construct() {
    }

    function upload() {

        if(!file_exists(env('PATH_UPLOAD'))) {
            mkdir(env('PATH_UPLOAD'), 0777);
        }

        $file_name = env('INTERFAZ_PEDIDO')."_".date('Ymdhis').".txt";
        $file_full_name = getcwd()."/".env('PATH_UPLOAD')."/".$file_name;

        $file_name_i = env('INTERFAZ_PEDIDO_ITEM')."_".date('Ymdhis').".txt";
        $file_full_name_i = getcwd()."/".env('PATH_UPLOAD')."/".$file_name_i;

        $file =  fopen($file_full_name, "a");
        $file_i =  fopen($file_full_name_i, "a");

        echo "Interfaz `".env('INTERFAZ_PEDIDO')."`\n";

        $query = DB::table('ventas_pedidos as ped')
                    ->leftJoin("ventas_clientes as cli","cli.id","ped.fk_cliente")
                    ->leftJoin("ventas_vendedores as ven","ven.id","cli.fk_vendedor")
                    ->leftJoin("coral_erp.system_usuarios as usr","usr.id","ven.fk_user")
                    ->leftJoin("erp_sucursales as suc","suc.id","ven.fk_sucursal")
                    ->leftJoin("system_acl_user_rol as usr_rol","usr_rol.fk_user","usr.id")
                    ->leftJoin("ventas_ventas as venta","venta.id","ped.fk_venta")
                    ->leftJoin("facturacion_facturas as fact","fact.id","venta.fk_factura")
                    ->select(
                        "venta.id as venta_id",
                        "venta.fk_factura",
                        "venta.folio as cdOrder",
                        "usr.username as cdCreationUser",
                        "ped.fk_cliente as cdStore",
                        DB::raw("concat(usr.username,'_',ven.fk_sucursal) as cdRegion"),
                        DB::raw("substr(ped.fecha,1,10) as dtOrder"),
                        DB::raw("0 as vlDiscountTotal"),
                        DB::raw("0 as vlTotalOrder"),
                        DB::raw("
                            (
                                select count(detalle.id_venta)
                                from ventas_ventas_detalles as detalle
                                left join ventas_productos as prod on prod.id=detalle.id_producto 
                                where detalle.id_venta=venta.id and prod.id_grupo=10
                            ) as vlTotalUnit
                        "),
                        "cli.fk_ruta as cdRoute",
                        DB::raw("if(ped.estado='SURTIDO','APPRO','CANC') as cdStatusOrder"),
                        DB::raw("ifnull(concat(fact.serie,'',fact.folio),'NULL') as cdInvoice"),
                        DB::raw("concat(fact.serie,'',fact.fk_serie) as dsDisplayText"),
                        DB::raw("ifnull(substr(fact.fecha,1,10),'NULL') as dtInvoice"),
                        DB::raw("0 as vlTotalInvoice"),
                        DB::raw("
                            round
                            (
                                (
                                    select sum(detalle.cantidad)
                                    from facturacion_facturas_detalles as detalle 
                                    left join ventas_productos as prod on prod.id=detalle.id_producto 
                                    where detalle.fk_factura=venta.fk_factura and prod.id_grupo=10
                                )
                            ) 
                            as nrTotalQuantity
                        "),
                        DB::raw("if(fact.id_status,'LIBER',if(fact.id_status=0,'CANC','NULL')) as cdStatusInvoice"),
                    )
                    ->where("cli.fk_vendedor", ">", 0)
                    ->where("ven.fk_sucursal", ">", 0)
                    ->whereNotNull("suc.nombre")
                    ->where("usr_rol.fk_rol", 4)
                    ->where("cli.estado", "ACTIVO")
                    ->where("ven.estado", "ACTIVO")
                    ->whereIn("ped.estado", ['SURTIDO','CANCELADO'])
                    ->whereNotNull("venta.fk_factura")
                    // ->where("venta.fk_factura",'32892') // Prueba con grupo UNILEVER
                    ->whereRaw("ped.fecha >=(select date_format(now(),'%Y-%m-01'))")
                    ->whereRaw("substr(usr.nombre,1,3) in ('MXL', 'TIJ', 'MZT','CLN')")
                    ->whereRaw("
                        (
                            select sum(detalle.total)
                            from facturacion_facturas_detalles as detalle 
                            left join ventas_productos as prod on prod.id=detalle.id_producto 
                            where detalle.fk_factura=venta.fk_factura and prod.id_grupo=10
                        ) > 0
                    ")
                    ;

        // dd($query->toSql());
        
        $data = $query->get();
        
        $articulos = [];
        
        foreach($data as $row) {
            // dd($row);
            $query_i = DB::table("facturacion_facturas_detalles as factdet")
                    ->leftJoin("ventas_productos as prod", "prod.id", "factdet.id_producto")
                    ->select(
                        DB::raw("'$row->cdOrder' as cdOrder"),
                        "prod.clave as cdProduct",
                        DB::raw("0 as nrSequenceOrder"),
                        DB::raw("round(factdet.cantidad) as nrQuantityOrder"),
                        DB::raw("0 as vlDiscountSubtotal"),
                        DB::raw("0 as vlSubtotalOrder"),
                        DB::raw("'$row->cdInvoice' as cdInvoice"),
                        DB::raw("0 as nrSequenceInvoice"),
                        DB::raw("round(factdet.cantidad) as nrQuantityInvoice"),
                        DB::raw("0 as vlSubtotalInvoice"),
                        "factdet.id",
                        "factdet.precio_unitario",
                        "factdet.precio_con_impuestos",
                        "prod.precio5",
                    )
                    ->where("factdet.fk_factura", $row->fk_factura)
                    ->where("factdet.id_producto",">",0)
                    ->where("prod.id_grupo",10)
                    // ->orderBy("factdet.nombre")
                    ;
                    // dd($query_i->toSql());

            $data_i = $query_i->get();

            foreach($data_i as $row_i) {

                if($row_i->precio_con_impuestos < $row_i->precio5) {

                    $row_i->precio_con_impuestos = $row_i->precio5;
                }

                if(!isset(${"nrSequenceInvoice".$row_i->cdOrder.$row_i->cdProduct})) ${"nrSequenceInvoice".$row_i->cdOrder.$row_i->cdProduct} = 0;

                ${"nrSequenceInvoice".$row_i->cdOrder.$row_i->cdProduct}++;

                $row_i->nrSequenceOrder = ${"nrSequenceInvoice".$row_i->cdOrder.$row_i->cdProduct};
                $row_i->nrSequenceInvoice = ${"nrSequenceInvoice".$row_i->cdOrder.$row_i->cdProduct};

                $row_i->vlSubtotalOrder = $row_i->nrQuantityOrder * $row_i->precio_con_impuestos;
                $row_i->vlSubtotalInvoice = $row_i->nrQuantityOrder * $row_i->precio_con_impuestos;
                $row->vlTotalInvoice += $row_i->nrQuantityOrder * $row_i->precio_con_impuestos;
                $row->vlTotalOrder += $row_i->nrQuantityOrder * $row_i->precio_con_impuestos;

                $line_i = $row_i->cdOrder."\t".$row_i->cdProduct."\t".$row_i->nrSequenceOrder."\t".$row_i->nrQuantityOrder;
                $line_i.= "\t".$row_i->vlDiscountSubtotal."\t".$row_i->vlSubtotalOrder;
                $line_i.= "\t".$row_i->cdInvoice."\t".$row_i->nrSequenceInvoice."\t".$row_i->nrQuantityInvoice;
                $line_i.="\t".$row_i->vlSubtotalInvoice."\n";
                
                fwrite($file_i, $line_i); 
            }
             
            $line = $row->cdOrder."\t".$row->cdCreationUser."\t".$row->cdStore."\t".$row->cdRegion."\t".$row->dtOrder."\t".$row->vlDiscountTotal;
            $line.= "\t".$row->vlTotalOrder."\t".$row->vlTotalUnit."\t".$row->cdRoute."\t".$row->cdStatusOrder."\t".$row->cdInvoice;
            $line.= "\t".$row->dsDisplayText."\t".$row->dtInvoice."\t".$row->vlTotalInvoice."\t".$row->nrTotalQuantity."\t".$row->cdStatusInvoice."\n";
            
            fwrite($file, $line); 
        }

        fclose($file);
        // die();
        
        try {

            $connection = ssh2_connect(env('FTP_IP'), 22);
            ssh2_auth_password($connection, env('FTP_USER'), env('FTP_PASSWORD'));
            
            $sftp = ssh2_sftp($connection);
            
            // INVOICE
            echo "\n\t`$file_name`"; 

            $resFile = fopen("ssh2.sftp://{$sftp}/Import/Inbox/".$file_name, 'w');
            $srcFile = fopen($file_full_name, 'r');
            
            $writtenBytes = stream_copy_to_stream($srcFile, $resFile);

            fclose($resFile);
            fclose($srcFile);

            // INVOICE ITEM
            echo "\n\t`$file_name_i`";

            $resFile = fopen("ssh2.sftp://{$sftp}/Import/Inbox/".$file_name_i, 'w');
            $srcFile = fopen($file_full_name_i, 'r');
            
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