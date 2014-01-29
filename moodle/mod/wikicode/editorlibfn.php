<?php

/**
 * @author Antonio J. González
 * @package mod-wikicode
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/wikicode/locallib.php');

function salto_linea() {echo "<br>";}
function mostrar_variable($variable, $titulo) {echo $titulo.": ".$variable; salto_linea(); }
function escribir_fichero($cadena) {$file = fopen("fichero_prueba.txt","a"); fputs($file, $cadena); fclose($file); }

/**
 * @param string $codigo
 * 
 * @return string New code
 */
function deleteEnterRepeat($codigo) {
	
	$codigo = str_replace(chr(13) . chr(10), chr(10), $codigo);
	
	$return = "";
	
	for($i=0; $i<strlen($codigo); $i++) {
		if (substr($codigo, $i - 1, 1) != chr(10) || substr($codigo, $i, 1) != chr(10) || substr($codigo, $i+1, 1) != chr(10) || substr($codigo, $i+2, 1) != chr(10)) {
			$return = $return . substr($codigo, $i, 1);
		}
	}
	
	$return = str_replace(chr(10), chr(13) . chr(10), $return);
	
	return $return;
}

/**
 * Return function name in a determinate cursor position
 *
 * @param string $codigo Full source code
 * @param int $posicion Character Position
 */
function getFunction($codigo, $posicion) {
		
	//First. Level of the character.
	$level = getLevel($codigo, $posicion);
	$help_cursor = $posicion;
	
	//When level=0, cursor is out any function. Get next function.
	if ($level == 0) {
		while ($level == 0 && $help_cursor <= strlen($codigo)) {
			$help_cursor++;
			$level = getLevel($codigo, $help_cursor);
		}	
	}
	
	if ($level == 0) {return FALSE;} //There isnt any function in the code
	
	//Function Start
	while (getLevel($codigo, $help_cursor) > 0) {
		
	    $cursor_lu = strrpos(substr($codigo, 0, $help_cursor), "{");
		$cursor_ld = strrpos(substr($codigo, 0, $help_cursor), "}");
		
		if (!$cursor_lu) {$cursor_lu=0;}
		if (!$cursor_ld) {$cursor_ld=0;}
		
		if ($cursor_lu > $cursor_ld) {
			$help_cursor = $cursor_lu;
		}
		else {
			$help_cursor = $cursor_ld;
		}
		
	}
	
	//Get function name
	$end_cursor = strrpos(substr($codigo, 0, $help_cursor),"(");
	while (substr($codigo,$end_cursor-1,1) == " ") {
		$end_cursor = $end_cursor - 1;
	}
	
	$init_cursor = strrpos(substr($codigo, 0, $end_cursor)," ");
	$init_cursor_aux = strrpos(substr($codigo, 0, $end_cursor),chr(10));
	
	if ($init_cursor_aux > $init_cursor) {$init_cursor = $init_cursor_aux;} // When main declaration have not object type
	
	$function_name = substr($codigo, $init_cursor, $end_cursor-$init_cursor);
	
	return trim($function_name);	
}

/**
 * Return level indexation in a determinate cursor position
 *
 * @param string $codigo Full source code
 * @param int $posicion Character Position
 */
function getLevel($codigo, $posicion) {
		
	$subcodigo = substr($codigo, 0, $posicion);
	
	$levelup = substr_count($subcodigo,"{");
	$leveldown = substr_count($subcodigo,"}");
	
	$level = $levelup - $leveldown;
	
	return $level;
}

/**
 * Return True is a determinated function is locked.
 * 
 * @param string $codigo Full source code
 * @param string $function Function name
 */
function isLocked($codigo, $function) {
	
	//Starting code in main function, ignoring declarations
	$pos_main = strpos(strtolower($codigo),"main");
	$subcodigo = substr($codigo, $pos_main);
	
	//Special case: Main function
	if (strtolower($function) == "main") {

		$lock = strpos(substr($codigo, 0, $pos_main), "<!");
		
		if (is_numeric($lock)) {
			return TRUE;
		}
		return FALSE;
	}
	
	//Get position of function start
	$end_cursor = strpos($subcodigo, $function);
	$level = getLevel($codigo, $pos_main + $end_cursor);
	
	while ($level > 0) {
		$end_cursor = strpos($subcodigo, $function, $end_cursor + 1);
		$level = getLevel($codigo, $pos_main + $end_cursor);
	}
	
	$init_cursor = strrpos(substr($subcodigo,0,$end_cursor),"}");
	$subcadena = substr($subcodigo, $init_cursor, $end_cursor - $init_cursor);
	
	//Search the locking-tag
	$lock = strpos($subcadena, "<!");
	
	if (is_numeric($lock)) {
		return TRUE;
	}
	
	return FALSE;
}

/**
 * Return True is a determinated function is locked by the user.
 * 
 * @param string $codigo Full source code
 * @param string $function Function name
 */
function isLockedByUser($codigo, $function) {
					
	global $USER;
	
	//Starting code in main function, ignoring declarations
	$pos_main = strpos(strtolower($codigo),"main");
	$subcodigo = substr($codigo, $pos_main);
	
	//Special case: Main function
	if (strtolower($function) == "main") {

		$lock = strpos(substr($codigo, 0, $pos_main), "<!".$USER->username);
		
		if (is_numeric($lock)) {
			return TRUE;
		}
		return FALSE;
	}
	
	//Get position of function start
	$end_cursor = strpos($subcodigo, $function);
	$level = getLevel($codigo, $pos_main + $end_cursor);
	
	while ($level > 0) {
		$end_cursor = strpos($subcodigo, $function, $end_cursor + 1);
		$level = getLevel($codigo, $pos_main + $end_cursor);
	}
	
	$init_cursor = strrpos(substr($subcodigo,0,$end_cursor),"}");
	$subcadena = substr($subcodigo, $init_cursor, $end_cursor - $init_cursor);
	
	//Search the locking-tag
	$lock = strpos($subcadena, "<!".$USER->username);
	
	if (is_numeric($lock)) {
		return TRUE;
	}
	
	return FALSE;
}

/**
 * Lock a determinate function into a source-code
 * 
 * @param string $codigo Full source-code
 * @param string $function Function name
 * @param string $user User who is blocking function
 * @param int $pos_end Optional Param. Force cursor position end_lock.
 * 
 * @return Source-code with new lock tag. False if is not possible.
 */
function lockFunction($codigo, $function, $user, $pos_end="") {
	
	$obviar=array(chr(10), chr(13), chr(32));
	
	if (isLocked($codigo, $function)) {
		return FALSE;
	}
	
	if (strtolower($function) == 'main') {
		
		$init_cursor = 0;
		$pos_main = 0;
		
		while (in_array(substr($codigo,$init_cursor, 1),$obviar) ) {
			$init_cursor++;
		}
		
		$end_cursor = strpos($codigo, "}", $init_cursor);
		if ($end_cursor==FALSE) {$end_cursor=strlen($codigo);}
		
		$level = getLevel($codigo, $end_cursor);
		
		while ($level > 0) {
			$end_cursor = strpos($codigo, "}", $end_cursor) + 1;
		    $level = getLevel($codigo, $end_cursor);
		}
		
	} 
	else 
	{
		//Starting code in main function, ignoring declarations
		$pos_main = strpos(strtolower($codigo),"main");
		$subcodigo = substr($codigo, $pos_main);

	    //Get position of function start
		$end_cursor = strpos($subcodigo, $function);
		$level = getLevel($codigo, $pos_main + $end_cursor);
	
		while ($level > 0) {
			$end_cursor = strpos($subcodigo, $function, $end_cursor + 1);
			$level = getLevel($codigo, $pos_main + $end_cursor);
		}
	
		$init_cursor = strrpos(substr($subcodigo,0,$end_cursor),"}");
		
		if (substr($codigo,$pos_main + $init_cursor + 1, 4) == "</!>") {$init_cursor = $init_cursor + 4;}
	
		while (in_array(substr($codigo,$pos_main + $init_cursor + 1, 1),$obviar) ) {
			$init_cursor++;
		}
	
		$end_cursor = $pos_main + $end_cursor;
		$end_cursor = strpos($codigo, "}", $end_cursor);
		if ($end_cursor==FALSE) {
			$end_cursor=strlen($codigo);
		} else {
			$end_cursor = $end_cursor + 1;
		}
	
		$level = getLevel($codigo, $end_cursor);
	
		while ($level > 0) {
			$end_cursor = strpos($codigo, "}", $end_cursor) + 1;
		    $level = getLevel($codigo, $end_cursor);
		}
		
		$init_cursor++;
		
	}

    if ($pos_end != "") {$end_cursor = $pos_end;}
	
	$return  = substr($codigo, 0, $init_cursor + $pos_main);
	$return .= "<!";
	$return .= $user;
	$return .= ">";
	$return .= substr($codigo, $init_cursor + $pos_main, $end_cursor - $init_cursor - $pos_main);
	$return .= "</!>";
	$return .= substr($codigo, $end_cursor);
	
	return $return;
}

/**
 * Unlock a determinate function into a source-code
 * 
 * @param string $codigo Full source-code
 * @param string $function Function name
 * 
 * @return string Source-code without lock tag. False if is not locked.
 */
function unlockFunction($codigo, $function) {
	
	if (!isLocked($codigo, $function)) {
		return FALSE;
	}
	
	$pos_main = strpos(strtolower($codigo), "main");
	
	if (strtolower($function) == "main") {
		$init_start = strrpos(substr($codigo, 0, $pos_main),"<!");
		$end_start = strpos($codigo,">",$init_start);
		$init_ending = strpos($codigo,"</!>",$pos_main);
	}
	else {
		$subcodigo = substr($codigo, $pos_main);
		
		$init_start = strpos($subcodigo, $function);
		$level = getLevel($codigo, $pos_main + $init_start);
		
		while ($level > 0) {
			$init_start = strpos($subcodigo, $function, $init_start + 1);
			$level = getLevel($codigo, $pos_main + $init_start);
		}
		
		$init_start = strrpos(substr($codigo, 0, $pos_main + $init_start), "<!");
		$end_start = strpos($codigo,">",$init_start);
		$init_ending = strpos($codigo,"</!>", $end_start);
	}
	
	$return  = substr($codigo, 0, $init_start);
	$return .= substr($codigo, $end_start + 1, $init_ending - $end_start - 1);
	$return .= substr($codigo, $init_ending + 4);
	
	return $return;
}

/**
 * Returned all functions that are being locked by a determined user
 * 
 * @param string $codigo Full source-code
 * @param string $user Username
 * 
 * @return array Function names
 */
function getLocksByUser($codigo, $user) {
	$functions = array();
	
	$lock = strpos($codigo, "<!".$user.">");
	
	if (is_bool($lock)) {return false;}
	
	while (is_numeric($lock)) {
		array_push($functions, getFunction($codigo, $lock));
		$lock = strpos($codigo, "<!".$user.">", $lock + 1);
	}
	
	return $functions;
}

/**
 * Return init char-position of a function locked
 * 
 * @param string $codigo Full source-code
 * @param string $function Function name
 * 
 * @return int Character position
 */
function getInitFunctionLock($codigo, $function) {
		
	if (!isLocked($codigo, $function)) {return false;}
	
	if (strtolower($function) == 'main') {
		$pos = strpos($codigo, "<!");
	}
	else {
		$pos_main = strpos(strtolower($codigo), "main");
		$subcode = substr($codigo, $pos_main);
		
		$pos = strpos($subcode, $function);
		$level = getLevel($codigo, $pos + $pos_main);
		
		while ($level > 0) {
			$pos = strpos($subcode, $function, $pos + 1);
			$level = getLevel($codigo, $pos + $pos_main);
		}
		$pos = strrpos(substr($codigo, 0,  $pos_main + $pos), "<!");

	}
	
	return $pos;
}

/**
 * Return ending char-position of a function locked
 * 
 * @param string $codigo Full source-code
 * @param string $function Function name
 * 
 * @return int Character position
 */
function getEndFunctionLock($codigo, $function) {
		
	if (!isLocked($codigo, $function)) {return false;}
	
	if (strtolower($function) == 'main') {
		$pos = strpos($codigo, "</!>");
	}
	else {
		$pos_main = strpos(strtolower($codigo), "main");

		$subcode = substr($codigo, $pos_main);
		
		$pos = strpos($subcode, $function);
		$level = getLevel($codigo, $pos + $pos_main);
		
		while ($level > 0) {
			$pos = strpos($subcode, $function, $pos + 1);
			$level = getLevel($codigo, $pos + $pos_main);
		}
		
		$subcodigo = substr($codigo,$pos_main + $pos);
		$pos = strpos($subcodigo, "</!>") + $pos_main + $pos;
	}
	
	return $pos + 4;
}

/**
 * Return locked function with the tag-lock delimiters
 * 
 * @param string $codigo Full source-code
 * @param string $function Function name
 * 
 * @return string Function source-code
 */
function getFunctionLock($codigo, $function) {
	
	if (!isLocked($codigo, $function)) {return false;}
	
	return substr($codigo, getInitFunctionLock($codigo, $function), getEndFunctionLock($codigo, $function) - getInitFunctionLock($codigo, $function));
}

/**
 * Return locked function with the tag-lock delimiters
 * 
 * @param string $codigo Full source-code
 * @param string $function Function name
 * @param string $newfunction New source-code of the function
 * 
 * @return string Function source-code
 */
function changeFunctionLock($codigo, $function, $newfunction) {
	
	if (!isLocked($codigo, $function)) {return false;}
	
	$init_function = getInitFunctionLock($codigo, $function);
	$end_function = getEndFunctionLock($codigo, $function);
	
	$return  = substr($codigo, 0, $init_function);
	$return .= $newfunction;
	$return .= substr($codigo, $end_function);
	
	return $return;
	
}

/**********************
 * DATABASE FUNCTIONS *
 **********************/
 
/**
 * Return original source-code from DB
 * 
 * @param int $pageid
 * 
 * @return string Original source-code
 */
function getCodigofromDB($pageid) {
	
	$version = wikicode_get_current_version($pageid);
	
	$resultado = $version->content;
	
	return $resultado;
}

/**
 * Save new source-code into DB
 * 
 * @param int $pageid
 * @param string $newcode
 */
function saveCodigoToDB($pageid, $newcode, $timer=0) {
	
	global $DB;
	
	$page = wikicode_get_page($pageid);
	$page->cachedcontent = $newcode;
	
	$page->timerendered = time();
	if ($page->timestartedit == 0) {
		$page->timestartedit = time();
	}
	$page->timeendedit = time();
	$page->timer = $page->timer + $timer;
	$DB->update_record('wikicode_pages', $page);
	
	$version = wikicode_get_current_version($pageid);
	$version->content = str_replace("\\n", chr(92) . chr(92) . "n", $newcode);
	$DB->update_record('wikicode_versions', $version);
	
	return TRUE;
}

/******************
 *** PROCEDURES ***
 ******************/
 
/**
 * Procedure to exec when push a key into edior
 * 
 * @param string $newcode
 * @param int $position
 * @param int $pageid
 * @param string $del
 * 
 * @return object
 */
function keyPress($newcode, $position, $pageid, $del, $timer) {
	global $USER;
	
	$return = array();
	
	$code = $newcode;
	$position++;
	
	if ($del==1) {$position = $position - 2;}
	
	//In first place, change the double quotes for simple quotes.
	$code = str_replace("\"", "'", $code);
	$code = str_replace("\\n", "¨n", $code);
    $code = str_replace("\\", "", $code);
	
	//Get the original code
	$originalcode = getCodigofromDB($pageid);
	$dbcode = $originalcode;
	$originalcode = str_replace("\"", "'", $originalcode);
    $originalcode = str_replace("\\", "", $originalcode);
	
	//Get the function name which are modifying
	$function = getFunction($code, $position);
	escribir_fichero($function);
	
	//There is not any function in the code
	if (is_bool($function)) {
		$newcodelock = $code;
		$endcode = $code;
		
		$last_function = strrpos($code, "}");
		
		if (!is_bool($last_function)) {
			if ($del==0) {
				//$endcode = $originalcode . substr($code, $position - 1, 1);
			}
			else {
				$endcode = substr($originalcode, 0, strlen($originalcode) - 1);
			}
		}
		
		escribir_fichero($endcode);
		
		$return["position"] = $position;
	}
	elseif (isLocked($originalcode, $function) && !isLockedByUser($originalcode, $function)) //Function is blocked by other user
	{
		$endcode = $originalcode;
	}
	else {
		//Locking function into the original source-code if this is not locked
		if (!isLocked($originalcode, $function)){
			$originalcode = lockFunction($originalcode, $function, $USER->username);
		}
	
		//Insert lock tags into the new source-code
		$newcodeLock = lockFunction($code, $function, $USER->username);
	
		//Change original function by the new function
		$newfunction = getFunctionLock($newcodeLock, $function);
		$endcode = changeFunctionLock($originalcode, $function, $newfunction);
	}
	
	//Saving into DB
	$returncode = str_replace("'","\"", $endcode);
	$returncode = str_replace("¨n", "\\n", $returncode);
	
	if (trim($returncode) == "" && strlen($dbcode) > 1) {$returncode = $dbcode;}
	$returncode = deleteEnterRepeat($returncode);
	
	if (saveCodigoToDB($pageid, $returncode, $timer)) {
		$return["code"] = wikicode_remove_tags_owner($returncode);
	}
	
	//Get new cursor position
	if (!is_bool($function)) {
		$lenght_user = strlen($USER->username) + 3;
		$pos_inside = $position - $lenght_user - getInitFunctionLock($newcodeLock, $function);
		$str_search = deleteEnterRepeat(substr($newcodeLock, getInitFunctionLock($newcodeLock, $function) + $lenght_user, $pos_inside + $lenght_user));
		$newposition = strpos($return["code"], $str_search) + strlen($str_search);
		$return["position"] = $newposition;
	} else {
		$return["position"] = $position;
	}
	
	return $return;
}

/**
 * Procedure to exec when user removed a need-part
 * 
 * @param string $newcode
 * @param int $position
 * @param int $pageid
 * @param string $char_del
 * 
 * @return object
 */
function removePairKey($newcode, $position, $pageid, $char_del, $timer) {
	global $USER;
	
	$code = $newcode;
	$position = $position - 1;
	
	//In first place, change the double quotes for simple quotes.
	$code = str_replace("\"", "'", $code);
	$code = str_replace("\n", chr(10), $code);
    $code = str_replace("\\", "", $code);
	
	//Get the original code
	$dbcode = getCodigofromDB($pageid);
	$dbcode = str_replace("\"", "'", $dbcode);
    $dbcode = str_replace("\\", "", $dbcode);
	
	//Code before remove any character
	$originalcode = substr($code, 0, $position) . $char_del . substr($code, $position);
	
	if ($char_del == "{") {
		$positionlevel = $position;
	} else {
		$positionlevel = $position + 1;
	}
	
	$level = getLevel($originalcode, $positionlevel);
	$function = getFunction($originalcode, $position);
	
	if ($level == 0) {
		if ($char_del == "{" && substr($originalcode, $position + 1, 1) == "}") {
			$newfunction = "";
		} else if ($char_del == "}" && substr($originalcode, $position -1, 1) == "{") {
			$newfunction = "";
		} else {
			//Insert lock tags into the new source-code
			$newcodeLock = lockFunction($originalcode, $function, $USER->username);
			$newfunction = getFunctionLock($newcodeLock, $function);
		}
	} else {
			
		$found = 1;
		$pos_pair = 0;
		
		if ($char_del == "{") {
			for ($i=$position; $found != 0; $i++) {
				if (substr($originalcode, $i + 1, 1) == "{") {
					$found++;
				} else if (substr($originalcode, $i + 1, 1) == "}") {
					$found--;
				}
				$pos_pair = $i + 1;
			}	
		} else if ($char_del == "}") {
			for ($i=1; $found != 0; $i++) {
				if (substr($originalcode, $position - $i, 1) == "}") {
					$found++;
				} else if (substr($originalcode, $position - $i, 1) == "{") {
					$found--;
				}
				$pos_pair = $position - $i;
			} 
		}
		if ($position < $pos_pair) {
			$newcode = substr($originalcode, 0, $position) . substr($originalcode, $position + 1, $pos_pair - $position - 1) . substr($originalcode, $pos_pair + 1);
		} else {
			$newcode = substr($originalcode, 0, $pos_pair) . substr($originalcode, $pos_pair + 1, $position - $pos_pair - 1) . substr($originalcode, $position + 1);
		}
		
		$newcodeLock = lockFunction($newcode, $function, $USER->username);
		$newfunction = getFunctionLock($newcodeLock, $function);

	}
	
	//Locking function into the original source-code if this is not locked
	if (!isLocked($dbcode, $function)){
		$dbcode = lockFunction($dbcode, $function, $USER->username);
	}
	
	//Change original function by the new function
	$endcode = changeFunctionLock($dbcode, $function, $newfunction);
	
	//Saving into DB
	$returncode = str_replace("'","\"", $endcode);
	$returncode = deleteEnterRepeat($returncode);
	
	if (saveCodigoToDB($pageid, $returncode, $timer)) {
		$return["code"] = wikicode_remove_tags_owner($returncode);
	}
	
	return $return;
	
}

/**
 * Procedure to exec when user removed a need-part
 * 
 * @param string $function
 * @param int $pageid
 * 
 * @return object
 */
function unlockFunctionDB($function, $pageid) {
		
	//Get the original code
	$dbcode = getCodigofromDB($pageid);
	
	//Unlock the function
	$savecode = unlockFunction($dbcode, $function);
	
	//Save newcode
	if (saveCodigoToDB($pageid, $savecode)) {
		$return["code"] = wikicode_remove_tags_owner($savecode);
	}
	
	return $return;
	
}

?>

