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
use dcBlog;
use dcCore;
use dcPage;
use dcWorkspace;
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

        $preferences = dcCore::app()->auth->user_prefs->get(My::id());

        return
        dcPage::jsJson('dm_lastcomments', [
            'dmLastComments_LastCommentId' => $last_comment_id,
            'dmLastComments_AutoRefresh'   => $preferences->autorefresh,
            'dmLastComments_Badge'         => $preferences->badge,
            'dmLastComments_LastCounter'   => 0,
            'dmLastComments_SpamCount'     => -1,
            'dmLastComments_Interval'      => ($preferences->interval ?? 30),
        ]) .
        dcPage::jsModuleLoad(My::id() . '/js/service.js', dcCore::app()->getVersion(My::id())) .
        dcPage::cssModuleLoad(My::id() . '/css/style.css', 'screen', dcCore::app()->getVersion(My::id()));
    }

    private static function composeSQLSince(int $nb, string $unit = 'HOUR')
    {
        switch (dcCore::app()->con->syntax()) {
            case 'sqlite':
                $ret = 'datetime(\'' .
                    dcCore::app()->con->escape('now') . '\', \'' .
                    dcCore::app()->con->escape('-' . sprintf('%d', $nb) . ' ' . $unit) .
                    '\')';

                break;
            case 'postgresql':
                $ret = '(NOW() - \'' . dcCore::app()->con->escape(sprintf('%d', $nb) . ' ' . $unit) . '\'::INTERVAL)';

                break;
            case 'mysql':
            default:
                $ret = '(NOW() - INTERVAL ' . sprintf('%d', $nb) . ' ' . $unit . ')';

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
            $params['sql'] = ' AND comment_dt >= ' . self::composeSQLSince($recents) . ' ';
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
                $ret .= '<a href="' . dcCore::app()->adminurl->get('admin.comment', ['id' => $rs->comment_id]) . '">' . $rs->post_title . '</a>';
                $info = [];
                $dt   = '<time datetime="' . Date::iso8601(strtotime($rs->comment_dt), dcCore::app()->auth->getInfo('user_tz')) . '">%s</time>';
                if ($large) {
                    if ($author) {
                        $info[] = __('by') . ' ' . $rs->comment_author;
                    }
                    if ($date) {
                        $info[] = sprintf($dt, __('on') . ' ' . Date::dt2str(dcCore::app()->blog->settings->system->date_format, $rs->comment_dt));
                    }
                    if ($time) {
                        $info[] = sprintf($dt, __('at') . ' ' . Date::dt2str(dcCore::app()->blog->settings->system->time_format, $rs->comment_dt));
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
                if (count($info)) {
                    $ret .= ' (' . implode(' ', $info) . ')';
                }
                $ret .= '</li>';
            }
            $ret .= '</ul>';
            $ret .= '<p><a href="' . dcCore::app()->adminurl->get('admin.comments') . '">' . __('See all comments') . '</a></p>';

            return $ret;
        }

        return '<p>' . __('No comments') .
                ($recents > 0 ? ' ' . sprintf(__('since %d hour', 'since %d hours', $recents), $recents) : '') . '</p>';
    }

    public static function adminDashboardContents($contents)
    {
        $preferences = dcCore::app()->auth->user_prefs->get(My::id());

        // Add modules to the contents stack
        if ($preferences->active) {
            $class = ($preferences->large ? 'medium' : 'small');
            $ret   = '<div id="last-comments" class="box ' . $class . '">' .
            '<h3>' . '<img src="' . urldecode(dcPage::getPF(My::id() . '/icon.svg')) . '" alt="" class="icon-small" />' . ' ' . __('Last comments') . '</h3>';
            $ret .= self::getLastComments(
                dcCore::app(),
                $preferences->nb,
                $preferences->large,
                $preferences->author,
                $preferences->date,
                $preferences->time,
                $preferences->nospam,
                $preferences->recents
            );
            $ret .= '</div>';
            $contents[] = new ArrayObject([$ret]);
        }
    }

    public static function adminAfterDashboardOptionsUpdate()
    {
        $preferences = dcCore::app()->auth->user_prefs->get(My::id());

        // Get and store user's prefs for plugin options
        try {
            $preferences->put('active', !empty($_POST['dmlast_comments']), dcWorkspace::WS_BOOL);
            $preferences->put('nb', (int) $_POST['dmlast_comments_nb'], dcWorkspace::WS_INT);
            $preferences->put('large', empty($_POST['dmlast_comments_small']), dcWorkspace::WS_BOOL);
            $preferences->put('author', !empty($_POST['dmlast_comments_author']), dcWorkspace::WS_BOOL);
            $preferences->put('date', !empty($_POST['dmlast_comments_date']), dcWorkspace::WS_BOOL);
            $preferences->put('time', !empty($_POST['dmlast_comments_time']), dcWorkspace::WS_BOOL);
            $preferences->put('nospam', !empty($_POST['dmlast_comments_nospam']), dcWorkspace::WS_BOOL);
            $preferences->put('recents', (int) $_POST['dmlast_comments_recents'], dcWorkspace::WS_INT);
            $preferences->put('autorefresh', !empty($_POST['dmlast_comments_autorefresh']), dcWorkspace::WS_BOOL);
            $preferences->put('interval', (int) $_POST['dmlast_comments_interval'], dcWorkspace::WS_INT);
            $preferences->put('badge', !empty($_POST['dmlast_comments_badge']), dcWorkspace::WS_BOOL);
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    public static function adminDashboardOptionsForm()
    {
        $preferences = dcCore::app()->auth->user_prefs->get(My::id());

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
    }
}
