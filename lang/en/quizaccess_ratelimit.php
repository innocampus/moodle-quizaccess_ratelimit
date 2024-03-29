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
 * Strings for plugin.
 *
 * @package    quizaccess_ratelimit
 * @copyright  2021 Martin Gauk, TU Berlin <gauk@math.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Rate limiting access rule';
$string['privacy:metadata'] = 'The rate limiting access rule plugin does not store any personal data.';
$string['ratelimit:exempt'] = 'Bypass the quiz rate limiting';
$string['setting:ms_between_attempts'] = 'Time between attempts (ms)';
$string['setting:ms_between_attempts_desc'] = 'Minimum time between two new quiz attempts in milliseconds.';
$string['message'] = 'The server is processing many requests at the moment. Please wait until your quiz starts in a few seconds.';
