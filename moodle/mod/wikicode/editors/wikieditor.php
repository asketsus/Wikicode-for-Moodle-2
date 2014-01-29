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
 * This file contains all necessary code to define a wiki editor
 *
 * @package mod-wiki-2.0
 * @copyrigth 2009 Marc Alier, Jordi Piguillem marc.alier@upc.edu
 * @copyrigth 2009 Universitat Politecnica de Catalunya http://www.upc.edu
 *
 * @author Josep Arus
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/lib/form/textarea.php');

class MoodleQuickForm_Wikieditor extends MoodleQuickForm_textarea {

    private $files;

    function MoodleQuickForm_Wikieditor($elementName = null, $elementLabel = null, $attributes = null) {
        if (isset($attributes['Wiki_format'])) {
            $this->wikiformat = $attributes['Wiki_format'];
            unset($attributes['Wiki_format']);
        }
        if (isset($attributes['files'])) {
            $this->files = $attributes['files'];
            unset($attributes['files']);
        }

        parent::MoodleQuickForm_textarea($elementName, $elementLabel, $attributes);
    }

    function setWikiFormat($wikiformat) {
        $this->wikiformat = $wikiformat;
    }

    function toHtml() {
        $textarea = parent::toHtml();

        return $this->{
            $this->wikiformat."Editor"}
            ($textarea);
    }

    function creoleEditor($textarea) {
        return $this->printWikiEditor($textarea);
    }

    function nwikiEditor($textarea) {
        return $this->printWikiEditor($textarea);
    }
	
	function CEditor($textarea) {
		global $OUTPUT;

        $textarea = $OUTPUT->container_start().$textarea.$OUTPUT->container_end();
		$editor = $this->getEditor();

        return $textarea.$editor;

	}

    private function printWikiEditor($textarea) {
        global $OUTPUT;

        $textarea = $OUTPUT->container_start().$textarea.$OUTPUT->container_end();

        $buttons = $this->getButtons();

        return $buttons.$textarea;
    }
	
	private function getEditor() {
		global $PAGE, $OUTPUT, $CFG;
		
		$editor = $this->wikiformat;
		
		$PAGE->requires->js('/mod/wikicode/js/php.js');
		$PAGE->requires->js('/mod/wikicode/js/codemirror.js');
			
			
		$html .= "<script src=\"js/php.js\" type=\"text/javascript\"></script>";
		$html .= "<script src=\"js/jquery.js\" type=\"text/javascript\"></script>";
		$html .= "<script src=\"js/unlock.js\" type=\"text/javascript\"></script>";
       
        $html .= "<link rel=\"stylesheet\" type=\"text/css\"/>";
        $html .= "<style type=\"text/css\">";
        $html .= " .CodeMirror-line-numbers {";
        $html .= "   width: 2.2em;";
        $html .= "  color: #aaa;";
        $html .= "  background-color: #eee;";
        $html .= "  text-align: right;";
        $html .= "  padding-right: .3em;";
        $html .= "  font-size: 10pt;";
        $html .= "  font-family: monospace;";
        $html .= "  padding-top: .5em;";
        $html .= "}";
        $html .= "</style>";

        $html .= "<script type=\"text/javascript\">";
        $html .=   "var editor = CodeMirror.fromTextArea('id_newcontent', {";
        $html .=   "parserfile: \"parseC.js\",";
        $html .=   "stylesheet: \"css/Ccolors.css\",";
        $html .=   "path: \"js/\",";
        $html .=   "continuousScanning: 500,";
		$html .=   "height: \"450px\",";
        $html .=   "lineNumbers: true";
        $html .= "});";
        $html .="</script>";

        return $html;
		
	}

    private function getButtons() {
        global $PAGE, $OUTPUT, $CFG;

        $editor = $this->wikiformat;

        $tag = $this->getTokens($editor, 'bold');
        $Wiki_editor['bold'] = array('ed_bold.gif', get_string('wikiboldtext', 'Wikicode'), $tag[0], $tag[1], get_string('wikiboldtext', 'Wikicode'));
		
        $Wiki_editor['italic'] = array('ed_italic.gif', get_string('wikiitalictext', 'Wikicode'), $tag[0], $tag[1], get_string('wikiitalictext', 'Wikicode'));

        $Wiki_editor['image'] = array('ed_img.gif', get_string('wikiimage', 'Wikicode'), $imagetag[0], $imagetag[1], get_string('wikiimage', 'Wikicode'));

        $Wiki_editor['internal'] = array('ed_internal.gif', get_string('wikiinternalurl', 'Wikicode'), $tag[0], $tag[1], get_string('wikiinternalurl', 'Wikicode'));

        $Wiki_editor['external'] = array('ed_external.gif', get_string('wikiexternalurl', 'Wikicode'), $tag, "", get_string('wikiexternalurl', 'Wikicode'));

        $Wiki_editor['u_list'] = array('ed_ul.gif', get_string('wikiunorderedlist', 'Wikicode'), '\\n'.$tag[0], '', '');
        $Wiki_editor['o_list'] = array('ed_ol.gif', get_string('wikiorderedlist', 'Wikicode'), '\\n'.$tag[1], '', '');

        $Wiki_editor['h1'] = array('ed_h1.gif', get_string('wikiheader', 'Wikicode', 1), '\\n'.$tag.' ', ' '.$tag.'\\n', get_string('wikiheader', 'Wikicode', 1));
        $Wiki_editor['h2'] = array('ed_h2.gif', get_string('wikiheader', 'Wikicode', 2), '\\n'.$tag.$tag.' ', ' '.$tag.$tag.'\\n', get_string('wikiheader', 'Wikicode', 2));
        $Wiki_editor['h3'] = array('ed_h3.gif', get_string('wikiheader', 'Wikicode', 3), '\\n'.$tag.$tag.$tag.' ', ' '.$tag.$tag.$tag.'\\n', get_string('wikiheader', 'Wikicode', 3));

        $Wiki_editor['hr'] = array('ed_hr.gif', get_string('wikihr', 'Wikicode'), '\\n'.$tag.'\\n', '', '');

        $Wiki_editor['nowiki'] = array('ed_nowiki.gif', get_string('wikinowikitext', 'Wikicode'), $tag[0], $tag[1], get_string('wikinowikitext', 'Wikicode'));

        $PAGE->requires->js('/mod/wikicode/editors/wikicode/buttons.js');

        $html = '<div class="wikieditor-toolbar">';
        foreach ($Wiki_editor as $button) {
            $html .= "<a href=\"javascript:insertTags";
            $html .= "('".$button[2]."','".$button[3]."','".$button[4]."');\">";
            $html .= html_writer::empty_tag('img', array('alt' => $button[1], 'src' => $CFG->wwwroot . '/mod/wikicode/editors/wikicode/images/' . $button[0]));
            $html .= "</a>";
        }
        $html .= "<select onchange=\"insertTags('{$imagetag[0]}', '{$imagetag[1]}', this.value)\">";
        $html .= "<option value='" . s(get_string('wikiimage', 'Wikicode')) . "'>" . get_string('insertimage', 'Wikicode') . '</option>';
        foreach ($this->files as $filename) {
            $html .= "<option value='".s($filename)."'>";
            $html .= $filename;
            $html .= '</option>';
        }
        $html .= '</select>';
        $html .= $OUTPUT->help_icon('insertimage', 'Wikicode');
        $html .= '</div>';

        return $html;
    }

    private function getTokens($format, $token) {
        $tokens = Wikicode_parser_get_token($format, $token);

        if (is_array($tokens)) {
            foreach ($tokens as & $t) {
                $this->escapeToken($t);
            }
        } else {
            $this->escapeToken($tokens);
        }

        return $tokens;
    }

    private function escapeToken(&$token) {
        $token = urlencode(str_replace("'", "\'", $token));
    }
}

//register wikieditor
MoodleQuickForm::registerElementType('wikicodeeditor', $CFG->dirroot."/mod/wikicode/editors/wikieditor.php", 'MoodleQuickForm_Wikieditor');
