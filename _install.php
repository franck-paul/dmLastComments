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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$new_version = $core->plugins->moduleInfo('dmLastComments', 'version');
$old_version = $core->getVersion('dmLastComments');

if (version_compare($old_version, $new_version, '>=')) {
    return;
}

try
{
    $core->auth->user_prefs->addWorkspace('dmlastcomments');

    // Default prefs for last comments
    $core->auth->user_prefs->dmlastcomments->put('last_comments', false, 'boolean', 'Display last comments', false, true);
    $core->auth->user_prefs->dmlastcomments->put('last_comments_nb', 5, 'integer', 'Number of last comments displayed', false, true);
    $core->auth->user_prefs->dmlastcomments->put('last_comments_large', true, 'boolean', 'Large display', false, true);
    $core->auth->user_prefs->dmlastcomments->put('last_comments_author', true, 'boolean', 'Show authors', false, true);
    $core->auth->user_prefs->dmlastcomments->put('last_comments_date', true, 'boolean', 'Show dates', false, true);
    $core->auth->user_prefs->dmlastcomments->put('last_comments_time', true, 'boolean', 'Show times', false, true);
    $core->auth->user_prefs->dmlastcomments->put('last_comments_nospam', false, 'boolean', 'Exclude junk comments', false, true);
    $core->auth->user_prefs->dmlastcomments->put('last_comments_recents', 0, 'integer', 'Max age of comments (in hours)', false, true);
    $core->auth->user_prefs->dmlastcomments->put('last_comments_autorefresh', false, 'boolean', 'Auto refresh', false, true);
    $core->auth->user_prefs->dmlastcomments->put('last_comments_badge', true, 'boolean', 'Display counter (Auto refresh only)', false, true);

    $core->setVersion('dmLastComments', $new_version);

    return true;
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}
return false;
