=head1 NAME

 Servers::sqld::percona::installer - i-MSCP Percona server installer implementation

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

package Servers::sqld::percona::installer;

use strict;
use warnings;
use iMSCP::Crypt qw/ decryptRijndaelCBC /;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute qw/ execute /;
use iMSCP::File;
use iMSCP::TemplateParser qw/ processByRef /;
use Servers::sqld::percona;
use parent 'Servers::sqld::mysql::installer';

=head1 DESCRIPTION

 i-MSCP Percona server installer implementation.

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::sqld::percona:installer

=cut

sub _init
{
    my ($self) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'sqld'} = Servers::sqld::percona->getInstance();
    $self->{'cfgDir'} = $self->{'sqld'}->{'cfgDir'};
    $self->{'config'} = $self->{'sqld'}->{'config'};
    $self;
}

=item _setType( )

 Set SQL server type

 Return 0 on success, other on failure

=cut

sub _setType
{
    my ($self) = @_;

    debug( sprintf( 'SQL server type set to: %s', 'percona' ));
    $self->{'config'}->{'SQLD_TYPE'} = 'mysql';
    0;
}

=item _buildConf( )

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldBuildConf' );
    return $rs if $rs;

    my $rootUName = $main::imscpConfig{'ROOT_USER'};
    my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
    my $confDir = $self->{'config'}->{'SQLD_CONF_DIR'};

    # Make sure that the conf.d directory exists
    iMSCP::Dir->new( dirname => "$confDir/conf.d" )->make( {
            user  => $rootUName,
            group => $rootGName,
            mode  => 0755
        } );

    # Create the /etc/mysql/my.cnf file if missing
    unless ( -f "$confDir/my.cnf" ) {
        $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'mysql', 'my.cnf', \ my $cfgTpl, {} );
        return $rs if $rs;

        unless ( defined $cfgTpl ) {
            $cfgTpl = "!includedir $confDir/conf.d/\n";
        } elsif ( $cfgTpl !~ m%^!includedir\s+$confDir/conf.d/\n%m ) {
            $cfgTpl .= "!includedir $confDir/conf.d/\n";
        }

        my $file = iMSCP::File->new( filename => "$confDir/my.cnf" );
        $file->set( $cfgTpl );

        $rs = $file->save();
        $rs ||= $file->owner( $rootUName, $rootGName );
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'onLoadTemplate', 'mysql', 'imscp.cnf', \ my $cfgTpl, {} );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/imscp.cnf" )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read %s", "$self->{'cfgDir'}/imscp.cnf" ));
            return 1;
        }
    }

    $cfgTpl .= <<'EOF';
[mysqld]
performance_schema = 0
max_connections = 500
max_allowed_packet = 500M
sql_mode =
EOF

    $cfgTpl .= "innodb_use_native_aio = @{[ $self->_isMysqldInsideCt() ? 0 : 1 ]}\n";

    my $version = version->parse( "$self->{'config'}->{'SQLD_VERSION'}" );

    # Fix For: The 'INFORMATION_SCHEMA.SESSION_VARIABLES' feature is disabled; see the documentation for
    # 'show_compatibility_56' (3167) - Occurs when executing mysqldump with Percona server 5.7.x
    $cfgTpl .= "show_compatibility_56 = 1\n" if $version >= version->parse( '5.7.6' );
    # For backward compatibility - We will review this in later version
    $cfgTpl .= "default_password_lifetime = 0\n" if $version >= version->parse( '5.7.4' );
    $cfgTpl .= "event_scheduler = DISABLED\n";

    processByRef( { SQLD_SOCK_DIR => $self->{'config'}->{'SQLD_SOCK_DIR'} }, \$cfgTpl );

    my $file = iMSCP::File->new( filename => "$confDir/conf.d/imscp.cnf" );
    $file->set( $cfgTpl );

    $rs = $file->save();
    $rs ||= $file->owner( $rootUName, $rootGName );
    $rs ||= $file->mode( 0644 );
    $rs ||= $self->{'eventManager'}->trigger( 'afterSqldBuildConf' );
}

=item _updateServerConfig( )

 Update server configuration

  - Upgrade MySQL system tables if necessary
  - Disable unwanted plugins

 Return 0 on success, other on failure

=cut

sub _updateServerConfig
{
    my ($self) = @_;

    # Upgrade MySQL tables if necessary.

    {
        # Need to ignore SIGHUP, as otherwise a SIGHUP can sometimes abort the upgrade
        # process in the middle.
        #local $SIG{'HUP'} = 'IGNORE';

        my $mysqlConffile = File::Temp->new();
        print $mysqlConffile <<"EOF";
[mysql_upgrade]
host = @{[ main::setupGetQuestion( 'DATABASE_HOST' ) ]}
port = @{[ main::setupGetQuestion( 'DATABASE_PORT' ) ]}
user = "@{ [ main::setupGetQuestion( 'DATABASE_USER' ) =~ s/"/\\"/gr ] }"
password = "@{ [ decryptRijndaelCBC( $main::imscpDBKey, $main::imscpDBiv, main::setupGetQuestion( 'DATABASE_PASSWORD' )) =~ s/"/\\"/gr ] }"
EOF
        $mysqlConffile->flush();

        # Filter all "duplicate column", "duplicate key" and "unknown column"
        # errors as the command is designed to be idempotent.
        my $rs = execute( "/usr/bin/mysql_upgrade --defaults-file=$mysqlConffile 2>&1 | egrep -v '^(1|\@had|ERROR (1054|1060|1061))'", \my $stdout );
        error( sprintf( "Couldn't upgrade SQL server system tables: %s", $stdout )) if $rs;
        return $rs if $rs;
        debug( $stdout ) if $stdout;
    }

    # Disable unwanted plugins

    return 0 unless version->parse( "$self->{'config'}->{'SQLD_VERSION'}" ) >= version->parse( '5.6.6' );

    eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();
        local $dbh->{'RaiseError'};

        # Disable unwanted plugins (bc reasons)
        for ( qw/ cracklib_password_check simple_password_check validate_password / ) {
            $dbh->do( "UNINSTALL PLUGIN $_" ) if $dbh->selectrow_hashref( "SELECT name FROM mysql.plugin WHERE name = '$_'" );
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
