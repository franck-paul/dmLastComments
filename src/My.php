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
use dcPage;

/**
 * Plugin definitions
 */
class My
{
    /**
     * This module id
     */
    public static function id(): string
    {
        return basename(dirname(__DIR__));
    }

    /**
     * This module name
     */
    public static function name(): string
    {
        return __((string) dcCore::app()->plugins->moduleInfo(self::id(), 'name'));
    }

    /**
     * This module directory path
     */
    public static function path(): string
    {
        return dirname(__DIR__);
    }

    // Contexts

    /** @var int Install context */
    public const INSTALL = 0;

    /** @var int Prepend context */
    public const PREPEND = 1;

    /** @var int Frontend context */
    public const FRONTEND = 2;

    /** @var int Backend context (usually when the connected user may access at least one functionnality of this module) */
    public const BACKEND = 3;

    /** @var int Manage context (main page of module) */
    public const MANAGE = 4;

    /** @var int Config context (config page of module) */
    public const CONFIG = 5;

    /** @var int Menu context (adding a admin menu item) */
    public const MENU = 6;

    /** @var int Widgets context (managing blog's widgets) */
    public const WIDGETS = 7;

    /** @var int Uninstall context */
    public const UNINSTALL = 8;

    // User-defined contexts (10+)

    /**
     * Check permission depending on given context
     *
     * @param      int   $context  The context
     *
     * @return     bool  true if allowed, else false
     */
    public static function checkContext(int $context): bool
    {
        switch ($context) {
            case self::INSTALL:    // Installation of module
                return defined('DC_CONTEXT_ADMIN')
                    && dcCore::app()->auth->isSuperAdmin()   // Manageable only by super-admin
                    && dcCore::app()->newVersion(self::id(), dcCore::app()->plugins->moduleInfo(self::id(), 'version'))
                ;

            case self::UNINSTALL:  // Uninstallation of module
                return defined('DC_RC_PATH')
                    && dcCore::app()->auth->isSuperAdmin()   // Manageable only by super-admin
                ;

            case self::PREPEND:    // Prepend context
                return defined('DC_RC_PATH')
                ;

            case self::FRONTEND:    // Frontend context
                return defined('DC_RC_PATH')
                ;

            case self::BACKEND:     // Backend context
                return defined('DC_CONTEXT_ADMIN')
                    // Check specific permission
                    && dcCore::app()->blog && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                        dcCore::app()->auth::PERMISSION_ADMIN,  // Admin+
                    ]), dcCore::app()->blog->id)
                ;

            case self::MANAGE:      // Main page of module
                return defined('DC_CONTEXT_ADMIN')
                    // Check specific permission
                    && dcCore::app()->blog && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                        dcCore::app()->auth::PERMISSION_ADMIN,  // Admin+
                    ]), dcCore::app()->blog->id)
                ;

            case self::CONFIG:      // Config page of module
                return defined('DC_CONTEXT_ADMIN')
                    && dcCore::app()->auth->isSuperAdmin()   // Manageable only by super-admin
                ;

            case self::MENU:        // Admin menu
                return defined('DC_CONTEXT_ADMIN')
                    // Check specific permission
                    && dcCore::app()->blog && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                        dcCore::app()->auth::PERMISSION_ADMIN,  // Admin+
                    ]), dcCore::app()->blog->id)
                ;

            case self::WIDGETS:     // Blog widgets
                return defined('DC_CONTEXT_ADMIN')
                    // Check specific permission
                    && dcCore::app()->blog && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                        dcCore::app()->auth::PERMISSION_ADMIN,  // Admin+
                    ]), dcCore::app()->blog->id)
                ;
        }

        return false;
    }

    /**
     * Return array of module icon(s)
     *
     * [light_mode_icon_url, dark_mode_icon_url] or [both_modes_icon_url]
     *
     * @return     array
     */
    public static function icons(): array
    {
        return [
            urldecode(dcPage::getPF(self::id() . '/icon.svg')),
            //urldecode(dcPage::getPF(self::id() . '/icon-dark.svg')),
        ];
    }

    /**
     * Return URL regexp scheme cope by the plugin
     *
     * @return     string
     */
    public static function urlScheme(): string
    {
        return '/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.' . self::id())) . '(&.*)?$/';
    }

    /**
     * Makes an url including optionnal parameters.
     *
     * @param      array   $params  The parameters
     *
     * @return     string
     */
    public static function makeUrl(array $params = []): string
    {
        return dcCore::app()->adminurl->get('admin.plugin.' . self::id(), $params);
    }
}
