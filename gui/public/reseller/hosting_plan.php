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

use iMSCP\Event\Event;
use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\TemplateEngine;

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function generatePage(TemplateEngine $tpl)
{
    $stmt = exec_query(
        'SELECT id, name, status FROM hosting_plans WHERE reseller_id = ? ORDER BY id', $_SESSION['user_id']
    );

    if (!$stmt->rowCount()) {
        $tpl->assign('HOSTING_PLANS', '');
        set_page_message(tr('No hosting plan available.'), 'static_info');
        return;
    }

    $tpl->assign([
        'TR_ID'     => tr('Id'),
        'TR_NAME'   => tr('Name'),
        'TR_STATUS' => tr('Status'),
        'TR_EDIT'   => tr('Edit'),
        'TR_ACTION' => tr('Actions'),
        'TR_DELETE' => tr('Delete')
    ]);

    EventAggregator::getInstance()->registerListener('onGetJsTranslations', function (Event $e) {
        $translations = $e->getParam('translations');
        $translations['core']['hp_delete_confirmation'] = tr('Are you sure you want to delete this hosting plan?');
    });

    while ($row = $stmt->fetchRow()) {
        $tpl->assign([
            'ID'     => $row['id'],
            'NAME'   => tohtml($row['name']),
            'STATUS' => $row['status'] ? tr('Available') : tr('Unavailable'),
        ]);
        $tpl->parse('HOSTING_PLAN', '.hosting_plan');
    }
}

require 'imscp-lib.php';

check_login('reseller');
EventAggregator::getInstance()->dispatch(Events::onResellerScriptStart);

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'        => 'shared/layouts/ui.tpl',
    'page'          => 'reseller/hosting_plan.tpl',
    'page_message'  => 'layout',
    'hosting_plans' => 'page',
    'hosting_plan'  => 'hosting_plans'
]);

$tpl->assign('TR_PAGE_TITLE', tr('Reseller / Hosting Plans / Overview'));

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(
    Events::onResellerScriptEnd, ['templateEngine' => $tpl]
);
$tpl->prnt();

unsetMessages();
