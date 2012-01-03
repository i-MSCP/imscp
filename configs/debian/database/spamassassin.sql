--
-- i-MSCP a internet Multi Server Control Panel
--
-- Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
-- Copyright (C) 2010-2012 by internet Multi Server Control Panel - http://i-mscp.net
--
-- Version: $Id$
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
-- The Original Code is "ispCP ω (OMEGA) a Virtual Hosting Control Panel".
--
-- The Initial Developer of the Original Code is ispCP Team.
-- Portions created by Initial Developer are Copyright (C) 2006-2010 by
-- isp Control Panel. All Rights Reserved.
--
-- Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
-- internet Multi Server Control Panel. All Rights Reserved.
--
-- The i-MSCP Home Page is:
--
--    http://i-mscp.net
--
-- --------------------------------------------------------

create database `{SPAMASSASSIN_DATABASE}` CHARACTER SET utf8 COLLATE utf8_unicode_ci;

use `{SPAMASSASSIN_DATABASE}`;

CREATE TABLE bayes_expire (
  id int(11) NOT NULL default '0',
  runtime int(11) NOT NULL default '0',
  KEY bayes_expire_idx1 (id)
) TYPE=InnoDB;

CREATE TABLE bayes_global_vars (
  variable varchar(30) NOT NULL default '',
  value varchar(200) NOT NULL default '',
  PRIMARY KEY  (variable)
) TYPE=InnoDB;

INSERT INTO bayes_global_vars VALUES ('VERSION','3');

CREATE TABLE bayes_seen (
  id int(11) NOT NULL default '0',
  msgid varchar(200) binary NOT NULL default '',
  flag char(1) NOT NULL default '',
  PRIMARY KEY  (id,msgid)
) TYPE=InnoDB;

CREATE TABLE bayes_token (
  id int(11) NOT NULL default '0',
  token char(5) NOT NULL default '',
  spam_count int(11) NOT NULL default '0',
  ham_count int(11) NOT NULL default '0',
  atime int(11) NOT NULL default '0',
  PRIMARY KEY  (id, token),
  INDEX bayes_token_idx1 (id, atime)
) TYPE=InnoDB;

CREATE TABLE bayes_vars (
  id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(200) NOT NULL default '',
  spam_count int(11) NOT NULL default '0',
  ham_count int(11) NOT NULL default '0',
  token_count int(11) NOT NULL default '0',
  last_expire int(11) NOT NULL default '0',
  last_atime_delta int(11) NOT NULL default '0',
  last_expire_reduce int(11) NOT NULL default '0',
  oldest_token_age int(11) NOT NULL default '2147483647',
  newest_token_age int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE bayes_vars_idx1 (username)
) TYPE=InnoDB;

CREATE TABLE awl (
  username varchar(100) NOT NULL default '',
  email varchar(255) NOT NULL default '',
  ip varchar(40) NOT NULL default '',
  count int(11) NOT NULL default '0',
  totscore float NOT NULL default '0',
  lastupdate timestamp(14) NOT NULL,
  signedby varchar(255) NOT NULL default '',
  PRIMARY KEY (username,email,signedby,ip)
) TYPE=InnoDB;
