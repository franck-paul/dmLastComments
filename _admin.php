<?php
/**
 * @brief dmLastComments, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

// dead but useful code, in order to have translations
__('Last Comments Dashboard Module') . __('Display last comments on dashboard');

// Dashboard behaviours
$core->addBehavior('adminDashboardHeaders', ['dmLastCommentsBehaviors', 'adminDashboardHeaders']);
$core->addBehavior('adminDashboardContents', ['dmLastCommentsBehaviors', 'adminDashboardContents']);

$core->addBehavior('adminAfterDashboardOptionsUpdate', ['dmLastCommentsBehaviors', 'adminAfterDashboardOptionsUpdate']);
$core->addBehavior('adminDashboardOptionsForm', ['dmLastCommentsBehaviors', 'adminDashboardOptionsForm']);

# BEHAVIORS
class dmLastCommentsBehaviors
{
    public static function adminDashboardHeaders()
    {
        global $core;

        $sqlp = [
            'limit'      => 1,                 // only the last one
            'no_content' => true,              // content is not required
            'order'      => 'comment_id DESC' // get last first
        ];

        $rs = $core->blog->getComments($sqlp);

        if ($rs->count()) {
            $rs->fetch();
            $last_comment_id = $rs->comment_id;
        } else {
            $last_comment_id = -1;
        }

        $core->auth->user_prefs->addWorkspace('dmlastcomments');

        return
        '<script type="text/javascript">' . "\n" .
        dcPage::jsVar('dotclear.dmLastComments_LastCommentId', $last_comment_id) .
        dcPage::jsVar('dotclear.dmLastComments_AutoRefresh', $core->auth->user_prefs->dmlastcomments->last_comments_autorefresh) .
        dcPage::jsVar('dotclear.dmLastComments_Badge', $core->auth->user_prefs->dmlastcomments->last_comments_badge) .
        dcPage::jsVar('dotclear.dmLastComments_LastCounter', 0) .
        dcPage::jsVar('dotclear.dmLastComments_SpamCount', -1) .
        "</script>\n" .
        dcPage::jsLoad(urldecode(dcPage::getPF('dmLastComments/js/service.js')), $core->getVersion('dmLastComments')) .
        dcPage::cssLoad(urldecode(dcPage::getPF('dmLastComments/css/style.css')), 'screen', $core->getVersion('dmLastComments'));
    }

    public static function getLastComments($core, $nb, $large, $author, $date, $time, $nospam, $recents = 0,
        $last_id = -1, &$last_counter = 0) {
        $recents = (integer) $recents;
        $nb      = (integer) $nb;

        // Get last $nb comments
        $params = [];
        if ($nb > 0) {
            $params['limit'] = $nb;
        } else {
            $params['limit'] = 30; // As in first page of comments' list
        }
        if ($nospam) {
            // Exclude junk comment from list
            $params['comment_status_not'] = -2;
        }
        if ($recents > 0) {
            $params['sql'] = ' AND comment_dt >= (NOW() - INTERVAL ' . sprintf($recents) . ' HOUR) ';
        }
        $rs = $core->blog->getComments($params, false);
        if (!$rs->isEmpty()) {
            $ret = '<ul>';
            while ($rs->fetch()) {
                $ret .= '<li class="line';
                if ($last_id != -1 && $rs->comment_id > $last_id) {
                    $ret .= ($last_id != -1 && $rs->comment_id > $last_id ? ' dmlc-new' : '');
                    $last_counter++;
                }
                if ($rs->comment_status == -2) {
                    $ret .= ' sts-junk';
                }
                $ret .= '" id="dmlc' . $rs->comment_id . '">';
                $ret .= '<a href="comment.php?id=' . $rs->comment_id . '">' . $rs->post_title . '</a>';
                $info = [];
                if ($large) {
                    if ($author) {
                        $info[] = __('by') . ' ' . $rs->comment_author;
                    }
                    if ($date) {
                        $info[] = __('on') . ' ' . dt::dt2str($core->blog->settings->system->date_format, $rs->comment_dt);
                    }
                    if ($time) {
                        $info[] = __('at') . ' ' . dt::dt2str($core->blog->settings->system->time_format, $rs->comment_dt);
                    }
                } else {
                    if ($author) {
                        $info[] = $rs->comment_author;
                    }
                    if ($date) {
                        $info[] = dt::dt2str(__('%Y-%m-%d'), $rs->comment_dt);
                    }
                    if ($time) {
                        $info[] = dt::dt2str(__('%H:%M'), $rs->comment_dt);
                    }
                }
                if (count($info)) {
                    $ret .= ' (' . implode(' ', $info) . ')';
                }
                $ret .= '</li>';
            }
            $ret .= '</ul>';
            $ret .= '<p><a href="comments.php">' . __('See all comments') . '</a></p>';

            return $ret;
        } else {
            return '<p>' . __('No comments') .
                ($recents > 0 ? ' ' . sprintf(__('since %d hour', 'since %d hours', $recents), $recents) : '') . '</p>';
        }
    }

    public static function adminDashboardContents($core, $contents)
    {
        // Add modules to the contents stack
        $core->auth->user_prefs->addWorkspace('dmlastcomments');
        if ($core->auth->user_prefs->dmlastcomments->last_comments) {
            $class = ($core->auth->user_prefs->dmlastcomments->last_comments_large ? 'medium' : 'small');
            $ret   = '<div id="last-comments" class="box ' . $class . '">' .
            '<h3>' . '<img src="' . urldecode(dcPage::getPF('dmLastComments/icon.png')) . '" alt="" />' . ' ' . __('Last comments') . '</h3>';
            $ret .= dmLastCommentsBehaviors::getLastComments($core,
                $core->auth->user_prefs->dmlastcomments->last_comments_nb,
                $core->auth->user_prefs->dmlastcomments->last_comments_large,
                $core->auth->user_prefs->dmlastcomments->last_comments_author,
                $core->auth->user_prefs->dmlastcomments->last_comments_date,
                $core->auth->user_prefs->dmlastcomments->last_comments_time,
                $core->auth->user_prefs->dmlastcomments->last_comments_nospam,
                $core->auth->user_prefs->dmlastcomments->last_comments_recents);
            $ret .= '</div>';
            $contents[] = new ArrayObject([$ret]);
        }
    }

    public static function adminAfterDashboardOptionsUpdate($userID)
    {
        global $core;

        // Get and store user's prefs for plugin options
        $core->auth->user_prefs->addWorkspace('dmlastcomments');
        try {
            $core->auth->user_prefs->dmlastcomments->put('last_comments', !empty($_POST['dmlast_comments']), 'boolean');
            $core->auth->user_prefs->dmlastcomments->put('last_comments_nb', (integer) $_POST['dmlast_comments_nb'], 'integer');
            $core->auth->user_prefs->dmlastcomments->put('last_comments_large', empty($_POST['dmlast_comments_small']), 'boolean');
            $core->auth->user_prefs->dmlastcomments->put('last_comments_author', !empty($_POST['dmlast_comments_author']), 'boolean');
            $core->auth->user_prefs->dmlastcomments->put('last_comments_date', !empty($_POST['dmlast_comments_date']), 'boolean');
            $core->auth->user_prefs->dmlastcomments->put('last_comments_time', !empty($_POST['dmlast_comments_time']), 'boolean');
            $core->auth->user_prefs->dmlastcomments->put('last_comments_nospam', !empty($_POST['dmlast_comments_nospam']), 'boolean');
            $core->auth->user_prefs->dmlastcomments->put('last_comments_recents', (integer) $_POST['dmlast_comments_recents'], 'integer');
            $core->auth->user_prefs->dmlastcomments->put('last_comments_autorefresh', !empty($_POST['dmlast_comments_autorefresh']), 'boolean');
            $core->auth->user_prefs->dmlastcomments->put('last_comments_badge', !empty($_POST['dmlast_comments_badge']), 'boolean');
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }

    public static function adminDashboardOptionsForm($core)
    {
        // Add fieldset for plugin options
        $core->auth->user_prefs->addWorkspace('dmlastcomments');

        echo '<div class="fieldset" id="dmlastcomments"><h4>' . __('Last comments on dashboard') . '</h4>' .

        '<p>' .
        form::checkbox('dmlast_comments', 1, $core->auth->user_prefs->dmlastcomments->last_comments) . ' ' .
        '<label for="dmlast_comments" class="classic">' . __('Display last comments') . '</label></p>' .

        '<p><label for="dmlast_comments_nb" class="classic">' . __('Number of last comments to display:') . '</label> ' .
        form::number('dmlast_comments_nb', 1, 999, (integer) $core->auth->user_prefs->dmlastcomments->last_comments_nb) .
        '</p>' .

        '<p>' .
        form::checkbox('dmlast_comments_author', 1, $core->auth->user_prefs->dmlastcomments->last_comments_author) . ' ' .
        '<label for="dmlast_comments_author" class="classic">' . __('Show authors') . '</label></p>' .

        '<p>' .
        form::checkbox('dmlast_comments_date', 1, $core->auth->user_prefs->dmlastcomments->last_comments_date) . ' ' .
        '<label for="dmlast_comments_date" class="classic">' . __('Show dates') . '</label></p>' .

        '<p>' .
        form::checkbox('dmlast_comments_time', 1, $core->auth->user_prefs->dmlastcomments->last_comments_time) . ' ' .
        '<label for="dmlast_comments_time" class="classic">' . __('Show times') . '</label></p>' .

        '<p>' .
        form::checkbox('dmlast_comments_nospam', 1, $core->auth->user_prefs->dmlastcomments->last_comments_nospam) . ' ' .
        '<label for="dmlast_comments_nospam" class="classic">' . __('Exclude junk comments') . '</label></p>' .

        '<p><label for="dmlast_comments_recents" class="classic">' . __('Max age of comments to display (in hours):') . '</label> ' .
        form::number('dmlast_comments_recents', 1, 96, (integer) $core->auth->user_prefs->dmlastcomments->last_comments_recents) .
        '</p>' .
        '<p class="form-note">' . __('Leave empty to ignore age of comments') . '</p>' .

        '<p>' .
        form::checkbox('dmlast_comments_small', 1, !$core->auth->user_prefs->dmlastcomments->last_comments_large) . ' ' .
        '<label for="dmlast_comments_small" class="classic">' . __('Small screen') . '</label></p>' .

        '<p>' .
        form::checkbox('dmlast_comments_autorefresh', 1, $core->auth->user_prefs->dmlastcomments->last_comments_autorefresh) . ' ' .
        '<label for="dmlast_comments_autorefresh" class="classic">' . __('Auto refresh') . '</label></p>' .

        '<p>' .
        form::checkbox('dmlast_comments_badge', 1, $core->auth->user_prefs->dmlastcomments->last_comments_badge) . ' ' .
        '<label for="dmlast_comments_badge" class="classic">' . __('Display badges (only if Auto refresh is enabled)') . '</label></p>' .

            '</div>';
    }
}
