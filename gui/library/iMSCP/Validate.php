<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Registry as Registry;

/**
 * iMSCP validation class
 */
class iMSCP_Validate
{
    /**
     * @var iMSCP_Validate
     */
    protected static $instance = NULL;

    /**
     * @var iMSCP_Config_Handler_File
     */
    protected $config = NULL;

    /**
     * @var Zend_Validate_Abstract[]
     */
    protected $validators = [];

    /**
     * Instance of last Validator invoked.
     *
     * @var Zend_Validate_Abstract
     */
    protected $lastValidator = NULL;

    /**
     * Last iMSCP_Validate validation error messages.
     *
     * @var array
     */
    protected $lastValidationErrorMessages = [];

    /**
     * Singleton - Make new unavailable.
     */
    private function __construct()
    {
        $this->config = Registry::get('config');
    }

    /**
     * Implements singleton design pattern.
     *
     * @static
     * @return iMSCP_Validate
     */
    static public function getInstance()
    {
        if (self::$instance === NULL) {
            self::$instance = new self();
        }

        return self::$instance;
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
    public function email($email, $options = [])
    {
        if (array_key_exists('onlyLocalPart', $options) && $options['onlyLocalPart']) {
            // We do not want process hostname part validation on email address so
            // we disable it and we provides dummy value for global pass check
            $options['domain'] = false;
            $email .= '@dummy';
        } else {
            $options['hostname'] = new Zend_Validate_Hostname(['tld' => false]);
        }

        return $this->processValidation('EmailAddress', $email, $options);
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
    protected function processValidation($validatorName, $input, $options)
    {
        /** @var $validator Zend_Validate_Barcode_Ean18 */
        $validator = self::getZendValidator($validatorName);

        // Getting validator default options
        $defaultOptions = $validator->getOptions();

        // Setup validator options
        $validator->setOptions((array)$options);

        // Process validation
        if (!($retVal = $validator->isValid($input))) {
            $this->lastValidationErrorMessages = array_merge(
                $this->lastValidationErrorMessages, $this->lastValidator->getMessages()
            );
        }

        // Reset default options on validator
        $validator->setOptions($defaultOptions);
        return $retVal;
    }

    /**
     * Returns instance of a specific Zend validator.
     *
     * @param string $validatorName Zend validator name
     * @param array $options Options to pass to the validator OPTIONAL
     * @return Zend_Validate_Abstract
     */
    public function getZendValidator($validatorName, $options = [])
    {
        if (!array_key_exists($validatorName, $this->validators)) {
            $validator = 'Zend_Validate_' . $validatorName;
            $this->validators[$validatorName] = new $validator($options);
        }

        $this->lastValidator = $this->validators[$validatorName];
        return $this->validators[$validatorName];
    }

    /**
     * Validates a hostname.
     *
     * @see Zend_Validate_Hostname for available options
     * @param string $hostname Hostname to be validated
     * @param array $options Validator options OPTIONAL
     * @return bool TRUE if hostname is valid, FALSE otherwise
     */
    public function hostname($hostname, $options = [])
    {
        if (!array_key_exists('tld', $options)) {
            $options['tld'] = false;
        }

        return $this->processValidation('Hostname', $hostname, $options);
    }

    /**
     * Validates an Ip address.
     *
     * @see Zend_Validate_Ip for available options
     * @param string $ip Ip address to be validated
     * @param array $options Validator options OPTIONAL
     * @return bool TRUE if ip address is valid, FALSE otherwise
     */
    public function ip($ip, $options = [])
    {
        return $this->processValidation('Ip', $ip, $options);
    }

    /**
     * Returns error messages for last validation as a single string.
     *
     * @static
     * @return string
     */
    public function getLastValidationMessages()
    {
        if (!empty($this->lastValidationErrorMessages)) {
            $messages = $this->lastValidationErrorMessages;
            $this->lastValidationErrorMessages = [];
            return format_message($messages);
        }

        return '';
    }

    /**
     * Assert that the given values are equals
     *
     * @param mixed $value1 Value
     * @param mixed $value2 Value
     * @param string|array $messages OPTIONAL Error message(s)
     * @return bool
     */
    public function assertEquals($value1, $value2, $messages = NULL)
    {
        if (($value1 === $value2)) {
            return true;
        }

        if (NULL === $messages) {
            $messages = tr('The values must not be equal', $value1, $value2);
        }

        $this->lastValidationErrorMessages = array_merge($this->lastValidationErrorMessages, (array)$messages);
        return false;
    }

    /**
     * Asserts that the given values are not equals
     *
     * @param mixed $value1 Value
     * @param mixed $value2 Value
     * @param string|array $messages OPTIONAL Error message(s)
     * @return bool
     */
    public function assertNotEquals($value1, $value2, $messages = NULL)
    {
        if ($value1 !== $value2) {
            return true;
        }

        if (NULL === $messages) {
            $messages = tr('The values must not be equal', $value1, $value2);
        }

        $this->lastValidationErrorMessages = array_merge($this->lastValidationErrorMessages, (array)$messages);
        return false;


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
    public function assertContains($value, array $stack, $strict = true, $messages = NULL)
    {
        if (in_array($value, $stack, $strict)) {
            return true;
        }

        if (NULL === $messages) {
            $messages = tr('The value has not been found in the stack');
        }

        $this->lastValidationErrorMessages = array_merge($this->lastValidationErrorMessages, (array)$messages);
        return false;
    }

    /**
     * Assert that the given value is not in the given value stack
     *
     * @param mixed $value value
     * @param array $stack Value stack
     * @param array|string $messages OPTIONAL Error message(s)
     * @return bool
     */
    public function assertNotContains($value, array $stack, $messages = NULL)
    {
        if (!in_array($value, $stack, true)) {
            return true;
        }

        if (NULL === $messages) {
            $messages = tr('The value has been found in the stack');
        }

        $this->lastValidationErrorMessages = array_merge($this->lastValidationErrorMessages, (array)$messages);
        return false;
    }

    /**
     * Singleton - Make clone unavailable.
     *
     * @return void
     */
    private function __clone()
    {

    }
}
