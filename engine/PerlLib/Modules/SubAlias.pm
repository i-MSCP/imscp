=head1 NAME

 Modules::SubAlias - i-MSCP SubAlias module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by internet Multi Server Control Panel
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
use File::Spec;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::OpenSSL;
use Net::LibIDN qw/idn_to_unicode/;
use Servers::httpd;
use parent 'Modules::Abstract';

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

    my $rs = $self->_loadData( $subAliasId );
    return $rs if $rs;

    my @sql;
    if ($self->{'subdomain_alias_status'} =~ /^to(?:add|change|enable)$/) {
        $rs = $self->add();
        @sql = (
            'UPDATE subdomain_alias SET subdomain_alias_status = ? WHERE subdomain_alias_id = ?',
            ($rs ? scalar getMessageByType( 'error' ) || 'Unknown error' : 'ok'), $subAliasId
        );
    } elsif ($self->{'subdomain_alias_status'} eq 'todelete') {
        $rs = $self->delete();
        if ($rs) {
            @sql = (
                'UPDATE subdomain_alias SET subdomain_alias_status = ? WHERE subdomain_alias_id = ?',
                scalar getMessageByType( 'error' ) || 'Unknown error', $subAliasId
            );
        } else {
            @sql = ('DELETE FROM subdomain_alias WHERE subdomain_alias_id = ?', $subAliasId);
        }
    } elsif ($self->{'subdomain_alias_status'} eq 'todisable') {
        $rs = $self->disable();
        @sql = (
            'UPDATE subdomain_alias SET subdomain_alias_status = ? WHERE subdomain_alias_id = ?',
            ($rs ? scalar getMessageByType( 'error' ) || 'Unknown error' : 'disabled'), $subAliasId
        );
    } elsif ($self->{'subdomain_alias_status'} eq 'torestore') {
        $rs = $self->restore();
        @sql = (
            'UPDATE subdomain_alias SET subdomain_alias_status = ? WHERE subdomain_alias_id = ?',
            ($rs ? scalar getMessageByType( 'error' ) || 'Unknown error' : 'ok'), $subAliasId
        );
    }

    my $rdata = iMSCP::Database->factory()->doQuery( 'dummy', @sql );
    unless (ref $rdata eq 'HASH') {
        error( $rdata );
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

    my $rdata = iMSCP::Database->factory()->doQuery(
        'subdomain_alias_id',
        "
            SELECT sub.*, domain_name AS user_home, alias_name, alias.external_mail, domain_admin_id, domain_php, domain_cgi,
                domain_traffic_limit, domain_mailacc_limit, domain_dns, domain.domain_id, web_folder_protection,
                ips.ip_number, mail_count.mail_on_domain
            FROM subdomain_alias AS sub
            INNER JOIN domain_aliasses AS alias ON (sub.alias_id = alias.alias_id)
            INNER JOIN domain ON (alias.domain_id = domain.domain_id)
            INNER JOIN server_ips AS ips ON (alias.alias_ip_id = ips.ip_id)
            LEFT JOIN (
                SELECT sub_id AS id, COUNT(sub_id) AS mail_on_domain FROM mail_users
                WHERE sub_id= ? AND mail_type IN ('alssub_mail', 'alssub_forward', 'alssub_mail,alssub_forward', 'alssub_catchall')
                GROUP BY sub_id
            ) AS mail_count ON (sub.subdomain_alias_id = mail_count.id)
            WHERE sub.subdomain_alias_id = ?
        ",
        $subAliasId, $subAliasId
    );
    unless (ref $rdata eq 'HASH') {
        error( $rdata );
        return 1;
    }

    unless ($rdata->{$subAliasId}) {
        error( sprintf( 'Subdomain alias with ID %s has not been found or is in an inconsistent state', $subAliasId ) );
        return 1;
    }

    %{$self} = (%{$self}, %{$rdata->{$subAliasId}});
    0;
}

=item _getHttpdData($action)

 Data provider method for Httpd servers

 Param string $action Action
 Return hash Hash containing module data, die on failure

=cut

sub _getHttpdData
{
    my ($self, $action) = @_;

    return %{$self->{'httpd'}} if $self->{'httpd'};

    my $httpd = Servers::httpd->factory();
    my $groupName = my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.
        ($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});
    my $homeDir = File::Spec->canonpath( "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'user_home'}" );
    my $webDir = File::Spec->canonpath( "$homeDir/$self->{'subdomain_alias_mount'}" );
    my $db = iMSCP::Database->factory();
    my $confLevel = $httpd->{'phpConfig'}->{'PHP_CONFIG_LEVEL'};

    if ($confLevel eq 'per_user') {
        $confLevel = 'dmn';
    } elsif ($confLevel eq 'per_domain') {
        $confLevel = 'als';
    } else {
        $confLevel = 'subals';
    }

    my $phpiniMatchId = $confLevel eq 'dmn' ? $self->{'domain_id'} : (
                $confLevel eq 'als' ? $self->{'alias_id'} : $self->{'subdomain_alias_id'}
        );
    my $phpini = $db->doQuery(
        'domain_id', 'SELECT * FROM php_ini WHERE domain_id = ? AND domain_type = ?', $phpiniMatchId, $confLevel
    );
    ref $phpini eq 'HASH' or die( $phpini );

    my $certData = $db->doQuery(
        'domain_id', 'SELECT * FROM ssl_certs WHERE domain_id = ? AND domain_type = ? AND status = ?',
        $self->{'subdomain_alias_id'}, 'alssub', 'ok'
    );
    ref $certData eq 'HASH' or die( $certData );

    my $haveCert = $certData->{$self->{'subdomain_alias_id'}}
        && $self->isValidCertificate( $self->{'subdomain_alias_name'}.'.'.$self->{'alias_name'} );
    my $allowHSTS = $haveCert && $certData->{$self->{'subdomain_alias_id'}}->{'allow_hsts'} eq 'on';
    my $hstsMaxAge = $allowHSTS ? $certData->{$self->{'subdomain_alias_id'}}->{'hsts_max_age'} : '';
    my $hstsIncludeSubDomains = (
        $allowHSTS && $certData->{$self->{'subdomain_alias_id'}}->{'hsts_include_subdomains'} eq 'on'
    ) ? '; includeSubDomains' : '';

    $self->{'httpd'} = {
        DOMAIN_ADMIN_ID         => $self->{'domain_admin_id'},
        DOMAIN_NAME             => $self->{'subdomain_alias_name'}.'.'.$self->{'alias_name'},
        DOMAIN_NAME_UNICODE     => idn_to_unicode( $self->{'subdomain_alias_name'}.'.'.$self->{'alias_name'}, 'utf-8' ),
        DOMAIN_IP               => $self->{'ip_number'},
        DOMAIN_TYPE             => 'alssub',
        PARENT_DOMAIN_NAME      => $self->{'alias_name'},
        ROOT_DOMAIN_NAME        => $self->{'user_home'},
        HOME_DIR                => $homeDir,
        WEB_DIR                 => $webDir,
        MOUNT_POINT             => $self->{'subdomain_alias_mount'},
        SHARED_MOUNT_POINT      => $self->_sharedMountPoint(),
        PEAR_DIR                => $httpd->{'phpConfig'}->{'PHP_PEAR_DIR'},
        TIMEZONE                => $main::imscpConfig{'TIMEZONE'},
        USER                    => $userName,
        GROUP                   => $groupName,
        PHP_SUPPORT             => $self->{'domain_php'},
        CGI_SUPPORT             => $self->{'domain_cgi'},
        WEB_FOLDER_PROTECTION   => $self->{'web_folder_protection'},
        SSL_SUPPORT             => $haveCert,
        HSTS_SUPPORT            => $allowHSTS,
        HSTS_MAX_AGE            => $hstsMaxAge,
        HSTS_INCLUDE_SUBDOMAINS => $hstsIncludeSubDomains,
        BWLIMIT                 => $self->{'domain_traffic_limit'},
        ALIAS                   => $userName.'subals'.$self->{'subdomain_alias_id'},
        FORWARD                 => $self->{'subdomain_alias_url_forward'} || 'no',
        FORWARD_TYPE            => $self->{'subdomain_alias_type_forward'} || '',
        DISABLE_FUNCTIONS       => $phpini->{$phpiniMatchId}->{'disable_functions'} //
            'exec,passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system',
        MAX_EXECUTION_TIME      => $phpini->{$phpiniMatchId}->{'max_execution_time'} // 30,
        MAX_INPUT_TIME          => $phpini->{$phpiniMatchId}->{'max_input_time'} // 60,
        MEMORY_LIMIT            => $phpini->{$phpiniMatchId}->{'memory_limit'} // 128,
        ERROR_REPORTING         => $phpini->{$phpiniMatchId}->{'error_reporting'} || 'E_ALL & ~E_DEPRECATED & ~E_STRICT',
        DISPLAY_ERRORS          => $phpini->{$phpiniMatchId}->{'display_errors'} || 'off',
        POST_MAX_SIZE           => $phpini->{$phpiniMatchId}->{'post_max_size'} // 8,
        UPLOAD_MAX_FILESIZE     => $phpini->{$phpiniMatchId}->{'upload_max_filesize'} // 2,
        ALLOW_URL_FOPEN         => $phpini->{$phpiniMatchId}->{'allow_url_fopen'} || 'off',
        PHP_FPM_LISTEN_PORT     => ($phpini->{$phpiniMatchId}->{'id'} // 0) - 1
    };
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

    return %{$self->{'mta'}} if $self->{'mta'};

    $self->{'mta'} = {
        DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
        DOMAIN_NAME     => $self->{'subdomain_alias_name'}.'.'.$self->{'alias_name'},
        DOMAIN_TYPE     => $self->getType(),
        TYPE            => 'valssub_entry',
        EXTERNAL_MAIL   => $self->{'external_mail'},
        MAIL_ENABLED    => ($self->{'mail_on_domain'} || $self->{'domain_mailacc_limit'} >= 0) ? 1 : 0
    };
    %{$self->{'mta'}};
}

=item _getNamedData($action)

 Data provider method for named servers

 Param string $action Action
 Return hash Hash containing module data, die on failure

=cut

sub _getNamedData
{
    my ($self, $action) = @_;

    return %{$self->{'named'}} if $self->{'named'};

    my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.
        ($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

    $self->{'named'} = {
        DOMAIN_ADMIN_ID    => $self->{'domain_admin_id'},
        DOMAIN_NAME        => $self->{'subdomain_alias_name'}.'.'.$self->{'alias_name'},
        PARENT_DOMAIN_NAME => $self->{'alias_name'},
        DOMAIN_IP          => $self->{'ip_number'},
        USER_NAME          => $userName.'alssub'.$self->{'subdomain_alias_id'}
    };

    # Only no wildcard MX (NOT LIKE '*.%') must be add to existent subdomains
    if ($self->{'external_mail'} =~ /^(?:domain|filter)$/) {
        $self->{'named'}->{'MAIL_ENABLED'} = 1;

        my $rdata = iMSCP::Database->factory()->doQuery(
            'domain_dns_id',
            '
                SELECT domain_dns_id, domain_text FROM domain_dns
                WHERE domain_id = ? AND alias_id = ? AND domain_dns NOT LIKE ? AND domain_type = ? AND owned_by = ?
            ',
            $self->{'domain_id'}, $self->{'alias_id'}, '*.%', 'MX', 'ext_mail_feature'
        );
        ref $rdata eq 'HASH' or die( $rdata );

        ($self->{'named'}->{'MAIL_DATA'}->{$_} = $rdata->{$_}->{'domain_text'}) =~ s/(.*)\.$/$1./ for keys %{$rdata};
    } elsif ($self->{'mail_on_domain'} || $self->{'domain_mailacc_limit'} >= 0) {
        $self->{'named'}->{'MAIL_ENABLED'} = 1;
        $self->{'named'}->{'MAIL'}->{1} = "10\tmail.$self->{'alias_name'}.";
    } else {
        $self->{'named'}->{'MAIL_ENABLED'} = 0;
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

    return %{$self->{'packages'}} if $self->{'packages'};

    my $userName = my $groupName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.
        ($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});
    my $homeDir = File::Spec->canonpath( "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'user_home'}" );
    my $webDir = File::Spec->canonpath( "$homeDir/$self->{'subdomain_alias_mount'}" );

    $self->{'packages'} = {
        DOMAIN_ADMIN_ID       => $self->{'domain_admin_id'},
        ALIAS                 => $userName,
        DOMAIN_NAME           => $self->{'subdomain_alias_name'}.'.'.$self->{'alias_name'},
        USER                  => $userName,
        GROUP                 => $groupName,
        HOME_DIR              => $homeDir,
        WEB_DIR               => $webDir,
        FORWARD               => $self->{'subdomain_alias_url_forward'} || 'no',
        FORWARD_TYPE          => $self->{'subdomain_alias_type_forward'} || '',
        WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'}
    };
    %{$self->{'packages'}};
}

=item _sharedMountPoint()

 Does this subdomain alias share mount point with another domain?

 Return bool, die on failure

=cut

sub _sharedMountPoint
{
    my $self = shift;

    my $regexp = "^$self->{'subdomain_alias_mount'}(/.*|\$)";
    my $db = iMSCP::Database->factory()->getRawDb();
    my ($nbSharedMountPoints) = $db->selectrow_array(
        "
            SELECT COUNT(mount_point) AS nb_mount_points FROM (
                SELECT alias_mount AS mount_point FROM domain_aliasses
                WHERE domain_id = ? AND alias_status NOT IN ('todelete', 'ordered') AND alias_mount RLIKE ?
                UNION
                SELECT subdomain_mount AS mount_point FROM subdomain
                WHERE domain_id = ? AND subdomain_status != 'todelete' AND subdomain_mount RLIKE ?
                UNION
                SELECT subdomain_alias_mount AS mount_point FROM subdomain_alias
                WHERE subdomain_alias_id <> ? AND subdomain_alias_status != 'todelete'
                AND alias_id IN (SELECT alias_id FROM domain_aliasses WHERE domain_id = ?)
                AND subdomain_alias_mount RLIKE ?
            ) AS tmp
        ",
        undef,
        $self->{'domain_id'}, $regexp, $self->{'domain_id'}, $regexp, $self->{'subdomain_alias_id'},
        $self->{'domain_id'}, $regexp
    );

    die( $db->errstr ) if $db->err;
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
        'ca_bundle_container_path'   => $certFile
    );
    !$openSSL->validateCertificateChain();
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
