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

use Dotclear\App;

class BackendRest
{
    /**
     * Gets the spam count.
     *
     * @return     array<string, mixed>   The payload.
     */
    public static function getSpamCount(): array
    {
        $count = App::blog()->getComments(['comment_status' => App::blog()::COMMENT_JUNK], true)->f(0);

        return [
            'ret' => true,
            'nb'  => $count,
        ];
    }

    /**
     * Serve method to check new comments for current blog.
     *
     * @param      array<string, string>   $get    The get
     *
     * @return     array<string, mixed>   The payload.
     */
    public static function checkNewComments($get): array
    {
        $preferences = My::prefs();
        if (!$preferences) {
            return [
                'ret' => false,
            ];
        }

        $last_id         = empty($get['last_id']) ? -1 : $get['last_id'];
        $last_comment_id = -1;

        $sqlp = [
            'no_content' => true, // content is not required
            'order'      => 'comment_id ASC',
            'sql'        => 'AND comment_id > ' . $last_id, // only new ones
        ];
        if ($preferences->nospam) {
            // Exclude junk comment from list
            $sqlp['comment_status_not'] = App::blog()::COMMENT_JUNK;
        }

        $rs    = App::blog()->getComments($sqlp);
        $count = $rs->count();

        if ($count) {
            while ($rs->fetch()) {
                $last_comment_id = (int) $rs->comment_id;
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
     * @param      array<string, string>   $get    The get
     *
     * @return     array<string, mixed>   The payload.
     */
    public static function getLastCommentsRows($get): array
    {
        $stored_id = empty($get['stored_id']) ? -1 : (int) $get['stored_id'];
        $last_id   = empty($get['last_id']) ? -1 : (int) $get['last_id'];
        $counter   = empty($get['counter']) ? 0 : (int) $get['counter'];

        $payload = [
            'ret'       => true,
            'stored_id' => $stored_id,
            'last_id'   => $last_id,
            'counter'   => 0,
        ];

        $preferences = My::prefs();
        if (!$preferences) {
            return $payload;
        }

        $list = BackendBehaviors::getLastComments(
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

        return [...$payload, 'list' => $list, 'counter' => $counter];
    }
}
