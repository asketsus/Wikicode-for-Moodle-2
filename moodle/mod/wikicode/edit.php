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
 * This file contains all necessary code to edit a wiki page
 *
 * @package mod-wikicode
 *
 * @author Jordi Piguillem
 * @author Marc Alier
 * @author David Jimenez
 * @author Josep Arus
 * @author Kenneth Riba
 * @author Antonio J. González
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_once($CFG->dirroot . '/mod/wikicode/lib.php');
require_once($CFG->dirroot . '/mod/wikicode/locallib.php');
require_once($CFG->dirroot . '/mod/wikicode/pagelib.php');

$pageid = required_param('pageid', PARAM_INT);
$contentformat = optional_param('contentformat', '', PARAM_ALPHA);
$option = optional_param('editoption', '', PARAM_TEXT);
$section = optional_param('section', "", PARAM_TEXT);
$version = optional_param('version', -1, PARAM_INT);
$attachments = optional_param('attachments', 0, PARAM_INT);
$deleteuploads = optional_param('deleteuploads', 0, PARAM_RAW);
$compiled = optional_param('compiled', 0, PARAM_INT);

$newcontent = '';
if (!empty($newcontent) && is_array($newcontent)) {
    $newcontent = $newcontent['text'];
} 

if (!empty($option) && is_array($option)) {
    $option = $option['editoption'];
}

if (!$page = wikicode_get_page($pageid)) {
    print_error('incorrectpageid', 'wikicode');
}

if (!$subwiki = wikicode_get_subwiki($page->subwikiid)) {
    print_error('incorrectsubwikiid', 'wikicode');
}

if (!$wiki = wikicode_get_wiki($subwiki->wikiid)) {
    print_error('incorrectwikiid', 'wikicode');
}

if (!$cm = get_coursemodule_from_instance('wikicode', $wiki->id)) {
    print_error('invalidcoursemodule');
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

if (!empty($section) && !$sectioncontent = wikicode_get_section_page($page, $section)) {
    print_error('invalidsection', 'wikicode');
}

require_login($course, true, $cm);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('mod/wikicode:editpage', $context);

add_to_log($course->id, 'wikicode', 'edit', "edit.php?id=$cm->id", "$wiki->id");

if ($option == get_string('save', 'wikicode')) {
    if (!confirm_sesskey()) {
        print_error(get_string('invalidsesskey', 'wikicode'));
    }
    $wikipage = new page_wikicode_save($wiki, $subwiki, $cm);
    $wikipage->set_page($page);
    $wikipage->set_newcontent($newcontent);
    $wikipage->set_upload(true);
} else {  
    if ($option == 'Compile' or $option == 'Download EXE') {
        if (!confirm_sesskey()) {
            print_error(get_string('invalidsesskey', 'wikicode'));
        }
        $wikipage = new page_wikicode_compile($wiki, $subwiki, $cm);
        $wikipage->set_page($page);
		$wikipage->set_download(($option == 'Download EXE'));
    }
	else {
        if ($option == get_string('cancel')) {
            //delete lock
            wikicode_delete_locks($page->id, $USER->id, $section);

            redirect($CFG->wwwroot . '/mod/wikicode/view.php?pageid=' . $pageid);
        } else {
            $wikipage = new page_wikicode_edit($wiki, $subwiki, $cm);
            $wikipage->set_page($page);
            $wikipage->set_upload($option == get_string('upload', 'wikicode'));
			$wikipage->set_compiled($compiled);
        }
    }

    if (has_capability('mod/wikicode:overridelock', $context)) {
        $wikipage->set_overridelock(true);
    }
}

if ($version >= 0) {
    $wikipage->set_versionnumber($version);
}

if (!empty($section)) {
    $wikipage->set_section($sectioncontent, $section);
}

if (!empty($attachments)) {
    $wikipage->set_attachments($attachments);
}

if (!empty($deleteuploads)) {
    $wikipage->set_deleteuploads($deleteuploads);
}

if (!empty($contentformat)) {
    $wikipage->set_format($contentformat);
}

$wikipage->print_header();

$wikipage->print_content();

$wikipage->print_footer();
