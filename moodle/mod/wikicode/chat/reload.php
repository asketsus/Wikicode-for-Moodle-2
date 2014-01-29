<?php

/**
 * @author Antonio J. González
 * @package mod-wikicode
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 $path = $_GET["path"];
 
 $fp = fopen($path, 'r');
 $chat = fread($fp, filesize($path));
 fclose($fp);
 
 echo json_encode($chat);
 
?>