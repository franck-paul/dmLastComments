/*global $, dotclear */
'use strict';

dotclear.dmLastCommentsSpam = function () {
  $.get('services.php', {
    f: 'dmLastCommentsSpam',
    xd_check: dotclear.nonce,
  })
    .done(function (data) {
      if ($('rsp[status=failed]', data).length > 0) {
        // For debugging purpose only:
        // console.log($('rsp',data).attr('message'));
        window.console.log('Dotclear REST server error');
      } else {
        const nb_spams = Number($('rsp>check', data).attr('ret'));
        if (nb_spams !== undefined && nb_spams != dotclear.dmLastComments_SpamCount) {
          dotclear.badge($('#dashboard-main #icons p a[href="comments.php"]'), {
            id: 'dmls',
            remove: nb_spams == 0,
            value: nb_spams,
            sibling: true,
            icon: true,
          });
          dotclear.dmLastComments_SpamCount = nb_spams;
        }
      }
    })
    .fail(function (jqXHR, textStatus, errorThrown) {
      window.console.log(`AJAX ${textStatus} (status: ${jqXHR.status} ${errorThrown})`);
    })
    .always(function () {
      // Nothing here
    });
};

dotclear.dmLastCommentsCheck = function () {
  const params = {
    f: 'dmLastCommentsCheck',
    xd_check: dotclear.nonce,
    last_id: dotclear.dmLastComments_LastCommentId,
  };
  $.get('services.php', params, function (data) {
    if ($('rsp[status=failed]', data).length > 0) {
      // For debugging purpose only:
      // console.log($('rsp',data).attr('message'));
      window.console.log('Dotclear REST server error');
    } else {
      const new_comments = Number($('rsp>check', data).attr('ret'));
      if (new_comments > 0) {
        const args = {
          f: 'dmLastCommentsRows',
          xd_check: dotclear.nonce,
          stored_id: dotclear.dmLastComments_LastCommentId,
          last_id: $('rsp>check', data).attr('last_id'),
          last_counter: dotclear.dmLastComments_LastCounter,
        };
        // Store last comment id
        dotclear.dmLastComments_LastCommentId = $('rsp>check', data).attr('last_id');
        // Get new list
        $.get('services.php', args, function (data) {
          if ($('rsp[status=failed]', data).length > 0) {
            // For debugging purpose only:
            // console.log($('rsp',data).attr('message'));
            window.console.log('Dotclear REST server error');
          } else {
            if (Number($('rsp>rows', data).attr('ret')) > 0) {
              // Display new comments
              const xml = $('rsp>rows', data).attr('list');
              // Replace current list with the new one
              if ($('#last-comments ul').length) {
                $('#last-comments ul').remove();
              }
              if ($('#last-comments p').length) {
                $('#last-comments p').remove();
              }
              const counter = Number($('rsp>rows', data).attr('counter'));
              if (counter > 0) {
                dotclear.dmLastComments_LastCounter = Number(dotclear.dmLastComments_LastCounter) + counter;
              }
              $('#last-comments h3').after(xml);
              if (dotclear.dmLastComments_Badge) {
                // Badge on module
                dotclear.badge($('#last-comments'), {
                  id: 'dmlc',
                  value: dotclear.dmLastComments_LastCounter,
                  remove: dotclear.dmLastComments_LastCounter == 0,
                });
                // Badge on menu item
                dotclear.badge($('#main-menu li a[href="comments.php"]'), {
                  id: 'dmlc',
                  value: dotclear.dmLastComments_LastCounter,
                  remove: dotclear.dmLastComments_LastCounter == 0,
                  inline: true,
                  sibling: true,
                });
              }
              // Bind every new lines for viewing comment content
              $.expandContent({
                lines: $('#last-comments li.line'),
                callback: dotclear.dmLastCommentsView,
              });
              $('#last-comments ul').addClass('expandable');
            }
          }
        });
      }
    }
  });
};

dotclear.dmLastCommentsView = function (line, action, e) {
  action = action || 'toggle';
  if ($(line).attr('id') == undefined) {
    return;
  }

  const commentId = $(line).attr('id').substr(4);
  const lineId = `dmlce${commentId}`;
  let li = document.getElementById(lineId);

  // If meta key down or it's a spam then display content HTML code
  const clean = e.metaKey || $(line).hasClass('sts-junk');

  if (!li) {
    // Get comment content if possible
    dotclear.getCommentContent(
      commentId,
      function (content) {
        if (content) {
          li = document.createElement('li');
          li.id = lineId;
          li.className = 'expand';
          $(li).append(content);
          $(line).addClass('expand');
          line.parentNode.insertBefore(li, line.nextSibling);
        } else {
          // No content, content not found or server error
          $(line).removeClass('expand');
        }
      },
      {
        metadata: false,
        clean: clean,
      }
    );
  } else {
    $(li).toggle();
    $(line).toggleClass('expand');
  }
};

$(function () {
  Object.assign(dotclear, dotclear.getData('dm_lastcomments'));
  $.expandContent({
    lines: $('#last-comments li.line'),
    callback: dotclear.dmLastCommentsView,
  });
  $('#last-comments ul').addClass('expandable');
  if (dotclear.dmLastComments_AutoRefresh) {
    // Auto refresh requested : Set 30 seconds interval between two checks for new comments and spam counter check
    dotclear.dmLastComments_Timer = setInterval(dotclear.dmLastCommentsCheck, 30 * 1000);
    if (dotclear.dmLastComments_Badge) {
      const icon_com = $('#dashboard-main #icons p a[href="comments.php"]');
      if (icon_com.length) {
        // First pass
        dotclear.dmLastCommentsSpam();
        // Then fired every 30 seconds
        dotclear.dmLastComments_TimerSpam = setInterval(dotclear.dmLastCommentsSpam, 30 * 1000);
      }
    }
  }
});
