<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2011 by i-MSCP team
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
 * @package		iMSCP Roundcube password changer
 * @copyright	2010-2011 by i-MSCP team
 * @author 		Sascha Bay
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */
define('PASSWORD_ERROR', 2);
define('PASSWORD_CONNECT_ERROR', 3);
define('PASSWORD_SUCCESS', 0);

class imscp_pw_changer extends rcube_plugin{

	public $task = 'settings';

	function init(){
		$rcmail = rcmail::get_instance();
		// add Tab label
		$rcmail->output->add_label('password');
		$this->register_action('plugin.imscp_pw_changer', array($this, 'password_init'));
		$this->register_action('plugin.imscp_pw_changer-save', array($this, 'password_save'));
		$this->include_script('password.js');
	}

	function password_init(){
		$this->add_texts('localization/');
		$this->register_handler('plugin.body', array($this, 'password_form'));

		$rcmail = rcmail::get_instance();
		$rcmail->output->set_pagetitle($this->gettext('changepasswd'));
		$rcmail->output->send('plugin');
	}

	function password_save(){
		$rcmail = rcmail::get_instance();
		$this->load_config();

		$this->add_texts('localization/');
		$this->register_handler('plugin.body', array($this, 'password_form'));
		$rcmail->output->set_pagetitle($this->gettext('changepasswd'));

		if((!isset($_POST['_confpasswd'])) || !isset($_POST['_newpasswd'])) {
		  $rcmail->output->command('display_message', $this->gettext('nopassword'), 'error');
		}
		elseif($_POST['_confpasswd'] != $_POST['_newpasswd']) {
		  $rcmail->output->command('display_message', $this->gettext('passwordinconsistency'), 'error');
		}
		elseif (strlen($_POST['_newpasswd']) < $rcmail->config->get('password_length')) {
		  $rcmail->output->command('display_message', $this->gettext('passwordlenght').$rcmail->config->get('password_length'), 'error');
		}
		else {
		  $newpwd = get_input_value('_newpasswd', RCUBE_INPUT_POST);
		  if (!($res = $this->_save($newpwd))) {
			$rcmail->output->command('display_message', $this->gettext('successfullysaved'), 'confirmation');
			$_SESSION['password'] = $rcmail->encrypt($newpwd);
		  } else
			$rcmail->output->command('display_message', $res, 'error');
		}

		rcmail_overwrite_action('plugin.imscp_pw_changer');
		$rcmail->output->send('plugin');
	}

	function password_form(){
		$rcmail = rcmail::get_instance();
		$this->load_config();

		// add some labels to client
		$rcmail->output->add_label(
		  'imscp_pw_changer.nopassword',
		  'imscp_pw_changer.passwordinconsistency'
		);

		$rcmail->output->set_env('product_name', $rcmail->config->get('product_name'));

		$table = new html_table(array('cols' => 2));

		// show new password selection
		$field_id = 'newpasswd';
		$input_newpasswd = new html_passwordfield(array('name' => '_newpasswd', 'id' => $field_id,
		  'size' => 20, 'autocomplete' => 'off'));

		$table->add('title', html::label($field_id, Q($this->gettext('newpasswd'))));
		$table->add(null, $input_newpasswd->show());

		// show confirm password selection
		$field_id = 'confpasswd';
		$input_confpasswd = new html_passwordfield(array('name' => '_confpasswd', 'id' => $field_id,
		  'size' => 20, 'autocomplete' => 'off'));

		$table->add('title', html::label($field_id, Q($this->gettext('confpasswd'))));
		$table->add(null, $input_confpasswd->show());

		$out = html::div(array('class' => "settingsbox", 'style' => "margin:0"),
		  html::div(array('id' => "prefs-title"), $this->gettext('changepasswd')) .
		  html::div(array('style' => "padding:15px"), $table->show() .
			html::p(null,
			  $rcmail->output->button(array(
				'command' => 'plugin.imscp_pw_changer-save',
				'type' => 'input',
				'class' => 'button mainaction',
				'label' => 'save'
			)))
		  )
		);

		$rcmail->output->add_gui_object('passform', 'imscp_pw_changer-form');

		return $rcmail->output->form_tag(
			array(
				'id' => 'imscp_pw_changer-form',
				'name' => 'imscp_pw_changer-form',
				'method' => 'post',
				'action' => './?_task=settings&_action=plugin.imscp_pw_changer-save',
			),
			$out
		);
	}

	private function _save($passwd){
		$config = rcmail::get_instance()->config;
		$driver = $this->home.'/drivers/'.$config->get('password_driver', 'sql').'.php';

		if (!is_readable($driver)) {
			raise_error(
				array(
					'code' => 600,
					'type' => 'php',
					'file' => __FILE__,
					'message' => "Password plugin: Unable to open driver file $driver"
				),
				true,
				false
			);
			return $this->gettext('internalerror');
		}

		include($driver);

		if (!function_exists('password_save')) {
			raise_error(array(
				'code' => 600,
				'type' => 'php',
				'file' => __FILE__,
				'message' => "Password plugin: Broken driver: $driver"
			), true, false);
			return $this->gettext('internalerror');
		}
		$result = password_save($passwd);
		switch ($result) {
			case PASSWORD_SUCCESS:
				return;
			case PASSWORD_CONNECT_ERROR;
				return $this->gettext('connecterror');
			case PASSWORD_ERROR:
			default:
				return $this->gettext('internalerror');
		}
	}
}
?>
