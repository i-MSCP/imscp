# VHCS(tm) - Virtual Hosting Control System
# (c) 2001-2006 moleSoftware
# http://vhcs.net | http://www.molesoftware.com
# All rights reserved
#
# Datenbank: `vhcs_pro_dev`
# --------------------------------------------------------
#
# Tabellenstruktur für Tabelle `htaccess_users`
#

DROP TABLE IF EXISTS `htaccess_users`;
CREATE TABLE `htaccess_users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL default '0',
  `uname` varchar(255) default NULL,
  `upass` varchar(255) default NULL,
  `status` varchar(255) default NULL,
  UNIQUE KEY `id` (`id`)
) TYPE=MyISAM;

#
# Daten für Tabelle `htaccess_users`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `htaccess_groups`
#

DROP TABLE IF EXISTS `htaccess_groups`;
CREATE TABLE `htaccess_groups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL default '0',
  `ugroup` varchar(255) default NULL,
  `members` text,
  `status` varchar(255) default NULL,
  UNIQUE KEY `id` (`id`)
) TYPE=MyISAM;

#
# Daten für Tabelle `htaccess_groups`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `htaccess`
#

DROP TABLE IF EXISTS `htaccess`;
CREATE TABLE `htaccess` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL default '0',
  `user_id` text,
  `group_id` text,
  `auth_type` varchar(255) default NULL,
  `auth_name` varchar(255) default NULL,
  `path` text,
  `status` varchar(255) default NULL,
  UNIQUE KEY `id` (`id`)
) TYPE=MyISAM;

#
# Daten für Tabelle `htaccess`
#

