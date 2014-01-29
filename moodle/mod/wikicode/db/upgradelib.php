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
 * @package   mod-wikicode
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function wiki_add_wiki_fields() {
    global $DB;

    upgrade_set_timeout();
    $dbman = $DB->get_manager();
    /// Define table wiki to be created
    $table = new xmldb_table('wikicode');

    // Adding fields to wiki_code table
    $wikitable = new xmldb_table('wikicode');

    // in MOODLE_20_SABLE branch, summary field is renamed as intro
    // so we renamed it back to summary to keep upgrade going as moodle 1.9
    $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null);
    if ($dbman->field_exists($wikitable, $field)) {
        $dbman->rename_field($wikitable, $field, 'summary');
    }
    $dbman->add_field($wikitable, $field);

    $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', null);
    if (!$dbman->field_exists($wikitable, $field)) {
        $dbman->add_field($wikitable, $field);
    }

    $field = new xmldb_field('firstpagetitle', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'First Page', null);
    $dbman->add_field($wikitable, $field);

    $field = new xmldb_field('wikimode', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'collaborative', null);
    $dbman->add_field($wikitable, $field);

    $field = new xmldb_field('defaultformat', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'creole', null);
    $dbman->add_field($wikitable, $field);

    $field = new xmldb_field('forceformat', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', null);
    $dbman->add_field($wikitable, $field);

    $field = new xmldb_field('scaleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', null);
    $dbman->add_field($wikitable, $field);

    $field = new xmldb_field('editbegin', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', null);
    $dbman->add_field($wikitable, $field);

    $field = new xmldb_field('editend', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0', null);
    $dbman->add_field($wikitable, $field);

}

/**
 * Install wiki 2.0 tables
 */
function wiki_upgrade_install_20_tables() {
    global $DB;
    upgrade_set_timeout();
    $dbman = $DB->get_manager();

    /// Define table wiki_subwikis_code to be created
    $table = new xmldb_table('wikicode_subwikis');

    /// Adding fields to table wiki_subwikis
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('wikiid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

    /// Adding keys to table wiki_subwikis
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_key('wikiidgroupiduserid', XMLDB_KEY_UNIQUE, array('wikiid', 'groupid', 'userid'));
    $table->add_key('wikicodefk', XMLDB_KEY_FOREIGN, array('wikiid'), 'wikicode', array('id'));

    /// Conditionally launch create table for wiki_subwikis_code
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    /// Define table wiki_pages_code to be created
    $table = new xmldb_table('wikicode_pages');

    /// Adding fields to table wiki_pages
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('subwikiid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'title');
    $table->add_field('cachedcontent', XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('timerendered', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('pageviews', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('readonly', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

    /// Adding keys to table wiki_pages_code
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_key('subwikititleuser', XMLDB_KEY_UNIQUE, array('subwikiid', 'title', 'userid'));
    $table->add_key('subwikicodefk', XMLDB_KEY_FOREIGN, array('subwikiid'), 'wikicode_subwiki', array('id'));

    /// Conditionally launch create table for wiki_pages_code
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    /// Define table wiki_versions to be created
    $table = new xmldb_table('wikicode_versions');

    /// Adding fields to table wiki_versions_code
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('pageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('content', XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null);
    $table->add_field('contentformat', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'creole');
    $table->add_field('version', XMLDB_TYPE_INTEGER, '5', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

    /// Adding keys to table wiki_versions_code
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_key('pagecodefk', XMLDB_KEY_FOREIGN, array('pageid'), 'wikicode_pages', array('id'));

    /// Conditionally launch create table for wiki_versions
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    /// Define table wiki_synonyms_code to be created
    $table = new xmldb_table('wikicode_synonyms');

    /// Adding fields to table wiki_synonyms
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('subwikiid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('pageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('pagesynonym', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'Pagesynonym');

    /// Adding keys to table wiki_synonyms
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_key('pageidsyn', XMLDB_KEY_UNIQUE, array('pageid', 'pagesynonym'));

    /// Conditionally launch create table for wiki_synonyms_code
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    /// Define table wiki_links_code to be created
    $table = new xmldb_table('wikicode_links');

    /// Adding fields to table wiki_links
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('subwikiid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('frompageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('topageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('tomissingpage', XMLDB_TYPE_CHAR, '255', null, null, null, null);

    /// Adding keys to table wiki_links_code
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_key('frompageidcodefk', XMLDB_KEY_FOREIGN, array('frompageid'), 'wikicode_pages', array('id'));
    $table->add_key('subwikicodefk', XMLDB_KEY_FOREIGN, array('subwikiid'), 'wikicode_subwiki', array('id'));

    /// Conditionally launch create table for wiki_links
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    /// Define table wiki_locks_code to be created
    $table = new xmldb_table('wikicode_locks');

    /// Adding fields to table wiki_locks_code
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('pageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('sectionname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
    $table->add_field('lockedat', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

    /// Adding keys to table wiki_locks_code
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Conditionally launch create table for wiki_locks
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }
}
