=head1 NAME

 Modules::Domain - i-MSCP Domain module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by internet Multi Server Control Panel
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
use Encode qw / decode_utf8 /;
use File::Spec;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::Ext2Attributes qw(clearImmutable);
use iMSCP::Execute;
use iMSCP::OpenSSL;
use iMSCP::Rights;
use Modules::User;
use Net::LibIDN qw/idn_to_unicode/;
use Servers::httpd;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP Domain module.

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

=item process($domainId)

 Process module

 Param int $domainId Domain unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
    my ($self, $domainId) = @_;

    my $rs = $self->_loadData( $domainId );
    return $rs if $rs;

    my @sql;
    if ($self->{'domain_status'} =~ /^to(?:add|change|enable)$/) {
        $rs = $self->add();
        @sql = (
            'UPDATE domain SET domain_status = ? WHERE domain_id = ?',
            ($rs ? scalar getMessageByType( 'error' ) || 'Unknown error' : 'ok'), $domainId
        );
    } elsif ($self->{'domain_status'} eq 'todelete') {
        $rs = $self->delete();
        if ($rs) {
            @sql = (
                'UPDATE domain SET domain_status = ? WHERE domain_id = ?',
                scalar getMessageByType( 'error' ) || 'Unknown error', $domainId
            );
        } else {
            @sql = ('DELETE FROM domain WHERE domain_id = ?', $domainId);
        }
    } elsif ($self->{'domain_status'} eq 'todisable') {
        $rs = $self->disable();
        @sql = (
            'UPDATE domain SET domain_status = ? WHERE domain_id = ?',
            ($rs ? scalar getMessageByType( 'error' ) || 'Unknown error' : 'disabled'), $domainId
        );
    } elsif ($self->{'domain_status'} eq 'torestore') {
        $rs = $self->restore();
        @sql = (
            'UPDATE domain SET domain_status = ? WHERE domain_id = ?',
            ($rs ? scalar getMessageByType( 'error' ) || 'Unknown error' : 'ok'), $domainId
        );
    }

    my $rdata = iMSCP::Database->factory()->doQuery( 'dummy', @sql );
    unless (ref $rdata eq 'HASH') {
        error( $rdata );
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

    if ($self->{'domain_status'} eq 'tochange') {
        my $db = iMSCP::Database->factory();

        # Sets the status of any subdomain that belongs to this domain to 'tochange'.
        # FIXME: This reflect a bad implementation in the way that entities are managed. This will be solved
        # in version 2.0.0.
        my $rs = $db->doQuery(
            'u',
            "UPDATE subdomain SET subdomain_status = 'tochange' WHERE domain_id = ? AND subdomain_status <> 'todelete'",
            $self->{'domain_id'}
        );
        unless (ref $rs eq 'HASH') {
            error( $rs );
            return 1;
        }

        $rs = $db->doQuery(
            'u',
            "
                UPDATE domain_dns SET domain_dns_status = 'tochange'
                WHERE domain_id = ? AND alias_id = 0 AND domain_dns_status NOT IN('todelete', 'todisable', 'disabled')
            ",
            $self->{'domain_id'}
        );
        unless (ref $rs eq 'HASH') {
            error( $rs );
            return 1;
        }
    }

    $self->SUPER::add();
}

=item disable()

 Disable domain

 Return int 0 on success, other on failure

=cut

sub disable
{
    my $self = shift;

    # Sets the status of any subdomain that belongs to this domain to 'todisable'.
    my $rs = iMSCP::Database->factory()->doQuery(
        'u',
        "UPDATE subdomain SET subdomain_status = 'todisable' WHERE domain_id = ? AND subdomain_status <> 'todelete'",
        $self->{'domain_id'}
    );
    unless (ref $rs eq 'HASH') {
        error( $rs );
        return 1;
    }

    $self->SUPER::disable();
}

=item restore()

 Restore domain

 Return int 0 on success, other on failure

=cut

sub restore
{
    my $self = shift;

    my $dmnDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}";
    my $bkpDir = "$dmnDir/backups";
    my @bkpFiles = iMSCP::Dir->new( dirname => $bkpDir )->getFiles();

    return 0 unless @bkpFiles;

    for my $bkpFile(@bkpFiles) {
        unless (-l "$bkpDir/$bkpFile") { # Doesn't follow any symlink (See #990)
            if ($bkpFile =~ /^(.+?)\.sql(?:\.(bz2|gz|lzma|xz))?$/) {
                # Restore SQL database
                my $sqldName = $1;
                my $archType = $2 || '';

                my $rdata = iMSCP::Database->factory()->doQuery(
                    'sqld_name',
                    '
                        SELECT * FROM sql_database INNER JOIN sql_user USING(sqld_id)
                        WHERE domain_id = ? AND sqld_name = ? LIMIT 1
                    ',
                    $self->{'domain_id'}, $sqldName
                );
                unless (ref $rdata eq 'HASH') {
                    error( $rdata );
                    return 1;
                }
                unless (%{$rdata}) {
                    warning(sprintf( 'Orphaned db (%s) or missing SQL user for this db. skipping...', $sqldName ));
                    next;
                }

                my $cmd;
                if ($archType eq 'bz2') {
                    $cmd = 'bzcat -d ';
                } elsif ($archType eq 'gz') {
                    $cmd = 'zcat -d ';
                } elsif ($archType eq 'lzma') {
                    $cmd = 'lzma -dc ';
                } elsif ($archType eq 'xz') {
                    $cmd = 'xz -dc ';
                } else {
                    $cmd = 'cat ';
                }

                my @cmd = (
                    'nice', '-n', '15', # Reduce the CPU priority
                    'ionice', '-c2', '-n5', # Reduce the I/O priority
                    $cmd,
                    escapeShell( "$bkpDir/$bkpFile" ), '|', 'mysql',
                    '-h', escapeShell( $main::imscpConfig{'DATABASE_HOST'} ),
                    '-P', escapeShell( $main::imscpConfig{'DATABASE_PORT'} ),
                    '-u', escapeShell( $rdata->{$sqldName}->{'sqlu_name'} ),
                    '-p'.escapeShell( $rdata->{$sqldName}->{'sqlu_pass'} ),
                    escapeShell( $rdata->{$sqldName}->{'sqld_name'} )
                );

                my $rs = execute( "@cmd", \ my $stdout, \ my $stderr );
                debug( $stdout ) if $stdout;
                warning( sprintf( 'Could not restore SQL database: %s', $stderr || 'Unknown error' ) ) if $rs;
            } elsif ($bkpFile =~ /^(?!mail-).+?\.tar(?:\.(bz2|gz|lzma|xz))?$/) {
                # Restore domain files
                my $archType = $1 || '';
                # Since we are now using immutable bit to protect some folders, we must in order do the following
                # to restore a backup archive:
                #
                # - Update status of sub, als and alssub, entities linked to the parent domain to 'torestore'
                # - Un-protect user home dir (clear immutable flag recursively)
                # - restore the files
                # - Run the restore() parent method
                #
                # The first and last tasks allow the i-MSCP Httpd server implementations to set correct permissions and
                # set immutable flag on folders if needed for each entity
                #
                # Note: This is a bunch of works but this will be fixed when the backup feature will be rewritten

                if ($archType eq 'bz2') {
                    $archType = 'bzip2';
                } elsif ($archType eq 'gz') {
                    $archType = 'gzip';
                }

                my $db = iMSCP::Database->factory();

                # Update status of any sub to 'torestore'
                my $rdata = $db->doQuery(
                    'dummy', 'UPDATE subdomain SET subdomain_status = ? WHERE domain_id = ?', 'torestore',
                    $self->{'domain_id'}
                );
                unless (ref $rdata eq 'HASH') {
                    error( $rdata );
                    return 1;
                }

                # Update status of any als to 'torestore'
                $rdata = $db->doQuery(
                    'dummy', 'UPDATE domain_aliasses SET alias_status = ? WHERE domain_id = ?', 'torestore',
                    $self->{'domain_id'}
                );
                unless (ref $rdata eq 'HASH') {
                    error( $rdata );
                    return 1;
                }

                # Update status of any alssub to 'torestore'
                $rdata = $db->doQuery(
                    'dummy',
                    "
                        UPDATE subdomain_alias SET subdomain_alias_status = 'torestore'
                        WHERE alias_id IN (SELECT alias_id FROM domain_aliasses WHERE domain_id = ?)
                    ",
                    $self->{'domain_id'}
                );
                unless (ref $rdata eq 'HASH') {
                    error( $rdata );
                    return 1;
                }

                # Un-protect folders recursively
                clearImmutable( $dmnDir, 1 );

                my $cmd;
                if ($archType ne '') {
                    $cmd = "nice -n 12 ionice -c2 -n5 tar -x -p --$archType -C ".escapeShell( $dmnDir ).' -f '.
                        escapeShell( "$bkpDir/$bkpFile" );
                } else {
                    $cmd = 'nice -n 12 ionice -c2 -n5 tar -x -p -C '.escapeShell( $dmnDir ).' -f '.
                        escapeShell( "$bkpDir/$bkpFile" );
                }

                my $rs = execute( $cmd, \ my $stdout, \ my $stderr );
                debug( $stdout ) if $stdout;
                error( $stderr || 'Unknown error' ) if $rs;

                #                my $groupName =
                #                    my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.
                #                    ($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});
                #
                #                $rs = setRights( $dmnDir, { user => $userName, group => $groupName, recursive => 1 } );
                $rs ||= $self->SUPER::restore();
                return $rs if $rs;
            }
        }
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _loadData($domainId)

 Load data

 Param int $domainId Domain unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
    my ($self, $domainId) = @_;

    my $rdata = iMSCP::Database->factory()->doQuery(
        'domain_id',
        "
            SELECT t1.*, t2.ip_number, t3.mail_on_domain FROM domain AS t1
            INNER JOIN server_ips AS t2 ON (t1.domain_ip_id = t2.ip_id)
            LEFT JOIN (
                SELECT domain_id, COUNT(domain_id) AS mail_on_domain FROM mail_users WHERE mail_type LIKE 'normal\\_%' GROUP BY domain_id
            ) AS t3 ON(t1.domain_id = t3.domain_id)
            WHERE t1.domain_id = ?
        ",
        $domainId
    );
    unless (ref $rdata eq 'HASH') {
        error( $rdata );
        return 1;
    }
    unless ($rdata->{$domainId}) {
        error( sprintf( 'Domain with ID %s has not been found or is in an inconsistent state', $domainId ) );
        return 1;
    }

    %{$self} = (%{$self}, %{$rdata->{$domainId}});
    0;
}

=item _getData($action)

 Data provider method for servers and packages

 Param string $action Action
 Return hashref Reference to a hash containing data, die on failure

=cut

sub _getData
{
    my ($self, $action) = @_;

    $self->{'_data'} = do {
        my $httpd = Servers::httpd->factory();
        my $groupName = my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.
            ($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});
        my $homeDir = File::Spec->canonpath( "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}" );
        my $documentRoot = File::Spec->canonpath( "$homeDir/$self->{'document_root'}" );
        my $db = iMSCP::Database->factory();

        my $phpini = $db->doQuery(
            'domain_id', "SELECT * FROM php_ini WHERE domain_id = ? AND domain_type = 'dmn'", $self->{'domain_id'}
        );
        ref $phpini eq 'HASH' or die( $phpini );

        my $certData = $db->doQuery(
            'domain_id', 'SELECT * FROM ssl_certs WHERE domain_id = ? AND domain_type = ? AND status = ?',
            $self->{'domain_id'}, 'dmn', 'ok'
        );
        ref $certData eq 'HASH' or die( $certData );

        #my $haveCert = ($certData->{$self->{'domain_id'}} && $self->isValidCertificate( $self->{'domain_name'} ));
        my $haveCert = (
            $certData->{$self->{'domain_id'}}
            && -f "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$self->{'domain_name'}.pem"
        );
        my $allowHSTS = ($haveCert && $certData->{$self->{'domain_id'}}->{'allow_hsts'} eq 'on');
        my $hstsMaxAge = $allowHSTS ? $certData->{$self->{'domain_id'}}->{'hsts_max_age'} : '';
        my $hstsIncludeSubDomains = ($allowHSTS && $certData->{$self->{'domain_id'}}->{'hsts_include_subdomains'} eq 'on')
            ? '; includeSubDomains' : '';

        {
            ACTION                  => $action,
            STATUS                  => $self->{'domain_status'},
            BASE_SERVER_VHOST       => $main::imscpConfig{'BASE_SERVER_VHOST'},
            BASE_SERVER_IP          => $main::imscpConfig{'BASE_SERVER_IP'},
            BASE_SERVER_PUBLIC_IP   => $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'},
            DOMAIN_ADMIN_ID         => $self->{'domain_admin_id'},
            DOMAIN_NAME             => $self->{'domain_name'},
            DOMAIN_NAME_UNICODE     => decode_utf8( idn_to_unicode( $self->{'domain_name'}, 'utf-8' ) ),
            DOMAIN_IP               => $self->{'ip_number'},
            DOMAIN_TYPE             => 'dmn',
            PARENT_DOMAIN_NAME      => $self->{'domain_name'},
            ROOT_DOMAIN_NAME        => $self->{'domain_name'},
            HOME_DIR                => $homeDir,
            WEB_DIR                 => $homeDir,
            MOUNT_POINT             => '/',
            DOCUMENT_ROOT           => $documentRoot,
            SHARED_MOUNT_POINT      => 0,
            PEAR_DIR                => $httpd->{'phpConfig'}->{'PHP_PEAR_DIR'},
            TIMEZONE                => $main::imscpConfig{'TIMEZONE'},
            USER                    => $userName,
            GROUP                   => $groupName,
            PHP_SUPPORT             => $self->{'domain_php'},
            CGI_SUPPORT             => $self->{'domain_cgi'},
            WEB_FOLDER_PROTECTION   => $self->{'web_folder_protection'},
            SSL_SUPPORT             => $haveCert,
            SSL_SUPPORT             => $haveCert,
            HSTS_SUPPORT            => $allowHSTS,
            HSTS_MAX_AGE            => $hstsMaxAge,
            HSTS_INCLUDE_SUBDOMAINS => $hstsIncludeSubDomains,
            BWLIMIT                 => $self->{'domain_traffic_limit'},
            ALIAS                   => $userName,
            FORWARD                 => $self->{'url_forward'} || 'no',
            FORWARD_TYPE            => $self->{'type_forward'} || '',
            FORWARD_PRESERVE_HOST   => $self->{'host_forward'} || 'Off',
            DISABLE_FUNCTIONS       => $phpini->{$self->{'domain_id'}}->{'disable_functions'} // 'exec,passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system',
            MAX_EXECUTION_TIME      => $phpini->{$self->{'domain_id'}}->{'max_execution_time'} // 30,
            MAX_INPUT_TIME          => $phpini->{$self->{'domain_id'}}->{'max_input_time'} // 60,
            MEMORY_LIMIT            => $phpini->{$self->{'domain_id'}}->{'memory_limit'} // 128,
            ERROR_REPORTING         => $phpini->{$self->{'domain_id'}}->{'error_reporting'} || 'E_ALL & ~E_DEPRECATED & ~E_STRICT',
            DISPLAY_ERRORS          => $phpini->{$self->{'domain_id'}}->{'display_errors'} || 'off',
            POST_MAX_SIZE           => $phpini->{$self->{'domain_id'}}->{'post_max_size'} // 8,
            UPLOAD_MAX_FILESIZE     => $phpini->{$self->{'domain_id'}}->{'upload_max_filesize'} // 2,
            ALLOW_URL_FOPEN         => $phpini->{$self->{'domain_id'}}->{'allow_url_fopen'} || 'off',
            PHP_FPM_LISTEN_PORT     => ($phpini->{$self->{'domain_id'}}->{'id'} // 0) - 1,
            EXTERNAL_MAIL           => $self->{'external_mail'},
            MAIL_ENABLED            => ($self->{'external_mail'} eq 'off' && ($self->{'mail_on_domain'} || $self->{'domain_mailacc_limit'} >= 0))
        }
    } unless %{$self->{'_data'}};

    $self->{'_data'};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
