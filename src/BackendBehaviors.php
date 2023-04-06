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

        $settings = dcCore::app()->auth->user_prefs->dmlastcomments;

        return
        dcPage::jsJson('dm_lastcomments', [
            'dmLastComments_LastCommentId' => $last_comment_id,
            'dmLastComments_AutoRefresh'   => $settings->last_comments_autorefresh,
            'dmLastComments_Badge'         => $settings->last_comments_badge,
            'dmLastComments_LastCounter'   => 0,
            'dmLastComments_SpamCount'     => -1,
        ]) .
        dcPage::jsModuleLoad('dmLastComments/js/service.js', dcCore::app()->getVersion('dmLastComments')) .
        dcPage::cssModuleLoad('dmLastComments/css/style.css', 'screen', dcCore::app()->getVersion('dmLastComments'));
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
                $ret .= '<a href="comment.php?id=' . $rs->comment_id . '">' . $rs->post_title . '</a>';
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
            $ret .= '<p><a href="comments.php">' . __('See all comments') . '</a></p>';

            return $ret;
        }

        return '<p>' . __('No comments') .
                ($recents > 0 ? ' ' . sprintf(__('since %d hour', 'since %d hours', $recents), $recents) : '') . '</p>';
    }

    public static function adminDashboardContents($contents)
    {
        $settings = dcCore::app()->auth->user_prefs->dmlastcomments;

        // Add modules to the contents stack
        if ($settings->last_comments) {
            $class = ($settings->last_comments_large ? 'medium' : 'small');
            $ret   = '<div id="last-comments" class="box ' . $class . '">' .
            '<h3>' . '<img src="' . urldecode(dcPage::getPF('dmLastComments/icon.png')) . '" alt="" />' . ' ' . __('Last comments') . '</h3>';
            $ret .= self::getLastComments(
                dcCore::app(),
                $settings->last_comments_nb,
                $settings->last_comments_large,
                $settings->last_comments_author,
                $settings->last_comments_date,
                $settings->last_comments_time,
                $settings->last_comments_nospam,
                $settings->last_comments_recents
            );
            $ret .= '</div>';
            $contents[] = new ArrayObject([$ret]);
        }
    }

    public static function adminAfterDashboardOptionsUpdate()
    {
        $settings = dcCore::app()->auth->user_prefs->dmlastcomments;

        // Get and store user's prefs for plugin options
        try {
            $settings->put('last_comments', !empty($_POST['dmlast_comments']), 'boolean');
            $settings->put('last_comments_nb', (int) $_POST['dmlast_comments_nb'], 'integer');
            $settings->put('last_comments_large', empty($_POST['dmlast_comments_small']), 'boolean');
            $settings->put('last_comments_author', !empty($_POST['dmlast_comments_author']), 'boolean');
            $settings->put('last_comments_date', !empty($_POST['dmlast_comments_date']), 'boolean');
            $settings->put('last_comments_time', !empty($_POST['dmlast_comments_time']), 'boolean');
            $settings->put('last_comments_nospam', !empty($_POST['dmlast_comments_nospam']), 'boolean');
            $settings->put('last_comments_recents', (int) $_POST['dmlast_comments_recents'], 'integer');
            $settings->put('last_comments_autorefresh', !empty($_POST['dmlast_comments_autorefresh']), 'boolean');
            $settings->put('last_comments_badge', !empty($_POST['dmlast_comments_badge']), 'boolean');
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    public static function adminDashboardOptionsForm()
    {
        $settings = dcCore::app()->auth->user_prefs->dmlastcomments;

        // Add fieldset for plugin options
        // Add fieldset for plugin options
        echo
        (new Fieldset('dmlastcomments'))
        ->legend((new Legend(__('Last comments on dashboard'))))
        ->fields([
            (new Para())->items([
                (new Checkbox('dmlast_comments', $settings->last_comments))
                    ->value(1)
                    ->label((new Label(__('Display last comments'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Number('dmlast_comments_nb', 1, 999, $settings->last_comments_nb))
                    ->label((new Label(__('Number of last comments to display:'), Label::INSIDE_TEXT_BEFORE))),
            ]),
            (new Para())->items([
                (new Checkbox('dmlast_comments_author', $settings->last_comments_author))
                    ->value(1)
                    ->label((new Label(__('Show authors'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Checkbox('dmlast_comments_date', $settings->last_comments_date))
                    ->value(1)
                    ->label((new Label(__('Show dates'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Checkbox('dmlast_comments_time', $settings->last_comments_time))
                    ->value(1)
                    ->label((new Label(__('Show times'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Checkbox('dmlast_comments_nospam', $settings->last_comments_nospam))
                    ->value(1)
                    ->label((new Label(__('Exclude junk comments'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Number('dmlast_comments_recents', 1, 96, $settings->last_comments_recents))
                    ->label((new Label(__('Max age of comments to display (in hours):'), Label::INSIDE_TEXT_BEFORE))),
            ]),
            (new Para())->class('form-note')->items([
                (new Text(null, __('Leave empty to ignore age of comments'))),
            ]),
            (new Para())->items([
                (new Checkbox('dmlast_comments_small', $settings->last_comments_large))
                    ->value(1)
                    ->label((new Label(__('Small screen'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Checkbox('dmlast_comments_autorefresh', $settings->last_comments_autorefresh))
                    ->value(1)
                    ->label((new Label(__('Auto refresh'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Checkbox('dmlast_comments_badge', $settings->last_comments_badge))
                    ->value(1)
                    ->label((new Label(__('Display badges (only if Auto refresh is enabled)'), Label::INSIDE_TEXT_AFTER))),
            ]),
        ])
        ->render();
    }
}
