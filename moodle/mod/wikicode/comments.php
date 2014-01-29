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
 * This file contains all necessary code to view a discussion page
 *
 * @package mod-wikicode
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_once($CFG->dirroot . '/mod/wikicode/lib.php');
require_once($CFG->dirroot . '/mod/wikicode/locallib.php');
require_once($CFG->dirroot . '/mod/wikicode/pagelib.php');

$pageid = required_param('pageid', PARAM_TEXT);

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

require_login($course->id, true, $cm);

add_to_log($course->id, 'wikicode', 'comments', 'comments.php?id=' . $cm->id, $wiki->id);

/// Print the page header
$wikipage = new page_wikicode_comments($wiki, $subwiki, $cm);

$wikipage->set_page($page);

$wikipage->print_header();
$wikipage->print_content();
$wikipage->print_footer();
