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

        /* 
        $query = DB::table('ventas_pedidos as ped')
                    ->leftJoin("ventas_vendedores as ven","ven.id","ped.fk_vendedor")
                    ->leftJoin("coral_erp.system_usuarios as usr","usr.id","ven.fk_user")
                    ->leftJoin("ventas_clientes as cli","cli.id","ped.fk_cliente")
                    ->leftJoin("ventas_ventas as venta","venta.id","ped.fk_venta")
                    ->leftJoin("facturacion_facturas as fact","fact.id","venta.fk_factura")
                    ->select(
                        'venta.folio as cdOrder',
                        'usr.username as cdCreationUser',
                        'ped.fk_cliente as cdStore',
                        'usr.username as cdRegion',
                        DB::raw("substr(ped.fecha,1,10) as dtOrder"),
                        DB::raw('ifnull(venta.descuento,0) as vlDiscountTotal'),
                        // DB::raw('ifnull(venta.total,0) as vlTotalOrder'),
                        DB::raw("
                            (
                                SELECT sum(detalle.importe)
                                from ventas_ventas_detalles as detalle 
                                left join ventas_productos as prod on prod.id=detalle.id_producto 
                                where detalle.id_venta=venta.id and prod.id_grupo=10
                            ) as vlTotalOrder
                        "),
                        DB::raw("
                            (
                                SELECT COUNT(detalle.id_venta)
                                from ventas_ventas_detalles as detalle 
                                where detalle.id_venta=venta.id
                            ) as vlTotalUnit
                        "),
                        'cli.fk_ruta as cdRoute',
                        DB::raw("if(ped.estado='SURTIDO','APPR','CANC') as cdStatusOrder"),
                        DB::raw("ifnull(fact.folio,'NULL') as cdInvoice"),
                        DB::raw("concat(fact.serie,'',fact.fk_serie) as dsDisplayText"),
                        DB::raw("ifnull(substr(fact.fecha,1,10),'NULL') as dtInvoice"),
                        // DB::raw("ifnull(fact.total,'NULL') as vlTotalInvoice"),
                        DB::raw("
                            (
                                SELECT sum(detalle.importe)
                                from facturacion_facturas_detalles as detalle 
                                left join ventas_productos as pro on pro.id=detalle.id_producto 
                                where detalle.fk_factura=fact.id and pro.id_grupo=10
                            ) as vlTotalInvoice
                        "),
                        DB::raw("
                            (
                                select ifnull(sum(factdet.cantidad),'NULL')
                                from facturacion_facturas_detalles as factdet
                                where factdet.fk_factura=fact.id
                            ) as nrTotalQuantity
                        "),
                        DB::raw("if(fact.id_status,'1',if(fact.id_status=0,'0','NULL')) as cdStatusInvoice"),
                        'venta.folio as nrSequenceOrder',
                        'fact.folio as nrSequenceInvoice',
                        // "fact.id_status as cdStatusInvoice"
                        // DB::raw("if(fact.id_status,'ACT',if(fact.id_status=0,'DEACT','NULL')) as cdStatusInvoice")
                    )
                    // ->whereRaw("ped.fecha >=(select date_format(now(),'%Y-%m-01'))")
                    ->whereIn("ped.estado", ['SURTIDO','CANCELADO'])
                    ->whereNotNull("venta.fk_factura")
                    ->where("venta.fk_factura",'32892') // Prueba con grupo UNILEVER
                    ->whereRaw("ped.fecha >=(select date_format(now(),'%Y-%m-01'))")
                    ; 
                    */

                    $query = DB::table('ventas_pedidos as ped')
                    ->leftJoin("ventas_vendedores as ven","ven.id","ped.fk_vendedor")
                    ->leftJoin("coral_erp.system_usuarios as usr","usr.id","ven.fk_user")
                    ->leftJoin("ventas_clientes as cli","cli.id","ped.fk_cliente")
                    ->leftJoin("ventas_ventas as venta","venta.id","ped.fk_venta")
                    ->leftJoin("facturacion_facturas as fact","fact.id","venta.fk_factura")
                    ->select(
                        'venta.folio as cdOrder',
                        'usr.username as cdCreationUser',
                        'ped.fk_cliente as cdStore',
                        'usr.username as cdRegion',
                        DB::raw("substr(ped.fecha,1,10) as dtOrder"),
                        DB::raw('ifnull(venta.descuento,0) as vlDiscountTotal'),
                        // DB::raw('ifnull(venta.total,0) as vlTotalOrder'),
                        DB::raw("
                            (
                                SELECT sum(detalle.importe)
                                from ventas_ventas_detalles as detalle 
                                left join ventas_productos as prod on prod.id=detalle.id_producto 
                                where detalle.id_venta=venta.id and prod.id_grupo=10
                            ) as vlTotalOrder
                        "),
                        DB::raw("
                            (
                                SELECT COUNT(detalle.id_venta)
                                from ventas_ventas_detalles as detalle 
                                where detalle.id_venta=venta.id
                            ) as vlTotalUnit
                        "),
                        'cli.fk_ruta as cdRoute',
                        DB::raw("if(ped.estado='SURTIDO','APPR','CANC') as cdStatusOrder"),
                        DB::raw("ifnull(fact.folio,'NULL') as cdInvoice"),
                        DB::raw("concat(fact.serie,'',fact.fk_serie) as dsDisplayText"),
                        DB::raw("ifnull(substr(fact.fecha,1,10),'NULL') as dtInvoice"),
                        // DB::raw("ifnull(fact.total,'NULL') as vlTotalInvoice"),
                        /* DB::raw("
                            (
                                SELECT sum(detalle.importe)
                                from facturacion_facturas_detalles as detalle 
                                left join ventas_productos as pro on pro.id=detalle.id_producto 
                                where detalle.fk_factura=fact.id and pro.id_grupo=10
                            ) as vlTotalInvoice
                        "),
                        DB::raw("
                            (
                                select ifnull(sum(factdet.cantidad),'NULL')
                                from facturacion_facturas_detalles as factdet
                                where factdet.fk_factura=fact.id
                            ) as nrTotalQuantity
                        "), */
                        DB::raw("if(fact.id_status,'1',if(fact.id_status=0,'0','NULL')) as cdStatusInvoice"),
                        'venta.folio as nrSequenceOrder',
                        'fact.folio as nrSequenceInvoice',
                        // "fact.id_status as cdStatusInvoice"
                        // DB::raw("if(fact.id_status,'ACT',if(fact.id_status=0,'DEACT','NULL')) as cdStatusInvoice")
                    )
                    // ->whereRaw("ped.fecha >=(select date_format(now(),'%Y-%m-01'))")
                    ->whereIn("ped.estado", ['SURTIDO','CANCELADO'])
                    ->whereNotNull("venta.fk_factura")
                    ->where("venta.fk_factura",'32892') // Prueba con grupo UNILEVER
                    ->whereRaw("ped.fecha >=(select date_format(now(),'%Y-%m-01'))")
                    ;

        // dd($query->toSql());
        
        $data = $query->get();
        
        dd($data);

        $articulos = [];
        
        foreach($data as $row) {

            /* 
            $line = $row->cdOrder."\t".$row->cdCreationUser."\t".$row->cdStore."\t".$row->cdRegion."\t".$row->dtOrder."\t".$row->vlDiscountTotal;
            $line.= "\t".$row->vlTotalOrder."\t".$row->vlTotalUnit."\t".$row->cdRoute."\t".$row->cdStatusOrder."\t".$row->cdInvoice;
            $line.= "\t".$row->dsDisplayText."\t".$row->dtInvoice."\t".$row->vlTotalInvoice."\t".$row->nrTotalQuantity."\t".$row->cdStatusInvoice."\n";
            
            fwrite($file, $line); 
            */

            // DB::statement(DB::raw('set @contador:=0'));
            // DB::statement(DB::raw("set @id:=0;"));

            $query_i = DB::table('facturacion_facturas_detalles as det')
                    ->leftJoin("facturacion_facturas as fact","fact.id","det.fk_factura")
                    ->leftJoin("ventas_productos as prod", "prod.id", "det.id_producto")
                    ->select(
                        DB::raw("'$row->cdOrder' as cdOrder"),
                        'prod.clave as cdProduct',
                        DB::raw("'$row->nrSequenceOrder' as nrSequenceOrder"),
                        'det.cantidad as nrQuantityOrder',
                        DB::raw("'NULL' as vlDiscountSubtotal"),
                        DB::raw("ifnull(det.descuento_pesos,'NULL') as vlSubtotalOrder"),
                        DB::raw("'$row->cdInvoice' as cdInvoice"),
                        DB::raw("'$row->nrSequenceInvoice' as nrSequenceInvoice"),
                        'det.cantidad as nrQuantityInvoice',
                        'det.importe as vlSubtotalInvoice',
                        'det.nombre as producto',
                        "prod.id_grupo"
                    )
                    ->where("fact.folio", $row->cdInvoice)
                    ->where("det.id_producto",">",0)
                    ->where("prod.id_grupo",10)
                    ->orderBy("det.nombre");
                    // ->leftJoin("coral_erp.system_usuarios as usr","usr.id","ven.fk_user")
                    // ->leftJoin("ventas_clientes as cli","cli.id","ped.fk_cliente")
                    // ->leftJoin("ventas_ventas as venta","venta.id","ped.fk_venta")
                    // ->leftJoin("facturacion_facturas as fact","fact.id","venta.fk_factura")
            // dd($query_i->toSql());
            $data_i = $query_i->get();

            foreach($data_i as $row_i) {

                if($row_i->id_grupo == 10) {
                    // Prueba grupo UNILEVER
                    // echo $row_i->cdInvoice."|".$row_i->cdProduct."\n<br>";
                    // continue;
                }

                if(!isset($articulos[$row_i->cdOrder][$row_i->producto])) {

                    $articulos[$row_i->cdOrder][$row_i->producto] = [
                        'nrSequenceInvoice' => 0
                    ];

                    // $articulos[$row_i->cdOrder][$row_i->producto]['nrSequenceInvoice'] = 0;
                }

                // $articulos[$row_i->cdOrder][$row_i->producto]['nrSequenceInvoice']++;
                
                // dd($articulos[$row_i->cdOrder][$row_i->producto]);

                /* 
                */

                
                if(!isset(${"nrSequenceInvoice".$row_i->cdOrder.$row_i->producto})) ${"nrSequenceInvoice".$row_i->cdOrder.$row_i->producto} = 0;

                ${"nrSequenceInvoice".$row_i->cdOrder.$row_i->producto}++;
                
                $line_i = $row_i->cdOrder."\t".$row_i->cdProduct."\t".${"nrSequenceInvoice".$row_i->cdOrder.$row_i->producto}."\t".$row_i->nrQuantityOrder;
                $line_i.= "\t".$row_i->vlDiscountSubtotal."\t".$row_i->vlSubtotalOrder;
                $line_i.= "\t".$row_i->cdInvoice."\t".${"nrSequenceInvoice".$row_i->cdOrder.$row_i->producto}."\t".$row_i->nrQuantityInvoice;
                $line_i.="\t".$row_i->vlSubtotalInvoice."\n";
                
                fwrite($file_i, $line_i); 
            }
        }

        // dd($articulos);

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