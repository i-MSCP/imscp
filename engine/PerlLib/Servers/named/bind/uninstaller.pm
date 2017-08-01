=head1 NAME

 Servers::named::bind::uninstaller - i-MSCP Bind9 Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurente Declercq <l.declercq@nuxwin.com>
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

package Servers::named::bind::uninstaller;

use strict;
use warnings;
use File::Basename;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use Servers::named::bind;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Uninstaller for the i-MSCP Bind9 Server implementation.

=head1 PUBLIC METHODS

=over 4

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    $self->_removeConfig();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::named::bind::uninstaller

=cut

sub _init
{
    my ($self) = @_;

    $self->{'named'} = Servers::named::bind->getInstance();
    $self->{'cfgDir'} = $self->{'named'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'vrlDir'} = "$self->{'cfgDir'}/imscp";
    $self->{'config'} = $self->{'named'}->{'config'};
    $self;
}

=item _removeConfig( )

 Remove configuration

 Return int 0 on success, other on failure

=cut

sub _removeConfig
{
    my ($self) = @_;

    if ( exists $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'} ) {
        my $dirname = dirname( $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'} );
        if ( -d $dirname ) {
            my $filename = basename( $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'} );

            if ( -f "$self->{'bkpDir'}/$filename.system" ) {
                my $rs = iMSCP::File->new( filename => "$self->{'bkpDir'}/$filename.system" )->copyFile(
                    $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'}, { preserve => 'no' }
                );
                return $rs if $rs;

                my $file = iMSCP::File->new( filename => $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'} );
                $rs = $file->mode( 0640 );
                $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'} );
                return $rs if $rs;
            }
        }
    }

    for ( 'BIND_CONF_FILE', 'BIND_LOCAL_CONF_FILE', 'BIND_OPTIONS_CONF_FILE' ) {
        next unless exists $self->{'config'}->{$_};

        my $dirname = dirname( $self->{'config'}->{$_} );
        next unless -d $dirname;

        my $filename = basename( $self->{'config'}->{$_} );
        next unless -f "$self->{'bkpDir'}/$filename.system";

        my $rs = iMSCP::File->new( filename => "$self->{'bkpDir'}/$filename.system" )->copyFile(
            $self->{'config'}->{$_}, { preserve => 'no' }
        );
        return $rs if $rs;

        my $file = iMSCP::File->new( filename => $self->{'config'}->{$_} );
        $rs = $file->mode( 0640 );
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'} );
        return $rs if $rs;
    }

    iMSCP::Dir->new( dirname => $self->{'config'}->{'BIND_DB_MASTER_DIR'} )->remove();
    iMSCP::Dir->new( dirname => $self->{'config'}->{'BIND_DB_SLAVE_DIR'} )->remove();
    iMSCP::Dir->new( dirname => $self->{'wrkDir'} )->clear();
    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
