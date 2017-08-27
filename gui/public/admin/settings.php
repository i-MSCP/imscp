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

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('admin');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

$cfg = iMSCP_Registry::get('config');

if (!empty($_POST)) {
    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditAdminGeneralSettings);

    $checkForUpdate = isset($_POST['checkforupdate']) ? clean_input($_POST['checkforupdate']) : $cfg['CHECK_FOR_UPDATES'];

    $lostPasswd = isset($_POST['lostpassword']) ? clean_input($_POST['lostpassword']) : $cfg['LOSTPASSWORD'];
    $lostPasswdTimeout = isset($_POST['lostpassword_timeout']) ? clean_input($_POST['lostpassword_timeout']) : $cfg['LOSTPASSWORD_TIMEOUT'];

    $passwdStrong = isset($_POST['passwd_strong']) ? clean_input($_POST['passwd_strong']) : $cfg['PASSWD_STRONG'];
    $passwdChars = isset($_POST['passwd_chars']) ? clean_input($_POST['passwd_chars']) : $cfg['PASSWD_CHARS'];

    $bruteforce = isset($_POST['bruteforce']) ? clean_input($_POST['bruteforce']) : $cfg['BRUTEFORCE'];
    $bruteforceBetween = isset($_POST['bruteforce_between'])
        ? clean_input($_POST['bruteforce_between']) : $cfg['BRUTEFORCE_BETWEEN'];
    $bruteforceMaxLogin = isset($_POST['bruteforce_max_login'])
        ? clean_input($_POST['bruteforce_max_login']) : $cfg['BRUTEFORCE_MAX_LOGIN'];
    $bruteforceBlockTime = isset($_POST['bruteforce_block_time'])
        ? clean_input($_POST['bruteforce_block_time']) : $cfg['BRUTEFORCE_BLOCK_TIME'];
    $bruteforceBetweenTime = isset($_POST['bruteforce_block_time'])
        ? clean_input($_POST['bruteforce_between_time']) : $cfg['BRUTEFORCE_BETWEEN_TIME'];
    $bruteforceMaxCapcha = isset($_POST['bruteforce_max_capcha'])
        ? clean_input($_POST['bruteforce_max_capcha']) : $cfg['BRUTEFORCE_MAX_CAPTCHA'];
    $bruteforceMaxAttemptsBeforeWait = isset($_POST['bruteforce_max_attempts_before_wait'])
        ? clean_input($_POST['bruteforce_max_attempts_before_wait']) : $cfg['BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT'];

    $createDefaultEmails = isset($_POST['create_default_email_addresses'])
        ? clean_input($_POST['create_default_email_addresses']) : $cfg['CREATE_DEFAULT_EMAIL_ADDRESSES'];
    $countDefaultEmails = isset($_POST['count_default_email_addresses'])
        ? clean_input($_POST['count_default_email_addresses']) : $cfg['COUNT_DEFAULT_EMAIL_ADDRESSES'];
    $protecttDefaultEmails = isset($_POST['protect_default_email_addresses'])
        ? clean_input($_POST['protect_default_email_addresses']) : $cfg['PROTECT_DEFAULT_EMAIL_ADDRESSES'];
    $hardMailSuspension = isset($_POST['hard_mail_suspension'])
        ? clean_input($_POST['hard_mail_suspension']) : $cfg['HARD_MAIL_SUSPENSION'];
    $emailQuotaSyncMode = isset($_POST['email_quota_sync_mode'])
        ? clean_input($_POST['email_quota_sync_mode']) : $cfg['EMAIL_QUOTA_SYNC_MODE'];

    $userInitialLang = isset($_POST['def_language'])
        ? clean_input($_POST['def_language']) : $cfg['USER_INITIAL_LANG'];
    $supportSystem = isset($_POST['support_system'])
        ? clean_input($_POST['support_system']) : $cfg['IMSCP_SUPPORT_SYSTEM'];
    $domainRowsPerPage = isset($_POST['domain_rows_per_page'])
        ? clean_input($_POST['domain_rows_per_page']) : $cfg['DOMAIN_ROWS_PER_PAGE'];
    $logLevel = isset($_POST['log_level']) && in_array($_POST['log_level'], ['0', 'E_USER_ERROR', 'E_USER_WARNING', 'E_USER_NOTICE'])
        ? $_POST['log_level'] : $cfg['LOG_LEVEL'];
    $prevExtLoginAdmin = isset($_POST['prevent_external_login_admin'])
        ? clean_input($_POST['prevent_external_login_admin']) : $cfg['PREVENT_EXTERNAL_LOGIN_ADMIN'];
    $prevExtLoginReseller = isset($_POST['prevent_external_login_reseller'])
        ? clean_input($_POST['prevent_external_login_reseller']) : $cfg['PREVENT_EXTERNAL_LOGIN_RESELLER'];
    $prevExtLoginClient = isset($_POST['prevent_external_login_client'])
        ? clean_input($_POST['prevent_external_login_client']) : $cfg['PREVENT_EXTERNAL_LOGIN_CLIENT'];
    $enableSSL = isset($_POST['enableSSL']) ? clean_input($_POST['enableSSL']) : $cfg['ENABLE_SSL'];

    if (
        !is_number($checkForUpdate) || !is_number($lostPasswd) || !is_number($passwdStrong) || !is_number($bruteforce)
        || !is_number($bruteforceBetween) || !is_number($createDefaultEmails) || !is_number($countDefaultEmails)
        || !is_number($protecttDefaultEmails) || !is_number($hardMailSuspension) || !is_number($emailQuotaSyncMode)
        || !is_number($supportSystem) || !is_number($prevExtLoginAdmin) || !is_number($prevExtLoginReseller)
        || !is_number($prevExtLoginClient) || !is_number($enableSSL)
        || !in_array($userInitialLang, i18n_getAvailableLanguages(true), true)
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
        $dbCfg['PROTECT_DEFAULT_EMAIL_ADDRESSES'] = $protecttDefaultEmails;
        $dbCfg['HARD_MAIL_SUSPENSION'] = $hardMailSuspension;
        $dbCfg['EMAIL_QUOTA_SYNC_MODE'] = $emailQuotaSyncMode;
        $dbCfg['USER_INITIAL_LANG'] = $userInitialLang;
        $dbCfg['IMSCP_SUPPORT_SYSTEM'] = $supportSystem;
        $dbCfg['DOMAIN_ROWS_PER_PAGE'] = $domainRowsPerPage;
        $dbCfg['LOG_LEVEL'] = defined($logLevel) ? constant($logLevel) : 0;
        $dbCfg['PREVENT_EXTERNAL_LOGIN_ADMIN'] = $prevExtLoginAdmin;
        $dbCfg['PREVENT_EXTERNAL_LOGIN_RESELLER'] = $prevExtLoginReseller;
        $dbCfg['PREVENT_EXTERNAL_LOGIN_CLIENT'] = $prevExtLoginClient;
        $dbCfg['ENABLE_SSL'] = $enableSSL;

        $cfg->merge($dbCfg);
        iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditAdminGeneralSettings);

        $updtCount = $dbCfg->countQueries('update');
        $newCount = $dbCfg->countQueries('insert');

        if ($updtCount > 0) {
            set_page_message(
                ntr('The configuration parameter has been updated.',
                    '%d configuration parameters were updated',
                    $updtCount,
                    $updtCount
                ),
                'success'
            );
        }

        if ($newCount > 0) {
            set_page_message(
                ntr('A new configuration parameter has been created.',
                    '%d configuration parameters were created',
                    $newCount,
                    $newCount
                ),
                'success'
            );
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
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'admin/settings.tpl',
    'page_message' => 'layout',
    'def_language' => 'page'
]);

if ($cfg['CHECK_FOR_UPDATES']) {
    $tpl->assign([
        'CHECK_FOR_UPDATES_SELECTED_ON'  => ' selected',
        'CHECK_FOR_UPDATES_SELECTED_OFF' => ''
    ]);
} else {
    $tpl->assign([
        'CHECK_FOR_UPDATES_SELECTED_ON'  => '',
        'CHECK_FOR_UPDATES_SELECTED_OFF' => ' selected'
    ]);
}

if ($cfg['LOSTPASSWORD']) {
    $tpl->assign([
        'LOSTPASSWORD_SELECTED_ON'  => ' selected',
        'LOSTPASSWORD_SELECTED_OFF' => ''
    ]);
} else {
    $tpl->assign([
        'LOSTPASSWORD_SELECTED_ON' => '',
        'LOSTPASSWORD_SELECTED_OFF', ' selected'
    ]);
}

if ($cfg['PASSWD_STRONG']) {
    $tpl->assign([
        'PASSWD_STRONG_ON'  => ' selected',
        'PASSWD_STRONG_OFF' => ''
    ]);
} else {
    $tpl->assign([
        'PASSWD_STRONG_ON'  => '',
        'PASSWD_STRONG_OFF' => ' selected'
    ]);
}

if ($cfg['BRUTEFORCE']) {
    $tpl->assign([
        'BRUTEFORCE_SELECTED_ON'  => 'selected',
        'BRUTEFORCE_SELECTED_OFF' => ''
    ]);
} else {
    $tpl->assign([
        'BRUTEFORCE_SELECTED_ON'  => '',
        'BRUTEFORCE_SELECTED_OFF' => ' selected'
    ]);
}

if ($cfg['BRUTEFORCE_BETWEEN']) {
    $tpl->assign([
        'BRUTEFORCE_BETWEEN_SELECTED_ON'  => ' selected',
        'BRUTEFORCE_BETWEEN_SELECTED_OFF' => ''
    ]);
} else {
    $tpl->assign([
        'BRUTEFORCE_BETWEEN_SELECTED_ON'  => '',
        'BRUTEFORCE_BETWEEN_SELECTED_OFF' => ' selected'
    ]);
}

if ($cfg['IMSCP_SUPPORT_SYSTEM']) {
    $tpl->assign([
        'SUPPORT_SYSTEM_SELECTED_ON'  => ' selected',
        'SUPPORT_SYSTEM_SELECTED_OFF' => ''
    ]);
} else {
    $tpl->assign([
        'SUPPORT_SYSTEM_SELECTED_ON'  => '',
        'SUPPORT_SYSTEM_SELECTED_OFF' => ' selected'
    ]);
}

if ($cfg['CREATE_DEFAULT_EMAIL_ADDRESSES']) {
    $tpl->assign([
        'CREATE_DEFAULT_EMAIL_ADDRESSES_ON'  => ' selected',
        'CREATE_DEFAULT_EMAIL_ADDRESSES_OFF' => ''
    ]);
} else {
    $tpl->assign([
        'CREATE_DEFAULT_EMAIL_ADDRESSES_ON'  => '',
        'CREATE_DEFAULT_EMAIL_ADDRESSES_OFF' => ' selected'
    ]);
}

if ($cfg['PROTECT_DEFAULT_EMAIL_ADDRESSES']) {
    $tpl->assign([
        'PROTECT_DEFAULT_EMAIL_ADDRESSES_ON'  => ' selected',
        'PROTECT_DEFAULT_EMAIL_ADDRESSES_OFF' => ''
    ]);
} else {
    $tpl->assign([
        'PROTECT_DEFAULT_EMAIL_ADDRESSESL_ON' => '',
        'PROTECT_DEFAULT_EMAIL_ADDRESSES_OFF' => ' selected'
    ]);
}

if ($cfg['COUNT_DEFAULT_EMAIL_ADDRESSES']) {
    $tpl->assign([
        'COUNT_DEFAULT_EMAIL_ADDRESSES_ON'  => ' selected',
        'COUNT_DEFAULT_EMAIL_ADDRESSES_OFF' => ''
    ]);
} else {
    $tpl->assign([
        'COUNT_DEFAULT_EMAIL_ADDRESSES_ON'  => '',
        'COUNT_DEFAULT_EMAIL_ADDRESSES_OFF' => ' selected'
    ]);
}

if ($cfg['HARD_MAIL_SUSPENSION']) {
    $tpl->assign([
        'HARD_MAIL_SUSPENSION_ON'  => ' selected',
        'HARD_MAIL_SUSPENSION_OFF' => ''
    ]);
} else {
    $tpl->assign([
        'HARD_MAIL_SUSPENSION_ON'  => '',
        'HARD_MAIL_SUSPENSION_OFF' => ' selected'
    ]);
}

if (isset($cfg['EMAIL_QUOTA_SYNC_MODE']) && $cfg['EMAIL_QUOTA_SYNC_MODE']) {
    $tpl->assign([
        'REDISTRIBUTE_EMAIl_QUOTA_YES' => ' selected',
        'REDISTRIBUTE_EMAIl_QUOTA_NO'  => ''
    ]);
} else {
    $tpl->assign([
        'REDISTRIBUTE_EMAIl_QUOTA_YES' => '',
        'REDISTRIBUTE_EMAIl_QUOTA_NO'  => ' selected'
    ]);
}

if ($cfg['PREVENT_EXTERNAL_LOGIN_ADMIN']) {
    $tpl->assign([
        'PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_ON'  => ' selected',
        'PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_OFF' => ''
    ]);
} else {
    $tpl->assign([
        'PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_ON'  => '',
        'PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_OFF' => ' selected'
    ]);
}

if ($cfg['PREVENT_EXTERNAL_LOGIN_RESELLER']) {
    $tpl->assign([
        'PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_ON'  => ' selected',
        'PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_OFF' => ''
    ]);
} else {
    $tpl->assign([
        'PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_ON'  => '',
        'PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_OFF' => ' selected'
    ]);
}

if ($cfg['PREVENT_EXTERNAL_LOGIN_CLIENT']) {
    $tpl->assign([
            'PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_ON'  => ' selected',
            'PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_OFF' => ''
        ]
    );
} else {
    $tpl->assign([
        'PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_ON'  => '',
        'PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_OFF' => ' selected'
    ]);
}

switch ($cfg['LOG_LEVEL']) {
    case 0:
        $tpl->assign([
            'LOG_LEVEL_SELECTED_OFF'     => ' selected',
            'LOG_LEVEL_SELECTED_NOTICE'  => '',
            'LOG_LEVEL_SELECTED_WARNING' => '',
            'LOG_LEVEL_SELECTED_ERROR'   => ''
        ]);
        break;
    case E_USER_NOTICE:
        $tpl->assign([
            'LOG_LEVEL_SELECTED_OFF'     => '',
            'LOG_LEVEL_SELECTED_NOTICE'  => ' selected',
            'LOG_LEVEL_SELECTED_WARNING' => '',
            'LOG_LEVEL_SELECTED_ERROR'   => ''
        ]);
        break;
    case E_USER_WARNING:
        $tpl->assign([
            'LOG_LEVEL_SELECTED_OFF'     => '',
            'LOG_LEVEL_SELECTED_NOTICE'  => '',
            'LOG_LEVEL_SELECTED_WARNING' => ' selected',
            'LOG_LEVEL_SELECTED_ERROR'   => ''
        ]);
        break;
    default:
        $tpl->assign([
            'LOG_LEVEL_SELECTED_OFF'     => '',
            'LOG_LEVEL_SELECTED_NOTICE'  => '',
            'LOG_LEVEL_SELECTED_WARNING' => '',
            'LOG_LEVEL_SELECTED_ERROR'   => ' selected'
        ]);
}

if ($cfg['ENABLE_SSL']) {
    $tpl->assign([
        'ENABLE_SSL_ON'  => ' selected',
        'ENABLE_SSL_OFF' => ''
    ]);
} else {
    $tpl->assign([
        'ENABLE_SSL_ON'  => '',
        'ENABLE_SSL_OFF' => ' selected'
    ]);
}

$tpl->assign([
    'TR_PAGE_TITLE'                          => tohtml(tr('Admin / Settings')),
    'TR_UPDATES'                             => tohtml(tr('Updates')),
    'LOSTPASSWORD_TIMEOUT_VALUE'             => tohtml($cfg['LOSTPASSWORD_TIMEOUT'], 'htmlAttr'),
    'PASSWD_CHARS'                           => tohtml($cfg['PASSWD_CHARS'], 'htmlAttr'),
    'BRUTEFORCE_MAX_LOGIN_VALUE'             => tohtml($cfg['BRUTEFORCE_MAX_LOGIN'], 'htmlAttr'),
    'BRUTEFORCE_BLOCK_TIME_VALUE'            => tohtml($cfg['BRUTEFORCE_BLOCK_TIME'], 'htmlAttr'),
    'BRUTEFORCE_BETWEEN_TIME_VALUE'          => tohtml($cfg['BRUTEFORCE_BETWEEN_TIME'], 'htmlAttr'),
    'BRUTEFORCE_MAX_CAPTCHA'                 => tohtml($cfg['BRUTEFORCE_MAX_CAPTCHA'], 'htmlAttr'),
    'BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT'    => tohtml($cfg['BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT'], 'htmlAttr'),
    'DOMAIN_ROWS_PER_PAGE'                   => tohtml($cfg['DOMAIN_ROWS_PER_PAGE'], 'htmlAttr'),
    'TR_SETTINGS'                            => tohtml(tr('Settings')),
    'TR_MESSAGE'                             => tohtml(tr('Message')),
    'TR_LOSTPASSWORD'                        => tohtml(tr('Lost password')),
    'TR_LOSTPASSWORD_TIMEOUT'                => tohtml(tr('Activation link expire time in minutes')),
    'TR_PASSWORD_SETTINGS'                   => tohtml(tr('Password settings')),
    'TR_PASSWD_STRONG'                       => tohtml(tr('Strong passwords')),
    'TR_PASSWD_CHARS'                        => tohtml(tr('Password minimum length')),
    'TR_BRUTEFORCE'                          => tohtml(tr('Bruteforce detection')),
    'TR_BRUTEFORCE_BETWEEN'                  => tohtml(tr('Blocking time between logins and captcha attempts')),
    'TR_BRUTEFORCE_MAX_LOGIN'                => tohtml(tr('Max number of login attempts')),
    'TR_BRUTEFORCE_BLOCK_TIME'               => tohtml(tr('Blocktime in minutes')),
    'TR_BRUTEFORCE_BETWEEN_TIME'             => tohtml(tr('Blocking time between login/captcha attempts in seconds')),
    'TR_BRUTEFORCE_MAX_CAPTCHA'              => tohtml(tr('Maximum number of captcha validation attempts')),
    'TR_BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT' => tohtml(tr('Maximum number of validation attempts before waiting restriction intervenes')),
    'TR_OTHER_SETTINGS'                      => tohtml(tr('Other settings')),
    'TR_MAIL_SETTINGS'                       => tohtml(tr('Email settings')),
    'TR_CREATE_DEFAULT_EMAIL_ADDRESSES'      => tohtml(tr('Create default mail accounts')),
    'TR_COUNT_DEFAULT_EMAIL_ADDRESSES'       => tohtml(tr('Count default mail accounts')),
    'PROTECT_DEFAULT_EMAIL_ADDRESSES'        => tohtml(tr('Protect default mail accounts against change and removal')),
    'TR_HARD_MAIL_SUSPENSION'                => tohtml(tr('Mail accounts are hard suspended')),
    'TR_EMAIL_QUOTA_SYNC_MODE'               => tohtml(tr('Redistribute unused quota across existing mail accounts')),
    'TR_USER_INITIAL_LANG'                   => tohtml(tr('Panel default language')),
    'TR_SUPPORT_SYSTEM'                      => tohtml(tr('Support system')),
    'TR_ENABLED'                             => tohtml(tr('Enabled')),
    'TR_DISABLED'                            => tohtml(tr('Disabled')),
    'TR_YES'                                 => tohtml(tr('Yes')),
    'TR_NO'                                  => tohtml(tr('No')),
    'TR_UPDATE'                              => tohtml(tr('Update')),
    'TR_SERVERPORTS'                         => tohtml(tr('Server ports')),
    'TR_ADMIN'                               => tohtml(tr('Admin')),
    'TR_RESELLER'                            => tohtml(tr('Reseller')),
    'TR_DOMAIN_ROWS_PER_PAGE'                => tohtml(tr('Domains per page')),
    'TR_LOG_LEVEL'                           => tohtml(tr('Mail Log Level')),
    'TR_E_USER_OFF'                          => tohtml(tr('Disabled')),
    'TR_E_USER_NOTICE'                       => tohtml(tr('Notices, Warnings and Errors')),
    'TR_E_USER_WARNING'                      => tohtml(tr('Warnings and Errors')),
    'TR_E_USER_ERROR'                        => tohtml(tr('Errors')),
    'TR_CHECK_FOR_UPDATES'                   => tohtml(tr('Check for update')),
    'TR_ENABLE_SSL'                          => tohtml(tr('Enable SSL')),
    'TR_SSL_HELP'                            => tohtml(tr('Defines whether or not customers can add/change SSL certificates for their domains.')),
    'TR_PREVENT_EXTERNAL_LOGIN_ADMIN'        => tohtml(tr('Prevent external login for admins')),
    'TR_PREVENT_EXTERNAL_LOGIN_RESELLER'     => tohtml(tr('Prevent external login for resellers')),
    'TR_PREVENT_EXTERNAL_LOGIN_CLIENT'       => tohtml(tr('Prevent external login for clients'))
]);

generateNavigation($tpl);
generateLanguagesList($tpl, $cfg['USER_INITIAL_LANG']);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
