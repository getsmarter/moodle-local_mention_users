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

global $CFG, $PAGE, $USER;

require('../../config.php');
require_once($CFG->dirroot.'/local/mention_users/lib.php');

$course_id = optional_param('course', 0, PARAM_INT); // Course ID
$action = required_param('action', PARAM_TEXT); // Action
$reply_id = optional_param('reply', 0, PARAM_INT); // Reply ID
$forum_id = optional_param('forum', 0, PARAM_INT); // Forum ID
$group_id = optional_param('group', 0, PARAM_INT); // Group ID
$grouping_id = '';
$advancedforum = optional_param('advancedforum', 0, PARAM_INT);

$result = new stdClass();
$result->result = false; // set in case uncaught error happens
$result->content = 'Unknown error';

/**
 * @param context_course $context
 * @param $user
 * @return bool
 */
function check_capability($context, $user)
{
    global $result, $USER;

    try {
        if (has_capability('local/getsmarter:mention_' . $user->shortname, $context, $USER->id)) {
            return true;
        }
    } catch (Exception $e) {
        error_log($e);
        $result->result = false;
        $result->content = $e;

        header('Content-type: application/json');
        echo json_encode($result);
        die();
    }

    return false;
}

//Only allow to add action if logged in
if(isloggedin()) {

	if($action == 'tribute') {

		global $DB;
		if ($reply_id != 0 && $forum_id == 0) {
			if ($advancedforum == 0) {
				$forum_discussions_id = $DB->get_field('forum_posts', 'discussion', array("id"=>$reply_id));
				$course_id = $DB->get_field('forum_discussions', "course", array("id"=>$forum_discussions_id));
				$forum_id = $DB->get_field('forum_discussions', "forum", array("id"=>$forum_discussions_id));
				$group_id = $DB->get_field('forum_discussions', "groupid", array("id"=>$forum_discussions_id));
			} elseif ($advancedforum == 1) {
				$forum_discussions_id = $DB->get_field('hsuforum_posts', 'discussion', array("id"=>$reply_id));
				$course_id = $DB->get_field('hsuforum_discussions', "course", array("id"=>$forum_discussions_id));
				$forum_id = $DB->get_field('hsuforum_discussions', "forum", array("id"=>$forum_discussions_id));
				$group_id = $DB->get_field('hsuforum_discussions', "groupid", array("id"=>$forum_discussions_id));
			}
		} elseif ($forum_id != 0 && $reply_id == 0) {
			if ($advancedforum == 0) {
				$course_id = $DB->get_field('forum', "course", array("id"=>$forum_id));
			} elseif ($advancedforum == 1) {
				$course_id = $DB->get_field('hsuforum', "course", array("id"=>$forum_id));
			}
		}

		if ($advancedforum == 0) {
			$moduleid = $DB->get_field('modules', 'id', array("name"=>'forum'));
		} elseif ($advancedforum == 1) {
			$moduleid = $DB->get_field('modules', 'id', array("name"=>'hsuforum'));
		}

		$availability = $DB->get_field('course_modules', "availability", array("course"=>$course_id, "instance"=>$forum_id, 'module'=>$moduleid));

		if ($availability) {
			$restrictions = json_decode($availability)->c;

			if (isset($restrictions)) {
				foreach ($restrictions as $restriction) {
					if ($restriction->type == 'group') {
						$group_id = $restriction->id;
					} elseif ($restriction->type == 'grouping') {
						$grouping_id = $restriction->id;
					}
				}
			}
		}

		$context_id = $DB->get_field(
			'context',
			'id',
			array(
				'instanceid' => $course_id,
				'contextlevel' => 50,
			)
		);

		$course_staff = $DB->get_records_sql(
			"SELECT DISTINCT
				ue.userid,
				e.courseid,
				u.firstname,
				u.lastname,
				u.username,
				r.shortname
			FROM {user_enrolments} ue
			JOIN {enrol} e ON (e.id = ue.enrolid)
			JOIN {user} u ON (ue.userid = u.id)
			JOIN {role_assignments} ra ON (u.id = ra.userid AND ra.contextid = ?)
			JOIN {role} r ON (ra.roleid = r.id)
			WHERE e.courseid = ?
			AND r.shortname != 'student'
			ORDER BY firstname",
			array($context_id, $course_id)
		);

		//var_dump($course_staff);

		if ($group_id <= 0 && $grouping_id == 0) {
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
				JOIN {role_assignments} ra ON (u.id = ra.userid AND ra.contextid = ?)
				JOIN {role} r ON (ra.roleid = r.id)
				WHERE e.courseid = ?
				AND r.shortname = 'student'
				AND ue.status != 1
				ORDER BY firstname
				;";

			$users = $DB->get_records_sql($sql, array($context_id, $course_id));
		} elseif ($group_id > 0 && $grouping_id == 0) {
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
				JOIN {role_assignments} ra ON (u.id = ra.userid AND ra.contextid = ?)
				JOIN {role} r ON (ra.roleid = r.id)
				JOIN {groups} g ON (g.courseid = e.courseid)
				JOIN {groups_members} gm ON (ue.userid = gm.userid ) AND (gm.groupid = g.id)
				WHERE e.courseid = ?
				AND g.id = ?
				AND r.shortname = 'student'
				AND ue.status != 1
				ORDER BY firstname
				;";

			$users = $DB->get_records_sql($sql, array($context_id, $course_id, $group_id));
		} elseif ($grouping_id != 0 && $group_id >= 0) {
			// users should only be able to mention users in their group
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
				JOIN {role_assignments} ra ON (u.id = ra.userid AND ra.contextid = ?)
				JOIN {role} r ON (ra.roleid = r.id)
				JOIN {groups_members} gm ON (u.id = gm.userid)
				JOIN {groupings_groups} gg ON (gm.groupid = gg.groupid)
				WHERE e.courseid = ?
				AND gg.groupingid = ?
				AND gm.groupid = ?
				AND r.shortname = 'student'
				AND ue.status != 1
				ORDER BY firstname
				;";

			$users = $DB->get_records_sql($sql, array($context_id, $course_id, $grouping_id, $group_id));
		} elseif ($grouping_id != 0 && $group_id <= 0) {
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
				JOIN {role_assignments} ra ON (u.id = ra.userid AND ra.contextid = ?)
				JOIN {role} r ON (ra.roleid = r.id)
				JOIN {groups_members} gm ON (u.id = gm.userid)
				JOIN {groupings_groups} gg ON (gm.groupid = gg.groupid)
				WHERE e.courseid = ?
				AND gg.groupingid = ?
				AND r.shortname = 'student'
				AND ue.status != 1
				ORDER BY firstname
				;";

			$users = $DB->get_records_sql($sql, array($context_id, $course_id, $grouping_id));
		}

		$users = array_merge($users, $course_staff);
		$context = \context_course::instance($course_id);

		if (isset($users)) {
			$data = array();
			foreach ($users as $user) {
                if(check_capability($context, $user)) {
                    array_push($data, $user->firstname . ' ' . $user->lastname, $user->userid);
                }
			}

			$post = json_encode($data);

			$result->result = true;
			$result->courseid = $course_id;
			$result->content = $post;
		}
	}
	else {
		$result->result = false;
		$result->content = 'Invalid action';
	}
}

header('Content-type: application/json');
echo json_encode($result);
