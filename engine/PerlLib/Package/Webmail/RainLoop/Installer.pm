=head1 NAME

Package::Webmail::RainLoop::Installer - i-MSCP RainLoop package installer

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package Package::Webmail::RainLoop::Installer;

use strict;
use warnings;
use File::Basename;
use iMSCP::Composer;
use iMSCP::Config;
use iMSCP::Crypt qw/ randomStr /;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Dialog::InputValidation;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::TemplateParser;
use JSON;
use Package::FrontEnd;
use Servers::sqld;
use parent 'Common::SingletonClass';

our $VERSION = '0.1.0.*@dev';

%main::sqlUsers = () unless %main::sqlUsers;
@main::createdSqlUsers = () unless @main::createdSqlUsers;

=head1 DESCRIPTION

 This is the installer for the i-MSCP RainLoop package.

 See Package::Webmail::RainLoop::RainLoop for more information.

=head1 PUBLIC METHODS

=over 4

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub showDialog
{
    my ($self, $dialog) = @_;

    my $masterSqlUser = main::setupGetQuestion( 'DATABASE_USER' );
    my $dbUser = main::setupGetQuestion('RAINLOOP_SQL_USER', $self->{'rainloop'}->{'config'}->{'DATABASE_USER'} || 'rainloop_user');
    my $dbPass = main::setupGetQuestion('RAINLOOP_SQL_PASSWORD', $self->{'rainloop'}->{'config'}->{'DATABASE_PASSWORD'});

    if ($main::reconfigure =~ /^(?:webmails|all|forced)$/
        || !isValidUsername($dbUser)
        || !isStringNotInList($dbUser, 'root', 'debian-sys-maint', $masterSqlUser)
        || !isValidPassword($dbPass)
    ) {
        my ($rs, $msg) = (0, '');

        do {
            ($rs, $dbUser) = $dialog->inputbox( <<"EOF", $dbUser );

Please enter a username for the RainLoop SQL user:$msg
EOF
            $msg = '';
            if (!isValidUsername($dbUser)
                || !isStringNotInList($dbUser, 'root', 'debian-sys-maint', $masterSqlUser)
            ) {
                $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
            }
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;

        if (isStringNotInList($dbUser, keys %main::sqlUsers)) {
            do {
                ($rs, $dbPass) = $dialog->inputbox( <<"EOF", $dbPass || randomStr(16, iMSCP::Crypt::ALNUM) );

Please enter a password for the RainLoop SQL user:$msg
EOF
                $msg = (isValidPassword($dbPass)) ? '' : $iMSCP::Dialog::InputValidation::lastValidationError;
            } while $rs < 30 && $msg;
            return $rs if $rs >= 30;
        } else {
            $dbPass = $main::sqlUsers{$dbUser};
        }
    }

    main::setupSetQuestion( 'RAINLOOP_SQL_USER', $dbUser );
    main::setupSetQuestion( 'RAINLOOP_SQL_PASSWORD', $dbPass );
    $main::sqlUsers{$dbUser} = $dbPass;
    0;
}

=item preinstall()

 Process preinstall tasks

 Return int 0

=cut

sub preinstall
{
    my $self = shift;

    my $rs = iMSCP::Composer->getInstance()->registerPackage( 'imscp/rainloop', $VERSION );
    $rs ||= $self->{'eventManager'}->register( 'afterFrontEndBuildConfFile', \&afterFrontEndBuildConfFile );
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $self = shift;

    my $rs = $self->_installFiles();
    $rs ||= $self->_mergeConfig();
    $rs ||= $self->_setupDatabase();
    $rs ||= $self->_buildConfig();
    $rs ||= $self->_buildHttpdConfig();
    $rs ||= $self->_setVersion();
    $rs ||= $self->_saveConfig();
}

=back

=head1 EVENT LISTENERS

=over 4

=item afterFrontEndBuildConfFile(\$tplContent, $filename)

 Include httpd configuration into frontEnd vhost files

 Param string \$tplContent Template file tplContent
 Param string $tplName Template name
 Return int 0 on success, other on failure

=cut

sub afterFrontEndBuildConfFile
{
    my ($tplContent, $tplName) = @_;

    return 0 unless $tplName =~ /^00_master(?:_ssl)?\.conf$/;

    $$tplContent = replaceBloc(
        "# SECTION custom BEGIN.\n",
        "# SECTION custom END.\n",
        "    # SECTION custom BEGIN.\n".
            getBloc(
                "# SECTION custom BEGIN.\n",
                "# SECTION custom END.\n",
                $$tplContent
            ).
            "    include imscp_rainloop.conf;\n".
            "    # SECTION custom END.\n",
        $$tplContent
    );
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Package::Webmail::RainLoop::Installer

=cut

sub _init
{
    my $self = shift;

    $self->{'rainloop'} = Package::Webmail::RainLoop::RainLoop->getInstance();
    $self->{'frontend'} = Package::FrontEnd->getInstance();
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self;
}

=item _installFiles()

 Install files

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
    my $self = shift;

    my $packageDir = "$main::imscpConfig{'IMSCP_HOMEDIR'}/packages/vendor/imscp/rainloop";

    unless (-d $packageDir) {
        error( "Couldn't find the imscp/rainloop package in the packages cache directory" );
        return 1;
    }

    my $destDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/rainloop";

    local $@;
    my $rs = eval {
        # Install new files
        iMSCP::Dir->new( dirname => "$packageDir/src" )->rcopy( "${destDir}-new" );

        # Copy new i-MSCP files
        iMSCP::Dir->new( dirname => "$packageDir/iMSCP/src" )->rcopy( "${destDir}-new" );

        if (-d $destDir) {
            my $dataSrcDir = "$destDir/data/_data_11c052c218cd2a2febbfb268624efdc1/_default_";

            # Copy files from previous installation
            if (-d "$dataSrcDir/storage") {
                iMSCP::Dir->new( dirname => "$dataSrcDir/storage" )->rcopy( "${destDir}-new/storage" );
            }

            # Remove files from previous installation
            iMSCP::Dir->new( dirname => $destDir )->remove( );

            # Remove file which are no longer needed
            for ('application.ini', 'plugin-imscp-change-password.ini') {
                next unless -f;
                my $rs = iMSCP::File->new( filename => "$self->{'rainloop'}->{'cfgDir'}/$_")->delFile();
                return $rs if $rs;
            }
        }

        # Replace old files by new files
        iMSCP::Dir->new( dirname => "${destDir}-new" )->moveDir( $destDir );

        # Copy configuration files
        iMSCP::Dir->new( dirname => "$packageDir/iMSCP/config" )->rcopy( $self->{'rainloop'}->{'cfgDir'} );
    };
    if ($@) {
        error($@);
        return 1;
    }
    $rs;
}

=item _mergeConfig

 Merge old config if any

 Return int 0

=cut

sub _mergeConfig
{
    my $self = shift;

    if (%{$self->{'rainloop'}->{'config'}}) {
        my %oldConfig = %{$self->{'rainloop'}->{'config'}};

        tie %{$self->{'rainloop'}->{'config'}}, 'iMSCP::Config', fileName => "$self->{'rainloop'}->{'cfgDir'}/rainloop.data";

        while(my ($key, $value) = each(%oldConfig)) {
            next unless exists $self->{'rainloop'}->{'config'}->{$key};
            $self->{'rainloop'}->{'config'}->{$key} = $value;
        }

        return 0;
    }

    tie %{$self->{'rainloop'}->{'config'}}, 'iMSCP::Config', fileName => "$self->{'rainloop'}->{'cfgDir'}/rainloop.data";
    0;
}

=item _setupDatabase()

 Setup database

 Return int 0 on success, other on failure

=cut

sub _setupDatabase
{
    my $self = shift;

    my $sqlServer = Servers::sqld->factory();
    my $imscpDbName = main::setupGetQuestion( 'DATABASE_NAME' );
    my $rainLoopDbName = $imscpDbName.'_rainloop';
    my $dbUser = main::setupGetQuestion( 'RAINLOOP_SQL_USER' );
    my $dbUserHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $oldDbUserHost = $main::imscpOldConfig{'DATABASE_USER_HOST'} || '';
    my $dbPass = main::setupGetQuestion( 'RAINLOOP_SQL_PASSWORD' );
    my $dbOldUser = $self->{'rainloop'}->{'config'}->{'DATABASE_USER'};

    my $db = iMSCP::Database->factory();
    my $quotedDbName = $db->quoteIdentifier( $rainLoopDbName );

    my $rs = $db->doQuery( 'c', "CREATE DATABASE IF NOT EXISTS $quotedDbName CHARACTER SET utf8 COLLATE utf8_unicode_ci" );
    unless (ref $rs eq 'HASH') {
        error( sprintf( 'Could not create SQL database: %s', $rs ) );
        return 1;
    }

    for my $sqlUser ($dbOldUser, $dbUser) {
        next if !$sqlUser || grep($_ eq "$sqlUser\@$dbUserHost", @main::createdSqlUsers);
        for my $host($dbUserHost, $oldDbUserHost) {
            next unless $host;
            $sqlServer->dropUser( $sqlUser, $host );
        }
    }

    # Create SQL user if not already created by another server/package installer
    unless (grep($_ eq "$dbUser\@$dbUserHost", @main::createdSqlUsers)) {
        debug( sprintf( 'Creating %s@%s SQL user', $dbUser, $dbUserHost ) );
        $sqlServer->createUser( $dbUser, $dbUserHost, $dbPass );
        push @main::createdSqlUsers, "$dbUser\@$dbUserHost";
    }

    # Give needed privileges to this SQL user
    $quotedDbName =~ s/([%_])/\\$1/g;
    $rs = $db->doQuery( 'g', "GRANT ALL PRIVILEGES ON $quotedDbName.* TO ?\@?", $dbUser, $dbUserHost );
    unless (ref $rs eq 'HASH') {
        error( sprintf( 'Could not add SQL privileges: %s', $rs ) );
        return 1;
    }

    # No need to escape wildcard characters. See https://bugs.mysql.com/bug.php?id=18660
    $quotedDbName = $db->quoteIdentifier( $imscpDbName );
    $rs = $db->doQuery(
        'g', "GRANT SELECT (mail_addr, mail_pass), UPDATE (mail_pass) ON $quotedDbName.mail_users TO ?\@?",
        $dbUser, $dbUserHost
    );
    unless (ref $rs eq 'HASH') {
        error( sprintf( 'Could not add SQL privileges: %s', $rs ) );
        return 1;
    }

    $self->{'rainloop'}->{'config'}->{'DATABASE_USER'} = $dbUser;
    $self->{'rainloop'}->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;
    0;
}

=item _buildConfig()

 Build RainLoop configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConfig
{
    my $self = shift;

    my $confDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/rainloop/data/_data_11c052c218cd2a2febbfb268624efdc1/_default_/configs";
    my $panelUName = my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};

    for my $confFile('application.ini', 'plugin-imscp-change-password.ini') {
        my $data = {
            DATABASE_NAME     => $confFile eq 'application.ini'
                ? main::setupGetQuestion( 'DATABASE_NAME' ).'_rainloop' : main::setupGetQuestion( 'DATABASE_NAME' ),
            DATABASE_HOST     => main::setupGetQuestion( 'DATABASE_HOST' ),
            DATATABASE_PORT   => main::setupGetQuestion( 'DATABASE_PORT' ),
            DATABASE_USER     => main::setupGetQuestion( 'RAINLOOP_SQL_USER' ),
            DATABASE_PASSWORD => main::setupGetQuestion( 'RAINLOOP_SQL_PASSWORD' ),
            DISTRO_CA_BUNDLE  => main::setupGetQuestion( 'DISTRO_CA_BUNDLE' ),
            DISTRO_CA_PATH    => main::setupGetQuestion( 'DISTRO_CA_PATH' )
        };

        my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'rainloop', $confFile, \ my $cfgTpl, $data );
        return $rs if $rs;

        unless (defined $cfgTpl) {
            $cfgTpl = iMSCP::File->new( filename => "$confDir/$confFile" )->get();
            unless (defined $cfgTpl) {
                error( sprintf( 'Could not read %s file', "$confDir/$confFile" ) );
                return 1;
            }
        }

        $cfgTpl = process( $data, $cfgTpl );

        my $file = iMSCP::File->new( filename => "$confDir/$confFile" );
        $rs = $file->set( $cfgTpl );
        $rs ||= $file->save();
        $rs ||= $file->mode( 0640 );
        $rs ||= $file->owner( $panelUName, $panelGName );
        return $rs if $rs;
    }

    0;
}

=item _setVersion()

 Set version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
    my $self = shift;

    my $packageDir = "$main::imscpConfig{'IMSCP_HOMEDIR'}/packages/vendor/imscp/rainloop";
    my $json = iMSCP::File->new( filename => "$packageDir/composer.json" )->get();
    unless (defined $json) {
        error( sprintf( 'Could not read %s file', "$packageDir/composer.json" ) );
        return 1;
    }

    $json = decode_json( $json );
    debug( sprintf( 'Set new rainloop version to %s', $json->{'version'} ) );
    $self->{'rainloop'}->{'config'}->{'RAINLOOP_VERSION'} = $json->{'version'};
    0;
}

=item _buildHttpdConfig()

 Build Httpd configuration

=cut

sub _buildHttpdConfig
{
    my $self = shift;

    $self->{'frontend'}->buildConfFile(
        "$self->{'rainloop'}->{'cfgDir'}/nginx/imscp_rainloop.conf",
        {
            GUI_PUBLIC_DIR => $main::imscpConfig{'GUI_PUBLIC_DIR'}
        },
        {
            destination => "$self->{'frontend'}->{'config'}->{'HTTPD_CONF_DIR'}/imscp_rainloop.conf"
        }
    );
}

=item _saveConfig()

 Save configuration

 Return int 0 on success, other on failure

=cut

sub _saveConfig
{
    my $self = shift;

    (tied %{$self->{'rainloop'}->{'config'}})->flush();

    iMSCP::File->new( filename => "$self->{'rainloop'}->{'cfgDir'}/rainloop.data" )->copyFile(
        "$self->{'rainloop'}->{'cfgDir'}/rainloop.old.data"
    );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
