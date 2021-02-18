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
 * External forum API
 *
 * @package    local_mention_users
 * @copyright  2012 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot.'/local/mention_users/lib.php');

/**
 * Class local_mention_users_external
 */
class local_mention_users_external extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function mention_get_users_parameters()
    {
        return new external_function_parameters(array(
            'action' => new external_value(PARAM_TEXT, 'The type of action'),
            'reply' => new external_value(PARAM_INT, 'The reply id', VALUE_DEFAULT, 0),
            'forum' => new external_value(PARAM_INT, 'The forum id', VALUE_DEFAULT, 0),
            'group' => new external_value(PARAM_INT, 'The group id', VALUE_DEFAULT, 0),
            'newdiscussion' => new external_value(PARAM_BOOL, 'Identify if new discussion', VALUE_DEFAULT, 0),
            'advancedforum' => new external_value(PARAM_INT, 'Identify if forum or hsuforum', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Retrieve mentionable users based on capability
     *
     * @param String $action - the type of action
     * @param Int $replyid - the reply id.
     * @param Int $forumid - the forum id.
     * @param Int $groupid - the group id.
     * @param Bool $newdiscussion - identify if this is a new discussion or not.
     * @param Int $advancedforum - identify if this is an advanced forum or not.
     * @return Object - List of mentionable users
     * @throws invalid_parameter_exception
     */
    public static function mention_get_users($action, $replyid, $forumid, $groupid, $newdiscussion, $advancedforum)
    {
        global $DB;

        $params = self::validate_parameters(self::mention_get_users_parameters(), array(
            'action' => $action,
            'reply' =>  $replyid,
            'forum' => $forumid,
            'group' => $groupid,
            'newdiscussion' => $newdiscussion,
            'advancedforum' => $advancedforum,
        ));

        $action = $params['action'];
        $replyid = $params['reply'];
        $forumid = $params['forum'];
        $groupid = $params['group'];
        $newdiscussion = $params['newdiscussion'];
        $advancedforum = $params['advancedforum'];

        $groupingid = '';
        $result = new stdClass();
        $result->result = false;
        $result->content = 'Unknown error';

        try {
            if ($action == 'tribute') {
                if ($replyid != 0 && $forumid == 0) {
                    if ($advancedforum == 0) {
                        $forumdiscussionsid = $DB->get_field('forum_posts', 'discussion', array("id"=>$replyid));
                        $courseid = $DB->get_field('forum_discussions', "course", array("id"=>$forumdiscussionsid));
                        $forumid = $DB->get_field('forum_discussions', "forum", array("id"=>$forumdiscussionsid));
                        $groupid = $DB->get_field('forum_discussions', "groupid", array("id"=>$forumdiscussionsid));
                    } elseif ($advancedforum == 1) {
                        $forumdiscussionsid = $DB->get_field('hsuforum_posts', 'discussion', array("id"=>$replyid));
                        $courseid = $DB->get_field('hsuforum_discussions', "course", array("id"=>$forumdiscussionsid));
                        $forumid = $DB->get_field('hsuforum_discussions', "forum", array("id"=>$forumdiscussionsid));
                        $groupid = $DB->get_field('hsuforum_discussions', "groupid", array("id"=>$forumdiscussionsid));
                    }
                } elseif ($forumid != 0 && $replyid == 0) {
                    if ($advancedforum == 0) {
                        $courseid = $DB->get_field('forum', "course", array("id"=>$forumid));
                    } elseif ($advancedforum == 1) {
                        $courseid = $DB->get_field('hsuforum', "course", array("id"=>$forumid));
                    }
                }

                if ($advancedforum == 0) {
                    $moduleid = $DB->get_field('modules', 'id', array("name"=>'forum'));
                } elseif ($advancedforum == 1) {
                    $moduleid = $DB->get_field('modules', 'id', array("name"=>'hsuforum'));
                }

                $availability = $DB->get_field('course_modules', "availability", array("course"=>$courseid, "instance"=>$forumid, 'module'=>$moduleid));

                if ($availability) {
                    $restrictions = json_decode($availability)->c;

                    if (isset($restrictions)) {
                        foreach ($restrictions as $restriction) {
                            if ($restriction->type == 'group') {
                                $groupid = $restriction->id;
                            } elseif ($restriction->type == 'grouping') {
                                $groupingid = $restriction->id;
                            }
                        }
                    }
                }

                $contextid = $DB->get_field(
                    'context',
                    'id',
                    array(
                        'instanceid' => $courseid,
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
                    array($contextid, $courseid)
                );

                if ($groupid <= 0 && $groupingid == 0) {
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

                    $users = $DB->get_records_sql($sql, array($contextid, $courseid));
                } elseif ($groupid > 0 && $groupingid == 0) {
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

                    $users = $DB->get_records_sql($sql, array($contextid, $courseid, $groupid));
                } elseif ($groupingid != 0 && $groupid >= 0) {
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

                    $users = $DB->get_records_sql($sql, array($contextid, $courseid, $groupingid, $groupid));
                } elseif ($groupingid != 0 && $groupid <= 0) {
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

                    $users = $DB->get_records_sql($sql, array($contextid, $courseid, $groupingid));
                }

                $context = \context_course::instance($courseid);
                $users = array_merge($users, $course_staff);
                $allUserIds = "";

                if (!empty($users) && !$newdiscussion) {

                    foreach($users AS $user) {
                        if (mentions_check_capability($context, $user)) {
                            $allUserIds .= $user->userid . ",";
                        }
                    }

                    $allUserIds = rtrim($allUserIds, ",");
                }

                if (isset($users)) {
                    $data = array();

                    if(!empty($allUserIds) && has_capability('local/getsmarter:mention_all', $context, $USER->id)) {
                        array_push($data, 'all', $allUserIds);
                    }

                    foreach ($users as $user) {
                        if (mentions_check_capability($context, $user)) {
                            array_push($data, $user->firstname . ' ' . $user->lastname, $user->userid);
                        }
                    }

                    $post = json_encode($data);

                    $result->result = true;
                    $result->courseid = $courseid;
                    $result->content = $post;
                }
            }
            else {
                $result->result = false;
                $result->courseid = 0;
                $result->content = 'Invalid action';
            }

        } catch (Exception $e) {
            error_log('local_mention_users_mention_get_users: ' . $e);
        }

        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.5
     */
    public static function mention_get_users_returns()
    {
        return new external_single_structure(
            array(
                'result' => new external_value(PARAM_BOOL, 'The allowed mentionable users'),
                'courseid' => new external_value(PARAM_INT, 'The allowed mentionable users'),
                'content' => new external_value(PARAM_TEXT, 'The allowed mentionable users')
            )
        );
    }
}
