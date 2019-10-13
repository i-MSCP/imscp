<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * @noinspection
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 * PhpIncludeInspection
 */

use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\TemplateEngine;

/**
 * Hide disabled features
 *
 * @param TemplateEngine $tpl Template engine instance
 */
function client_hideDisabledFeatures(TemplateEngine $tpl)
{
    if (!customerHasFeature('backup')) {
        $tpl->assign('BACKUP_FEATURE', '');
    }
}

require_once 'imscp-lib.php';

check_login('user');
EventAggregator::getInstance()->dispatch(Events::onClientScriptStart);

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'         => 'shared/layouts/ui.tpl',
    'page'           => 'client/webtools.tpl',
    'page_message'   => 'layout',
    'backup_feature' => 'page',
]);
$tpl->assign([
    'TR_PAGE_TITLE'             => tohtml(tr('Client / Webtools / Overview')),
    'TR_FEATURE'                => tohtml(tr('Feature')),
    'TR_DESCRIPTION'            => tohtml(tr('Description')),
    'TR_HTACCESS_TOOLTIPS'      => tohtml(tr('Protected areas'), 'htmlAttr'),
    'TR_HTACCESS_TXT'           => tohtml(tr('Manage your protected areas, users and groups.')),
    'TR_ERROR_PAGES_TOOLTIPS'   => tohtml(tr('Error pages'), 'htmlAttr'),
    'TR_ERROR_PAGES_TXT'        => tohtml(tr('Customize error pages for your domain.')),
    'TR_BACKUP_TOOLTIPS'        => tohtml(tr('Backup'), 'htmlAttr'),
    'TR_BACKUP_TXT'             => tohtml(tr('Backup and restore settings.')),
]);

generateNavigation($tpl);
client_hideDisabledFeatures($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(
    Events::onClientScriptEnd, ['templateEngine' => $tpl]
);
$tpl->prnt();
unsetMessages();
