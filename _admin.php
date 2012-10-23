<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2012 Franck Paul
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

// Dashboard behaviours
$core->addBehavior('adminDashboardItems',array('dmLastCommentsBehaviors','adminDashboardItems'));
$core->addBehavior('adminDashboardContents',array('dmLastCommentsBehaviors','adminDashboardContents'));

// User-preferecences behaviours
$core->addBehavior('adminBeforeUserOptionsUpdate',array('dmLastCommentsBehaviors','adminBeforeUserUpdate'));
$core->addBehavior('adminPreferencesForm',array('dmLastCommentsBehaviors','adminPreferencesForm'));

# BEHAVIORS
class dmLastCommentsBehaviors
{
	private static function getLastComments($core,$nb,$large,$author,$date,$time,$nospam,$recents = 0)
	{
		// Get last $nb comments
		$params = array();
		if ((integer) $nb > 0) {
			$params['limit'] = (integer) $nb;
		} else {
			$params['limit'] = 30;	// As in first page of comments' list
		}
		if ($nospam) {
			// Exclude junk comment from list
			$params['comment_status_not'] = -2;
		}
		if ((integer) $recents > 0) {
			$params['sql'] = ' AND comment_dt >= (NOW() - INTERVAL '.sprintf((integer) $recents).' HOUR) ';
		}
		$rs = $core->blog->getComments($params,false);
		if (!$rs->isEmpty()) {
			$ret = '<ul>';
			while ($rs->fetch()) {
				$ret .= '<li>';
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
			return '<p>'.__('No comment').((integer) $recents > 0 ? ' '.sprintf(__('since %d hours'),(integer) $recents) : '').'</p>';
		}
	}
	
	public static function adminDashboardItems($core,$items)
	{
		// Add small module to the items stack
		$core->auth->user_prefs->addWorkspace('dmlastcomments');
		if ($core->auth->user_prefs->dmlastcomments->last_comments && !$core->auth->user_prefs->dmlastcomments->last_comments_large) {
			$ret = '<div id="last-comments">'.'<h3>'.'<img src="index.php?pf=dmLastComments/icon.png" alt="" />'.' '.__('Last comments').'</h3>';
			$ret .= dmLastCommentsBehaviors::getLastComments($core,
				$core->auth->user_prefs->dmlastcomments->last_comments_nb,
				false,
				$core->auth->user_prefs->dmlastcomments->last_comments_author,
				$core->auth->user_prefs->dmlastcomments->last_comments_date,
				$core->auth->user_prefs->dmlastcomments->last_comments_time,
				$core->auth->user_prefs->dmlastcomments->last_comments_nospam,
				$core->auth->user_prefs->dmlastcomments->last_comments_recents);
			$ret .= '</div>';
			$items[] = new ArrayObject(array($ret));
		}
	}

	public static function adminDashboardContents($core,$contents)
	{
		// Add large modules to the contents stack
		$core->auth->user_prefs->addWorkspace('dmlastcomments');
		if ($core->auth->user_prefs->dmlastcomments->last_comments && $core->auth->user_prefs->dmlastcomments->last_comments_large) {
			$ret = '<div id="last-comments">'.'<h3>'.'<img src="index.php?pf=dmLastComments/icon.png" alt="" />'.' '.__('Last comments').'</h3>';
			$ret .= dmLastCommentsBehaviors::getLastComments($core,
				$core->auth->user_prefs->dmlastcomments->last_comments_nb,
				true,
				$core->auth->user_prefs->dmlastcomments->last_comments_author,
				$core->auth->user_prefs->dmlastcomments->last_comments_date,
				$core->auth->user_prefs->dmlastcomments->last_comments_time,
				$core->auth->user_prefs->dmlastcomments->last_comments_nospam,
				$core->auth->user_prefs->dmlastcomments->last_comments_recents);
			$ret .= '</div>';
			$contents[] = new ArrayObject(array($ret));
		}
	}

	public static function adminBeforeUserUpdate($cur,$userID)
	{
		global $core;

		// Get and store user's prefs for plugin options
		$core->auth->user_prefs->addWorkspace('dmlastcomments');
		try {
			// Pending comments
			$core->auth->user_prefs->dmlastcomments->put('last_comments',!empty($_POST['dmlast_comments']),'boolean');
			$core->auth->user_prefs->dmlastcomments->put('last_comments_nb',(integer)$_POST['dmlast_comments_nb'],'integer');
			$core->auth->user_prefs->dmlastcomments->put('last_comments_large',!empty($_POST['dmlast_comments_large']),'boolean');
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
	
	public static function adminPreferencesForm($core)
	{
		// Add fieldset for plugin options
		$core->auth->user_prefs->addWorkspace('dmlastcomments');

		echo '<div class="col">';

		echo '<fieldset><legend>'.__('Last comments on dashboard').'</legend>'.
		
		'<p><label for"dmlast_comments" class="classic">'.
		form::checkbox('dmlast_comments',1,$core->auth->user_prefs->dmlastcomments->last_comments).' '.
		__('Display last comments').'</label></p>'.

		'<p><label for"dmlast_comments_nb">'.__('Number of last comments to display:').
		form::field('dmlast_comments_nb',2,3,(integer) $core->auth->user_prefs->dmlastcomments->last_comments_nb).
		'</label></p>'.

		'<p><label for"dmlast_comments_large" class="classic">'.
		form::checkbox('dmlast_comments_large',1,$core->auth->user_prefs->dmlastcomments->last_comments_large).' '.
		__('Display last comments in large section (under favorites)').'</label></p>'.

		'<p><label for"dmlast_comments_author" class="classic">'.
		form::checkbox('dmlast_comments_author',1,$core->auth->user_prefs->dmlastcomments->last_comments_author).' '.
		__('Show authors').'</label></p>'.

		'<p><label for"dmlast_comments_date" class="classic">'.
		form::checkbox('dmlast_comments_date',1,$core->auth->user_prefs->dmlastcomments->last_comments_date).' '.
		__('Show dates').'</label></p>'.

		'<p><label for"dmlast_comments_time" class="classic">'.
		form::checkbox('dmlast_comments_time',1,$core->auth->user_prefs->dmlastcomments->last_comments_time).' '.
		__('Show times').'</label></p>'.

		'<p><label for"dmlast_comments_nospam" class="classic">'.
		form::checkbox('dmlast_comments_nospam',1,$core->auth->user_prefs->dmlastcomments->last_comments_nospam).' '.
		__('Exclude junk comments').'</label></p>'.

		'<p><label for"dmlast_comments_recents">'.__('Max age of comments to display (in hours):').
		form::field('dmlast_comments_recents',2,3,(integer) $core->auth->user_prefs->dmlastcomments->last_comments_recents).
		'</label></p>'.
		'<p class="form-note">'.__('Leave empty to ignore age of comments').'</p>'.

		'<br class="clear" />'. //Opera sucks
		'</fieldset>';

		echo '</div>';
	}
}
?>