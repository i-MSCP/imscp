<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright	2010-2011 by i-MSCP team
 * @author		iMSCP Team
 * @author		Hannes Koschier <hannes@cheat.at>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/************************************************************************************
 * Script functions
 */

/**
 * Generate layout color form.
 *
 * @author Laurent Declercq <l.declerq@nuxwin.com>
 * @since iMSCP 1.0.1.6
 * @param $tpl iMSCP_pTemplate Template engine instance
 * @return void
 */
function client_generateLayoutColorForm($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$colors = layout_getAvailableColorSet();

	if(!empty($POST) && isset($_POST['layoutColor']) && in_array($_POST['layoutColor'], $colors)) {
		$selectedColor = $_POST['layoutColor'];
	} else {
		$selectedColor = layout_getUserLayoutColor($_SESSION['user_id']);
	}

	if (!empty($colors)) {
		foreach ($colors as $color) {
			$tpl->assign(array(
				'COLOR' => $color,
				'SELECTED_COLOR' => ($color == $selectedColor) ? $cfg->HTML_SELECTED : ''));
			$tpl->parse('LAYOUT_COLOR_BLOCK', '.layout_color_block');
		}
	} else {
		$tpl->assign('LAYOUT_COLORS_BLOCK', '');
	}
}

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', $cfg->CLIENT_TEMPLATE_PATH . '/../shared/layouts/ui.tpl');
$tpl->define_dynamic(
	array(
		'page' => $cfg->CLIENT_TEMPLATE_PATH . '/layout.tpl',
		'page_message' => 'page',
		'layout_colors_block' => 'page',
		'layout_color_block' => 'layout_colors_block'));

/**
 * Dispatches request
 */
if(isset($_POST['uaction'])) {
    if($_POST['uaction'] == 'changeLayoutColor' && isset($_POST['layoutColor'])) {
		if(layout_setUserLayoutColor($_SESSION['user_id'], $_POST['layoutColor'])) {
			if(!isset($_SESSION['logged_from_id'])) {
     			$_SESSION['user_theme_color'] = $_POST['layoutColor'];
				set_page_message(tr('Layout color successfully updated.'), 'success');
			} else {
				set_page_message(tr("Customer's layout color successfully updated."), 'success');
			}
		} else {
			set_page_message(tr('Unknown layout color.'), 'error');
		}
    } else {
        set_page_message(tr('Unknown action: %s', tohtml($_POST['uaction'])), 'error');
    }
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - client / Setting Layout'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_LAYOUT_SETTINGS' => tr('Layout settings'),
		'TR_LAYOUT_COLOR' => tr('Layout color'),
		'TR_CHOOSE_LAYOUT_COLOR' =>  tr('Choose layout color'),
		'TR_CHANGE' => tr('Change')));

generateNavigation($tpl);
client_generateLayoutColorForm($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();