-- MySQL dump 9.11
--
-- Host: localhost    Database: vhcs22
-- ------------------------------------------------------
-- Server version	4.0.24_Debian-2-log

--
-- Table structure for table `htaccess_users`
--

DROP TABLE IF EXISTS `htaccess_users`;
CREATE TABLE `htaccess_users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL default '0',
  `uname` varchar(255) default NULL,
  `upass` varchar(255) default NULL,
  UNIQUE KEY `id` (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `htaccess_users`
--

--
-- Table structure for table `htaccess_groups`
--

DROP TABLE IF EXISTS `htaccess_groups`;
CREATE TABLE `htaccess_groups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL default '0',
  `ugroup` varchar(255) default NULL,
  `members` text,
  UNIQUE KEY `id` (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `htaccess_groups`
--

--
-- Table structure for table `htaccess`
--

DROP TABLE IF EXISTS `htaccess`;
CREATE TABLE `htaccess` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL default '0',
  `user_id` text,
  `group_id` text,
  `auth_type` varchar(255) default NULL,
  `auth_name` varchar(255) default NULL,
  `path` text,
  `status` text,
  UNIQUE KEY `id` (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `htaccess`
--

