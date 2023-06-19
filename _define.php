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
    '3.3',
    [
        'requires'    => [['core', '2.26']],
        'permissions' => dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_ADMIN,
        ]),
        'type'     => 'plugin',
        'settings' => [
            'pref' => '#user-favorites.dmlastcomments',
        ],

        'details'    => 'https://open-time.net/?q=dmlastcomments',
        'support'    => 'https://github.com/franck-paul/dmlastcomments',
        'repository' => 'https://raw.githubusercontent.com/franck-paul/dmlastcomments/master/dcstore.xml',
    ]
);
