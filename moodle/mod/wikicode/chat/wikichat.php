<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains all necessary code to define a wikicode chat
 *
 * @package mod-wikicode
 *
 * @author Antonio J. González
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/lib/form/text.php');

class MoodleQuickForm_Wikichat extends MoodleQuickForm_text {

    private $itemid;

    function MoodleQuickForm_Wikichat($elementName = null, $elementLabel = null, $attributes = null) {	
		$this->itemid = $attributes['itemid'];		
    }

    function setWikiFormat($wikiformat) {
        $this->wikiformat = $wikiformat;
    }

    function toHtml() {
        $chat = $this->getChat();

        return $chat;
    }

    private function getChat() {
    	global $PAGE, $OUTPUT, $CFG, $USER;
		
		$itemid = $this->itemid;
		make_upload_directory('wikicode');
		make_upload_directory('wikicode/chat');
		make_upload_directory('wikicode/chat/logs');
		
		$html = "<link type=\"text/css\" rel=\"stylesheet\" href=\"chat/style.css\" />";
		
		$html .= "<div id=\"wrapper\">";
	    $html .= "<div id=\"menu\">";
	    $html .= "	<div style=\"clear:both\"></div>";
	    $html .= "</div>";	
	    $html .= "<div id=\"chatbox\">";
	    
	    if(file_exists($CFG->dataroot."/wikicode/chat/logs/log_".$itemid.".html") && filesize($CFG->dataroot."/wikicode/chat/logs/log_".$itemid.".html") > 0){
	
			$handle = fopen($CFG->dataroot."/wikicode/chat/logs/log_".$itemid.".html", "r");
			$contents = fread($handle, filesize($CFG->dataroot."/wikicode/chat/logs/log_".$itemid.".html"));
			fclose($handle);
		
			$html .= $contents;
	    }
	
		$html .= "</div>
		";

		$html .= "	<input name=\"usermsg\" type=\"text\" id=\"usermsg\" />
		";
		$html .= "	<input name=\"submitmsg\" type=\"submit\"  id=\"submitmsg\" value=\"Send\" />
		";

  		$html .= "</div>
  		";
		
  		/*$html .= "<script type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js\"></script>
  		";*/
  		$html .= "<script type=\"text/javascript\">
  		";
		// jQuery Document
  		$html .= "$(document).ready(function(){
  			";
		
		//If user submits the form
  		$html .= 	"$(\"#submitmsg\").click(function(){
  				";
  		$html .= 		"var clientmsg = $(\"#usermsg\").val();
  		";
  		$html .= 		"$.post(\"chat/post.php\", {text: clientmsg, path: \"".$CFG->dataroot."\", itemid: \"".$itemid."\", user: \"".$USER->username."\"});
  		";				
  		$html .= 		"$(\"#usermsg\").attr(\"value\", \"\");
  		";
  		$html .= 		"return false;
  		";
  		$html .= 	"});
  		";
		
		$html .= "$(\"#usermsg\").keypress(function(event){
						var keycode = ((event.keyCode ? event.keyCode : event.which));
						
						if (keycode == 13) {
							var clientmsg = $(\"#usermsg\").val();
							$.post(\"chat/post.php\", {text: clientmsg, path: \"".$CFG->dataroot."\", itemid: \"".$itemid."\", user: \"".$USER->username."\"});
							$(\"#usermsg\").attr(\"value\", \"\");
							return false;
						}	
				  });";
	
		//Load the file containing the chat log
		$html .= 	"function loadLog(){
						var oldscrollHeight = $(\"#chatbox\").attr(\"scrollHeight\") - 20;
						$.getJSON(\"chat/reload.php\",
							{path: \"".$CFG->dataroot."/wikicode/chat/logs/log_".$itemid.".html\"},
							function(html){
								$(\"#chatbox\").html(html); 		
								var newscrollHeight = $(\"#chatbox\").attr(\"scrollHeight\") - 20;
								if(newscrollHeight > oldscrollHeight){
									$(\"#chatbox\").animate({ scrollTop: newscrollHeight }, 'normal'); 
								}
							});
					}
					setInterval (loadLog, 2500);
					});
				</script>";  
			
		return $html;
    }

}

//register wikieditor
MoodleQuickForm::registerElementType('wikicodechat', $CFG->dirroot."/mod/wikicode/editors/wikichat.php", 'MoodleQuickForm_Wikichat');
