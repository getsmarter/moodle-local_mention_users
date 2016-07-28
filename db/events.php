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
 * Segment
 *
 * @package    local_segment
 * @copyright  2014 GetSmarter {@link http://www.getsmarter.co.za}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//Define an array of event handlers
$observers = array (
    array (
        'eventname' => '\mod_forum\event\assessable_uploaded',
        'callback'  => 'local_mention_users_observer::email_mention',
    ),
    array (
        'eventname' => '\mod_hsuforum\event\assessable_uploaded',
        'callback'  => 'local_mention_users_observer::email_mention_hsu',
    )
);
