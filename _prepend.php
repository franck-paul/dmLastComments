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

// Public and Admin mode

if (!defined('DC_CONTEXT_ADMIN')) {return false;}

// Admin mode

$__autoload['dmLastCommentsRest'] = dirname(__FILE__) . '/_services.php';

// Register REST methods
$core->rest->addFunction('dmLastCommentsCheck', ['dmLastCommentsRest', 'checkNewComments']);
$core->rest->addFunction('dmLastCommentsRows', ['dmLastCommentsRest', 'getLastCommentsRows']);
$core->rest->addFunction('dmLastCommentsSpam', ['dmLastCommentsRest', 'getSpamCount']);
