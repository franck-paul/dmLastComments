<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of dmLastComments, a plugin for Dotclear 2.
#
# Copyright (c) Franck Paul and contributors
# carnet.franck.paul@gmail.com
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) { return; }

// dead but useful code, in order to have translations
__('Last Comments Dashboard Module').__('Display last comments on dashboard');

// Dashboard behaviours
$core->addBehavior('adminDashboardHeaders',array('dmLastCommentsBehaviors','adminDashboardHeaders'));
$core->addBehavior('adminDashboardContents',array('dmLastCommentsBehaviors','adminDashboardContents'));

$core->addBehavior('adminAfterDashboardOptionsUpdate',array('dmLastCommentsBehaviors','adminAfterDashboardOptionsUpdate'));
$core->addBehavior('adminDashboardOptionsForm',array('dmLastCommentsBehaviors','adminDashboardOptionsForm'));

# BEHAVIORS
class dmLastCommentsBehaviors
{
	public static function adminDashboardHeaders()
	{
		return dcPage::jsLoad('index.php?pf=dmLastComments/js/service.js');
	}

	private static function getLastComments($core,$nb,$large,$author,$date,$time,$nospam,$recents = 0)
	{
		$recents = (integer) $recents;
		$nb = (integer) $nb;

		// Get last $nb comments
		$params = array();
		if ($nb > 0) {
			$params['limit'] = $nb;
		} else {
			$params['limit'] = 30;	// As in first page of comments' list
		}
		if ($nospam) {
			// Exclude junk comment from list
			$params['comment_status_not'] = -2;
		}
		if ($recents > 0) {
			$params['sql'] = ' AND comment_dt >= (NOW() - INTERVAL '.sprintf($recents).' HOUR) ';
		}
		$rs = $core->blog->getComments($params,false);
		if (!$rs->isEmpty()) {
			$ret = '<ul>';
			while ($rs->fetch()) {
				$ret .= '<li class="line" id="dmlc'.$rs->comment_id.'">';
				$ret .= '<a href="comment.php?id='.$rs->comment_id.'">'.$rs->post_title.'</a>';
				$info = array();
				if ($large) {
					if ($author) {
						$info[] = __('by').' '.$rs->comment_author;
					}
					if ($date) {
						$info[] = __('on').' '.dt::dt2str($core->blog->settings->system->date_format,$rs->comment_dt);
					}
					if ($time) {
						$info[] = __('at').' '.dt::dt2str($core->blog->settings->system->time_format,$rs->comment_dt);
					}
				} else {
					if ($author) {
						$info[] = $rs->comment_author;
					}
					if ($date) {
						$info[] = dt::dt2str(__('%Y-%m-%d'),$rs->comment_dt);
					}
					if ($time) {
						$info[] = dt::dt2str(__('%H:%M'),$rs->comment_dt);
					}
				}
				if (count($info)) {
					$ret .= ' ('.implode(' ',$info).')';
				}
				$ret .= '</li>';
			}
			$ret .= '</ul>';
			$ret .= '<p><a href="comments.php">'.__('See all comments').'</a></p>';
			return $ret;
		} else {
			return '<p>'.__('No comments').
				($recents > 0 ? ' '.sprintf(__('since %d hour','since %d hours',$recents),$recents) : '').'</p>';
		}
	}

	public static function adminDashboardContents($core,$contents)
	{
		// Add modules to the contents stack
		$core->auth->user_prefs->addWorkspace('dmlastcomments');
		if ($core->auth->user_prefs->dmlastcomments->last_comments) {
			$class = ($core->auth->user_prefs->dmlastcomments->last_comments_large ? 'medium' : 'small');
			$ret = '<div id="last-comments" class="box '.$class.'">'.
				'<h3>'.'<img src="index.php?pf=dmLastComments/icon.png" alt="" />'.' '.__('Last comments').'</h3>';
			$ret .= dmLastCommentsBehaviors::getLastComments($core,
				$core->auth->user_prefs->dmlastcomments->last_comments_nb,
				$core->auth->user_prefs->dmlastcomments->last_comments_large,
				$core->auth->user_prefs->dmlastcomments->last_comments_author,
				$core->auth->user_prefs->dmlastcomments->last_comments_date,
				$core->auth->user_prefs->dmlastcomments->last_comments_time,
				$core->auth->user_prefs->dmlastcomments->last_comments_nospam,
				$core->auth->user_prefs->dmlastcomments->last_comments_recents);
			$ret .= '</div>';
			$contents[] = new ArrayObject(array($ret));
		}
	}

	public static function adminAfterDashboardOptionsUpdate($userID)
	{
		global $core;

		// Get and store user's prefs for plugin options
		$core->auth->user_prefs->addWorkspace('dmlastcomments');
		try {
			// Pending comments
			$core->auth->user_prefs->dmlastcomments->put('last_comments',!empty($_POST['dmlast_comments']),'boolean');
			$core->auth->user_prefs->dmlastcomments->put('last_comments_nb',(integer)$_POST['dmlast_comments_nb'],'integer');
			$core->auth->user_prefs->dmlastcomments->put('last_comments_large',empty($_POST['dmlast_comments_small']),'boolean');
			$core->auth->user_prefs->dmlastcomments->put('last_comments_author',!empty($_POST['dmlast_comments_author']),'boolean');
			$core->auth->user_prefs->dmlastcomments->put('last_comments_date',!empty($_POST['dmlast_comments_date']),'boolean');
			$core->auth->user_prefs->dmlastcomments->put('last_comments_time',!empty($_POST['dmlast_comments_time']),'boolean');
			$core->auth->user_prefs->dmlastcomments->put('last_comments_nospam',!empty($_POST['dmlast_comments_nospam']),'boolean');
			$core->auth->user_prefs->dmlastcomments->put('last_comments_recents',(integer)$_POST['dmlast_comments_recents'],'integer');
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}

	public static function adminDashboardOptionsForm($core)
	{
		// Add fieldset for plugin options
		$core->auth->user_prefs->addWorkspace('dmlastcomments');

		echo '<div class="fieldset"><h4>'.__('Last comments on dashboard').'</h4>'.

		'<p>'.
		form::checkbox('dmlast_comments',1,$core->auth->user_prefs->dmlastcomments->last_comments).' '.
		'<label for="dmlast_comments" class="classic">'.__('Display last comments').'</label></p>'.

		'<p><label for="dmlast_comments_nb" class="classic">'.__('Number of last comments to display:').'</label> '.
		form::field('dmlast_comments_nb',2,3,(integer) $core->auth->user_prefs->dmlastcomments->last_comments_nb).
		'</p>'.

		'<p>'.
		form::checkbox('dmlast_comments_author',1,$core->auth->user_prefs->dmlastcomments->last_comments_author).' '.
		'<label for="dmlast_comments_author" class="classic">'.__('Show authors').'</label></p>'.

		'<p>'.
		form::checkbox('dmlast_comments_date',1,$core->auth->user_prefs->dmlastcomments->last_comments_date).' '.
		'<label for="dmlast_comments_date" class="classic">'.__('Show dates').'</label></p>'.

		'<p>'.
		form::checkbox('dmlast_comments_time',1,$core->auth->user_prefs->dmlastcomments->last_comments_time).' '.
		'<label for="dmlast_comments_time" class="classic">'.__('Show times').'</label></p>'.

		'<p>'.
		form::checkbox('dmlast_comments_nospam',1,$core->auth->user_prefs->dmlastcomments->last_comments_nospam).' '.
		'<label for="dmlast_comments_nospam" class="classic">'.__('Exclude junk comments').'</label></p>'.

		'<p><label for="dmlast_comments_recents" class="classic">'.__('Max age of comments to display (in hours):').'</label> '.
		form::field('dmlast_comments_recents',2,3,(integer) $core->auth->user_prefs->dmlastcomments->last_comments_recents).
		'</p>'.
		'<p class="form-note">'.__('Leave empty to ignore age of comments').'</p>'.

		'<p>'.
		form::checkbox('dmlast_comments_small',1,!$core->auth->user_prefs->dmlastcomments->last_comments_large).' '.
		'<label for="dmlast_comments_small" class="classic">'.__('Small screen').'</label></p>'.

		'</div>';
	}
}
