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
use Dotclear\Core\Process;

class Backend extends Process
{
    public static function init(): bool
    {
        // dead but useful code, in order to have translations
        __('Last Comments Dashboard Module') . __('Display last comments on dashboard');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        dcCore::app()->addBehaviors([
            // Dashboard behaviours
            'adminDashboardHeaders'    => BackendBehaviors::adminDashboardHeaders(...),
            'adminDashboardContentsV2' => BackendBehaviors::adminDashboardContents(...),

            'adminAfterDashboardOptionsUpdate' => BackendBehaviors::adminAfterDashboardOptionsUpdate(...),
            'adminDashboardOptionsFormV2'      => BackendBehaviors::adminDashboardOptionsForm(...),
        ]);

        // Register REST methods
        dcCore::app()->rest->addFunction('dmLastCommentsCheck', BackendRest::checkNewComments(...));
        dcCore::app()->rest->addFunction('dmLastCommentsRows', BackendRest::getLastCommentsRows(...));
        dcCore::app()->rest->addFunction('dmLastCommentsSpam', BackendRest::getSpamCount(...));

        return true;
    }
}
