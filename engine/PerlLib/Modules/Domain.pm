=head1 NAME

 Modules::Domain - i-MSCP Domain module

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

package Modules::Domain;

use strict;
use warnings;
use iMSCP::Debug;
use Modules::User;
use iMSCP::Execute;
use iMSCP::Dir;
use iMSCP::Database;
use iMSCP::Rights;
use iMSCP::OpenSSL;
use iMSCP::Ext2Attributes qw(clearImmutable);
use File::Spec;
use Net::LibIDN qw/idn_to_unicode/;
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
    if (grep($_ eq $self->{'domain_status'}, ( 'toadd', 'tochange', 'toenable' ))) {
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
        # This is needed, else, the DNS resource records for the subdomains are not re-added in DNS zone files.
        # FIXME: This reflect a bad implementation in the way that entities are managed. This will be solved
        # in version 2.0.0.
        my $rs = $db->doQuery(
            'u', 'UPDATE subdomain SET subdomain_status = ? WHERE domain_id = ? AND subdomain_status <> ?',
            'tochange', $self->{'domain_id'}, 'todelete'
        );
        unless (ref $rs eq 'HASH') {
            error( $rs );
            return 1;
        }

        $rs = $db->doQuery(
            'u',
            'UPDATE domain_dns SET domain_dns_status = ? WHERE domain_id = ? AND alias_id = ? AND domain_dns_status <> ?'
            ,
            'tochange', $self->{'domain_id'}, '0', 'todelete'
        );
        unless (ref $rs eq 'HASH') {
            error( $rs );
            return 1;
        }
    }

    $self->SUPER::add();
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
                    warning( sprintf( 'Orphaned database (%s) or missing SQL user for this database. skipping...',
                            $sqldName ) );
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

                my $rs = execute( "@cmd", \my $stdout, \my $stderr );
                debug( $stdout ) if $stdout;
                warning( sprintf( 'Could not to restore SQL database: %s', $stderr ) ) if $stderr && $rs;
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
                        UPDATE subdomain_alias
                        SET subdomain_alias_status = 'torestore'
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

                my $rs = execute( $cmd, \my $stdout, \my $stderr );
                debug( $stdout ) if $stdout;
                error( $stderr ) if $stderr && $rs;
                return $rs if $rs;

                my $groupName =
                    my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.
                    ($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

                $rs = setRights( $dmnDir, { user => $userName, group => $groupName, recursive => 1 } );
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
        '
            SELECT domain.*, ip_number, IFNULL(mail_on_domain, 0) AS mail_on_domain
            FROM domain INNER JOIN server_ips ON (domain_ip_id = ip_id)
            LEFT JOIN (
                SELECT domain_id, COUNT(domain_id) AS mail_on_domain FROM mail_users WHERE sub_id = 0
                GROUP BY domain_id
            ) AS mail_count USING (domain_id)
            WHERE domain_id = ?
        ',
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
    my $homeDir = File::Spec->canonpath( "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}" );
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

    my $haveCert = $certData->{$self->{'domain_id'}} && $self->isValidCertificate( $self->{'domain_name'} );

    $self->{'httpd'} = {
        DOMAIN_ADMIN_ID       => $self->{'domain_admin_id'},
        DOMAIN_NAME           => $self->{'domain_name'},
        DOMAIN_NAME_UNICODE   => idn_to_unicode( $self->{'domain_name'}, 'UTF-8' ),
        DOMAIN_IP             => $self->{'ip_number'},
        DOMAIN_TYPE           => 'dmn',
        PARENT_DOMAIN_NAME    => $self->{'domain_name'},
        ROOT_DOMAIN_NAME      => $self->{'domain_name'},
        HOME_DIR              => $homeDir,
        WEB_DIR               => $homeDir,
        MOUNT_POINT           => '/',
        SHARED_MOUNT_POINT    => 0,
        PEAR_DIR              => $main::imscpConfig{'PEAR_DIR'},
        TIMEZONE              => $main::imscpConfig{'TIMEZONE'},
        USER                  => $userName,
        GROUP                 => $groupName,
        PHP_SUPPORT           => $self->{'domain_php'},
        CGI_SUPPORT           => $self->{'domain_cgi'},
        WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'},
        SSL_SUPPORT           => $haveCert,
        BWLIMIT               => $self->{'domain_traffic_limit'},
        ALIAS                 => $userName,
        FORWARD               => 'no',
        DISABLE_FUNCTIONS     => $phpini->{$self->{'domain_id'}}->{'disable_functions'} //
            'exec,passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system',
        MAX_EXECUTION_TIME    => $phpini->{$self->{'domain_id'}}->{'max_execution_time'} // 30,
        MAX_INPUT_TIME        => $phpini->{$self->{'domain_id'}}->{'max_input_time'} // 60,
        MEMORY_LIMIT          => $phpini->{$self->{'domain_id'}}->{'memory_limit'} // 128,
        ERROR_REPORTING       =>
        $phpini->{$self->{'domain_id'}}->{'error_reporting'} || 'E_ALL & ~E_DEPRECATED & ~E_STRICT',
        DISPLAY_ERRORS        => $phpini->{$self->{'domain_id'}}->{'display_errors'} || 'off',
        POST_MAX_SIZE         => $phpini->{$self->{'domain_id'}}->{'post_max_size'} // 8,
        UPLOAD_MAX_FILESIZE   => $phpini->{$self->{'domain_id'}}->{'upload_max_filesize'} // 2,
        ALLOW_URL_FOPEN       => $phpini->{$self->{'domain_id'}}->{'allow_url_fopen'} || 'off',
        PHP_FPM_LISTEN_PORT   => ($phpini->{$self->{'domain_id'}}->{'id'} // 0) - 1
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
        DOMAIN_NAME     => $self->{'domain_name'},
        DOMAIN_TYPE     => $self->getType(),
        TYPE            => 'vdmn_entry',
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
        DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
        DOMAIN_NAME     => $self->{'domain_name'},
        DOMAIN_IP       => $self->{'ip_number'},
        USER_NAME       => $userName,
        MAIL_ENABLED    => (
                $self->{'mail_on_domain'} || $self->{'domain_mailacc_limit'} >= 0 &&
                    grep($_ eq $self->{'external_mail'}, ( 'wildcard', 'off' ))
            ) ? 1 : 0,
        SPF_RECORDS     => [ ]
    };

    return %{$self->{'named'}} unless $action =~ /add/ && grep($_ eq $self->{'external_mail'}, ( 'domain', 'filter',
        'wildcard' ));

    my $db = iMSCP::Database->factory();
    my $rdata = $db->doQuery(
        'domain_dns_id',
        '
            SELECT domain_dns_id, domain_dns, domain_text FROM domain_dns
            WHERE domain_id = ? AND alias_id = ? AND owned_by = ? AND domain_dns_status <> ?
        ',
        $self->{'domain_id'}, 0, 'ext_mail_feature', 'todelete'
    );
    ref $rdata eq 'HASH' or die( $rdata );

    if (%{$rdata}) {
        my (@domainHosts, @wildcardHosts);

        # Add SPF records for external MX
        for(keys %{$rdata}) {
            (my $host = $rdata->{$_}->{'domain_text'}) =~ s/\d+\s+(.*)\.$/$1/;

            if (index( $rdata->{$_}->{'domain_dns'}, '*' ) != 0) {
                push @domainHosts, "a:$host";
            } else {
                push @wildcardHosts, "a:$host";
            }
        }

        if (@domainHosts) {
            push @{$self->{'named'}->{'SPF_RECORDS'}}, "@\tIN\tTXT\t\"v=spf1 mx @domainHosts -all\""
        }

        if (@wildcardHosts) {
            push @{$self->{'named'}->{'SPF_RECORDS'}}, "*\tIN\tTXT\t\"v=spf1 mx @wildcardHosts -all\""
        }
    }

    # We must trigger the Subdomain module whatever the number of entries - See #503
    $rdata = $db->doQuery(
        'dummy', 'UPDATE subdomain SET subdomain_status = ? WHERE subdomain_status <> ? AND domain_id = ?',
        'tochange', 'todelete', $self->{'domain_id'}
    );
    ref $rdata eq 'HASH' or die( $rdata );
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
    my $homeDir = File::Spec->canonpath( "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}" );

    $self->{'packages'} = {
        DOMAIN_ADMIN_ID       => $self->{'domain_admin_id'},
        ALIAS                 => $userName,
        DOMAIN_NAME           => $self->{'domain_name'},
        USER                  => $userName,
        GROUP                 => $groupName,
        HOME_DIR              => $homeDir,
        WEB_DIR               => $homeDir,
        FORWARD               => 'no',
        WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'}
    };
    %{$self->{'packages'}};
}

=item isValidCertificate($domainName)

 Does the SSL certificate which belongs to the domain is valid?

 Param string $domainName Domain name
 Return bool TRUE if the domain SSL certificate is valid, FALSE otherwise

=cut

sub isValidCertificate
{
    my ($self, $domainName) = @_;

    my $certFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$domainName.pem";
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
