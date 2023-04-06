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

use dcCore;
use dcNsProcess;
use Exception;

class Install extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN')
            && My::phpCompliant()
            && dcCore::app()->newVersion(My::id(), dcCore::app()->plugins->moduleInfo(My::id(), 'version'));

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        try {
            // Default prefs for last comments
            $settings = dcCore::app()->auth->user_prefs->dmlastcomments;

            $settings->put('last_comments', false, 'boolean', 'Display last comments', false, true);
            $settings->put('last_comments_nb', 5, 'integer', 'Number of last comments displayed', false, true);
            $settings->put('last_comments_large', true, 'boolean', 'Large display', false, true);
            $settings->put('last_comments_author', true, 'boolean', 'Show authors', false, true);
            $settings->put('last_comments_date', true, 'boolean', 'Show dates', false, true);
            $settings->put('last_comments_time', true, 'boolean', 'Show times', false, true);
            $settings->put('last_comments_nospam', false, 'boolean', 'Exclude junk comments', false, true);
            $settings->put('last_comments_recents', 0, 'integer', 'Max age of comments (in hours)', false, true);
            $settings->put('last_comments_autorefresh', false, 'boolean', 'Auto refresh', false, true);
            $settings->put('last_comments_badge', true, 'boolean', 'Display counter (Auto refresh only)', false, true);

            return true;
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }
}
