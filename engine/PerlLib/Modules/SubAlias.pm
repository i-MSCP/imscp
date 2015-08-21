=head1 NAME

 Modules::SubAlias - i-MSCP SubAlias module

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

package Modules::SubAlias;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Execute;
use iMSCP::Dir;
use iMSCP::OpenSSL;
use Net::LibIDN qw/idn_to_unicode/;
use File::Spec;
use Scalar::Defer;
use parent 'Modules::Abstract';

# Lazy-load default phpini data (only once)
my $phpiniDefaultData = lazy {
	my $rows = iMSCP::Database->factory()->doQuery('name', 'SELECT * FROM config WHERE name LIKE ?', 'PHPINI%');
	(ref $rows eq 'HASH') or die($rows);
	$rows;
};

=head1 DESCRIPTION

 i-MSCP SubAlias module.

=head1 PUBLIC METHODS

=over 4

=item getType()

 Get module type

 Return string Module type

=cut

sub getType
{
	'Sub';
}

=item process($subAliasId)

 Process module

 Param int $subAliasId Subdomain alias unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
	my ($self, $subAliasId) = @_;

	my $rs = $self->_loadData($subAliasId);
	return $rs if $rs;

	my @sql;

	if($self->{'subdomain_alias_status'} ~~ [ 'toadd', 'tochange', 'toenable' ]) {
		$rs = $self->add();

		@sql = (
			'UPDATE subdomain_alias SET subdomain_alias_status = ? WHERE subdomain_alias_id = ?',
			($rs ? scalar getMessageByType('error') || 'Unknown error' : 'ok'),
			$subAliasId
		);
	} elsif($self->{'subdomain_alias_status'} eq 'todelete') {
		$rs = $self->delete();

		if($rs) {
			@sql = (
				'UPDATE subdomain_alias SET subdomain_alias_status = ? WHERE subdomain_alias_id = ?',
				scalar getMessageByType('error') || 'Unknown error',
				$subAliasId
			);
		} else {
			@sql = ('DELETE FROM subdomain_alias WHERE subdomain_alias_id = ?', $subAliasId);
		}
	} elsif($self->{'subdomain_alias_status'} eq 'todisable') {
		$rs = $self->disable();

		@sql = (
			'UPDATE subdomain_alias SET subdomain_alias_status = ? WHERE subdomain_alias_id = ?',
			($rs ? scalar getMessageByType('error') || 'Unknown error' : 'disabled'),
			$subAliasId
		);
	} elsif($self->{'subdomain_alias_status'} eq 'torestore') {
		$rs = $self->restore();

		@sql = (
			'UPDATE subdomain_alias SET subdomain_alias_status = ? WHERE subdomain_alias_id = ?',
			($rs ? scalar getMessageByType('error') || 'Unknown error' : 'ok'),
			$subAliasId
		);
	}

	my $rdata = iMSCP::Database->factory()->doQuery('dummy', @sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _loadData($subAliasId)

 Load data

 Param int $subAliasId Subdomain alias unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
	my ($self, $subAliasId) = @_;

	my $row = iMSCP::Database->factory()->doQuery(
		'subdomain_alias_id',
		"
			SELECT
				subdomain_alias.*, domain_name AS user_home, alias_name, domain_aliasses.external_mail, domain_admin_id,
				domain_php, domain_cgi, domain_traffic_limit, domain_mailacc_limit, domain_dns, domain.domain_id,
				web_folder_protection, ip_number, mail_on_domain
			FROM
				subdomain_alias
			INNER JOIN
				domain_aliasses USING (alias_id)
			INNER JOIN
				domain USING (domain_id)
			INNER JOIN
				server_ips ON (alias_ip_id = ip_id)
			LEFT JOIN (
				SELECT
					sub_id AS subdomain_alias_id, COUNT(sub_id) AS mail_on_domain
				FROM
					mail_users
				WHERE
					sub_id= ?
				AND
					mail_type LIKE 'alssub%'
				AND
					status <> 'todelete'
			) AS mail_count USING(subdomain_alias_id)
			WHERE
				subdomain_alias_id = ?
		",
		$subAliasId,
		$subAliasId
	);
	unless(ref $row eq 'HASH') {
		error($row);
		return 1;
	}

	unless(exists $row->{$subAliasId}) {
		error("Subdomain alias with ID $subAliasId has not been found or is in an inconsistent state");
		return 1;
	}

	%{$self} = (%{$self}, %{$row->{$subAliasId}});

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
		my $homeDir = File::Spec->canonpath("$main::imscpConfig{'USER_WEB_DIR'}/$self->{'user_home'}");
		my $webDir = File::Spec->canonpath("$homeDir/$self->{'subdomain_alias_mount'}");

		my $db = iMSCP::Database->factory();

		my $phpiniData = $db->doQuery('domain_id', 'SELECT * FROM php_ini WHERE domain_id = ?', $self->{'domain_id'});
		unless(ref $phpiniData eq 'HASH') {
			fatal($phpiniData);
		}

		my $certData = $db->doQuery(
			'domain_id',
			'SELECT * FROM ssl_certs WHERE domain_id = ? AND domain_type = ? AND status = ?',
			$self->{'subdomain_alias_id'},
			'alssub',
			'ok'
		);
		unless(ref $certData eq 'HASH') {
			fatal($certData);
		}

		my $haveCert = exists $certData->{$self->{'subdomain_alias_id'}} &&
			$self->isValidCertificate($self->{'subdomain_alias_name'} . '.' . $self->{'alias_name'});
		my $allowHSTS = $haveCert && $certData->{$self->{'subdomain_alias_id'}}->{'allow_hsts'} eq 'on';

		$self->{'httpd'} = {
			DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
			DOMAIN_NAME => $self->{'subdomain_alias_name'} . '.' . $self->{'alias_name'},
			DOMAIN_NAME_UNICODE => idn_to_unicode($self->{'subdomain_alias_name'} . '.' . $self->{'alias_name'}, 'UTF-8'),
			DOMAIN_IP => $self->{'ip_number'},
			DOMAIN_TYPE => 'alssub',
			PARENT_DOMAIN_NAME => $self->{'alias_name'},
			ROOT_DOMAIN_NAME => $self->{'user_home'},
			HOME_DIR => $homeDir,
			WEB_DIR => $webDir,
			MOUNT_POINT => $self->{'subdomain_alias_mount'},
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
			ALIAS => $userName . 'subals' . $self->{'subdomain_alias_id'},
			FORWARD => (defined $self->{'subdomain_alias_url_forward'} && $self->{'subdomain_alias_url_forward'} ne '')
				? $self->{'subdomain_alias_url_forward'} : 'no',
			FORWARD_TYPE => (defined $self->{'subdomain_alias_type_forward'} && $self->{'subdomain_alias_type_forward'} ne '')
				? $self->{'subdomain_alias_type_forward'} : '',
			DISABLE_FUNCTIONS => (exists $phpiniData->{$self->{'domain_id'}})
				? $phpiniData->{$self->{'domain_id'}}->{'disable_functions'}
				: $phpiniDefaultData->{'PHPINI_DISABLE_FUNCTIONS'}->{'value'},
			MAX_EXECUTION_TIME => (exists $phpiniData->{$self->{'domain_id'}})
				? $phpiniData->{$self->{'domain_id'}}->{'max_execution_time'}
				: $phpiniDefaultData->{'PHPINI_MAX_EXECUTION_TIME'}->{'value'},
			MAX_INPUT_TIME => (exists $phpiniData->{$self->{'domain_id'}})
				? $phpiniData->{$self->{'domain_id'}}->{'max_input_time'}
				: $phpiniDefaultData->{'PHPINI_MAX_INPUT_TIME'}->{'value'},
			MEMORY_LIMIT => (exists $phpiniData->{$self->{'domain_id'}})
				? $phpiniData->{$self->{'domain_id'}}->{'memory_limit'}
				: $phpiniDefaultData->{'PHPINI_MEMORY_LIMIT'}->{'value'},
			ERROR_REPORTING => (exists $phpiniData->{$self->{'domain_id'}})
				? $phpiniData->{$self->{'domain_id'}}->{'error_reporting'}
				: $phpiniDefaultData->{'PHPINI_ERROR_REPORTING'}->{'value'},
			DISPLAY_ERRORS => (exists $phpiniData->{$self->{'domain_id'}})
				? $phpiniData->{$self->{'domain_id'}}->{'display_errors'}
				: $phpiniDefaultData->{'PHPINI_DISPLAY_ERRORS'}->{'value'},
			POST_MAX_SIZE => (exists $phpiniData->{$self->{'domain_id'}})
				? $phpiniData->{$self->{'domain_id'}}->{'post_max_size'}
				: $phpiniDefaultData->{'PHPINI_POST_MAX_SIZE'}->{'value'},
			UPLOAD_MAX_FILESIZE => (exists $phpiniData->{$self->{'domain_id'}})
				? $phpiniData->{$self->{'domain_id'}}->{'upload_max_filesize'}
				: $phpiniDefaultData->{'PHPINI_UPLOAD_MAX_FILESIZE'}->{'value'},
			ALLOW_URL_FOPEN => (exists $phpiniData->{$self->{'domain_id'}})
				? $phpiniData->{$self->{'domain_id'}}->{'allow_url_fopen'}
				: $phpiniDefaultData->{'PHPINI_ALLOW_URL_FOPEN'}->{'value'},
			PHPINI_OPEN_BASEDIR => ($phpiniDefaultData->{'PHPINI_OPEN_BASEDIR'}->{'value'})
				? ':' . $phpiniDefaultData->{'PHPINI_OPEN_BASEDIR'}->{'value'} : ''
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
			DOMAIN_NAME => $self->{'subdomain_alias_name'} . '.' . $self->{'alias_name'},
			DOMAIN_TYPE => 'alssub',
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
			DOMAIN_NAME => $self->{'subdomain_alias_name'} . '.' . $self->{'alias_name'},
			PARENT_DOMAIN_NAME => $self->{'alias_name'},
			DOMAIN_IP => $self->{'ip_number'},
			USER_NAME => $userName . 'alssub' . $self->{'subdomain_alias_id'}
		};

		# Only no wildcard MX (NOT LIKE '*.%') must be add to existent subdomains
		if($self->{'external_mail'} ~~ [ 'domain', 'filter' ]) {
			$self->{'named'}->{'MAIL_ENABLED'} = 1;

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
				$self->{'alias_id'},
				'*.%',
				'MX',
				'ext_mail_feature'
			);
			unless(ref $rdata eq 'HASH') {
				fatal($rdata);
			}

			($self->{'named'}->{'MAIL_DATA'}->{$_} = $rdata->{$_}->{'domain_text'}) =~ s/(.*)\.$/$1./ for keys %{$rdata};
		} elsif($self->{'mail_on_domain'} || $self->{'domain_mailacc_limit'} >= 0) {
				$self->{'named'}->{'MAIL_ENABLED'} = 1;
				$self->{'named'}->{'MAIL'}->{1} = "10\tmail.$self->{'alias_name'}.";
		} else {
			$self->{'named'}->{'MAIL_ENABLED'} = 0;
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
		my $homeDir = File::Spec->canonpath("$main::imscpConfig{'USER_WEB_DIR'}/$self->{'user_home'}");
		my $webDir = File::Spec->canonpath("$homeDir/$self->{'subdomain_alias_mount'}");

		$self->{'packages'} = {
			DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
			ALIAS => $userName,
			DOMAIN_NAME => $self->{'subdomain_alias_name'} . '.' . $self->{'alias_name'},
			USER => $userName,
			GROUP => $groupName,
			HOME_DIR => $homeDir,
			WEB_DIR => $webDir,
			FORWARD => (defined $self->{'subdomain_alias_url_forward'} && $self->{'subdomain_alias_url_forward'} ne '')
				? $self->{'subdomain_alias_url_forward'} : 'no',
			FORWARD_TYPE => (defined $self->{'subdomain_alias_type_forward'} && $self->{'subdomain_alias_type_forward'} ne '')
				? $self->{'subdomain_alias_type_forward'} : '',
			WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'}
		};
	}

	%{$self->{'packages'}};
}

=item _sharedMountPoint()

 Does this subdomain alias share mount point with another domain?

 Return bool

=cut

sub _sharedMountPoint
{
	my $self = shift;

	my $regexp = "^$self->{'subdomain_alias_mount'}(/.*|\$)";

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
					subdomain_alias_id <> ?
				AND
					subdomain_alias_status != 'todelete'
				AND
					alias_id IN (SELECT alias_id FROM domain_aliasses WHERE domain_id = ?)
				AND
					subdomain_alias_mount RLIKE ?
			) AS tmp
		",
		undef,
		$self->{'domain_id'},
		$regexp,
		$self->{'domain_id'},
		$regexp,
		$self->{'subdomain_alias_id'},
		$self->{'domain_id'},
		$regexp
	);

	fatal($db->errstr) if $db->err;

	($nbSharedMountPoints || $self->{'subdomain_alias_mount'} eq '/');
}

=item isValidCertificate($subdomainAliasName)

 Does the SSL certificate which belongs to the subdomain alias is valid?

 Param string $subdomainAliasName Subdomain alias name
 Return bool TRUE if the domain SSL certificate is valid, FALSE otherwise

=cut

sub isValidCertificate
{
	my ($self, $subdomainAliasName) = @_;

	my $certFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$subdomainAliasName.pem";

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
