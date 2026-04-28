<?php

/**
 * @brief dmLastComments, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul contact@open-time.net
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
        $count = is_numeric($count = App::blog()->getComments(['comment_status' => App::status()->comment()::JUNK], true)->f(0)) ? (int) $count : 0;

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
    public static function checkNewComments(array $get): array
    {
        $preferences = My::prefs();

        $last_id         = empty($get['last_id']) ? -1 : $get['last_id'];
        $last_comment_id = -1;

        $sqlp = [
            'no_content' => true, // content is not required
            'order'      => 'comment_id ASC',
            'sql'        => 'AND comment_id > ' . $last_id, // only new ones
        ];
        if ($preferences->nospam) {
            // Exclude junk comment from list
            $sqlp['comment_status_not'] = App::status()->comment()::JUNK;
        }

        $rs    = App::blog()->getComments($sqlp);
        $count = $rs->count();

        if ($count) {
            while ($rs->fetch()) {
                $last_comment_id = is_numeric($last_comment_id = $rs->comment_id) ? (int) $last_comment_id : -1;
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
    public static function getLastCommentsRows(array $get): array
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

        // Variable data helpers
        $_Bool = fn (mixed $var): bool => (bool) $var;
        $_Int  = fn (mixed $var, int $default = 0): int => $var !== null && is_numeric($val = $var) ? (int) $val : $default;

        $preferences = My::prefs();

        $list = BackendBehaviors::getLastComments(
            $_Int($preferences->nb),
            $_Bool($preferences->large),
            $_Bool($preferences->author),
            $_Bool($preferences->date),
            $_Bool($preferences->time),
            $_Bool($preferences->nospam),
            $_Int($preferences->recents),
            $_Int($stored_id),
            $counter
        );

        return [
            ...$payload,
            'list'    => $list,
            'counter' => $counter,
        ];
    }
}
