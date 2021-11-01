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
if (!defined('DC_RC_PATH')) {
    return;
}

$this->registerModule(
    'Last Comments Dashboard Module',     // Name
    'Display last comments on dashboard', // Description
    'Franck Paul',                        // Author
    '1.5',                                // Version
    [
        'requires'    => [['core', '2.19']],
        'permissions' => 'admin',
        'type'        => 'plugin',
        'settings'    => [                                                // Settings
            'pref' => '#user-favorites.dmlastcomments'
        ],

        'details'    => 'https://open-time.net/?q=dmlastcomments',       // Details URL
        'support'    => 'https://github.com/franck-paul/dmlastcomments', // Support URL
        'repository' => 'https://raw.githubusercontent.com/franck-paul/dmlastcomments/master/dcstore.xml'
    ]
);
