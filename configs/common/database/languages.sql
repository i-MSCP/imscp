--
-- ISPCP ω (OMEGA) a Virtual Hosting Control Panel
-- Copyright (c) 2001-2006 by moleSoftware GmbH
-- Copyright (c) 2006-2010 by ispCP | http://isp-control.net
--
-- Version: $Id: database.sql 2201 2009-11-19 02:11:48Z nuxwin $
--
-- The contents of this file are subject to the Mozilla Public License
-- Version 1.1 (the "License"); you may not use this file except in
-- compliance with the License. You may obtain a copy of the License at
-- http://www.mozilla.org/MPL/
--
-- Software distributed under the License is distributed on an "AS IS"
-- basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
-- License for the specific language governing rights and limitations
-- under the License.
--
-- The Original Code is "VHCS - Virtual Hosting Control System".
--
-- The Initial Developer of the Original Code is moleSoftware GmbH.
-- Portions created by Initial Developer are Copyright (C) 2001-2006
-- by moleSoftware GmbH. All Rights Reserved.
-- Portions created by the ispCP Team are Copyright (C) 2006-2010 by
-- isp Control Panel. All Rights Reserved.
--
-- The ispCP ω Home Page is:
--
--    http://isp-control.net
--
-- --------------------------------------------------------

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Databse: `ispcp`
--

-- --------------------------------------------------------

--
-- table structure for table `lang_EnglishBritain`
--

DROP TABLE IF EXISTS `lang_EnglishBritain`;
CREATE TABLE `lang_EnglishBritain` (
  `msgid` text collate utf8_unicode_ci,
  `msgstr` text collate utf8_unicode_ci,
  KEY msgid (msgid(25))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- data for table `lang_EnglishBritain`
--

INSERT INTO `lang_EnglishBritain` (`msgid`, `msgstr`) VALUES
('ispcp_languageSetlocaleValue', 'en_GB'),
('ispcp_table', 'EnglishBritain'),
('ispcp_language', 'English (GB)'),
('encoding', 'UTF-8'),
('Incorrect domain name syntax', 'Incorrect <i>domain name</i> syntax'),
('Incorrect forward syntax', 'Incorrect <i>forward</i> syntax!'),
('Incorrect mount point syntax', 'Incorrect <i>mount point</i> syntax!'),
('Mail', 'e-mail');
