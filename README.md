# moodle-quizaccess_ratelimit

Moodle quizaccess plugin that limits the number of users per second who are able to start a new quiz attempt.

It aims to solve the problem of high server loads, which occur when too many students attempt to start a quiz simultaneously.

## Requirements

This plugin requires Moodle 3.9+ and a PostgreSQL database.

## Installation

Install the plugin by copying the code to
`mod/quiz/accessrule/ratelimit`.

Example:

    git clone https://github.com/innocampus/moodle-quizaccess_ratelimit.git mod/quiz/accessrule/ratelimit

See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins

If you have read-only database slaves, you must add the table quizaccess_ratelimit to exclude_tables in your config.

## Usage

The administrator has to define the minimum time between two attempts in milliseconds. The reciprocal of this value
specifies how many users per second are allowed to start a new quiz attempt.

Users will have to wait, when too many users want to start an attempt at the same time.
The maximum waiting time is determined by the closing time and the time limit of the quiz
(in order to guarantee that no user is disadvantaged as a consequence of a delay by the plugin).
This means there can be still situations when too many users are starting an attempt.

The rate limiting affects only quizzes that have a preflight check form (e.g. when a time limit or a password was defined).
Users with the capability `quizaccess/ratelimit:exempt` are exempt from the rate limiting (only administrators by default).

## Comparison with quizaccess_delayed

There is another plugin ([quizaccess_delayed](https://github.com/juacas/quizaccess_delayed))
that tries to reduce the server load at the beginning of a quiz.

* quizaccess_delayed assigns an individual quiz opening time to each user who is enrolled in the course. The user
might start the attempt at this time or later.
* This plugin uses the leaky bucket algorithm to implement the rate limiting and it is checked at the moment
when the user wants to start an attempt. The users will not notice any delay as long as the rate limit is not exceeded. 
* The rate limiting of this plugin applies to all users in all quizzes, while quizaccess_delayed only enforces
the entry rate limit per quiz. 
* quizaccess_delayed is compatible with all databases that Moodle supports, while this plugin only supports PostgreSQL.
* quizaccess_delayed enforces the individual quiz opening time on the server side, while this plugin does not
enforce the rate limiting on the server side (the users could manipulate their browsers to skip the delay).
