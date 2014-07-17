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

package Modules::Alias;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Execute;
use iMSCP::Dir;
use iMSCP::Database;
use Net::LibIDN qw/idn_to_unicode/;
use parent 'Modules::Domain';

sub loadData
{
	my $self = $_[0];

	my $sql = "
		SELECT
			alias.*, domain_name AS user_home, domain_admin_id, domain_php, domain_cgi, domain_traffic_limit,
			domain_mailacc_limit, domain_dns, web_folder_protection, ips.ip_number, mail_count.mail_on_domain
		FROM
			domain_aliasses AS alias
		INNER JOIN
			domain ON (alias.domain_id = domain.domain_id)
		INNER JOIN
			server_ips AS ips ON (alias.alias_ip_id = ips.ip_id)
		LEFT JOIN
			(
				SELECT
					sub_id AS id, COUNT( sub_id ) AS mail_on_domain
				FROM
					mail_users
				WHERE
					sub_id= ?
				AND
					mail_type IN ('alias_forward', 'alias_mail', 'alias_mail,alias_forward', 'alias_catchall')
				GROUP BY
					sub_id
			) AS mail_count
		ON
			(alias.alias_id = mail_count.id)
		WHERE
			alias.alias_id = ?
	";
	my $rdata = iMSCP::Database->factory()->doQuery('alias_id', $sql, $self->{'alsId'}, $self->{'alsId'});
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$self->{'alsId'}}) {
		error("Domain with ID $self->{'alsId'} has not been found or is in an inconsistent state");
		return 1;
	}

	%{$self} = (%{$self}, %{$rdata->{$self->{'alsId'}}});

	0;
}

sub process
{
	my $self = $_[0];
	$self->{'alsId'} = $_[1];

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;

	if($self->{'alias_status'} ~~ ['toadd', 'tochange', 'toenable']) {
		$rs = $self->add();

		@sql = (
			"UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ?",
			($rs ? scalar getMessageByType('error') : 'ok'),
			$self->{'alias_id'}
		);
	} elsif($self->{'alias_status'} eq 'todelete') {
		$rs = $self->delete();

		if($rs) {
			@sql = (
				"UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ?",
				scalar getMessageByType('error'),
				$self->{'alias_id'}
			);
		} else {
			@sql = ("DELETE FROM domain_aliasses WHERE alias_id = ?", $self->{'alias_id'});
		}
	} elsif($self->{'alias_status'} eq 'todisable') {
		$rs = $self->disable();

		@sql = (
			"UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ?",
			($rs ? scalar getMessageByType('error') : 'disabled'),
			$self->{'alias_id'}
		);
	} elsif($self->{'alias_status'} eq 'torestore') {
		$rs = $self->restore();

		@sql = (
			"UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ?",
			($rs ? scalar getMessageByType('error') : 'ok'),
			$self->{'alias_id'}
		);
	}

	my $rdata = iMSCP::Database->factory()->doQuery('dummy', @sql);
	if(ref $rdata ne 'HASH') {
		error($rdata);
		return 1;
	}

	$rs;
}

sub restore
{
	$_[0]->Modules::Abstract::restore();
}

sub _getHttpdData
{
	my $self = $_[0];

	my $groupName = my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

	my $homeDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'user_home'}";
	$homeDir =~ s~/+~/~g;
	$homeDir =~ s~/$~~g;

	my $webDir = "$homeDir/$self->{'alias_mount'}";
	$webDir =~ s~/+~/~g;
	$webDir =~ s~/$~~g;

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery('name', 'SELECT * FROM config WHERE name LIKE ?', 'PHPINI%');
	unless (ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	my $phpiniData = $db->doQuery('domain_id', 'SELECT * FROM php_ini WHERE domain_id = ?', $self->{'domain_id'});
	unless (ref $phpiniData eq 'HASH') {
		error($phpiniData);
		return 1;
	}

	my $certData = $db->doQuery(
		'domain_id',
		'SELECT * FROM ssl_certs WHERE domain_id = ? AND domain_type = ? AND status = ?',
		$self->{'alias_id'},
		'als',
		'ok'
	);
	unless (ref $certData eq 'HASH') {
		error($certData);
		return 1;
	}

	my $haveCert = exists $certData->{$self->{'alias_id'}} && $self->isValidCertificate($self->{'alias_name'});

	$self->{'httpd'} = {
		DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
		DOMAIN_TYPE => 'als',
		DOMAIN_NAME => $self->{'alias_name'},
		DOMAIN_NAME_UNICODE => idn_to_unicode($self->{'alias_name'}, 'UTF-8'),
		PARENT_DOMAIN_NAME => $self->{'alias_name'},
		ROOT_DOMAIN_NAME => $self->{'user_home'},
		DOMAIN_IP => $self->{'ip_number'},
		WWW_DIR => $main::imscpConfig{'USER_WEB_DIR'},
		HOME_DIR => $homeDir,
		WEB_DIR => $webDir,
		MOUNT_POINT => $self->{'alias_mount'},
		PEAR_DIR => $main::imscpConfig{'PEAR_DIR'},
		PHP_TIMEZONE => $main::imscpConfig{'PHP_TIMEZONE'},
		BASE_SERVER_VHOST_PREFIX => $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'},
		BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
		USER => $userName,
		GROUP => $groupName,
		PHP_SUPPORT => $self->{'domain_php'},
		CGI_SUPPORT => $self->{'domain_cgi'},
		WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'},
		SSL_SUPPORT => $haveCert,
		BWLIMIT => $self->{'domain_traffic_limit'},
		ALIAS => $userName . 'als' . $self->{'alias_id'},
		FORWARD => (defined $self->{'url_forward'} && $self->{'url_forward'} ne '') ? $self->{'url_forward'} : 'no',
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
			: $rdata->{'PHPINI_ALLOW_URL_FOPEN'}->{'value'},
		PHPINI_OPEN_BASEDIR => ($rdata->{'PHPINI_OPEN_BASEDIR'}->{'value'})
			? ':' . $rdata->{'PHPINI_OPEN_BASEDIR'}->{'value'} : ''
	};

	if($self->{'alias_status'} eq 'todelete') {
		my $sharedMountPoints = $self->_getSharedMountPoints();

		unless(ref $sharedMountPoints eq 'HASH') {
			return 1;
		}

		$self->{'httpd'}->{'SHARED_MOUNT_POINTS'} = [keys %{$sharedMountPoints}];
	}

	0;
}

sub _getMtaData
{
	my $self = $_[0];

	$self->{'mta'} = {
		DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
		DOMAIN_NAME => $self->{'alias_name'},
		DOMAIN_TYPE => $self->{'type'},
		TYPE => 'vals_entry',
		EXTERNAL_MAIL => $self->{'external_mail'},
		MAIL_ENABLED => ($self->{'mail_on_domain'} || $self->{'domain_mailacc_limit'} >= 0) ? 1 : 0
	};

	0;
}

sub _getNamedData
{
	my $self = $_[0];

	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

	$self->{'named'} = {
		DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
		DOMAIN_NAME => $self->{'alias_name'},
		DOMAIN_IP => $self->{'ip_number'},
		USER_NAME => $userName . 'als' . $self->{'alias_id'},
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
			'SELECT * FROM domain_dns WHERE domain_id = ? AND alias_id = ?',
			$self->{'domain_id'},
			$self->{'alias_id'}
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

		# We must trigger the module subalias whatever the number of entries - See #503
		$rdata = $db->doQuery(
			'update',
			'
				UPDATE
					subdomain_alias
				SET
					subdomain_alias_status = ?
				WHERE
					subdomain_alias_status = ?
				AND
					alias_id = ?
			',
			'tochange', 'ok', $self->{'alias_id'}
		);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}
	}

	0;
}

sub _getPackagesData
{
	my $self = $_[0];

	my $userName = my $groupName =  $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

	my $homeDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'user_home'}";
	$homeDir =~ s~/+~/~g;
	$homeDir =~ s~/$~~g;

	my $webDir = "$homeDir/$self->{'user_home'}/$self->{'alias_mount'}";
	$webDir =~ s~/+~/~g;
	$webDir =~ s~/$~~g;

	$self->{'packages'} = {
		DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
		ALIAS => $userName,
		DOMAIN_NAME => $self->{'alias_name'},
		USER => $userName,
		GROUP => $groupName,
		HOME_DIR => $homeDir,
		WEB_DIR => $webDir,
		FORWARD => (defined $self->{'url_forward'} && $self->{'url_forward'} ne '') ? $self->{'url_forward'} : 'no',
		WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'}
	};

	0;
}

sub _getSharedMountPoints
{
	my $self = $_[0];

	my $regexp = "^$self->{'alias_mount'}(/.*|\$)";
	my @sql = (
		"
			SELECT
				alias_mount AS mount_point
			FROM
				domain_aliasses
			WHERE
				alias_id <> ?
			AND
				domain_id = ?
			AND
				alias_status NOT IN ('todelete', 'ordered')
			AND
				alias_mount RLIKE ?
			UNION
			SELECT
				subdomain_mount AS mount_point
			FROM
				subdomain
			WHERE
				domain_id = ?
			AND
				subdomain_status != 'todelete'
			AND
				subdomain_mount RLIKE ?
			UNION
			SELECT
				subdomain_alias_mount AS mount_point
			FROM
				subdomain_alias
			WHERE
				subdomain_alias_status != 'todelete'
			AND
				alias_id IN (SELECT alias_id FROM domain_aliasses WHERE domain_id = ?)
			AND
				subdomain_alias_mount RLIKE ?
		",
		$self->{'alias_id'},
		$self->{'domain_id'},
		$regexp,

		$self->{'domain_id'},
		$regexp,

		$self->{'domain_id'},
		$regexp
	);
	my $rdata = iMSCP::Database->factory()->doQuery('mount_point', @sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$rdata;
}

1;
