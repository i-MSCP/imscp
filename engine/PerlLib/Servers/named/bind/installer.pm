=head1 NAME

 Servers::named::bind::installer - i-MSCP Bind9 Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by internet Multi Server Control Panel
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

package Servers::named::bind::installer;

use strict;
use warnings;
use File::Basename;
use iMSCP::Boolean;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute 'execute';
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Net;
use iMSCP::ProgramFinder;
use iMSCP::Service;
use iMSCP::Umask '$UMASK';
use Servers::named::bind;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Installer for the i-MSCP Bind9 Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%events )

 Register setup event listeners

 Param iMSCP::EventManager \%events
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( $self, $events ) = @_;

    $events->registerOne( 'beforeSetupDialog', sub {
        push @{ $_[0] },
            sub { $self->_dialogForDnsServerType( @_ ) },
            sub { $self->_dialogForMasterDnsServerIps( @_ ) },
            sub { $self->_dialogForSlaveDnsServerIps( @_ ) },
            sub { $self->_dialogForDnsServerIpv6Support( @_ ) },
            sub { $self->_dialogForLocalResolving( @_ ) };
        0;
    } );
}

=item preinstall( )

 Pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    for my $configVar (
        qw/ BIND_IPV6 BIND_MODE LOCAL_DNS_RESOLVER PRIMARY_DNS SECONDARY_DNS /
    ) {
        $self->{'config'}->{$configVar} = ::setupGetQuestion( $configVar );
    }

    # Ubuntu 20.04 has renamed service unit from bind9 to named
    $self->{'config'}->{'NAMED_SERVICE'} = ( $::imscpConfig{'DISTRO_CODENAME'} eq 'focal' ) ? 'named' : 'bind9';

    0;
}

=item install( )

 Installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    for my $conffile (
        'BIND_CONF_DEFAULT_FILE',
        'BIND_CONF_FILE',
        'BIND_LOCAL_CONF_FILE',
        'BIND_OPTIONS_CONF_FILE'
    ) {
        if ( $self->{'config'}->{$conffile} ne '' ) {
            my $rs = $self->_bkpConfFile( $self->{'config'}->{$conffile} );
            return $rs if $rs;
        }
    }

    my $rs = $self->_makeDirs();
    $rs ||= $self->_buildConf();
    $rs ||= $self->_oldEngineCompatibility();
}

=item postinstall( )

 Post-installation tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ( $self ) = @_;

    local $@;
    eval { iMSCP::Service->getInstance()->enable(
        $self->{'config'}->{'NAMED_SERVICE'}
    ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{ $_[0] }, [ sub { $self->{'named'}->restart(); }, 'Bind9' ];
            0;
        },
        100
    );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::named::bind::installer

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'events'} = iMSCP::EventManager->getInstance();
    $self->{'named'} = Servers::named::bind->getInstance();
    $self->{'cfgDir'} = "$::imscpConfig{'CONF_DIR'}/bind";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'config'} = $self->{'named'}->{'config'};
    $self;
}

=item _dialogForDnsServerType( \%dialog )

 Dialog for DNS server type

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForDnsServerType
{
    my ( $self, $dialog ) = @_;

    my $value = ::setupGetQuestion(
        'BIND_MODE',
        length $self->{'config'}->{'BIND_MODE'}
            ? $self->{'config'}->{'BIND_MODE'}
            : 'master'
    );

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ named servers all / )
        && grep ( $value eq $_, qw/ master slave / )
    ) {
        ::setupSetQuestion( 'BIND_MODE', $value );
        return 20;
    }

    my %choices = (
        Master => 'master',
        Slave  => 'slave'
    );

    ( my $ret, $value ) = $dialog->select(
        <<"EOF", \%choices, $value eq 'slave' ? 'slave' : 'master' );
Please select the DNS server type to configure
EOF
    return 30 if $ret == 30;

    ::setupSetQuestion( 'BIND_MODE', $value );
    0;
}

=item _dialogForMasterDnsServerIps( \%dialog )

 Dialog for master DNS server IP addresses

 In master mode, the base server public IP will be set.
 In slave mode, user will be asked for master DNS server IP addresses.

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForMasterDnsServerIps
{
    my ( $self, $dialog ) = @_;

    if ( 'master' eq ::setupGetQuestion(
        'BIND_MODE', $self->{'config'}->{'BIND_MODE'}
    ) ) {
        # In master DNS mode, the local DNS server is the master DNS server
        ::setupSetQuestion(
            'PRIMARY_DNS', ::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' )
        );
        return 20;
    }

    my @values = split /(?:[;,]| )/, ::setupGetQuestion(
        'PRIMARY_DNS',
        length $self->{'config'}->{'PRIMARY_DNS'}
            ? $self->{'config'}->{'PRIMARY_DNS'}
            : 'no'
    );

    # IF the local DNS server was previously the master DNS server, we
    # need remove the base server public IP from the list of master DNS
    # server IP addresses. In slave mode, the local DNS server MUST not
    # act as master DNS server.
    if ( "@values" eq ::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' ) ) {
        @values = ();
    }

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ named servers all / )
        && length "@values"
        && ( "@values" eq 'no' || $self->_checkIps( @values ) )
    ) {
        ::setupSetQuestion( 'PRIMARY_DNS', "@values" );
        return 20;
    }

    my $slaveDnsIp = ::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' );
    my ( $ret, $msg ) = ( 0, '' );
    do {
        ( $ret, my $value ) = $dialog->string( <<"EOF", "@values" );
${msg}Please enter the master DNS server IP addresses, each space separated:
EOF
        if ( $ret != 30 ) {
            @values = grep /\S/, split ' ', $value;

            unless ( @values ) {
                $msg = "\\Z1You must enter at least one IP address.\\Zn\n\n";
            } elsif ( grep ( $slaveDnsIp eq $_, @values ) ) {
                $msg = sprintf(
                    "\\Z1TThe %s IP address is that of the slave DNS server.\\Zn\n\n",
                    $slaveDnsIp
                );
            } elsif ( !$self->_checkIps( @values ) ) {
                $msg = "\\Z1Invalid or disallowed IP address found.\\Zn\n\n";
            } else {
                $msg = '';
            }
        }
    } while $ret != 30 && length $msg;

    ::setupSetQuestion( 'PRIMARY_DNS', "@values" );
    0;
}

=item _dialogForSlaveDnsServerIps( \%dialog )

 Dialog for slave DNS server IP addresses

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForSlaveDnsServerIps
{
    my ( $self, $dialog ) = @_;

    if ( 'slave' eq ::setupGetQuestion(
        'BIND_MODE', $self->{'config'}->{'BIND_MODE'}
    ) ) {
        # In slave DNS mode, the local DNS server is one of slave DNS servers
        ::setupSetQuestion(
            'SECONDARY_DNS', ::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' )
        );
        return 20;
    }

    my @values = split /(?:[;,]| )/, ::setupGetQuestion(
        'SECONDARY_DNS',
        length $self->{'config'}->{'SECONDARY_DNS'}
            ? $self->{'config'}->{'SECONDARY_DNS'}
            : 'no'
    );

    # IF the local DNS server was previously the slave DNS server, we
    # need remove the base server public IP from the list of slave DNS
    # server IP addresses. In master mode, the local DNS server MUST not
    # act as slave DNS server.
    if ( "@values" eq ::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' ) ) {
        @values = ();
    }

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ named servers all / )
        && length "@values"
        && ( "@values" eq 'no' || $self->_checkIps( @values ) )
    ) {
        ::setupSetQuestion( 'SECONDARY_DNS', "@values" );
        return 20;
    }

    FIRST_DIALOG:
    my $ret = $dialog->boolean( <<"EOF", !!grep ( "@values" eq $_, '', 'no' ));
Do you want to add slave DNS servers?
EOF
    return 30 if $ret == 30;

    if ( $ret ) {
        ::setupSetQuestion( 'SECONDARY_DNS', 'no' );
        return 0;
    }

    my $masterDnsIp = ::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' );
    my $msg = '';
    do {
        ( $ret, my $value ) = $dialog->string( <<"EOF", "@values" );
${msg}Please enter the slave DNS server IP addresses, each separated by space:
EOF
        if ( $ret != 30 ) {
            @values = grep /\S/, split ' ', $value;

            unless ( length $value ) {
                $msg = "\\Z1You must enter at least one IP address.\\Zn\n\n";
            } elsif ( grep ( $masterDnsIp eq $_, @values ) ) {
                $msg = sprintf(
                    "\\Z1TThe %s IP address is that of the master DNS server.\\Zn\n\n",
                    $masterDnsIp
                );
            } elsif ( !$self->_checkIps( @values ) ) {
                $msg = "\\Z1Wrong or disallowed IP address found.\\Zn\n\n";
            } else {
                $msg = '';
            }
        }
    } while $ret != 30 && length $msg;
    goto FIRST_DIALOG if $ret == 30;

    ::setupSetQuestion( 'SECONDARY_DNS', "@values" );
    0;
}

=item _dialogForDnsServerIpv6Support( \%dialog )

 Dialog for DNS server IPv6 support

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForDnsServerIpv6Support
{
    my ( $self, $dialog ) = @_;

    unless ( ::setupGetQuestion( 'IPV6_SUPPORT', FALSE ) ) {
        ::setupSetQuestion( 'BIND_IPV6', 'no' );
        return 20;
    }

    my $value = ::setupGetQuestion(
        'BIND_IPV6',
        length $self->{'config'}->{'BIND_IPV6'}
            ? $self->{'config'}->{'BIND_IPV6'}
            : 'no'
    );

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ named servers all / )
        && grep ( $value eq $_, qw/ yes no / )
    ) {
        ::setupSetQuestion( 'BIND_IPV6', $value );
        return 20;
    }

    my $ret = $dialog->boolean( <<"EOF", $value ne 'yes' );
Do you want to enable the IPv6 support for your DNS server?
EOF
    return 30 if $ret == 30;

    ::setupSetQuestion( 'BIND_IPV6', $ret ? 'no' : 'yes' );
    0;
}

=item _dialogForLocalResolving( \%dialog )

 Dialog for local resolving

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForLocalResolving
{
    my ( $self, $dialog ) = @_;

    my $value = ::setupGetQuestion(
        'LOCAL_DNS_RESOLVER',
        length $self->{'config'}->{'LOCAL_DNS_RESOLVER'}
            ? $self->{'config'}->{'LOCAL_DNS_RESOLVER'}
            : 'yes'
    );

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ resolver named all / )
        && grep ( $value eq $_, qw/ yes no /)
    ) {
        ::setupSetQuestion( 'LOCAL_DNS_RESOLVER', $value );
        return 20;
    }

    my $ret = $dialog->boolean( <<"EOF", $value eq 'no' );
Do you want to use the local DNS server for local resolving?
EOF
    return 30 if $ret == 30;

    ::setupSetQuestion( 'LOCAL_DNS_RESOLVER', $ret ? 'no' : 'yes' );
    0;
}

=item _bkpConfFile( $cfgFile )

 Backup configuration file

 Param string $cfgFile Configuration file path
 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
    my ( $self, $cfgFile ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeNamedBkpConfFile', $cfgFile );
    return $rs if $rs;

    if ( -f $cfgFile ) {
        my $file = iMSCP::File->new( filename => $cfgFile );
        my $filename = basename( $cfgFile );
        unless ( -f "$self->{'bkpDir'}/$filename.system" ) {
            $rs = $file->copyFile( "$self->{'bkpDir'}/$filename.system" );
            return $rs if $rs;
        } else {
            $rs = $file->copyFile( "$self->{'bkpDir'}/$filename." . time );
            return $rs if $rs;
        }
    }

    $self->{'events'}->trigger( 'afterNamedBkpConfFile', $cfgFile );
}

=item _makeDirs( )

 Create directories

 Return int 0 on success, other on failure

=cut

sub _makeDirs
{
    my ( $self ) = @_;

    my @dirs = (
        [
            $self->{'config'}->{'BIND_CONF_DIR'},
            $::imscpConfig{'ROOT_USER'},
            $self->{'config'}->{'BIND_GROUP'},
            02750,
        ],
        [
            $self->{'config'}->{'BIND_DB_ROOT_DIR'},
            $::imscpConfig{'ROOT_USER'},
            $self->{'config'}->{'BIND_GROUP'},
            02770
        ],
        [
            $self->{'config'}->{'BIND_DB_MASTER_DIR'},
            $::imscpConfig{'ROOT_USER'},
            $self->{'config'}->{'BIND_GROUP'},
            02750
        ],
        [
            $self->{'config'}->{'BIND_DB_SLAVE_DIR'},
            $::imscpConfig{'ROOT_USER'},
            $self->{'config'}->{'BIND_GROUP'},
            02750
        ]
    );

    my $rs = $self->{'events'}->trigger( 'beforeNamedMakeDirs', \@dirs );
    return $rs if $rs;

    local $@;
    eval {
        for my $dir ( @dirs ) {
            iMSCP::Dir->new( dirname => $dir->[0] )->make( {
                user  => $dir->[1],
                group => $dir->[2],
                mode  => $dir->[3]
            } );
        }

        iMSCP::Dir->new(
            dirname => $self->{'config'}->{'BIND_DB_MASTER_DIR'}
        )->clear();

        if ( $self->{'config'}->{'BIND_MODE'} ne 'slave' ) {
            iMSCP::Dir->new(
                dirname => $self->{'config'}->{'BIND_DB_SLAVE_DIR'}
            )->clear();
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->trigger( 'afterNamedMakeDirs', \@dirs );
}

=item _buildConf( )

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
    my ( $self ) = @_;

    # default conffile (Debian/Ubuntu specific)
    if ( $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'}
        && -f $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'}
    ) {
        my $tplName = basename(
            $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'}
        );
        my $rs = $self->{'events'}->trigger(
            'onLoadTemplate', 'bind', $tplName, \my $tplContent, {}
        );
        return $rs if $rs;

        unless ( defined $tplContent ) {
            $tplContent = iMSCP::File->new(
                filename => $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'}
            )->get();
            return 1 unless defined $tplContent;
        }

        # Enable/disable local DNS resolver
        $tplContent =~ s/RESOLVCONF=(?:no|yes)/RESOLVCONF=$self->{'config'}->{'LOCAL_DNS_RESOLVER'}/i;

        # Fix for #IP-1333
        my $service = iMSCP::Service->getInstance();
        if ( $service->isSystemd() ) {
            if ( $self->{'config'}->{'LOCAL_DNS_RESOLVER'} eq 'yes' ) {
                $service->enable( 'bind9-resolvconf' );
            } else {
                $service->stop( 'bind9-resolvconf' );
                $service->disable( 'bind9-resolvconf' );
            }
        }

        # Enable/disable IPV6 support
        if ( $tplContent =~ /OPTIONS="(.*)"/ ) {
            ( my $options = $1 ) =~ s/\s*-[46]\s*//g;
            $options = '-4 ' . $options
                unless $self->{'config'}->{'BIND_IPV6'} eq 'yes';
            $tplContent =~ s/OPTIONS=".*"/OPTIONS="$options"/;
        }

        $rs = $self->{'events'}->trigger(
            'afterNamedBuildConf', \$tplContent, $tplName
        );
        return $rs if $rs;

        my $file = iMSCP::File->new(
            filename => "$self->{'wrkDir'}/$tplName"
        );
        $file->set( $tplContent );

        $rs = $file->save();
        $rs ||= $file->owner(
            $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'}
        );
        $rs ||= $file->mode( 0644 );
        $rs ||= $file->copyFile(
            $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'}
        );
        return $rs if $rs;
    }

    # option conffile
    if ( $self->{'config'}->{'BIND_OPTIONS_CONF_FILE'} ) {
        my $tplName = basename( $self->{'config'}->{'BIND_OPTIONS_CONF_FILE'} );
        my $rs = $self->{'events'}->trigger(
            'onLoadTemplate', 'bind', $tplName, \my $tplContent, {}
        );
        return $rs if $rs;

        unless ( defined $tplContent ) {
            $tplContent = iMSCP::File->new( filename => "$self->{'cfgDir'}/$tplName" )->get();
            return 1 unless defined $tplContent;
        }

        if ( $self->{'config'}->{'BIND_IPV6'} eq 'no' ) {
            $tplContent =~ s/listen-on-v6\s+\{\s+any;\s+\};/listen-on-v6 { none; };/;
        }

        my $namedVersion = $self->_getVersion();
        unless ( defined $namedVersion ) {
            error( "Couldn't retrieve named (Bind9) version" );
            return 1;
        }

        if ( version->parse( $namedVersion ) >= version->parse( '9.9.3' ) ) {
            $tplContent =~ s%//\s+(check-spf\s+ignore;)%$1%;
        }

        $rs = $self->{'events'}->trigger( 'afterNamedBuildConf', \$tplContent, $tplName );
        return $rs if $rs;

        my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$tplName" );
        $file->set( $tplContent );

        local $UMASK = 027;
        $rs = $file->save();
        $rs ||= $file->owner(
            $::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'}
        );
        $rs ||= $file->mode( 0640 );
        $rs ||= $file->copyFile(
            $self->{'config'}->{'BIND_OPTIONS_CONF_FILE'}
        );
        return $rs if $rs;
    }

    # master conffile
    if ( $self->{'config'}->{'BIND_CONF_FILE'} ) {
        my $tplName = basename( $self->{'config'}->{'BIND_CONF_FILE'} );
        my $rs = $self->{'events'}->trigger(
            'onLoadTemplate', 'bind', $tplName, \my $tplContent, {}
        );
        return $rs if $rs;

        unless ( defined $tplContent ) {
            $tplContent = iMSCP::File->new(
                filename => "$self->{'cfgDir'}/$tplName"
            )->get();
            return 1 unless defined $tplContent;
        }

        unless ( -f "$self->{'config'}->{'BIND_CONF_DIR'}/bind.keys" ) {
            $tplContent =~ s%include\s+\Q"$self->{'config'}->{'BIND_CONF_DIR'}\E/bind.keys";\n%%;
        }

        $rs = $self->{'events'}->trigger(
            'afterNamedBuildConf', \$tplContent, $tplName
        );
        return $rs if $rs;

        my $file = iMSCP::File->new(
            filename => "$self->{'wrkDir'}/$tplName"
        );
        $file->set( $tplContent );

        local $UMASK = 027;
        $rs = $file->save();
        $rs ||= $file->owner(
            $::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'}
        );
        $rs ||= $file->mode( 0640 );
        $rs ||= $file->copyFile( $self->{'config'}->{'BIND_CONF_FILE'} );
        return $rs if $rs;
    }

    # local conffile
    if ( $self->{'config'}->{'BIND_LOCAL_CONF_FILE'} ) {
        my $tplName = basename( $self->{'config'}->{'BIND_LOCAL_CONF_FILE'} );
        my $rs = $self->{'events'}->trigger(
            'onLoadTemplate', 'bind', $tplName, \my $tplContent, {}
        );
        return $rs if $rs;

        unless ( defined $tplContent ) {
            $tplContent = iMSCP::File->new(
                filename => "$self->{'cfgDir'}/$tplName"
            )->get();
            return 1 unless defined $tplContent;
        }

        $rs = $self->{'events'}->trigger(
            'afterNamedBuildConf', \$tplContent, $tplName
        );
        return $rs if $rs;

        my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$tplName" );
        $file->set( $tplContent );

        local $UMASK = 027;
        $rs = $file->save();
        $rs ||= $file->owner(
            $::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'}
        );
        $rs ||= $file->mode( 0640 );
        $rs ||= $file->copyFile( $self->{'config'}->{'BIND_LOCAL_CONF_FILE'} );
        return $rs if $rs;
    }

    0;
}

=item _checkIps( @ips )

 Check IP addresses

 Param list @ips List of IP addresses to check
 Return bool TRUE if all IPs are valid, FALSE otherwise

=cut

sub _checkIps
{
    my ( undef, @ips ) = @_;

    my $net = iMSCP::Net->getInstance();

    for my $ipAddr ( @ips ) {
        return 0 unless $net->isValidAddr( $ipAddr )
            && $net->getAddrType( $ipAddr ) =~ /^(?:PRIVATE|UNIQUE-LOCAL-UNICAST|PUBLIC|GLOBAL-UNICAST)$/;
    }

    1;
}

=item _getVersion( )

 Get named version

 Return string on success, undef on failure

=cut

sub _getVersion
{
    my ( $self ) = @_;

    my $rs = execute( [ $self->{'config'}->{'NAMED'}, '-v' ], \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;

    return $1 if !$rs && $stdout =~ /^BIND\s+([0-9.]+)/;
    undef;
}

=item _oldEngineCompatibility( )

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeNamedOldEngineCompatibility' );
    return $rs if $rs;

    if ( -f "$self->{'cfgDir'}/bind.old.data" ) {
        $rs = iMSCP::File->new( filename => "$self->{'cfgDir'}/bind.old.data" )->delFile();
        return $rs if $rs;
    }

    if ( iMSCP::ProgramFinder::find( 'resolvconf' ) ) {
        $rs = execute( "resolvconf -d lo.imscp", \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
    }

    local $@;
    eval {
        iMSCP::Dir->new(
            dirname => $self->{'config'}->{'BIND_DB_ROOT_DIR'}
        )->clear(
            undef, qr/\.db$/
        );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->trigger( 'afterNamedOldEngineCompatibility' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
