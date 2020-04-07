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
    '1.4',                                // Version
    [
        'requires'    => [['core', '2.16']],
        'permissions' => 'admin',
        'type'        => 'plugin',
        'details'     => 'https://open-time.net/?q=dmlastcomments',       // Details URL
        'support'     => 'https://github.com/franck-paul/dmLastComments', // Support URL
        'settings'    => [                                                // Settings
            'pref' => '#user-favorites.dmlastcomments'
        ]
    ]
);
