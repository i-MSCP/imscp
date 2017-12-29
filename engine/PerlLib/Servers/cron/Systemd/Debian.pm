=head1 NAME

 Servers::cron::Vixie::Debian - i-MSCP (Debian) Systemd cron server abstract implementation

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

package Servers::cron::Systemd::Debian;

use strict;
use warnings;
use Class::Autouse qw/ :nostat iMSCP::Service /;
use iMSCP::Debug qw/ error /;
use parent 'Servers::cron::Vixie::Debian';

=head1 DESCRIPTION

 i-MSCP (Debian) systemd cron server implementation.
 
 See SYSTEMD.CRON(7) manpage.

=head1 PUBLIC METHODS

=over 4

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    eval { iMSCP::Service->getInstance()->stop( 'cron.target' ); };
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

    eval { iMSCP::Service->getInstance()->enable( 'cron.target' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]},
                [
                    sub {
                        iMSCP::Service->getInstance()->start( 'cron.target' );
                        0;
                    },
                    'Cron'
                ];
            0;
        },
        -99
    );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
