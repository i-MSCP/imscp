<?php
/**
 *  ispCP ω (OMEGA) a Virtual Hosting Control Panel
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2008 by ispCP | http://isp-control.net
 *  @version 	SVN: $ID$
 *  @link 		http://isp-control.net
 *  @author		ispCP Team
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 *
 **/

$cfg_obj = new Config('/etc/ispcp/ispcp.conf');

/* Status not ok -> Try to get the error and display a message */
if ($cfg_obj->status != 'ok') {

    if ($cfg_obj->status == 'err') {
		/* cannot open ispcp.conf file - we must show warning */
        print "<center><b><font color=red>Cannot open the ispcp.conf config file !<br><br>Please contact your system administrator</font></b></center>";
		die();
    }

    if (substr($cfg_obj->status, 0, 24) == 'missing config variable:') {
        /* cannot open ispcp.conf file - we must show warning */
		print "<center><b><font color=red>config variable ".substr($cfg_obj->status, 25)." is missing!<br><br>Please contact your system administrator</font></b></center>";
		die();
    }
}

$cfg = $cfg_obj->getValues();

class Config {
    /* this class will parse config file and get all variables avaible in PHP */
    var $config_file;       /* config filename */

	/* IMPORTANT: any adding & removing of variables in /etc/ispcp/ispcp.conf should also be made here! */
    var $cfg_values = array( /* array with all options from config file - predefined with null */
		'BuildDate' => null,
		'Version' => null,
		'CodeName' => null,
		'DEFAULT_ADMIN_ADDRESS' => null,
		'SERVER_HOSTNAME' => null,
		'BASE_SERVER_IP' => null,
		'BASE_SERVER_VHOST' => null,
		'MR_LOCK_FILE' => null,
		'CMD_AWK' => null,
		'CMD_BZCAT' => null,
		'CMD_BZIP' => null,
		'CMD_CHOWN' => null,
		'CMD_CAT' => null,
		'CMD_CHMOD' => null,
		'CMD_CP' => null,
		'CMD_DIFF' => null,
		'CMD_DU' => null,
		'CMD_ECHO' => null,
		'CMD_GZCAT' => null,
		'CMD_GZIP' => null,
		'CMD_GREP' => null,
		'CMD_GROUPADD' => null,
		'CMD_GROUPDEL' => null,
		'CMD_HOSTNAME' => null,
		'CMD_IFCONFIG' => null,
		'CMD_IPTABLES' => null,
		'CMD_LN' => null,
		'CMD_MYSQL' => null,
		'CMD_MV' => null,
		'CMD_PS' => null,
		'CMD_RM' => null,
		'CMD_SHELL' => null,
		'CMD_TAR' => null,
		'CMD_TOUCH' => null,
		'CMD_USERADD' => null,
		'CMD_USERDEL' => null,
		'CMD_WC' => null,
		'PEAR_DIR' => null,
		'DATABASE_TYPE' => null,
		'DATABASE_HOST' => null,
		'DATABASE_NAME' => null,
		'DATABASE_PASSWORD' => null,
		'DATABASE_USER' => null,
		'DATABASE_DIR' => null,
		'CMD_MYSQLDUMP' => null,
		'CONF_DIR' => null,
		'LOG_DIR' => null,
		'PHP_STARTER_DIR' => null,
		'ROOT_DIR' => null,
		'GUI_ROOT_DIR' => null,
		'APACHE_WWW_DIR' => null,
		'SCOREBOARDS_DIR' => null,
		'ZIP' => null,
		'PHP4_FASTCGI_BIN' => null,
		'PHP5_FASTCGI_BIN' => null,
		'PHP_VERSION' => null,
		'FTPD_CONF_FILE' => null,
		'BIND_CONF_FILE' => null,
		'BIND_DB_DIR' => null,
		'SECONDARY_DNS' => null,
		'AWSTATS_ACTIVE' => null,
		'AWSTATS_MODE' => null,
		'AWSTATS_CACHE_DIR' => null,
		'AWSTATS_CONFIG_DIR' => null,
		'AWSTATS_ENGINE_DIR' => null,
		'AWSTATS_WEB_DIR' => null,
		'AWSTATS_ROOT_DIR' => null,
		'APACHE_NAME' => null,
		'APACHE_RESTART_TRY' => null,
		'APACHE_CONF_DIR' => null,
		'APACHE_CMD' => null,
		'APACHE_LOG_DIR' => null,
		'APACHE_BACKUP_LOG_DIR' => null,
		'APACHE_USERS_LOG_DIR' => null,
		'APACHE_MODS_DIR' => null,
		'APACHE_SITES_DIR' => null,
		'APACHE_CUSTOM_SITES_CONFIG_DIR' => null,
		'APACHE_SUEXEC_USER_PREF' => null,
		'APACHE_SUEXEC_MIN_GID' => null,
		'APACHE_SUEXEC_MIN_UID' => null,
		'APACHE_USER' => null,
		'APACHE_GROUP' => null,
		'POSTFIX_CONF_FILE' => null,
		'POSTFIX_MASTER_CONF_FILE' => null,
		'MTA_LOCAL_MAIL_DIR' => null,
		'MTA_VIRTUAL_MAIL_DIR' => null,
		'MTA_LOCAL_ALIAS_HASH' => null,
		'MTA_VIRTUAL_CONF_DIR' => null,
		'MTA_VIRTUAL_ALIAS_HASH' => null,
		'MTA_VIRTUAL_DMN_HASH' => null,
		'MTA_VIRTUAL_MAILBOX_HASH' => null,
		'MTA_TRANSPORT_HASH' => null,
		'MTA_SENDER_ACCESS_HASH' => null,
		'MTA_MAILBOX_MIN_UID' => null,
		'MTA_MAILBOX_UID' => null,
		'MTA_MAILBOX_UID_NAME' => null,
		'MTA_MAILBOX_GID' => null,
		'MTA_MAILBOX_GID_NAME' => null,
		'MTA_SASLDB_FILE' => null,
		'ETC_SASLDB_FILE' => null,
		'CMD_SASLDB_LISTUSERS2' => null,
		'CMD_SASLDB_PASSWD2' => null,
		'CMD_POSTMAP' => null,
		'CMD_NEWALIASES' => null,
		'COURIER_CONF_DIR' => null,
		'AUTHLIB_CONF_DIR' => null,
		'CMD_MAKEUSERDB' => null,
		'BACKUP_HOUR' => null,
		'BACKUP_MINUTE' => null,
		'BACKUP_ISPCP' => null,
		'BACKUP_DOMAINS' => null,
		'BACKUP_ROOT_DIR' => null,
		'CMD_CRONTAB' => null,
		'CMD_AMAVIS' => null,
		'CMD_AUTHD' => null,
		'CMD_FTPD' => null,
		'CMD_HTTPD' => null,
		'CMD_IMAP' => null,
		'CMD_IMAP_SSL' => null,
		'CMD_MTA' => null,
		'CMD_NAMED' => null,
		'CMD_POP' => null,
		'CMD_POP_SSL' => null,
		'CMD_ISPCPD' => null,
		'CMD_ISPCPN' => null,
		'CMD_PFLOGSUM' => null,
		'TRAFF_LOG_DIR' => null,
		'FTP_TRAFF_LOG' => null,
		'MAIL_TRAFF_LOG' => null,
		'PREV_TRAFF_LOG_MAX_SIZE' => null,
		'TRAFF_ROOT_DIR' => null,
		'TOOLS_ROOT_DIR' => null,
		'QUOTA_ROOT_DIR' => null,
		'MAIL_LOG_INC_AMAVIS' => null,
		'USER_INITIAL_THEME' => null,
		'FTP_USERNAME_SEPARATOR' => null,
		'FTP_HOMEDIR' => null,
		'IPS_LOGO_PATH' => null,
		'ISPCP_SUPPORT_SYSTEM_PATH' => null,
		'ISPCP_SUPPORT_SYSTEM_TARGET' => null,
		'MYSQL_PREFIX' => null,
		'MYSQL_PREFIX_TYPE' => null,
		'WEBMAIL_PATH' => null,
		'WEBMAIL_TARGET' => null,
		'PMA_PATH' => null,
		'PMA_TARGET' => null,
		'FILEMANAGER_PATH' => null,
		'FILEMANAGER_TARGET' => null,
		'DATE_FORMAT' => null,
		'RKHUNTER_LOG' => null,
		'CHKROOTKIT_LOG' => null,
		'OTHER_ROOTKIT_LOG' => null,
		'HTACCESS_USERS_FILE_NAME' => null,
		'HTACCESS_GROUPS_FILE_NAME' => null,
		'HTPASSWD_CMD' => null,
		'BACKUP_FILE_DIR' => null,
		'DEBUG' => null,
	);
    var $status;

    function Config($cfg = "/etc/ispcp/ispcp.conf") {
        $this -> config_file = $cfg;
        $this -> status = "ok";
        if ($this->parseFile() == FALSE) {
#            $this->status = 'err';
            return FALSE;
        }
        else {
            return TRUE;
        }
    }

    function parseFile() {
        /* open file ... parse it and put it in $cfg_values */
        @$fd = fopen($this->config_file,'r');
        if ($fd == FALSE) {
            /* ooops error */
            $this->status = 'err';
            return FALSE;
        }

        while(!feof($fd)){
            $buffer = fgets($fd,4096);
            /* remove spaces  */
            $buffer = ltrim($buffer);
            if (strlen($buffer) < 3) {
                /* empty */
            }
            else if ($buffer[0] == '#' || $buffer[0] == ';') {
                /* this is comment */
            }
            else if (strpos($buffer,'=') === false) {
                /* have no = :( */
            }
            else {
                $pair = explode('=',$buffer,2);

                $pair[0] = ltrim($pair[0]);
                $pair[0] = rtrim($pair[0]);

                $pair[1] = ltrim($pair[1]);
                $pair[1] = rtrim($pair[1]);

                /* ok we have it :) */
                $this->cfg_values[$pair[0]]=$pair[1];
            }
        }
		fclose($fd);

		foreach ($this->cfg_values as $k=>$v) {
		    if ($v === null) {
	        	$this->status = "missing config variable: '$k'";
				return FALSE;
		    }
		}

	    return TRUE;
	}

	function getValues() {
        return $this->cfg_values;
    }
}

function decrypt_db_password ($db_pass) {
    global $ispcp_db_pass_key, $ispcp_db_pass_iv;

    if ($db_pass == '')
		return '';

    if (extension_loaded('mcrypt') || @dl('mcrypt.'.PHP_SHLIB_SUFFIX)) {

        $text = @base64_decode($db_pass."\n");
        /* Open the cipher */
        $td = @mcrypt_module_open ('blowfish', '', 'cbc', '');
        /* Create key */
        $key = $ispcp_db_pass_key;
        /* Create the IV and determine the keysize length */
        $iv = $ispcp_db_pass_iv;

        /* Intialize encryption */
        @mcrypt_generic_init ($td, $key, $iv);
        /* Decrypt encrypted string */
        $decrypted = @mdecrypt_generic ($td, $text);
        @mcrypt_module_close ($td);

        /* Show string */
        return trim($decrypted);

    } else {
        system_message("ERROR: The php-extension 'mcrypt' not loaded !");
        die();
    }

}

?>