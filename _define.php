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
$this->registerModule(
    'Last Comments Dashboard Module',
    'Display last comments on dashboard',
    'Franck Paul',
    '7.2',
    [
        'date'        => '2025-03-06T00:22:39+0100',
        'requires'    => [['core', '2.33']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'settings'    => [
            'pref' => '#user-favorites.dmlastcomments',
        ],

        'details'    => 'https://open-time.net/?q=dmlastcomments',
        'support'    => 'https://github.com/franck-paul/dmlastcomments',
        'repository' => 'https://raw.githubusercontent.com/franck-paul/dmlastcomments/main/dcstore.xml',
        'license'    => 'gpl2',
    ]
);
