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
declare(strict_types=1);

namespace Dotclear\Plugin\dmLastComments;

use dcBlog;
use dcCore;

class BackendRest
{
    /**
     * Gets the spam count.
     *
     * @return     array   The payload.
     */
    public static function getSpamCount(): array
    {
        $count = dcCore::app()->blog->getComments(['comment_status' => dcBlog::COMMENT_JUNK], true)->f(0);

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
     * @return     array   The payload.
     */
    public static function checkNewComments($get): array
    {
        $preferences = My::prefs();

        $last_id         = !empty($get['last_id']) ? $get['last_id'] : -1;
        $last_comment_id = -1;

        $sqlp = [
            'no_content' => true, // content is not required
            'order'      => 'comment_id ASC',
            'sql'        => 'AND comment_id > ' . $last_id, // only new ones
        ];
        if ($preferences->nospam) {
            // Exclude junk comment from list
            $sqlp['comment_status_not'] = dcBlog::COMMENT_JUNK;
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
     * @return     array   The payload.
     */
    public static function getLastCommentsRows($get): array
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

        $preferences = My::prefs();

        $list = BackendBehaviors::getLastComments(
            dcCore::app(),
            $preferences->nb,
            $preferences->large,
            $preferences->author,
            $preferences->date,
            $preferences->time,
            $preferences->nospam,
            $preferences->recents,
            $stored_id,
            $counter
        );

        return array_merge($payload, ['list' => $list, 'counter' => $counter]);
    }
}
