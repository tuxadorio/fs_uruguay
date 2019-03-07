<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


/**
 * Description of admin_uruguay
 *
 * @author carlos
 */
class admin_uruguay extends fs_controller
{
	public $impuestos_uy;
	public $variables;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Uruguay', 'admin');
   }
   
   protected function private_core()
   {
		 $this->init_variables();
		 
		$this->impuestos_uy = array(
			array('codigo' => 'IVA22', 'descripcion' => 'IVA 22%', 'porcentaje' => 22, 'recargo' => 0, 'subcuenta_compras' => '', 'subcuenta_ventas' => '', 'default'=>true),
			array('codigo' => 'IVA10', 'descripcion' => 'IVA 10%', 'porcentaje' => 10, 'recargo' => 0, 'subcuenta_compras' => '', 'subcuenta_ventas' => '', 'default'=>false),
			array('codigo' => 'EXENTO', 'descripcion' => 'EXENTO', 'porcentaje' => 0, 'recargo' => 0, 'subcuenta_compras' => '', 'subcuenta_ventas' => '', 'default'=>false)
		);
	   
      if( isset($_GET['opcion']) )
      {
         if($_GET['opcion'] == 'moneda')
         {
			 $this->moneda();
            /*$this->empresa->coddivisa = 'UYU';
            if( $this->empresa->save() )
            {
               $this->new_message('Datos guardados correctamente.');
            }*/
         }
         else if($_GET['opcion'] == 'pais')
         {
			$this->pais();
            $this->configuracion_regional();			
			 /*
            $this->empresa->codpais = 'URY';
            if( $this->empresa->save() )
            {
               $this->new_message('Datos guardados correctamente.');
            }
			*/
         }
         else if($_GET['opcion'] == 'regimenes')
         {
            $fsvar = new fs_var();
            if( $fsvar->simple_save('cliente::regimenes_iva', 'Básico,Mínimo,Exento') )
            {
               $this->new_message('Datos guardados correctamente.');
            }
         }
         else if($_GET['opcion'] == 'impuestos')
         {
            $this->set_impuestos();
         }
      }
      else
      {
         $this->check_ejercicio();
         $this->share_extensions();

      }
   }

    public function init_variables()
    {
        $this->variables = array();
        $this->variables['zona_horaria'] = "America/Montevideo";
        $this->variables['nf0'] = "2";
        $this->variables['nf0_art'] = "4";
        $this->variables['nf1'] = ",";
        $this->variables['nf2'] = ".";
        $this->variables['pos_divisa'] = "left";
        $this->variables['factura'] = "factura";
        $this->variables['facturas'] = "facturas";
        $this->variables['factura_simplificada'] = "factura simplificada";
        $this->variables['factura_rectificativa'] = "nota de credito";
        $this->variables['albaran'] = "remito";
        $this->variables['albaranes'] = "remitos";
        $this->variables['pedido'] = "pedido";
        $this->variables['pedidos'] = "pedidos";
        $this->variables['presupuesto'] = "presupuesto";
        $this->variables['presupuestos'] = "presupuestos";
        $this->variables['provincia'] = "departamento";
        $this->variables['apartado'] = "apartado";
        $this->variables['cifnif'] = "CI/RUT";
        $this->variables['iva'] = "IVA";
        $this->variables['numero2'] = "número 2";
        $this->variables['serie'] = "serie";
        $this->variables['series'] = "series";
    }
	
    public function moneda()
    {
        $tratamiento = false;
        //Validamos si existe la moneda UYU
        $div0 = new divisa();
        $divisa1 = $div0->get('UYU');
        if (!$divisa1) {
            $div0->coddivisa = 'UYU';
            $div0->codiso = '858';
            $div0->descripcion = 'PESOS URUGUAYOS';
            $div0->simbolo = '$U';
            $div0->tasaconv = 32.00;
            $div0->tasaconv_compra = 33.00;
            $div0->save();
            $tratamiento = true;
        }
        //Validamos si existe la moneda USD
        //por temas de operaciones en dolares
        $divisa2 = $div0->get('USD');
        if (!$divisa2) {
            $div0->coddivisa = 'USD';
            $div0->codiso = '840';
            $div0->descripcion = 'DÓLARES EE.UU.';
            $div0->simbolo = '$';
            $div0->tasaconv = 1;
            $div0->tasaconv_compra = 1;
            $div0->save();
            $tratamiento = true;
        }

        if ($tratamiento) {
            $this->new_message('Datos de moneda UYU y USD actualizados correctamente.');
        }

        if ($this->empresa->coddivisa != 'UYU') {
            //Elegimos la divisa para la empresa como UYU si no esta generada
            $this->empresa->coddivisa = 'UYU';
            if ($this->empresa->save()) {
                $this->new_message('Datos de moneda para la empresa guardados correctamente.');
            }
        }
    }

   private function share_extensions()
   {
      $fsext = new fs_extension();
      $fsext->name = 'puc_uruguay';
      $fsext->from = __CLASS__;
      $fsext->to = 'contabilidad_ejercicio';
      $fsext->type = 'fuente';
      $fsext->text = 'Plan contable Uruguay';
      $fsext->params = 'plugins/fs_uruguay/extras/uruguay.xml';
      $fsext->save();
   }
   
   
   private function check_ejercicio()
   {
      $ej0 = new ejercicio();
      foreach($ej0->all_abiertos() as $ejercicio)
      {
         if($ejercicio->longsubcuenta != 10)
         {
            $ejercicio->longsubcuenta = 10;
            if( $ejercicio->save() )
            {
               $this->new_message('Datos del ejercicio '.$ejercicio->codejercicio.' modificados correctamente.');
            }
            else
            {
               $this->new_error_msg('Error al modificar el ejercicio.');
            }
         }
      }
   }
   
   public function regimenes_ok()
   {
      $fsvar = new fs_var();
      $regimenes = $fsvar->simple_get('cliente::regimenes_iva');
      
      if($regimenes == 'Básico,Mínimo,Exento')
      {
         return TRUE;
      }
      else
      {
         return FALSE;
      }
   }
   
   public function ejercicio_ok()
   {
      $ok = FALSE;
      
      $ej0 = new ejercicio();
      $ejerccio = $ej0->get_by_fecha( $this->today() );
      if($ejerccio)
      {
         $subc0 = new subcuenta();
         foreach($subc0->all_from_ejercicio($ejerccio->codejercicio) as $sc)
         {
            $ok = TRUE;
            break;
         }
      }
      
      return $ok;
   }
   
   public function impuestos_ok()
   {
      $ok = FALSE;
      
      $imp0 = new impuesto();
      foreach($imp0->all() as $i)
      {
         if($i->codimpuesto == 'IVA22')
         {
            $ok = TRUE;
            break;
         }
      }
      
      return $ok;
   }
   
   private function set_impuestos()
   {
	  
        $impuestos = new impuesto();
        //Eliminamos los Impuestos que no son de UY
        $lista_impuestos = array();
        foreach ($this->impuestos_uy as $imp) {
            $lista_impuestos[] = $imp['porcentaje'];
        }

        foreach ($impuestos->all() as $imp) {
            $imp->delete();
        }

        //Agregamos los Impuestos de UY
        foreach ($this->impuestos_uy as $imp) {
            $tratamiento=false;
            if (!$impuestos->get_by_iva($imp['porcentaje'])) {
                $imp0 = new impuesto();
                $imp0->codimpuesto = $imp['codigo'];
                $imp0->descripcion = $imp['descripcion'];
                $imp0->iva = $imp['porcentaje'];
                $imp0->recargo = $imp['recargo'];
                $imp0->codsubcuentasop = $imp['subcuenta_compras'];
                $imp0->codsubcuentarep = $imp['subcuenta_ventas'];
                $imp0->is_default();
                if ($imp0->save()) {
                    $tratamiento = true;
                }
                if($tratamiento===true AND $imp['default']){
                    $this->save_codimpuesto($imp['codigo']);
                }
            }
        }

        //Corregimos la información de las Cuentas especiales con los nombres correctos
        $cuentas_especiales_uy = array();
        $cuentas_especiales_rd['IVAACR'] = 'Cuentas acreedoras de IVA en la regularización';
        $cuentas_especiales_uy['IVASOP'] = 'Cuentas de IVA Compras';
        $cuentas_especiales_uy['IVARXP'] = 'Cuentas de IVA exportaciones';
        $cuentas_especiales_uy['IVASIM'] = 'Cuentas de IVA importaciones';
        $cuentas_especiales_uy['IVAREX'] = 'Cuentas de IVA para clientes exentos';
        $cuentas_especiales_uy['IVAREP'] = 'Cuentas de IVA Ventas';
        $cuentas_especiales = new cuenta_especial();
        foreach ($cuentas_especiales_uy as $id => $desc) {
            $linea = $cuentas_especiales->get($id);
            if ($linea->descripcion !== $desc) {
                $linea->descripcion = $desc;
                $linea->save();
            }
        }

        //Cargamos el ejercicio configurando la longitud de cuentas a 10
        $cod = $this->empresa->codejercicio;
        $ejer0 = new ejercicio();
        $ejer = $ejer0->get($cod);
        $ejer->longsubcuenta = 10;
        $ejer->save();

        if ($tratamiento) {
            $this->new_message('Impuestos de Uruguay actualizados correctamente');
			$this->impuestos_ok = TRUE;
        } else {
            $this->new_message('No se modificaron datos de impuestos previamente tratados.');
        }
   }

    public function pais()
    {
        $pais0 = new pais();
        $pais1 = $pais0->get('URY');
        if (!$pais1) {
            $pais0->codpais = 'URY';
            $pais0->codiso = 'UY';
            $pais0->nombre = 'Uruguay';
            $pais0->save();
        }

        $pais2 = $pais0->get('USA');
        if (!$pais2) {
            $pais0->codpais = 'USA';
            $pais0->codiso = 'US';
            $pais0->nombre = 'Estados Unidos';
            $pais0->save();
        }

        $this->empresa->codpais = 'URY';
        if ($this->empresa->save()) {
            $this->new_message('Datos guardados correctamente.');
        }
    }

    public function configuracion_regional()
    {
        //Configuramos la información básica para config2.ini
        $guardar = false;
        foreach ($GLOBALS['config2'] as $i => $value) {
            if (isset($this->variables[$i])) {
                $GLOBALS['config2'][$i] = $this->variables[$i];
                $guardar = true;
            }
        }

        if ($guardar) {
            $file = fopen('tmp/' . FS_TMP_NAME . 'config2.ini', 'w');
            if ($file) {
                foreach ($GLOBALS['config2'] as $i => $value) {
                    if (is_numeric($value)) {
                        fwrite($file, $i . " = " . $value . ";\n");
                    } else {
                        fwrite($file, $i . " = '" . $value . "';\n");
                    }
                }
                fclose($file);
            }
            $this->new_message('Datos de configuracion regional guardados correctamente.');
        }
    }
	
   private function desvincular_articulos($codimpuesto)
   {
      $sql = "UPDATE articulos SET codimpuesto = null WHERE codimpuesto = "
              .$this->empresa->var2str($codimpuesto).';';
      
      if( $this->db->table_exists('articulos') )
      {
         $this->db->exec($sql);
      }
   }
   
   public function formato_divisa_ok()
   {
      if(FS_POS_DIVISA == 'left')
      {
         return TRUE;
      }
      else
      {
         return FALSE;
      }
   }
}
