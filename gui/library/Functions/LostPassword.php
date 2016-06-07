<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2016 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/**
 * Create captcha image
 *
 * @throws iMSCP_Exception
 * @param  string $strSessionVar
 * @return void
 */
function createImage($strSessionVar)
{
    $cfg = iMSCP_Registry::get('config');
    $rgBgColor = $cfg['LOSTPASSWORD_CAPTCHA_BGCOLOR'];
    $rgTextColor = $cfg['LOSTPASSWORD_CAPTCHA_TEXTCOLOR'];

    if (!($image = imagecreate($cfg['LOSTPASSWORD_CAPTCHA_WIDTH'], $cfg['LOSTPASSWORD_CAPTCHA_HEIGHT']))) {
        throw new iMSCP_Exception('Cannot initialize new GD image stream.');
    }

    imagecolorallocate($image, $rgBgColor[0], $rgBgColor[1], $rgBgColor[2]);
    $textColor = imagecolorallocate($image, $rgTextColor[0], $rgTextColor[1], $rgTextColor[2]);
    $white = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);
    $nbLetters = 6;

    $x = ($cfg['LOSTPASSWORD_CAPTCHA_WIDTH'] / 2) - ($nbLetters * 20 / 2);
    $y = mt_rand(15, 30);

    $string = \iMSCP\Crypt::randomStr($nbLetters);
    for ($i = 0; $i < $nbLetters; $i++) {
        $fontFile = LIBRARY_PATH . '/Resources/Fonts/' . $cfg['LOSTPASSWORD_CAPTCHA_FONTS'][mt_rand(0, count($cfg['LOSTPASSWORD_CAPTCHA_FONTS']) - 1)];
        imagettftext($image, 20, 0, $x, $y, $textColor, $fontFile, $string[$i]);
        $x += 20;
        $y = mt_rand(15, 25);
    }

    $_SESSION[$strSessionVar] = $string;

    // obfuscation
    for ($i = 0; $i < 5; $i++) {
        $x1 = mt_rand(0, $x - 1);
        $y1 = mt_rand(0, round($y / 10, 0));
        $x2 = mt_rand(0, round($x / 10, 0));
        $y2 = mt_rand(0, $y - 1);
        imageline($image, $x1, $y1, $x2, $y2, $white);
        $x1 = mt_rand(0, $x - 1);
        $y1 = $y - mt_rand(1, round($y / 10, 0));
        $x2 = $x - mt_rand(1, round($x / 10, 0));
        $y2 = mt_rand(0, $y - 1);
        imageline($image, $x1, $y1, $x2, $y2, $white);
    }

    header('Content-type: image/png');
    imagepng($image); // create and send PNG image
    imagedestroy($image); // destroy image from server
}

/**
 * Remove old keys
 *
 * @param int $ttl
 * @return void
 */
function removeOldKeys($ttl)
{
    exec_query(
        'UPDATE admin SET uniqkey = NULL, uniqkey_time = NULL WHERE uniqkey_time < ?',
        date('Y-m-d H:i:s', time() - $ttl * 60)
    );
}

/**
 * Sets unique key
 *
 * @param string $adminName
 * @param string $uniqueKey
 * @return void
 */
function setUniqKey($adminName, $uniqueKey)
{
    exec_query('UPDATE admin SET uniqkey = ?, uniqkey_time = ? WHERE admin_name = ?', array(
        $uniqueKey, date('Y-m-d H:i:s', time()), $adminName
    ));
}

/**
 * Set password
 *
 * @param string $uniqueKey
 * @param string $userPassword
 * @return void
 */
function setPassword($uniqueKey, $userPassword)
{
    exec_query('UPDATE admin SET admin_pass = ?, uniqkey = NULL, uniqkey_time = NULL WHERE uniqkey = ?', array(
        cryptPasswordWithSalt($userPassword), $uniqueKey
    ));
}

/**
 * Checks for unique key existence
 *
 * @param string $uniqueKey
 * @return bool TRUE if the key exists, FALSE otherwise
 */
function uniqueKeyExists($uniqueKey)
{
    $stmt = exec_query('SELECT uniqkey FROM admin WHERE uniqkey = ?', $uniqueKey);
    return (bool)$stmt->rowCount();
}

/**
 * generate unique key
 *
 * @return string Unique key
 */
function uniqkeygen()
{
    do {
        $uniqueKey = sha1(\iMSCP\Crypt::randomStr(32));
    } while (uniqueKeyExists($uniqueKey));

    return $uniqueKey;
}

/**
 * Send password request validation
 *
 * @param string $adminName
 * @return bool TRUE on success, FALSE otherwise
 */
function sendPasswordRequestValidation($adminName)
{
    $stmt = exec_query('SELECT admin_id, created_by, fname, lname, email FROM admin WHERE admin_name = ?', $adminName);
    if (!$stmt->rowCount()) {
        set_page_message(tr('Wrong username.'), 'error');
        return false;
    }
    $row = $stmt->fetchRow();

    $createdBy = $row['created_by'];
    if ($createdBy == 0) {
        $createdBy = $row['admin_id']; // Force usage of default template for any admin request
    }

    $data = get_lostpassword_activation_email($createdBy);

    # Create uniq key for password request validation
    $uniqueKey = uniqkeygen();
    setUniqKey($adminName, $uniqueKey);

    $ret = send_mail(array(
        'mail_id' => 'lostpw-msg-1',
        'fname' => $row['fname'],
        'lname' => $row['lname'],
        'username' => $adminName,
        'email' => $row['email'],
        'subject' => $data['subject'],
        'message' => $data['message'],
        'placeholders' => array(
            '{LINK}' => "{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}{BASE_SERVER_VHOST_PORT}/lostpassword.php?key=$uniqueKey",
        )
    ));

    if (!$ret) {
        write_log(sprintf('Could not send new password request validation to %s', $adminName), E_USER_ERROR);
        set_page_message(tr('An unexpected error occurred. Please contact your administrator.'));
        return false;
    }

    return true;
}

/**
 * Send new password
 *
 * @param string $uniqueKey
 * @return bool TRUE when password was sended, FALSE otherwise
 */
function sendPassword($uniqueKey)
{
    $stmt = exec_query(
        'SELECT admin_id, admin_name, created_by, fname, lname, email, uniqkey FROM admin WHERE uniqkey = ?', $uniqueKey
    );

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow();

        if (!\iMSCP\Crypt::hashEqual($row['uniqkey'], $uniqueKey)) {
            showBadRequestErrorPage();
        }

        # Generate new user password
        $userPassword = passgen();
        setPassword($uniqueKey, $userPassword);
        write_log(sprintf('Lostpassword: A New password has been set for %s user', $row['admin_name']), E_USER_NOTICE);

        $createdBy = $row['created_by'];
        if ($createdBy == 0) {
            $createdBy = $row['admin_id'];
        }

        $data = get_lostpassword_password_email($createdBy);
        $ret = send_mail(array(
            'mail_id' => 'lostpw-msg-2',
            'fname' => $row['fname'],
            'lname' => $row['lname'],
            'username' => $row['admin_name'],
            'email' => $row['email'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'placeholders' => array(
                '{PASSWORD}' => $userPassword
            )
        ));

        if (!$ret) {
            write_log(sprintf('Could not send new passsword to %s', $row['admin_name']), E_USER_ERROR);
            set_page_message(tr('An unexpected error occurred. Please contact your administrator.'));
            return false;
        }

        return true;
    } else {
        set_page_message(tr('Your request for password renewal is either invalid or has expired.'), 'error');
    }

    return false;
}
