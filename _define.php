<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of dmLastComments, a plugin for Dotclear 2.
#
# Copyright (c) Franck Paul and contributors
# carnet.franck.paul@gmail.com
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Last Comments Dashboard Module",     // Name
    "Display last comments on dashboard", // Description
    "Franck Paul",                        // Author
    '1.2',                                // Version
    array(
        'requires'    => array(array('core', '2.8')),
        'permissions' => 'admin',
        'support'     => 'https://open-time.net/?q=dmlastcomments', // Support URL
        'type'        => 'plugin'
    )
);
