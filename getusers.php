<?php

/**
 * Forum Actions
 * Adds one action to the database
 *
 * @package   mention_users
 * @copyright 2016
 * @author    Norbert Ritter
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/local/mention_users/lib.php');

$action = required_param('action', PARAM_TEXT); // Action
$reply_id = optional_param('reply', 0, PARAM_INT); // Reply ID
$forum_id = optional_param('forum', 0, PARAM_INT); // Forum ID
$group_id = optional_param('group', 0, PARAM_INT); // Group ID

$result = new stdClass();
$result->result = false; // set in case uncaught error happens
$result->content = 'Unknown error';

//Only allow to add action if logged in
if(isloggedin()) {

	if($action == 'tribute') {

		global $DB;
		if ($reply_id != 0 && $forum_id == 0) {
			$forum_discussions_id = $DB->get_field('forum_posts', 'discussion', array("id"=>$reply_id));
			$course_id = $DB->get_field('forum_discussions', "course", array("id"=>$forum_discussions_id));
			$forum_id = $DB->get_field('forum_discussions', "forum", array("id"=>$forum_discussions_id));
		} elseif ($forum_id != 0 && $reply_id == 0) {
			$course_id = $DB->get_field('forum', "course", array("id"=>$forum_id));
		}

		$availability = $DB->get_field('course_modules', "availability", array("course"=>$course_id, "instance"=>$forum_id));
		$restrictions = json_decode($availability)->c;

		foreach ($restrictions as $restriction) {
			error_log(print_r($restriction,1));
			if ($restriction->type == 'group') {
				$group_id = $restriction->id;
			} elseif ($restriction->type == 'grouping') {
				$grouping_id = $restriction->id;
			}
		}

		if ($group_id == 0 && $grouping_id == 0) {
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
		} elseif ($group_id != 0 && $grouping_id == 0) {
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
				JOIN {groups} g ON (g.courseid = e.courseid)
				JOIN {groups_members} gm ON (ue.userid = gm.userid ) AND (gm.groupid = g.id)
				WHERE e.courseid = ?
				AND g.id = ?
				AND r.shortname IN ('student', 'coursecoach', 'headtutor', 'tutor')
				ORDER BY firstname
				;";

			$users = $DB->get_records_sql($sql, array($course_id, $group_id));
		} elseif ($grouping_id != 0 && $group_id == 0) {
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
				JOIN {groups_members} gm ON (u.id = gm.userid)
				JOIN {groupings_groups} gg ON (gm.groupid = gg.groupid)
				WHERE e.courseid = ?
				AND gg.groupingid = ?
				AND r.shortname IN ('student', 'coursecoach', 'headtutor', 'tutor')
				ORDER BY firstname
				;";

			$users = $DB->get_records_sql($sql, array($course_id, $grouping_id));
		}

		$data = array();
		foreach ($users as $user) {
			array_push($data, $user->firstname . ' ' . $user->lastname, $user->userid);
		}

		$post = json_encode($data);

		$result->result = true;
		$result->courseid = $course_id;
		$result->content = $post;
	}
	else {
		$result->result = false;
		$result->content = 'Invalid action';
	}
}

header('Content-type: application/json');
echo json_encode($result);
