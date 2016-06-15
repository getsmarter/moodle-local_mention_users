<?php

/**
 * Forum Actions
 * Adds one action to the database
 *
 * @package   forum_actions
 * @copyright 2014 Moodle Pty Ltd (http://moodle.com)
 * @author    Mikhail Janowski <mikhail@getsmarter.co.za>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/local/mention_users/lib.php');

$action = required_param('action', PARAM_TEXT); // Action
$reply_id = required_param('reply', PARAM_INT); // Reply ID

$result = new stdClass();
$result->result = false; // set in case uncaught error happens
$result->content = 'Unknown error';

//Only allow to add action if logged in
if(isloggedin()) {

	if($action == 'tribute') {

		global $DB;
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
		$post = json_encode($post);

		// populatePostActions($post);

		$result->result = true;
		$result->content = $post;
	}
	else {
		$result->result = false;
		$result->content = 'Invalid action';
	}
}

header('Content-type: application/json');
echo json_encode($result);
		error_log('--------------------------ezek-------------------');
		error_log(print_r($post,1));
//echo '<pre>'.print_r($actionid, true).'</pre>';

