dotclear.viewLastCommentContent = function(line, action) {
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
		callback: dotclear.viewLastCommentContent
	});
	$('#last-comments ul').css('list-style-type', 'none').css('padding-left', '0.5em');
});
