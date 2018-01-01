=head1 NAME

 iMSCP::Servers::Po::Dovecot::Debian - i-MSCP (Debian) Dovecot IMAP/POP3 server implementation

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

package iMSCP::Servers::Po::Dovecot::Debian;

use strict;
use warnings;
use iMSCP::Service;
use parent 'iMSCP::Servers::Po::Dovecot::Abstract';

=head1 DESCRIPTION

 i-MSCP (Debian) Dovecot IMAP/POP3 server implementation.

=head1 PUBLIC METHODS

=over 4

=item preinstall( )

 See iMSCP::Servers::Po::Dovecot::Abstract::preinstall()

=cut

sub preinstall
{
    my ($self) = @_;

    eval {
        my $serviceMngr = iMSCP::Service->getInstance();

        # Disable dovecot.socket unit if any
        # Dovecot as configured by i-MSCP doesn't rely on systemd activation socket
        # This also solve problem on boxes where IPv6 is not available; default dovecot.socket unit file make
        # assumption that IPv6 is available without further checks...
        # See also: https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=814999
        if ( $serviceMngr->isSystemd() && $serviceMngr->hasService( 'dovecot.socket' ) ) {
            $serviceMngr->stop( 'dovecot.socket' );
            $serviceMngr->disable( 'dovecot.socket' );
        }

        $self->stop();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    eval { iMSCP::Service->getInstance()->enable( 'dovecot' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]}, [ sub { $self->start(); }, 'Dovecot' ];
            0;
        },
        5
    );
}

=item uninstall( )

 See iMSCP::Servers::Po::Dovecot::Abstract::uninstall()

=cut

sub uninstall
{
    my ($self) = @_;

    my $rs = $self->SUPER::uninstall();

    unless ( $rs || !iMSCP::Service->getInstance()->hasService( 'dovecot' ) ) {
        $self->{'restart'} ||= 1;
    } else {
        $self->{'restart'} ||= 0;
    }

    $rs;
}

=item start( )

 See iMSCP::Servers::Po::Dovecot::Abstract::start()

=cut

sub start
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeDovecotStart' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->start( 'dovecot' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterDovecotStart' );
}

=item stop( )

 See iMSCP::Servers::Po::Dovecot::Abstract::stop()

=cut

sub stop
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeDovecotStop' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->stop( 'dovecot' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterDovecotStop' );
}

=item restart( )

 See iMSCP::Servers::Po::Dovecot::Abstract::restart()

=cut

sub restart
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeDovecotRestart' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->restart( 'dovecot' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterDovecotRestart' );
}

=back

=head1 SHUTDOWN TASKS

=over 4

=item shutdown( $priority )

 Restart the Dovecot IMAP/POP server when needed

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
