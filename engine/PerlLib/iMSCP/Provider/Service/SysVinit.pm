=head1 NAME

 iMSCP::Provider::Service::Sysvinit - Base service provider for SysVinit

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

package iMSCP::Provider::Service::SysVinit;

use strict;
use warnings;
use Carp 'croak';
use File::Spec;
use iMSCP::Boolean;
use iMSCP::Debug qw/ debug getMessageByType /;
use iMSCP::File;
use iMSCP::LsbRelease;
use parent qw/ iMSCP::Provider::Service::Abstract /;

=head1 DESCRIPTION

 SysVinit init provider.

=head1 PUBLIC METHODS

=over 4

=item remove( $service )

 See iMSCP::Provider::Service::Interface

=cut

sub remove
{
    my ( $self, $service ) = @_;

    defined $service or croak( 'Missing or undefined $service parameter' );

    return unless $self->hasService( $service );

    $self->stop( $service );

    debug( sprintf( "Removing the %s SysVinit script", $service ));
    iMSCP::File->new( filename => $self->resolveSysVinitScript( $service, TRUE ))->delFile() == 0 or croak(
        getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error'
    );
}

=item start( $service )

 See iMSCP::Provider::Service::Interface

=cut

sub start
{
    my ( $self, $service ) = @_;

    defined $service or croak( 'Missing or undefined $service parameter' );

    return if $self->isRunning( $service );

    $self->_exec( [ $self->resolveSysVinitScript( $service ), 'start' ] );
}

=item stop( $service )

 See iMSCP::Provider::Service::Interface

=cut

sub stop
{
    my ( $self, $service ) = @_;

    defined $service or croak( 'Missing or undefined $service parameter' );

    return unless $self->isRunning( $service );

    $self->_exec( [ $self->resolveSysVinitScript( $service ), 'stop' ] );
}

=item restart( $service )

 See iMSCP::Provider::Service::Interface

=cut

sub restart
{
    my ( $self, $service ) = @_;

    defined $service or croak( 'Missing or undefined $service parameter' );

    if ( $self->isRunning( $service ) ) {
        $self->_exec( [ $self->resolveSysVinitScript( $service ), 'restart' ] );
        return;
    }

    # Service is not running yet, we start it instead
    $self->_exec( [ $self->resolveSysVinitScript( $service ), 'start' ] );
}

=item reload( $service )

 See iMSCP::Provider::Service::Interface

=cut

sub reload
{
    my ( $self, $service ) = @_;

    defined $service or croak( 'Missing or undefined $service parameter' );

    if ( $self->isRunning( $service ) ) {
        # We need to catch STDERR as we do do want croak on failure
        my $ret = $self->_exec( [ $self->resolveSysVinitScript( $service ), 'reload' ], undef, \my $stderr );

        # If the reload action failed, we try a restart instead. This cover
        # case where the reload action is not supported.
        $self->restart( $service ) if $ret;
        return;
    }

    # Service is not running yet, we start it instead
    $self->_exec( [ $self->resolveSysVinitScript( $service ), 'start' ] );
}

=item isRunning( $service )

 See iMSCP::Provider::Service::Interface

=cut

sub isRunning
{
    my ( $self, $service ) = @_;

    defined $service or croak( 'Missing or undefined $service parameter' );

    unless ( defined $self->{'_pid_pattern'} ) {
        # We need to catch STDERR as we do not croak on failure when command
        # status is other than 0 but no STDERR
        my $ret = $self->_exec( [ $self->resolveSysVinitScript( $service ), 'status' ], undef, \my $stderr );
        croak( $stderr ) if $ret && length $stderr;
        return $ret == 0;
    }

    my $ret = $self->_getPid( $self->{'_pid_pattern'} );
    undef $self->{'_pid_pattern'};
    $ret;
}

=item hasService( $service )

 See iMSCP::Provider::Service::Interface

=cut

sub hasService
{
    my ( $self, $service ) = @_;

    defined $service or croak( 'Missing or undefined $service parameter' );

    eval { $self->resolveSysVinitScript( $service, TRUE ); };
}

=item setPidPattern( $pattern )

 Set PID pattern for next _getPid( ) invocation

 Param string|Regexp $pattern Process PID pattern
 Return void

=cut

sub setPidPattern
{
    my ( $self, $pattern ) = @_;

    defined $pattern or croak( 'Missing or undefined $pattern parameter' );

    $self->{'_pid_pattern'} = ref $pattern eq 'Regexp' ? $pattern : qr/$pattern/;
}

=item resolveSysVinitScript( $service [, $nocache =  FALSE ] )

 Resolve the given SysVinit script

 Param string $service Service name
 Param boolean $nocache OPTIONAL If true, no cache will be used
 Return string Full SysVinit script path on success, croak on failure

=cut

sub resolveSysVinitScript
{
    my ( $self, $service, $nocache ) = @_;

    CORE::state %resolved;

    if ( $nocache ) {
        delete $resolved{$service};
    } elsif ( exists $resolved{$service} ) {
        $resolved{$service} or croak( sprintf( "Couldn't resolve the %s SysVinit script", $service ));
        return $resolved{$service};
    }

    for my $path ( @{ $self->{'sysvinitscriptpaths'} } ) {
        my $initScriptPath = File::Spec->join( $path, $service );
        $resolved{$service} = $initScriptPath if -f $initScriptPath;
        last if $resolved{$service};

        $initScriptPath .= '.sh';
        $resolved{$service} = $initScriptPath if -f $initScriptPath;
    }

    if ( $nocache ) {
        $resolved{$service} or croak( sprintf( "Couldn't resolve the %s SysVinit script", $service ));
        return delete $resolved{$service};
    }

    $resolved{$service} or croak( sprintf( "Couldn't resolve the %s SysVinit script", $service ));
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Provider::Service::SysVinit, croak on failure

=cut

sub _init
{
    my ( $self ) = @_;

    my $distID = iMSCP::LsbRelease->getInstance()->getId( 'short' );

    if ( $distID =~ /^(?:FreeBSD|DragonFly)$/ ) {
        $distID = [ '/etc/rc.d', '/usr/local/etc/rc.d' ];
    } elsif ( $distID eq 'HP-UX' ) {
        $distID = [ '/sbin/init.d' ];
    } elsif ( $distID eq 'Archlinux' ) {
        $self->{'sysvinitscriptpaths'} = [ '/etc/rc.d' ];
    } else {
        $self->{'sysvinitscriptpaths'} = [ '/etc/init.d' ];
    }

    $self;
}

=item _getPs( )

 Get proper 'ps' invocation for the platform

 Return int Command exit status

=cut

sub _getPs
{
    my $distID = iMSCP::LsbRelease->getInstance()->getId( 'short' );

    if ( $distID eq 'OpenWrt' ) {
        'ps www';
    } elsif ( grep ( $distID eq $_, qw/ FreeBSD NetBSD OpenBSD Darwin DragonFly / ) ) {
        'ps auxwww';
    } else {
        'ps -ef'
    }
}

=item _getPid( $pattern )

 Get the process ID for a running process

 Param Regexp $pattern PID pattern
 Return int|undef Process ID or undef if not found

=cut

sub _getPid
{
    my ( $self, $pattern ) = @_;

    defined $pattern or croak( 'Missing or undefined $pattern parameter' );

    my $ps = $self->_getPs();
    open my $fh, '-|', $ps or croak( sprintf( "Couldn't pipe to %s: %s", $ps, $! ));

    while ( my $line = <$fh> ) {
        next unless $line =~ /$pattern/;
        return ( split /\s+/, $line =~ s/^\s+//r )[1];
    }

    undef;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
