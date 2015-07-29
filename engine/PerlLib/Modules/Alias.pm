=head1 NAME

 Modules::Alias - i-MSCP domain alias module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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

package Modules::Alias;

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

=head1 DESCRIPTION

 i-MSCP domain alias module.

=head1 PUBLIC METHODS

=over 4

=item getType()

 Get module type

 Return string Module type

=cut

sub getType
{
	'Dmn';
}

=item process($aliasId)

 Process module

 Param int $aliasId Domain alias unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
	my ($self, $aliasId) = @_;

	my $rs = $self->_loadData($aliasId);
	return $rs if $rs;

	my @sql;

	if($self->{'alias_status'} ~~ [ 'toadd', 'tochange', 'toenable' ]) {
		$rs = $self->add();

		@sql = (
			"UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ?",
			($rs ? scalar getMessageByType('error') || 'Unknown error' : 'ok'),
			$aliasId
		);
	} elsif($self->{'alias_status'} eq 'todelete') {
		$rs = $self->delete();

		if($rs) {
			@sql = (
				"UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ?",
				scalar getMessageByType('error') || 'Unknown error',
				$aliasId
			);
		} else {
			@sql = ("DELETE FROM domain_aliasses WHERE alias_id = ?", $aliasId);
		}
	} elsif($self->{'alias_status'} eq 'todisable') {
		$rs = $self->disable();

		@sql = (
			"UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ?",
			($rs ? scalar getMessageByType('error') || 'Unknown error' : 'disabled'),
			$aliasId
		);
	} elsif($self->{'alias_status'} eq 'torestore') {
		$rs = $self->restore();

		@sql = (
			"UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ?",
			($rs ? scalar getMessageByType('error') || 'Unknown error' : 'ok'),
			$aliasId
		);
	}

	my $rdata = iMSCP::Database->factory()->doQuery('dummy', @sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$rs;
}

=item add()

 Add domain

 Return int 0 on success, other on failure

=cut

sub add
{
	my $self = shift;

	if($self->{'alias_status'} eq 'tochange') {
		my $db = iMSCP::Database->factory();

		# Sets the status of any subdomain that belongs to this domain alias to 'tochange'.
		# This is needed, else, the DNS resource records for the subdomains are not re-added in DNS zone files.
		# FIXME: This reflect a bad implementation in the way that entities are managed. This will be solved
		# in version 2.0.0.
		my $rs = $db->doQuery(
			'u',
			'UPDATE subdomain_alias SET subdomain_alias_status = ? WHERE alias_id = ? AND subdomain_alias_status <> ?',
			'tochange',
			$self->{'alias_id'},
			'todelete'
		);
		unless(ref $rs eq 'HASH') {
			error($rs);
			return 1;
		}

		$rs = $db->doQuery(
			'u',
			'UPDATE domain_dns SET domain_dns_status = ? WHERE alias_id = ? AND domain_dns_status <> ?',
			'tochange',
			$self->{'alias_id'},
			'todelete'
		);
		unless(ref $rs eq 'HASH') {
			error($rs);
			return 1;
		}
	}

	$self->SUPER::add();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _loadData($aliasId)

 Load data

 Param int $aliasId Domain Alias unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
	my ($self, $aliasId) = @_;

	my $rdata = iMSCP::Database->factory()->doQuery(
		'alias_id',
		"
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
		",
		$aliasId,
		$aliasId
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$aliasId}) {
		error("Domain with ID $aliasId has not been found or is in an inconsistent state");
		return 1;
	}

	%{$self} = (%{$self}, %{$rdata->{$aliasId}});

	0;
}

=item _getHttpdData($action)

 Data provider method for Httpd servers

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getHttpdData
{
	my ($self, $action) = @_;

	unless($self->{'httpd'}) {
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
			fatal($rdata);
		}

		my $phpiniData = $db->doQuery('domain_id', 'SELECT * FROM php_ini WHERE domain_id = ?', $self->{'domain_id'});
		unless (ref $phpiniData eq 'HASH') {
			fatal($phpiniData);
		}

		my $certData = $db->doQuery(
			'domain_id',
			'SELECT * FROM ssl_certs WHERE domain_id = ? AND domain_type = ? AND status = ?',
			$self->{'alias_id'},
			'als',
			'ok'
		);
		unless (ref $certData eq 'HASH') {
			fatal($certData);
		}

		my $haveCert =
			exists $certData->{$self->{'alias_id'}} &&
			$self->isValidCertificate($self->{'alias_name'});

		my $allowHSTS =
			$haveCert &&
			$certData->{$self->{'alias_id'}}{'allow_hsts'} eq 'on';

		$self->{'httpd'} = {
			DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
			DOMAIN_NAME => $self->{'alias_name'},
			DOMAIN_NAME_UNICODE => idn_to_unicode($self->{'alias_name'}, 'UTF-8'),
			DOMAIN_IP => $self->{'ip_number'},
			DOMAIN_TYPE => 'als',
			PARENT_DOMAIN_NAME => $self->{'alias_name'},
			ROOT_DOMAIN_NAME => $self->{'user_home'},
			HOME_DIR => $homeDir,
			WEB_DIR => $webDir,
			MOUNT_POINT => $self->{'alias_mount'},
			SHARED_MOUNT_POINT => $self->_sharedMountPoint(),
			PEAR_DIR => $main::imscpConfig{'PEAR_DIR'},
			TIMEZONE => $main::imscpConfig{'TIMEZONE'},
			USER => $userName,
			GROUP => $groupName,
			PHP_SUPPORT => $self->{'domain_php'},
			CGI_SUPPORT => $self->{'domain_cgi'},
			WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'},
			SSL_SUPPORT => $haveCert,
			HSTS_SUPPORT => $allowHSTS,
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
	}

	%{$self->{'httpd'}};
}

=item _getMtaData($action)

 Data provider method for MTA servers

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getMtaData
{
	my ($self, $action) = @_;

	unless($self->{'mta'}) {
		$self->{'mta'} = {
			DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
			DOMAIN_NAME => $self->{'alias_name'},
			DOMAIN_TYPE => $self->getType(),
			TYPE => 'vals_entry',
			EXTERNAL_MAIL => $self->{'external_mail'},
			MAIL_ENABLED => ($self->{'mail_on_domain'} || $self->{'domain_mailacc_limit'} >= 0) ? 1 : 0
		};
	}

	%{$self->{'mta'}};
}

=item _getNamedData($action)

 Data provider method for named servers

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getNamedData
{
	my ($self, $action) = @_;

	unless($self->{'named'}) {
		my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
			($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

		$self->{'named'} = {
			DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
			DOMAIN_NAME => $self->{'alias_name'},
			DOMAIN_IP => $self->{'ip_number'},
			USER_NAME => $userName . 'als' . $self->{'alias_id'},
			MAIL_ENABLED => (
				($self->{'mail_on_domain'} || $self->{'domain_mailacc_limit'} >= 0) &&
				($self->{'external_mail'} ~~ [ 'wildcard', 'off' ])
			) ? 1 : 0,
			SPF_RECORDS => []
		};

		if($action =~ /add/ && $self->{'external_mail'} ~~ [ 'domain', 'filter', 'wildcard' ]) {
			my $db = iMSCP::Database->factory();
			my $rdata = $db->doQuery(
				'domain_dns_id',
				'
					SELECT
						domain_dns_id, domain_dns, domain_text
					FROM
						domain_dns
					WHERE
						domain_id = ?
					AND
						alias_id = ?
					AND
						owned_by = ?
					AND
						domain_dns_status <> ?
				',
				$self->{'domain_id'},
				$self->{'alias_id'},
				'ext_mail_feature',
				'todelete'
			);
			unless(ref $rdata eq 'HASH') {
				fatal($rdata);
			}

			if(%{$rdata}) {
				my (@domainHosts, @wildcardHosts);

				# Add SPF records for external MX
				for(keys %{$rdata}) {
					(my $host = $rdata->{$_}->{'domain_text'}) =~ s/\d+\s+(.*)\.$/$1/;

					if((index($rdata->{$_}->{'domain_dns'}, '*') != 0)) {
						push @domainHosts, "a:$host";
					} else {
						push @wildcardHosts, "a:$host";
					}
				}

				if(@domainHosts) {
					push @{$self->{'named'}->{'SPF_RECORDS'}}, "@\tIN\tTXT\t\"v=spf1 mx @domainHosts ~all\""
				}

				if(@wildcardHosts) {
					push @{$self->{'named'}->{'SPF_RECORDS'}}, "*\tIN\tTXT\t\"v=spf1 mx @wildcardHosts ~all\""
				}
			}

			# We must trigger the SubAlias module whatever the number of entries - See #503
			$rdata = $db->doQuery(
				'dummy',
				'
					UPDATE
						subdomain_alias
					SET
						subdomain_alias_status = ?
					WHERE
						subdomain_alias_status <> ?
					AND
						alias_id = ?
				',
				'tochange',
				'todelete',
				$self->{'alias_id'}
			);
			unless(ref $rdata eq 'HASH') {
				fatal($rdata);
			}
		}
	}

	%{$self->{'named'}};
}

=item _getPackagesData($action)

 Data provider method for i-MSCP packages

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getPackagesData
{
	my ($self, $action) = @_;

	unless($self->{'packages'}) {
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
	}

	%{$self->{'packages'}};
}

=item _sharedMountPoint()

 Does this domain alias share mount point with another domain?

 Return bool

=cut

sub _sharedMountPoint
{
	my $self = shift;

	my $regexp = "^$self->{'alias_mount'}(/.*|\$)";

	my $db = iMSCP::Database->factory()->getRawDb();

	my ($nbSharedMountPoints) = $db->selectrow_array(
		"
			SELECT
				COUNT(mount_point) AS nb_mount_points
			FROM (
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
			) AS tmp
		",
		undef,
		$self->{'alias_id'},
		$self->{'domain_id'},
		$regexp,
		$self->{'domain_id'},
		$regexp,
		$self->{'domain_id'},
		$regexp
	);

	fatal($db->errstr) if $db->err;

	($nbSharedMountPoints || $self->{'alias_mount'} eq '/');
}

=item isValidCertificate($domainAliasName)

 Does the SSL certificate which belongs to that the domain alias is valid?

 Param string $domainAliasName Domain alias name
 Return bool TRUE if the domain SSL certificate is valid, FALSE otherwise

=cut

sub isValidCertificate
{
	my ($self, $domainAliasName) = @_;

	my $certFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$domainAliasName.pem";

	my $openSSL = iMSCP::OpenSSL->new(
		'private_key_container_path' => $certFile,
		'certificate_container_path' => $certFile,
		'ca_bundle_container_path' => $certFile
	);

	! $openSSL->validateCertificateChain();
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
