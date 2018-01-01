=head1 NAME

 iMSCP::Servers::Po::Courier::Debian - i-MSCP (Debian) Courier IMAP/POP3 server implementation

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

package iMSCP::Servers::Po::Courier::Debian;

use strict;
use warnings;
use iMSCP::Service;
use parent 'iMSCP::Servers::Po::Courier::Abstract';

=head1 DESCRIPTION

 i-MSCP (Debian) Courier IMAP/POP3 server implementation.

=head1 PUBLIC METHODS

=over 4

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    $self->stop();
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    eval {
        my @toEnableServices = ( 'courier-authdaemon', 'courier-pop', 'courier-pop' );
        my @toDisableServices = ();

        if ( $main::imscpConfig{'SERVICES_SSL_ENABLED'} eq 'yes' ) {
            push @toEnableServices, 'courier-pop-ssl', 'courier-imap-ssl';
        } else {
            push @toDisableServices, 'courier-pop-ssl', 'courier-imap-ssl';
        }

        my $serviceMngr = iMSCP::Service->getInstance();
        $serviceMngr->enable( $_ ) for @toEnableServices;

        for ( @toDisableServices ) {
            $serviceMngr->stop( $_ );
            $serviceMngr->disable( $_ );
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]}, [ sub { $self->start(); }, 'Courier IMAP/POP, Courier Authdaemon' ];
            0;
        },
        5
    );
}

=item start( )

 See iMSCP::Servers::Po::Courier::abstract::start()

=cut

sub start
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeCourierStart' );
    return $rs if $rs;

    eval {
        my $serviceMngr = iMSCP::Service->getInstance();
        $serviceMngr->start( $_ ) for 'courier-authdaemon', 'courier-pop', 'courier-imap';

        if ( $main::imscpConfig{'SERVICES_SSL_ENABLED'} eq 'yes' ) {
            $serviceMngr->start( $_ ) for 'courier-pop-ssl', 'courier-imap-ssl';
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterCourierStart' );
}

=item stop( )

 See iMSCP::Servers::Po::Courier::abstract::stop()

=cut

sub stop
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeCourierStop' );
    return $rs if $rs;

    eval {
        my $serviceMngr = iMSCP::Service->getInstance();

        for ( 'courier-authdaemon', 'courier-pop', 'courier-imap', 'courier-pop-ssl', 'courier-imap-ssl' ) {
            $serviceMngr->stop( $_ );
        }

    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterCourierStop' );
}

=item restart( )

 See iMSCP::Servers::Po::Courier::abstract::restart()

=cut

sub restart
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeCourierRestart' );
    return $rs if $rs;

    eval {
        my $serviceMngr = iMSCP::Service->getInstance();
        $serviceMngr->restart( $_ ) for 'courier-authdaemon', 'courier-pop', 'courier-imap';

        if ( $main::imscpConfig{'SERVICES_SSL_ENABLED'} eq 'yes' ) {
            $serviceMngr->restart( $_ ) for 'courier-pop-ssl', 'courier-imap-ssl';
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterCourierRestart' );
}

=back

=head1 SHUTDOWN TASKS

=over 4

=item shutdown( $priority )

 Restart the Courier IMAP/POP servers when needed

 This method is called automatically before the program exit.

 Param int $priority Server priority
 Return void

=cut

sub shutdown
{
    my ($self, $priority) = @_;

    return unless $self->{'restart'};

    iMSCP::Service->getInstance()->registerDelayedAction( 'courier', [ 'restart', sub { $self->restart(); } ], $priority );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
