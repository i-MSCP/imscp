=head1 NAME

 iMSCP::Database Provides database key and initialization vector.

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

package iMSCP::Database::Encryption;

use strict;
use warnings;
use iMSCP::Debug 'debug';
use iMSCP::Crypt qw/ decryptRijndaelCBC randomStr /;
use iMSCP::Umask '$UMASK';
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Provides database key and initialization vector.

=cut

=head1 PUBLIC METHOD

=over 4

=item getKey

 Get key

 Return string key, die on failure

=cut

sub getKey
{
    my ( $self ) = @_;

    $self->_load unless defined $self->{'_key'};
    $self->{'_key'};
}

=item getIV

 Get initialization vector

 Return string initialization vector, die on failure

=cut

sub getIV
{
    my ( $self ) = @_;

    $self->_load unless defined $self->{'_vector'};
    $self->{'_vector'};
}

=back

=head1 PRIVATE METHODS

=over 4

=item _load( )

 Load key and initialization vector, create them if needed

 Return void, die on failure

=cut

sub _load
{
    my ( $self ) = @_;

    my $keyFile = "$::imscpConfig{'CONF_DIR'}/imscp-db-keys";
    our $db_pass_key = '{KEY}';
    our $db_pass_iv = '{IV}';

    require "$keyFile" if -f $keyFile;

    if ( $db_pass_key eq '{KEY}' || length $db_pass_key != 32 || $db_pass_iv eq '{IV}' || length $db_pass_iv != 16 ) {
        require Data::Dumper;
        debug( 'Generating new encryption key and vector...' );

        -d $::imscpConfig{'CONF_DIR'} or die( sprintf( "%s doesn't exist or is not a directory", $::imscpConfig{'CONF_DIR'} ));

        local $UMASK = 027; # imscp-db-keys file must not be created world-readable
        open my $fh, '>', "$::imscpConfig{'CONF_DIR'}/imscp-db-keys" or die(
            sprintf( "Couldn't open %s file for writing: %s", "$::imscpConfig{'CONF_DIR'}/imscp-db-keys", $! )
        );
        print { $fh } Data::Dumper->Dump( [ randomStr( 32 ), randomStr( 16 ) ], [ qw/ db_pass_key db_pass_iv / ] );
        close $fh;
        delete $INC{$keyFile};
        require "$keyFile";
    }

    $self->{'_key'} = $db_pass_key;
    undef $db_pass_key;
    $self->{'_vector'} = $db_pass_iv;
    undef $db_pass_key;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
