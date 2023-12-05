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
 * Steps definitions related with accessrule ratelimit
 *
 * @package    quizaccess_ratelimit
 * @copyright  2023 Felix Di Lenarda, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../../lib/behat/behat_base.php');

class behat_quizaccess_ratelimit extends behat_base {

    /**
     * Resets the counter and timemodified fields in quizaccess_ratelimit table.
     *
     * @Given /^I reset the quiz rate limit counters$/
     */
    public function i_reset_the_quiz_rate_limit_counters() {
        global $DB;

        // SQL to reset counter and timemodified fields
        $sql = "UPDATE {quizaccess_ratelimit} SET counter = 0, timemodified = 0";
        $DB->execute($sql);
    }
}