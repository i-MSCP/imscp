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
 * @version		0.0.1
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * iMSCP_Plugins_Demo class.
 *
 * This plugin is intended to be used on demo server. He allow to disable some actions and also
 * show a modal dialog to allow the tester to choose what account he want use to login on.
 *
 * @category	iMSCP
 * @package		iMSCP_Plugins
 * @subpackage	Demo
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.1
 * @TODO WORK IN PROGRESS
 */
class iMSCP_Plugins_Demo implements iMSCP_Events_Listeners_Interface
{
	/**
	 * Plugin configuration parameters.
	 *
	 * @var array
	 */
	protected $_config = array();

	/**
	 * Listened events
	 *
	 * @var array
	 */
	protected $_listenedEvents = array(
		iMSCP_Events::onLoginScriptEnd,
		iMSCP_Events::onBeforeEditAdmin,
		iMSCP_Events::onBeforeEditReseller,
		iMSCP_Events::onBeforeEditCustomer,
	);

	/**
	 * Constructor.
	 *
	 * @param string|Zend_Config_Xml $config config object or config filepath
	 */
	public function __construct($config = null)
	{
		if($config instanceof Zend_Config_Xml) {
			$this->_config = $config->toArray();
		} elseif(is_string($config) && is_readable($config)) {
			$config = new Zend_Config_Xml($config);
			$this->_config = $config->toArray();
		} else {
			throw new iMSCP_Exception('Demo plugin must be configured.');
		}

		if(isset($this->_config['disabled_actions'])) {
			$this->setDisabledActions($this->_config['disabled_actions']);
		}
	}

	/**
	 * Sets disabled actions.
	 *
	 * Note: action names are same as event names without the 'onBefore' prefix. Only onBefore* actions are supported.
	 *
	 * @param array $actionNames List of actions to disable
	 * @return void
	 */
	public function setDisabledActions(array $actionNames)
	{
		$events = array();

		foreach($actionNames as $actionName) {
			$events[] = "onBefore$actionName";
		}

		$this->_listenedEvents = array_unique(array_merge($events, $this->_listenedEvents));
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
	 * Imlementslistener methods that are not explicitely implemented.
	 *
	 * @param string $listenerMethod Litener method name
	 * @param iMSCP_Events $params
	 */
	public function __call($listenerMethod, $params)
	{
		if(in_array($listenerMethod, $this->getListenedEvents())) {
			set_page_message(tr('This action is not permitted in <strong>demo</strong> version.'), 'info');

			if(isset($_SERVER['HTTP_REFERER'])) {
				redirectTo($_SERVER['HTTP_REFERER']);
			} else {
				redirectTo('index.php');
			}
		}
	}

	/**
	 * Implements the onBeforeDeleteUser listener method.
	 *
	 * @param int $userId User unique identifier
	 */
	public function onBeforeDeleteUser($userId)
	{
		$this->_protectDemoUser($userId, 'DeleteUser');
	}

	/**
	 * Implements the onBeforeEditReseller listener method.
	 *
	 * @param int $userId Reseller unique identifier
	 */
	public function onBeforeEditReseller($userId)
	{
		$this->_protectDemoUser($userId, 'EditReseller');
	}

	/**
	 * Implements the onBeforeEditCustomer listener method.
	 *
	 * @param int $userId Customer unique identifier
	 */
	public function onBeforeEditCustomer($userId)
	{
		$this->_protectDemoUser($userId, 'EditCustomer');
	}

	/**
	 * Protect demo user / domain accounts against some actions.
	 *
	 * @param int $userId User unique identifier
	 * @param string $fromAction Action name from which user is protected
	 * @return void
	 */
	protected function _protectDemoUser($userId, $fromAction)
	{
		if (isset($this->_config['user_accounts'])) {
			$query = 'SELECT `admin_name` FROM `admin` WHERE `admin_id` = ?';
			$stmt = exec_query($query, (int) $userId);

			if($stmt->rowCount()) {
				$username = idn_to_utf8($stmt->fields['admin_name']);
				$foundUser = false;

				foreach ($this->_config['user_accounts'] as $account) {
					if($account['username'] == $username && (isset($account['protected']) && $account['protected'])) {
						$foundUser = true;
					}
				}

				if($foundUser) {
					switch($fromAction) {
						case 'EditAdmin':
						case 'EditReseller':
						case 'EditCustomer':

							// Only password change is not allowed
							if(
								(!empty($_POST['userpassword']) && !empty($_POST['userpassword_repeat'])) || // reseller/user_edit.php
								(isset($_POST['uaction']) && $_POST['uaction'] == 'updt_pass') || // reseller/password_change.php
								(!empty($_POST['pass']) && !empty($_POST['pass_rep'])) || // admin/admin_edit.php
								(!empty($data['password']) && !empty($data['pasword_confirmation'])) // admin/reseller_edit.php
							) {
								set_page_message(tr("You are not allowed to change the demo's users passwords."), 'info');
							} else {
								return;
							}
							break;
						case 'DeleteUser':
						case 'DeleteDomain':
							set_page_message(tr('This user/domain account cannot be removed.'), 'info');
							break;
					}

					$this->__call("onBefore$fromAction", new iMSCP_Events());
				}
			}
		}
	}

	/**
	 * Implements the onLoginScriptEnd listener method.
	 *
	 * @param iMSCP_Events_Response $event
	 * @return void
	 */
	public function onLoginScriptEnd($event)
	{
		if(($jsCode = $this->_getCredentialsDialog())) {
			$tpl = $event->getTemplateEngine();
			$tpl->replaceLastParseResult(
				str_replace('</head>', $this->_getCredentialsDialog() . PHP_EOL . '</head>', $tpl->getLastParseResult()));
		}
	}

	/**
	 * Returns modal dialog js code for credentials.
	 *
	 * @return mixed
	 */
	protected function _getCredentialsDialog()
	{
		if (($credentials = $this->_getCredentials())) {
			return '
				<script type="text/javascript">
				/*<![CDATA[*/
					$(document).ready(function() {
						var welcome = "Welcome to the i-MSCP Demo version";
						var credentialInfo = "Please select the account you want use to login and click on the \'Ok\' button..<br /><br />";

						$("<div/>", {"id": "demo", html: "<h2>" + welcome + "</h2>" + credentialInfo}).appendTo("body");
						$("<select/>", {"id": "demo_credentials"}).appendTo("#demo");

						var credentials = ' . $credentials . '

						$.each(credentials, function() {
							$("#demo_credentials").append($("<option></option>").val(this.username + "~~" + this.password).text(this.label));
						})

						$("#demo_credentials").change(function() {
							var credentials = $("#demo_credentials option:selected").val().split("~~");
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
			return null;
		}
	}

	/**
	 * Returns credentials to push in select element.
	 *
	 * @return mixed
	 */
	protected function _getCredentials()
	{
		if(isset($this->_config['user_accounts'])) {
			return json_encode($this->_config['user_accounts']);
		}

		return null;
	}
}
