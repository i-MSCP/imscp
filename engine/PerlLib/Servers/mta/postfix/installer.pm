=head1 NAME

 Servers::mta::postfix::installer - i-MSCP Postfix MTA server installer implementation

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package Servers::mta::postfix::installer;

use strict;
use warnings;
use File::Basename;
use iMSCP::Config;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::TemplateParser;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use Servers::mta::postfix;
use version;
use parent 'Common::SingletonClass';

%main::sqlUsers = () unless %main::sqlUsers;
@main::createdSqlUsers = () unless @main::createdSqlUsers;

=head1 DESCRIPTION

 i-MSCP Postfix MTA server installer implementation.

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my $self = shift;

    my $rs = $self->_addUsersAndGroups();
    $rs ||= $self->_makeDirs();
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $self = shift;

    my $rs = $self->setVersion();
    $rs ||= $self->_createPostfixMaps();
    $rs ||= $self->_buildConf();
    $rs ||= $self->_buildAliasesDb();
    $rs ||= $self->_saveConf();
    $rs ||= $self->_oldEngineCompatibility();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::mta::postfix::installer

=cut

sub _init
{
    my $self = shift;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'mta'} = Servers::mta::postfix->getInstance();
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/postfix";
    $self->{'config'} = $self->{'mta'}->{'config'};

    # Be sure to work with newest conffile
    # Cover case where the conffile has been loaded prior installation of new files (even if discouraged)
    untie(%{$self->{'config'}});
    tie %{$self->{'config'}}, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/postfix.data";

    my $oldConf = "$self->{'cfgDir'}/postfix.old.data";
    if(-f $oldConf) {
        tie my %oldConfig, 'iMSCP::Config', fileName => $oldConf, readonly => 1;
        while(my($key, $value) = each(%oldConfig)) {
            next unless exists $self->{'config'}->{$key};
            $self->{'config'}->{$key} = $value;
        }
    }

    $self;
}

=item _addUsersAndGroups()

 Add users and groups

 Return int 0 on success, other on failure

=cut

sub _addUsersAndGroups
{
    my $self = shift;

    my @groups = (
        [
            $self->{'config'}->{'MTA_MAILBOX_GID_NAME'}, # Group name
            'yes' # Whether it's a system group
        ]
    );
    my @users = (
        [
            $self->{'config'}->{'MTA_MAILBOX_UID_NAME'}, # User name
            $self->{'config'}->{'MTA_MAILBOX_GID_NAME'}, # User primary group name
            'vmail_user', # Comment
            $self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}, # User homedir
            'yes', # Whether it's a system user
            [ $main::imscpConfig{'IMSCP_GROUP'} ] # Additional user group(s)
        ]
    );

    my @userToGroups = ();
    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddUsersAndGroups', \ @groups, \ @users, \ @userToGroups );
    return $rs if $rs;

    # Create groups
    my $systemGroup = iMSCP::SystemGroup->getInstance();
    for my $group(@groups) {
        $rs = $systemGroup->addSystemGroup( $group->[0], ($group->[1] eq 'yes') ? 1 : 0 );
        return $rs if $rs;
    }

    # Create users
    for my $user(@users) {
        my $systemUser = iMSCP::SystemUser->new();
        $systemUser->{'group'} = $user->[1];
        $systemUser->{'comment'} = $user->[2];
        $systemUser->{'home'} = $user->[3];
        $systemUser->{'system'} = 'yes' if $user->[4] eq 'yes';

        $rs = $systemUser->addSystemUser( $user->[0] );
        return $rs if $rs;

        if (defined $user->[5]) {
            for my $group(@{$user->[5]}) {
                $rs = $systemUser->addToGroup( $group );
                return $rs if $rs;
            }
        }
    }

    # User to groups
    for my $entry(@userToGroups) {
        my $systemUser = iMSCP::SystemUser->new();
        my $user = $entry->[0];
        for my $group(@{$entry->[1]}) {
            $rs = $systemUser->addToGroup( $group, $user );
            return $rs if $rs;
        }
    }

    $self->{'eventManager'}->trigger( 'afterMtaAddUsersAndGroups' );
}

=item _makeDirs()

 Create directories

 Return int 0 on success, other on failure

=cut

sub _makeDirs
{
    my $self = shift;

    my @directories = (
        [
            $self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}, # eg. /etc/postfix/imscp
            $main::imscpConfig{'ROOT_USER'},
            $main::imscpConfig{'ROOT_GROUP'},
            0750
        ],
        [
            $self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}, # eg. /var/mail/virtual
            $self->{'config'}->{'MTA_MAILBOX_UID_NAME'},
            $self->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            0750
        ]
    );

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaMakeDirs', \ @directories );

    # Make sure to start with clean directory
    $rs ||= iMSCP::Dir->new(dirname => $self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'})->remove();
    return $rs if $rs;

    for my $dir(@directories) {
        $rs = iMSCP::Dir->new( dirname => $dir->[0] )->make(
            {
                user           => $dir->[1],
                group          => $dir->[2],
                mode           => $dir->[3],
                fixpermissions => iMSCP::Getopt->fixPermissions
            }
        );
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterMtaMakeDirs' );
}

=item _buildConf()

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaBuildConf' );
    $rs ||= $self->_buildMasterCfFile();
    $rs ||= $self->_buildMainCfFile();
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaBuildConf' );
}

=item setVersion()

 Set Postfix version

 Return 0 on success, other on failure

=cut

sub setVersion
{
    my $self = shift;

    my $rs = execute( [ 'postconf', '-d', '-h', 'mail_version' ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    debug( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    chomp( $stdout );
    unless ($stdout =~ /^(\d+\.\d+\.\d+)$/) {
        error( 'Unexpected value returned by POSTCONF(1) command.' );
        return 1;
    }

    $self->{'config'}->{'POSTFIX_VERSION'} = $1;
    0;
}

=item _createPostfixMaps()

 Ceate postfix maps

 Return int 0 on success, other on failure

=cut

sub _createPostfixMaps
{
    my $self = shift;

    my @lookupTables = (
        $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'}, $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'},
        $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'}, $self->{'config'}->{'MTA_TRANSPORT_HASH'},
        $self->{'config'}->{'MTA_RELAY_HASH'}
    );

    my $rs = $self->{'eventManager'}->trigger( 'beforeCreatePostfixMaps', \ @lookupTables );
    return $rs if $rs;

    for (@lookupTables) {
        $rs = $self->{'mta'}->addMapEntry($_);
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterCreatePostfixMaps', \ @lookupTables );
}

=item _buildAliasesDb()

 Build aliases database

 Return int 0 on success, other on failure

=cut

sub _buildAliasesDb
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaBuildAliasesDb' );
    $rs ||= $self->{'eventManager'}->trigger( 'onLoadTemplate', 'postfix', 'aliases', \ my $cfgTpl, { } );
    return $rs if $rs;

    unless (defined $cfgTpl) {
        $cfgTpl = iMSCP::File->new( filename => $self->{'config'}->{'MTA_LOCAL_ALIAS_HASH'} )->get();
        $cfgTpl = '' unless defined $cfgTpl;
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeMtaBuildAliasesDbFile', \ $cfgTpl, 'aliases' );
    return $rs if $rs;

    # Add alias for local root user
    $cfgTpl =~ s/^root:.*\n//gim;
    $cfgTpl .= 'root: '.main::setupGetQuestion( 'DEFAULT_ADMIN_ADDRESS' )."\n";

    $rs = $self->{'eventManager'}->trigger( 'afterMtaBuildAliasesDbFile', \ $cfgTpl, 'aliases' );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'MTA_LOCAL_ALIAS_HASH'} );
    $rs = $file->set( $cfgTpl );
    $rs ||= $file->save();
    return $rs if $rs;

    $rs = execute( 'newaliases', \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    $self->{'eventManager'}->trigger( 'afterMtaBuildAliasesDb' );
}

=item _saveConf()

 Save configuration file

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
    my $self = shift;

    (tied %{$self->{'config'}})->flush();
    iMSCP::File->new( filename => "$self->{'cfgDir'}/postfix.data" )->copyFile( "$self->{'cfgDir'}/postfix.old.data" );
}

=item _buildMasterCfFile()

 Build master.cf file

 Return int 0 on success, other on failure

=cut

sub _buildMasterCfFile
{
    my $self = shift;

    my $data = {
        MTA_MAILBOX_UID_NAME => $self->{'config'}->{'MTA_MAILBOX_UID_NAME'},
        IMSCP_GROUP          => $main::imscpConfig{'IMSCP_GROUP'},
        ARPL_PATH            => $main::imscpConfig{'ROOT_DIR'}."/engine/messenger/imscp-arpl-msgr"
    };

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'postfix', 'master.cf', \ my $cfgTpl, $data );
    return $rs if $rs;

    unless (defined $cfgTpl) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/master.cf" )->get();
        unless (defined $cfgTpl) {
            error( sprintf( 'Could not read %s file', "$self->{'cfgDir'}/master.cf" ) );
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeMtaBuildMasterCfFile', \ $cfgTpl, 'master.cf' );
    return $rs if $rs;

    $cfgTpl = process( $data, $cfgTpl );

    $rs = $self->{'eventManager'}->trigger( 'afterMtaBuildMasterCfFile', \ $cfgTpl, 'master.cf' );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'POSTFIX_MASTER_CONF_FILE'} );
    $rs ||= $file->set( $cfgTpl );
    $rs ||= $file->save();
}

=item _buildMainCfFile()

 Build main.cf file

 Return int 0 on success, other on failure

=cut

sub _buildMainCfFile
{
    my $self = shift;

    my $baseServerIp = main::setupGetQuestion( 'BASE_SERVER_IP' );
    my $baseServerIpType = iMSCP::Net->getInstance->getAddrVersion( $baseServerIp );
    my $gid = getgrnam( $self->{'config'}->{'MTA_MAILBOX_GID_NAME'} );
    my $uid = getpwnam( $self->{'config'}->{'MTA_MAILBOX_UID_NAME'} );
    my $hostname = main::setupGetQuestion( 'SERVER_HOSTNAME' );
    my $data = {
        MTA_INET_PROTOCOLS       => $baseServerIpType,
        MTA_SMTP_BIND_ADDRESS    => $baseServerIpType eq 'ipv4' ? $baseServerIp : '',
        MTA_SMTP_BIND_ADDRESS6   => $baseServerIpType eq 'ipv6' ? $baseServerIp : '',
        MTA_HOSTNAME             => $hostname,
        MTA_LOCAL_DOMAIN         => "$hostname.local",
        MTA_VERSION              => $main::imscpConfig{'Version'},
        MTA_TRANSPORT_HASH       => $self->{'config'}->{'MTA_TRANSPORT_HASH'},
        MTA_LOCAL_MAIL_DIR       => $self->{'config'}->{'MTA_LOCAL_MAIL_DIR'},
        MTA_LOCAL_ALIAS_HASH     => $self->{'config'}->{'MTA_LOCAL_ALIAS_HASH'},
        MTA_VIRTUAL_MAIL_DIR     => $self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'},
        MTA_VIRTUAL_DMN_HASH     => $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'},
        MTA_VIRTUAL_MAILBOX_HASH => $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'},
        MTA_VIRTUAL_ALIAS_HASH   => $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'},
        MTA_RELAY_HASH           => $self->{'config'}->{'MTA_RELAY_HASH'},
        MTA_MAILBOX_MIN_UID      => $uid,
        MTA_MAILBOX_UID          => $uid,
        MTA_MAILBOX_GID          => $gid
    };

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'postfix', 'main.cf', \ my $cfgTpl, $data );
    return $rs if $rs;

    unless (defined $cfgTpl) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/main.cf" )->get();
        unless (defined $cfgTpl) {
            error( sprintf( 'Could not read %s file', "$self->{'cfgDir'}/main.cf" ) );
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeMtaBuildMainCfFile', \$cfgTpl, 'main.cf' );
    return $rs if $rs;

    $cfgTpl = process( $data, $cfgTpl );

    $rs = $self->{'eventManager'}->trigger( 'afterMtaBuildMainCfFile', \ $cfgTpl, 'main.cf' );

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'POSTFIX_CONF_FILE'} );
    $rs ||= $file->set( $cfgTpl );
    $rs ||= $file->save();
    return $rs if $rs;

    # Add TLS parameters if required
    return 0 unless main::setupGetQuestion( 'SERVICES_SSL_ENABLED' ) eq 'yes';

    $self->{'eventManager'}->register(
        'afterMtaBuildConf',
        sub {
            my %params = (
                # smtpd TLS parameters (opportunistic)
                smtpd_tls_security_level         => { action => 'replace', values => [ 'may' ] },
                smtpd_tls_ciphers                => { action => 'replace', values => [ 'high' ] },
                smtpd_tls_exclude_ciphers        => { action => 'replace', values => [ 'aNULL', 'MD5' ] },
                smtpd_tls_protocols              => { action => 'replace', values => [ '!SSLv2', '!SSLv3' ] },
                smtpd_tls_loglevel               => { action => 'replace', values => [ '0' ] },
                smtpd_tls_cert_file              => {
                    action => 'replace', values => [ "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem" ]
                },
                smtpd_tls_key_file               => {
                    action => 'replace', values => [ "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem" ]
                },
                smtpd_tls_auth_only              => { action => 'replace', values => [ 'no' ] },
                smtpd_tls_received_header        => { action => 'replace', values => [ 'yes' ] },
                smtpd_tls_session_cache_database => {
                    action => 'replace', values => [ 'btree:/var/lib/postfix/smtpd_scache' ]
                },
                smtpd_tls_session_cache_timeout  => { action => 'replace', values => [ '3600s' ] },
                # smtp TLS parameters (opportunistic)
                smtp_tls_security_level          => { action => 'replace', values => [ 'may' ] },
                smtp_tls_ciphers                 => { action => 'replace', values => [ 'high' ] },
                smtp_tls_exclude_ciphers         => { action => 'replace', values => [ 'aNULL', 'MD5' ] },
                smtp_tls_protocols               => { action => 'replace', values => [ '!SSLv2', '!SSLv3' ] },
                smtp_tls_loglevel                => { action => 'replace', values => [ '0' ] },
                smtp_tls_CAfile                  => {
                    action => 'replace', values => [ '/etc/ssl/certs/ca-certificates.crt' ]
                },
                smtp_tls_session_cache_database  => {
                    action => 'replace', values => [ 'btree:/var/lib/postfix/smtp_scache' ]
                }
            );

            if (version->parse( $self->{'config'}->{'POSTFIX_VERSION'} ) >= version->parse( '2.10.0' )) {
                $params{'smtpd_relay_restrictions'} = { action => 'replace', values => [ '' ], empty => 1 };
            }

            if (version->parse( $self->{'config'}->{'POSTFIX_VERSION'} ) >= version->parse( '3.0.0' )) {
                $params{'compatibility_level'} = { action => 'replace', values => [ '2' ] };
            }

            $self->{'mta'}->postconf( %params );
        }
    );
}

=item _oldEngineCompatibility()

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaOldEngineCompatibility' );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtadOldEngineCompatibility' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
