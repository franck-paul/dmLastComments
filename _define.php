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

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Last Comments Dashboard Module",     // Name
    "Display last comments on dashboard", // Description
    "Franck Paul",                        // Author
    '1.2',                                // Version
    [
        'requires'    => [['core', '2.8']],
        'permissions' => 'admin',
        'support'     => 'https://open-time.net/?q=dmlastcomments', // Support URL
        'type'        => 'plugin',
        'settings'    => ['pref' => '#user-favorites.dmlastcomments'] // Settings
    ]
);
