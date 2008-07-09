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
