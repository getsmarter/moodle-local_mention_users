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

define(['jquery', 'core/ajax', 'local_mention_users/tribute'], function($, ajax) {

  var module = {};

  module.init = function() {

    var reply_id = $('input[name=reply]').val();
    if ($('input[name=forum]').length > 0) {
      var forum_id = $('input[name=forum]').val();
    }else{
      var forum_id = 0;
    }

    if (/hsuforum/.test(window.location.href)) {
      var advanced_forum = 1;
    } else {
      var advanced_forum = 0;
    }

    function getUsers(replyId, forumId, advanced_forum) {
      var new_discussion = window.location.pathname.indexOf("/mod/hsuforum/view.php") > -1;

        var mentionUsers = ajax.call([
            {
                methodname: 'local_mention_users_getusers',
                args: {
                    action: 'tribute',
                    reply: replyId,
                    forum: forumId,
                    advancedforum: advanced_forum,
                    newdiscussion: new_discussion
                }
            }
        ]);

        mentionUsers[0].done(function(response) {
            if (response.result) {
                populateTributeArray(response.content, response.courseid);
            } else {
                throw response.content;
            }
        }).fail(function(ex) {
            throw ex;
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

      window.tributeinstance = tribute;
      window.usersarray = users_array;

      let user = null;
      let userid = window.location.search.match(/u=(\d+)/);
      let useridpassed = false;
      let windowhashash = false;
      if (userid !== null) {
        // This is a bit hacky, but if a user has the auto-tag functionality, once they submit, it retags user, so if
        // there is the hash (which is added to scroll to the post), don't allow aut-tagging
        useridpassed = true;
        windowhashash = window.location.hash !== '';
        user = users_array.filter(function(item) {return item.value == userid[1]})[0];
      }

      // Atto Editor
      if (document.getElementById('id_messageeditable')) {
        $(document).ready(function() {
          tribute.attach(document.getElementById('id_messageeditable'));
        });
      }

      //Advanced forum
      document.addEventListener("DOMSubtreeModified", throttle( function() {
        if (!$('.hsuforum-textarea').attr('data-tribute')) {
          tribute.attach(document.querySelectorAll('.hsuforum-textarea'));
        }
        if (!$('#hiddenadvancededitoreditable').attr('data-tribute')) {
          tribute.attach(document.querySelectorAll('#hiddenadvancededitoreditable'));
          if (useridpassed && !windowhashash){
            $('.hsuforum-textarea').append(
              '<span contenteditable="false"><a href=' + window.location.origin + '/user/view.php?id=' + user.value + '&course=' + courseid + ' target="_blank" userid="' + user.value + '">@' + user.key + '</a></span>&nbsp;'
            );
            $('.hsuforum-textarea').get(0).scrollIntoView();
          } else if (useridpassed && windowhashash) {
            $('.hsuforum-textarea').empty();
          }
        }
        if (!$('#hiddenadvancededitoreditable').attr('data-tribute')) {
          tribute.attach(document.querySelectorAll('#hiddenadvancededitoreditable'));
          if (useridpassed && !windowhashash){
            $('#hiddenadvancededitoreditable').append(
              '<span contenteditable="false"><a href=' + window.location.origin + '/user/view.php?id=' + user.value + '&course=' + courseid + ' target="_blank" userid="' + user.value + '">@' + user.key + '</a></span>&nbsp;'
            )
            $('#hiddenadvancededitoreditable').get(0).scrollIntoView();
          } else if (useridpassed && windowhashash) {
            $('#hiddenadvancededitoreditable').empty();
          }
        }
      }, 50 ), false );

      // This is to ensure that the DOMSubtreeModified event doesn't execute our code over and over.
      // http://stackoverflow.com/questions/11867331/how-to-identify-that-last-domsubtreemodified-is-fired
      function throttle( fn, time ) {
          var t = 0;
          return function() {
              var args = arguments,
                  ctx = this;

                  clearTimeout(t);

              t = setTimeout( function() {
                  fn.apply( ctx, args );
              }, time );
          };
      }
    }

    // Anchor links offset because hanging navbar hides half the post by default
    var shiftWindow = function() { scrollBy(0, -70) };
    if (location.hash) shiftWindow();
    window.addEventListener("hashchange", shiftWindow);
    window.addEventListener('hashchange', function() {
      setTimeout(function() {
        $('.hsuforum-textarea').empty();
        $('#hiddenadvancededitoreditable').empty();
      }, 1000);
    });

    getUsers(reply_id, forum_id, advanced_forum);
  };
  return module;
});
