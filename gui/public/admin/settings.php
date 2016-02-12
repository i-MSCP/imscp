<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by i-MSCP Team
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

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);
check_login('admin');

$cfg = iMSCP_Registry::get('config');

if (!empty($_POST)) {
    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditAdminGeneralSettings);

    $checkForUpdate = isset($_POST['checkforupdate']) ? clean_input($_POST['checkforupdate']) : $cfg['CHECK_FOR_UPDATES'];

    $lostPasswd = isset($_POST['lostpassword']) ? clean_input($_POST['lostpassword']) : $cfg['LOSTPASSWORD'];
    $lostPasswdTimeout = isset($_POST['lostpassword_timeout']) ? clean_input($_POST['lostpassword_timeout']) : $cfg['LOSTPASSWORD_TIMEOUT'];

    $passwdStrong = isset($_POST['passwd_strong']) ? clean_input($_POST['passwd_strong']) : $cfg['PASSWD_STRONG'];
    $passwdChars = isset($_POST['passwd_chars']) ? clean_input($_POST['passwd_chars']) : $cfg['PASSWD_CHARS'];

    $bruteforce = isset($_POST['bruteforce']) ? clean_input($_POST['bruteforce']) : $cfg['BRUTEFORCE'];
    $bruteforceBetween = isset($_POST['bruteforce_between']) ? clean_input($_POST['bruteforce_between']) : $cfg['BRUTEFORCE_BETWEEN'];
    $bruteforceMaxLogin = isset($_POST['bruteforce_max_login']) ? clean_input($_POST['bruteforce_max_login']) : $cfg['BRUTEFORCE_MAX_LOGIN'];
    $bruteforceBlockTime = isset($_POST['bruteforce_block_time']) ? clean_input($_POST['bruteforce_block_time']) : $cfg['BRUTEFORCE_BLOCK_TIME'];
    $bruteforceBetweenTime = isset($_POST['bruteforce_block_time']) ? clean_input($_POST['bruteforce_between_time']) : $cfg['BRUTEFORCE_BETWEEN_TIME'];
    $bruteforceMaxCapcha = isset($_POST['bruteforce_max_capcha']) ? clean_input($_POST['bruteforce_max_capcha']) : $cfg['BRUTEFORCE_MAX_CAPTCHA'];
    $bruteforceMaxAttemptsBeforeWait = isset($_POST['bruteforce_max_attempts_before_wait']) ? clean_input($_POST['bruteforce_max_attempts_before_wait']) : $cfg['BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT'];

    $createDefaultEmails = isset($_POST['create_default_email_addresses']) ? clean_input($_POST['create_default_email_addresses']) : $cfg['CREATE_DEFAULT_EMAIL_ADDRESSES'];
    $countDefaultEmails = isset($_POST['count_default_email_addresses']) ? clean_input($_POST['count_default_email_addresses']) : $cfg['COUNT_DEFAULT_EMAIL_ADDRESSES'];
    $hardMailSuspension = isset($_POST['hard_mail_suspension']) ? clean_input($_POST['hard_mail_suspension']) : $cfg['HARD_MAIL_SUSPENSION'];
    $emailQuotaSyncMode = isset($_POST['email_quota_sync_mode']) ? clean_input($_POST['email_quota_sync_mode']) : $cfg['EMAIL_QUOTA_SYNC_MODE'];

    $userInitialLang = isset($_POST['def_language']) ? clean_input($_POST['def_language']) : $cfg['USER_INITIAL_LANG'];
    $supportSystem = isset($_POST['support_system']) ? clean_input($_POST['support_system']) : $cfg['IMSCP_SUPPORT_SYSTEM'];
    $compressOutput = isset($_POST['compress_output']) ? clean_input($_POST['compress_output']) : $cfg['COMPRESS_OUTPUT'];
    $showCompressionSize = isset($_POST['show_compression_size']) ? clean_input($_POST['show_compression_size']) : $cfg['SHOW_COMPRESSION_SIZE'];
    $domainRowsPerPage = isset($_POST['domain_rows_per_page']) ? clean_input($_POST['domain_rows_per_page']) : $cfg['DOMAIN_ROWS_PER_PAGE'];
    $logLevel = isset($_POST['log_level']) && in_array($_POST['log_level'], array('0', 'E_USER_ERROR', 'E_USER_WARNING', 'E_USER_NOTICE')) ? $_POST['log_level'] : $cfg['LOG_LEVEL'];
    $prevExtLoginAdmin = isset($_POST['prevent_external_login_admin']) ? clean_input($_POST['prevent_external_login_admin']) : $cfg['PREVENT_EXTERNAL_LOGIN_ADMIN'];
    $prevExtLoginReseller = isset($_POST['prevent_external_login_reseller']) ? clean_input($_POST['prevent_external_login_reseller']) : $cfg['PREVENT_EXTERNAL_LOGIN_RESELLER'];
    $prevExtLoginClient = isset($_POST['prevent_external_login_client']) ? clean_input($_POST['prevent_external_login_client']) : $cfg['PREVENT_EXTERNAL_LOGIN_CLIENT'];
    $enableSSL = isset($_POST['enableSSL']) ? clean_input($_POST['enableSSL']) : $cfg['ENABLE_SSL'];

    if (
        !is_number($checkForUpdate) || !is_number($lostPasswd) || !is_number($passwdStrong) || !is_number($bruteforce)
        || !is_number($bruteforceBetween) || !is_number($createDefaultEmails) || !is_number($countDefaultEmails)
        || !is_number($hardMailSuspension) || !is_number($emailQuotaSyncMode) || !is_number($supportSystem)
        || !is_number($compressOutput) || !is_number($showCompressionSize) || !is_number($prevExtLoginAdmin)
        || !is_number($prevExtLoginReseller) || !is_number($prevExtLoginClient) || !is_number($enableSSL)
    ) {
        showBadRequestErrorPage();
    }

    if (
        !is_number($lostPasswdTimeout) || !is_number($passwdChars) || !is_number($bruteforceMaxLogin)
        || !is_number($bruteforceBlockTime) || !is_number($bruteforceBetweenTime) || !is_number($bruteforceMaxCapcha)
        || !is_number($bruteforceMaxAttemptsBeforeWait) || !is_number($domainRowsPerPage)
    ) {
        set_page_message(tr('Only positive numbers are allowed.'), 'error');
    } elseif ($domainRowsPerPage < 1) {
        $domainRowsPerPage = 1;
    } else {
        /** @var iMSCP_Config_Handler_Db $dbCfg */
        $dbCfg = iMSCP_Registry::get('dbConfig');

        $dbCfg['CHECK_FOR_UPDATES'] = $checkForUpdate;
        $dbCfg['LOSTPASSWORD'] = $lostPasswd;
        $dbCfg['LOSTPASSWORD_TIMEOUT'] = $lostPasswdTimeout;
        $dbCfg['PASSWD_STRONG'] = $passwdStrong;
        $dbCfg['PASSWD_CHARS'] = $passwdChars;
        $dbCfg['BRUTEFORCE'] = $bruteforce;
        $dbCfg['BRUTEFORCE_BETWEEN'] = $bruteforceBetween;
        $dbCfg['BRUTEFORCE_MAX_LOGIN'] = $bruteforceMaxLogin;
        $dbCfg['BRUTEFORCE_BLOCK_TIME'] = $bruteforceBlockTime;
        $dbCfg['BRUTEFORCE_BETWEEN_TIME'] = $bruteforceBetweenTime;
        $dbCfg['BRUTEFORCE_MAX_CAPTCHA'] = $bruteforceMaxCapcha;
        $dbCfg['BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT'] = $bruteforceMaxAttemptsBeforeWait;
        $dbCfg['CREATE_DEFAULT_EMAIL_ADDRESSES'] = $createDefaultEmails;
        $dbCfg['COUNT_DEFAULT_EMAIL_ADDRESSES'] = $countDefaultEmails;
        $dbCfg['HARD_MAIL_SUSPENSION'] = $hardMailSuspension;
        $dbCfg['EMAIL_QUOTA_SYNC_MODE'] = $emailQuotaSyncMode;
        $dbCfg['USER_INITIAL_LANG'] = $userInitialLang;
        $dbCfg['IMSCP_SUPPORT_SYSTEM'] = $supportSystem;
        $dbCfg['DOMAIN_ROWS_PER_PAGE'] = $domainRowsPerPage;
        $dbCfg['LOG_LEVEL'] = $logLevel;
        $dbCfg['COMPRESS_OUTPUT'] = $compressOutput;
        $dbCfg['SHOW_COMPRESSION_SIZE'] = $showCompressionSize;
        $dbCfg['PREVENT_EXTERNAL_LOGIN_ADMIN'] = $prevExtLoginAdmin;
        $dbCfg['PREVENT_EXTERNAL_LOGIN_RESELLER'] = $prevExtLoginReseller;
        $dbCfg['PREVENT_EXTERNAL_LOGIN_CLIENT'] = $prevExtLoginClient;
        $dbCfg['ENABLE_SSL'] = $enableSSL;

        $cfg->merge($dbCfg);
        iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditAdminGeneralSettings);

        $updtCount = $dbCfg->countQueries('update');
        $newCount = $dbCfg->countQueries('insert');

        if ($updtCount > 0) {
            set_page_message(tr('%d configuration parameter(s) have/has been updated.', $updtCount), 'success');
        }

        if ($newCount > 0) {
            set_page_message(tr('%d configuration parameter(s) have/has been created.', $newCount), 'success');
        }

        if ($newCount == 0 && $updtCount == 0) {
            set_page_message(tr('Nothing has been changed.'), 'info');
        } else {
            write_log(sprintf('Settings were updated by %s.', $_SESSION['user_logged']), E_USER_NOTICE);
        }
    }

    redirectTo('settings.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'admin/settings.tpl',
    'page_message' => 'layout',
    'def_language' => 'page'
));


if ($cfg['CHECK_FOR_UPDATES']) {
    $tpl->assign(array(
        'CHECK_FOR_UPDATES_SELECTED_ON' => ' selected',
        'CHECK_FOR_UPDATES_SELECTED_OFF' => ''
    ));
} else {
    $tpl->assign(array(
        'CHECK_FOR_UPDATES_SELECTED_ON' => '',
        'CHECK_FOR_UPDATES_SELECTED_OFF' => ' selected'
    ));
}

if ($cfg['LOSTPASSWORD']) {
    $tpl->assign(array(
        'LOSTPASSWORD_SELECTED_ON' => ' selected',
        'LOSTPASSWORD_SELECTED_OFF' => ''
    ));
} else {
    $tpl->assign(array(
        'LOSTPASSWORD_SELECTED_ON' => '',
        'LOSTPASSWORD_SELECTED_OFF', ' selected'
    ));
}

if ($cfg['PASSWD_STRONG']) {
    $tpl->assign(array(
        'PASSWD_STRONG_ON' => ' selected',
        'PASSWD_STRONG_OFF' => ''
    ));
} else {
    $tpl->assign(array(
        'PASSWD_STRONG_ON' => '',
        'PASSWD_STRONG_OFF' => ' selected'
    ));
}

if ($cfg['BRUTEFORCE']) {
    $tpl->assign(array(
        'BRUTEFORCE_SELECTED_ON' => 'selected',
        'BRUTEFORCE_SELECTED_OFF' => ''
    ));
} else {
    $tpl->assign(array(
        'BRUTEFORCE_SELECTED_ON' => '',
        'BRUTEFORCE_SELECTED_OFF' => ' selected'
    ));
}

if ($cfg['BRUTEFORCE_BETWEEN']) {
    $tpl->assign(array(
        'BRUTEFORCE_BETWEEN_SELECTED_ON' => ' selected',
        'BRUTEFORCE_BETWEEN_SELECTED_OFF' => ''
    ));
} else {
    $tpl->assign(array(
        'BRUTEFORCE_BETWEEN_SELECTED_ON' => '',
        'BRUTEFORCE_BETWEEN_SELECTED_OFF' => ' selected'
    ));
}

if ($cfg['IMSCP_SUPPORT_SYSTEM']) {
    $tpl->assign(array(
        'SUPPORT_SYSTEM_SELECTED_ON' => ' selected',
        'SUPPORT_SYSTEM_SELECTED_OFF' => ''
    ));
} else {
    $tpl->assign(array(
        'SUPPORT_SYSTEM_SELECTED_ON' => '',
        'SUPPORT_SYSTEM_SELECTED_OFF' => ' selected'
    ));
}

if ($cfg['CREATE_DEFAULT_EMAIL_ADDRESSES']) {
    $tpl->assign(array(
        'CREATE_DEFAULT_EMAIL_ADDRESSES_ON' => ' selected',
        'CREATE_DEFAULT_EMAIL_ADDRESSES_OFF' => ''
    ));
} else {
    $tpl->assign(array(
        'CREATE_DEFAULT_EMAIL_ADDRESSES_ON' => '',
        'CREATE_DEFAULT_EMAIL_ADDRESSES_OFF' => ' selected'
    ));
}

if ($cfg['COUNT_DEFAULT_EMAIL_ADDRESSES']) {
    $tpl->assign(array(
        'COUNT_DEFAULT_EMAIL_ADDRESSES_ON' => ' selected',
        'COUNT_DEFAULT_EMAIL_ADDRESSES_OFF' => ''
    ));
} else {
    $tpl->assign(array(
        'COUNT_DEFAULT_EMAIL_ADDRESSES_ON' => '',
        'COUNT_DEFAULT_EMAIL_ADDRESSES_OFF' => ' selected'
    ));
}

if ($cfg['HARD_MAIL_SUSPENSION']) {
    $tpl->assign(array(
        'HARD_MAIL_SUSPENSION_ON' => ' selected',
        'HARD_MAIL_SUSPENSION_OFF' => ''
    ));
} else {
    $tpl->assign(array(
        'HARD_MAIL_SUSPENSION_ON' => '',
        'HARD_MAIL_SUSPENSION_OFF' => ' selected'
    ));
}

if (isset($cfg['EMAIL_QUOTA_SYNC_MODE']) && $cfg['EMAIL_QUOTA_SYNC_MODE']) {
    $tpl->assign(array(
        'REDISTRIBUTE_EMAIl_QUOTA_YES' => ' selected',
        'REDISTRIBUTE_EMAIl_QUOTA_NO' => ''
    ));
} else {
    $tpl->assign(array(
        'REDISTRIBUTE_EMAIl_QUOTA_YES' => '',
        'REDISTRIBUTE_EMAIl_QUOTA_NO' => ' selected'
    ));
}

if ($cfg['COMPRESS_OUTPUT']) {
    $tpl->assign(array(
        'COMPRESS_OUTPUT_ON' => ' selected',
        'COMPRESS_OUTPUT_OFF' => ''
    ));
} else {
    $tpl->assign(array(
        'COMPRESS_OUTPUT_ON' => '',
        'COMPRESS_OUTPUT_OFF' => ' selected'
    ));
}

if ($cfg['SHOW_COMPRESSION_SIZE']) {
    $tpl->assign(array(
        'SHOW_COMPRESSION_SIZE_SELECTED_ON' => ' selected',
        'SHOW_COMPRESSION_SIZE_SELECTED_OFF' => ''
    ));
} else {
    $tpl->assign(array(
        'SHOW_COMPRESSION_SIZE_SELECTED_ON' => '',
        'SHOW_COMPRESSION_SIZE_SELECTED_OFF' => ' selected'
    ));
}

if ($cfg['PREVENT_EXTERNAL_LOGIN_ADMIN']) {
    $tpl->assign(array(
        'PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_ON' => ' selected',
        'PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_OFF' => ''
    ));
} else {
    $tpl->assign(array(
        'PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_ON' => '',
        'PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_OFF' => ' selected'
    ));
}

if ($cfg['PREVENT_EXTERNAL_LOGIN_RESELLER']) {
    $tpl->assign(array(
        'PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_ON' => ' selected',
        'PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_OFF' => ''
    ));
} else {
    $tpl->assign(array(
        'PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_ON' => '',
        'PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_OFF' => ' selected'
    ));
}

if ($cfg['PREVENT_EXTERNAL_LOGIN_CLIENT']) {
    $tpl->assign(array(
            'PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_ON' => ' selected',
            'PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_OFF' => ''
        )
    );
} else {
    $tpl->assign(array(
        'PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_ON' => '',
        'PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_OFF' => ' selected'
    ));
}

switch ($cfg['LOG_LEVEL']) {
    case false:
        $tpl->assign(array(
            'LOG_LEVEL_SELECTED_OFF' => ' selected',
            'LOG_LEVEL_SELECTED_NOTICE' => '',
            'LOG_LEVEL_SELECTED_WARNING' => '',
            'LOG_LEVEL_SELECTED_ERROR' => ''
        ));
        break;
    case E_USER_NOTICE:
        $tpl->assign(array(
            'LOG_LEVEL_SELECTED_OFF' => '',
            'LOG_LEVEL_SELECTED_NOTICE' => ' selected',
            'LOG_LEVEL_SELECTED_WARNING' => '',
            'LOG_LEVEL_SELECTED_ERROR' => ''
        ));
        break;
    case E_USER_WARNING:
        $tpl->assign(array(
            'LOG_LEVEL_SELECTED_OFF' => '',
            'LOG_LEVEL_SELECTED_NOTICE' => '',
            'LOG_LEVEL_SELECTED_WARNING' => ' selected',
            'LOG_LEVEL_SELECTED_ERROR' => ''
        ));
        break;
    default:
        $tpl->assign(array(
            'LOG_LEVEL_SELECTED_OFF' => '',
            'LOG_LEVEL_SELECTED_NOTICE' => '',
            'LOG_LEVEL_SELECTED_WARNING' => '',
            'LOG_LEVEL_SELECTED_ERROR' => ' selected'
        ));
}

if ($cfg['ENABLE_SSL']) {
    $tpl->assign(array(
        'ENABLE_SSL_ON' => ' selected',
        'ENABLE_SSL_OFF' => ''
    ));
} else {
    $tpl->assign(array(
        'ENABLE_SSL_ON' => '',
        'ENABLE_SSL_OFF' => ' selected'
    ));
}

$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Admin / Settings'),
    'TR_UPDATES' => tr('Updates'),
    'LOSTPASSWORD_TIMEOUT_VALUE' => tohtml($cfg['LOSTPASSWORD_TIMEOUT']),
    'PASSWD_CHARS' => tohtml($cfg['PASSWD_CHARS']),
    'BRUTEFORCE_MAX_LOGIN_VALUE' => tohtml($cfg['BRUTEFORCE_MAX_LOGIN']),
    'BRUTEFORCE_BLOCK_TIME_VALUE' => tohtml($cfg['BRUTEFORCE_BLOCK_TIME']),
    'BRUTEFORCE_BETWEEN_TIME_VALUE' => tohtml($cfg['BRUTEFORCE_BETWEEN_TIME']),
    'BRUTEFORCE_MAX_CAPTCHA' => tohtml($cfg['BRUTEFORCE_MAX_CAPTCHA']),
    'BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT' => $cfg['BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT'],
    'DOMAIN_ROWS_PER_PAGE' => tohtml($cfg['DOMAIN_ROWS_PER_PAGE']),
    'TR_SETTINGS' => tr('Settings'),
    'TR_MESSAGE' => tr('Message'),
    'TR_LOSTPASSWORD' => tr('Lost password'),
    'TR_LOSTPASSWORD_TIMEOUT' => tr('Activation link expire time <small>(In minutes)</small>'),
    'TR_PASSWORD_SETTINGS' => tr('Password settings'),
    'TR_PASSWD_STRONG' => tr('Strong passwords'),
    'TR_PASSWD_CHARS' => tr('Password minimum length'),
    'TR_BRUTEFORCE' => tr('Bruteforce detection'),
    'TR_BRUTEFORCE_BETWEEN' => tr('Blocking time between logins and captcha attempts'),
    'TR_BRUTEFORCE_MAX_LOGIN' => tr('Max number of login attempts'),
    'TR_BRUTEFORCE_BLOCK_TIME' => tr('Blocktime <small>(in minutes)</small>'),
    'TR_BRUTEFORCE_BETWEEN_TIME' => tr('Blocking time between login/captcha attempts <small>(In seconds)</small>'),
    'TR_BRUTEFORCE_MAX_CAPTCHA' => tr('Maximum number of captcha validation attempts'),
    'TR_BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT' => tr('Maximum number of validation attempts before waiting restriction intervenes'),
    'TR_OTHER_SETTINGS' => tr('Other settings'),
    'TR_MAIL_SETTINGS' => tr('Email settings'),
    'TR_CREATE_DEFAULT_EMAIL_ADDRESSES' => tr('Create default email addresses'),
    'TR_COUNT_DEFAULT_EMAIL_ADDRESSES' => tr('Count default email addresses'),
    'TR_HARD_MAIL_SUSPENSION' => tr('Email accounts are hard suspended'),
    'TR_EMAIL_QUOTA_SYNC_MODE' => tr('Redistribute unused quota across existing mailboxes'),
    'TR_USER_INITIAL_LANG' => tr('Panel default language'),
    'TR_SUPPORT_SYSTEM' => tr('Support system'),
    'TR_ENABLED' => tr('Enabled'),
    'TR_DISABLED' => tr('Disabled'),
    'TR_YES' => tr('Yes'),
    'TR_NO' => tr('No'),
    'TR_UPDATE' => tr('Update'),
    'TR_SERVERPORTS' => tr('Server ports'),
    'TR_ADMIN' => tr('Admin'),
    'TR_RESELLER' => tr('Reseller'),
    'TR_DOMAIN_ROWS_PER_PAGE' => tr('Domains per page'),
    'TR_LOG_LEVEL' => tr('Mail Log Level'),
    'TR_E_USER_OFF' => tr('Disabled'),
    'TR_E_USER_NOTICE' => tr('Notices, Warnings and Errors'),
    'TR_E_USER_WARNING' => tr('Warnings and Errors'),
    'TR_E_USER_ERROR' => tr('Errors'),
    'TR_CHECK_FOR_UPDATES' => tr('Check for update'),
    'TR_ENABLE_SSL' => tr('Enable SSL'),
    'TR_SSL_HELP' => tr('Defines whether or not customers can add/change SSL certificates for their domains.'),
    'TR_COMPRESS_OUTPUT' => tr('Compress HTML output'),
    'TR_SHOW_COMPRESSION_SIZE' => tr('Show HTML output compression size comment'),
    'TR_PREVENT_EXTERNAL_LOGIN_ADMIN' => tr('Prevent external login for admins'),
    'TR_PREVENT_EXTERNAL_LOGIN_RESELLER' => tr('Prevent external login for resellers'),
    'TR_PREVENT_EXTERNAL_LOGIN_CLIENT' => tr('Prevent external login for clients')
));

generateNavigation($tpl);
gen_def_language($tpl, $cfg['USER_INITIAL_LANG']);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();

unsetMessages();
