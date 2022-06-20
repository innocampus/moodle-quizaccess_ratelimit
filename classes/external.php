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
 * External class to implement the rate limiting.
 *
 * @package    quizaccess_ratelimit
 * @copyright  2021 Martin Gauk, TU Berlin <gauk@math.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_ratelimit;

use coding_exception;
use context_system;
use dml_exception;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use restricted_context_exception;

/**
 * External function to implement the rate limiting.
 *
 * @copyright  2021 Martin Gauk, TU Berlin <gauk@math.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * Parameters for the get_waiting_time external function.
     * @return external_function_parameters
     */
    public static function get_waiting_time_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Return values of the get_waiting_time external function.
     * @return external_single_structure
     */
    public static function get_waiting_time_returns(): external_single_structure {
        return new external_single_structure([
            'seconds' => new external_value(PARAM_INT, 'no. of seconds to wait'),
            'message' => new external_value(PARAM_RAW, 'message to display'),
        ]);
    }

    /**
     * Checks how long the user has to wait (if at all) before being permitted to start a quiz attempt.
     * Returns the wait time in seconds along with a message to display to the user in case he is forced to wait.
     *
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function get_waiting_time(): array {
        global $SESSION;

        // Ensure user is logged in.
        $context = context_system::instance();
        self::validate_context($context);

        $message = get_string('message', 'quizaccess_ratelimit');

        $curtime = time();
        if (isset($SESSION->quizaccess_ratelimit_time) && $SESSION->quizaccess_ratelimit_time >= $curtime) {
            $seconds = $SESSION->quizaccess_ratelimit_time - $curtime;
        } else {
            $seconds = manager::get_seconds_to_wait();

            // Save the time to prevent user from taking more space than needed in the leaky bucket.
            $SESSION->quizaccess_ratelimit_time = $curtime + $seconds;
        }

        return [
            'seconds' => $seconds,
            'message' => $message, // Pass message here, so client doesn't have to do another request.
        ];
    }
}
