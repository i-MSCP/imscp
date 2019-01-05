=head1 NAME

 Modules::Domain - i-MSCP Domain module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Modules::Domain;

use strict;
use warnings;
use File::Basename;
use File::Spec;
use File::Temp;
use iMSCP::Boolean;
use iMSCP::Debug qw/ debug error getMessageByType warning /;
use iMSCP::Dir;
use iMSCP::Ext2Attributes 'clearImmutable';
use iMSCP::Execute qw/ execute escapeShell /;
use Net::LibIDN 'idn_to_unicode';
use Servers::httpd;
use Servers::sqld;
use Try::Tiny;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP Domain module.

=head1 PUBLIC METHODS

=over 4

=item getType( )

 Get module type

 Return string Module type

=cut

sub getType
{
    'Dmn';
}

=item process( \%data )

 Process module

 Param hashref \%data Domain data
 Return int 0 on success, other on failure

=cut

sub process
{
    my ( $self, $data ) = @_;

    try {
        $self->_loadData( $data->{'id'} );

        my ( @sql, $rs );
        if ( $self->{'domain_status'} =~ /^to(?:add|change|enable)$/ ) {
            $rs = $self->add();
            @sql = (
                'UPDATE domain SET domain_status = ? WHERE domain_id = ?', undef,
                ( $rs ? getMessageByType( 'error', { amount => 1 } ) || 'Unknown error' : 'ok' ), $data->{'id'}
            );
        } elsif ( $self->{'domain_status'} eq 'todisable' ) {
            $rs = $self->disable();
            @sql = (
                'UPDATE domain SET domain_status = ? WHERE domain_id = ?', undef,
                ( $rs ? getMessageByType( 'error', { amount => 1 } ) || 'Unknown error' : 'disabled' ), $data->{'id'}
            );
        } elsif ( $self->{'domain_status'} eq 'torestore' ) {
            $rs = $self->restore();
            @sql = (
                'UPDATE domain SET domain_status = ? WHERE domain_id = ?', undef,
                ( $rs ? getMessageByType( 'error', { amount => 1 } ) || 'Unknown error' : 'ok' ), $data->{'id'}
            );
        } else {
            $rs = $self->delete();
            @sql = $rs ? (
                'UPDATE domain SET domain_status = ? WHERE domain_id = ?', undef,
                getMessageByType( 'error', { amount => 1 } ) || 'Unknown error', $data->{'id'}
            ) : ( 'DELETE FROM domain WHERE domain_id = ?', undef, $data->{'id'} );
        }

        $self->{'_conn'}->run( fixup => sub { $_->do( @sql ); } );
        $rs;
    } catch {
        error( $_ );
        1;
    };
}

=item add( )

 Add domain

 Schedule change of custom DNS records that belong to this domain unless there
 is already a pending task for them. See #IP-1801

 Return int 0 on success, other on failure

=cut

sub add
{
    my ( $self ) = @_;

    return $self->SUPER::add() if $self->{'domain_status'} eq 'toadd' || $::execmode eq 'setup';

    try {
        $self->{'_conn'}->run( fixup => sub {
            $_->do(
                "UPDATE domain_dns SET domain_dns_status = 'tochange' WHERE domain_id = ? AND alias_id = 0 AND domain_dns_status = 'ok'",
                undef, $self->{'domain_id'}
            );
        } );
        0;
    } catch {
        error( $_ );
        1;
    } || $self->SUPER::add();
}

=item disable( )

 Disable domain

 Return int 0 on success, other on failure

=cut

sub disable
{
    my ( $self ) = @_;

    try {
        $self->{'_conn'}->run( fixup => sub {
            $_->do(
                "UPDATE subdomain SET subdomain_status = 'todisable' WHERE domain_id = ? AND subdomain_status <> 'todelete'", undef,
                $self->{'domain_id'}
            );
        } );
        0;
    } catch {
        error( $_ );
        1;
    } || $self->SUPER::disable();
}

=item restore( )

 Restore backup

 Return int 0 on success, other on failure

=cut

sub restore
{
    my ( $self ) = @_;

    my $homeDir = "$::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}";
    my $bkpDir = "$homeDir/backups";

    # Restore know databases only
    my $rows = $self->{'_conn'}->run( fixup => sub {
        $_->selectall_arrayref( 'SELECT sqld_name FROM sql_database WHERE domain_id = ?', { Slice => {} }, $self->{'domain_id'} );
    } );

    for my $row ( @{ $rows } ) {
        # Encode slashes as SOLIDUS unicode character
        # Encode dots as Full stop unicode character
        ( my $encodedDbName = $row->{'sqld_name'} ) =~ s%([./])%{ '/', '@002f', '.', '@002e' }->{$1}%ge;

        for my $ext ( '.sql', '.sql.bz2', '.sql.gz', '.sql.lzma', '.sql.xz' ) {
            my $dbDumpFilePath = File::Spec->catfile( $bkpDir, $encodedDbName . $ext );
            debug( $dbDumpFilePath );
            next unless -f $dbDumpFilePath;
            $self->_restoreDatabase( $row->{'sqld_name'}, $dbDumpFilePath );
        }
    }

    # Restore first Web backup found
    for my $backup ( iMSCP::Dir->new( dirname => $bkpDir )->getFiles() ) {
        next if -l "$bkpDir/$backup"; # Don't follow symlinks (See #IP-990)
        next unless /^web-backup-.+?\.tar(?:\.(bz2|gz|lzma|xz))?$/;

        my $archType = $1 || '';

        return 1 if try {
            {
                # Since we are now using immutable bit to protect some folders, we must in order do the following
                # to restore a backup archive:
                #
                # - Un-protect user homedir (clear immutable flag recursively)
                # - Restore web files
                # - Update status of sub, als and alssub, entities linked to the parent domain to 'torestore'
                # - Run the restore( ) parent method
                #
                # The third and last tasks allow the i-MSCP Httpd server implementations to set correct permissions and
                # set immutable flag on folders if needed for each entity
                #
                # Note: This is a lot of works but this will be fixed when the backup feature will be rewritten

                if ( $archType eq 'bz2' ) {
                    $archType = 'bzip2';
                } elsif ( $archType eq 'gz' ) {
                    $archType = 'gzip';
                }

                clearImmutable( $homeDir, TRUE ); # Un-protect homedir recursively

                my $stderr;
                execute(
                    [ '/bin/tar', '-x', '-p',
                        $archType ne '' ? ( "--$archType", '-C', $homeDir, '-f', "$bkpDir/$backup" ) : ( '-C', $homeDir, '-f', "$bkpDir/$backup" ),
                    ],
                    \my $stdout,
                    \$stderr
                ) == 0 or die( $stderr || 'Unknown error' );
                debug( $stdout ) if $stdout;
            }

            $self->{'_conn'}->txn( fixup => sub {
                $_->do(
                    "UPDATE subdomain SET subdomain_status = 'torestore' WHERE domain_id = ? AND subdomain_status <> 'todelete'", undef,
                    $self->{'domain_id'}
                );
                $_->do(
                    "UPDATE domain_aliasses SET alias_status = 'torestore' WHERE domain_id = ? AND alias_status <> 'todelete'", undef,
                    $self->{'domain_id'}
                );
                $_->do(
                    "
                    UPDATE subdomain_alias AS t1
                    JOIN domain_aliasses AS t2 USING(alias_id)
                    SET t1.subdomain_alias_status = 'torestore'
                    WHERE t2.domain_id = ?
                    AND t1.subdomain_alias_status <> 'todelete'
                ",
                    undef, $self->{'domain_id'}
                );
            } );
            0;
        } catch {
            error( $_ );
            1;
        };

        last;
    }

    $self->SUPER::restore();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _loadData( $domainId )

 Load data

 Param int $domainId Domain unique identifier
 Return void, die on failure

=cut

sub _loadData
{
    my ( $self, $domainId ) = @_;

    my $row = $self->{'_conn'}->run( fixup => sub {
        $_->selectrow_hashref(
            "
                SELECT t1.domain_id, t1.domain_admin_id, t1.domain_mailacc_limit, t1.domain_name, t1.domain_status, t1.domain_php, t1.domain_cgi,
                    t1.external_mail, t1.web_folder_protection, t1.document_root, t1.url_forward, t1.type_forward, t1.host_forward,
                    IFNULL(t2.ip_number, '0.0.0.0') AS ip_number,
                    t3.private_key, t3.certificate, t3.ca_bundle, t3.allow_hsts, t3.hsts_max_age, t3.hsts_include_subdomains,
                    t4.mail_on_domain
                FROM domain AS t1
                LEFT JOIN server_ips AS t2 ON (t2.ip_id = t1.domain_ip_id)
                LEFT JOIN ssl_certs AS t3 ON(t3.domain_id = t1.domain_id AND t3.domain_type = 'dmn' AND t3.status = 'ok')
                LEFT JOIN (
                    SELECT domain_id, COUNT(domain_id) AS mail_on_domain FROM mail_users WHERE mail_type LIKE 'normal\\_%' GROUP BY domain_id
                ) AS t4 ON(t4.domain_id = t1.domain_id)
                WHERE t1.domain_id = ?
            ",
            undef, $domainId
        );
    } );
    $row or die( sprintf( 'Data not found for domain (ID %d)', $domainId ));
    %{ $self } = ( %{ $self }, %{ $row } );
}

=item _getData( $action )

 Data provider method for servers and packages

 Param string $action Action
 Return hashref Reference to a hash containing data, die on failure

=cut

sub _getData
{
    my ( $self, $action ) = @_;

    $self->{'_data'} = do {
        my $httpd = Servers::httpd->factory();
        my $ug = $::imscpConfig{'SYSTEM_USER_PREFIX'} . ( $::imscpConfig{'SYSTEM_USER_MIN_UID'}+$self->{'domain_admin_id'} );
        my $homeDir = File::Spec->canonpath( "$::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}" );
        my $documentRoot = File::Spec->canonpath( "$homeDir/$self->{'document_root'}" );
        my $phpini = $self->{'_conn'}->run( fixup => sub {
            $_->selectrow_hashref( "SELECT * FROM php_ini WHERE domain_id = ? AND domain_type = 'dmn'", undef, $self->{'domain_id'} ) || {};
        } );
        my $haveCert = defined $self->{'certificate'} && -f "$::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$self->{'domain_name'}.pem";
        my $allowHSTS = $haveCert && $self->{'allow_hsts'} eq 'on';
        my $hstsMaxAge = $allowHSTS ? $self->{'hsts_max_age'} : 0;
        my $hstsIncludeSubDomains = $allowHSTS && $self->{'hsts_include_subdomains'} eq 'on'
            ? '; includeSubDomains' : ( $allowHSTS ? '' : '; includeSubDomains' );
        {
            ACTION                  => $action,
            STATUS                  => $self->{'domain_status'},
            BASE_SERVER_VHOST       => $::imscpConfig{'BASE_SERVER_VHOST'},
            BASE_SERVER_IP          => $::imscpConfig{'BASE_SERVER_IP'},
            BASE_SERVER_PUBLIC_IP   => $::imscpConfig{'BASE_SERVER_PUBLIC_IP'},
            DOMAIN_ADMIN_ID         => $self->{'domain_admin_id'},
            DOMAIN_ID               => $self->{'domain_id'},
            DOMAIN_NAME             => $self->{'domain_name'},
            DOMAIN_NAME_UNICODE     => idn_to_unicode( $self->{'domain_name'}, 'utf-8' ),
            DOMAIN_IP               => $self->{'ip_number'},
            DOMAIN_TYPE             => 'dmn',
            PARENT_DOMAIN_NAME      => $self->{'domain_name'},
            ROOT_DOMAIN_NAME        => $self->{'domain_name'},
            HOME_DIR                => $homeDir,
            WEB_DIR                 => $homeDir,
            MOUNT_POINT             => '/',
            DOCUMENT_ROOT           => $documentRoot,
            SHARED_MOUNT_POINT      => FALSE,
            PEAR_DIR                => $httpd->{'phpConfig'}->{'PHP_PEAR_DIR'},
            TIMEZONE                => $::imscpConfig{'TIMEZONE'},
            USER                    => $ug,
            GROUP                   => $ug,
            PHP_SUPPORT             => $self->{'domain_php'},
            CGI_SUPPORT             => $self->{'domain_cgi'},
            WEB_FOLDER_PROTECTION   => $self->{'web_folder_protection'},
            SSL_SUPPORT             => $haveCert,
            HSTS_SUPPORT            => $allowHSTS,
            HSTS_MAX_AGE            => $hstsMaxAge,
            HSTS_INCLUDE_SUBDOMAINS => $hstsIncludeSubDomains,
            ALIAS                   => 'dmn' . $self->{'domain_id'},
            FORWARD                 => $self->{'url_forward'} || 'no',
            FORWARD_TYPE            => $self->{'type_forward'} || '',
            FORWARD_PRESERVE_HOST   => $self->{'host_forward'} || 'Off',
            DISABLE_FUNCTIONS       => $phpini->{'disable_functions'} // '',
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
    } unless %{ $self->{'_data'} };

    $self->{'_data'};
}

=item _restoreDatabase( $dbName, $dbDumpFilePath )

 Restore a database from the given database dump file
 
 Param string $dbName Database name
 Param string $dbDumpFilePath Path to database dump file
 Return void, die on failure

=cut

sub _restoreDatabase
{
    my ( undef, $dbName, $dbDumpFilePath ) = @_;

    my ( undef, undef, $archFormat ) = fileparse( $dbDumpFilePath, qr/\.(?:bz2|gz|lzma|xz)/ );

    my $cmd;
    if ( defined $archFormat ) {
        if ( $archFormat eq '.bz2' ) {
            $cmd = 'bzcat -d ';
        } elsif ( $archFormat eq '.gz' ) {
            $cmd = 'zcat -d ';
        } elsif ( $archFormat eq '.lzma' ) {
            $cmd = 'lzma -dc ';
        } elsif ( $archFormat eq '.xz' ) {
            $cmd = 'xz -dc ';
        } else {
            warning( sprintf( "Unsupported '%s' SQL database dump archive format. skipping...", $archFormat ));
            return;
        }
    } else {
        $cmd = 'cat ';
    }

    my @cmd = ( $cmd, escapeShell( $dbDumpFilePath ), '|', 'mysql', escapeShell( $dbName ) );
    my $rs = execute( "@cmd", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    $rs == 0 or die( error( sprintf( "Couldn't restore the '%s' SQL database: %s", $dbName, $stderr || 'Unknown error' )));
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
