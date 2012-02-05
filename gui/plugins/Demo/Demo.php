<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 - 2012 by i-MSCP Team
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
 * @package		iMSCP_Plugins
 * @subpackage	Demo
 * @copyright	2010 - 2012 by i-MSCP Team
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.5
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/** @See iMSCP_Plugin_Action */
require_once 'iMSCP/Plugin/Action.php';

/** @See iMSCP_Events_Listeners_Interface */
require_once 'iMSCP/Events/Listeners/Interface.php';

/**
 * iMSCP_Plugins_Demo class.
 *
 * This plugin allow to setup an i-MSCP demo server.
 *
 * @category	iMSCP
 * @package		iMSCP_Plugins
 * @subpackage	Demo
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.6
 */
class iMSCP_Plugins_Demo extends iMSCP_Plugin_Action implements iMSCP_Events_Listeners_Interface
{
	/**
	 * Listened events.
	 *
	 * @var array
	 */
	protected $_listenedEvents = array();

	/**
	 * Disabled actions.
	 *
	 * @var array
	 */
	protected $_disabledActions = array();

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		if ($this->getConfig('user_accounts')) {
			$this->_listenedEvents[] = 'onLoginScriptEnd';
		}

		if (($disabledActions = $this->getConfig('disabled_actions'))) {
			$this->setDisabledActions($disabledActions);
		} else {
			$this->setDisabledActions();
		}
	}

	/**
	 * Register a callback for the given event(s).
	 *
	 * @param iMSCP_Events_Manager $controller
	 */
	public function register(iMSCP_Events_Manager $controller)
	{
		$controller->registerListener($this->getListenedEvents(), $this, 1000);
		$this->_controller = $controller;
	}

	/**
	 * Returns list of listened events.
	 *
	 * @return array
	 */
	public function getListenedEvents()
	{
		return $this->_listenedEvents;
	}

	/**
	 * Implements listener methods that are not explicitely implemented.
	 *
	 * @param string $listenerMethod Litener method name
	 * @param mixed $params
	 * @return void
	 */
	public function __call($listenerMethod, $params)
	{
		if (in_array($listenerMethod, $this->getListenedEvents())) {
			if (!Zend_Session::namespaceIsset('pageMessages')) {
				set_page_message(tr('The %s action is not permitted in demo version.', str_replace('onBefore', '', "<strong>$listenerMethod</strong>")), 'info');
			}

			if (isset($_SERVER['HTTP_REFERER'])) {
				redirectTo($_SERVER['HTTP_REFERER']);
			} else {
				redirectTo('index.php');
			}
		}
	}

	/**
	 * Implements the onBeforeEditUser listener method.
	 *
	 * @param int $userId User unique identifier
	 * @return void
	 */
	public function onBeforeEditUser($userId)
	{
		if ($this->isDisabledAction('onBeforeEditUser')) {
			$this->__call('onBeforeEditUser', $userId);
		} else {
			$this->_protectDemoUser($userId, 'onBeforeEditUser');
		}
	}

	/**
	 * Implements the onBeforeDeleteUser listener method.
	 *
	 * @param int $userId User unique identifier
	 * @return void
	 */
	public function onBeforeDeleteUser($userId)
	{
		if ($this->isDisabledAction('onBeforeDeleteUser')) {
			$this->__call('onBeforeDeleteUser', $userId);
		} else {
			$this->_protectDemoUser($userId, 'onBeforeDeleteUser');
		}
	}

	/**
	 * Implements the onBeforeDeleteDomain listener methods.
	 *
	 * @param $domainId Domain unique identifier
	 * @return void
	 */
	public function onBeforeDeleteDomain($domainId)
	{
		// Avoid interfering with child' events
		$this->getController()->unregisterListener($this->getListenedEvents(), $this);

		if ($this->isDisabledAction('onBeforeDeleteDomain')) {
			$this->__call('onBeforeDeleteDomain', $domainId);
		} else {
			$query = 'SELECT `domain_admin_id` FROM `domain` WHERE `domain_id` = ?';
			$stmt = exec_query($query, (int)$domainId);

			if ($stmt->rowCount()) {
				$this->_protectDemoUser($stmt->fields['domain_admin_id'], 'onBeforeDeleteDomain');
			}
		}
	}

	/**
	 * Is disabled action?
	 *
	 * @param string $actionName Action name
	 * @return bool TRUE if the given action is disabled, FALSE otherwise
	 */
	public function isDisabledAction($actionName)
	{
		return in_array($actionName, $this->_disabledActions);
	}

	/**
	 * Sets disabled actions.
	 *
	 * @param array $actionNames List of actions to disable
	 * @return void
	 */
	protected function setDisabledActions(array $actionNames = array())
	{
		$this->_disabledActions = $actionNames;

		// Accounts explicitely protected against deletion and password modification
		if (isset($this->_config['user_accounts'])) {
			foreach ($this->_config['user_accounts'] as $account) {
				if (isset($account['protected']) && $account['protected']) {
					$actionNames[] = 'onBeforeEditUser';
					$actionNames[] = 'onBeforeDeleteUser';
					$actionNames[] = 'onBeforeDeleteDomain';
					break;
				}
			}
		}

		$this->_listenedEvents = array_unique(array_merge($this->getListenedEvents(), $actionNames));
	}

	/**
	 * Protect demo user / domain accounts against some actions.
	 *
	 * @param int $userId User unique identifier
	 * @param string $fromAction Action name from which $userId is protected
	 * @return void
	 */
	protected function _protectDemoUser($userId, $fromAction)
	{
		$query = 'SELECT `admin_name` FROM `admin` WHERE `admin_id` = ?';
		$stmt = exec_query($query, (int)$userId);

		if ($stmt->rowCount()) {
			$username = idn_to_utf8($stmt->fields['admin_name']);
			$foundUser = false;

			foreach ($this->getConfig('user_accounts') as $account) {
				if ($account['username'] == $username && (isset($account['protected']) && $account['protected'])) {
					$foundUser = true;
				}
			}

			if ($foundUser) {
				switch ($fromAction) {
					case 'onBeforeEditUser':
						// Only password change is not allowed
						if (
							// admin/password_change.php
							// reseller/password_change.php
							// client/password_change.php
							// admin/admin_edit.php
							!empty($_POST['pass']) ||
							// admin/reseller_edit.php
							!empty($_POST['password']) ||
							// reseller/user_edit.php
							!empty($_POST['userpassword'])
						) {
							set_page_message(tr("You are not allowed to change the demo's users passwords. Create your own user if you want test this feature."), 'info');
						} else {
							return;
						}
						break;
					case 'onBeforeDeleteUser':
					case 'onBeforeDeleteDomain':
						set_page_message(tr("The demo's user accounts can't be removed. Create your own user if you want test this feature."), 'info');
						break;
				}

				$this->__call($fromAction, new iMSCP_Events());
			}
		}
	}

	/**
	 * Implements the onLoginScriptEnd listener method.
	 *
	 * Create a modal dialog to allow users to choose user account they want use to login. Available users are those
	 * defined in plugin configuration. If an user account doesn't exists in database, it is not showed.
	 *
	 * @param iMSCP_Events_Response $event
	 * @return void
	 */
	public function onLoginScriptEnd($event)
	{
		if ($this->getConfig('user_accounts') && ($jsCode = $this->_getCredentialsDialog()) != '') {
			$tpl = $event->getTemplateEngine();
			$tpl->replaceLastParseResult(str_replace('</head>', $jsCode . PHP_EOL . '</head>', $tpl->getLastParseResult()));
		}
	}

	/**
	 * Returns modal dialog js code for credentials.
	 *
	 * @return string
	 */
	protected function _getCredentialsDialog()
	{
		$credentials = $this->_getCredentials();

		if (!empty($credentials)) {
			return '
				<script type="text/javascript">
				/*<![CDATA[*/
					$(document).ready(function() {
						var welcome = ' . json_encode(tr('Welcome to the i-MSCP Demo version')) . ';
						var credentialInfo = ' . json_encode(tr("Please select the account you want use to login and click on the 'Ok' button.")) . ' + "<br /><br />";
						$("<div/>", {"id": "demo", html: "<h2>" + welcome + "</h2>" + credentialInfo}).appendTo("body");
						$("<select/>", {"id": "demo_credentials"}).appendTo("#demo");
						var credentials = ' . json_encode($credentials) . '
						$.each(credentials, function() {
							$("#demo_credentials").append($("<option></option>").val(this.username + " " + this.password).text(this.label));
						})
						$("#demo_credentials").change(function() {
							var credentials = $("#demo_credentials option:selected").val().split(" ");
							$("#uname").val(credentials.shift());
							$("#upass").val(credentials.shift());
						}).trigger("change");
						$("#demo").dialog({
							modal: true, width:"500", autoOpen:true, height:"auto", buttons: {Ok: function(){$(this).dialog("close");}},
							title:"i-MSCP Demo"
						});
					});
				/*]]>*/
				</script>
			';
		} else {
			return '';
		}
	}

	/**
	 * Returns credentials to push in select element.
	 *
	 * @return array
	 */
	protected function _getCredentials()
	{
		$credentials = array();

		foreach ($this->getConfig('user_accounts') as $account) {
			if (isset($account['label']) && isset($account['username']) && isset($account['password'])) {
				$query = 'SELECT COUNT(`admin_id`) `cnt` FROM `admin` WHERE `admin_name` = ? AND (`admin_pass` = ? OR `admin_pass` = MD5(?))';
				$stmt = exec_query($query, array(idn_to_ascii($account['username']), crypt($account['password']), $account['password']));

				if ($stmt->fields['cnt'] > 0) {
					$credentials[] = array(
						'label' => $account['label'], 'username' => $account['username'], 'password' => $account['password']
					);
				}
			}
		}

		return $credentials;
	}
}
