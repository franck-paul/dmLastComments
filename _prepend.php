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

if (!defined('DC_RC_PATH')) { return; }

// Public and Admin mode

if (!defined('DC_CONTEXT_ADMIN')) { return false; }

// Admin mode

$__autoload['dmLastCommentsRest'] = dirname(__FILE__).'/_services.php';

// Register REST methods
$core->rest->addFunction('dmLastCommentsCheck',array('dmLastCommentsRest','checkNewComments'));
$core->rest->addFunction('dmLastCommentsRows',array('dmLastCommentsRest','getLastCommentsRows'));
$core->rest->addFunction('dmLastCommentsSpam',array('dmLastCommentsRest','getSpamCount'));
