/*global $, dotclear */
'use strict';

dotclear.dmLastCommentsSpam = (icon) => {
  dotclear.services(
    'dmLastCommentsSpam',
    (data) => {
      try {
        const response = JSON.parse(data);
        if (response?.success) {
          if (response?.payload.ret) {
            const nb_spams = response.payload.nb;
            if (nb_spams !== undefined && nb_spams !== dotclear.dmLastComments_SpamCount) {
              dotclear.badge(icon, {
                id: 'dmls',
                value: nb_spams,
                remove: !nb_spams,
                sibling: true,
                icon: true,
              });
              dotclear.dmLastComments_SpamCount = nb_spams;
            }
          }
        } else {
          console.log(dotclear.debug && response?.message ? response.message : 'Dotclear REST server error');
          return;
        }
      } catch (e) {
        console.log(e);
      }
    },
    (error) => {
      console.log(error);
    },
    true, // Use GET method
    { json: 1 },
  );
};

dotclear.dmLastCommentsRows = (last_id, menu) => {
  // Get new list
  dotclear.services(
    'dmLastCommentsRows',
    (data) => {
      try {
        const response = JSON.parse(data);
        if (response?.success) {
          if (response?.payload.ret) {
            const { counter } = response.payload;
            // Replace current list with the new one
            if ($('#last-comments ul').length) {
              $('#last-comments ul').remove();
            }
            if ($('#last-comments p').length) {
              $('#last-comments p').remove();
            }
            if (counter > 0) {
              dotclear.dmLastComments_LastCounter = Number(dotclear.dmLastComments_LastCounter) + counter;
            }
            $('#last-comments h3').after(response.payload.list);

            if (dotclear.dmLastComments_Badge) {
              // Badge on module
              dotclear.badge($('#last-comments'), {
                id: 'dmlc',
                value: dotclear.dmLastComments_LastCounter,
                remove: !dotclear.dmLastComments_LastCounter,
              });
              // Badge on each menu items
              menu.each((item) => {
                dotclear.badge(menu[item], {
                  id: 'dmlc',
                  value: dotclear.dmLastComments_LastCounter,
                  remove: !dotclear.dmLastComments_LastCounter,
                  inline: true,
                });
              });
            }
            // Bind every new lines for viewing comment content
            $.expandContent({
              lines: $('#last-comments li.line'),
              callback: dotclear.dmLastCommentsView,
            });
            $('#last-comments ul').addClass('expandable');
          }
        } else {
          console.log(dotclear.debug && response?.message ? response.message : 'Dotclear REST server error');
          return;
        }
      } catch (e) {
        console.log(e);
      }
    },
    (error) => {
      console.log(error);
    },
    true, // Use GET method
    {
      json: 1,
      stored_id: dotclear.dmLastComments_LastCommentId,
      last_id,
      last_counter: dotclear.dmLastComments_LastCounter,
    },
  );
};

dotclear.dmLastCommentsCheck = (menu) => {
  dotclear.services(
    'dmLastCommentsCheck',
    (data) => {
      try {
        const response = JSON.parse(data);
        if (response?.success) {
          if (response?.payload.ret && response.payload.nb > 0) {
            dotclear.dmLastCommentsRows(response.payload.last_id, menu);
            // Store last comment id
            dotclear.dmLastComments_LastCommentId = response.payload.last_id;
          }
        } else {
          console.log(dotclear.debug && response?.message ? response.message : 'Dotclear REST server error');
          return;
        }
      } catch (e) {
        console.log(e);
      }
    },
    (error) => {
      console.log(error);
    },
    true, // Use GET method
    {
      json: 1,
      last_id: dotclear.dmLastComments_LastCommentId,
    },
  );
};

dotclear.dmLastCommentsView = (line, action = 'toggle', e = null) => {
  if ($(line).attr('id') === undefined) {
    return;
  }

  const commentId = $(line).attr('id').substring(4);
  const lineId = `dmlce${commentId}`;
  let li = document.getElementById(lineId);

  // If meta key down or it's a spam then display content HTML code
  const clean = e.metaKey || $(line).hasClass('sts-junk');

  if (li) {
    $(li).toggle();
    $(line).toggleClass('expand');
  } else {
    // Get comment content if possible
    dotclear.getCommentContent(
      commentId,
      (content) => {
        if (content) {
          li = document.createElement('li');
          li.id = lineId;
          li.className = 'expand';
          $(li).append(content);
          $(line).addClass('expand');
          line.parentNode.insertBefore(li, line.nextSibling);
          return;
        }
        // No content, content not found or server error
        $(line).removeClass('expand');
      },
      {
        metadata: false,
        clean,
      },
    );
  }
};

dotclear.ready(() => {
  Object.assign(dotclear, dotclear.getData('dm_lastcomments'));
  $.expandContent({
    lines: $('#last-comments li.line'),
    callback: dotclear.dmLastCommentsView,
  });
  $('#last-comments ul').addClass('expandable');
  if (!dotclear.dmLastComments_AutoRefresh) {
    return;
  }

  // Auto refresh

  // Comments
  // First pass
  let menu_com = $('#main-menu li a[href="comments.php"]');
  if (!menu_com.length) {
    menu_com = $('#main-menu li #menu-process-comments-fav, #main-menu li #menu-process-Comments');
  }
  dotclear.dmLastComments_LastCommentId = -1;
  dotclear.dmLastComments_LastCounter = 0;
  dotclear.dmLastCommentsCheck(menu_com);
  // Set interval between two checks for new comments and spam counter check
  dotclear.dmLastComments_Timer = setInterval(
    dotclear.dmLastCommentsCheck,
    (dotclear.dmLastComments_Interval || 30) * 1000,
    menu_com,
  );

  // Spams
  if (!dotclear.dmLastComments_Badge) {
    return;
  }
  let icon_com = $('#dashboard-main #icons p a[href="comments.php"]');
  if (!icon_com.length) {
    icon_com = $('#dashboard-main #icons p #icon-process-comments-fav');
  }
  if (icon_com.length) {
    // First pass
    dotclear.dmLastCommentsSpam(icon_com);
    // Then fired every X seconds
    dotclear.dmLastComments_TimerSpam = setInterval(
      dotclear.dmLastCommentsSpam,
      (dotclear.dmLastComments_Interval || 30) * 1000,
      icon_com,
    );
  }
});
