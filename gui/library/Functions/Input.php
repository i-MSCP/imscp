<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Exception as iMSCPException;
use Zend_Escaper_Escaper as Escaper;
use Zend_Filter_Digits as FilterDigits;
use Zend_Validate_File_MimeType as FileMimeTypeValidator;

global $ESCAPER;
$ESCAPER = new Escaper('UTF-8');

/**
 * clean_html replaces up defined inputs
 *
 * @param string $text text string to be cleaned
 * @return string cleared text string
 */
function clean_html($text)
{
    return strip_tags(preg_replace(
        [
            '@<script[^>]*?>.*?</script[\s]*>@si', // remove JavaScript
            '@<[\/\!]*?[^<>]*?>@si', // remove HTML tags
            '@([\r\n])[\s]+@', // remove spaces
            '@&(quot|#34|#034);@i', // change HTML entities
            '@&(apos|#39|#039);@i', // change HTML entities
            '@&(amp|#38);@i',
            '@&(lt|#60);@i',
            '@&(gt|#62);@i',
            '@&(nbsp|#160);@i',
            '@&(iexcl|#161);@i',
            '@&(cent|#162);@i',
            '@&(pound|#163);@i',
            '@&(copy|#169);@i'
            /*'@&#(\d+);@e'*/
        ],
        ['', '', '\1', '"', "'", '&', '<', '>', ' ', chr(161), chr(162), chr(163), chr(169)],
        $text
    ));
}

/**
 * Clean input
 *
 * @param string $input input data (eg. post-var) to be cleaned
 * @return string space trimmed input string
 */
function clean_input($input)
{
    return trim($input, "\x20");
}

/**
 * Filter digits from the given string
 *
 * In case filtering lead to an empty string and if there is no $default value
 * defined, a bad request error (400) is raised.
 *
 * @param string $input String to filter
 * @param string $default Default value if $input is empty after filtering
 * @return string containing only digits
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function filter_digits($input, $default = NULL)
{
    static $filter = NULL;

    if (NULL === $filter) {
        $filter = new FilterDigits();
    }

    $input = $filter->filter(clean_input($input));

    if ($input === '') {
        if (NULL === $default) {
            showBadRequestErrorPage();
        }

        $input = $default;
    }

    return $input;
}

/**
 * Escape a string for the HTML Body context
 *
 * @throws iMSCP_Exception
 * @param string $string String to be converted
 * @param string $escapeType Escape type (html|htmlAttr)
 * @return string HTML entitied text
 */
function tohtml($string, $escapeType = 'html')
{
    global $ESCAPER;

    $string = (string)$string;

    if ($escapeType == 'html') {
        return $ESCAPER->escapeHtml($string);
    }

    if ($escapeType == 'htmlAttr') {
        return $ESCAPER->escapeHtmlAttr($string);
    }

    throw new iMSCPException(sprintf('Unknown escape type: %s', $escapeType));
}

/**
 * Escape a string for the Javascript context
 *
 * @param string $string String to be converted
 * @return string
 */
function tojs($string)
{
    global $ESCAPER;
    return $ESCAPER->escapeJs($string);
}

/**
 * Escape a string for the URI or Parameter contexts.
 *
 * @param string $string String to be converted
 * @return string
 */
function tourl($string)
{
    global $ESCAPER;
    return $ESCAPER->escapeUrl($string);
}

/**
 * Checks if the syntax of the given password is valid
 *
 * @param string $password username to be checked
 * @param string $unallowedChars RegExp for unallowed characters
 * @param bool $noErrorMsg Whether or not error message should be discarded
 * @return bool TRUE if the password is valid, FALSE otherwise
 * @throws Zend_Exception
 */
function checkPasswordSyntax($password, $unallowedChars = '/[^\x21-\x7e]/', $noErrorMsg = false)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');
    $ret = true;
    $passwordLength = strlen($password);

    if ($cfg['PASSWD_CHARS'] < 6) {
        $cfg['PASSWD_CHARS'] = 6;
    } elseif ($cfg['PASSWD_CHARS'] > 30) {
        $cfg['PASSWD_CHARS'] = 30;
    }

    if ($passwordLength < $cfg['PASSWD_CHARS'] || $passwordLength > 30) {
        if (!$noErrorMsg) {
            set_page_message(tr('The password must be between %d and %d characters.', $cfg['PASSWD_CHARS'], 30), 'error');
        }

        $ret = false;
    }

    if (!empty($unallowedChars) && preg_match($unallowedChars, $password)) {
        if (!$noErrorMsg) {
            set_page_message(tr('Password contains unallowed characters.'), 'error');
        }

        $ret = false;
    }

    if ($cfg['PASSWD_STRONG'] && !(preg_match('/[0-9]/', $password) && preg_match('/[a-zA-Z]/', $password))) {
        if (!$noErrorMsg) {
            set_page_message(tr('Password must contain letters and digits.'), 'error');
        }

        $ret = false;
    }

    return $ret;
}

/**
 * Validates a username
 *
 * This function validates syntax of usernames. The characters allowed are all
 * alphanumeric in upper or lower case, the hyphen , the low dash and  the dot,
 * the three latter  being banned at the beginning and end of string.
 *
 * Successive instances of a dot or underscore are prohibited
 *
 * @param string $username the username to be checked
 * @param int $min_char number of min. chars
 * @param int $max_char number min. chars
 * @return boolean True if the username is valid, FALSE otherwise
 */
function validates_username($username, $min_char = 2, $max_char = 30)
{
    $pattern = '@^[[:alnum:]](:?(?<![-_])(:?-*|[_.])?(?![-_])[[:alnum:]]*)*?(?<![-_.])$@';
    return (bool)(preg_match($pattern, $username) && strlen($username) >= $min_char && strlen($username) <= $max_char);
}

/**
 * Check syntax of the given email
 *
 * @param string $email Email addresse to check
 * @param bool $localPartOnly If true, check only the local part
 * @return bool
 * @throws Zend_Validate_Exception
 */
function chk_email($email, $localPartOnly = false)
{
    $options = [];

    if ($localPartOnly) {
        $options['onlyLocalPart'] = true;
    }

    return iMSCP_Validate::getInstance()->email($email, $options);
}

/**
 * Validate a domain name
 *
 * @param string $domainName Domain name
 * @return bool TRUE if the given domain name is valid, FALSE otherwise
 * @throws Zend_Exception
 */
function isValidDomainName($domainName)
{
    global $dmnNameValidationErrMsg;

    if (strpos($domainName, '.') === 0 || substr($domainName, -1) == '.') {
        $dmnNameValidationErrMsg = tr('Domain name cannot start nor end with dot.');
        return false;
    }

    if (($asciiDomainName = encode_idna($domainName)) === false) {
        $dmnNameValidationErrMsg = tr('Invalid domain name.');
        return false;
    }

    $asciiDomainName = strtolower($asciiDomainName);

    if (strlen($asciiDomainName) > 255) {
        $dmnNameValidationErrMsg = tr('Domain name (ASCII form) cannot be greater than 255 characters.');
        return false;
    }

    if (preg_match('/([^a-z0-9\-\.])/', $asciiDomainName, $m)) {
        $dmnNameValidationErrMsg = tr('Domain name contains an invalid character: %s', $m[1]);
        return false;
    }

    if (strpos($asciiDomainName, '..') !== false) {
        $dmnNameValidationErrMsg = tr('Usage of dot in domain name labels is prohibited.');
        return false;
    }

    $labels = explode('.', $asciiDomainName);

    if (sizeof($labels) < 2) {
        $dmnNameValidationErrMsg = tr('Invalid domain name.');
        return false;
    }

    foreach ($labels as $label) {
        if (strlen($label) > 63) {
            $dmnNameValidationErrMsg = tr('Domain name labels cannot be greater than 63 characters.');
            return false;
        }

        # Already done on full domain name above
        #if (preg_match('/([^a-z0-9\-])/', $label, $m)) {
        #    $dmnNameValidationErrMsg = tr("Domain name label '%s' contain an invalid character: %s", $label, $m[1]);
        #    return false;
        #}

        if (preg_match('/^[\-]|[\-]$/', $label)) {
            $dmnNameValidationErrMsg = tr('Domain name labels cannot start nor end with hyphen.');
            return false;
        }
    }

    return true;
}

/**
 * Function for checking i-MSCP limits syntax.
 *
 * @param string $data Limit field data (by default valids are numbers greater
 *                     equal 0)
 * @param mixed $extra single extra permitted value or array of permitted
 *                    values
 * @return bool false incorrect syntax (ranges) true correct syntax (ranges)
 */
function imscp_limit_check($data, $extra = -1)
{
    if ($extra !== NULL
        && !is_bool($extra)
    ) {
        if (is_array($extra)) {
            $nextra = '';
            $max = count($extra);

            foreach ($extra as $n => $element) {
                $nextra = $element . ($n < $max) ? '|' : '';
            }

            $extra = $nextra;
        } else {
            $extra .= '|';
        }
    } else {
        $extra = '';
    }

    return (bool)preg_match("/^(${extra}0|[1-9][0-9]*)$/D", $data);
}

/**
 * Checks if a file match the given mimetype(s)
 *
 * @param  string $pathFile File to check for mimetype
 * @param  array|string $mimeTypes Accepted mimetype(s)
 * @return bool TRUE if the file match the givem mimetype(s), FALSE otherwise
 * @throws Zend_Validate_Exception
 */
function checkMimeType($pathFile, array $mimeTypes)
{
    $mimeTypes['headerCheck'] = true;
    $validator = new FileMimeTypeValidator($mimeTypes);

    if ($validator->isValid($pathFile)) {
        return true;
    }

    return false;
}

/**
 * Get user login data form
 *
 * @param bool $usernameRequired Flag indicating whether username is required
 * @param bool $passwordRequired Flag indicating whether password is required
 * @return Zend_Form
 * @throws Zend_Exception
 * @throws Zend_Form_Exception
 */
function getUserLoginDataForm($usernameRequired = true, $passwordRequired = true)
{
    $cfg = iMSCP_Registry::get('config');
    $minPasswordLength = intval($cfg['PASSWD_CHARS']);

    if ($minPasswordLength < 6) {
        $minPasswordLength = 6;
    }

    $form = new Zend_Form(['elements' => [
        'admin_name'              => ['text', [
            'validators' => [
                ['NotEmpty', true, ['type' => 'string', 'messages' => tr('The username cannot be empty.')]],
                ['Regex', true, '/^[[:alnum:]](:?(?<![-_])(:?-*|[_.])?(?![-_])[[:alnum:]]*)*?(?<![-_.])$/', 'messages' => tr('Invalid username.')],
                ['StringLength', true, ['min' => 2, 'max' => 30, 'messages' => tr('The username must be between %d and %d characters.', 2, 30)]],
                ['Callback', true, [
                    function ($username) {
                        return !exec_query('SELECT COUNT(admin_id) FROM admin WHERE admin_name = ?', $username)->fetchRow(PDO::FETCH_COLUMN);
                    },
                    'messages' => tr("The '%value%' username is not available.")
                ]]
            ],
            'Required'   => true
        ]],
        'admin_pass'              => ['password', [
            'validators' => [
                ['NotEmpty', true, ['type' => 'string', 'messages' => tr('The password cannot be empty.')]],
                [
                    'StringLength',
                    true,
                    [
                        'min'      => $minPasswordLength,
                        'max'      => 30,
                        'messages' => tr('The password must be between %d and %d characters.', $minPasswordLength, 30)
                    ]
                ],
                ['Regex', true, ['/^[\x21-\x7e]+$/', 'messages' => tr('The password contains unallowed characters.')]]
            ],
            'Required'   => true
        ]],
        'admin_pass_confirmation' => ['password', ['validators' => [['Identical', true, ['admin_pass', 'messages' => tr('Passwords do not match.')]]]]]]
    ]);

    if ($cfg['PASSWD_STRONG']) {
        $form->getElement('admin_pass')->addValidator('Callback', true, [
            function ($password) {
                return preg_match('/[0-9]/', $password) && preg_match('/[a-zA-Z]/', $password);
            },
            'messages' => tr('The password must contain letters and digits.'),
        ]);
    }

    if (!$usernameRequired) {
        $form->getElement('admin_name')->removeValidator('NoEmpty')->setRequired(false);
    }

    if (!$passwordRequired) {
        $form->getElement('admin_pass')->removeValidator('NoEmpty')->setRequired(false);
    }

    $form->setElementFilters(['StripTags', 'StringTrim']);
    return $form;
}

/**
 * Get user personal data form
 *
 * @return Zend_Form
 * @throws Zend_Exception
 * @throws Zend_Form_Exception
 */
function getUserPersonalDataForm()
{
    $form = new Zend_Form([
        'elementPrefixPath' => ['validate' => ['prefix' => 'iMSCP_Validate', 'path' => 'iMSCP/Validate/']],
        'elements'          => [
            'fname'   => ['text', ['validators' => [
                //['AlnumAndHyphen', true, ['allowWhiteSpace' => true, 'messages' => tr('Invalid first name.')]],
                ['StringLength', true, ['min' => 1, 'max' => 200, 'messages' => tr('The first name must be between %d and %d characters.', 1, 200)]]
            ]]],
            'lname'   => ['text', ['validators' => [
                //['AlnumAndHyphen', true, ['allowWhiteSpace' => true, 'messages' => tr('Invalid last name.')]],
                ['StringLength', true, ['min' => 1, 'max' => 200, 'messages' => tr('The last name must be between %d and %d characters.', 1, 200)]]
            ]]],
            'gender'  => ['select', ['validators' => [
                ['InArray', true, ['haystack' => ['M', 'F', 'U'], 'strict' => true, 'messages' => tr('Invalid gender.')]],
            ]]],
            'firm'    => ['text', ['validators' => [
                //['AlnumAndHyphen', true, ['allowWhiteSpace' => true, 'messages' => tr('Invalid company.')]],
                ['StringLength', true, ['min' => 1, 'max' => 200, 'messages' => tr('The company name must be between %d and %d characters.', 1, 200)]]
            ]]],
            'street1' => ['text', ['validators' => [
                //['AlnumAndHyphen', true, ['allowWhiteSpace' => true, 'messages' => tr('Invalid street 1.')]],
                ['StringLength', true, ['min' => 1, 'max' => 200, 'messages' => tr('The street 1 name must be between %d and %d characters', 1, 200)]]
            ]]],
            'street2' => ['text', ['validators' => [
                //['AlnumAndHyphen', true, ['allowWhiteSpace' => true, 'messages' => tr('Invalid street 2.')]],
                ['StringLength', true, ['min' => 1, 'max' => 200, 'messages' => tr('The street 2 name must be between %d and %d characters.', 1, 200)]]
            ]]],
            'zip'     => ['text', ['validators' => [
                //['Alnum', true, ['allowWhiteSpace' => true, 'messages' => tr('Invalid zipcode.')]],
                ['StringLength', true, ['min' => 1, 'max' => 10, 'messages' => tr('The zipcode must be between %d and %d characters.', 1, 10)]]
            ]]],
            'city'    => ['text', ['validators' => [
                //['AlnumAndHyphen', true, ['allowWhiteSpace' => true, 'messages' => tr('Invalid city.')]],
                ['StringLength', true, ['min' => 1, 'max' => 200, 'messages' => tr('The city name must be between %d and %d characters.', 1, 200)]]
            ]]],
            'state'   => ['text', ['validators' => [
                //['AlnumAndHyphen', true, ['allowWhiteSpace' => true, 'messages' => tr('Invalid state/province.')]],
                ['StringLength', true, ['min' => 1, 'max' => 200, 'messages' => tr('The state/province name must be between %d and %d characters.', 1, 200)]]
            ]]],
            'country' => ['text', ['validators' => [
                //['AlnumAndHyphen', true, ['allowWhiteSpace' => true, 'messages' => tr('Invalid country.')]],
                ['StringLength', true, ['min' => 1, 'max' => 200, 'messages' => tr('The country name must be between %d and %d characters.', 1, 200)]]
            ]]],
            'email'   => ['text', [
                'validators' => [
                    ['NotEmpty', true, ['type' => 'string', 'messages' => tr('The email address cannot be empty.')]],
                    ['EmailAddress', true, ['messages' => tr('Invalid email address.')]]
                ],
                'Required'   => true
            ]],
            'phone'   => ['text', ['validators' => [
                ['Regex', true, ['/^[0-9()\s.+-]+$/', 'messages' => tr('Invalid phone.')]],
                ['StringLength', true, ['min' => 1, 'max' => 200, 'messages' => tr('The phone number must be between %d and %d characters.', 1, 200)]]
            ]
            ]],
            'fax'     => ['text', ['validators' => [
                ['Regex', true, ['/^[0-9()\s.+]+$/', 'messages' => tr('Invalid phone.')]],
                ['StringLength', true, ['min' => 1, 'max' => 200, 'messages' => tr('The fax number must be between %d and %d characters.', 1, 200)]]
            ]]]
        ]
    ]);

    $form->setElementFilters(['StripTags', 'StringTrim']);
    $form->getElement('email')->addFilter('stringToLower');
    return $form;
}
