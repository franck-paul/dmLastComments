/*global $, dotclear */
'use strict';

dotclear.dmLastCommentsSpam = function() {
  var params = {
    f: 'dmLastCommentsSpam',
    xd_check: dotclear.nonce,
  };
  $.get('services.php', params, function(data) {
    if ($('rsp[status=failed]', data).length > 0) {
      // For debugging purpose only:
      // console.log($('rsp',data).attr('message'));
      window.console.log('Dotclear REST server error');
    } else {
      var nb_spams = Number($('rsp>check', data).attr('ret'));
      if (nb_spams != dotclear.dmLastComments_SpamCount) {
        dotclear.badge(
          $('#dashboard-main #icons p a[href="comments.php"]'), {
            id: 'dmls',
            remove: (nb_spams == 0),
            value: nb_spams,
            sibling: true,
            icon: true
          }
        );
        dotclear.dmLastComments_SpamCount = nb_spams;
      }
    }
  });
};
dotclear.dmLastCommentsCheck = function() {
  var params = {
    f: 'dmLastCommentsCheck',
    xd_check: dotclear.nonce,
    last_id: dotclear.dmLastComments_LastCommentId
  };
  $.get('services.php', params, function(data) {
    if ($('rsp[status=failed]', data).length > 0) {
      // For debugging purpose only:
      // console.log($('rsp',data).attr('message'));
      window.console.log('Dotclear REST server error');
    } else {
      var new_comments = Number($('rsp>check', data).attr('ret'));
      if (new_comments > 0) {
        var args = {
          f: 'dmLastCommentsRows',
          xd_check: dotclear.nonce,
          stored_id: dotclear.dmLastComments_LastCommentId,
          last_id: $('rsp>check', data).attr('last_id'),
          last_counter: dotclear.dmLastComments_LastCounter
        };
        // Store last comment id
        dotclear.dmLastComments_LastCommentId = $('rsp>check', data).attr('last_id');
        // Get new list
        $.get('services.php', args, function(data) {
          if ($('rsp[status=failed]', data).length > 0) {
            // For debugging purpose only:
            // console.log($('rsp',data).attr('message'));
            window.console.log('Dotclear REST server error');
          } else {
            if (Number($('rsp>rows', data).attr('ret')) > 0) {
              // Display new comments
              var xml = $('rsp>rows', data).attr('list');
              // Replace current list with the new one
              if ($('#last-comments ul').length) {
                $('#last-comments ul').remove();
              }
              if ($('#last-comments p').length) {
                $('#last-comments p').remove();
              }
              var counter = Number($('rsp>rows', data).attr('counter'));
              if (counter > 0) {
                dotclear.dmLastComments_LastCounter = Number(dotclear.dmLastComments_LastCounter) + counter;
              }
              $('#last-comments h3').after(xml);
              if (dotclear.dmLastComments_Badge) {
                // Badge on module
                dotclear.badge(
                  $('#last-comments'), {
                    id: 'dmlc',
                    value: dotclear.dmLastComments_LastCounter,
                    remove: (dotclear.dmLastComments_LastCounter == 0),
                  }
                );
                // Badge on menu item
                dotclear.badge(
                  $('#main-menu li a[href="comments.php"]'), {
                    id: 'dmlc',
                    value: dotclear.dmLastComments_LastCounter,
                    remove: (dotclear.dmLastComments_LastCounter == 0),
                    inline: true,
                    sibling: true
                  }
                );
              }
              // Bind every new lines for viewing comment content
              $.expandContent({
                lines: $('#last-comments li.line'),
                callback: dotclear.dmLastCommentsView
              });
              $('#last-comments ul').addClass('expandable');
            }
          }
        });
      }
    }
  });
};
dotclear.dmLastCommentsView = function(line, action) {
  action = action || 'toggle';
  var commentId = $(line).attr('id').substr(4);
  var li = document.getElementById('dmlce' + commentId);
  if (!li && (action == 'toggle' || action == 'open')) {
    li = document.createElement('li');
    li.id = 'dmlce' + commentId;
    li.className = 'expand';
    // Get comment content
    $.get('services.php', {
      f: 'getCommentById',
      id: commentId
    }, function(data) {
      var rsp = $(data).children('rsp')[0];
      if (rsp.attributes[0].value == 'ok') {
        var comment = $(rsp).find('comment_display_content').text();
        if (comment) {
          $(li).append(comment);
        }
      } else {
        window.alert($(rsp).find('message').text());
      }
    });
    $(line).toggleClass('expand');
    line.parentNode.insertBefore(li, line.nextSibling);
  } else if (li && li.style.display == 'none' && (action == 'toggle' || action == 'open')) {
    $(li).css('display', 'block');
    $(line).addClass('expand');
  } else if (li && li.style.display != 'none' && (action == 'toggle' || action == 'close')) {
    $(li).css('display', 'none');
    $(line).removeClass('expand');
  }
};
$(function() {
  $.expandContent({
    lines: $('#last-comments li.line'),
    callback: dotclear.dmLastCommentsView
  });
  $('#last-comments ul').addClass('expandable');
  if (dotclear.dmLastComments_AutoRefresh) {
    // Auto refresh requested : Set 30 seconds interval between two checks for new comments and spam counter check
    dotclear.dmLastComments_Timer = setInterval(dotclear.dmLastCommentsCheck, 30 * 1000);
    if (dotclear.dmLastComments_Badge) {
      var icon_com = $('#dashboard-main #icons p a[href="comments.php"]');
      if (icon_com.length) {
        // First pass
        dotclear.dmLastCommentsSpam();
        // Then fired every 30 seconds
        dotclear.dmLastComments_TimerSpam = setInterval(dotclear.dmLastCommentsSpam, 30 * 1000);
      }
    }
  }
});
