=head1 NAME

 iMSCP::Rights - Package providing function for setting file ownership and permissions.

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

package iMSCP::Rights;

use strict;
use warnings;
use iMSCP::Debug;
use File::Find;
use autouse Lchown => qw/ lchown /;
use parent 'Exporter';

our @EXPORT = qw/ setRights /;

=head1 DESCRIPTION

 Package providing function for setting file ownership and permissions.

=head1 PUBLIC FUNCTIONS

=over 4

=item setRights( $target, \%options )

 Depending on the given options, set owner, group and permissions on the given target

 Param string $target Target file or directory
 Param hash \%options:
  mode      : Set mode on the given directory/file
  dirmode   : Set mode on directories
  filemode  : Set mode on files
  user      : Set owner on the given file
  group     : Set group for the given file
  recursive : Whether or not operations must be processed recursively

 Return int 0 on success, 1 on failure

=cut

sub setRights
{
    my ($target, $options) = @_;

    local $@;
    eval {
        defined $target or die( '$target parameter is not defined' );
        ref $options eq 'HASH' && %{$options} or die( '$options parameter is not defined' );

        if ( defined $options->{'mode'} && ( defined $options->{'dirmode'} || defined $options->{'filemode'} ) ) {
            die( '`mode` option is not allowed when using dirmode/filemode options' );
        }

        my $uid = $options->{'user'} ? getpwnam( $options->{'user'} ) : -1;
        my $gid = $options->{'group'} ? getgrnam( $options->{'group'} ) : -1;
        defined $uid or die( sprintf( 'user option refers to inexistent user: %s', $options->{'user'} ));
        defined $gid or die( sprintf( 'group option refers to inexistent group: %s', $options->{'group'} ));

        my $mode = defined $options->{'mode'} ? oct( $options->{'mode'} ) : undef;
        my $dirmode = defined $options->{'dirmode'} ? oct( $options->{'dirmode'} ) : undef;
        my $filemode = defined $options->{'filemode'} ? oct( $options->{'filemode'} ) : undef;

        if ( $options->{'recursive'} ) {
            local $SIG{'__WARN__'} = sub { die @_ };
            find(
                {
                    wanted   => sub {
                        if ( $options->{'user'} || $options->{'group'} ) {
                            lchown $uid, $gid, $_ or die( sprintf( "Couldn't set user/group on %s: %s", $_, $! ));
                        }

                        return if -l; # We do not call chmod on symkink targets

                        if ( $mode ) {
                            chmod $mode, $_ or die( sprintf( "Couldn't set mode on %s: %s", $_, $! ));
                        } elsif ( $dirmode && -d _ ) {
                            chmod $dirmode, $_ or die( sprintf( "Couldn't set mode on %s: %s", $_, $! ));
                        } elsif ( $filemode ) {
                            chmod $filemode, $_ or die( sprintf( "Couldn't set mode on %s: %s", $_, $! ));
                        }
                    },
                    no_chdir => 1
                },
                $target
            );

            return 0;
        }

        if ( $options->{'user'} || $options->{'group'} ) {
            lchown $uid, $gid, $target or die( sprintf( "Couldn't set user/group on %s: %s", $target, $! ));
        }

        unless ( -l $target ) { # We do not call chmod on symkink targets
            if ( $mode ) {
                chmod $mode, $target or die( sprintf( "Couldn't set mode on %s: %s", $_, $! ));
            } elsif ( $dirmode && -d _ ) {
                chmod $dirmode, $target or die( sprintf( "Couldn't set mode on %s: %s", $_, $! ));
            } elsif ( $filemode ) {
                chmod $filemode, $target or die( sprintf( "Couldn't set mode on %s: %s", $_, $! ));
            }
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
