=head1 NAME

 Modules::Subdomain - i-MSCP Subdomain module

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

package Modules::Subdomain;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Execute;
use iMSCP::Dir;
use iMSCP::OpenSSL;
use Servers::httpd;
use File::Spec;
use Net::LibIDN qw/idn_to_unicode/;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP Subdomain module.

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

=item process($subdomainId)

 Process module

 Param int $subdomainId Subdomain unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
    my ($self, $subdomainId) = @_;

    my $rs = $self->_loadData( $subdomainId );
    return $rs if $rs;

    my @sql;
    if (grep($_ eq $self->{'subdomain_status'}, ( 'toadd', 'tochange', 'toenable' ))) {
        $rs = $self->add();
        @sql = (
            'UPDATE subdomain SET subdomain_status = ? WHERE subdomain_id = ?',
            ($rs ? scalar getMessageByType( 'error' ) || 'Unknown error' : 'ok'), $subdomainId
        );
    } elsif ($self->{'subdomain_status'} eq 'todelete') {
        $rs = $self->delete();
        if ($rs) {
            @sql = (
                'UPDATE subdomain SET subdomain_status = ? WHERE subdomain_id = ?',
                scalar getMessageByType( 'error' ) || 'Unknown error', $subdomainId
            );
        } else {
            @sql = ('DELETE FROM subdomain WHERE subdomain_id = ?', $subdomainId);
        }
    } elsif ($self->{'subdomain_status'} eq 'todisable') {
        $rs = $self->disable();
        @sql = (
            'UPDATE subdomain SET subdomain_status = ? WHERE subdomain_id = ?',
            ($rs ? scalar getMessageByType( 'error' ) || 'Unknown error' : 'disabled'), $subdomainId
        );
    } elsif ($self->{'subdomain_status'} eq 'torestore') {
        $rs = $self->restore();
        @sql = (
            'UPDATE subdomain SET subdomain_status = ? WHERE subdomain_id = ?',
            ($rs ? scalar getMessageByType( 'error' ) || 'Unknown error' : 'ok'), $subdomainId
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

=item _loadData($subdomainId)

 Load data

 Param int $subdomainId Subdomain unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
    my ($self, $subdomainId) = @_;

    my $rdata = iMSCP::Database->factory()->doQuery(
        'subdomain_id',
        "
            SELECT sub.*, domain_name AS user_home, domain_admin_id, domain_php, domain_cgi, domain_traffic_limit,
                domain_mailacc_limit, domain_dns, external_mail, web_folder_protection, ips.ip_number,
                mail_count.mail_on_domain
            FROM subdomain AS sub
            INNER JOIN domain ON (sub.domain_id = domain.domain_id)
            INNER JOIN server_ips AS ips ON (domain.domain_ip_id = ips.ip_id)
            LEFT JOIN (
                SELECT sub_id AS id, COUNT( sub_id ) AS mail_on_domain FROM mail_users
                WHERE sub_id= ? AND mail_type IN ('subdom_mail', 'subdom_forward', 'subdom_mail,subdom_forward', 'subdom_catchall')
                GROUP BY sub_id
            ) AS mail_count ON (sub.subdomain_id = mail_count.id)
            WHERE sub.subdomain_id = ?
        ",
        $subdomainId,
        $subdomainId
    );
    unless (ref $rdata eq 'HASH') {
        error( $rdata );
        return 1;
    }

    unless ($rdata->{$subdomainId}) {
        error( sprintf( 'Subdomain with ID %s has not been found or is in an inconsistent state', $subdomainId ) );
        return 1;
    }

    %{$self} = (%{$self}, %{$rdata->{$subdomainId}});
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

    my $groupName = my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.
        ($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});
    my $homeDir = File::Spec->canonpath( "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'user_home'}" );
    my $webDir = File::Spec->canonpath( "$homeDir/$self->{'subdomain_mount'}" );
    my $db = iMSCP::Database->factory();
    my $confLevel = ($main::imscpConfig{'HTTPD_SERVER'} eq 'apache_php_fpm')
        ? Servers::httpd->factory()->{'phpfpmConfig'}->{'PHP_FPM_POOLS_LEVEL'}
        : Servers::httpd->factory()->{'config'}->{'INI_LEVEL'};
    $confLevel = grep($_ eq $confLevel, ( 'per_user', 'per_domain' )) ? 'dmn' : 'sub';

    my $phpiniMatchId = $confLevel eq 'dmn' ? $self->{'domain_id'} : $self->{'subdomain_id'};
    my $phpini = $db->doQuery(
        'domain_id', 'SELECT * FROM php_ini WHERE domain_id = ? AND domain_type = ?', $phpiniMatchId, $confLevel
    );
    ref $phpini eq 'HASH' or die( $phpini );

    my $certData = $db->doQuery(
        'domain_id', 'SELECT * FROM ssl_certs WHERE domain_id = ? AND domain_type = ? AND status = ?',
        $self->{'subdomain_id'}, 'sub', 'ok'
    );
    ref $certData eq 'HASH' or die( $certData );

    my $haveCert = $certData->{$self->{'subdomain_id'}}
        && $self->isValidCertificate( $self->{'subdomain_name'}.'.'.$self->{'user_home'} );

    $self->{'httpd'} = {
        DOMAIN_ADMIN_ID       => $self->{'domain_admin_id'},
        DOMAIN_NAME           => $self->{'subdomain_name'}.'.'.$self->{'user_home'},
        DOMAIN_NAME_UNICODE   => idn_to_unicode( $self->{'subdomain_name'}.'.'.$self->{'user_home'}, 'UTF-8' ),
        DOMAIN_IP             => $self->{'ip_number'},
        DOMAIN_TYPE           => 'sub',
        PARENT_DOMAIN_NAME    => $self->{'user_home'},
        ROOT_DOMAIN_NAME      => $self->{'user_home'},
        HOME_DIR              => $homeDir,
        WEB_DIR               => $webDir,
        MOUNT_POINT           => $self->{'subdomain_mount'},
        SHARED_MOUNT_POINT    => $self->_sharedMountPoint(),
        PEAR_DIR              => $main::imscpConfig{'PEAR_DIR'},
        TIMEZONE              => $main::imscpConfig{'TIMEZONE'},
        USER                  => $userName,
        GROUP                 => $groupName,
        PHP_SUPPORT           => $self->{'domain_php'},
        CGI_SUPPORT           => $self->{'domain_cgi'},
        WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'},
        SSL_SUPPORT           => $haveCert,
        BWLIMIT               => $self->{'domain_traffic_limit'},
        ALIAS                 => $userName.'sub'.$self->{'subdomain_id'},
        FORWARD               => $self->{'subdomain_url_forward'} || 'no',
        DISABLE_FUNCTIONS     => $phpini->{$phpiniMatchId}->{'disable_functions'} //
            'exec,passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system',
        MAX_EXECUTION_TIME    => $phpini->{$phpiniMatchId}->{'max_execution_time'} // 30,
        MAX_INPUT_TIME        => $phpini->{$phpiniMatchId}->{'max_input_time'} // 60,
        MEMORY_LIMIT          => $phpini->{$phpiniMatchId}->{'memory_limit'} // 128,
        ERROR_REPORTING       => $phpini->{$phpiniMatchId}->{'error_reporting'} || 'E_ALL & ~E_DEPRECATED & ~E_STRICT',
        DISPLAY_ERRORS        => $phpini->{$phpiniMatchId}->{'display_errors'} || 'off',
        POST_MAX_SIZE         => $phpini->{$phpiniMatchId}->{'post_max_size'} // 8,
        UPLOAD_MAX_FILESIZE   => $phpini->{$phpiniMatchId}->{'upload_max_filesize'} // 2,
        ALLOW_URL_FOPEN       => $phpini->{$phpiniMatchId}->{'allow_url_fopen'} || 'off',
        PHP_FPM_LISTEN_PORT   => ($phpini->{$phpiniMatchId}->{'id'} // 0) - 1
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
        DOMAIN_NAME     => $self->{'subdomain_name'}.'.'.$self->{'user_home'},
        DOMAIN_TYPE     => $self->getType(),
        TYPE            => 'vsub_entry',
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
        DOMAIN_NAME        => $self->{'subdomain_name'}.'.'.$self->{'user_home'},
        PARENT_DOMAIN_NAME => $self->{'user_home'},
        DOMAIN_IP          => $self->{'ip_number'},
        USER_NAME          => $userName.'sub'.$self->{'subdomain_id'}
    };

    if (grep($_ eq $self->{'external_mail'}, ( 'domain', 'filter' ))) {
        $self->{'named'}->{'MAIL_ENABLED'} = 1;

        # only no wildcard MX (NOT LIKE '*.%') must be add to existent subdomains
        my $rdata = iMSCP::Database->factory()->doQuery(
            'domain_dns_id',
            '
                SELECT domain_dns_id, domain_text FROM domain_dns
                WHERE domain_id = ? AND alias_id = ? AND domain_dns NOT LIKE ? AND domain_type = ? AND owned_by = ?
            ',
            $self->{'domain_id'}, 0, '*.%', 'MX', 'ext_mail_feature'
        );
        ref $rdata eq 'HASH' or die( $rdata );
        ($self->{'named'}->{'MAIL_DATA'}->{$_} = $rdata->{$_}->{'domain_text'}) =~ s/(.*)\.$/$1./ for keys %{$rdata};
    } elsif ($self->{'mail_on_domain'} || $self->{'domain_mailacc_limit'} >= 0) {
        $self->{'named'}->{'MAIL_ENABLED'} = 1;
        $self->{'named'}->{'MAIL_DATA'}->{1} = "10\tmail.$self->{'user_home'}.";
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
    my $webDir = File::Spec->canonpath( "$homeDir/$self->{'subdomain_mount'}" );

    $self->{'packages'} = {
        DOMAIN_ADMIN_ID       => $self->{'domain_admin_id'},
        ALIAS                 => $userName,
        DOMAIN_NAME           => $self->{'subdomain_name'}.'.'.$self->{'user_home'},
        USER                  => $userName,
        GROUP                 => $groupName,
        HOME_DIR              => $homeDir,
        WEB_DIR               => $webDir,
        FORWARD               => $self->{'subdomain_url_forward'} || 'no',
        WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'}
    };
    %{$self->{'packages'}};
}

=item _sharedMountPoint()

 Does this subdomain share mount point with another domain?

 Return bool, die on failure

=cut

sub _sharedMountPoint
{
    my $self = shift;

    my $regexp = "^$self->{'subdomain_mount'}(/.*|\$)";
    my $db = iMSCP::Database->factory()->getRawDb();
    my ($nbSharedMountPoints) = $db->selectrow_array(
        "
            SELECT COUNT(mount_point) AS nb_mount_points FROM (
                SELECT alias_mount AS mount_point FROM domain_aliasses
                WHERE domain_id = ? AND alias_status NOT IN ('todelete', 'ordered') AND alias_mount RLIKE ?
                UNION
                SELECT subdomain_mount AS mount_point FROM subdomain
                WHERE subdomain_id <> ? AND domain_id = ? AND subdomain_status <> 'todelete' AND subdomain_mount RLIKE ?
                UNION
                SELECT subdomain_alias_mount AS mount_point FROM subdomain_alias
                WHERE subdomain_alias_status <> 'todelete'
                AND alias_id IN (SELECT alias_id FROM domain_aliasses WHERE domain_id = ?)
                AND subdomain_alias_mount RLIKE ?
            ) AS tmp
        ",
        undef, $self->{'domain_id'}, $regexp, $self->{'subdomain_id'}, $self->{'domain_id'}, $regexp,
        $self->{'domain_id'}, $regexp
    );

    die( $db->errstr ) if $db->err;
    ($nbSharedMountPoints || $self->{'subdomain_mount'} eq '/');
}

=item isValidCertificate($subdomainName)

 Does the SSL certificate which belong to the subdomain is valid?

 Param string $subdomainName Subdomain name
 Return bool TRUE if the domain SSL certificate is valid, FALSE otherwise

=cut

sub isValidCertificate
{
    my ($self, $subdomainName) = @_;

    my $certFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$subdomainName.pem";
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
