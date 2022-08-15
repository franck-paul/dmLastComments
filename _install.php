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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$new_version = dcCore::app()->plugins->moduleInfo('dmLastComments', 'version');
$old_version = dcCore::app()->getVersion('dmLastComments');

if (version_compare($old_version, $new_version, '>=')) {
    return;
}

try {
    dcCore::app()->auth->user_prefs->addWorkspace('dmlastcomments');

    // Default prefs for last comments
    dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments', false, 'boolean', 'Display last comments', false, true);
    dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_nb', 5, 'integer', 'Number of last comments displayed', false, true);
    dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_large', true, 'boolean', 'Large display', false, true);
    dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_author', true, 'boolean', 'Show authors', false, true);
    dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_date', true, 'boolean', 'Show dates', false, true);
    dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_time', true, 'boolean', 'Show times', false, true);
    dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_nospam', false, 'boolean', 'Exclude junk comments', false, true);
    dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_recents', 0, 'integer', 'Max age of comments (in hours)', false, true);
    dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_autorefresh', false, 'boolean', 'Auto refresh', false, true);
    dcCore::app()->auth->user_prefs->dmlastcomments->put('last_comments_badge', true, 'boolean', 'Display counter (Auto refresh only)', false, true);

    dcCore::app()->setVersion('dmLastComments', $new_version);

    return true;
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

return false;
