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


namespace quizaccess_ratelimit;

use advanced_testcase;
use dml_exception;


/**
 * Unit tests for the quizaccess_ratelimit plugin.
 *
 * @package    quizaccess_ratelimit
 * @copyright  2021 Lars Bonczek, TU Berlin <gauk@math.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      quizaccess_ratelimit
 */
class manager_test extends advanced_testcase {

    /**
     * Overwrites the `now` database function to return a specified timestamp.
     * Since `manager::get_seconds_to_wait` will be tested, and it calls the database's `now` function,
     * this makes testing specific wait-intervals much easier.
     *
     * @param int $time The desired timestamp to be returned by the `now` function.
     * @throws dml_exception
     */
    private function set_db_time(int $time) {
        global $DB;
        $DB->execute("CREATE SCHEMA IF NOT EXISTS override");
        $sql = "CREATE OR REPLACE FUNCTION override.now()
                          RETURNS timestamptz AS \$func$
                           SELECT to_timestamp($time) \$func$ language sql STABLE";
        $DB->execute($sql);
        $DB->execute("SET search_path = override, pg_catalog, public");
    }

    /**
     * Tests delay with extreme timout.
     *
     * @covers \quizaccess_ratelimit\manager::get_seconds_to_wait
     * @throws dml_exception
     */
    public function test_get_seconds_to_wait_extreme_timeout() {
        $this->resetAfterTest();

        $timeout = 10000000; // Ten thousand seconds.
        set_config('ms_between_attempts', $timeout, 'quizaccess_ratelimit');

        $time = 1614176234;
        $this->set_db_time($time);

        $this->assertEquals(0, manager::get_seconds_to_wait());

        $time += 9999;
        $this->set_db_time($time);

        $this->assertEquals(1, manager::get_seconds_to_wait());

        $time += 10001;
        $this->set_db_time($time);

        $this->assertEquals(0, manager::get_seconds_to_wait());

    }

    /**
     * Tests delay with large timout.
     *
     * @covers \quizaccess_ratelimit\manager::get_seconds_to_wait
     * @throws dml_exception
     */
    public function test_get_seconds_to_wait_high_timeout() {
        global $DB;
        $this->resetAfterTest();

        $timeout = 10001; // Just over ten seconds (to prevent decimal truncating problems).
        set_config('ms_between_attempts', $timeout, 'quizaccess_ratelimit');

        $time = 1614176234;
        $this->set_db_time($time);

        $this->assertEquals(0, manager::get_seconds_to_wait());
        $this->assertEquals(10, manager::get_seconds_to_wait());
        $this->assertEquals(20, manager::get_seconds_to_wait());

        $this->assertEquals(3, $DB->get_field('quizaccess_ratelimit', 'counter', []));

        $this->set_db_time(++$time);

        $this->assertEquals(29, manager::get_seconds_to_wait());

        $time += 38;
        $this->set_db_time($time);

        $this->assertEquals(1, manager::get_seconds_to_wait());

        $time += 11;
        $this->set_db_time($time);

        $this->assertEquals(0, manager::get_seconds_to_wait());
    }

    /**
     * Tests delay with low timout.
     *
     * @covers \quizaccess_ratelimit\manager::get_seconds_to_wait
     * @throws dml_exception
     */
    public function test_get_seconds_to_wait_low_timeout() {
        global $DB;
        $this->resetAfterTest();

        $timeout = 500; // Half a second.
        set_config('ms_between_attempts', $timeout, 'quizaccess_ratelimit');

        $time = 1614176234;
        $this->set_db_time($time);

        $this->assertEquals(0, manager::get_seconds_to_wait());
        $this->assertEquals(0, manager::get_seconds_to_wait());
        $this->assertEquals(1, manager::get_seconds_to_wait());
        $this->assertEquals(1, manager::get_seconds_to_wait());

        $this->assertEquals(4, $DB->get_field('quizaccess_ratelimit', 'counter', []));

        $this->set_db_time(++$time);

        $this->assertEquals(1, manager::get_seconds_to_wait());

        $this->assertEquals(3, $DB->get_field('quizaccess_ratelimit', 'counter', []));

        $this->set_db_time(++$time);

        $this->assertEquals(0, manager::get_seconds_to_wait());

        $this->assertEquals(2, $DB->get_field('quizaccess_ratelimit', 'counter', []));
    }

    /**
     * Tests delay with no timout.
     *
     * @covers \quizaccess_ratelimit\manager::get_seconds_to_wait
     * @throws dml_exception
     */
    public function test_get_seconds_to_wait_no_timeout() {
        global $DB;
        $this->resetAfterTest();

        $timeout = 0; // No timeout.
        set_config('ms_between_attempts', $timeout, 'quizaccess_ratelimit');

        $this->assertEquals(0, manager::get_seconds_to_wait());

        $this->assertEquals(0, $DB->get_field('quizaccess_ratelimit', 'counter', []));
    }
}
