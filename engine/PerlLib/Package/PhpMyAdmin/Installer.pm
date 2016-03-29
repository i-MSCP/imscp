=head1 NAME

Package::PhpMyAdmin::Installer - i-MSCP PhpMyAdmin package installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Package::PhpMyAdmin::Installer;

use strict;
use warnings;
use iMSCP::Database;
use iMSCP::Debug;
use Package::PhpMyAdmin;
use iMSCP::EventManager;
use iMSCP::TemplateParser;
use iMSCP::Composer;
use iMSCP::Execute;
use iMSCP::Rights;
use iMSCP::File;
use Package::FrontEnd;
use Servers::sqld;
use File::Basename;
use JSON;
use version;
use parent 'Common::SingletonClass';

%main::sqlUsers = () unless %main::sqlUsers;
@main::createdSqlUsers = () unless @main::createdSqlUsers;

=head1 DESCRIPTION

 i-MSCP PhpMyAdmin package installer.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners(\%eventManager)

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self, $eventManager) = @_;

    my $rs = $eventManager->register( 'beforeSetupDialog', sub {
            push @{$_[0]}, sub { $self->showDialog( @_ ) };
            0;
        } );
    $rs ||= $eventManager->register( 'afterFrontEndPreInstall', sub { $self->preinstall(); } );
    $rs ||= $eventManager->register( 'afterFrontEndInstall', sub { $self->install(); } );
}

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub showDialog
{
    my ($self, $dialog) = @_;

    my $dbUser = main::setupGetQuestion( 'PHPMYADMIN_SQL_USER', $self->{'config'}->{'DATABASE_USER'} || 'pma_user' );
    my $dbPass = main::setupGetQuestion( 'PHPMYADMIN_SQL_PASSWORD', $self->{'config'}->{'DATABASE_PASSWORD'} );
    my ($rs, $msg) = (0, '');

    if (grep($_ eq $main::reconfigure, ( 'sqlmanager', 'all', 'forced' ))
        || length $dbUser < 6 || length $dbUser > 16 || $dbUser !~ /^[\x21-\x5b\x5d-\x7e]+$/
        || length $dbPass < 6 || $dbPass !~ /^[\x21-\x5b\x5d-\x7e]+$/
    ) {
        # Ensure no special chars are present in password. If we don't, dialog will not let user set new password
        $dbPass = '';

        do {
            ($rs, $dbUser) = $dialog->inputbox( <<"EOF", $dbUser );

Please enter an username for the PhpMyAdmin SQL user:$msg
EOF
            if ($dbUser eq $main::imscpConfig{'DATABASE_USER'}) {
                $msg = "\n\n\\Z1You cannot reuse the i-MSCP SQL user '$dbUser'.\\Zn\n\nPlease try again:";
                $dbUser = '';
            } elsif (length $dbUser > 16) {
                $msg = "\n\n\\Username can be up to 16 characters long.\\Zn\n\nPlease try again:";
                $dbUser = '';
            } elsif (length $dbUser < 6) {
                $msg = "\n\n\\Z1Username must be at least 6 characters long.\\Zn\n\nPlease try again:";
                $dbUser = '';
            } elsif ($dbUser !~ /^[\x21-\x7e]+$/) {
                $msg = "\n\n\\Z1Only printable ASCII characters (excepted space) are allowed.\\Zn\n\nPlease try again:";
                $dbUser = '';
            }
        } while ($rs < 30 && !$dbUser);

        if ($rs < 30) {
            $msg = '';

            # Ask for the phpmyadmin restricted SQL user password unless we reuses existent SQL user
            unless (grep($_ eq $dbUser, ( keys %main::sqlUsers ))) {
                do {
                    ($rs, $dbPass) = $dialog->passwordbox( <<"EOF", $dbPass );

Please, enter a password for the phpmyadmin SQL user (blank for autogenerate):$msg
EOF
                    if ($dbPass ne '') {
                        if (length $dbPass < 6) {
                            $msg = "\n\n\\Z1Password must be at least 6 characters long.\\Zn\n\nPlease try again:";
                            $dbPass = '';
                        } elsif ($dbPass !~ /^[x21-\x7e]+$/) {
                            $msg = "\n\n\\Z1Only printable ASCII characters (excepted space) are allowed.\\Zn\n\nPlease try again:";
                            $dbPass = '';
                        } else {
                            $msg = '';
                        }
                    } else {
                        $msg = '';
                    }
                } while ($rs < 30 && $msg);
            } else {
                $dbPass = $main::sqlUsers{$dbUser};
            }

            if ($rs < 30) {
                unless ($dbPass) {
                    my @allowedChr = map { chr } (0x21 .. 0x7e);
                    $dbPass = '';
                    $dbPass .= $allowedChr[rand @allowedChr] for 1 .. 16;
                }

                $dialog->msgbox( <<"EOF" );

Password for the phpmyadmin SQL user set to: $dbPass
EOF
            }
        }
    }

    if ($rs < 30) {
        main::setupSetQuestion( 'PHPMYADMIN_SQL_USER', $dbUser );
        main::setupSetQuestion( 'PHPMYADMIN_SQL_PASSWORD', $dbPass );
        $main::sqlUsers{$dbUser} = $dbPass;
    }

    $rs;
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my $self = shift;

    my $version = version->parse( Servers::sqld->factory()->getVersion() ) >= version->parse( '5.5.0' )
          ? (version->parse( $self->_getPhpVersion() ) >= version->parse( '5.5.0' )
            ? '0.4.5.*@dev' : '0.4.0.*@dev'
        ) : '0.2.0.*@dev';

    my $rs = iMSCP::Composer->getInstance()->registerPackage( 'imscp/phpmyadmin', $version );
    $rs ||= $self->{'eventManager'}->register( 'afterFrontEndBuildConfFile', \&afterFrontEndBuildConfFile );
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $self = shift;

    my $rs = $self->_backupConfigFile(
        "$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self->{'config'}->{'PHPMYADMIN_CONF_DIR'}/config.inc.php"
    );
    $rs ||= $self->_installFiles();
    $rs ||= $self->_setupDatabase();
    $rs ||= $self->_setupSqlUser();
    $rs ||= $self->_generateBlowfishSecret();
    $rs ||= $self->_buildConfig();
    $rs ||= $self->_buildHttpdConfig();
    $rs ||= $self->_setVersion();
    $rs ||= $self->_saveConfig();
}

=item setGuiPermissions()

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
    return 0 unless -d "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma";

    my $panelUName = my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};

    setRights( "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma", {
            user => $panelUName, group => $panelGName, dirmode => '0550', filemode => '0440', recursive => 1
        } );
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

    return 0 unless grep($_ eq $tplName, ( '00_master.conf', '00_master_ssl.conf' ));

    $$tplContent = replaceBloc(
        "# SECTION custom BEGIN.\n",
        "# SECTION custom END.\n",
        "    # SECTION custom BEGIN.\n".
            getBloc(
                "# SECTION custom BEGIN.\n",
                "# SECTION custom END.\n",
                $$tplContent
            ).
            "    include imscp_pma.conf;\n".
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

 Return Package::PhpMyAdmin::Installer

=cut

sub _init
{
    my $self = shift;

    $self->{'phpmyadmin'} = Package::PhpMyAdmin->getInstance();
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = $self->{'phpmyadmin'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'config'} = $self->{'phpmyadmin'}->{'config'};

    my $oldConf = "$self->{'cfgDir'}/phpmyadmin.old.data";
    if (-f $oldConf) {
        tie my %oldConfig, 'iMSCP::Config', fileName => $oldConf;

        for my $oldConf(keys %oldConfig) {
            if (exists $self->{'config'}->{$oldConf}) {
                $self->{'config'}->{$oldConf} = $oldConfig{$oldConf};
            }
        }
    }

    $self;
}

=item _getPhpVersion()

 Get PHP version

 Return int PHP version on sucess, die on failure

=cut

sub _getPhpVersion
{
    my $self = shift;

    my $rs = execute( 'php -d date.timezone=UTC -v', \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    return $rs if $rs;

    $stdout =~ /PHP\s+([\d.]+)/ or die( sprintf( 'Could not find PHP version from `php -v` command output: %s',
            $stdout ) );
    $1;
}

=item _backupConfigFile()

 Backup the given configuration file

 Return int 0

=cut

sub _backupConfigFile
{
    my ($self, $cfgFile) = @_;

    return 0 unless -f $cfgFile && -d $self->{'bkpDir'};

    iMSCP::File->new( filename => $cfgFile )->copyFile( $self->{'bkpDir'}.'/'.fileparse( $cfgFile ).'.'.time );
}

=item _installFiles()

 Install files in production directory

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
    my $packageDir = "$main::imscpConfig{'CACHE_DATA_DIR'}/packages/vendor/imscp/phpmyadmin";

    unless (-d $packageDir) {
        error( "Couldn't find the imscp/phpmyadmin package into the packages cache directory" );
        return 1;
    }

    my $rs = execute( "rm -fR $main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma", \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $rs && $stderr;
    return $rs if $rs;

    $rs = execute( "cp -fR $packageDir $main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma", \$stdout, \$stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $rs && $stderr;
    $rs;
}

=item _saveConfig()

 Save configuration

 Return int 0 on success, other on failure

=cut

sub _saveConfig
{
    my $self = shift;

    iMSCP::File->new( filename => "$self->{'cfgDir'}/phpmyadmin.data" )->copyFile(
        "$self->{'cfgDir'}/phpmyadmin.old.data"
    );
}

=item _setupSqlUser()

 Setup restricted SQL user

 Return int 0 on success, other on failure

=cut

sub _setupSqlUser
{
    my $self = shift;

    my $sqlServer = Servers::sqld->factory();
    my $phpmyadminDbName = main::setupGetQuestion( 'DATABASE_NAME' ).'_pma';
    my $dbUser = main::setupGetQuestion( 'PHPMYADMIN_SQL_USER' );
    my $dbUserHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $dbPass = main::setupGetQuestion( 'PHPMYADMIN_SQL_PASSWORD' );
    my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};

    for my $sqlUser ($dbOldUser, $dbUser) {
        next if !$sqlUser || grep($_ eq "$sqlUser\@$dbUserHost", @main::createdSqlUsers);

        for my $host($dbUserHost, $main::imscpOldConfig{'DATABASE_USER_HOST'}) {
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

    my $db = iMSCP::Database->factory();

    # Give needed privileges to this SQL user

    my $rs = $db->doQuery( 'g', 'GRANT USAGE ON mysql.* TO ?@?', $dbUser, $dbUserHost );
    unless (ref $rs eq 'HASH') {
        error( sprintf( 'Could not add SQL privileges: %s', $rs ) );
        return 1;
    }

    $rs = $db->doQuery( 'g', 'GRANT SELECT ON mysql.db TO ?@?', $dbUser, $dbUserHost );
    unless (ref $rs eq 'HASH') {
        error( sprintf( 'Could not add SQL privileges: %s', $rs ) );
        return 1;
    }

    $rs = $db->doQuery(
        'g',
        '
            GRANT SELECT (Host, User, Select_priv, Insert_priv, Update_priv, Delete_priv, Create_priv, Drop_priv,
                Reload_priv, Shutdown_priv, Process_priv, File_priv, Grant_priv, References_priv, Index_priv,
                Alter_priv, Show_db_priv, Super_priv, Create_tmp_table_priv, Lock_tables_priv, Execute_priv,
                Repl_slave_priv, Repl_client_priv)
            ON mysql.user
            TO ?@?
        ',
        $dbUser, $dbUserHost
    );
    unless (ref $rs eq 'HASH') {
        error( sprintf( 'Could not add SQL privileges: %s', $rs ) );
        return 1;
    }

    # Check for mysql.host table existence (as for MySQL >= 5.6.7, the mysql.host table is no longer provided)
    $rs = $db->doQuery( '1', "SHOW tables FROM mysql LIKE 'host'" );
    unless (ref $rs eq 'HASH') {
        error( $rs );
        return 1;
    }

    if (%{$rs}) {
        $rs = $db->doQuery( 'g', 'GRANT SELECT ON mysql.user TO ?@?', $dbUser, $dbUserHost );
        unless (ref $rs eq 'HASH') {
            error( sprintf( 'Could not add SQL privileges: %s', $rs ) );
            return 1;
        }

        $rs = $db->doQuery(
            'g', 'GRANT SELECT (Host, Db, User, Table_name, Table_priv, Column_priv) ON mysql.tables_priv TO?@?',
            $dbUser, $dbUserHost
        );
        unless (ref $rs eq 'HASH') {
            error( sprintf( 'Could not add SQL privileges: %s', $rs ) );
            return 1;
        }
    }

    my $quotedDbName = $db->quoteIdentifier( $phpmyadminDbName );
    $rs = $db->doQuery( 'g', "GRANT ALL PRIVILEGES ON $quotedDbName.* TO ?@?", $dbUser, $dbUserHost );
    unless (ref $rs eq 'HASH') {
        error( sprintf( 'Could not add SQL privileges: %s', $rs ) );
        return 1;
    }

    $self->{'config'}->{'DATABASE_USER'} = $dbUser;
    $self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;
    0;
}

=item _setupDatabase()

 Setup database

 Return int 0 on success, other on failure

=cut

sub _setupDatabase
{
    my $self = shift;

    my $phpmyadminDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma";
    my $phpmyadminDbName = main::setupGetQuestion( 'DATABASE_NAME' ).'_pma';

    my $db = iMSCP::Database->factory();

    my $quotedDbName = $db->quoteIdentifier( $phpmyadminDbName );
    my $rs = $db->doQuery( '1', 'SHOW DATABASES LIKE ?', $phpmyadminDbName );
    unless (ref $rs eq 'HASH') {
        error( $rs );
        return 1;
    }

    if (%{$rs}) {
        $rs = $db->doQuery( '1', "SHOW TABLES FROM $quotedDbName" );
        unless (ref $rs eq 'HASH') {
            error( $rs );
            return 1;
        }
    }

    unless (%{$rs}) {
        $rs = $db->doQuery(
            'c', "CREATE DATABASE IF NOT EXISTS $quotedDbName CHARACTER SET utf8 COLLATE utf8_unicode_ci;"
        );
        unless (ref $rs eq 'HASH') {
            error( sprintf( "Could not create the PhpMyAdmin '%s' SQL database: %s", $phpmyadminDbName, $rs ) );
            return 1;
        }
    }

    # In any case (new install / upgrade) we execute queries from the create_tables.sql file. On upgrade, this will
    # create the missing tables

    my $schemaFile = "$phpmyadminDir/sql/create_tables.sql";
    $schemaFile = "$phpmyadminDir/examples/create_tables.sql" unless -f $schemaFile;

    $schemaFile = iMSCP::File->new( filename => $schemaFile )->get();
    unless (defined $schemaFile) {
        error( sprintf( 'Could not read %s', "$phpmyadminDir/examples/create_tables.sql" ) );
        return 1;
    }

    $schemaFile =~ s/^(--[^\n]{0,})?\n//gm;

    for my $sqlStmt(split /;\n/, $schemaFile) {
        # The PhpMyAdmin script contains the creation of the database as well
        # We ignore this part as the database has already been created
        if ($sqlStmt !~ /^CREATE DATABASE/ and $sqlStmt !~ /^USE/) {
            $rs = $db->doQuery( 'c', $sqlStmt );

            unless (ref $rs eq 'HASH') {
                error( sprintf( 'Could not execute SQL query: %s', $rs ) );
                return 1;
            }
        }
    }

    0;
}

=item _buildHttpdConfig()

 Build Httpd configuration

 Return int 0 on success, other on failure

=cut

sub _buildHttpdConfig
{
    my $frontEnd = Package::FrontEnd->getInstance();
    $frontEnd->buildConfFile(
        "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/PhpMyAdmin/config/nginx/imscp_pma.conf",
        { GUI_PUBLIC_DIR => $main::imscpConfig{'GUI_PUBLIC_DIR'} },
        { destination => "$frontEnd->{'config'}->{'HTTPD_CONF_DIR'}/imscp_pma.conf" }
    );
}

=item _setVersion()

 Set version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
    my $self = shift;

    my $json = iMSCP::File->new( filename => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma/composer.json" )->get();
    unless (defined $json) {
        error( sprintf( 'Could not read %s', "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma/composer.json" ) );
        return 1;
    }

    $json = decode_json( $json );
    debug( sprintf( 'Set new phpMyAdmin version to %s', $json->{'version'} ) );
    $self->{'config'}->{'PHPMYADMIN_VERSION'} = $json->{'version'};
    0;
}

=item _generateBlowfishSecret()

 Generate blowfish secret

 Return int 0

=cut

sub _generateBlowfishSecret
{
    my $blowfishSecret;
    $blowfishSecret .= (map { chr } (0x21 .. 0x7e))[rand( 70 )] for 1 .. 56;
    $_[0]->{'config'}->{'BLOWFISH_SECRET'} = $blowfishSecret;
    0;
}

=item _buildConfig()

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConfig
{
    my $self = shift;

    my $panelUName = my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};
    my $confDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self->{'config'}->{'PHPMYADMIN_CONF_DIR'}";
    my $dbName = main::setupGetQuestion( 'DATABASE_NAME' ).'_pma';
    (my $dbUser = main::setupGetQuestion( 'PHPMYADMIN_SQL_USER' )) =~ s%('|\\)%\\$1%g;
    my $dbHost = main::setupGetQuestion( 'DATABASE_HOST' );
    my $dbPort = main::setupGetQuestion( 'DATABASE_PORT' );
    (my $dbPass = main::setupGetQuestion( 'PHPMYADMIN_SQL_PASSWORD' )) =~ s%('|\\)%\\$1%g;
    (my $blowfishSecret = $self->{'config'}->{'BLOWFISH_SECRET'}) =~ s%('|\\)%\\$1%g;

    my $data = {
        PMA_DATABASE => $dbName,
        PMA_USER     => $dbUser,
        PMA_PASS     => $dbPass,
        HOSTNAME     => $dbHost,
        PORT         => $dbPort,
        UPLOADS_DIR  => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/uploads",
        TMP_DIR      => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/tmp",
        BLOWFISH     => $blowfishSecret
    };

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'phpmyadmin', 'imscp.config.inc.php', \ my $cfgTpl,
        $data );
    return $rs if $rs;

    unless (defined $cfgTpl) {
        $cfgTpl = iMSCP::File->new( filename => "$confDir/imscp.config.inc.php" )->get();
        unless (defined $cfgTpl) {
            error( sprintf( 'Could not read %s file', "$confDir/imscp.config.inc.php" ) );
            return 1;
        }
    }

    $cfgTpl = process( $data, $cfgTpl );

    my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/config.inc.php" );
    $rs = $file->set( $cfgTpl );
    $rs ||= $file->save();
    $rs ||= $file->mode( 0640 );
    $rs ||= $file->owner( $panelUName, $panelGName );
    $rs ||= $file->copyFile( "$confDir/config.inc.php" );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
