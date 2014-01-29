<?php

/**
 * @author Antonio J. González
 * @package mod-wikicode
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
global $USER, $DB;
 
$pageid = $_GET["pageid"];
 
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/wikicode/locallib.php');
require_once($CFG->dirroot . '/mod/wikicode/editorlibfn.php');

$version = wikicode_get_current_version($pageid);
$codigo = $version->content;

$funciones = getLocksByUser($codigo, $USER->username);

echo '<link rel="stylesheet" type="text/css"/>
      <style type="text/css">
       div#checks {
        text-align:left;
		margin:0 auto;
		padding:10px;
		background:#fff;
		height:auto;
		width:430px;
		border:1px solid #ACD8F0;
		overflow:auto;
		font-family:arial;
		font-size:9pt;
      }
      div#button {
        text-align:left;
		margin:0 auto;
		padding:10px;
		background:#ACD8F0;
		height:auto;
		width:430px;
		border:1px solid #ACD8F0;
		overflow:auto;
		font-family:arial;
		font-size:9pt;
      }
      div#titulo {
        text-align:left;
		margin:0 auto;
		padding:10px;
		background:#ACD8F0;
		height:auto;
		width:430px;
		border:1px solid #ACD8F0;
		overflow:auto;
		font-family:arial;
		font-size:10pt;
      }
      </style>';


echo '<div id="titulo"><h4>Functions</h4></div>';
echo '<div id="checks">';

foreach ($funciones as $variable => $valor){
 echo '
<input type="checkbox" name="'.$valor.'" value="'.$valor.'" />'.$valor.'</br>';
} 

echo '</div>';
echo '<div id="button"><button type="button" class="unlock">Unlock</button>    <button type="cancel" class="cancelUnlock">Cancel</button></div>';

echo '<script src="js/jquery.js"></script>';
echo '<script src="js/php.js"></script>';
echo '<script src="js/unlock.js"></script>';


?>