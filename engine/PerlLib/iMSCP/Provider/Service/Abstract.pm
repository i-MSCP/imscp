=head1 NAME

 iMSCP::Provider::Service::Abstract - Abstract class for init providers

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

package iMSCP::Provider::Service::Abstract;

use strict;
use warnings;
use Carp 'croak';
use iMSCP::Debug 'debug';
use iMSCP::Execute 'execute';
use parent qw/ Common::SingletonClass iMSCP::Provider::Service::Interface /;

my $EXEC_OUTPUT;

=head1 DESCRIPTION

 Abstract class for init providers.

=head1 PRIVATE METHODS

=over 4

=item _exec( \@command, [ \$stdout [, \$stderr ]] )

 Execute the given command

 It is possible to capture both STDOUT and STDERR output by providing scalar
 references. STDERR output is used for raising failure when the command status
 is other than 0 and if no scalar reference has been provided for its capture.

 Param array_ref \@command Command to execute
 Param scalar_ref \$stdout OPTIONAL Scalar reference for STDOUT capture
 Param scalar_ref \$stderr OPTIONAL Scalar reference for STDERR capture
 Return int Command exit status, croak on failure if the command status is other than 0 and if no scalar reference has been provided for STDERR

=cut

sub _exec
{
    my ( $self, $command, $stdout, $stderr ) = @_;

    my $ret = execute( $command, ref $stdout eq 'SCALAR' ? $stdout : \$stdout, ref $stderr eq 'SCALAR' ? $stderr : \$stderr );
    ref $stdout ? !length ${ $stdout } || debug( ${ $stdout } ) : !length $stdout || debug( $stdout );

    # Raise a failure if command status is other than 0 and if no scalar
    # reference has been provided for STDERR, giving choice to callers
    croak( $stderr || 'Unknown error' ) if $ret && ref $stderr ne 'SCALAR';

    # We cache STDOUT output.
    # see _getLastExecOutput()
    $EXEC_OUTPUT = \( ref $stdout ? ${ $stdout } : $stdout );
    $ret;
}

=item _getLastExecOutput()

 Get output of last exec command

 return string Command STDOUT

=cut

sub _getLastExecOutput
{
    my ( $self ) = @_;

    ${ $EXEC_OUTPUT };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
