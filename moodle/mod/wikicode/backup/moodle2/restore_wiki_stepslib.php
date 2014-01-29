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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod
 * @subpackage wikicode
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_wiki_activity_task
 */

/**
 * Structure step to restore one wiki activity
 */
class restore_wiki_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('wiki', '/activity/wikicode');
        if ($userinfo) {
            $paths[] = new restore_path_element('wiki_subwiki', '/activity/wikicode/subwikis/subwiki');
            $paths[] = new restore_path_element('wiki_page', '/activity/wikicode/subwikis/subwiki/pages/page');
            $paths[] = new restore_path_element('wiki_version', '/activity/wikicode/subwikis/subwiki/pages/page/versions/version');
            $paths[] = new restore_path_element('wiki_tag', '/activity/wikicode/subwikis/subwiki/pages/page/tags/tag');
            $paths[] = new restore_path_element('wiki_synonym', '/activity/wikicode/subwikis/subwiki/synonyms/synonym');
            $paths[] = new restore_path_element('wiki_link', '/activity/wikicode/subwikis/subwiki/links/link');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_wiki($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->editbegin = $this->apply_date_offset($data->editbegin);
        $data->editend = $this->apply_date_offset($data->editend);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the wiki record
        $newitemid = $DB->insert_record('wiki_code', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_wiki_subwiki($data) {
        global $DB;


        $data = (object)$data;
        $oldid = $data->id;
        $data->wikiid = $this->get_new_parentid('wiki');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('wiki_subwikis_code', $data);
        $this->set_mapping('wiki_subwiki', $oldid, $newitemid);
    }
    protected function process_wiki_page($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->subwikiid = $this->get_new_parentid('wiki_subwiki');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timerendered = $this->apply_date_offset($data->timerendered);

        $newitemid = $DB->insert_record('wiki_pages_code', $data);
        $this->set_mapping('wiki_page', $oldid, $newitemid, true); // There are files related to this
    }
    protected function process_wiki_version($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->pageid = $this->get_new_parentid('wiki_page');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $newitemid = $DB->insert_record('wiki_versions_code', $data);
        $this->set_mapping('wiki_version', $oldid, $newitemid);
    }
    protected function process_wiki_synonym($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->subwikiid = $this->get_new_parentid('wiki_subwiki');
        $data->pageid = $this->get_mappingid('wiki_page', $data->pageid);

        $newitemid = $DB->insert_record('wiki_synonyms_code', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }
    protected function process_wiki_link($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->subwikiid = $this->get_new_parentid('wiki_subwiki');
        $data->frompageid = $this->get_mappingid('wiki_page', $data->frompageid);
        $data->topageid = $this->get_mappingid('wiki_page', $data->topageid);

        $newitemid = $DB->insert_record('wiki_links_code', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }

    protected function process_wiki_tag($data) {
        global $CFG, $DB;

        $data = (object)$data;
        $oldid = $data->id;

        if (empty($CFG->usetags)) { // tags disabled in server, nothing to process
            return;
        }

        $tag = $data->rawname;
        $itemid = $this->get_new_parentid('wiki_page');
        tag_set_add('wiki_pages', $itemid, $tag);
    }

    protected function after_execute() {
        // Add wiki related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_wikicode', 'intro', null);
        $this->add_related_files('mod_wikicode', 'attachments', 'wiki_page');
    }
}
