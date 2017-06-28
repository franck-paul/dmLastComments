dotclear.dmLastCommentsSpam = function() {
	var params = {
		f: 'dmLastCommentsSpam',
		xd_check: dotclear.nonce,
	};
	$.get('services.php',params,function(data) {
		if ($('rsp[status=failed]',data).length > 0) {
			// For debugging purpose only:
			// console.log($('rsp',data).attr('message'));
			console.log('Dotclear REST server error');
		} else {
			var nb_spams = Number($('rsp>check',data).attr('ret'));
			if (nb_spams != dotclear.dmLastComments_SpamCount) {
				// First pass or spam counter changed
				var icon_com = $('#dashboard-main #icons p a[href="comments.php"]');
				if (icon_com.length) {
					// Icon exists on dashboard
					icon_com = icon_com.parent();
					// Remove badge if exists
					var spam_badge = icon_com.children('span.badge');
					if (spam_badge.length) {
						spam_badge.remove();
					}
					// Add new badge if some spams exist
					if (nb_spams > 0) {
						// Badge on icon
						xml = '<span class="badge badge-block badge-block-icon">'+nb_spams+'</span>';
						icon_com.append(xml);
					}
				}
				dotclear.dmLastComments_SpamCount = nb_spams;
			}
		}
	});
}

dotclear.dmLastCommentsCheck = function() {
	var params = {
		f: 'dmLastCommentsCheck',
		xd_check: dotclear.nonce,
		last_id: dotclear.dmLastComments_LastCommentId
	};
	$.get('services.php',params,function(data) {
		if ($('rsp[status=failed]',data).length > 0) {
			// For debugging purpose only:
			// console.log($('rsp',data).attr('message'));
			console.log('Dotclear REST server error');
		} else {
			var new_comments = Number($('rsp>check',data).attr('ret'));
			if (new_comments > 0) {
				var args = {
					f: 'dmLastCommentsRows',
					xd_check: dotclear.nonce,
					stored_id: dotclear.dmLastComments_LastCommentId,
					last_id: $('rsp>check',data).attr('last_id'),
					last_counter: dotclear.dmLastComments_LastCounter
				};
				// Store last comment id
				dotclear.dmLastComments_LastCommentId = $('rsp>check',data).attr('last_id');
				// Get new list
				$.get('services.php',args,function(data) {
					if ($('rsp[status=failed]',data).length > 0) {
						// For debugging purpose only:
						// console.log($('rsp',data).attr('message'));
						console.log('Dotclear REST server error');
					} else {
						if (Number($('rsp>rows',data).attr('ret')) > 0) {
							// Display new comments
							xml = $('rsp>rows',data).attr('list');
							// Replace current list with the new one
							parser = new DOMParser();
							list = parser.parseFromString(xml, "text/xml");
							if ($('#last-comments span.badge').length) {
								$('#last-comments span.badge').remove();
							}
							if ($('#last-comments ul').length) {
								$('#last-comments ul').remove();
							}
							if ($('#last-comments p').length) {
								$('#last-comments p').remove();
							}
							counter = Number($('rsp>rows',data).attr('counter'));
							if (counter > 0) {
								dotclear.dmLastComments_LastCounter = Number(dotclear.dmLastComments_LastCounter) + counter;
								if (dotclear.dmLastComments_Badge) {
									// Badge on module
									xml = '<span class="badge badge-block">'+dotclear.dmLastComments_LastCounter+'</span>'+xml;
									// Badge on menu item
									if ($('#main-menu li span.badge').length) {
										$('#main-menu li span.badge').remove();
									}
									badge = '<span class="badge badge-inline">'+dotclear.dmLastComments_LastCounter+'</span>';
									$('#main-menu li a[href="comments.php"]').after(badge);
								}
							}
							$('#last-comments h3').after(xml);
							// Bind every new lines for viewing comment content
							$.expandContent({
								lines: $('#last-comments li.line'),
								callback: dotclear.dmLastCommentsView
							});
							$('#last-comments ul').addClass('expandable');
							// Transition effect for new comments
							$('.dmlc-new').css('background','#A2CBE9');
							setTimeout(function () {
									$('.dmlc-new').css('background','#fff');
								},2000);
						}
					}
				});
			}
		}
	});
}

dotclear.dmLastCommentsView = function(line, action) {
	var action = action || 'toggle';
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
				alert($(rsp).find('message').text());
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
		dotclear.dmLastComments_Timer = setInterval(dotclear.dmLastCommentsCheck,30*1000);
		if (dotclear.dmLastComments_Badge) {
			$('#last-comments').addClass('badgeable');
			var icon_com = $('#dashboard-main #icons p a[href="comments.php"]');
			if (icon_com.length) {
				// Icon exists on dashboard
				icon_com.parent().addClass('badgeable');
				// First pass
				dotclear.dmLastCommentsSpam();
				// Then fired every 30 seconds
				dotclear.dmLastComments_TimerSpam = setInterval(dotclear.dmLastCommentsSpam,30*1000);
			}
		}
	}
});
