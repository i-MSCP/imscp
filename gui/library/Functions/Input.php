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

$ESCAPER = new Zend_Escaper_Escaper('UTF-8');

/**
 * clean_html replaces up defined inputs.
 *
 * @param string $text text string to be cleaned
 * @return string cleared text string
 */
function clean_html($text)
{
    return strip_tags(
        preg_replace(
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
        )
    );
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
 *
 */
function filter_digits($input, $default = NULL)
{
    static $filter = NULL;

    if (NULL === $filter) {
        $filter = new Zend_Filter_Digits();
    }

    $input = $filter->filter(clean_input($input));

    if ($input === '') {
        if (NULL !== $default) {
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
 * @param string $escapeType Escape type ( html|htmlAttr )
 * @return string HTML entitied text
 */
function tohtml($string, $escapeType = 'html')
{
    global $ESCAPER;

    if ($escapeType == 'html') {
        return $ESCAPER->escapeHtml($string);
    }

    if ($escapeType == 'htmlAttr') {
        return $ESCAPER->escapeHtmlAttr($string);
    }

    throw new iMSCP_Exception('Unknown escape type');
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

    if ($passwordLength < $cfg['PASSWD_CHARS']) {
        if (!$noErrorMsg) {
            set_page_message(tr('Password is shorter than %s characters.', $cfg['PASSWD_CHARS']), 'error');
        }

        $ret = false;
    } elseif ($passwordLength > 30) {
        if (!$noErrorMsg) {
            set_page_message(tr('Password cannot be longer than 30 characters.'), 'error');
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
            set_page_message(
                tr('Password must be at least %s characters long and contain letters and numbers to be valid.', $cfg['PASSWD_CHARS']),
                'error'
            );
        }

        $ret = false;
    }

    return $ret;
}

/**
 * Validates a username
 *
 * This function validates syntax of usernames. The characters allowed are all alphanumeric in upper or lower case, the
 * hyphen , the low dash and  the dot, the three latter  being banned at the beginning and end of string.
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
 */
function isValidDomainName($domainName)
{
    global $dmnNameValidationErrMsg;

    if (strpos($domainName, '.') === 0 || substr($domainName, -1) == '.') {
        $dmnNameValidationErrMsg = tr('Domain name cannot start nor end with dot.');
        return false;
    }

    if (($asciiDomainName = encode_idna($domainName)) !== false) {
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

        if (sizeof($labels) > 1) {
            foreach ($labels as $label) {
                if (strlen($label) > 63) {
                    $dmnNameValidationErrMsg = tr('Domain name labels cannot be greater than 63 characters.');
                    return false;
                }

                if (preg_match('/([^a-z0-9\-])/', $label, $m)) {
                    $dmnNameValidationErrMsg = tr("Domain name label '%s' contain an invalid character: %s", $label, $m[1]);
                    return false;
                }

                if (preg_match('/^[\-]|[\-]$/', $label)) {
                    $dmnNameValidationErrMsg = tr('Domain name labels cannot start nor end with hyphen.');
                    return false;
                }
            }
        } else {
            $dmnNameValidationErrMsg = tr('Invalid domain name.');
            return false;
        }
    } else {
        $dmnNameValidationErrMsg = tr('Invalid domain name.');
        return false;
    }

    return true;
}

/**
 * Function for checking i-MSCP limits syntax.
 *
 * @param string $data Limit field data (by default valids are numbers greater equal 0)
 * @param mixed $extra single extra permitted value or array of permitted values
 * @return bool false incorrect syntax (ranges) true correct syntax (ranges)
 */
function imscp_limit_check($data, $extra = -1)
{
    if ($extra !== NULL && !is_bool($extra)) {
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
 */
function checkMimeType($pathFile, array $mimeTypes)
{
    $mimeTypes['headerCheck'] = true;
    $validator = new Zend_Validate_File_MimeType($mimeTypes);

    if ($validator->isValid($pathFile)) {
        return true;
    }

    return false;
}
