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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

// dead but useful code, in order to have translations
__('Last Comments Dashboard Module') . __('Display last comments on dashboard');

# BEHAVIORS
class dmLastCommentsBehaviors
{
    public static function adminDashboardHeaders()
    {
        $sqlp = [
            'limit'      => 1,                 // only the last one
            'no_content' => true,              // content is not required
            'order'      => 'comment_id DESC', // get last first
        ];

        $rs = dcCore::app()->blog->getComments($sqlp);

        if ($rs->count()) {
            $rs->fetch();
            $last_comment_id = $rs->comment_id;
        } else {
            $last_comment_id = -1;
        }

        dcCore::app()->auth->user_prefs->addWorkspace('dmlastcomments');

        return
        dcPage::jsJson('dm_lastcomments', [
            'dmLastComments_LastCommentId' => $last_comment_id,
            'dmLastComments_AutoRefresh'   => dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_autorefresh,
            'dmLastComments_Badge'         => dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_badge,
            'dmLastComments_LastCounter'   => 0,
            'dmLastComments_SpamCount'     => -1,
        ]) .
        dcPage::jsModuleLoad('dmLastComments/js/service.js', dcCore::app()->getVersion('dmLastComments')) .
        dcPage::cssModuleLoad('dmLastComments/css/style.css', 'screen', dcCore::app()->getVersion('dmLastComments'));
    }

    private static function composeSQLSince($core, $nb, $unit = 'HOUR')
    {
        switch (dcCore::app()->con->syntax()) {
            case 'sqlite':
                $ret = 'datetime(\'' .
                    dcCore::app()->con->db_escape_string('now') . '\', \'' .
                    dcCore::app()->con->db_escape_string('-' . sprintf($nb) . ' ' . $unit) .
                    '\')';

                break;
            case 'postgresql':
                $ret = '(NOW() - \'' . dcCore::app()->con->db_escape_string(sprintf($nb) . ' ' . $unit) . '\'::INTERVAL)';

                break;
            case 'mysql':
            default:
                $ret = '(NOW() - INTERVAL ' . sprintf($nb) . ' ' . $unit . ')';

                break;
        }

        return $ret;
    }

    public static function getLastComments(
        $core,
        $nb,
        $large,
        $author,
        $date,
        $time,
        $nospam,
        $recents = 0,
        $last_id = -1,
        &$last_counter = 0
    ) {
        $recents = (int) $recents;
        $nb      = (int) $nb;

        // Get last $nb comments
        $params = [];
        if ($nb > 0) {
            $params['limit'] = $nb;
        } else {
            $params['limit'] = 30; // As in first page of comments' list
        }
        if ($nospam) {
            // Exclude junk comment from list
            $params['comment_status_not'] = dcBlog::COMMENT_JUNK;
        }
        if ($recents > 0) {
            $params['sql'] = ' AND comment_dt >= ' . dmLastCommentsBehaviors::composeSQLSince(dcCore::app(), $recents) . ' ';
        }
        $rs = dcCore::app()->blog->getComments($params, false);
        if (!$rs->isEmpty()) {
            $ret = '<ul>';
            while ($rs->fetch()) {
                $ret .= '<li class="line';
                if ($last_id != -1 && $rs->comment_id > $last_id) {
                    $ret .= ($last_id != -1 && $rs->comment_id > $last_id ? ' dmlc-new' : '');
                    $last_counter++;
                }
                if ($rs->comment_status == dcBlog::COMMENT_JUNK) {
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
                        $info[] = __('on') . ' ' . dt::dt2str(dcCore::app()->blog->settings->system->date_format, $rs->comment_dt);
                    }
                    if ($time) {
                        $info[] = __('at') . ' ' . dt::dt2str(dcCore::app()->blog->settings->system->time_format, $rs->comment_dt);
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
        }

        return '<p>' . __('No comments') .
                ($recents > 0 ? ' ' . sprintf(__('since %d hour', 'since %d hours', $recents), $recents) : '') . '</p>';
    }

    public static function adminDashboardContents($contents)
    {
        // Add modules to the contents stack
        dcCore::app()->auth->user_prefs->addWorkspace('dmlastcomments');
        if (dcCore::app()->auth->user_prefs->dmlastcomments->last_comments) {
            $class = (dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_large ? 'medium' : 'small');
            $ret   = '<div id="last-comments" class="box ' . $class . '">' .
            '<h3>' . '<img src="' . urldecode(dcPage::getPF('dmLastComments/icon.png')) . '" alt="" />' . ' ' . __('Last comments') . '</h3>';
            $ret .= dmLastCommentsBehaviors::getLastComments(
                dcCore::app(),
                dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_nb,
                dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_large,
                dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_author,
                dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_date,
                dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_time,
                dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_nospam,
                dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_recents
            );
            $ret .= '</div>';
            $contents[] = new ArrayObject([$ret]);
        }
    }

    public static function adminAfterDashboardOptionsUpdate()
    {
        // Get and store user's prefs for plugin options
        dcCore::app()->auth->user_prefs->addWorkspace('dmlastcomments');

        try {
            dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments', !empty($_POST['dmlast_comments']), 'boolean');
            dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_nb', (int) $_POST['dmlast_comments_nb'], 'integer');
            dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_large', empty($_POST['dmlast_comments_small']), 'boolean');
            dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_author', !empty($_POST['dmlast_comments_author']), 'boolean');
            dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_date', !empty($_POST['dmlast_comments_date']), 'boolean');
            dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_time', !empty($_POST['dmlast_comments_time']), 'boolean');
            dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_nospam', !empty($_POST['dmlast_comments_nospam']), 'boolean');
            dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_recents', (int) $_POST['dmlast_comments_recents'], 'integer');
            dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_autorefresh', !empty($_POST['dmlast_comments_autorefresh']), 'boolean');
            dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_badge', !empty($_POST['dmlast_comments_badge']), 'boolean');
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    public static function adminDashboardOptionsForm()
    {
        // Add fieldset for plugin options
        dcCore::app()->auth->user_prefs->addWorkspace('dmlastcomments');

        echo '<div class="fieldset" id="dmlastcomments"><h4>' . __('Last comments on dashboard') . '</h4>' .

        '<p>' .
        form::checkbox('dmlast_comments', 1, dcCore::app()->auth->user_prefs->dmlastcomments->last_comments) . ' ' .
        '<label for="dmlast_comments" class="classic">' . __('Display last comments') . '</label></p>' .

        '<p><label for="dmlast_comments_nb" class="classic">' . __('Number of last comments to display:') . '</label> ' .
        form::number('dmlast_comments_nb', 1, 999, dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_nb) .
        '</p>' .

        '<p>' .
        form::checkbox('dmlast_comments_author', 1, dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_author) . ' ' .
        '<label for="dmlast_comments_author" class="classic">' . __('Show authors') . '</label></p>' .

        '<p>' .
        form::checkbox('dmlast_comments_date', 1, dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_date) . ' ' .
        '<label for="dmlast_comments_date" class="classic">' . __('Show dates') . '</label></p>' .

        '<p>' .
        form::checkbox('dmlast_comments_time', 1, dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_time) . ' ' .
        '<label for="dmlast_comments_time" class="classic">' . __('Show times') . '</label></p>' .

        '<p>' .
        form::checkbox('dmlast_comments_nospam', 1, dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_nospam) . ' ' .
        '<label for="dmlast_comments_nospam" class="classic">' . __('Exclude junk comments') . '</label></p>' .

        '<p><label for="dmlast_comments_recents" class="classic">' . __('Max age of comments to display (in hours):') . '</label> ' .
        form::number('dmlast_comments_recents', 1, 96, dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_recents) .
        '</p>' .
        '<p class="form-note">' . __('Leave empty to ignore age of comments') . '</p>' .

        '<p>' .
        form::checkbox('dmlast_comments_small', 1, !dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_large) . ' ' .
        '<label for="dmlast_comments_small" class="classic">' . __('Small screen') . '</label></p>' .

        '<p>' .
        form::checkbox('dmlast_comments_autorefresh', 1, dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_autorefresh) . ' ' .
        '<label for="dmlast_comments_autorefresh" class="classic">' . __('Auto refresh') . '</label></p>' .

        '<p>' .
        form::checkbox('dmlast_comments_badge', 1, dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_badge) . ' ' .
        '<label for="dmlast_comments_badge" class="classic">' . __('Display badges (only if Auto refresh is enabled)') . '</label></p>' .

            '</div>';
    }
}

// Dashboard behaviours
dcCore::app()->addBehavior('adminDashboardHeaders', [dmLastCommentsBehaviors::class, 'adminDashboardHeaders']);
dcCore::app()->addBehavior('adminDashboardContentsV2', [dmLastCommentsBehaviors::class, 'adminDashboardContents']);

dcCore::app()->addBehavior('adminAfterDashboardOptionsUpdate', [dmLastCommentsBehaviors::class, 'adminAfterDashboardOptionsUpdate']);
dcCore::app()->addBehavior('adminDashboardOptionsFormV2', [dmLastCommentsBehaviors::class, 'adminDashboardOptionsForm']);
