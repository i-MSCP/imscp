CREATE TABLE IF NOT EXISTS `httpd_vlogger` (
  `vhost` VARCHAR(255) NOT NULL,
  `ldate` INT(8) UNSIGNED NOT NULL,
  `bytes` INT(32) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY(`vhost`,`ldate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
