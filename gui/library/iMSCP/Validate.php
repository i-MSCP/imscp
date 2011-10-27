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
 * @package		iMSCP_Core
 * @subpackage	Validate
 * @copyright	2010-2011 by i-MSCP team
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/**
 * iMSCP validation class.
 *
 * This class provide a way to access all validation routines via an unique handler.
 *
 * Note: Working in progress...
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Validate
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.2
 */
class iMSCP_Validate
{
	/**
	 * @var Zend_Validate_Abstract[]
	 */
	protected static $_validators = array();

	/**
	 * Translator adapter used by Zend validate component.
	 *
	 * @var Zend_Translate_Adapter
	 */
	protected static $_translator = null;

	/**
	 * Instance of last Validator invoked.
	 *
	 * @var Zend_Validate_Abstract
	 */
	protected static $_lastValidator = null;

	/**
	 * Validates an username.
	 *
	 * @static
	 * @throws iMSCP_Exception Because not implemented yet
	 * @param $username Username to be validated.
	 * @return bool TRUE if username is valid, FALSE otherwise
	 */
	public static function username($username)
	{
		// TODO: Implement username() method.
		require_once 'iMSCP/Exception.php';
		throw new iMSCP_Exception(__CLASS__ . '::username() is not implemented yet.');
	}

	/**
	 * Validates a password.
	 *
	 * @static
	 * @throws iMSCP_Exception Because not implemented yet
	 * @param $password Password to be validated
	 * @return void
	 */
	public static function password($password)
	{
		// TODO: Implement password() method.
		require_once 'iMSCP/Exception.php';
		throw new iMSCP_Exception(__CLASS__ . '::password() is not implemented yet.');
	}

	/**
	 * Validates an email address.
	 *
     * The following option keys are supported:
     * 'hostname'		=> A hostname validator, see Zend_Validate_Hostname
     * 'allow'			=> Options for the hostname validator, see Zend_Validate_Hostname::ALLOW_*
     * 'mx'				=> If MX check should be enabled, boolean
     * 'deep'			=> If a deep MX check should be done, boolean
	 * 'domain'			=> Disable hostname validation but not global pass check
	 * 'onlyLocalPart'	=> Disable hostname validation and provides dummy value for global pass check
	 *
	 * @static
	 * @param string $email email address to be validated
	 * @param array $options Validator options OPTIONAL (Zend validator options
	 * @return bool TRUE if email address is valid, FALSE otherwise
	 */
	public static function email($email, $options = array())
	{
		if(array_key_exists('onlyLocalPart', $options) && $options['onlyLocalPart']) {
			// We do not want process hostname part validation on email address so
			// we disable it and provide dummy value for global check
			$options['domain'] = false;
			$email .= '@dummy';
		}

		return self::_processValidation('EmailAddress', $email, $options);
	}

	/**
	 * Validates a hostname.
	 *
	 * @static
	 * @see Zend_Validate_Hostname for available options
	 * @param string $hostname Hostname to be validated
	 * @param array $options Validator options OPTIONAL
	 * @return bool TRUE if email address is valid, FALSE otherwise
	 */
	public static function _hostname($hostname, $options = array())
	{
		return self::_processValidation('Hostname', $hostname, $options);
	}

	/**
	 * Validates a domain name.
	 *
	 * @static
	 * @see iMSCP_Validate::hostname()
	 * @param string $domainName Domain name to be validated
	 * @param array $options Validator options OPTIONAL
	 * @return bool TRUE if domain name is valid, FALSE otherwise
	 */
	public static function _domainName($domainName, $options = array())
	{
		return self::_hostname($domainName, $options);
	}

	/**
	 * Validates a subdomain name.
	 *
	 * @static
	 * @see iMSCP_Validate::hostname()
	 * @param string $subdomainName Subdomain to be validated.
	 * @param array $options Validator options OPTIONAL
	 * @return bool TRUE if subdomain name is valid, FALSE otherwise
	 */
	public static function subdomainName($subdomainName, $options = array())
	{
		return self::_hostname($subdomainName, $options);
	}

	/**
	 * Validates an Ip address.
	 *
	 * @static
	 * @see Zend_Validate_Ip for available options
	 * @param string $ip Ip address to be validated
	 * @param array $options Validator options OPTIONAL
	 * @return bool TRUE if ip address is valid, FALSE otherwise
	 */
	public static function Ip($ip, $options = array())
	{
		return self::_processValidation('Ip', $ip, $options);
	}

	/**
	 * Sets translator for Zend validator.
	 *
	 * @static
	 * @throws iMSCP_Exception When $translator is not a Zend_Translate_Adapter instance
	 * @param Zend_Translate_Adapter $translator Translator adapter
	 * @return void
	 */
	static public function setTranslator($translator = null)
	{
		if (null === $translator) {
			require_once 'iMSCP/I18n/Adapter/Zend.php';
			$translator = new iMSCP_I18n_Adapter_Zend();
		} elseif (!$translator instanceof Zend_Translate_Adapter) {
			require_once 'iMSCP/Exception.php';
			throw new iMSCP_Exception('$translator must be an instance of Zend_Translate_Adapter.');
		}

		Zend_Validate_Abstract::setDefaultTranslator($translator);
	}

	/**
	 * Returns instance of a specific Zend validator.
	 *
	 * @static
	 * @param string $validatorName Zend validator name
	 * @param array $options Validator options OPTIONAL
	 * @return Zend_Validate_Abstract
	 */
	static public function getZendValidator($validatorName, $options = array())
	{
		if (!array_key_exists($validatorName, self::$_validators)) {
			$validator = 'Zend_Validate_' . $validatorName;

			require_once "Zend/Validate/$validatorName.php";

			self::$_validators[$validatorName] = new  $validator($options);

			if (empty(self::$_validators) && !Zend_Validate_Abstract::hasDefaultTranslator()) {
				self::setTranslator();
			}
		}

		self::$_lastValidator = self::$_validators[$validatorName];
		return self::$_validators[$validatorName];
	}

	/**
	 * Process validation.
	 *
	 * @param string $validatorName $validatorName Zend validator name
	 * @param mixed $input Input data to be validated
	 * @param array $options
	 * @return bool bool TRUE if input data are valid, FALSE otherwise
	 */
	static protected function _processValidation($validatorName, $input, $options)
	{
		/** @var $validator Zend_Validate_Abstract */
		$validator = self::getZendValidator($validatorName);

		// Getting validator default options
		$defaultOptions = $validator->getOptions();

		// Setup validator options
		$validator->setOptions($options);

		// Process validation
		$retVal = $validator->isValid($input);

		// Reset default options on validator
		$validator->setOptions($defaultOptions);

		return $retVal;
	}

	/**
	 * Returns messages from last validation as a single string.
	 *
	 * @static
	 * @return string
	 */
	static public function getLastValidationMessages()
	{
		if (null !== self::$_lastValidator) {
			return format_message(self::$_lastValidator->getMessages());
		} else {
			require_once 'iMSCP/Exception.php';
			throw new iMSCP_Exception('You must first invoke a validator.');
		}
	}
}
