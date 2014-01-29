<?php

/**
 * @author Antonio J. González
 * @package mod-wikicode
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/wikicode/editorlibfn.php');

$editorCode = $_GET["codigo"];
$position = $_GET["position"];
$pageid = $_GET["pageid"];
$mode = $_GET["mode"];
$del = $_GET["del"];
$char_del = $_GET["char_del"];
$timer = $_GET["timer"];

if ($char_del == '}' || $char_del == '{') {
	$mode = 2;
}

switch ($mode) {
	case 0:
		$return["code"] = "test";
		break;
    case 1: //Procedure when user push a key into the editor
        $return = keyPress($editorCode, $position, $pageid, $del, $timer);
        break;
	case 2: //Procedure when user remove a need-part
	    $return = removePairKey($editorCode, $position, $pageid, $char_del, $timer);
	    break;
	case 3: //Unlock function
	    $return = unlockFunctionDB($editorCode, $pageid);
	    break;
}

echo json_encode($return);

?>


































