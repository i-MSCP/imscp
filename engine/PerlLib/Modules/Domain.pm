#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::Domain;

use strict;
use warnings;

use iMSCP::Debug;
use Modules::User;
use iMSCP::Execute;
use iMSCP::Dir;
use iMSCP::Database;
use iMSCP::Rights;
use iMSCP::Database;
use iMSCP::OpenSSL;
use iMSCP::Ext2Attributes qw(clearImmutable);
use Net::LibIDN qw/idn_to_unicode/;
use parent 'Modules::Abstract';

sub _init
{
	my $self = shift;

	$self->{'type'} = 'Dmn';

	$self;
}

sub loadData
{
	my $self = shift;

	my $sql = "
		SELECT
			`domain`.*, `ips`.`ip_number`, `mail_count`.`mail_on_domain`
		FROM
			`domain` AS `domain`
		INNER JOIN
			`server_ips` AS `ips` ON (`domain`.`domain_ip_id` = `ips`.`ip_id`)
		LEFT JOIN
			(
				SELECT
					`domain_id` AS `id`, COUNT( `domain_id` ) AS `mail_on_domain`
				FROM
					`mail_users` WHERE `sub_id` = 0
				GROUP BY
					`domain_id`
			) AS `mail_count`
		ON
			(`domain`.`domain_id` = `mail_count`.`id`)
		WHERE
			`domain_id` = ?
	";
	my $rdata = iMSCP::Database->factory()->doQuery('domain_id', $sql, $self->{'dmnId'});
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$self->{'dmnId'}}) {
		error("Domain with ID '$self->{'dmnId'}' has not been found or is in an inconsistent state");
		return 1;
	}

	%{$self} = (%{$self}, %{$rdata->{$self->{'dmnId'}}});

	0;
}

sub process
{
	my $self = shift;
	$self->{'dmnId'} = shift;

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;

	if($self->{'domain_status'} =~ /^toadd|tochange|toenable$/) {
		$rs = $self->add();

		@sql = (
			"UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?",
			($rs ? scalar getMessageByType('error') : 'ok'),
			$self->{'domain_id'}
		);
	} elsif($self->{'domain_status'} eq 'todelete') {
		$rs = $self->delete();

		if($rs) {
			@sql = (
				"UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?",
				scalar getMessageByType('error'),
				$self->{'domain_id'}
			);
		} else {
			@sql = ("DELETE FROM `domain` WHERE `domain_id` = ?", $self->{'domain_id'});
		}
	} elsif($self->{'domain_status'} eq 'todisable') {
		$rs = $self->disable();

		@sql = (
			"UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?",
			($rs ? scalar getMessageByType('error') : 'disabled'),
			$self->{'domain_id'}
		);
	} elsif($self->{'domain_status'} eq 'torestore') {
		$rs = $self->restore();

		@sql = (
			"UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?",
			($rs ? scalar getMessageByType('error') : 'ok'),
			$self->{'domain_id'}
		);
	}

	my $rdata = iMSCP::Database->factory()->doQuery('dummy', @sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$rs;
}

sub restore
{
	my $self = shift;

	$self->{'action'} = 'restore';

	my $dmnDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}";
	my $dmnBkpdir = "$dmnDir/backups";
	my ($cmd, $rdata, $stdout, $stderr);
	my $rs = 0;

	my @bkpFiles = iMSCP::Dir->new('dirname' => $dmnBkpdir)->getFiles();

	return 0 unless @bkpFiles;

	for (@bkpFiles) {
		if(/^(.+?)\.sql\.(bz2|gz|lzma|xz)$/) { # Restore SQL database
			my $sql = "
				SELECT
					*
				FROM
					`sql_database`, `sql_user`
				WHERE
					`sql_database`.`domain_id` = ?
				AND
					`sql_user`.`sqld_id` = `sql_database`.`sqld_id`
				AND
					`sql_database`.`sqld_name` = ?
			";
			$rdata = iMSCP::Database->factory()->doQuery('sqld_name', $sql, $self->{'domain_id'}, $1);
			unless(ref $rdata eq 'HASH') {
				error($rdata);
				return 1,
			}

			unless(exists $rdata->{$1}) {
				$rdata = iMSCP::Database->factory()->doQuery(
					'dummy',
					"
						INSERT INTO `log` (
							`log_message`
						) VALUES (
							'
								Unable to restore the <strong>$1</strong> SQL database of domain $self->{'domain_name'}:
								Unknown database.
							'
						)
					"
				);
				unless(ref $rdata eq 'HASH') {
					error($rdata);
					return 1,
				}

				warning("orphaned database found ($1). skipping...");
				next;
			}

			if(scalar keys %{$rdata}) {
				my $dbuser = escapeShell($rdata->{$1}->{'sqlu_name'});
				my $dbpass = escapeShell($rdata->{$1}->{'sqlu_pass'});
				my $dbname = escapeShell($rdata->{$1}->{'sqld_name'});

				if($2 eq 'bz2') {
					$cmd = "$main::imscpConfig{'CMD_BZCAT'} -d ";
				} elsif($2 eq 'gz') {
					$cmd = "$main::imscpConfig{'CMD_GZCAT'} -d ";
				} elsif($2 eq 'lzma') {
					$cmd = "$main::imscpConfig{'CMD_LZMA'} -dc ";
				} elsif($2 eq 'xz') {
					$cmd = "$main::imscpConfig{'CMD_XZ'} -dc ";
				}

				$cmd .= "$dmnBkpdir/$_ | $main::imscpConfig{'CMD_MYSQL'} -u$dbuser -p$dbpass $dbname";

				$rs = execute($cmd, \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs;
			}
		} elsif(/^.+?\.tar\.(bz2|gz|lzma|xz)$/) { # Restore domain files
			# Since we are now using extended attribute to protect some folders, we must in order do the following to
			# restore a backup archive:
			#
			# - Update status of sub, als and alssub, entities linked to the parent domain to 'torestore'
			# - Un-protect user home dir (clear immutable flag recursively)
			# - restore the files
			# - Run the restore() parent method
			#
			# The first and last tasks allow the i-MSCP Httpd server implementations to set correct permissions and set
			# immutable flag on folders if needed for each entity
			#
			# Note: This is a bunch of works but this will be fixed when the backup feature will be rewritten

			my $type = $1;

			if($type eq 'bz2') {
				$type = 'bzip2';
			} elsif($type eq 'gz') {
				$type = 'gzip';
			}

			# TODO: Should we also update status of htuser, htgroup and htaccess entities?
			my $database = iMSCP::Database->factory();

			# Update status of any sub to 'torestore'
			$rdata = $database->doQuery(
				'dummy',
				"UPDATE `subdomain` SET `subdomain_status` = 'torestore' WHERE `domain_id` = ?",
				$self->{'domain_id'}
			);
			unless(ref $rdata eq 'HASH') {
				error($rdata);
				return 1;
			}

			# Update status of any als to 'torestore'
			$rdata = $database->doQuery(
				'dummy',
				"UPDATE `domain_aliasses` SET `alias_status` = 'torestore' WHERE `domain_id` = ?",
				$self->{'domain_id'}
			);
			unless(ref $rdata eq 'HASH') {
				error($rdata);
				return 1;
			}

			# Update status of any alssub to 'torestore'
			$rdata = $database->doQuery(
				'dummy',
				"
					UPDATE
						`subdomain_alias`
					SET
						`subdomain_alias_status` = 'torestore'
					WHERE
						`alias_id` IN (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?)
				",
				$self->{'domain_id'}
			);
			unless(ref $rdata eq 'HASH') {
				error($rdata);
				return 1;
			}

			# Un-protect folders recursively
			clearImmutable($dmnDir, 1);

			$cmd = "$main::imscpConfig{'CMD_TAR'} -x -p --$type -C '$dmnDir' -f '$dmnBkpdir/$_'";
			$rs = execute($cmd, \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;

			my $groupName =
			my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
				($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

			$rs = setRights($dmnDir, { 'user' => $userName, 'group' => $groupName, 'recursive' => 1 });
			return $rs if $rs;

			$rs = $self->SUPER::restore();
			return $rs if $rs;
		}
	}

	0;
}

sub buildHTTPDData
{
	my $self = shift;

	my $groupName = my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

	my $homeDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}";
	$homeDir =~ s~/+~/~g;
	$homeDir =~ s~/$~~g;

	my $db = iMSCP::Database->factory();

	my $sql = "SELECT * FROM `config` WHERE `name` LIKE 'PHPINI%'";
	my $rdata = $db->doQuery('name', $sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$sql = "SELECT * FROM `php_ini` WHERE `domain_id` = ?";
	my $phpiniData = $db->doQuery('domain_id', $sql, $self->{'domain_id'});
	unless(ref $phpiniData eq 'HASH') {
		error($phpiniData);
		return 1;
	}

	$sql = "SELECT * FROM `ssl_certs` WHERE `id` = ? AND `type` = ? AND `status` = ?";
	my $certData = $db->doQuery('id', $sql, $self->{'domain_id'}, 'dmn', 'ok');
	unless(ref $certData eq 'HASH') {
		error($certData);
		return 1;
	}

	my $haveCert = exists $certData->{$self->{'domain_id'}} && ! $self->testCert($self->{'domain_name'});

	$self->{'httpd'} = {
		DOMAIN_TYPE => 'dmn',
		DOMAIN_NAME => $self->{'domain_name'},
		DOMAIN_NAME_UNICODE => idn_to_unicode($self->{'domain_name'}, 'UTF-8'),
		PARENT_DOMAIN_NAME => $self->{'domain_name'},
		ROOT_DOMAIN_NAME => $self->{'domain_name'},
		DOMAIN_IP => $self->{'ip_number'},
		WWW_DIR => $main::imscpConfig{'USER_WEB_DIR'},
		HOME_DIR => $homeDir,
		WEB_DIR => $homeDir,
		MOUNT_POINT => '/',
		PEAR_DIR => $main::imscpConfig{'PEAR_DIR'},
		PHP_TIMEZONE => $main::imscpConfig{'PHP_TIMEZONE'},
		PHP_VERSION => $main::imscpConfig{'PHP_VERSION'},
		BASE_SERVER_VHOST_PREFIX => $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'},
		BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
		USER => $userName,
		GROUP => $groupName,
		PHP_SUPPORT => $self->{'domain_php'},
		CGI_SUPPORT => $self->{'domain_cgi'},
		WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'},
		SSL_SUPPORT => $haveCert,
		BWLIMIT => $self->{'domain_traffic_limit'},
		ALIAS => $userName,
		FORWARD => 'no',
		DISABLE_FUNCTIONS => (exists $phpiniData->{$self->{'domain_id'}})
		 	? $phpiniData->{$self->{'domain_id'}}->{'disable_functions'}
		 	: $rdata->{'PHPINI_DISABLE_FUNCTIONS'}->{'value'},
		MAX_EXECUTION_TIME => (exists $phpiniData->{$self->{'domain_id'}})
			? $phpiniData->{$self->{'domain_id'}}->{'max_execution_time'}
			: $rdata->{'PHPINI_MAX_EXECUTION_TIME'}->{'value'},
		MAX_INPUT_TIME => (exists $phpiniData->{$self->{'domain_id'}})
		 	? $phpiniData->{$self->{'domain_id'}}->{'max_input_time'}
		 	: $rdata->{'PHPINI_MAX_INPUT_TIME'}->{'value'},
		MEMORY_LIMIT => (exists $phpiniData->{$self->{'domain_id'}})
			? $phpiniData->{$self->{'domain_id'}}->{'memory_limit'}
			: $rdata->{'PHPINI_MEMORY_LIMIT'}->{'value'},
		ERROR_REPORTING => (exists $phpiniData->{$self->{'domain_id'}})
			? $phpiniData->{$self->{'domain_id'}}->{'error_reporting'}
			: $rdata->{'PHPINI_ERROR_REPORTING'}->{'value'},
		DISPLAY_ERRORS => (exists $phpiniData->{$self->{'domain_id'}})
			? $phpiniData->{$self->{'domain_id'}}->{'display_errors'}
			: $rdata->{'PHPINI_DISPLAY_ERRORS'}->{'value'},
		POST_MAX_SIZE => (exists $phpiniData->{$self->{'domain_id'}})
			? $phpiniData->{$self->{'domain_id'}}->{'post_max_size'}
			: $rdata->{'PHPINI_POST_MAX_SIZE'}->{'value'},
		UPLOAD_MAX_FILESIZE => (exists $phpiniData->{$self->{'domain_id'}})
			? $phpiniData->{$self->{'domain_id'}}->{'upload_max_filesize'}
			: $rdata->{'PHPINI_UPLOAD_MAX_FILESIZE'}->{'value'},
		ALLOW_URL_FOPEN => (exists $phpiniData->{$self->{'domain_id'}})
			? $phpiniData->{$self->{'domain_id'}}->{'allow_url_fopen'}
			: $rdata->{'PHPINI_ALLOW_URL_FOPEN'}->{value},
		PHPINI_OPEN_BASEDIR => ($rdata->{'PHPINI_OPEN_BASEDIR'}->{'value'})
			? ':' . $rdata->{'PHPINI_OPEN_BASEDIR'}->{'value'} : ''
	};

	0;
}

sub buildMTAData
{
	my $self = shift;

	$self->{'mta'} = {
		DOMAIN_NAME => $self->{'domain_name'},
		DOMAIN_TYPE => $self->{'type'},
		TYPE => 'vdmn_entry',
		EXTERNAL_MAIL => $self->{'external_mail'},
		MAIL_ENABLED => ($self->{'mail_on_domain'} || $self->{'domain_mailacc_limit'} >= 0) ? 1 : 0
	};

	0;
}

sub buildNAMEDData
{
	my $self = shift;

	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

	$self->{'named'} = {
		DOMAIN_NAME => $self->{'domain_name'},
		DOMAIN_IP => $self->{'ip_number'},
		USER_NAME => $userName,
		MAIL_ENABLED => (
			($self->{'mail_on_domain'} || $self->{'domain_mailacc_limit'} >= 0) &&
			($self->{'external_mail'} ~~ ['wildcard', 'off'])
		) ? 1 : 0
	};

	if($self->{'action'} eq 'add') {
		# Get DNS resource record added by 3rd party components (custom dns feature, mail feature, plugins...)
		my $db = iMSCP::Database->factory();

		my $rdata = $db->doQuery(
			'domain_dns_id',
			'SELECT * FROM `domain_dns` WHERE `domain_dns`.`domain_id` = ? AND `domain_dns`.`alias_id` = ?',
			$self->{'domain_id'}, 0
		);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}

		$self->{'named'}->{'CUSTOM_DNS_RECORD'}->{$_} = $rdata->{$_} for keys %{$rdata};

		# Add SPF records for external MX if needed
		if($self->{'external_mail'} ~~ ['domain', 'filter', 'wildcard']) {
			my $hosts = { 'domain' => [], 'wildcard' => [] };

			for(keys %{$self->{'named'}->{'CUSTOM_DNS_RECORD'}}) {
				if($self->{'named'}->{'CUSTOM_DNS_RECORD'}->{$_}->{'owned_by'} eq 'ext_mail_feature') {
					(my $host = $self->{'named'}->{'CUSTOM_DNS_RECORD'}->{$_}->{'domain_text'}) =~ s/\d+\s+(.*)\.$/$1/;

					if(index($self->{'named'}->{'CUSTOM_DNS_RECORD'}->{$_}->{'domain_dns'}, '*') != 0) {
						push @{$hosts->{'domain'}}, "a:$host";
					} else {
						push @{$hosts->{'wildcard'}}, "a:$host";
					}
				}
			}

			for my $type (keys %{$hosts}) {
				if(@{$hosts->{$type}}) {
					for('TXT', 'SPF') {
						$self->{'named'}->{'CUSTOM_DNS_RECORD'}->{"$_$type"} = {
							'domain_dns' => ($type eq 'domain') ? '@' : '*',
							'domain_class' => 'IN',
							'domain_type' => $_,
							'domain_text' => "\"v=spf1 @{$hosts->{$type}} mx -all\""
						}
					}
				}
			}
		}

		# We must trigger the module 'subdomain' whatever the number of entries - See #503
		$rdata = $db->doQuery(
			'dummy',
			'UPDATE `subdomain` SET `subdomain_status` = ? WHERE `subdomain_status` = ? AND`domain_id` = ?',
			'tochange', 'ok', $self->{'domain_id'}
		);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}
	}

	0;
}

sub buildADDONData
{
	my $self = shift;

	my $userName = my $groupName  = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

	my $homeDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}";
	$homeDir =~ s~/+~/~g;
	$homeDir =~ s~/$~~g;

	$self->{'AddonsData'} = {
		ALIAS => $userName,
		DOMAIN_NAME => $self->{'domain_name'},
		USER => $userName,
		GROUP => $groupName,
		HOME_DIR => $homeDir,
		WEB_DIR => $homeDir,
		FORWARD => 'no',
		WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'}
	};

	0;
}

sub testCert
{
	my $self = shift;
	my $domainName = shift;

	my $certFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$domainName.pem";
	my $openSSL = iMSCP::OpenSSL->getInstance();

	$openSSL->{'openssl_path'} = $main::imscpConfig{'CMD_OPENSSL'};
	$openSSL->{'cert_path'} = $certFile;
	$openSSL->{'intermediate_cert_path'} = $certFile;
	$openSSL->{'key_path'} = $certFile;

	$openSSL->ssl_check_all();
}

1;
