create database `{DATABASE_NAME}` CHARACTER SET utf8 COLLATE utf8_unicode_ci;

use `{DATABASE_NAME}`;

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(32) collate utf8_unicode_ci default NULL,
  `password` varchar(42) collate utf8_unicode_ci default NULL,
  `role` varchar(10) collate utf8_unicode_ci default NULL,
  `created_on` int(10) unsigned NOT NULL default '0',
  `owner` int(10) unsigned default '0',
  `firstname` varchar(200) collate utf8_unicode_ci default NULL,
  `lastname` varchar(200) collate utf8_unicode_ci default NULL,
  `gender` varchar(1) collate utf8_unicode_ci default NULL,
  `firm` varchar(200) collate utf8_unicode_ci default NULL,
  `zip` varchar(10) collate utf8_unicode_ci default NULL,
  `city` varchar(200) collate utf8_unicode_ci default NULL,
  `state` varchar(200) collate utf8_unicode_ci default NULL,
  `country` varchar(200) collate utf8_unicode_ci default NULL,
  `email` varchar(200) collate utf8_unicode_ci default NULL,
  `phone` varchar(200) collate utf8_unicode_ci default NULL,
  `fax` varchar(200) collate utf8_unicode_ci default NULL,
  `street1` varchar(200) collate utf8_unicode_ci default NULL,
  `street2` varchar(200) collate utf8_unicode_ci default NULL,
  `uniqkey` varchar(255) collate utf8_unicode_ci default NULL,
  `uniqkey_time` timestamp NULL default NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
