#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
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
use Servers::httpd;
use iMSCP::Database;
use Modules::openssl;
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
			`domain`.*, `ips`.`ip_number`, `mail_count`.`mail_on_domain`, `ips_count`.`domains_on_ip`
		FROM
			`domain` AS `domain`
		LEFT JOIN
			`server_ips` AS `ips` ON (`domain`.`domain_ip_id` = `ips`.`ip_id`)
		LEFT JOIN
			(
				SELECT
					`domain_id` AS `id`, COUNT( `domain_id` ) AS `mail_on_domain`
				FROM
					`mail_users` WHERE `sub_id`= 0
				GROUP BY
					`domain_id`
			) AS `mail_count`
		ON
			(`domain`.`domain_id` = `mail_count`.`id`)
		LEFT JOIN
			(
				SELECT
					`domain_ip_id` AS `ip_id`, COUNT( `domain_ip_id` ) AS `domains_on_ip`
				FROM
					`domain` WHERE `domain_status` != 'delete'
				GROUP BY
					`domain_ip_id`
			) AS `ips_count`
		ON
			(`domain`.`domain_ip_id` = `ips_count`.`ip_id`)
		WHERE
			`domain_id` = ?
	";

	my $rdata = iMSCP::Database->factory()->doQuery('domain_id', $sql, $self->{'dmnId'});
	if(ref $rdata ne 'HASH') {
		error($rdata);
		return 1;
	}

	unless($rdata->{$self->{'dmnId'}}) {
		error("No domain has id = $self->{'dmnId'}");
		return 1
	}

	$self->{$_} = $rdata->{$self->{'dmnId'}}->{$_} for keys %{$rdata->{$self->{'dmnId'}}};

	0;
}

sub process
{
	my $self = shift;
	$self->{'dmnId'} = shift;

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;

	if($self->{'domain_status'} =~ /^toadd|change|toenable|dnschange$/) {
		$rs = $self->add();
		@sql = (
			"UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?",
			($rs ? scalar getMessageByType('error') : 'ok'),
			$self->{'domain_id'}
		);
	} elsif($self->{'domain_status'} eq 'delete') {
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
	} elsif($self->{'domain_status'} eq 'restore') {
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
			# restore a backup:
			#
			# - Update status of sub, als and alssub, entities linked to the parent domain to 'restore'
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

			# Update status of any sub to 'restore'
			$rdata = $database->doQuery(
				'dummy',
				"UPDATE `subdomain` SET `subdomain_status` = 'restore' WHERE `domain_id` = ?",
				$self->{'domain_id'}
			);
			unless(ref $rdata eq 'HASH') {
				error($rdata);
				return 1;
			}

			# Update status of any als to 'restore'
			$rdata = $database->doQuery(
				'dummy',
				"UPDATE `domain_aliasses` SET `alias_status` = 'restore' WHERE `domain_id` = ?",
				$self->{'domain_id'}
			);
			unless(ref $rdata eq 'HASH') {
				error($rdata);
				return 1;
			}

			# Update status of any alssub to 'restore'
			$rdata = $database->doQuery(
				'dummy',
				"
					UPDATE
						`subdomain_alias`
					SET
						`subdomain_alias_status` = 'restore'
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

	my $groupName =
	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

	my $hDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}";
	$hDir =~ s~/+~/~g;

	my $pDir = $hDir;

	my $sql = "SELECT * FROM `config` WHERE `name` LIKE 'PHPINI%'";
	my $rdata = iMSCP::Database->factory()->doQuery('name', $sql);
	if(ref $rdata ne 'HASH') {
		error($rdata);
		return 1;
	}

	$sql = "SELECT * FROM `php_ini` WHERE `domain_id` = ?";
	my $phpiniData = iMSCP::Database->factory()->doQuery('domain_id', $sql, $self->{'domain_id'});
	if(ref $phpiniData ne 'HASH') {
		error($phpiniData);
		return 1;
	}

	$sql = "SELECT * FROM `ssl_certs` WHERE `id` = ? AND `type` = ? AND `status` = ?";
	my $certData = iMSCP::Database->factory()->doQuery('id', $sql, $self->{'domain_id'}, 'dmn', 'ok');
	if(ref $certData ne 'HASH') {
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
		WEB_DIR => $hDir,
		MOUNT_POINT => '/',
		PARENT_DIR => $pDir,
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
		IP_ON_DOMAIN => defined $self->{'domains_on_ip'}
			? $self->{'domains_on_ip'}
			: 0,
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
		PHPINI_OPEN_BASEDIR => (exists $phpiniData->{$self->{'domain_id'}}->{'PHPINI_OPEN_BASEDIR'})
			? ':' . $phpiniData->{$self->{'domain_id'}}->{'PHPINI_OPEN_BASEDIR'}
			: $rdata->{'PHPINI_OPEN_BASEDIR'}->{'value'} ? ':'.$rdata->{'PHPINI_OPEN_BASEDIR'}->{'value'} : ''
	};

	0;
}

sub buildMTAData
{
	my $self = shift;

	if(
		$self->{'action'} ne 'add' || defined $self->{'mail_on_domain'} && $self->{'mail_on_domain'} > 0 ||
		defined $self->{'domain_mailacc_limit'} && $self->{'domain_mailacc_limit'} >= 0
	) {
		$self->{'mta'} = {
			DOMAIN_NAME => $self->{'domain_name'},
			DOMAIN_TYPE => $self->{'type'},
			TYPE => 'vdmn_entry',
			EXTERNAL_MAIL => $self->{'external_mail'}
		};
	}

	0;
}

sub buildNAMEDData
{
	my $self = shift;

	# Both features custom dns and external mail share the same table but are independent
	if($self->{'action'} eq 'add' && ($self->{'domain_dns'} eq 'yes' || $self->{'external_mail'} eq 'on')) {

		my $sql = "
			SELECT
				*
			FROM
				`domain_dns`
			WHERE
				`domain_dns`.`domain_id` = ?
			AND
				`domain_dns`.`alias_id` = ?
		";

		my $rdata = iMSCP::Database->factory()->doQuery('domain_dns_id', $sql, $self->{'domain_id'}, 0);
		if(ref $rdata ne 'HASH') {
			error($rdata);
			return 1;
		}

		$self->{'named'}->{'DMN_CUSTOM'}->{$_} = $rdata->{$_} for keys %$rdata;

		# We must trigger the module 'subdomain' whatever the number of entries
		# found in the 'domain_dns' table to ensure that subdomain DNS entries will
		# be re-added into the db zone file. (It's a temporary fix for #503)
		#if(scalar keys %$rdata){
			$sql = "
				UPDATE
					`subdomain`
				SET
					`subdomain_status` = ?
				WHERE
					`subdomain_status` = ?
				AND
					`domain_id` = ?
			";

			$rdata = iMSCP::Database->factory()->doQuery('dummy', $sql, 'change', 'ok', $self->{'domain_id'});
			if(ref $rdata ne 'HASH') {
				error($rdata);
				return 1;
			}
		#}
	}

	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

	$self->{'named'}->{'DOMAIN_NAME'} = $self->{'domain_name'};
	$self->{'named'}->{'DOMAIN_IP'} = $self->{'ip_number'};
	$self->{'named'}->{'USER_NAME'} = $userName;
	$self->{'named'}->{'MX'} = (
		($self->{'mail_on_domain'} || $self->{'domain_mailacc_limit'} >= 0) && ($self->{'external_mail'} ne 'on')
		? '' : ';'
	);

	0;
}

sub buildADDONData
{
	my $self = shift;

	my $groupName =
	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

	my $hDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}";
	$hDir =~ s~/+~/~g;

	$self->{'AddonsData'} = {
		DOMAIN_NAME => $self->{'domain_name'},
		USER => $userName,
		GROUP => $groupName,
		WEB_DIR => $hDir,
		WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'}
	};

	0;
}

sub testCert
{
	my $self = shift;
	my $domainName = shift;

	my $certFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$domainName.pem";
	my $openSSL = Modules::openssl->getInstance();

	$openSSL->{'openssl_path'} = $main::imscpConfig{'CMD_OPENSSL'};
	$openSSL->{'cert_path'} = $certFile;
	$openSSL->{'intermediate_cert_path'} = $certFile;
	$openSSL->{'key_path'} = $certFile;

	$openSSL->ssl_check_all();
}

1;
