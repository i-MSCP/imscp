=head1 NAME

 iMSCP::Servers::Sqld::Percona::Abstract - i-MSCP Percona SQL server abstract implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Servers::Sqld::Percona::Abstract;

use strict;
use warnings;
use autouse 'iMSCP::Crypt' => qw/ decryptRijndaelCBC /;
use autouse 'iMSCP::Execute' => qw/ execute /;
use autouse 'iMSCP::TemplateParser' => qw/ processByRef /;
use Class::Autouse qw/ :nostat iMSCP::Database iMSCP::Dir iMSCP::File /;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Service;
use parent 'iMSCP::Servers::Sqld::Mysql::Abstract';

=head1 DESCRIPTION

 i-MSCP Percona SQL server abstract implementation.

=head1 PUBLIC METHODS

=over 4

=item postinstall( )

 See iMSCP::Servers::Sqld::Mysql::Abstract::Postinstall()

=cut

sub postinstall
{
    my ($self) = @_;

    eval { iMSCP::Service->getInstance()->enable( 'mysql' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->registerOne(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]}, [ sub { $self->restart(); }, 'Percona' ];
            0;
        },
        7
    );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _setVendor( )

 See iMSCP::Servers::Sqld::Mysql::Abstract::_setVendor()

=cut

sub _setVendor
{
    my ($self) = @_;

    debug( sprintf( 'SQL server vendor set to: %s', 'percona' ));
    $self->{'config'}->{'SQLD_VENDOR'} = 'mysql';
    0;
}

=item _buildConf( )

 See iMSCP::Servers::Sqld::Mysql::Abstract::_buildConf()

=cut

sub _buildConf
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePerconaBuildConf' );
    return $rs if $rs;

    eval {
        # Make sure that the conf.d directory exists
        iMSCP::Dir->new( dirname => "$self->{'config'}->{'SQLD_CONF_DIR'}/conf.d" )->make( {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => 0755
        } );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # Create the /etc/mysql/my.cnf file if missing
    unless ( -f "$self->{'config'}->{'SQLD_CONF_DIR'}/my.cnf" ) {
        $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'mysql', 'my.cnf', \ my $cfgTpl, {} );
        return $rs if $rs;

        unless ( defined $cfgTpl ) {
            $cfgTpl = "!includedir $self->{'config'}->{'SQLD_CONF_DIR'}/conf.d/\n";
        } elsif ( $cfgTpl !~ m%^!includedir\s+$self->{'config'}->{'SQLD_CONF_DIR'}/conf.d/\n%m ) {
            $cfgTpl .= "!includedir $self->{'config'}->{'SQLD_CONF_DIR'}/conf.d/\n";
        }

        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'SQLD_CONF_DIR'}/my.cnf" );
        $file->set( $cfgTpl );
        $rs = $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'onLoadTemplate', 'percona', 'imscp.cnf', \ my $cfgTpl, {} );
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

    my $file = iMSCP::File->new( filename => "$self->{'config'}->{'SQLD_CONF_DIR'}/conf.d/imscp.cnf" );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0644 );
    $rs ||= $self->{'eventManager'}->trigger( 'afterPerconaBuildConf' );
}

=item _updateServerConfig( )

 See iMSCP::Servers::Sqld::Mysql::Abstract::_updateServerConfig()

=cut

sub _updateServerConfig
{
    my ($self) = @_;

    # Upgrade MySQL tables if necessary.

    {
        # Need to ignore SIGHUP, as otherwise a SIGHUP can sometimes abort the upgrade
        # process in the middle.
        local $SIG{'HUP'} = 'IGNORE';

        my $mysqlConffile = File::Temp->new();
        print $mysqlConffile <<"EOF";
[mysql_upgrade]
host = @{[ main::setupGetQuestion( 'DATABASE_HOST' ) ]}
port = @{[ main::setupGetQuestion( 'DATABASE_PORT' ) ]}
user = "@{ [ main::setupGetQuestion( 'DATABASE_USER' ) =~ s/"/\\"/gr ] }"
password = "@{ [ decryptRijndaelCBC( $main::imscpKEY, $main::imscpIV, main::setupGetQuestion( 'DATABASE_PASSWORD' )) =~ s/"/\\"/gr ] }"
EOF
        $mysqlConffile->close();

        my $rs = execute( "/usr/bin/mysql_upgrade --defaults-extra-file=$mysqlConffile", \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        error( sprintf( "Couldn't upgrade SQL server system tables: %s", $stderr || 'Unknown error' )) if $rs;
        return $rs if $rs;
    }

    # Disable unwanted plugins

    return 0 if version->parse( "$self->{'config'}->{'SQLD_VERSION'}" ) < version->parse( '5.6.6' );

    eval {
        my $dbh = iMSCP::Database->getInstance()->getRawDb();
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
