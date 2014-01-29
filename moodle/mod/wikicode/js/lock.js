function pushData ($code, $pos, $del, $char_del, $caracter) {
	var $pageid = substr(document.URL,strrpos(document.URL, "=")+1); 
	var IE = document.selection && window.ActiveXObject && /MSIE/.test(navigator.userAgent);
	var $timeTotal = time() - window.frameElement.CodeMirror.textareaTimelast.value;
		
    $.getJSON("editorlib.php", { codigo: $code, position: $pos, pageid: $pageid, mode: "1", del: $del, char_del: $char_del, timer: $timeTotal }, function(json) {
    	var Insert = window.frameElement.CodeMirror.textareaInsert;
    	var Editor = window.frameElement.CodeMirror.editor;
    	var Timer = window.frameElement.CodeMirror.textareaTimelast;
    	
    	Timer.value = time();
    	
        var $code = str_replace(chr(10), "\n", json.code);
        var lastline = Editor.currentLine();
        var offset = Editor.cursorPosition().character;
        var lenght = strlen(Editor.lineContent(Editor.cursorPosition().line));
        
        if ($del == 1)
        	offset = offset - 1;
        else
            offset = offset + 1;
        
        if (offset < 0)
        {
        	lastline = lastline - 1;
        	offset = (-1) * lenght;
        	if (lenght == 0) {offset = -0.1;}
        }
        
        if ($caracter == 13) {
        	lastline++;
        	offset = 0;
        }
        
        Editor.importCode($code + chr(13) + chr(10));
        Editor.afterPaste(lastline, offset, $caracter);
        Insert.value = 1;
       }
    );
};

jQuery(document).bind('paste', function(e){ e.preventDefault(); });
jQuery(document).bind('cut', function(e){ e.preventDefault(); });

$(document).keydown(function(event){
	
	var Insert = window.frameElement.CodeMirror.textareaInsert;
	var Editor = window.frameElement.CodeMirror.editor;
	
	keycode = ((event.keyCode ? event.keyCode : event.which));
	
	if (Insert.value == 0)
		return false;
	
	if (keycode == 8)
	{
		Insert.value = 0;
		event.preventDefault();
		
		var $code = new String("");
		$code = Editor.getCode();
  
		var line = Editor.currentLine();
	 	var $position = 0;
	 	var pos_end = 0;
	
	 	for (i=1;i<line;i=i+1) {pos_end = strpos($code, "\n", pos_end + 1);}
  	
		$position = pos_end + Editor.cursorPosition().character;
		if (line>1) {$position++;}
		
		var jsonLimites = getLimites($code);
  	
        	var $limite=new Array();
        	var $total=0;
        	var $i=1;
        	var $bandera = 0;
  	   
  	    	$.each(jsonLimites, function(index, value) { 
               	$limite[index] = value;
  			   	$total=index;
        	});
	   
	    	while ($i < $total) {
	   	   		$j = $i + 1;
	   	 
           		if ($position > $limite[$i] && $position < $limite[$j]) {
	   	      		$bandera = 1;
	   	      		window.frameElement.CodeMirror.textareaInsert.value = 1;
	   	   		}
	   	 
	   	   		$i = $i + 2;
	    	}
	    	
	    	if ($bandera == 0) {
	    	
	    		$ant = substr($code, 0, $position-1);
	    		if ($ant == false || $position == 0)
	   	    		$ant = "";
	   	      
				$pos = substr($code, $position);
				if ($pos == false)
	   	    		$pos = "";
	   	         
	   			$codigo = $ant + $pos;
	   	 		$incremento = -1;
	   	    	$del = 1;
	   	    	$char_del = substr($code, $position - 1, 1);
	   	    	
	   	    	pushData($codigo, $position, $del, $char_del, keycode);
	   	    
	   	   	}
		
		return false;	
	}
	
});

$(document).keypress(function(event){ 
	
  var keycode = ((event.keyCode ? event.keyCode : event.which));
	
  var Insert = window.frameElement.CodeMirror.textareaInsert;
  var IE = document.selection && window.ActiveXObject && /MSIE/.test(navigator.userAgent);
  
  if ( Insert.value == 0 ) {
  	return false;
  }
  
  if (event.which == 127) {
  	return false;
  }
  
  Insert.value = 0;
  
  var Editor = window.frameElement.CodeMirror.editor;
	
  var $code = new String("");
  $code = Editor.getCode();
  
  var jsonLimites = getLimites($code);
  
  var line = Editor.currentLine();
  var $position = 0;
  var pos_end = 0;
	
  for (i=1;i<line;i=i+1) {pos_end = strpos($code, "\n", pos_end + 1);}
  	
  $position = pos_end + Editor.cursorPosition().character;
  var lenght = strlen(Editor.lineContent(Editor.cursorPosition().line));
  if (line>1) {$position++;}
	
  if (keycode == 13 || keycode == 123 || keycode == 125 || (keycode == 32 && getLevel($code, $position) == 0) || keycode == 59) {
	
     event.preventDefault();
  	
        var $limite=new Array();
        var $total=0;
        var $i=1;
        var $bandera = 0;
  	   
  	    $.each(jsonLimites, function(index, value) { 
               $limite[index] = value;
  			   $total=index;
        });
	   
	    while ($i < $total) {
	   	   $j = $i + 1;
	   	 
           if ($position > $limite[$i] && $position < $limite[$j]) {
	   	      $bandera = 1;
	   	      window.frameElement.CodeMirror.textareaInsert.value = 1;
	   	   }
	   	 
	   	   $i = $i + 2;
	    }
	   
	    if ($bandera == 0) {
	   	   var $caracteres;
	   	   var $incremento = 1;
	   	   var $del = 0;
	   	   var $char_del = "";
	   	   
	   	   $caracteres = strlen($code);
	   	   
	   	   $ant = substr($code, 0, $position);
	   	   if ($ant == false)
	   	      $ant = "";
	   	      
	   	   $pos = substr($code, $position, $caracteres);
	   	   if ($pos == false)
	   	      $pos = "";
	   	  
	   	   if (keycode == 13) {
	   	   	  $codigo = $ant + chr(13) + chr(10) + $pos;
	   	   }
	   	   else if (keycode == 123) {
	   	      $codigo = $ant + String.fromCharCode(123) + String.fromCharCode(125) + $pos;
	   	      $incremento = 1;
	   	   }
	   	   else if (keycode == 125) {
	   	   	  $codigo = $ant + $pos;
	   	   	  $incremento = 0;
	   	   }
	   	   else
	   	      $codigo = $ant + String.fromCharCode(keycode) + $pos;
	   			  	    
	   	   pushData($codigo, $position, $del, $char_del, keycode);
	    }

  
  } else {
  	
	var $limite=new Array();
    var $total=0;
    var $i=1;
  	   
  	$.each(jsonLimites, function(index, value) { 
          $limite[index] = value;
  	      $total=index;
    });
	   
	while ($i < $total) {
		$j = $i + 1;
	   	 
        if ($position > $limite[$i] && $position < $limite[$j]) {
	   		window.frameElement.CodeMirror.textareaInsert.value = 1;
	   		return false;
	   	}
	   	 
	   	$i = $i + 2;
	}
  	
  	Insert.value = 1;
  }

});

(function ($, undefined) {
    $.fn.getCursorPosition = function() {
        var el = $(this).get(0);
        var pos = 0;
        if('selectionStart' in el) {
            pos = el.selectionStart;
        } else if('selection' in document) {
            el.focus();
            var Sel = document.selection.createRange();
            var SelLength = document.selection.createRange().text.length;
            Sel.moveStart('character', -el.value.length);
            pos = Sel.text.length - SelLength;
        }
        return pos;
    }
    
})(jQuery);

$.fn.setCursorPosition = function(pos) {
  this.each(function(index, elem) {
    if (elem.setSelectionRange) {
      elem.setSelectionRange(pos, pos);
    } else if (elem.createTextRange) {
      var range = elem.createTextRange();
      range.collapse(true);
      range.moveEnd('character', pos);
      range.moveStart('character', pos);
      range.select();
    }
  });
  return this;
};

function getLevel($codigo, $posicion) {
		
	$subcodigo = substr($codigo, 0, $posicion);
	
	$levelup = substr_count($subcodigo,"{");
	$leveldown = substr_count($subcodigo,"}");
	
	$level = $levelup - $leveldown;
	
	return $level;
}

function getLimites($codigo) {
	
	var $i = 1;
	var $vector = new Array();
 
	$codigo = str_replace("\"", "'", $codigo);
	$codigo = str_replace("\\", "", $codigo);
   
	var $desde = strpos($codigo, '<!');
 
	while (is_numeric($desde)) {
		$hasta = strpos($codigo, '</!>', $desde) + 4;
	 
		$vector[$i] = $desde;
		$vector[($i+1)] = $hasta + 1;
	 
		$i = $i+2;
	 
		$desde = strpos($codigo, '<!', $hasta);
	}
 
 
	// No hay ningún bloqueo en el código
	if(!isArray($vector)){
		$vector[1] = -1;
	}
 
 	return $vector;
}

function isArray(obj) {
   if (obj.constructor.toString().indexOf("Array") == -1)
      return false;
   else
      return true;
}
