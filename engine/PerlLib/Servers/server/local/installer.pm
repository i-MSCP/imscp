=head1 NAME

 Servers::server::local::installer - i-MSCP local server implementation

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

package Servers::server::local::installer;

use strict;
use warnings;
use iMSCP::Database;
use DateTime::TimeZone;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dialog::InputValidation;
use iMSCP::Execute qw/ execute /;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Net;
use Net::LibIDN qw/ idn_to_ascii idn_to_unicode /;
use LWP::Simple qw/ get /;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP local server implementation

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%eventManager )

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self, $eventManager) = @_;

    # Must be done here because installers can rely on this configuration parameter
    main::setupSetQuestion( 'IPV6_SUPPORT', -f '/proc/net/if_inet6' ? 1 : 0 );

    $eventManager->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]},
                sub { $self->hostnameDialog( @_ ) },
                sub { $self->primaryIpDialog( @_ ) },
                sub { $self->timezoneDialog( @_ ) };
            0;
        },
        # We register these dialogs with a hightest priority to show them before any other server/package dialog
        999
    );
}

=item hostnameDialog( \%dialog )

 Ask for server hostname

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub hostnameDialog
{
    my (undef, $dialog) = @_;

    my $hostname = main::setupGetQuestion( 'SERVER_HOSTNAME' );

    if ( $main::reconfigure =~ /^(?:local_server|system_hostname|hostnames|all|forced)$/
        || !isValidHostname( $hostname )
    ) {
        chomp( $hostname = $hostname || `hostname --fqdn 2>/dev/null` || '' );
        $hostname = idn_to_unicode( $hostname, 'utf-8' );

        my ($rs, $msg) = ( 0, '' );
        do {
            ( $rs, $hostname ) = $dialog->inputbox( <<"EOF", $hostname );

Please enter your server fully qualified hostname:$msg
EOF
            $msg = isValidHostname( $hostname ) ? '' : $iMSCP::Dialog::InputValidation::lastValidationError;
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;
    }

    main::setupSetQuestion( 'SERVER_HOSTNAME', idn_to_ascii( $hostname, 'utf-8' ));
    0;
}

=item primaryIpDialog( \%dialog )

 Ask for server primary IP

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub primaryIpDialog
{
    my (undef, $dialog) = @_;

    my @ipList = sort
        grep(
            isValidIpAddr( $_, qr/(?:PRIVATE|UNIQUE-LOCAL-UNICAST|PUBLIC|GLOBAL-UNICAST)/ ),
            iMSCP::Net->getInstance()->getAddresses()
        ),
        'None';
    unless ( @ipList ) {
        error( "Couldn't get list of server IP addresses. At least one IP address must be configured." );
        return 1;
    }

    my $lanIP = main::setupGetQuestion( 'BASE_SERVER_IP' );
    $lanIP = 'None' if $lanIP eq '0.0.0.0';
    my $wanIP = main::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' );

    if ( iMSCP::Getopt->preseed
        && !$wanIP
        && ( !isValidIpAddr( $lanIP, qr/(?:PUBLIC|GLOBAL-UNICAST)/ ) )
    ) {
        chomp( $wanIP = get( 'https://ipinfo.io/ip' ) || '' );
    }

    if ( $main::reconfigure =~ /^(?:local_server|primary_ip|all|forced)$/
        || !grep( $_ eq $lanIP, @ipList )
        || ( $wanIP ne $lanIP && !isValidIpAddr( $wanIP, qr/(?:PUBLIC|GLOBAL-UNICAST)/ ) )
    ) {
        my ($rs, $msg) = ( 0, '' );

        do {
            ( $rs, $lanIP ) = $dialog->radiolist(
                <<"EOF", [ @ipList ], grep( $_ eq $lanIP, @ipList ) ? $lanIP : $ipList[0] );

Please select your server primary IP address:

The \\Zb`None'\\Zn option means that i-MSCP will configures the services to listen on all interfaces.
Note that this options is more suitable for Cloud computing services such as Scaleway and Amazon EC2.
EOF
            $lanIP = '0.0.0.0' if $lanIP && $lanIP eq 'None';
        } while $rs < 30 && !isValidIpAddr( $lanIP );
        return $rs if $rs >= 30;

        # IP inside private IP range?
        if ( !isValidIpAddr( $lanIP, qr/(?:PUBLIC|GLOBAL-UNICAST)/ ) ) {
            chomp( $wanIP = get( 'https://ipinfo.io/ip' ) || '' ) unless $wanIP;

            do {
                ( $rs, $wanIP ) = $dialog->inputbox( <<"EOF", $wanIP );

The IP address that you have selected is in private IP range.

Please enter your public IP address (WAN IP), or leave blank to force usage of the private IP address:$msg
EOF
                $msg = '';
                if ( $wanIP
                    && $wanIP ne $lanIP
                    && !isValidIpAddr( $wanIP, qr/(?:PUBLIC|GLOBAL-UNICAST)/ )
                ) {
                    $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
                } elsif ( !$wanIP ) {
                    $wanIP = $lanIP;
                }

                if ( $wanIP eq '0.0.0.0' ) {
                    $msg = <<"EOF";

\\Z1Invalid or unauthorized IP address.\\Zn

Please try again:
EOF
                }
            } while $rs < 30 && $msg;
            return $rs if $rs >= 30;
        } else {
            $wanIP = $lanIP
        }
    } elsif ( $lanIP eq 'None' ) {
        $lanIP = '0.0.0.0';
    }

    main::setupSetQuestion( 'BASE_SERVER_IP', $lanIP );
    main::setupSetQuestion( 'BASE_SERVER_PUBLIC_IP', $wanIP );
    0;
}

=item timezoneDialog( \%dialog )

 Ask for server timezone

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub timezoneDialog
{
    my (undef, $dialog) = @_;

    my $timezone = main::setupGetQuestion(
        'TIMEZONE', ( iMSCP::Getopt->preseed ) ? DateTime::TimeZone->new( name => 'local' )->name() : ''
    );

    if ( $main::reconfigure =~ /^(?:local_server|timezone|all|forced)$/
        || !isValidTimezone( $timezone )
    ) {
        my ($rs, $msg) = ( 0, '' );
        do {
            ( $rs, $timezone ) = $dialog->inputbox(
                <<"EOF", $timezone || DateTime::TimeZone->new( name => 'local' )->name());

Please enter your timezone:$msg
EOF
            $msg = isValidTimezone( $timezone ) ? '' : $iMSCP::Dialog::InputValidation::lastValidationError;
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;
    }

    main::setupSetQuestion( 'TIMEZONE', $timezone );
    0;
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSetupKernel' );
    return $rs if $rs;

    if ( -f "$main::imscpConfig{'SYSCTL_CONF_DIR'}/imscp.conf" ) {
        # Don't catch any error here to avoid permission denied error on some
        # vps due to restrictions set by provider
        $rs = execute(
            "$main::imscpConfig{'CMD_SYSCTL'} -p $main::imscpConfig{'SYSCTL_CONF_DIR'}/imscp.conf",
            \ my $stdout,
            \ my $stderr
        );
        debug( $stdout ) if $stdout;
        debug( $stderr ) if $stderr;
    }

    $self->{'eventManager'}->trigger( 'afterSetupKernel' );

    0;
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->_setupHostname();
    $rs ||= $self->_setupPrimaryIP();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::server::local::installer

=cut

sub _init
{
    my ($self) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self;
}

=item _setupHostname( )

 Setup server hostname

 Return int 0 on success, other on failure

=cut

sub _setupHostname
{
    my ($self) = @_;

    my $hostname = main::setupGetQuestion( 'SERVER_HOSTNAME' );
    my $lanIP = main::setupGetQuestion( 'BASE_SERVER_IP' );

    my $rs = $self->{'eventManager'}->trigger( 'beforeSetupServerHostname', \$hostname, \$lanIP );
    return $rs if $rs;

    my @labels = split /\./, $hostname;
    my $host = shift @labels;
    my $hostnameLocal = "$hostname.local";

    my $file = iMSCP::File->new( filename => '/etc/hosts' );
    $rs = $file->copyFile( '/etc/hosts.bkp' ) unless -f '/etc/hosts.bkp';
    return $rs if $rs;

    my $content = <<"EOF";
127.0.0.1   $hostnameLocal   localhost
$lanIP  $hostname   $host

# The following lines are desirable for IPv6 capable hosts
::1 localhost  ip6-localhost   ip6-loopback
fe00::0 ip6-localnet
ff00::0 ip6-mcastprefix
ff02::1 ip6-allnodes
ff02::2 ip6-allrouters
ff02::3 ip6-allhosts
EOF
    $file->set( $content );

    $rs = $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0644 );
    return $rs if $rs;

    $file = iMSCP::File->new( filename => '/etc/hostname' );
    $file->set( $host );

    $rs = $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0644 );
    return $rs if $rs;

    $file = iMSCP::File->new( filename => '/etc/mailname' );
    $file->set( $hostname );

    $rs = $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0644 );
    return $rs if $rs;

    $rs = execute( 'hostname -F /etc/hostname', \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || "Couldn't set server hostname" ) if $rs;
    $rs ||= $self->{'eventManager'}->trigger( 'afterSetupServerHostname' );
}

=item _setupPrimaryIP( )

 Setup server primary IP

 Return int 0 on success, other on failure

=cut

sub _setupPrimaryIP
{
    my ($self) = @_;

    my $primaryIP = main::setupGetQuestion( 'BASE_SERVER_IP' );
    my $rs = $self->{'eventManager'}->trigger( 'beforeSetupPrimaryIP', $primaryIP );
    return $rs if $rs;

    local $@;
    eval {
        my $netCard = ( $primaryIP eq '0.0.0.0' ) ? 'any' : iMSCP::Net->getInstance()->getAddrDevice( $primaryIP );
        defined $netCard or die( sprintf( "Couldn't find network card for the `%s' IP address", $primaryIP ));

        my $db = iMSCP::Database->factory();
        my $oldDbName = $db->useDatabase( main::setupGetQuestion( 'DATABASE_NAME' ));

        my $dbh = $db->getRawDb();
        local $dbh->{'RaiseError'} = 1;

        $dbh->selectrow_hashref( 'SELECT 1 FROM server_ips WHERE ip_number = ?', undef, $primaryIP )
            ? $dbh->do( 'UPDATE server_ips SET ip_card = ? WHERE ip_number = ?', undef, $netCard, $primaryIP )
            : $dbh->do(
            'INSERT INTO server_ips (ip_number, ip_card, ip_config_mode, ip_status) VALUES(?, ?, ?, ?)',
            undef, $primaryIP, $netCard, 'manual', 'ok'
        );

        $db->useDatabase( $oldDbName ) if $oldDbName;
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterSetupPrimaryIP', $primaryIP );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
