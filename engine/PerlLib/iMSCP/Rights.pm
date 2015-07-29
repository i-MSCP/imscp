=head1 NAME

 iMSCP::Rights - Package providing basic utilities for filesystem (permissions handling).

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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
use File::Find;
use parent 'Exporter';

our @EXPORT = qw/setRights/;

=head1 DESCRIPTION

Package providing basic utilities for filesystem (permissions handling).

=head1 PUBLIC FUNCTIONS

=over 4

=item setRights($target, \%options)

 Depending on the given options, set owner, group and permissions on the given target

 Param string $target Target file or directory
 Param hash \%options:
    mode      : Set mode on the given file (operate recursively only if the recursive option is true)
    dirmode   : Set mode on directories (recursive operation)
    filemode  : Set mode on files (recusive operation)
    user      : Set user on the given file (operate recursively only if the recursive option is true)
    group     : Set group for the given file (operate recursively only if the recusive option is true)
    recursive : Whether or not mode, owner and group operations should be processed recursively

    Note: Mixe of mode and dirmode/filemode options is not allowed
 Return int 0 on success or die on failure

=cut

sub setRights
{
	my ($target, $options) = @_;

	if(ref $options eq 'HASH' && %{$options}) {
		if(defined $options->{'mode'} && (defined $options->{'dirmode'} || defined $options->{'filemode'})) {
			die('Unallowed mixed options');
		}

		my $uid = $options->{'user'} ? getpwnam($options->{'user'}) : -1;
		my $gid = $options->{'group'} ? getgrnam($options->{'group'}) : -1;
		defined $uid or die(sprintf('user option refers to inexistent user: %s', $options->{'user'}));
		defined $gid or die(sprintf('group option refers to inexistent group: %s', $options->{'group'}));

		my $mode = defined $options->{'mode'} ? oct($options->{'mode'}) : undef;
		my $dirmode = defined $options->{'dirmode'} ? oct($options->{'dirmode'}) : undef;
		my $filemode = defined $options->{'filemode'} ? oct($options->{'filemode'}) : undef;

		if((($mode || $options->{'user'} || $options->{'group'}) && $options->{'recursive'}) || $dirmode || $filemode) {
			local $SIG{__WARN__} = sub { die @_ };
			find {
				wanted => sub {
					if(($options->{'user'} || $options->{'group'}) && $options->{'recursive'}) {
						chown $uid, $gid, $_ or die(sprintf('Could not set user/group on %s: %s', $_, $!));
					}

					if($mode && $options->{'recursive'}) {
						chmod $mode, $_ or die(sprintf('Could not set mode on %s: %s', $_, $!));
					} else {
						if(-d && $dirmode) {
							chmod $dirmode, $_ or die(sprintf('Could not set mode on %s: %s', $_, $!));
						} elsif($filemode) {
							chmod $filemode, $_ or die(sprintf('Could not set mode on %s: %s', $_, $!));
						}
					}
				},
				no_chdir => 1
			}, $target;
		}

		if(($options->{'user'} || $options->{'group'}) && ! $options->{'recursive'}) {
			chown $uid, $gid, $target or die(sprintf('Could not set user/group on %s: %s', $target, $!));
		}

		if($mode && ! $options->{'recursive'}) {
			chmod $mode, $target  or die(sprintf('Could not set mode on %s: %s', $_, $!));
		}
	} else {
		die('Expects at least one option');
	}

	0;
}

=back

=head1 AUTHOR

Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
