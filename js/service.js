/*global dotclear */
'use strict';

dotclear.ready(() => {
  dotclear.dmLastComments = dotclear.getData('dm_lastcomments');

  const viewComment = (line, _action = 'toggle', event = null) => {
    dotclear.dmViewComment(line, 'dmlce', event.metaKey || line.classList.contains('sts-junk'));
  };

  const getSpamCount = (icon) => {
    dotclear.services(
      'dmLastCommentsSpam',
      (data) => {
        try {
          const response = JSON.parse(data);
          if (response?.success) {
            if (response?.payload.ret) {
              const nb_spams = response.payload.nb;
              if (nb_spams !== undefined && nb_spams !== dotclear.dmLastComments.spamCount) {
                dotclear.badge(icon, {
                  id: 'dmls',
                  value: nb_spams,
                  remove: nb_spams <= 0,
                  sibling: true,
                  icon: true,
                });
                dotclear.dmLastComments.spamCount = nb_spams;
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

  const getCommentsRows = (last_id, menu) => {
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
              for (const item of document.querySelectorAll('#last-comments ul')) item.remove();
              for (const item of document.querySelectorAll('#last-comments p')) item.remove();
              if (counter > 0) {
                dotclear.dmLastComments.lastCounter = Number(dotclear.dmLastComments.lastCounter) + counter;
              }
              const title = document.querySelector('#last-comments h3');
              title?.insertAdjacentHTML('afterend', response.payload.list);

              if (dotclear.dmLastComments.badge) {
                // Badge on module
                dotclear.badge(document.querySelector('#last-comments'), {
                  id: 'dmlc',
                  value: dotclear.dmLastComments.lastCounter,
                  remove: dotclear.dmLastComments.lastCounter <= 0,
                });
                // Badge on each menu items
                for (const item of menu) {
                  dotclear.badge(item, {
                    id: 'dmlc',
                    value: dotclear.dmLastComments.lastCounter,
                    remove: dotclear.dmLastComments.lastCounter <= 0,
                    inline: true,
                  });
                }
              }
              // Bind every new lines for viewing comment content
              dotclear.expandContent({
                lines: document.querySelectorAll('#last-comments li.line'),
                callback: viewComment,
              });
              for (const item of document.querySelectorAll('#last-comments ul')) item.classList.add('expandable');
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
        stored_id: dotclear.dmLastComments.lastCommentId,
        last_id,
        last_counter: dotclear.dmLastComments.lastCounter,
      },
    );
  };

  const getLastComments = (menu) => {
    dotclear.services(
      'dmLastCommentsCheck',
      (data) => {
        try {
          const response = JSON.parse(data);
          if (response?.success) {
            if (response?.payload.ret && response.payload.nb > 0) {
              getCommentsRows(response.payload.last_id, menu);
              // Store last comment id
              dotclear.dmLastComments.lastCommentId = response.payload.last_id;
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
        last_id: dotclear.dmLastComments.lastCommentId,
      },
    );
  };

  dotclear.expandContent({
    lines: document.querySelectorAll('#last-comments li.line'),
    callback: viewComment,
  });
  for (const item of document.querySelectorAll('#last-comments ul')) item.classList.add('expandable');

  if (!dotclear.dmLastComments.autoRefresh) {
    return;
  }

  // First pass
  const menu_com = document.querySelectorAll('#main-menu li #menu-process-comments-fav, #main-menu li #menu-process-Comments');
  dotclear.dmLastComments.lastCommentId = -1;
  dotclear.dmLastComments.lastCounter = 0;
  getLastComments(menu_com);

  // Set interval between two checks for new comments and spam counter check
  dotclear.dmLastComments.timer = setInterval(getLastComments, (dotclear.dmLastComments.interval || 30) * 1000, menu_com);

  // Spams
  if (!dotclear.dmLastComments.badge) {
    return;
  }
  icon_com = document.querySelector('#dashboard-main #icons p #icon-process-comments-fav');
  if (icon_com) {
    // First pass
    getSpamCount(icon_com);

    // Then fired every X seconds
    dotclear.dmLastComments.timerSpam = setInterval(getSpamCount, (dotclear.dmLastComments.interval || 30) * 1000, icon_com);
  }
});
