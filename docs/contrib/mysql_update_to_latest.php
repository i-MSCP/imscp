<?php

/*
*
* Update Script um alle benötigten Datenbank anpassungen für VHCS.2.4.7.2 vorzunehmen.
* Inspired by update_to_latest from the phpbb project
*
* Anleitung:
*
* 1) mysql_update_to_latest.php hochladen und in /var/www/vhcs2/gui ablegen (Wenn Du die GUI Adresse veraendert hast, dann dorthin!)
* 2) Die PHP Datei einmal ausfuehren indem Du sie mit dem Browser aufrufst (http://www.Deine-VHCSAdresse.de/mysql_update_to_latest.php)
* 3) Die PHP Datei wieder löschen!    
*
*
*
*/

include("include/vhcs-lib.php");

function _sql(&$update_sql, &$errored, &$error_ary, $echo_dot = true)
{
        global $sql;

        if (!($result = exec_query($sql,$update_sql, array())))
        {
                $errored = true;
                $error_ary['sql'][] = (is_array($update_sql)) ? $update_sql[$i] : $update_sql;
                $error_ary['error_code'][] = $sql->ErrorMsg();
        }

        if ($echo_dot)
        {
                echo ". \n";
                flush();
        }

        return $result;
}

@set_time_limit(120);


/* Begin SQL - COMMON Data if there's something likely to be add in 2.4.7.2 without logic please add it here */

$update_sql[] = "ALTER TABLE `vhcs2`.`login` ADD `ipaddr` varchar(15) NULL AFTER `session_id`;";

$update_sql[] = "ALTER TABLE `vhcs2`.`login` ADD `user_name` varchar(255) NULL AFTER `ipaddr`;";

$update_sql[] = "ALTER TABLE `vhcs2`.`login` ADD `login_count` tinyint(1) NULL AFTER `lastaccess`;";

$update_sql[] = "CREATE TABLE `vhcs2`.`config` (`name` varchar(255) NOT NULL default '',`value` varchar(255) NOT NULL default '',PRIMARY KEY  (`name`));";

$update_sql[] = "ALTER TABLE `vhcs2`.`admin` ADD `uniqkey_time` TIMESTAMP NULL AFTER `uniqkey`;";

$update_sql[] = "ALTER TABLE `vhcs2`.`admin` ADD UNIQUE ( `admin_name` );";

$update_sql[] = "ALTER TABLE `vhcs2`.`domain` ADD INDEX i_domain_domain_admin_id ( `domain_admin_id` );";

$update_sql[] = "ALTER TABLE `vhcs2`.`domain` ADD UNIQUE ( `domain_name` );";

$update_sql[] = "ALTER TABLE `vhcs2`.`domain_traffic` ADD INDEX i_domain_traffic_domain_id ( `domain_id` );";

$update_sql[] = "ALTER TABLE `vhcs2`.`htaccess_users` ADD `status` varchar(255) default NULL;";

$update_sql[] = "ALTER TABLE `vhcs2`.`htaccess_groups` ADD `status` varchar(255) default NULL;";

$update_sql[] = "ALTER TABLE `vhcs2`.`htaccess` CHANGE `status` `status` VARCHAR( 255 ) default NULL;";

$update_sql[] = "UPDATE `vhcs2`.`subdomain` SET `subdomain_status` = 'change' WHERE `subdomain_status` = 'ok';";

$update_sql[] = "UPDATE `vhcs2`.`domain` SET `domain_status` = 'change' WHERE `domain_status` = 'ok';";

$update_sql[] = "UPDATE `vhcs2`.`domain_aliasses` SET `alias_status` = 'change' WHERE `alias_status` = 'ok';";

$update_sql[] = "UPDATE `vhcs2`.`user_gui_props` SET `lang` = 'lang_German' WHERE `lang` = 'lang_Deutsch';";

$update_sql[] = "UPDATE `vhcs2`.`user_gui_props` SET `lang` = 'lang_PortuguesBrazil' WHERE `lang` = 'lang_Portugues_Brasil';";

$update_sql[] = "DELETE FROM `vhcs2`.`login`;";

$update_sql[] = "INSERT INTO `config` ( `name`, `value` ) VALUES ('PORT_FTP', '21;tcp;FTP;1;0'), ('PORT_SSH', '22;tcp;SSH;1;0'),('PORT_TELNET', '23;tcp;TELNET;1;0'),('PORT_SMTP', '25;tcp;SMPT;1;0'),('PORT_DNS', '53;tcp;DNS;1;0'),('PORT_HTTP', '80;tcp;HTTP;1;0'),('PORT_HTTPS', '443;tcp;HTTPS;1;0'),('PORT_POP3', '110;tcp;POP3;1;0'),('PORT_POP3-SSL', '995;tcp;POP3-SSL;1;0'),('PORT_IMAP', '143;tcp;IMAP;1;0'),('PORT_IMAP-SSL', '993;tcp;IMAP-SSL;1;0');";


/* Specials please add any data which needs conditionals after here */ 


//prüfen, ob Lang_Deutsch + lang_Portugues_Brasil vorhanden sind...

$test1_sql = "select * from `vhcs`.`lang_Deutsch` Limit 1";

if (mysql_query($test1_sql)) {
	//tabelle existiert - wir können daraus lesen
	$update_sql[] = "CREATE TABLE `vhcs2`.`lang_German` (
				`id` int( 10 ) unsigned NOT NULL AUTO_INCREMENT ,
				`msgid` text,
				`msgstr` text,
				`msg_row` int( 10 ) unsigned default NULL ,
				`msg_column` int( 10 ) unsigned default NULL ,
				`msg_file_dest` text,
				`msg_name` varchar( 250 ) default NULL ,
				UNIQUE KEY `id` ( `id` )
				) ENGINE = MYISAM;";
	
	$update_sql[] = "INSERT INTO `vhcs2`.`lang_German` SELECT * FROM `vhcs2`.`lang_Deutsch` ;";
	
	$update_sql[] = "DROP TABLE IF EXISTS `vhcs2`.`lang_Deutsch`;";
	
}

$test2_sql = "select * from `vhcs`.`lang_Portugues_Brasil` Limit 1";

if (mysql_query($test2_sql)) {
	//falsche Tabelle existiert - auch hier ein update
	$update_sql[] = "CREATE TABLE `vhcs2`.`lang_PortuguesBrazil` (
				`id` int( 10 ) unsigned NOT NULL AUTO_INCREMENT ,
				`msgid` text,
				`msgstr` text,
				`msg_row` int( 10 ) unsigned default NULL ,
				`msg_column` int( 10 ) unsigned default NULL ,
				`msg_file_dest` text,
				`msg_name` varchar( 250 ) default NULL ,
				UNIQUE KEY `id` ( `id` )
				) ENGINE = MYISAM;";
	
	$update_sql[] = "INSERT INTO `vhcs2`.`lang_PortuguesBrazil` SELECT * FROM `vhcs2`.`lang_Portugues_Brasil` ;";
	
	$update_sql[] = "DROP TABLE IF EXISTS `vhcs2`.`lang_Portugues_Brasil`;	";

}

echo "<h2>Updating database schema</h2>\n";
echo "<p>Progress :: <b>";
flush();

$error_ary = array();
$errored = false;
if (count($update_sql))
{
        for ($i = 0; $i < count($update_sql); $i++)
        {
                _sql($update_sql[$i], $errored, $error_ary);
        }

        echo "</b> <b class=\"ok\">Done</b><br />Result &nbsp; :: \n";

        if ($errored)
        {
                echo " <b>Some queries failed, the statements and errors are listing below</b>\n<ul>";

                for ($i = 0; $i < count($error_ary['sql']); $i++)
                {
                        echo "<li>Error :: <b>" . $error_ary['error_code'][$i]['message'] . "</b><br />";
                        echo "SQL &nbsp; :: <b>" . $error_ary['sql'][$i] . "</b><br /><br /></li>";
                }

                echo "</ul>\n<p>This is probably nothing to worry about, update will continue. Should this fail to complete you may need to seek help at our development board. See <a href=\"docs\README.html\">README</a> for details on how to obtain advice.</p>\n";
        }
        else
        {
                echo "<b>No errors</b>\n";
        }
}
else
{
        echo " No updates required</b></p>\n";
}





?> 
