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
 * mention_users
 *
 * @package    local_mention_users
 * @copyright  2016 Norbert Ritter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Add a category to the Site Admin menu
$ADMIN->add('localplugins', new admin_category('local_mention_users', get_string('pluginname', 'local_mention_users')));

//General settings page
$temp = new admin_settingpage('local_mention_users_general',  'Settings', 'local/mention_users:manage');

  // Enable tracking
$name = 'local_mention_users/enabletracking';
$title = 'Enable tracking';
$description = 'Enable or disable mention_users event tracking.';
$default = 0;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$temp->add($setting);

  // Send to this role
$name = 'local_mention_users/emailfromrole';
$title = 'Send from role';
$description = 'The role of the course instructor or other person you want emails to be sent from. Emails will be sent from the first user with this role in the course.';
$default = 5;
$context = context_course::instance(1); // site wide course context
$roles = get_assignable_roles($context);
$setting = new admin_setting_configselect($name, $title, $description, $default, $roles);
$temp->add($setting);

  // Email subject
$name = 'local_mention_users/defaultproperties_subject';
$title = 'Email Subject';
$description = 'The value for the subject of an email.';
$default =
"Forum Post - You have been Mentioned in a Forum Post | {course_fullname}";
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$temp->add($setting);

  //Email body
$name = 'local_mention_users/defaultproperties_body';
$title = 'Email Body';
$description = 'The value for the body of an email.';
$default =
"Hi {student_first_name},

You have been mentioned in a forum post. Please click the following link to view.
{post_link}

Regards,
{coach_first_name}";
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$temp->add($setting);

$ADMIN->add('local_mention_users', $temp);
