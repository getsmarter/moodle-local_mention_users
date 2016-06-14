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
