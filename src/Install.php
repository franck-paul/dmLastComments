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
use dcWorkspace;
use Dotclear\Core\Process;
use Exception;

class Install extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            $old_version = dcCore::app()->getVersion(My::id());
            if (version_compare((string) $old_version, '3.1', '<')) {
                // Rename settings workspace
                if (dcCore::app()->auth->user_prefs->exists('dmlastcomments')) {
                    dcCore::app()->auth->user_prefs->delWorkspace(My::id());
                    dcCore::app()->auth->user_prefs->renWorkspace('dmlastcomments', My::id());
                }

                // Change settings names (remove last_comments_ prefix in them)
                $rename = function (string $name, dcWorkspace $preferences): void {
                    if ($preferences->prefExists('last_comments_' . $name, true)) {
                        $preferences->rename('last_comments_' . $name, $name);
                    }
                };

                $preferences = My::prefs();
                foreach (['nb', 'large', 'author', 'date', 'time', 'nospam', 'recents', 'autorefresh', 'badge'] as $pref) {
                    $rename($pref, $preferences);
                }
                $preferences->rename('last_comments', 'active');
            }

            // Default prefs for last comments
            $preferences = My::prefs();

            $preferences->put('active', false, dcWorkspace::WS_BOOL, 'Display last comments', false, true);
            $preferences->put('nb', 5, dcWorkspace::WS_INT, 'Number of last comments displayed', false, true);
            $preferences->put('large', true, dcWorkspace::WS_BOOL, 'Large display', false, true);
            $preferences->put('author', true, dcWorkspace::WS_BOOL, 'Show authors', false, true);
            $preferences->put('date', true, dcWorkspace::WS_BOOL, 'Show dates', false, true);
            $preferences->put('time', true, dcWorkspace::WS_BOOL, 'Show times', false, true);
            $preferences->put('nospam', false, dcWorkspace::WS_BOOL, 'Exclude junk comments', false, true);
            $preferences->put('recents', 0, dcWorkspace::WS_INT, 'Max age of comments (in hours)', false, true);
            $preferences->put('autorefresh', false, dcWorkspace::WS_BOOL, 'Auto refresh', false, true);
            $preferences->put('interval', 30, dcWorkspace::WS_INT, 'Interval between two refreshes', false, true);
            $preferences->put('badge', true, dcWorkspace::WS_BOOL, 'Display counter (Auto refresh only)', false, true);
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }
}
