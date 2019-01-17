=head1 NAME

 Servers::po::courier::installer - i-MSCP Courier IMAP/POP3 Server installer implementation

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package Servers::po::courier::installer;

use strict;
use warnings;
use File::Basename;
use File::Spec;
use File::Temp;
use iMSCP::Boolean;
use iMSCP::Config;
use iMSCP::Crypt qw/ ALNUM randomStr /;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dialog::InputValidation;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute qw/ execute executeNoWait /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Mount qw/ addMountEntry isMountpoint mount umount /;
use iMSCP::ProgramFinder;
use iMSCP::Stepper;
use iMSCP::SystemUser;
use iMSCP::TemplateParser qw/ process replaceBloc /;
use iMSCP::Umask '$UMASK';
use Servers::mta::postfix;
use Servers::po::courier;
use Servers::sqld;
use Try::Tiny;
use parent 'Common::SingletonClass';

%::SQL_USERS = () unless %::SQL_USERS;

=head1 DESCRIPTION

 i-MSCP Courier IMAP/POP3 Server installer implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%em )

 Register setup event listeners

 Param iMSCP::EventManager \%em
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( $self, $em ) = @_;

    my $rs = $em->register( 'beforeSetupDialog', sub {
        push @{ $_[0] }, sub { $self->authdaemonSqlUserDialog( @_ ) };
        0;
    } );
    $rs ||= $em->register( 'beforeMtaBuildMainCfFile', sub { $self->configurePostfix( @_ ); } );
    $rs ||= $em->register( 'beforeMtaBuildMasterCfFile', sub { $self->configurePostfix( @_ ); } );
}

=item authdaemonSqlUserDialog(\%dialog)

 Authdaemon SQL user dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub authdaemonSqlUserDialog
{
    my ( $self, $dialog ) = @_;

    my $masterSqlUser = ::setupGetQuestion( 'DATABASE_USER' );
    my $dbUser = ::setupGetQuestion( 'AUTHDAEMON_SQL_USER', $self->{'config'}->{'AUTHDAEMON_DATABASE_USER'} || 'imscp_srv_user' );
    my $dbUserHost = ::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $dbPass = ::setupGetQuestion(
        'AUTHDAEMON_SQL_PASSWORD', ( iMSCP::Getopt->preseed ? randomStr( 16, ALNUM ) : $self->{'config'}->{'AUTHDAEMON_DATABASE_PASSWORD'} )
    );

    if ( iMSCP::Getopt->reconfigure =~ /^(?:po|servers|all|forced)$/ || !isValidUsername( $dbUser )
        || !isStringNotInList( $dbUser, 'root', 'debian-sys-maint', $masterSqlUser, 'vlogger_user' ) || !isValidPassword( $dbPass )
        || !isAvailableSqlUser( $dbUser )
    ) {
        my ( $rs, $msg ) = ( 0, '' );

        do {
            ( $rs, $dbUser ) = $dialog->inputbox( <<"EOF", $dbUser );

Please enter an username for the Courier Authdaemon SQL user:$msg
EOF
            $msg = '';
            if ( !isValidUsername( $dbUser ) || !isStringNotInList( $dbUser, 'root', 'debian-sys-maint', $masterSqlUser, 'vlogger_user' )
                || !isAvailableSqlUser( $dbUser )
            ) {
                $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
            }
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;

        unless ( defined $::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
            do {
                ( $rs, $dbPass ) = $dialog->inputbox( <<"EOF", $dbPass || randomStr( 16, ALNUM ));

Please enter a password for the Courier Authdaemon SQL user:$msg
EOF
                $msg = isValidPassword( $dbPass ) ? '' : $iMSCP::Dialog::InputValidation::lastValidationError;
            } while $rs < 30 && $msg;
            return $rs if $rs >= 30;

            $::sqlUsers{$dbUser . '@' . $dbUserHost} = $dbPass;
        } else {
            $dbPass = $::sqlUsers{$dbUser . '@' . $dbUserHost};
        }
    } elsif ( defined $::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
        $dbPass = $::sqlUsers{$dbUser . '@' . $dbUserHost};
    } else {
        $::sqlUsers{$dbUser . '@' . $dbUserHost} = $dbPass;
    }

    ::setupSetQuestion( 'AUTHDAEMON_SQL_USER', $dbUser );
    ::setupSetQuestion( 'AUTHDAEMON_SQL_PASSWORD', $dbPass );
    0;
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    my $rs = $self->_setupAuthdaemonSqlUser();
    $rs ||= $self->_buildConf();
    $rs ||= $self->_setupSASL();
    $rs ||= $self->_migrateFromDovecot();
    $rs ||= $self->_oldEngineCompatibility();
}

=back

=head1 EVENT LISTENERS

=over 4

=item configurePostfix( \$fileContent, $file )

 Injects configuration for both, maildrop MDA and Cyrus SASL in Postfix configuration files.

 Listener that listen on the following events:
  - beforeMtaBuildMainCfFile
  - beforeMtaBuildMasterCfFile

 Param string \$fileC Configuration file content
 Param string $file Configuration filename
 Return int 0 on success, other on failure

=cut

sub configurePostfix
{
    my ( $self, $fileC, $file ) = @_;

    if ( $file eq 'main.cf' ) {
        return $self->{'eventManager'}->register( 'afterMtaBuildConf', sub {
            $self->{'mta'}->postconf( (
                # Maildrop MDA parameters
                virtual_transport                      => {
                    action => 'replace',
                    values => [ 'maildrop' ]
                },
                maildrop_destination_concurrency_limit => {
                    action => 'replace',
                    values => [ '2' ]
                },
                maildrop_destination_recipient_limit   => {
                    action => 'replace',
                    values => [ '1' ]
                },
                # Cyrus SASL parameters
                smtpd_sasl_type                        => {
                    action => 'replace',
                    values => [ 'cyrus' ]
                },
                smtpd_sasl_path                        => {
                    action => 'replace',
                    values => [ 'smtpd' ]
                },
                smtpd_sasl_auth_enable                 => {
                    action => 'replace',
                    values => [ 'yes' ]
                },
                smtpd_sasl_security_options            => {
                    action => 'replace',
                    values => [ 'noanonymous' ]
                },
                smtpd_sasl_authenticated_header        => {
                    action => 'replace',
                    values => [ 'yes' ]
                },
                broken_sasl_auth_clients               => {
                    action => 'replace',
                    values => [ 'yes' ]
                },
                # SMTP restrictions
                smtpd_helo_restrictions                => {
                    action => 'add',
                    values => [ 'permit_sasl_authenticated' ],
                    after  => qr/permit_mynetworks/
                },
                smtpd_sender_restrictions              => {
                    action => 'add',
                    values => [ 'permit_sasl_authenticated' ],
                    after  => qr/permit_mynetworks/
                },
                smtpd_recipient_restrictions           => {
                    action => 'add',
                    values => [ 'permit_sasl_authenticated' ],
                    after  => qr/permit_mynetworks/
                }
            ));
        } );
    }

    if ( $file eq 'master.cf' ) {
        ${ $fileC } .= process(
            {
                MAILDROP_DELIVER_PATH => $self->{'config'}->{'MAILDROP_DELIVER_PATH'},
                MAILDROP_QUOTA_FILE   => "$self->{'cfgDir'}/maildrop-quota-warning",
                MTA_MAILBOX_UID_NAME  => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'},
                MTA_MAILBOX_GID_NAME  => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'}
            },
            <<'EOF'
maildrop  unix  -       n       n       -       -       pipe
 flags=DRhu user={MTA_MAILBOX_UID_NAME}:{MTA_MAILBOX_GID_NAME} argv={MAILDROP_DELIVER_PATH} -W {MAILDROP_QUOTA_FILE} -w 90 -d ${user}@${nexthop} ${extension} ${recipient} ${user} ${nexthop} ${sender}
EOF
        );
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::po::courier::installer

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'po'} = Servers::po::courier->getInstance();
    $self->{'mta'} = Servers::mta::postfix->getInstance();
    $self->{'cfgDir'} = $self->{'po'}->{'cfgDir'};
    $self->{'config'} = $self->{'po'}->{'config'};
    $self;
}

=item _setupAuthdaemonSqlUser( )

 Setup authdaemon SQL user

 Return int 0 on success, other on failure

=cut

sub _setupAuthdaemonSqlUser
{
    my ( $self ) = @_;

    try {
        my $dbName = ::setupGetQuestion( 'DATABASE_NAME' );
        my $dbUser = ::setupGetQuestion( 'AUTHDAEMON_SQL_USER' );
        my $dbUserHost = ::setupGetQuestion( 'DATABASE_USER_HOST' );
        my $dbPass = ::setupGetQuestion( 'AUTHDAEMON_SQL_PASSWORD' );

        if ( length $self->{'config'}->{'AUTHDAEMON_DATABASE_USER'} && length $::imscpOldConfig{'DATABASE_USER_HOST'}
            && $dbUser . $dbUserHost ne $self->{'config'}->{'AUTHDAEMON_DATABASE_USER'} . $::imscpOldConfig{'DATABASE_USER_HOST'}
            && !exists $::SQL_USERS{$self->{'config'}->{'AUTHDAEMON_DATABASE_USER'} . $::imscpOldConfig{'DATABASE_USER_HOST'}}
        ) {
            Servers::sqld->factory()->dropUser( $self->{'config'}->{'DATABASE_USER'}, $::imscpOldConfig{'DATABASE_USER_HOST'} );
        }

        unless ( exists $::SQL_USERS{$dbUser . $dbUserHost} ) {
            Servers::sqld->factory()->createUser( $dbUser, $dbUserHost, $dbPass );
            undef $::SQL_USERS{$dbUser . $dbUserHost};
        }

        iMSCP::Database->factory()->getConnector()->run( fixup => sub {
            # Backslash in database name must not be escaped. See https://bugs.mysql.com/bug.php?id=18660
            $_->do( "GRANT SELECT ON @{ [ $_->quote_identifier( $dbName ) ] }.mail_users TO ?\@?", undef, $dbUser, $dbUserHost );
        } );

        @{ $self->{'config'} }{qw/ AUTHDAEMON_DATABASE_USER AUTHDAEMON_DATABASE_PASSWORD /} = ( $dbUser, $dbPass );
        0;
    } catch {
        error( $_ );
        1;
    };
}

=item _buildConf( )

 Build courier configuration files

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
    my ( $self ) = @_;

    my $rs = $self->_buildDHparametersFile();
    $rs ||= $self->_buildAuthdaemonrcFile();
    $rs ||= $self->_buildSslConfFiles();
    return $rs if $rs;

    my $data = {
        DATABASE_HOST        => ::setupGetQuestion( 'DATABASE_HOST' ),
        DATABASE_PORT        => ::setupGetQuestion( 'DATABASE_PORT' ),
        DATABASE_USER        => $self->{'config'}->{'AUTHDAEMON_DATABASE_USER'},
        DATABASE_PASSWORD    => $self->{'config'}->{'AUTHDAEMON_DATABASE_PASSWORD'},
        DATABASE_NAME        => ::setupGetQuestion( 'DATABASE_NAME' ),
        HOST_NAME            => ::setupGetQuestion( 'SERVER_HOSTNAME' ),
        MTA_MAILBOX_UID      => ( scalar getpwnam( $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'} ) ),
        MTA_MAILBOX_GID      => ( scalar getgrnam( $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'} ) ),
        MTA_VIRTUAL_MAIL_DIR => $self->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}
    };

    my %cfgFiles = (
        authmysqlrc => [
            "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/authmysqlrc", # Dest path
            $self->{'config'}->{'AUTHDAEMON_USER'},                # Owner
            $self->{'config'}->{'AUTHDAEMON_GROUP'},               # Group
            0640                                                   # Permissions
        ]
    );

    {
        local $UMASK = 027; # authmysqlrc file must not be created/copied world-readable

        for my $conffile ( keys %cfgFiles ) {
            $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'courier', $conffile, \my $cfgTpl, $data );
            return $rs if $rs;

            unless ( defined $cfgTpl ) {
                $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/$conffile" )->get();
                return 1 unless defined $cfgTpl;
            }

            $rs = $self->{'eventManager'}->trigger( 'beforePoBuildConf', \$cfgTpl, $conffile );
            return $rs if $rs;

            $cfgTpl = process( $data, $cfgTpl );

            $rs = $self->{'eventManager'}->trigger( 'afterPoBuildConf', \$cfgTpl, $conffile );
            return $rs if $rs;

            my $file = iMSCP::File->new( filename => $cfgFiles{$conffile}->[0] );
            $file->set( $cfgTpl );

            $rs = $file->save();
            $rs ||= $file->owner( $cfgFiles{$conffile}->[1], $cfgFiles{$conffile}->[2] );
            $rs ||= $file->mode( $cfgFiles{$conffile}->[3] );
            return $rs if $rs;
        }
    }

    if ( -f "$self->{'cfgDir'}/imapd.local" ) {
        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'COURIER_CONF_DIR'}/imapd" );
        my $fileContent = $file->get();
        return 1 unless defined $fileContent;

        $fileContent = replaceBloc(
            qr/(:?^\n)?# Servers::po::courier::installer - BEGIN\n/m, qr/# Servers::po::courier::installer - ENDING\n/, '', $fileContent
        );

        $fileContent .= <<"EOF";

# Servers::po::courier::installer - BEGIN
. $self->{'cfgDir'}/imapd.local
# Servers::po::courier::installer - ENDING
EOF
        $file->set( $fileContent );
        $rs = $file->save();
        $rs ||= $file->owner( $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'} );
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    0;
}

=item _setupSASL( )

 Setup SASL for Postfix

 Return int 0 on success, other on failure

=cut

sub _setupSASL
{
    my ( $self ) = @_;

    try {
        # Add postfix user in 'mail' group to make it able to access
        # authdaemon rundir
        my $rs = iMSCP::SystemUser->new()->addToGroup(
            $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'}, $self->{'mta'}->{'config'}->{'POSTFIX_USER'}
        );
        return $rs if $rs;

        # Mount authdaemond socket directory in Postfix chroot
        # Postfix won't be able to connect to socket located outside of its chroot
        my $fsSpec = File::Spec->canonpath( $self->{'config'}->{'AUTHLIB_SOCKET_DIR'} );
        my $fsFile = File::Spec->canonpath( "$self->{'mta'}->{'config'}->{'POSTFIX_QUEUE_DIR'}/$self->{'config'}->{'AUTHLIB_SOCKET_DIR'}" );
        my $fields = { fs_spec => $fsSpec, fs_file => $fsFile, fs_vfstype => 'none', fs_mntops => 'bind,slave' };

        iMSCP::Dir->new( dirname => $fsFile )->make();

        $rs = addMountEntry( "$fields->{'fs_spec'} $fields->{'fs_file'} $fields->{'fs_vfstype'} $fields->{'fs_mntops'}" );
        $rs ||= mount( $fields ) unless isMountpoint( $fields->{'fs_file'} );

        # Build Cyrus SASL smtpd.conf configuration file

        $rs ||= $self->{'eventManager'}->trigger( 'onLoadTemplate', 'courier', 'smtpd.conf', \my $cfgTpl );
        return $rs if $rs;

        unless ( defined $cfgTpl ) {
            $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/sasl/smtpd.conf" )->get();
            return 1 unless defined $cfgTpl;
        }

        $cfgTpl = process(
            {
                PWCHECK_METHOD  => $self->{'config'}->{'PWCHECK_METHOD'},
                LOG_LEVEL       => $self->{'config'}->{'LOG_LEVEL'},
                MECH_LIST       => $self->{'config'}->{'MECH_LIST'},
                AUTHDAEMON_PATH => $self->{'config'}->{'AUTHDAEMON_PATH'}
            },
            $cfgTpl
        );

        local $UMASK = 027; # smtpd.conf file must not be created/copied world-readable

        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'SASL_CONF_DIR'}/smtpd.conf" );
        $file->set( $cfgTpl );
        $rs = $file->save();
        $rs ||= $file->owner( $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'} );
        $rs ||= $file->mode( 0640 );
    } catch {
        error( $_ );
        1;
    };
}

=item _buildDHparametersFile( )

 Build the DH parameters file with a stronger size (2048 instead of 768)

 Fix: #IP-1401
 Return int 0 on success, other on failure

=cut

sub _buildDHparametersFile
{
    my ( $self ) = @_;

    return 0 unless iMSCP::ProgramFinder::find( 'certtool' ) || iMSCP::ProgramFinder::find( 'mkdhparams' );

    if ( -f "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/dhparams.pem" ) {
        my $rs = execute(
            [ 'openssl', 'dhparam', '-in', "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/dhparams.pem", '-text', '-noout' ], \my $stdout, \my $stderr
        );
        debug( $stderr || 'Unknown error' ) if $rs;
        if ( $rs == 0 && $stdout =~ /\((\d+)\s+bit\)/ && $1 >= 2048 ) {
            return 0; # Don't regenerate file if not needed
        }

        $rs = iMSCP::File->new( filename => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/dhparams.pem" )->delFile();
        return $rs if $rs;
    }

    startDetail();

    my $rs = step(
        sub {
            my ( $tmpFile, $cmd );

            if ( iMSCP::ProgramFinder::find( 'certtool' ) ) {
                $tmpFile = File::Temp->new( UNLINK => 0 );
                $cmd = "certtool --generate-dh-params --sec-param medium > $tmpFile";
            } else {
                $cmd = 'DH_BITS=2048 mkdhparams';
            }

            my $output = '';
            my $outputHandler = sub {
                return if $_[0] =~ /^[.+]/;
                $output .= $_[0];
                step( undef, "Generating DH parameter file\n\n$output", 1, 1 );
            };

            my $rs = executeNoWait( $cmd, ( iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose ? undef : $outputHandler ), $outputHandler );
            error( $output || 'Unknown error' ) if $rs;
            $rs ||= iMSCP::File->new( filename => $tmpFile->filename )->moveFile( "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/dhparams.pem" ) if $tmpFile;
            $rs;
        }, 'Generating DH parameter file', 1, 1
    );
    endDetail();
    $rs;
}

=item _buildAuthdaemonrcFile( )

 Build the authdaemonrc file

 Return int 0 on success, other on failure

=cut

sub _buildAuthdaemonrcFile
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'courier', 'authdaemonrc', \my $cfgTpl, {} );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/authdaemonrc" )->get();
        return 1 unless defined $cfgTpl;
    }

    $rs = $self->{'eventManager'}->trigger( 'beforePoBuildAuthdaemonrcFile', \$cfgTpl, 'authdaemonrc' );
    return $rs if $rs;

    $cfgTpl =~ s/authmodulelist=".*"/authmodulelist="authmysql"/;

    $rs = $self->{'eventManager'}->trigger( 'afterPoBuildAuthdaemonrcFile', \$cfgTpl, 'authdaemonrc' );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/authdaemonrc" );
    $file->set( $cfgTpl );

    $rs = $file->save();
    $rs ||= $file->owner( $self->{'config'}->{'AUTHDAEMON_USER'}, $self->{'config'}->{'AUTHDAEMON_GROUP'} );
    $rs ||= $file->mode( 0660 );
}

=item _buildSslConfFiles( )

 Build ssl configuration file

 Return int 0 on success, other on failure

=cut

sub _buildSslConfFiles
{
    my ( $self ) = @_;

    return 0 unless ::setupGetQuestion( 'SERVICES_SSL_ENABLED', 'no' ) eq 'yes';

    for ( $self->{'config'}->{'COURIER_IMAP_SSL'}, $self->{'config'}->{'COURIER_POP_SSL'} ) {
        my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'courier', $_, \my $cfgTpl, {} );
        return $rs if $rs;

        unless ( defined $cfgTpl ) {
            $cfgTpl = iMSCP::File->new( filename => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/$_" )->get();
            return 1 unless defined $cfgTpl;
        }

        $rs = $self->{'eventManager'}->trigger( 'beforePoBuildSslConfFile', \$cfgTpl, $_ );
        return $rs if $rs;

        if ( $cfgTpl =~ /^TLS_CERTFILE=/gm ) {
            $cfgTpl =~ s!^(TLS_CERTFILE=).*!$1$::imscpConfig{'CONF_DIR'}/imscp_services.pem!gm;
        } else {
            $cfgTpl .= "TLS_CERTFILE=$::imscpConfig{'CONF_DIR'}/imscp_services.pem\n";
        }

        $rs = $self->{'eventManager'}->trigger( 'afterPoBuildSslConfFile', \$cfgTpl, $_ );
        return $rs if $rs;

        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/$_" );
        $file->set( $cfgTpl );

        $rs = $file->save();
        $rs ||= $file->owner( $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'} );
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    0;
}

=item _migrateFromDovecot( )

 Migrate mailboxes from Dovecot

 Return int 0 on success, other on failure

=cut

sub _migrateFromDovecot
{
    my ( $self ) = @_;

    return 0 unless $::imscpOldConfig{'PO_SERVER'} eq 'dovecot';

    my $rs = $self->{'eventManager'}->trigger( 'beforePoMigrateFromDovecot' );
    return $rs if $rs;

    $rs = execute(
        [
            'perl', "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlVendor/courier-dovecot-migrate.pl", '--to-courier', '--quiet', '--convert', '--overwrite',
            '--recursive', $self->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}
        ],
        \my $stdout, \my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;

    unless ( $rs ) {
        $self->{'po'}->{'forceMailboxesQuotaRecalc'} = TRUE;
        $::imscpOldConfig{'PO_SERVER'} = 'courier';
        $::imscpOldConfig{'PO_PACKAGE'} = 'Servers::po::courier';
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPoMigrateFromDovecot' );
}

=item _oldEngineCompatibility( )

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
    my ( $self ) = @_;

    try {

        if ( -f "$self->{'cfgDir'}/courier.old.data" ) {
            my $rs = iMSCP::File->new( filename => "$self->{'cfgDir'}/courier.old.data" )->delFile();
            return $rs if $rs;
        }

        if ( -f "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/userdb" ) {
            my $file = iMSCP::File->new( filename => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/userdb" );
            $file->set( '' );
            my $rs = $file->save();
            $rs ||= $file->mode( 0600 );
            return $rs if $rs;

            $rs = execute( [ 'makeuserdb', '-f', "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/userdb" ], \my $stdout, \my $stderr );
            debug( $stdout ) if $stdout;
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs if $rs;
        }

        # Remove postfix user from authdaemon group.
        # It is now added in mail group (since 1.5.0)
        my $rs = iMSCP::SystemUser->new()->removeFromGroup( $self->{'config'}->{'AUTHDAEMON_GROUP'}, $self->{'mta'}->{'config'}->{'POSTFIX_USER'} );
        return $rs if $rs;

        # Remove old authdaemon socket private/authdaemon mount directory.
        # Replaced by var/run/courier/authdaemon (since 1.5.0)
        my $fsFile = File::Spec->canonpath( "$self->{'mta'}->{'config'}->{'POSTFIX_QUEUE_DIR'}/private/authdaemon" );
        $rs ||= umount( $fsFile );
        return $rs if $rs;

        iMSCP::Dir->new( dirname => $fsFile )->remove();
    } catch {
        error( $_ );
        1;
    };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
