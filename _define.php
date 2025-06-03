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
    '8.1',
    [
        'date'     => '2025-06-03T12:00:11+0200',
        'requires' => [
            ['core', '2.34'],
            ['dmHelper', '5.0'],
        ],
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
