<?php

/**
 * @author Antonio J. González
 * @package mod-wikicode
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 function escribir_fichero($cadena) {$file = fopen("fichero_prueba.txt","w"); fputs($file, $cadena); fclose($file); }
 
 $codigo = $_GET["codigo"];
 $i = 1;
 
 $codigo = str_replace("\"", "'", $codigo);
 $codigo = str_replace("\\", "", $codigo);
   
 $desde = strpos($codigo, '<!');
 
 while (is_numeric($desde)) {
	 $hasta = strpos($codigo, '</!>', $desde) + 4;
	 
	 $vector[$i] = $desde;
	 $vector[($i+1)] = $hasta + 1;
	 
	 $i = $i+2;
	 
	 $desde = strpos($codigo, '<!', $hasta);
 }
 
 
 // No hay ningún bloqueo en el código
 if (!is_array($vector)) {
 	$vector[1] = -1;
 }
 
 echo json_encode($vector);
 
?>
