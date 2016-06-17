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

    $id_array = self::parse_id($content);

    error_log("geeeesdafsadfsadf------- ");
    error_log(print_r($id_array,1));
 }

 public function parse_id($content) {
    $parsed_string_array = array();

    $string_array = explode('userid="',$content);

    for ($x = 1; $x < count($string_array); $x++) {
       $string = $string_array[$x];

       $parsed_string=explode('userid="',$string)[0];
       array_push($parsed_string_array,$parsed_string);

    }

    $id_array = array();

    foreach($parsed_string_array as $string) {
     $id = explode('">', $string)[0];
     array_push($id_array, $id);
    }

    return $id_array;
 }
}

