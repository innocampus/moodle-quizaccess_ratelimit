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
 * Manager to implement the rate limiting.
 *
 * @package    quizaccess_ratelimit
 * @copyright  2021 Martin Gauk, TU Berlin <gauk@math.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_ratelimit;

use dml_exception;
use stdClass;

/**
 * Manager to implement the rate limiting using the leaky bucket algorithm.
 *
 * @copyright  2021 Martin Gauk, TU Berlin <gauk@math.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /**
     * If there isn't a counter, add one to the db table.
     * @throws dml_exception
     */
    public static function init_counter() {
        global $DB;

        if (!$DB->record_exists('quizaccess_ratelimit', [])) {
            // Add a record that tracks the information needed for the algorithm.
            $record = new stdClass();
            $record->counter = 0;
            $record->timemodified = 0;
            $DB->insert_record('quizaccess_ratelimit', $record);
        }
    }

    /**
     * Number of users that are allowed to start a quiz attempt per second. Returns infinity if there is no limit.
     *
     * @return float
     * @throws dml_exception
     */
    private static function get_rate(): float {
        $msbetweenattempts = get_config('quizaccess_ratelimit', 'ms_between_attempts');
        if ($msbetweenattempts < 1) {
            return INF;
        }
        return 1000. / $msbetweenattempts;
    }

    /**
     * The number of seconds that a user has to wait.
     *
     * Computes the number of seconds by tracking the number of users that want to start a new
     * quiz attempt now using the leaky bucket algorithm.
     *
     * @return int
     * @throws dml_exception
     */
    public static function get_seconds_to_wait(): int {
        global $DB;

        $rate = self::get_rate();

        // Do not access the database if there is no rate limit.
        if (is_infinite($rate)) {
            return 0;
        }

        // Get the number of users that want to start a quiz right now.
        $sql = "UPDATE {quizaccess_ratelimit}
                   SET counter = GREATEST(1, counter + 1 - ((extract(epoch from now())::bigint - timemodified)::real * :rate)),
                       timemodified = extract(epoch from now())::bigint
             RETURNING counter";

        // The function get_field_sql actually expects a SELECT query.
        // However, this query behaves like a SELECT thanks to the RETURNING part.
        // A problem is that this function tries to read data from a slave database node
        // (if there is any), while this query must be executed on the master.
        $number = $DB->get_field_sql($sql, ['rate' => $rate], MUST_EXIST);

        return (int) (($number - 1) / $rate);
    }
}
