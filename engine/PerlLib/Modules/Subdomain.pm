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

package Modules::Subdomain;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Execute;
use iMSCP::Dir;
use iMSCP::OpenSSL;
use Net::LibIDN qw/idn_to_unicode/;
use parent 'Modules::Abstract';

sub _init
{
	my $self = $_[0];

	$self->{'type'} = 'Sub';

	$self;
}

sub loadData
{
	my $self = $_[0];

	my $sql = "
		SELECT
			sub.*, domain_name AS user_home, domain_admin_id, domain_php, domain_cgi, domain_traffic_limit,
			domain_mailacc_limit, domain_dns, external_mail, web_folder_protection, ips.ip_number,
			mail_count.mail_on_domain
		FROM
			subdomain AS sub
		INNER JOIN
			domain ON (sub.domain_id = domain.domain_id)
		INNER JOIN
			server_ips AS ips ON (domain.domain_ip_id = ips.ip_id)
		LEFT JOIN
			(
				SELECT
					sub_id AS id, COUNT( sub_id ) AS mail_on_domain
				FROM
					mail_users
				WHERE
					sub_id= ?
				AND
					mail_type IN ('subdom_mail', 'subdom_forward', 'subdom_mail,subdom_forward', 'subdom_catchall')
				GROUP BY
					sub_id
			) AS mail_count
		ON
			(sub.subdomain_id = mail_count.id)
		WHERE
			sub.subdomain_id = ?
	";
	my $rdata = iMSCP::Database->factory()->doQuery('subdomain_id', $sql, $self->{'subId'}, $self->{'subId'});
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$self->{'subId'}}) {
		error("Subdomain with ID $self->{'subId'} has not been found or is in an inconsistent state");
		return 1;
	}

	%{$self} = (%{$self}, %{$rdata->{$self->{'subId'}}});

	0;
}

sub process
{
	my $self = $_[0];
	$self->{'subId'} = $_[1];

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;

	if($self->{'subdomain_status'} ~~ ['toadd', 'tochange', 'toenable']) {
		$rs = $self->add();

		@sql = (
			'UPDATE subdomain SET subdomain_status = ? WHERE subdomain_id = ?',
			($rs ? scalar getMessageByType('error') : 'ok'), $self->{'subdomain_id'}
		);
	} elsif($self->{'subdomain_status'} eq 'todelete') {
		$rs = $self->delete();

		if($rs) {
			@sql = (
				'UPDATE subdomain SET subdomain_status = ? WHERE subdomain_id = ?',
				scalar getMessageByType('error'), $self->{'subdomain_id'}
			);
		} else {
			@sql = ('DELETE FROM subdomain WHERE subdomain_id = ?', $self->{'subdomain_id'});
		}
	} elsif($self->{'subdomain_status'} eq 'todisable') {
		$rs = $self->disable();

		@sql = (
			'UPDATE subdomain SET subdomain_status = ? WHERE subdomain_id = ?',
			($rs ? scalar getMessageByType('error') : 'disabled'), $self->{'subdomain_id'}
		);
	} elsif($self->{'subdomain_status'} eq 'torestore') {
		$rs = $self->restore();

		@sql = (
			'UPDATE subdomain SET subdomain_status = ? WHERE subdomain_id = ?',
			($rs ? scalar getMessageByType('error') : 'ok'), $self->{'subdomain_id'}
		);
	}

	my $rdata = iMSCP::Database->factory()->doQuery('dummy', @sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$rs;
}

sub _getHttpdData
{
	my $self = $_[0];

	my $groupName = my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

	my $homeDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'user_home'}";
	$homeDir =~ s~/+~/~g;
	$homeDir =~ s~/$~~g;

	my $webDir = "$homeDir/$self->{'subdomain_mount'}";
	$webDir =~ s~/+~/~g;
	$webDir =~ s~/$~~g;

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery('name', 'SELECT * FROM config WHERE name LIKE ?', 'PHPINI%');
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	my $phpiniData = $db->doQuery('domain_id', 'SELECT * FROM php_ini WHERE domain_id = ?', $self->{'domain_id'});
	unless(ref $phpiniData eq 'HASH') {
		error($phpiniData);
		return 1;
	}

	my $certData = $db->doQuery(
		'domain_id',
		'SELECT * FROM ssl_certs WHERE domain_id = ? AND domain_type = ? AND status = ?',
		$self->{'subdomain_id'},
		'sub',
		'ok'
	);
	unless(ref $certData eq 'HASH') {
		error($certData);
		return 1;
	}

	my $haveCert =
		exists $certData->{$self->{'subdomain_id'}} &&
		$self->isValidCertificate($self->{'subdomain_name'} . '.' . $self->{'user_home'});

	$self->{'httpd'} = {
		DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
		DOMAIN_TYPE => 'sub',
		DOMAIN_NAME => $self->{'subdomain_name'} . '.' . $self->{'user_home'},
		DOMAIN_NAME_UNICODE => idn_to_unicode($self->{'subdomain_name'} . '.' . $self->{'user_home'}, 'UTF-8'),
		PARENT_DOMAIN_NAME => $self->{'user_home'},
		ROOT_DOMAIN_NAME => $self->{'user_home'},
		DOMAIN_IP => $self->{'ip_number'},
		WWW_DIR => $main::imscpConfig{'USER_WEB_DIR'},
		HOME_DIR => $homeDir,
		WEB_DIR => $webDir,
		MOUNT_POINT => $self->{'subdomain_mount'},
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
		ALIAS => $userName . 'sub' . $self->{'subdomain_id'},
		FORWARD => defined $self->{'subdomain_url_forward'} && $self->{'subdomain_url_forward'} ne ''
			? $self->{'subdomain_url_forward'} : 'no',
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

	if($self->{'subdomain_status'} eq 'todelete') {
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
		DOMAIN_NAME => $self->{'subdomain_name'} . '.' . $self->{'user_home'},
		DOMAIN_TYPE => $self->{'type'},
		TYPE => 'vsub_entry',
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
		DOMAIN_NAME => $self->{'subdomain_name'} . '.' . $self->{'user_home'},
		PARENT_DOMAIN_NAME => $self->{'user_home'},
		DOMAIN_IP => $self->{'ip_number'},
		USER_NAME => $userName . 'sub' . $self->{'subdomain_id'}
	};

	if($self->{'external_mail'} ~~ ['domain', 'filter']) {
		$self->{'named'}->{'MAIL_ENABLED'} = 1;

		# only no wildcard MX (NOT LIKE '*.%') must be add to existent subdomains
		my $rdata = iMSCP::Database->factory()->doQuery(
			'domain_dns_id',
			'
				SELECT
					domain_dns_id, domain_text
				FROM
					domain_dns
				WHERE
					domain_id = ?
				AND
					alias_id = ?
				AND
					domain_dns NOT LIKE ?
				AND
					domain_type = ?
				AND
					owned_by = ?
			',
			$self->{'domain_id'},
			0,
			'*.%',
			'MX',
			'ext_mail_feature'
		);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}

		($self->{'named'}->{'MAIL_DATA'}->{$_} = $rdata->{$_}->{'domain_text'}) =~ s/(.*)\.$/$1./ for keys %{$rdata};
	} elsif($self->{'mail_on_domain'} || $self->{'domain_mailacc_limit'} >= 0) {
		$self->{'named'}->{'MAIL_ENABLED'} = 1;
		$self->{'named'}->{'MAIL_DATA'}->{1} = "10\tmail.$self->{'user_home'}.";
	} else {
		$self->{'named'}->{'MAIL_ENABLED'} = 0;
	}

	0;
}

sub _getPackagesData
{
	my $self = $_[0];

	my $userName = my $groupName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

	my $homeDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'user_home'}";
	$homeDir =~ s~/+~/~g;
	$homeDir =~ s~/$~~g;

	my $webDir = "$homeDir/$self->{'subdomain_mount'}";
	$webDir =~ s~/+~/~g;
	$webDir =~ s~/$~~g;

	$self->{'packages'} = {
		DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
		ALIAS => $userName,
		DOMAIN_NAME => $self->{'subdomain_name'} . '.' . $self->{'user_home'},
		USER => $userName,
		GROUP => $groupName,
		HOME_DIR => $homeDir,
		WEB_DIR => $webDir,
		FORWARD => defined $self->{'subdomain_url_forward'} && $self->{'subdomain_url_forward'} ne ''
			? $self->{'subdomain_url_forward'} : 'no',
		WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'}
	};

	0;
}

sub _getSharedMountPoints
{
	my $self = $_[0];

	my $regexp = "^$self->{'subdomain_mount'}(/.*|\$)";
	my @sql = (
		"
			SELECT
				alias_mount AS mount_point
			FROM
				domain_aliasses
			WHERE
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
				subdomain_id <> ?
			AND
				domain_id = ?
			AND
				subdomain_status <> 'todelete'
			AND
				subdomain_mount RLIKE ?
			UNION
			SELECT
				subdomain_alias_mount AS mount_point
			FROM
				subdomain_alias
			WHERE
				subdomain_alias_status <> 'todelete'
			AND
				alias_id IN (SELECT alias_id FROM domain_aliasses WHERE domain_id = ?)
			AND
				subdomain_alias_mount RLIKE ?
		",
		$self->{'domain_id'},
		$regexp,

		$self->{'subdomain_id'},
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

sub isValidCertificate
{
	my ($self, $subdomainName) = @_;

	my $certFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$subdomainName.pem";

	my $openSSL = iMSCP::OpenSSL->new(
		'openssl_path' => $main::imscpConfig{'CMD_OPENSSL'},
		'private_key_container_path' => $certFile,
		'certificate_container_path' => $certFile,
		'ca_bundle_container_path' => $certFile
	);

	! $openSSL->validateCertificateChain();
}

1;
