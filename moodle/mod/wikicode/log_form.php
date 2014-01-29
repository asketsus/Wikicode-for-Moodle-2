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
 * @copyrigth 2009 Marc Alier, Jordi Piguillem marc.alier@upc.edu
 * @copyrigth 2009 Universitat Politecnica de Catalunya http://www.upc.edu
 *
 * @author Josep Arus
 * @author Antonio J. González
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

class mod_wikicode_log_form extends moodleform {

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
            $pagetitle = get_string('logpage', 'wikicode', $this->_customdata['pagetitle']);
        } else {
            $pagetitle = get_string('loging', 'wikicode');
        }
		
		//Edit time
		$timeE = $this->_customdata['page']->timer;
		$secondsE = $timeE % 60;
		$timeE = ($timeE - $secondsE) / 60;
		$minutesE = $timeE % 60;
		$hoursE = ($timeE - $minutesE) / 60;
		
		//Total time
		$time = $this->_customdata['page']->timeendedit - $this->_customdata['page']->timestartedit;
		$seconds = $time % 60;
		$time = ($time - $seconds) / 60;
		$minutes = $time % 60;
		$hours = ($time - $minutes) / 60;		
		
		//Stats
		$attr = array('size' => '75', 'readonly' => 1);
		$mform->addElement('header','stats', 'Stats');
		$attr['value'] = $hoursE . " hours, " . $minutesE . " minutes, " . $secondsE . " seconds";
		$mform->addElement('text', 'timeedit', 'Edit Time', $attr);
		$mform->addHelpButton('timeedit', 'timeedit', 'wikicode');
		$attr['value'] = $hours . " hours, " . $minutes . " minutes, " . $seconds . " seconds";
		$mform->addElement('text', 'timetotal', 'Total Time', $attr);
		$mform->addHelpButton('timetotal', 'timetotal', 'wikicode');
		$attr['value'] = $this->_customdata['page']->errorcompile;
		$mform->addElement('text', 'errorscompilation', 'Compilation Errors', $attr);
		$mform->addHelpButton('errorscompilation', 'errorscompilation', 'wikicode');


        $mform->addElement('hidden', 'contentformat');
        $mform->setDefault('contentformat', $format);
		
		$mform->addElement('hidden', 'insert');
		$mform->setDefault('insert', 1);

    }

}
