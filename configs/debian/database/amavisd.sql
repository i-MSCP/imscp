--
-- i-MSCP a internet Multi Server Control Panel
--
-- Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
-- Copyright (C) 2010-2012 by internet Multi Server Control Panel - http://i-mscp.net
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

create database `{AMAVIS_DATABASE}` CHARACTER SET utf8 COLLATE utf8_unicode_ci;

use `{AMAVIS_DATABASE}`;

CREATE TABLE users (
  id         int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,  -- unique id
  priority   integer      NOT NULL DEFAULT '7',  -- sort field, 0 is low prior.
  policy_id  integer unsigned NOT NULL DEFAULT '1',  -- JOINs with policy.id
  email      varbinary(255) NOT NULL UNIQUE,
  fullname   varchar(255) DEFAULT NULL,    -- not used by amavisd-new
  local      char(1)      -- Y/N  (optional field, see note further down)
);

CREATE TABLE mailaddr (
  id         int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  priority   integer      NOT NULL DEFAULT '7',  -- 0 is low priority
  email      varbinary(255) NOT NULL UNIQUE,
  owner	     varchar(64) DEFAULT NULL
);

CREATE TABLE wblist (
  rid        integer unsigned NOT NULL,  -- recipient: users.id
  sid        integer unsigned NOT NULL,  -- sender: mailaddr.id
  wb         varchar(10)  NOT NULL,  -- W or Y / B or N / space=neutral / score
  PRIMARY KEY (rid,sid)
);

CREATE TABLE policy (
  id  int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                    -- 'id' this is the _only_ required field
  policy_name      varchar(255),     -- not used by amavisd-new, a comment

  virus_lover          char(1) default NULL,     -- Y/N
  spam_lover           char(1) default NULL,     -- Y/N
  banned_files_lover   char(1) default NULL,     -- Y/N
  bad_header_lover     char(1) default NULL,     -- Y/N

  bypass_virus_checks  char(1) default NULL,     -- Y/N
  bypass_spam_checks   char(1) default NULL,     -- Y/N
  bypass_banned_checks char(1) default NULL,     -- Y/N
  bypass_header_checks char(1) default NULL,     -- Y/N

  spam_modifies_subj   char(1) default NULL,     -- Y/N

  virus_quarantine_to      varchar(64) default NULL,
  spam_quarantine_to       varchar(64) default NULL,
  banned_quarantine_to     varchar(64) default NULL,
  bad_header_quarantine_to varchar(64) default NULL,
  clean_quarantine_to      varchar(64) default NULL,
  other_quarantine_to      varchar(64) default NULL,

  spam_tag_level  float default NULL, -- higher score inserts spam info headers
  spam_tag2_level float default NULL, -- inserts 'declared spam' header fields
  spam_kill_level float default NULL, -- higher score triggers evasive actions
                                      -- e.g. reject/drop, quarantine, ...
                                     -- (subject to final_spam_destiny setting)
  spam_dsn_cutoff_level        float default NULL,
  spam_quarantine_cutoff_level float default NULL,

  addr_extension_virus      varchar(64) default NULL,
  addr_extension_spam       varchar(64) default NULL,
  addr_extension_banned     varchar(64) default NULL,
  addr_extension_bad_header varchar(64) default NULL,

  warnvirusrecip      char(1)     default NULL, -- Y/N
  warnbannedrecip     char(1)     default NULL, -- Y/N
  warnbadhrecip       char(1)     default NULL, -- Y/N
  newvirus_admin      varchar(64) default NULL,
  virus_admin         varchar(64) default NULL,
  banned_admin        varchar(64) default NULL,
  bad_header_admin    varchar(64) default NULL,
  spam_admin          varchar(64) default NULL,
  spam_subject_tag    varchar(64) default NULL,
  spam_subject_tag2   varchar(64) default NULL,
  message_size_limit  integer     default NULL, -- max size in bytes, 0 disable
  banned_rulenames    varchar(64) default NULL,  -- comma-separated list of ...
  domain_owner        varchar(64) DEFAULT NULL
        -- names mapped through %banned_rules to actual banned_filename tables
);


CREATE TABLE maddr (
  partition_tag integer   DEFAULT 0,   -- see $sql_partition_tag
  id         bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email      varbinary(255) NOT NULL,    -- full mail address
  domain     varchar(255) NOT NULL,    -- only domain part of the email address
                                       -- with subdomain fields in reverse
  CONSTRAINT part_email UNIQUE (partition_tag,email)
) ENGINE=InnoDB;

CREATE TABLE msgs (
  partition_tag integer  NOT NULL DEFAULT 0,   -- see $sql_partition_tag
  mail_id    varbinary(12)   NOT NULL,  -- long-term unique mail id
  secret_id  varbinary(12)   DEFAULT '',  -- authorizes release of mail_id
  am_id      varchar(20)   NOT NULL,    -- id used in the log
  time_num   integer unsigned NOT NULL, -- rx_time: seconds since Unix epoch
  time_iso TIMESTAMP NOT NULL DEFAULT 0,
  sid        bigint unsigned NOT NULL, -- sender: maddr.id
  policy     varchar(255)  DEFAULT '',  -- policy bank path (like macro %p)
  client_addr varchar(255) DEFAULT '',  -- SMTP client IP address (IPv4 or v6)
  size       integer unsigned NOT NULL, -- message size in bytes
  content    binary(1),                   -- content type: V/B/S/s/M/H/O/C:
                                        -- virus/banned/spam(kill)/spammy(tag2)
                                        -- /bad mime/bad header/oversized/clean
                                        -- is NULL on partially processed mail
  quar_type  binary(1),                   -- quarantined as: ' '/F/Z/B/Q/M/L
                                        --  none/file/zipfile/bsmtp/sql/
                                        --  /mailbox(smtp)/mailbox(lmtp)
  quar_loc   binary(255)  DEFAULT '',  -- quarantine location (e.g. file)
  dsn_sent   char(1),                   -- was DSN sent? Y/N/q (q=quenched)
  spam_level float,                     -- SA spam level (no boosts)
  message_id varchar(255)  DEFAULT '',  -- mail Message-ID header field
  from_addr  varchar(255)  DEFAULT '',  -- mail From header field,    UTF8
  subject    varchar(255)  DEFAULT '',  -- mail Subject header field, UTF8
  host       varchar(255)  NOT NULL,    -- hostname where amavisd is running
  PRIMARY KEY (partition_tag,mail_id),
  KEY msgs_idx_sid (sid),
  KEY msgs_idx_mess_id (message_id),
  KEY msgs_idx_time_iso (time_iso)
) ENGINE=InnoDB;

CREATE TABLE msgrcpt (
  partition_tag integer    DEFAULT 0,    -- see $sql_partition_tag
  mail_id    varbinary(12)   NOT NULL,     -- (must allow duplicates)
  rseqnum    integer       DEFAULT 0,    -- recipient count within one message
  rid        bigint unsigned NOT NULL,  -- recipient: maddr.id (dupl. allowed)
  content    char(1),                    -- content type: V/B/U/S/Y/M/H/O/T/C
  ds         char(1)       NOT NULL,     -- delivery status: P/R/B/D/T
                                         -- pass/reject/bounce/discard/tempfail
  rs         char(1)       NOT NULL,     -- release status: initialized to ' '
  bl         char(1)       DEFAULT ' ',  -- sender blacklisted by this recip
  wl         char(1)       DEFAULT ' ',  -- sender whitelisted by this recip
  bspam_level float,                     -- spam level + per-recip boost
  smtp_resp  varchar(255)  DEFAULT '',   -- SMTP response given to MTA
  KEY msgrcpt_idx_mail_id (mail_id),
  KEY msgrcpt_idx_rid (rid)
) ENGINE=InnoDB;

CREATE TABLE quarantine (
  partition_tag integer    DEFAULT 0,    -- see $sql_partition_tag
  mail_id    varbinary(12)   NOT NULL,    -- long-term unique mail id
  chunk_ind  integer unsigned NOT NULL, -- chunk number, starting with 1
  mail_text  blob NOT NULL,             -- store mail as chunks of octets
  PRIMARY KEY (partition_tag,mail_id,chunk_ind)
) ENGINE=InnoDB;
