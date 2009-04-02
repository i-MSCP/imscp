<?php


if (!function_exists('get_smtp_user'))
{
function get_smtp_user(&$user, &$pass) {
    global $username, $smtp_auth_mech,
           $smtp_sitewide_user, $smtp_sitewide_pass;

    if ($smtp_auth_mech == 'none') {
        $user = '';
        $pass = '';
    } elseif ( isset($smtp_sitewide_user) && isset($smtp_sitewide_pass) &&
               !empty($smtp_sitewide_user)) {
        $user = $smtp_sitewide_user;
        $pass = $smtp_sitewide_pass;
    } else {
        $user = $username;
        $pass = sqauth_read_password();
    }

    // plugin authors note: override $user or $pass by
    // returning an array where the new username is the
    // first array value and the new password is the
    // second array value e.g., return array($myuser, $mypass);
    //
    $ret = do_hook_function('smtp_auth', array($user, $pass));
    if (!empty($ret[0]))
        $user = $ret[0];
    if (!empty($ret[1]))
        $pass = $ret[1];
}
}



