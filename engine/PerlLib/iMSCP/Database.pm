=head1 NAME

 iMSCP::Database Database factory

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

package iMSCP::Database;

use strict;
use warnings;
use iMSCP::Debug 'debug';
use iMSCP::Crypt qw/ decryptRijndaelCBC randomStr /;
use iMSCP::Database::MySQL;
use iMSCP::Database::Encryption;
use Exporter 'import';

our @EXPORT = qw/ $DATABASE /;
our $DATABASE;

tie $DATABASE, 'iMSCP::Database::SCALAR' or die "Can't tie \$DATABASE";

{
    package iMSCP::Database::SCALAR;

    sub TIESCALAR
    {
        bless [], $_[0];
    }

    sub FETCH
    {
        iMSCP::Database::->factory()->getDatabase();
    }

    sub STORE
    {
        return unless length $_[1];
        iMSCP::Database::->factory()->useDatabase( $_[1] );
    }
}

=head1 DESCRIPTION

 Database factory

=cut

=head1 FUNCTIONS

=over 4

=item factory( )

 Create and return an iMSCP::Database::MySQL instance
  
 Instance is created once, using parameters from master i-MSCP configuration file.

 Return iMSCP::Database::mysql, die on failure

=cut

my $INSTANCE;

sub factory
{
    $INSTANCE //= do {
        my $enc = iMSCP::Database::Encryption->getInstance();
        iMSCP::Database::MySQL->new(
            DATABASE_HOST     => $::imscpConfig{'DATABASE_HOST'},
            DATABASE_PORT     => $::imscpConfig{'DATABASE_PORT'},
            DATABASE_NAME     => $::imscpConfig{'DATABASE_NAME'},
            DATABASE_USER     => $::imscpConfig{'DATABASE_USER'},
            DATABASE_PASSWORD => iMSCP::Crypt::decryptRijndaelCBC( $enc->getKey(), $enc->getIV(), $::imscpConfig{'DATABASE_PASSWORD'} )
        );
    };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
