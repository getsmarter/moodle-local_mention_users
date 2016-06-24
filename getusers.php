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

		$sql = "
			SELECT DISTINCT
				ue.userid,
				e.courseid,
				u.firstname,
				u.lastname,
				u.username,
				r.shortname
			FROM {user_enrolments} ue
			JOIN {enrol} e ON (e.id = ue.enrolid)
			JOIN {user} u ON (ue.userid = u.id)
			JOIN {role_assignments} ra ON (u.id = ra.userid)
			JOIN {role} r ON (ra.roleid = r.id)
			WHERE e.courseid = ?
			AND r.shortname IN ('student', 'coursecoach', 'headtutor', 'tutor')
			ORDER BY firstname
			;";

		$users = $DB->get_records_sql($sql, array($course_id));

		$data = array();
		foreach ($users as $user) {
			array_push($data, $user->firstname . ' ' . $user->lastname, $user->userid);
		}

		$post = json_encode($data);
		$proba = json_encode($data, JSON_FORCE_OBJECT);

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
