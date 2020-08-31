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


defined('MOODLE_INTERNAL') || die();

function xmldb_local_salesforce_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();


 if ($oldversion < 2016110216) {

        // Define table quiz_sections to be created.
        $table = new xmldb_table('salesforce_new_user');

        // Adding fields to table quiz_sections.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('password', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null);
        $table->add_field('datecreated', XMLDB_TYPE_INTEGER, '10', 0, null, null, null);
        $table->add_field('done', XMLDB_TYPE_INTEGER, '2', 0, null, null, null);

        // Adding keys to table quiz_sections.
        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for quiz_sections.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Quiz savepoint reached.
        upgrade_plugin_savepoint(true, 2016110216, 'salesforce');
    }

    // Moodle v3.1.0 release upgrade line.
    // Put any upgrade step following this.

    return true;
    
}
    