dotclear.dmLastCommentsCheck = function() {
	var params = {
		f: 'dmLastCommentsCheck',
		xd_check: dotclear.nonce,
		last_id: dotclear.dmLastComment_LastCommentId
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
					stored_id: dotclear.dmLastComment_LastCommentId,
					last_id: $('rsp>check',data).attr('last_id')
				};
				// Store last comment id
				dotclear.dmLastComment_LastCommentId = $('rsp>check',data).attr('last_id');
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
							if ($('#last-comments ul').length) {
								$('#last-comments ul').remove();
							}
							if ($('#last-comments p').length) {
								$('#last-comments p').remove();
							}
							$('#last-comments h3').after(xml);
							// Bind every new lines for viewing comment content
							$.expandContent({
								lines: $('#last-comments li.line'),
								callback: dotclear.dmLastCommentsView
							});
							$('#last-comments ul').css('list-style-type', 'none').css('padding-left', '0.5em');
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
	$('#last-comments ul').css('list-style-type', 'none').css('padding-left', '0.5em');

	if (dotclear.dmLastComment_AutoRefresh) {
		// Auto refresh requested : Set 30 seconds interval between two checks for new comments
		dotclear.dmLastComments_Timer = setInterval(dotclear.dmLastCommentsCheck,30*1000);
	}
});
