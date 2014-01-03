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
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Validate
 * @copyright   2010-2014 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/**
 * iMSCP validation class.
 *
 * This class provide a way to access all validation routines via an unique handler.
 *
 * Note: Working in progress...
 *
 * @category    iMSCP
 * @package        iMSCP_Core
 * @subpackage    Validate
 * @author        Laurent Declercq <l.declercq@nuxwin.com>
 * @version        0.0.6
 */
class iMSCP_Validate
{
    /**
     * @var iMSCP_Validate
     */
    protected static $_instance = null;

    /**
     * @var iMSCP_Config_Handler_File
     */
    protected $_config = null;

    /**
     * @var Zend_Validate_Abstract[]
     */
    protected $_validators = array();

    /**
     * Instance of last Validator invoked.
     *
     * @var Zend_Validate_Abstract
     */
    protected $_lastValidator = null;

    /**
     * Tell whether or not the default error messages must be overriden for the given validation method.
     *
     * @var string Validation method name
     */
    protected $_overrideMessagesFor = null;

    /**
     * Last iMSCP_Validate validation error messages.
     *
     * @var array
     */
    protected $_lastValidationErrorMessages = array();

    /**
     * Error messages that override those provided by validators in a specific validation context.
     *
     * @var array
     */
    protected $_messages = array(
        'domain' => array(
            'hostnameCannotDecodePunycode' => "'%value%' appears to be a domain name but the given punycode notation cannot be decoded",
            'hostnameDashCharacter' => "'%value%' appears to be a domain name but contains a dash in an invalid position",
            'hostnameInvalidHostname' => "'%value%' does not match the expected structure for a domain name",
            'hostnameInvalidHostnameSchema' => "'%value%' appears to be a domain name but cannot match against domain name schema for TLD '%tld%'",
            'hostnameUndecipherableTld' => "'%value%' appears to be a domain name but cannot extract TLD part",
            'hostnameUnknownTld' => "'%value%' appears to be a domain name but cannot match TLD against known list",
        ),

        'subdomain' => array(
            'hostnameCannotDecodePunycode' => "'%value%' appears to be a subdomain name but the given punycode notation cannot be decoded",
            'hostnameDashCharacter' => "'%value%' appears to be a subdomain name but contains a dash in an invalid position",
            'hostnameInvalidHostname' => "'%value%' does not match the expected structure for a subdomain name",
            'hostnameInvalidHostnameSchema' => "'%value%' appears to be a subdomain name but cannot match against subdomain schema for TLD '%tld%'",
            'hostnameUndecipherableTld' => "'%value%' appears to be a subdomain name but cannot extract TLD part",
            'hostnameUnknownTld' => "'%value%' appears to be a subdomain name but cannot match TLD against known list",
        )
    );

    /**
     * Singleton - Make new unavailable.
     */
    private function __construct()
    {
        $this->_config = iMSCP_Registry::get('config');
    }

    /**
     * Singleton - Make clone unavailable.
     *
     * @return void
     */
    private function __clone()
    {

    }

    /**
     * Implements singleton design pattern.
     *
     * @static
     * @return iMSCP_Validate
     */
    static public function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Validates an email address.
     *
     * The following option keys are supported:
     * 'hostname'      => A hostname validator, see Zend_Validate_Hostname
     * 'allow'         => Options for the hostname validator, see Zend_Validate_Hostname::ALLOW_*
     * 'mx'            => If MX check should be enabled, boolean
     * 'deep'          => If a deep MX check should be done, boolean
     * 'domain'        => If hostname validation must be disabled but not global pass check must be disabled, boolean
     * 'onlyLocalPart' => If hostname validation and global pass check must be disabled, boolean
     *
     * @param string $email email address to be validated
     * @param array $options Validator options OPTIONAL
     * @return bool TRUE if email address is valid, FALSE otherwise
     */
    public function email($email, $options = array())
    {
        if (array_key_exists('onlyLocalPart', $options) && $options['onlyLocalPart']) {
            // We do not want process hostname part validation on email address so
            // we disable it and we provides dummy value for global pass check
            $options['domain'] = false;
            $email .= '@dummy';
        }

        return $this->_processValidation('EmailAddress', $email, $options);
    }

    /**
     * Validates a hostname.
     *
     * @see Zend_Validate_Hostname for available options
     * @param string $hostname Hostname to be validated
     * @param array $options Validator options OPTIONAL
     * @return bool TRUE if email address is valid, FALSE otherwise
     */
    public function hostname($hostname, $options = array())
    {
        if (!array_key_exists('tld', $options)) {
            $options['tld'] = false;
        }

        return $this->_processValidation('Hostname', $hostname, $options);
    }

    /**
     * Validates an Ip address.
     *
     * @see Zend_Validate_Ip for available options
     * @param string $ip Ip address to be validated
     * @param array $options Validator options OPTIONAL
     * @return bool TRUE if ip address is valid, FALSE otherwise
     */
    public function ip($ip, $options = array())
    {
        return $this->_processValidation('Ip', $ip, $options);
    }

    /**
     * Set default translation object for all Zend validate objects.
     *
     * @throws iMSCP_Exception When $translator is not a Zend_Translate_Adapter instance
     * @param Zend_Translate_Adapter $translator Translator adapter
     * @return void
     */
    public function setDefaultTranslator($translator = null)
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
     * @param string $validatorName Zend validator name
     * @param array $options Options to pass to the validator OPTIONAL
     * @return Zend_Validate_Abstract
     */
    public function getZendValidator($validatorName, $options = array())
    {
        if (!array_key_exists($validatorName, $this->_validators)) {
            $validator = 'Zend_Validate_' . $validatorName;

            require_once "Zend/Validate/$validatorName.php";

            $this->_validators[$validatorName] = new $validator($options);

            if (empty($this->_validators) && !Zend_Validate_Abstract::hasDefaultTranslator()) {
                self::setDefaultTranslator();
            }
        }

        $this->_lastValidator = $this->_validators[$validatorName];
        return $this->_validators[$validatorName];
    }

    /**
     * Returns error messages for last validation as a single string.
     *
     * @static
     * @return string
     */
    public function getLastValidationMessages()
    {
        if (!empty($this->_lastValidationErrorMessages)) {
            $messages = $this->_lastValidationErrorMessages;
            $this->_lastValidationErrorMessages = array();
            return format_message($messages);
        } else {
            return '';
        }
    }

    /**
     * Process validation.
     *
     * @param string $validatorName $validatorName Zend validator name
     * @param mixed $input Input data to be validated
     * @param array $options Options to pass to validator
     * @throws iMSCP_Exception
     * @return bool bool TRUE if input data are valid, FALSE otherwise
     */
    protected function _processValidation($validatorName, $input, $options)
    {
        /** @var $validator Zend_Validate_Abstract */
        $validator = self::getZendValidator($validatorName);

        // Override validator default errors message if needed
        if (null != $this->_overrideMessagesFor) {
            if (isset($this->_messages[$this->_overrideMessagesFor])) {
                $defaultMessages = $validator->getMessageTemplates();
                $messages = $this->_messages[$this->_overrideMessagesFor];
                $validator->setMessages($messages);
            } else {
                throw new iMSCP_Exception(
                    sprintf(
                        'Custom error messages for the %s validation method are not defined.',
                        __CLASS__ . '::' . $this->_overrideMessagesFor));
            }
        }

        // Getting validator default options
        $defaultOptions = $validator->getOptions();

        // Setup validator options
        $validator->setOptions((array)$options);

        // Process validation
        if (!($retVal = $validator->isValid($input))) {
            $this->_lastValidationErrorMessages = array_merge(
                $this->_lastValidationErrorMessages, $this->_lastValidator->getMessages());
        }

        // Reset default options on validator
        $validator->setOptions($defaultOptions);

        if (isset($defaultMessages)) {
            $validator->setMessages($defaultMessages);
            $this->_overrideMessagesFor = null;
        }

        return $retVal;
    }

    /**
     * Assert that the given values are equals
     *
     * @param mixed $value1 Value
     * @param mixed $value2 Value
     * @param string|array $messages OPTIONAL Error message(s)
     * @return bool
     */
    public function assertEquals($value1, $value2, $messages = null)
    {
        if (($value1 !== $value2)) {
            if (null === $messages) {
                $messages = tr('The values must not be equal', $value1, $value2);
            }

            $this->_lastValidationErrorMessages = array_merge($this->_lastValidationErrorMessages, (array)$messages);

            return false;
        }

        return true;
    }

    /**
     * Asserts that the given values are not equals
     *
     * @param mixed $value1 Value
     * @param mixed $value2 Value
     * @param string|array $messages OPTIONAL Error message(s)
     * @return bool
     */
    public function assertNotEquals($value1, $value2, $messages = null)
    {
        if (($value1 === $value2)) {
            if (null === $messages) {
                $messages = tr('The values must not be equal', $value1, $value2);
            }

            $this->_lastValidationErrorMessages = array_merge($this->_lastValidationErrorMessages, (array)$messages);

            return false;
        }

        return true;
    }

    /**
     * Assert that the given value is in the given value stack
     *
     * @param mixed $value value
     * @param array $stack Value stack
	 * @param bool $strict Whether the check should be made in strict mode
     * @param array|string $messages OPTIONAL Error message(s)
     * @return bool
     */
    public function assertContains($value, array $stack, $strict = true, $messages = null)
    {
        if ((!in_array($value, $stack, $strict))) {
            if (null === $messages) {
                $messages = tr('The value has not been found in the stack');
            }

            $this->_lastValidationErrorMessages = array_merge($this->_lastValidationErrorMessages, (array)$messages);

            return false;
        }

        return true;
    }

    /**
     * Assert that the given value is not in the given value stack
     *
     * @param mixed $value value
     * @param array $stack Value stack
     * @param array|string $messages OPTIONAL Error message(s)
     * @return bool
     */
    public function assertNotContains($value, array $stack, $messages = null)
    {
        if ((in_array($value, $stack, true))) {
            if (null === $messages) {
                $messages = tr('The value has been found in the stack');
            }

            $this->_lastValidationErrorMessages = array_merge($this->_lastValidationErrorMessages, (array)$messages);

            return false;
        }

        return true;
    }
}
