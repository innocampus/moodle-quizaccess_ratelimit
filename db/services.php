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
 * Define services for plugin.
 *
 * @package    quizaccess_ratelimit
 * @copyright  2021 Martin Gauk, TU Berlin <gauk@math.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'quizaccess_ratelimit_get_waiting_time' => [
        'classname' => 'quizaccess_ratelimit\external',
        'methodname' => 'get_waiting_time',
        'description' => 'get waiting time before starting a new quiz attempt',
        'type' => 'write',
        'ajax' => true,
    ],
];
