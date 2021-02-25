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
 * Unit tests for the quizaccess_ratelimit plugin.
 *
 * @package    quizaccess_ratelimit
 * @copyright  2021 Lars Bonczek, TU Berlin <gauk@math.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/accessrule/ratelimit/rule.php');


/**
 * Unit tests for the quizaccess_ratelimit plugin.
 *
 * @package    quizaccess_ratelimit
 * @copyright  2021 Lars Bonczek, TU Berlin <gauk@math.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      quizaccess_ratelimit
 */
class quizaccess_ratelimit_testcase extends advanced_testcase {

    private function set_db_time($time) {
        global $DB;

        $this->assertTrue(is_int($time));

        $DB->execute('CREATE SCHEMA IF NOT EXISTS override');

        $sql = 'CREATE OR REPLACE FUNCTION override.now()
                  RETURNS timestamptz AS
                $func$
                SELECT to_timestamp('.$time.')
                $func$ language sql STABLE';
        $DB->execute($sql);

        $DB->execute('SET search_path = override, pg_catalog, public');
    }

    public function test_get_seconds_to_wait_extreme_timeout() {
        global $DB;
        $this->resetAfterTest();

        $timeout = 10000000; // Ten thousand seconds.
        set_config('ms_between_attempts', $timeout, 'quizaccess_ratelimit');

        $time = 1614176234;
        $this->set_db_time($time);

        $this->assertEquals(0, \quizaccess_ratelimit\manager::get_seconds_to_wait());

        $time += 9999;
        $this->set_db_time($time);

        $this->assertEquals(1, \quizaccess_ratelimit\manager::get_seconds_to_wait());

        $time += 10001;
        $this->set_db_time($time);

        $this->assertEquals(0, \quizaccess_ratelimit\manager::get_seconds_to_wait());

    }

    public function test_get_seconds_to_wait_high_timeout() {
        global $DB;
        $this->resetAfterTest();

        $timeout = 10001; // Just over ten seconds (to prevent decimal truncating problems).
        set_config('ms_between_attempts', $timeout, 'quizaccess_ratelimit');

        $time = 1614176234;
        $this->set_db_time($time);

        $this->assertEquals(0, \quizaccess_ratelimit\manager::get_seconds_to_wait());
        $this->assertEquals(10, \quizaccess_ratelimit\manager::get_seconds_to_wait());
        $this->assertEquals(20, \quizaccess_ratelimit\manager::get_seconds_to_wait());

        $this->assertEquals(3, $DB->get_field('quizaccess_ratelimit', 'counter', []));

        $this->set_db_time(++$time);

        $this->assertEquals(29, \quizaccess_ratelimit\manager::get_seconds_to_wait());

        $time += 38;
        $this->set_db_time($time);

        $this->assertEquals(1, \quizaccess_ratelimit\manager::get_seconds_to_wait());

        $time += 11;
        $this->set_db_time($time);

        $this->assertEquals(0, \quizaccess_ratelimit\manager::get_seconds_to_wait());
    }

    public function test_get_seconds_to_wait_low_timeout() {
        global $DB;
        $this->resetAfterTest();

        $timeout = 500; // Half a second.
        set_config('ms_between_attempts', $timeout, 'quizaccess_ratelimit');

        $time = 1614176234;
        $this->set_db_time($time);

        $this->assertEquals(0, \quizaccess_ratelimit\manager::get_seconds_to_wait());
        $this->assertEquals(0, \quizaccess_ratelimit\manager::get_seconds_to_wait());
        $this->assertEquals(1, \quizaccess_ratelimit\manager::get_seconds_to_wait());
        $this->assertEquals(1, \quizaccess_ratelimit\manager::get_seconds_to_wait());

        $this->assertEquals(4, $DB->get_field('quizaccess_ratelimit', 'counter', []));

        $this->set_db_time(++$time);

        $this->assertEquals(1, \quizaccess_ratelimit\manager::get_seconds_to_wait());

        $this->assertEquals(3, $DB->get_field('quizaccess_ratelimit', 'counter', []));

        $this->set_db_time(++$time);

        $this->assertEquals(0, \quizaccess_ratelimit\manager::get_seconds_to_wait());

        $this->assertEquals(2, $DB->get_field('quizaccess_ratelimit', 'counter', []));
    }

    public function test_get_seconds_to_wait_no_timeout() {
        global $DB;
        $this->resetAfterTest();

        $timeout = 0; // No timeout.
        set_config('ms_between_attempts', $timeout, 'quizaccess_ratelimit');

        $this->assertEquals(0, \quizaccess_ratelimit\manager::get_seconds_to_wait());

        $this->assertEquals(0, $DB->get_field('quizaccess_ratelimit', 'counter', []));
    }
}
