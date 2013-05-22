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

package Modules::Alias;

use strict;
use warnings;

use iMSCP::Debug;
use File::Temp;
use iMSCP::Database;
use iMSCP::Servers;
use iMSCP::Addons;
use iMSCP::Execute;
use iMSCP::Dir;
use Servers::httpd;
use iMSCP::Database;
use Net::LibIDN qw/idn_to_unicode/;
use parent 'Modules::Domain';

sub loadData
{
	my $self = shift;

	my $sql = "
		SELECT
			`alias`.*, `domain_name` AS `user_home`, `domain_admin_id`, `domain_php`, `domain_cgi`,
			`domain_traffic_limit`, `domain_mailacc_limit`, `domain_dns`, `web_folder_protection`, `ips`.`ip_number`,
			`mail_count`.`mail_on_domain`
		FROM
			`domain_aliasses` AS `alias`
		LEFT JOIN
			`domain` ON (`alias`.`domain_id` = `domain`.`domain_id`)
		LEFT JOIN
			`server_ips` AS `ips` ON (`alias`.`alias_ip_id` = `ips`.`ip_id`)
		LEFT JOIN
			(
				SELECT
					`sub_id` AS `id`, COUNT( `sub_id` ) AS `mail_on_domain`
				FROM
					`mail_users`
				WHERE
					`sub_id`= ?
				AND
					`mail_type` IN ('alias_forward', 'alias_mail', 'alias_mail,alias_forward', 'alias_catchall')
				GROUP BY
					`sub_id`
			) AS `mail_count`
		ON
			(`alias`.`alias_id` = `mail_count`.`id`)
		WHERE
		`alias`.`alias_id` = ?
	";

	my $rdata = iMSCP::Database->factory()->doQuery('alias_id', $sql, $self->{'alsId'}, $self->{'alsId'});
	if(ref $rdata ne 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$self->{'alsId'}}) {
		error("No alias has id = $self->{'alsId'}");
		return 1;
	}

	$self->{$_} = $rdata->{$self->{'alsId'}}->{$_} for keys %{$rdata->{$self->{'alsId'}}};

	0;
}

sub process
{
	my $self = shift;
	$self->{'alsId'} = shift;

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;

	if($self->{'alias_status'} =~ /^toadd|change|toenable|dnschange$/) {
		$rs = $self->add();
		@sql = (
			"UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `alias_id` = ?",
			($rs ? scalar getMessageByType('error') : 'ok'),
			$self->{'alias_id'}
		);
	} elsif($self->{'alias_status'} eq 'delete') {
		$rs = $self->delete();
		if($rs) {
			@sql = (
				"UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `alias_id` = ?",
				scalar getMessageByType('error'),
				$self->{'alias_id'}
			);
		}else {
			@sql = ("DELETE FROM `domain_aliasses` WHERE `alias_id` = ?", $self->{'alias_id'});
		}
	} elsif($self->{'alias_status'} eq 'todisable') {
		$rs = $self->disable();
		@sql = (
			"UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `alias_id` = ?",
			($rs ? scalar getMessageByType('error') : 'disabled'),
			$self->{'alias_id'}
		);
	} elsif($self->{'alias_status'} eq 'restore') {
		$rs = $self->restore();
		@sql = (
			"UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `alias_id` = ?",
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

sub buildHTTPDData
{
	my $self = shift;
	my $groupName =
	my $userName =
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} .
			($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});
	my $hDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'user_home'}/$self->{'alias_mount'}";
	$hDir =~ s~/+~/~g;

	my $pDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'user_home'}";
	$pDir =~ s~/+~/~g;

	my $sql = "SELECT * FROM `config` WHERE `name` LIKE 'PHPINI%'";
	my $rdata = iMSCP::Database->factory()->doQuery('name', $sql);
	if (ref $rdata ne 'HASH') {
		error($rdata);
		return 1;
	}

	$sql = "SELECT * FROM `php_ini` WHERE `domain_id` = ?";
	my $phpiniData = iMSCP::Database->factory()->doQuery('domain_id', $sql, $self->{'domain_id'});
	if (ref $phpiniData ne 'HASH') {
    	error($phpiniData);
    	return 1;
    }

	$sql = "SELECT * FROM `ssl_certs` WHERE `id` = ? AND `type` = ? AND `status` = ?";
	my $certData = iMSCP::Database->factory()->doQuery('id', $sql, $self->{'alias_id'}, 'als', 'ok');
	if (ref $certData ne 'HASH') {
        error($certData);
        return 1;
       }

	my $haveCert = exists $certData->{$self->{'alias_id'}} && !$self->testCert($self->{'alias_name'});

	$self->{'httpd'} = {
		DOMAIN_TYPE => 'als',
		DOMAIN_NAME => $self->{'alias_name'},
		DOMAIN_NAME_UNICODE => idn_to_unicode($self->{'alias_name'}, 'UTF-8'),
		ROOT_DOMAIN_NAME => $self->{'user_home'},
		PARENT_DOMAIN_NAME => $self->{'user_home'},
		DOMAIN_IP => $self->{'ip_number'},
		WWW_DIR => $main::imscpConfig{'USER_WEB_DIR'},
		WEB_DIR => $hDir,
		MOUNT_POINT => $self->{'alias_mount'},
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
		ALIAS => $userName.'als' . $self->{'alias_id'},
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
		PHPINI_OPEN_BASEDIR => (exists $phpiniData->{$self->{'domain_id'}}->{'PHPINI_OPEN_BASEDIR'})
			? ':'.$phpiniData->{$self->{'domain_id'}}->{'PHPINI_OPEN_BASEDIR'}
			: $rdata->{'PHPINI_OPEN_BASEDIR'}->{'value'} ? ':' . $rdata->{'PHPINI_OPEN_BASEDIR'}->{'value'} : ''
	};

	if($self->{'alias_status'} eq 'delete') {
		my $sharedMountPoints = $self->_getSharedMountPoints();

		unless(ref $sharedMountPoints eq 'HASH') {
			return 1;
		}

		$self->{'httpd'}->{'SHARED_MOUNT_POINTS'} = [keys %{$sharedMountPoints}];
	}

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
			DOMAIN_NAME => $self->{'alias_name'},
			DOMAIN_TYPE => $self->{'type'},
			TYPE => 'vals_entry',
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

		my $database = iMSCP::Database->factory();
		my $rdata = $database->doQuery('domain_dns_id', $sql, $self->{'domain_id'}, $self->{'alias_id'});
		if(ref $rdata ne 'HASH') {
			error($rdata);
			return 1;
		}

		$self->{'named'}->{'DMN_CUSTOM'}->{$_} = $rdata->{$_} for keys %$rdata;

		# We must trigger the module subalias whatever the number of entries
		# found in the 'domain_dns' table to ensure that sub alias DNS entries will
		# be re-added into the db zone file. (It's a temporary fix for #503)
		#if(scalar keys %$rdata){
			my $sql = "
				UPDATE
					`subdomain_alias`
				SET
					`subdomain_alias_status` = ?
				WHERE
					`subdomain_alias_status` = ?
				AND
					`alias_id` = ?
			";

			my $rdata = iMSCP::Database->factory()->doQuery('update', $sql, 'change', 'ok', $self->{'alias_id'});
			if(ref $rdata ne 'HASH') {
				error($rdata);
				return 1;
			}
		#}
	}

	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

	$self->{'named'}->{'DOMAIN_NAME'} = $self->{'alias_name'};
	$self->{'named'}->{'DOMAIN_IP'} = $self->{'ip_number'};
	$self->{'named'}->{'USER_NAME'} = $userName . 'als' . $self->{'alias_id'};
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

	my $hDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'user_home'}";
	$hDir =~ s~/+~/~g;

	$self->{'AddonsData'} = {
		DOMAIN_NAME => $self->{'alias_name'},
		USER => $userName,
		GROUP => $groupName,
		WEB_DIR => $hDir,
		WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'}
	};

	0;
}

sub _getSharedMountPoints
{
	my $self = shift;

	my $regexp = "^$self->{'alias_mount'}(/.*|\$)";
	my @sql = (
		"
			SELECT
				`alias_mount` AS `mount_point`
			FROM
				`domain_aliasses`
			WHERE
				`alias_id` <> ?
			AND
				`domain_id` = ?
			AND
				`alias_status` NOT IN ('delete', 'ordered')
			AND
				`alias_mount` RLIKE ?
			UNION
			SELECT
				`subdomain_mount` AS `mount_point`
			FROM
				`subdomain`
			WHERE
				`domain_id` = ?
			AND
				`subdomain_status` != 'delete'
			AND
				`subdomain_mount` RLIKE ?
			UNION
			SELECT
				`subdomain_alias_mount` AS `mount_point`
			FROM
				`subdomain_alias`
			WHERE
				`subdomain_alias_status` != 'delete'
			AND
				`alias_id` IN (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?)
			AND
				`subdomain_alias_mount` RLIKE ?
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
	if(ref $rdata ne 'HASH') {
		error($rdata);
		return 1;
	}

	$rdata;
}

1;
