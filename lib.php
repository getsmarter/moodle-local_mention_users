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
 * @package    local_mention_users
 * @copyright  2016 GetSmarter {@link http://www.getsmarter.co.za}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (get_config('local_mention_users', 'enabletracking') == 1) {
    if(isset($_SERVER['REQUEST_URI']) && (strpos($_SERVER['REQUEST_URI'], 'mod/forum/post.php') || strpos($_SERVER['REQUEST_URI'], 'mod/hsuforum/'))) {
        global $PAGE;
        $PAGE->requires->js_call_amd('local_mention_users/mention_users', 'init');
    }
}

// This function is included to force moodle to run the local lib.php on every page.
function local_mention_users_extend_navigation($param) {}

/**
 * @param context_course $context - The course context
 * @param $user - The user
 * @return bool - If the user can be mentioned
 */
function mentions_check_capability($context, $user)
{
    global $USER;

    try {
        return has_capability('local/getsmarter:mention_' . $user->shortname, $context, $USER->id);
    } catch (Exception $e) {
        error_log('local_mention_users_mentions_check_capability: ' . $e);
        return false;
    }
}
