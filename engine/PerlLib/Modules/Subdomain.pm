=head1 NAME

 Modules::Subdomain - i-MSCP Subdomain module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
use File::Spec;
use iMSCP::Debug qw/ debug error getLastError warning /;
use Net::LibIDN qw/ idn_to_unicode /;
use Servers::httpd;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP Subdomain module.

=head1 PUBLIC METHODS

=over 4

=item getType( )

 Get module type

 Return string Module type

=cut

sub getType
{
    'Sub';
}

=item process( $subdomainId )

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
    if ( $self->{'subdomain_status'} =~ /^to(?:add|change|enable)$/ ) {
        $rs = $self->add();
        @sql = ( 'UPDATE subdomain SET subdomain_status = ? WHERE subdomain_id = ?', undef,
            ( $rs ? getLastError( 'error' ) || 'Unknown error' : 'ok' ), $subdomainId );
    } elsif ( $self->{'subdomain_status'} eq 'todelete' ) {
        $rs = $self->delete();
        @sql = $rs
            ? ( 'UPDATE subdomain SET subdomain_status = ? WHERE subdomain_id = ?', undef,
                ( getLastError( 'error' ) || 'Unknown error' ), $subdomainId )
            : ( 'DELETE FROM subdomain WHERE subdomain_id = ?', undef, $subdomainId );
    } elsif ( $self->{'subdomain_status'} eq 'todisable' ) {
        $rs = $self->disable();
        @sql = ( 'UPDATE subdomain SET subdomain_status = ? WHERE subdomain_id = ?', undef,
            ( $rs ? getLastError( 'error' ) || 'Unknown error' : 'disabled' ), $subdomainId );
    } elsif ( $self->{'subdomain_status'} eq 'torestore' ) {
        $rs = $self->restore();
        @sql = ( 'UPDATE subdomain SET subdomain_status = ? WHERE subdomain_id = ?', undef,
            ( $rs ? getLastError( 'error' ) || 'Unknown error' : 'ok' ), $subdomainId );
    } else {
        warning(
            sprintf( 'Unknown action (%s) for subdomain (ID %d)', $self->{'subdomain_alias_status'}, $subdomainId )
        );
        return 0;
    }

    local $@;
    eval {
        local $self->{'_dbh'}->{'RaiseError'} = 1;
        $self->{'_dbh'}->do( @sql );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _loadData( $subdomainId )

 Load data

 Param int $subdomainId Subdomain unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
    my ($self, $subdomainId) = @_;

    local $@;
    eval {
        local $self->{'_dbh'}->{'RaiseError'} = 1;
        my $row = $self->{'_dbh'}->selectrow_hashref(
            "
                SELECT t1.*,
                    t2.domain_name AS user_home, t2.domain_admin_id, t2.domain_mailacc_limit, t2.domain_php, t2.domain_cgi,
                    t2.external_mail, t2.web_folder_protection,
                    IFNULL(t3.ip_number, '0.0.0.0') AS ip_number,
                    t4.private_key, t4.certificate, t4.ca_bundle, t4.allow_hsts, t4.hsts_max_age,
                    t4.hsts_include_subdomains,
                    t5.mail_on_domain
                FROM subdomain AS t1
                JOIN domain AS t2 USING(domain_id)
                JOIN server_ips AS t3 ON (t3.ip_id = t2.domain_ip_id)
                LEFT JOIN ssl_certs AS t4 ON(t4.domain_id = t1.subdomain_id AND t4.domain_type = 'sub' AND t4.status = 'ok')
                LEFT JOIN (
                    SELECT sub_id, COUNT(sub_id) AS mail_on_domain
                    FROM mail_users
                    WHERE mail_type LIKE 'subdom\\_%'
                    GROUP BY sub_id
                ) AS t5 ON (t5.sub_id = t1.subdomain_id)
                WHERE t1.subdomain_id = ?
            ",
            undef,
            $subdomainId
        );
        $row or die( sprintf( 'Data not found for subdomain (ID %d)', $subdomainId ));
        %{$self} = ( %{$self}, %{$row} );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _getData( $action )

 Data provider method for servers and packages

 Param string $action Action
 Return hashref Reference to a hash containing data, die on failure

=cut

sub _getData
{
    my ($self, $action) = @_;

    $self->{'_data'} = do {
        my $httpd = Servers::httpd->factory();
        my $groupName = my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}
            . ( $main::imscpConfig{'SYSTEM_USER_MIN_UID'}+$self->{'domain_admin_id'} );
        my $homeDir = File::Spec->canonpath( "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'user_home'}" );
        my $webDir = File::Spec->canonpath( "$homeDir/$self->{'subdomain_mount'}" );
        my $documentRoot = File::Spec->canonpath( "$webDir/$self->{'subdomain_document_root'}" );
        my $confLevel = $httpd->{'phpConfig'}->{'PHP_CONFIG_LEVEL'};
        $confLevel = $confLevel =~ /^per_(?:user|domain)$/ ? 'dmn' : 'sub';

        local $self->{'_dbh'}->{'RaiseError'} = 1;
        my $phpini = $self->{'_dbh'}->selectrow_hashref(
            'SELECT * FROM php_ini WHERE domain_id = ? AND domain_type = ?',
            undef, ( $confLevel eq 'dmn' ? $self->{'domain_id'} : $self->{'subdomain_id'} ), $confLevel
        ) || {};

        my $haveCert = (
            defined $self->{'certificate'}
                && -f "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$self->{'subdomain_name'}.$self->{'user_home'}.pem"
        );
        my $allowHSTS = ( $haveCert && $self->{'allow_hsts'} eq 'on' );
        my $hstsMaxAge = ( $allowHSTS ) ? $self->{'hsts_max_age'} : 0;
        my $hstsIncludeSubDomains = ( $allowHSTS && $self->{'hsts_include_subdomains'} eq 'on' )
            ? '; includeSubDomains' : ( ( $allowHSTS ) ? '' : '; includeSubDomains' );

        {
            ACTION                  => $action,
            STATUS                  => $self->{'subdomain_status'},
            BASE_SERVER_VHOST       => $main::imscpConfig{'BASE_SERVER_VHOST'},
            BASE_SERVER_IP          => $main::imscpConfig{'BASE_SERVER_IP'},
            BASE_SERVER_PUBLIC_IP   => $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'},
            DOMAIN_ADMIN_ID         => $self->{'domain_admin_id'},
            DOMAIN_NAME             => $self->{'subdomain_name'} . '.' . $self->{'user_home'},
            DOMAIN_NAME_UNICODE     =>
            idn_to_unicode( $self->{'subdomain_name'} . '.' . $self->{'user_home'}, 'utf-8' ),
            DOMAIN_IP               => $self->{'ip_number'},
            DOMAIN_TYPE             => 'sub',
            PARENT_DOMAIN_NAME      => $self->{'user_home'},
            ROOT_DOMAIN_NAME        => $self->{'user_home'},
            HOME_DIR                => $homeDir,
            WEB_DIR                 => $webDir,
            MOUNT_POINT             => $self->{'subdomain_mount'},
            DOCUMENT_ROOT           => $documentRoot,
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
            ALIAS                   => 'sub' . $self->{'subdomain_id'},
            FORWARD                 => $self->{'subdomain_url_forward'} || 'no',
            FORWARD_TYPE            => $self->{'subdomain_type_forward'} || '',
            FORWARD_PRESERVE_HOST   => $self->{'subdomain_host_forward'} || 'Off',
            DISABLE_FUNCTIONS       => $phpini->{'disable_functions'} || '',
            MAX_EXECUTION_TIME      => $phpini->{'max_execution_time'} || 30,
            MAX_INPUT_TIME          => $phpini->{'max_input_time'} || 60,
            MEMORY_LIMIT            => $phpini->{'memory_limit'} || 128,
            ERROR_REPORTING         => $phpini->{'error_reporting'} || 'E_ALL & ~E_DEPRECATED & ~E_STRICT',
            DISPLAY_ERRORS          => $phpini->{'display_errors'} || 'off',
            POST_MAX_SIZE           => $phpini->{'post_max_size'} || 8,
            UPLOAD_MAX_FILESIZE     => $phpini->{'upload_max_filesize'} || 2,
            ALLOW_URL_FOPEN         => $phpini->{'allow_url_fopen'} || 'off',
            PHP_FPM_LISTEN_PORT     => ( $phpini->{'id'} // 1 )-1,
            EXTERNAL_MAIL           => $self->{'external_mail'},
            MAIL_ENABLED            => ( $self->{'external_mail'} eq 'off' && ( $self->{'mail_on_domain'} || $self->{'domain_mailacc_limit'} >= 0 ) )
        }
    } unless %{$self->{'_data'}};

    $self->{'_data'};
}

=item _sharedMountPoint( )

 Does this subdomain share mount point with another domain?

 Return bool, die on failure

=cut

sub _sharedMountPoint
{
    my ($self) = @_;

    local $self->{'_dbh'}->{'RaiseError'} = 1;

    my $regexp = "^$self->{'subdomain_mount'}(/.*|\$)";
    my ($nbSharedMountPoints) = $self->{'_dbh'}->selectrow_array(
        "
            SELECT COUNT(mount_point) AS nb_mount_points FROM (
                SELECT alias_mount AS mount_point FROM domain_aliasses
                WHERE domain_id = ? AND alias_status NOT IN ('todelete', 'ordered') AND alias_mount RLIKE ?
                UNION ALL
                SELECT subdomain_mount AS mount_point FROM subdomain
                WHERE subdomain_id <> ? AND domain_id = ? AND subdomain_status <> 'todelete' AND subdomain_mount RLIKE ?
                UNION ALL
                SELECT subdomain_alias_mount AS mount_point FROM subdomain_alias
                WHERE subdomain_alias_status <> 'todelete'
                AND alias_id IN (SELECT alias_id FROM domain_aliasses WHERE domain_id = ?)
                AND subdomain_alias_mount RLIKE ?
            ) AS tmp
        ",
        undef, $self->{'domain_id'}, $regexp, $self->{'subdomain_id'}, $self->{'domain_id'}, $regexp,
        $self->{'domain_id'}, $regexp
    );

    ( $nbSharedMountPoints || $self->{'subdomain_mount'} eq '/' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
