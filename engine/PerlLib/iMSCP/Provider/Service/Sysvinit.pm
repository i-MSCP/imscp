=head1 NAME

 iMSCP::Provider::Service::Sysvinit - Base service provider for `sysvinit' scripts

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

package iMSCP::Provider::Service::Sysvinit;

use strict;
use warnings;
use Carp;
use File::Spec;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::LsbRelease;
use parent qw/ Common::SingletonClass iMSCP::Provider::Service::Interface /;

=head1 DESCRIPTION

 Base service provider for `sysvinit' scripts.

=head1 PUBLIC METHODS

=over 4

=item isEnabled( $service )

 See iMSCP::Provider::Service::Interface
 
 Note: NOOP for base provider

=cut

sub isEnabled
{
    shift;
}

=item enable( $service )

 See iMSCP::Provider::Service::Interface
 
 Note: NOOP for base provider

=cut

sub enable
{
    shift;
}

=item disable( $service )

 See iMSCP::Provider::Service::Interface

 Note: NOOP for base provider
 
=cut

sub disable
{
    shift;
}

=item remove( $service )

 See iMSCP::Provider::Service::Interface

=cut

sub remove
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );

    my $initScriptPath = eval { $self->getInitScriptPath( $service ); };
    if ( defined $initScriptPath ) {
        return 0 if iMSCP::File->new( filename => $initScriptPath )->delFile();
    }

    1;
}

=item start( $service )

 See iMSCP::Provider::Service::Interface

=cut

sub start
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    return 1 if $self->isRunning( $service );
    $self->_exec( $self->getInitScriptPath( $service ), 'start' ) == 0;
}

=item stop( $service )

 See iMSCP::Provider::Service::Interface

=cut

sub stop
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    return 1 unless $self->_isSysvinit( $service ) && $self->isRunning( $service );
    $self->_exec( $self->getInitScriptPath( $service ), 'stop' ) == 0;
}

=item restart( $service )

 See iMSCP::Provider::Service::Interface

=cut

sub restart
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    return $self->_exec( $self->getInitScriptPath( $service ), 'restart' ) == 0 if $self->isRunning( $service );
    $self->_exec( $self->getInitScriptPath( $service ), 'start' ) == 0;
}

=item reload( $service )

 See iMSCP::Provider::Service::Interface

=cut

sub reload
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    return $self->_exec( $self->getInitScriptPath( $service ), 'reload' ) == 0 if $self->isRunning( $service );
    $self->_exec( $self->getInitScriptPath( $service ), 'start' ) == 0;
}

=item isRunning( $service )

 See iMSCP::Provider::Service::Interface

=cut

sub isRunning
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );

    unless ( defined $self->{'_pid_pattern'} ) {
        return $self->_exec( $self->getInitScriptPath( $service ), 'status' ) == 0;
    }

    my $ret = $self->_getPid( $self->{'_pid_pattern'} );
    $self->{'_pid_pattern'} = undef;
    $ret;
}

=item hasService( $service )

 See iMSCP::Provider::Service::Interface

=cut

sub hasService
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    $self->_isSysvinit( $service );
}

=item getInitScriptPath( $service )

 Get full path of init script which belongs to the given service

 Param string $service Service name
 Return string Init script path on success, die on failure

=cut

sub getInitScriptPath
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    $self->_searchInitScript( $service );
}

=item setPidPattern( $pattern )

 Set PID pattern for next _getPid( ) invocation

 Param string|Regexp $pattern Process PID pattern
 Return int 0

=cut

sub setPidPattern
{
    my ($self, $pattern) = @_;

    defined $pattern or die( '$pattern parameter is not defined' );
    $self->{'_pid_pattern'} = ( ref $pattern eq 'Regexp' ) ? $pattern : qr/$pattern/;
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Provider::Service::Sysvinit
=cut

sub _init
{
    my ($self) = @_;

    my $distID = iMSCP::LsbRelease->getInstance()->getId( 'short' );

    if ( $distID =~ /^(?:FreeBSD|DragonFly)$/ ) {
        $self->{'sysvinitscriptpaths'} = [ '/etc/rc.d', '/usr/local/etc/rc.d' ];
    } elsif ( $distID eq 'HP-UX' ) {
        $self->{'sysvinitscriptpaths'} = [ '/sbin/init.d' ];
    } elsif ( $distID eq 'Archlinux' ) {
        $self->{'sysvinitscriptpaths'} = [ '/etc/rc.d' ];
    } else {
        $self->{'sysvinitscriptpaths'} = [ '/etc/init.d' ];
    }

    $self;
}

=item _isSysvinit( $service )

 Does the given service is managed by a sysvinit script?

 Param string $service Service name
 Return bool TRUE if the given service is managed by a sysvinit script, FALSE otherwise

=cut

sub _isSysvinit
{
    my ($self, $service) = @_;

    eval { $self->_searchInitScript( $service ); };
}

=item searchInitScript( $service )

 Search the init script which belongs to the given service in all available paths

 Param string $service Service name
 Return string Init script path on success, die on failure

=cut

sub _searchInitScript
{
    my ($self, $service) = @_;

    for ( @{$self->{'sysvinitscriptpaths'}} ) {
        my $initScriptPath = File::Spec->join( $_, $service );
        return $initScriptPath if -f $initScriptPath;

        $initScriptPath .= '.sh';
        return $initScriptPath if -f $initScriptPath;
    }

    die( sprintf( "Couldn't find sysvinit script for the `%s' service", $service ));
}

=item _exec( $command )

 Execute the given command

 Return int Command exit status

=cut

sub _exec
{
    my (undef, @command) = @_;

    my $ret = execute( [ @command ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    debug( $stderr ) unless $ret || !$stderr;
    error( $stderr ) if $ret && $stderr;
    $ret;
}

=item _getPs( )

 Get proper 'ps' invocation for the platform

 Return int Command exit status

=cut

sub _getPs
{
    # Fixme: iMSCP::LsbRelease is Linux specific. We must rewrite it to support all platforms below.
    my $id = iMSCP::LsbRelease->getInstance()->getId( 'short' );
    if ( $id eq 'OpenWrt' ) {
        'ps www';
    } elsif ( $id =~ /^(?:FreeBSD|NetBSD|OpenBSD|Darwin|DragonFly)$/ ) {
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
    my ($self, $pattern) = @_;

    defined $pattern or die( '$pattern parameter is not defined' );

    my $ps = $self->_getPs();
    open my $fh, '-|', $ps or die( sprintf( "Couldn't pipe to %s: %s", $ps, $! ));

    while ( <$fh> ) {
        next unless /$pattern/;
        debug( sprintf( 'Process matched line: %s', $_ ));
        return ( split /\s+/, s/^\s+//r )[1];
    }

    undef;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
