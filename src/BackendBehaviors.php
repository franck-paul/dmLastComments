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

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Text;
use Exception;

class BackendBehaviors
{
    public static function adminDashboardHeaders(): string
    {
        $sqlp = [
            'limit'      => 1,                 // only the last one
            'no_content' => true,              // content is not required
            'order'      => 'comment_id DESC', // get last first
        ];

        $rs = App::blog()->getComments($sqlp);

        if ($rs->count()) {
            $rs->fetch();
            $last_comment_id = $rs->comment_id;
        } else {
            $last_comment_id = -1;
        }

        $preferences = My::prefs();

        return
        Page::jsJson('dm_lastcomments', [
            'dmLastComments_LastCommentId' => $last_comment_id,
            'dmLastComments_AutoRefresh'   => $preferences->autorefresh,
            'dmLastComments_Badge'         => $preferences->badge,
            'dmLastComments_LastCounter'   => 0,
            'dmLastComments_SpamCount'     => -1,
            'dmLastComments_Interval'      => ($preferences->interval ?? 30),
        ]) .
        My::jsLoad('service.js') .
        My::cssLoad('style.css');
    }

    private static function composeSQLSince(int $nb, string $unit = 'HOUR'): string
    {
        return match (App::con()->syntax()) {
            'sqlite' => 'datetime(\'' .
                App::con()->escapeStr('now') . '\', \'' .
                App::con()->escapeStr('-' . sprintf('%d', $nb) . ' ' . $unit) .
                '\')',

            'postgresql' => '(NOW() - \'' . App::con()->escapeStr(sprintf('%d', $nb) . ' ' . $unit) . '\'::INTERVAL)',

            // default also stands for MySQL
            default => '(NOW() - INTERVAL ' . sprintf('%d', $nb) . ' ' . $unit . ')',
        };
    }

    public static function getLastComments(
        int $nb,
        bool $large,
        bool $author,
        bool $date,
        bool $time,
        bool $nospam,
        int $recents = 0,
        int $last_id = -1,
        int &$last_counter = 0
    ): string {
        // Get last $nb comments
        $params = [];
        if ($nb > 0) {
            $params['limit'] = $nb;
        } else {
            $params['limit'] = 30; // As in first page of comments' list
        }

        if ($nospam) {
            // Exclude junk comment from list
            $params['comment_status_not'] = App::status()->comment()::JUNK;
        }

        if ($recents > 0) {
            $params['sql'] = ' AND comment_dt >= ' . self::composeSQLSince($recents) . ' ';
        }

        $rs = App::blog()->getComments($params, false);
        if (!$rs->isEmpty()) {
            $ret = '<ul>';
            while ($rs->fetch()) {
                $ret .= '<li class="line';
                if ($last_id !== -1 && $rs->comment_id > $last_id) {
                    $ret .= ' dmlc-new';
                    ++$last_counter;
                }

                $ret .= ' sts-' . match ((int) $rs->comment_status) {
                    App::status()->comment()::JUNK        => 'junk',
                    App::status()->comment()::PENDING     => 'pending',
                    App::status()->comment()::PUBLISHED   => 'published',
                    App::status()->comment()::UNPUBLISHED => 'unpublished',
                    default                               => 'unknown',
                };

                $title = match ((int) $rs->comment_status) {
                    App::status()->comment()::JUNK        => __('Junk'),
                    App::status()->comment()::PENDING     => __('Pending'),
                    App::status()->comment()::PUBLISHED   => __('Published'),
                    App::status()->comment()::UNPUBLISHED => __('Unpublished'),
                    default                               => '',
                };

                $ret .= '" id="dmlc' . $rs->comment_id . '">';
                $ret .= '<a href="' . App::backend()->url()->get('admin.comment', ['id' => $rs->comment_id]) . '" title="' . $title . '">' . $rs->post_title . '</a>';
                $info = [];
                $dt   = '<time datetime="' . Date::iso8601((int) strtotime($rs->comment_dt), App::auth()->getInfo('user_tz')) . '">%s</time>';
                if ($large) {
                    if ($author) {
                        $info[] = __('by') . ' ' . $rs->comment_author;
                    }

                    if ($date) {
                        $info[] = sprintf($dt, __('on') . ' ' . Date::dt2str(App::blog()->settings()->system->date_format, $rs->comment_dt));
                    }

                    if ($time) {
                        $info[] = sprintf($dt, __('at') . ' ' . Date::dt2str(App::blog()->settings()->system->time_format, $rs->comment_dt));
                    }
                } else {
                    if ($author) {
                        $info[] = $rs->comment_author;
                    }

                    if ($date) {
                        $info[] = sprintf($dt, Date::dt2str(__('%Y-%m-%d'), $rs->comment_dt));
                    }

                    if ($time) {
                        $info[] = sprintf($dt, Date::dt2str(__('%H:%M'), $rs->comment_dt));
                    }
                }

                if ($info !== []) {
                    $ret .= ' (' . implode(' ', $info) . ')';
                }

                $ret .= '</li>';
            }

            $ret .= '</ul>';

            return $ret . ('<p><a href="' . App::backend()->url()->get('admin.comments') . '">' . __('See all comments') . '</a></p>');
        }

        return '<p>' . __('No comments') .
                ($recents > 0 ? ' ' . sprintf(__('since %d hour', 'since %d hours', $recents), $recents) : '') . '</p>';
    }

    /**
     * @param      ArrayObject<int, ArrayObject<int, non-falsy-string>>  $contents  The contents
     */
    public static function adminDashboardContents(ArrayObject $contents): string
    {
        $preferences = My::prefs();

        // Add modules to the contents stack
        if ($preferences->active) {
            $class = ($preferences->large ? 'medium' : 'small');
            $ret   = '<div id="last-comments" class="box ' . $class . '">' .
            '<h3>' . '<img src="' . urldecode(Page::getPF(My::id() . '/icon.svg')) . '" alt="" class="icon-small">' . ' ' . __('Last comments') . '</h3>';
            $ret .= self::getLastComments(
                $preferences->nb,
                $preferences->large,
                $preferences->author,
                $preferences->date,
                $preferences->time,
                $preferences->nospam,
                $preferences->recents
            );
            $ret .= '</div>';
            $contents->append(new ArrayObject([$ret]));
        }

        return '';
    }

    public static function adminAfterDashboardOptionsUpdate(): string
    {
        $preferences = My::prefs();

        // Get and store user's prefs for plugin options
        try {
            $preferences->put('active', !empty($_POST['dmlast_comments']), App::userWorkspace()::WS_BOOL);
            $preferences->put('nb', (int) $_POST['dmlast_comments_nb'], App::userWorkspace()::WS_INT);
            $preferences->put('large', empty($_POST['dmlast_comments_small']), App::userWorkspace()::WS_BOOL);
            $preferences->put('author', !empty($_POST['dmlast_comments_author']), App::userWorkspace()::WS_BOOL);
            $preferences->put('date', !empty($_POST['dmlast_comments_date']), App::userWorkspace()::WS_BOOL);
            $preferences->put('time', !empty($_POST['dmlast_comments_time']), App::userWorkspace()::WS_BOOL);
            $preferences->put('nospam', !empty($_POST['dmlast_comments_nospam']), App::userWorkspace()::WS_BOOL);
            $preferences->put('recents', (int) $_POST['dmlast_comments_recents'], App::userWorkspace()::WS_INT);
            $preferences->put('autorefresh', !empty($_POST['dmlast_comments_autorefresh']), App::userWorkspace()::WS_BOOL);
            $preferences->put('interval', (int) $_POST['dmlast_comments_interval'], App::userWorkspace()::WS_INT);
            $preferences->put('badge', !empty($_POST['dmlast_comments_badge']), App::userWorkspace()::WS_BOOL);
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return '';
    }

    public static function adminDashboardOptionsForm(): string
    {
        $preferences = My::prefs();

        // Add fieldset for plugin options
        echo
        (new Fieldset('dmlastcomments'))
        ->legend((new Legend(__('Last comments on dashboard'))))
        ->fields([
            (new Para())->items([
                (new Checkbox('dmlast_comments', $preferences->active))
                    ->value(1)
                    ->label((new Label(__('Display last comments'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Number('dmlast_comments_nb', 1, 999, $preferences->nb))
                    ->label((new Label(__('Number of last comments to display:'), Label::INSIDE_TEXT_BEFORE))),
            ]),
            (new Para())->items([
                (new Checkbox('dmlast_comments_author', $preferences->author))
                    ->value(1)
                    ->label((new Label(__('Show authors'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Checkbox('dmlast_comments_date', $preferences->date))
                    ->value(1)
                    ->label((new Label(__('Show dates'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Checkbox('dmlast_comments_time', $preferences->time))
                    ->value(1)
                    ->label((new Label(__('Show times'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Checkbox('dmlast_comments_nospam', $preferences->nospam))
                    ->value(1)
                    ->label((new Label(__('Exclude junk comments'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Number('dmlast_comments_recents', 0, 96, $preferences->recents))
                    ->label((new Label(__('Max age of comments to display (in hours):'), Label::INSIDE_TEXT_BEFORE))),
            ]),
            (new Para())->class('form-note')->items([
                (new Text(null, __('Leave empty to ignore age of comments'))),
            ]),
            (new Para())->items([
                (new Checkbox('dmlast_comments_small', !$preferences->large))
                    ->value(1)
                    ->label((new Label(__('Small screen'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Checkbox('dmlast_comments_autorefresh', $preferences->autorefresh))
                    ->value(1)
                    ->label((new Label(__('Auto refresh'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Number('dmlast_comments_interval', 0, 9_999_999, $preferences->interval))
                    ->label((new Label(__('Interval in seconds between two refreshes:'), Label::INSIDE_TEXT_BEFORE))),
            ]),
            (new Para())->items([
                (new Checkbox('dmlast_comments_badge', $preferences->badge))
                    ->value(1)
                    ->label((new Label(__('Display badges (only if Auto refresh is enabled)'), Label::INSIDE_TEXT_AFTER))),
            ]),
        ])
        ->render();

        return '';
    }
}
