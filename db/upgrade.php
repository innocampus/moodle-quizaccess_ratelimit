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
 * Upgrade script for plugin.
 *
 * @package    quizaccess_ratelimit
 * @copyright  2021 Martin Gauk, TU Berlin <gauk@math.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Function to upgrade quizaccess_ratelimit plugin.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool Result.
 * @throws ddl_exception
 * @throws ddl_field_missing_exception
 * @throws ddl_table_missing_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_quizaccess_ratelimit_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2021022403) {
        // Delete old setting.
        unset_config('rate', 'quizaccess_ratelimit');

        $table = new xmldb_table('quizaccess_ratelimit');

        // Drop old integer counter.
        $oldcounter = new xmldb_field('counter');
        if ($dbman->field_exists($table, $oldcounter)) {
            $dbman->drop_field($table, $oldcounter);
        }

        // Create new float counter.
        $newcounter = new xmldb_field('counter', XMLDB_TYPE_FLOAT, 10, null, XMLDB_NOTNULL, null, '0');
        $dbman->add_field($table, $newcounter);

        upgrade_plugin_savepoint(true, 2021022403, 'quizaccess', 'ratelimit');
    }

    return true;
}
