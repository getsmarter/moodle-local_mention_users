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

    $link = $_SERVER['HTTP_HOST'] . '/mod/forum/discuss.php?d=' . $discussion_id . '#p' . $post_id;
    self::send_email_to_students($id_array, $course_name, $course_coach, $link, $content);
 }

 public static function email_mention_hsu(\mod_hsuforum\event\assessable_uploaded $event) {
     global $CFG, $DB;

     $other = (object)$event->other;
     $content = $other->content;

     $id_array = self::parse_id($content);

     foreach ($id_array as $id) {

         $taggeduser = $DB->get_record('user', array('id' => $id));
         $taggedusername = '@'.$taggeduser->firstname.' '.$taggeduser->lastname;

         if (strpos($content, $taggedusername) !== false ) {
             $discussion_id = $other->discussionid;
             $post_id = $event->objectid;
             $course_id = $event->courseid;

             $course_name = $DB->get_field("course", "fullname", array("id"=>$course_id));

             $from_user = $DB->get_record("user", array("id"=>$event->userid));

             $link = $CFG->wwwroot . '/mod/hsuforum/discuss.php?d=' . $discussion_id . '#p' . $post_id;
             $subject = get_config('local_mention_users', 'defaultproperties_subject');
             $subject = str_replace("{course_fullname}", $course_name, $subject);

             $course_coach = self::get_course_coach($course_id);

             $body = get_config('local_mention_users', 'defaultproperties_body');
             $body = str_replace("{student_first_name}", $taggedusername, $body);
             $body = str_replace("{coach_first_name}", $course_coach->firstname, $body);
             $body = str_replace("{post_link}", 'http://' . $link, $body);
             $body = str_replace("{message_text}", $content, $body);
             $bodyhtml = text_to_html($body, null, false, true);

             $eventdata = new \core\message\message();
             $eventdata->component          = 'local_getsmarter_communication';
             $eventdata->name               = 'hsuforum_mentions';
             $eventdata->userfrom           = isset($from_user) && !empty($from_user) ? $from_user->id : -10;
             $eventdata->userto             = $id;
             $eventdata->subject            = $subject;
             $eventdata->courseid           = $event->courseid;
             $eventdata->fullmessage        = $bodyhtml;
             $eventdata->fullmessageformat  = FORMAT_PLAIN;
             $eventdata->fullmessagehtml    = '';
             $eventdata->notification       = 1;
             $eventdata->replyto            = '';
             $eventdata->smallmessage       = $subject;

             $contexturl = new moodle_url('/mod/hsuforum/discuss.php', array('d' => $discussion_id), 'p' . $post_id);
             $eventdata->contexturl = $contexturl->out();
             $eventdata->contexturlname = (isset($discussion->name) ? $discussion->name : '');

             try {
                 message_send($eventdata);
             } catch (Exception $e) {
                 error_log($e);
             }
         }
     }
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

 public static function send_email_to_students($id_array, $course_name, $course_coach, $link, $message_text) {
  foreach ($id_array as $id) {
    global $DB;
    global $CFG;
    require_once($CFG->libdir.'/moodlelib.php');

    $student = $DB->get_record('user', array('id'=>$id));

    $subject = get_config('local_mention_users', 'defaultproperties_subject');
    $body = get_config('local_mention_users', 'defaultproperties_body');

    $subject = str_replace("{course_fullname}", $course_name, $subject);
    $body = str_replace("{student_first_name}", $student->firstname, $body);
    $body = str_replace("{coach_first_name}", $course_coach->firstname, $body);
    $body = str_replace("{post_link}", 'http://' . $link, $body);
    $body = str_replace("{message_text}", $message_text, $body);

    $bodyhtml = text_to_html($body, null, false, true);
    email_to_user($student, $course_coach, $subject, $body, $bodyhtml);
  }
 }

 public static function get_course_coach($course_id) {
    global $DB;
    global $CFG;

    $context = context_course::instance($course_id);
    $role_id = get_config('local_mention_users', 'emailfromrole');
    if ($role_id == 'noreply') {
        $email = $CFG->noreplyaddress;
        return $email;
    } else {
        $users = get_role_users($role_id, $context);
        $user = current($users);
        return $user;
    }
 }
}
