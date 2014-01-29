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
 * This file keeps track of upgrades to the wiki module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * @package mod-wikicode
 * @copyrigth 2009 Marc Alier, Jordi Piguillem marc.alier@upc.edu
 * @copyrigth 2009 Universitat Politecnica de Catalunya http://www.upc.edu
 *
 * @author Jordi Piguillem
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 */

function xmldb_wiki_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    // TODO: Will hold the old tables so we will have chance to fix problems
    // Will remove old tables once migrating 100% stable
    // Step 10: delete old tables
    //if ($oldversion < 2011060300) {
        //$tables = array('wiki_pages', 'wiki_locks', 'wiki_entries');

        //foreach ($tables as $tablename) {
            //$table = new xmldb_table($tablename . '_old');
            //if ($dbman->table_exists($table)) {
                //$dbman->drop_table($table);
            //}
        //}
        //echo $OUTPUT->notification('Droping old tables', 'notifysuccess');
        //upgrade_mod_savepoint(true, 2011060300, 'wiki');
    //}

    // Moodle v2.1.0 release upgrade line
    // Put any upgrade step following this

    // Moodle v2.2.0 release upgrade line
    // Put any upgrade step following this

    return true;
}
