-- phpMyAdmin SQL Dump
-- version 2.11.1
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Erstellungszeit: 15. Oktober 2007 um 00:34
-- Server Version: 5.0.32
-- PHP-Version: 5.2.0-8+etch5~pu1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Databse: `ispcp`
--

-- --------------------------------------------------------

--
-- table structure for table `lang_English`
--

DROP TABLE IF EXISTS `lang_English`;
CREATE TABLE `lang_English` (
  `msgid` text collate utf8_unicode_ci,
  `msgstr` text collate utf8_unicode_ci,
  KEY msgid (msgid(25))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- data for table `lang_English`
--

INSERT INTO `lang_English` (`msgid`, `msgstr`) VALUES
('ispcp_languageSetlocaleValue', 'en_GB'),
('ispcp_table', 'English'),
('ispcp_language', 'English'),
('Incorrect domain name syntax', 'Incorrect <i>domain name</i> syntax'),
('Incorrect forward syntax', 'Incorrect <i>forward</i> syntax!'),
('Incorrect max FTP count or syntax!', 'Incorrect <i>max FTP</i> count or syntax!'),
('Incorrect max SQL databases count or syntax!', 'Incorrect <i>max mySQL databases</i> count or syntax!'),
('Incorrect max SQL users count or syntax!', 'Incorrect <i>max mySQL users</i> count or syntax!'),
('Incorrect max alias count or syntax!', 'Incorrect <i>max alias</i> count or syntax!'),
('Incorrect max disk amount or syntax!', 'Incorrect <i>max disk</i> amount or syntax!'),
('Incorrect max domain count or syntax!', 'Incorrect <i>max domain</i> count or syntax!'),
('Incorrect max mail count or syntax!', 'Incorrect <i>max e-mail</i> count or syntax!'),
('Incorrect max subdomain count or syntax!', 'Incorrect <i>max subdomain</i> count or syntax!'),
('Incorrect max traffic amount or syntax!', 'Incorrect <i>max traffic</i> amount or syntax!'),
('Incorrect mount point syntax', 'Incorrect <i>mount point</i> syntax!'),
('Mail', 'e-mail'),
('encoding', 'UTF-8');
