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
 * This file contains all necessary code to define and process an edit form
 *
 * @package mod-wikicode
 *
 * @author Josep Arus
 * @author Antonio J. González
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/mod/wikicode/editors/wikieditor.php');
require_once($CFG->dirroot . '/mod/wikicode/chat/wikichat.php');

class mod_wikicode_edit_form extends moodleform {

    protected function definition() {
        global $CFG, $USER, $DB;

        $mform =& $this->_form;

        $version = $this->_customdata['version'];
        $format  = $this->_customdata['format'];
        $tags    = !isset($this->_customdata['tags'])?"":$this->_customdata['tags'];

        if ($format != 'html') {
            $contextid  = $this->_customdata['contextid'];
            $filearea   = $this->_customdata['filearea'];
            $fileitemid = $this->_customdata['fileitemid'];
        }

        if (isset($this->_customdata['pagetitle'])) {
            $pagetitle = get_string('editingpage', 'wikicode', $this->_customdata['pagetitle']);
        } else {
            $pagetitle = get_string('editing', 'wikicode');
        }

        //editor
        $mform->addElement('header', 'general', $pagetitle);

        $fieldname = get_string('format' . $format, 'wikicode');
        if ($format != 'html') {
            // Use wiki editor
            $extensions = file_get_typegroup('extension', 'web_image');
            $fs = get_file_storage();
            $tree = $fs->get_area_tree($contextid, 'mod_wikicode', 'attachments', $fileitemid);
            $files = array();
            foreach ($tree['files'] as $file) {
                $filename = $file->get_filename();
                foreach ($extensions as $ext) {
                    if (preg_match('#'.$ext.'$#i', $filename)) {
                        $files[] = $filename;
                    }
                }
            }
			$buttoncommands=array();
			$buttoncommands[] =& $mform->createElement('button','editoption','Unlock', array('id' => 'btnunlock', 'class' => 'btnunlock'));
			$buttoncommands[] =& $mform->createElement('button','editoption','Refresh', array('id' => 'btnref', 'class' => 'btnref'));
			$buttoncommands[] =& $mform->createElement('submit', 'editoption', 'Save', array('id' => 'save'));
			$mform->addGroup($buttoncommands, 'editoption', 'Actions', '', true);
			$mform->addHelpButton('editoption', 'editoption', 'wikicode');
            $mform->addElement('wikicodeeditor', 'newcontent', $fieldname, array('cols' => 150, 'rows' => 30, 'Wiki_format' => $format, 'files'=>$files));
        } else {
            $mform->addElement('editor', 'newcontent_editor', $fieldname, null, page_wikicode_edit::$attachmentoptions);
            $mform->addHelpButton('newcontent_editor', 'formathtml', 'wikicode');
        }
		
		//chat
		$mform->addElement('header','chat','Chat');
		$mform->addElement('wikicodechat', 'wikicodechat', null, array('itemid'=>$fileitemid));
		
		//compiler
		$mform->addElement('header','compiler', 'Compiler');
		$mform->addElement('textarea', 'textCompiler', '', 'wrap="virtual" rows="3" cols="150" readonly="readonly" ');
		//$mform->addElement('submit','editoption','Compilar', array('id' => 'compile'));
		//$mform->addElement('submit','editoption','Descargar', array('id' => 'download'));
		
		$buttonarray=array();
		$buttonarray[] =& $mform->createElement('submit','editoption','Compile', array('id' => 'compile'));
		$buttonarray[] =& $mform->createElement('submit','editoption','Download EXE', array('id' => 'compile'));
		$mform->addGroup($buttonarray, 'editoption', 'Options:', '', true);

        //hiddens
        if ($version >= 0) {
            $mform->addElement('hidden', 'version');
            $mform->setDefault('version', $version);
        }

        $mform->addElement('hidden', 'contentformat');
        $mform->setDefault('contentformat', $format);
		
		$mform->addElement('hidden', 'insert');
		$mform->setDefault('insert', 1);

    }

}
