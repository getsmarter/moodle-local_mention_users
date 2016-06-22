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
 * @package    local_mention_users
 * @copyright  2016 Norbert Ritter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/mention_users/lib.php');

class local_mention_users_observer {

  public static function email_mention(\mod_forum\event\assessable_uploaded $event) {
    global $DB;

    $other = (object)$event->other;

    $content = $other->content;
    $discussion_id = $other->discussionid;
    $post_id = $event->objectid;
    $course_id = $event->courseid;

    $course_name = $DB->get_field("course", "fullname", array("id"=>$course_id));
    $course_coach = self::get_course_coach($course_id);

    $id_array = self::parse_id($content);

    self::send_email_to_students($id_array, $course_name, $course_coach);

    $link = $_SERVER['HTTP_HOST'] . '/mod/forum/discuss.php?d=' . $discussion_id . '#p' . $post_id;

    error_log("geeeesdafsadfsadf------- ");
    error_log(print_r($id_array,1));
 }

 public static function parse_id($content) {
    $string_array = explode('userid="',$content);
    $id_array = array();

    for ($x = 1; $x < count($string_array); $x++) {
       $string = $string_array[$x];
       $id = explode('">', $string)[0];
       array_push($id_array, $id);
    }

    return $id_array;
 }

 public static function send_email_to_students($id_array, $course_name, $course_coach) {
  foreach ($id_array as $id) {
    global $DB;
    global $CFG;
    require_once($CFG->libdir.'/moodlelib.php');

    $student = $DB->get_record('user', array('id'=>$id));
    $testuser = $DB->get_record('user', array('id'=>11577));
    error_log(print_r($testuser,1));
    $subject = 'Forum Post - You have been Mentioned in a Forum Post | ' . $course_name;
    $body = 'Hi' . $student->firstname .
            'You have been mentioned in a forum post. Please click the following link to view.
            Regards,
            ';

    $bodyhtml = text_to_html($body, null, false, true);
    error_log("emaileleje-------------------------------------------------------------");
    error_log(print_r($testuser->email,1));
    error_log(print_r($subject,1));
    error_log(print_r($body,1));
    error_log(print_r($bodyhtml,1));
    email_to_user($testuser, $testuser, $subject, $body, $bodyhtml);
    error_log("emailvege-------------------------------------------------------------------");
  }
 }

 public static function get_course_coach($course_id) {
    global $DB;

    $context = context_course::instance($course_id);
    $role_id = $DB->get_field("role", "id", array("shortname"=>'editingteacher'));
    $users = get_role_users($role_id, $context);
    $user = current($users);
    return $user;
 }
}

