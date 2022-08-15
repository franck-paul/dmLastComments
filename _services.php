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

class dmLastCommentsRest
{
    /**
     * Gets the spam count.
     *
     * @param      array   $get    The get
     *
     * @return     xmlTag  The spam count.
     */
    public static function getSpamCount($get)
    {
        $count = dcCore::app()->blog->getComments(['comment_status' => -2], true)->f(0);

        return [
            'ret' => true,
            'nb'  => $count,
        ];
    }

    /**
     * Serve method to check new comments for current blog.
     *
     * @param      array   $get    The get
     *
     * @return     xmlTag  The xml tag.
     */
    public static function checkNewComments($get)
    {
        $last_id         = !empty($get['last_id']) ? $get['last_id'] : -1;
        $last_comment_id = -1;

        $sqlp = [
            'no_content' => true, // content is not required
            'order'      => 'comment_id ASC',
            'sql'        => 'AND comment_id > ' . $last_id, // only new ones
        ];
        dcCore::app()->auth->user_prefs->addWorkspace('dmlastcomments');
        if (dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_nospam) {
            // Exclude junk comment from list
            $sqlp['comment_status_not'] = -2;
        }

        $rs    = dcCore::app()->blog->getComments($sqlp);
        $count = $rs->count();

        if ($count) {
            while ($rs->fetch()) {
                $last_comment_id = $rs->comment_id;
            }
        }

        return [
            'ret'     => true,
            'nb'      => $count,
            'last_id' => $last_comment_id,
        ];
    }

    /**
     * Gets the last comments rows.
     *
     * @param      array   $get    The get
     *
     * @return     xmlTag  The last comments rows.
     */
    public static function getLastCommentsRows($get)
    {
        $stored_id = !empty($get['stored_id']) ? $get['stored_id'] : -1;
        $last_id   = !empty($get['last_id']) ? $get['last_id'] : -1;
        $counter   = !empty($get['counter']) ? $get['counter'] : 0;

        $payload = [
            'ret'       => true,
            'stored_id' => $stored_id,
            'last_id'   => $last_id,
            'counter'   => 0,
        ];

        if ($stored_id == -1) {
            return $payload;
        }

        dcCore::app()->auth->user_prefs->addWorkspace('dmlastcomments');
        $list = dmLastCommentsBehaviors::getLastComments(
            dcCore::app(),
            dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_nb,
            dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_large,
            dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_author,
            dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_date,
            dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_time,
            dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_nospam,
            dcCore::app()->auth->user_prefs->dmlastcomments->last_comments_recents,
            $stored_id,
            $counter
        );

        return array_merge($payload, ['list' => $list, 'counter' => $counter]);
    }
}
