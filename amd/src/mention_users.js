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
 * Version details
 *
 * @package    local_mention_users
 * @copyright  2014 GetSmarter {@link http://www.getsmarter.co.za}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module local_mention_users/mention
 */

define(['jquery', 'local_mention_users/tribute'], function($) {

  var module = {};

  module.init = function() {

    var reply_id = $('input[name=reply]').val();

    function getUsers(replyId) {
      $.ajax({
        dataType: "json",
        url: '/local/mention_users/getusers.php',
        data: 'action=tribute' + '&reply=' + replyId,
        success: function(json) {

          if (json.result) {
            populateTributeArray(json.content, json.courseid);
          } else {
            window.alert(json.content);
          }

        }
      });
    }

    function populateTributeArray(content, courseid) {
      var data = JSON.parse(content);
      var users_array = [];

      for (i = 0; i < data.length; i += 2) {
        users_array.push({
          key: data[i],
          value: data[i + 1]
        });
      }

      var tribute = new Tribute({
        collection: [{
          selectTemplate: function(item) {
            return '<span contenteditable="false"><a href=' + window.location.origin + '/user/view.php?id=' + item.original.value + '&course=' + courseid + ' target="_blank" userid="' + item.original.value + '">@' + item.original.key + '</a></span>';
          },
          values: users_array
        }]
      })

      $(document).ready(function() {
        tribute.attach(document.getElementById('id_messageeditable'));
      });
    }

    getUsers(reply_id);
  };
  return module;
});
