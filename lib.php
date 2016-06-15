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

if(isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'mod/forum/post.php')) {
	global $PAGE;
	$PAGE->requires->css('/local/mention_users/tribute/tribute.css');
	$PAGE->requires->js_call_amd('local_mention_users/mention_users', 'init');

	global $DB;
	$reply_id = required_param('reply', PARAM_INT); // Forum post ID
	$forum_discussions_id = $DB->get_field('forum_posts', 'discussion', array("id"=>$reply_id));
	$course_id = $DB->get_field('forum_discussions', "course", array("id"=>$forum_discussions_id));

	// $users = get_enrolled_users($context);

	$context = context_course::instance($course_id);
	$role_id = $DB->get_field('role', 'id', array('shortname' => 'student'));
    $users = get_role_users($role_id, $context);


     $data = array();
    //////////////////////////////////
    foreach ($users as $user) {
    	$user_data = array("key" => $user->firstname . ' ' . $user->lastname, "value" => $user->id);
    	// array_push($data, "key" => $user->firstname . ' ' . $user->lastname, "value" => $user->id);
    	$data[] = $user_data;

    }

    	$post = array('values' => $data);
    	// error_log(print_r(json_encode($data),1));
    	error_log(print_r(json_encode($post),1));
    // error_log(print_r($data,1));
    ///////////////////////////////////


    $json = json_encode($users);

error_log("geciiiiiiiasdjlfliasdjfilj");
error_log(print_r($reply_id,1));
error_log(print_r($forum_discussions_id,1));
error_log(print_r($course_id,1));
// error_log(print_r($users,1));
// error_log(print_r($json,1));
error_log("replyiddddd");
}
