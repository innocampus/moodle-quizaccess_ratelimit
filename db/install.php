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
 * Install script for plugin.
 *
 * @package    quizaccess_ratelimit
 * @copyright  2021 Martin Gauk, TU Berlin <gauk@math.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Custom code to be run on installing the plugin.
 * @return bool Result.
 */
function xmldb_quizaccess_ratelimit_install(): bool {
    global $DB;

    if ($DB->get_dbfamily() != 'postgres') {
        echo "This plugin only supports PostgreSQL! Please uninstall this plugin immediately.";
        return false;
    }

    if ($DB->want_read_slave()) {
        echo "This plugin does not support read-only database slaves. You may add the table " .
            "quizaccess_ratelimit to exclude_tables in your config. Otherwise, please uninstall " .
            "this plugin immediately.";
        return false;
    }

    quizaccess_ratelimit\manager::init_counter();
    return true;
}
