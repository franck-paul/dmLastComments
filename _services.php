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

class dmLastCommentsRest
{
    /**
     * Serve method to get number of spams for current blog.
     *
     * @param     core     <b>dcCore</b>     dcCore instance
     * @param     get     <b>array</b>     cleaned $_GET
     */
    public static function getSpamCount($core, $get)
    {
        $count = $core->blog->getComments(['comment_status' => -2], true)->f(0);

        $rsp      = new xmlTag('check');
        $rsp->ret = $count;

        return $rsp;
    }

    /**
     * Serve method to check new comments for current blog.
     *
     * @param    core    <b>dcCore</b>    dcCore instance
     * @param    get        <b>array</b>    cleaned $_GET
     *
     * @return    <b>xmlTag</b>    XML representation of response
     */
    public static function checkNewComments($core, $get)
    {
        $last_id = !empty($get['last_id']) ? $get['last_id'] : -1;

        $sqlp = [
            'no_content' => true, // content is not required
            'order'      => 'comment_id ASC',
            'sql'        => 'AND comment_id > ' . $last_id // only new ones
        ];
        $core->auth->user_prefs->addWorkspace('dmlastcomments');
        if ($core->auth->user_prefs->dmlastcomments->last_comments_nospam) {
            // Exclude junk comment from list
            $sqlp['comment_status_not'] = -2;
        }

        $rs    = $core->blog->getComments($sqlp);
        $count = $rs->count();

        if ($count) {
            while ($rs->fetch()) {
                $last_comment_id = $rs->comment_id;
            }
        }
        $rsp      = new xmlTag('check');
        $rsp->ret = $count;
        if ($count) {
            $rsp->last_id = $last_comment_id;
        }

        return $rsp;
    }

    /**
     * Serve method to get new comments rows for current blog.
     *
     * @param    core    <b>dcCore</b>    dcCore instance
     * @param    get        <b>array</b>    cleaned $_GET
     *
     * @return    <b>xmlTag</b>    XML representation of response
     */
    public static function getLastCommentsRows($core, $get)
    {
        $rsp      = new xmlTag('rows');
        $rsp->ret = 0;

        $stored_id = !empty($get['stored_id']) ? $get['stored_id'] : -1;
        $last_id   = !empty($get['last_id']) ? $get['last_id'] : -1;
        $counter   = !empty($get['counter']) ? $get['counter'] : 0;

        $rsp->stored_id = $stored_id;
        $rsp->last_id   = $last_id;

        if ($stored_id == -1) {
            return $rsp;
        }

        $core->auth->user_prefs->addWorkspace('dmlastcomments');
        $ret = dmLastCommentsBehaviors::getLastComments($core,
            $core->auth->user_prefs->dmlastcomments->last_comments_nb,
            $core->auth->user_prefs->dmlastcomments->last_comments_large,
            $core->auth->user_prefs->dmlastcomments->last_comments_author,
            $core->auth->user_prefs->dmlastcomments->last_comments_date,
            $core->auth->user_prefs->dmlastcomments->last_comments_time,
            $core->auth->user_prefs->dmlastcomments->last_comments_nospam,
            $core->auth->user_prefs->dmlastcomments->last_comments_recents,
            $stored_id, $counter);

        $rsp->list    = $ret;
        $rsp->counter = $counter;
        $rsp->ret     = 1;

        return $rsp;
    }
}
