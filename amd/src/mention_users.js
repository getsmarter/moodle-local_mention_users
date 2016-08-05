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
    var forum_id = $('input[name=forum]').val();



    if (/hsuforum/.test(window.location.href)) {
      var advanced_forum = 1;

      // Group ID
      var group = $('.hsuforum-thread-byline')
      group.find('a').remove()
      var groupname = group.text().trim()

      var text = $('#hsuforum-discussion-template').html()

      var text_array = text.split('">' + groupname)[0].split('value="')
      var group_id = text_array[text_array.length - 1]

    } else {
      var advanced_forum = 0;
      var group_id = $('input[name=groupid]').val();
    }

    function getUsers(replyId, forumId, groupId, advanced_forum) {
      $.ajax({
        dataType: "json",
        url: '/local/mention_users/getusers.php',
        data: 'action=tribute' + '&reply=' + replyId + '&forum=' + forumId + '&group=' + groupId + '&advancedforum=' + advanced_forum,
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

      // Atto Editor
      if (document.getElementById('id_messageeditable')) {
        $(document).ready(function() {
          tribute.attach(document.getElementById('id_messageeditable'));
        });
      }

      //Advanced forum
      $( document ).bind("DOMSubtreeModified", function() {
        if (!$('.hsuforum-textarea').attr('data-tribute')) {
          tribute.attach(document.querySelectorAll('.hsuforum-textarea'));
        }
      });
    }

    // Anchor links offset because hanging navbar hides half the post by default
    var shiftWindow = function() { scrollBy(0, -70) };
    if (location.hash) shiftWindow();
    window.addEventListener("hashchange", shiftWindow);

    getUsers(reply_id, forum_id, group_id, advanced_forum);
  };
  return module;
});
