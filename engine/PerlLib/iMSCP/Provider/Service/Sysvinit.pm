=head1 NAME

 iMSCP::Provider::Service::Sysvinit - Base service provider for `sysvinit' scripts

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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
use Scalar::Defer;

# Paths in which sysvinit script must be searched
my $SYSVINITSCRIPTPATHS = lazy
    {
        # Fixme: iMSCP::LsbRelease is Linux specific. We must rewrite it to support all platforms below.
        my $id = iMSCP::LsbRelease->getInstance()->getId( 'short' );
        if ($id =~ /^(?:FreeBSD|DragonFly)$/) {
            [ '/etc/rc.d', '/usr/local/etc/rc.d' ];
        } elsif ($id eq 'HP-UX') {
            [ '/sbin/init.d' ];
        } elsif ($id eq 'Archlinux') {
            [ '/etc/rc.d' ];
        } else {
            [ '/etc/init.d' ];
        }
    };

=head1 DESCRIPTION

 Base service provider for `sysvinit' scripts.

=head1 PUBLIC METHODS

=over 4

=item getInstance()

 Get instance

 Return iMSCP::Provider::Service::Sysvinit

=cut

sub getInstance
{
    my $self = shift;

    no strict 'refs';
    my $instance = \${"${self}::_instance"};
    ${$instance} = bless ( { }, $self ) unless defined ${$instance};
    ${$instance};
}

=item isEnabled($service)

 Does the given service is enabled?

 Param string $service Service name
 Return bool TRUE

=cut

sub isEnabled
{
    confess 'not implemented';
}

=item enable($service)

 Enable the given service

 Param string $service Service name
 Return bool TRUE if the given service is enabled, FALSE otherwise

=cut

sub enable
{
    confess 'not implemented';
}

=item disable($service)

 Disable the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub disable
{
    confess 'not implemented';
}

=item remove($service)

 Remove the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub remove
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );

    local $@;
    my $initScriptPath = eval { $self->getInitScriptPath( $service ); };
    if (defined $initScriptPath) {
        return 0 if iMSCP::File->new( filename => $initScriptPath )->delFile();
    }

    1;
}

=item start($service)

 Start the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub start
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    return 1 if $self->isRunning( $service );
    $self->_exec( $self->getInitScriptPath( $service ), 'start' ) == 0;
}

=item stop($service)

 Stop the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub stop
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    return 1 unless $self->_isSysvinit( $service ) && $self->isRunning( $service );
    $self->_exec( $self->getInitScriptPath( $service ), 'stop' ) == 0;
}

=item restart($service)

 Restart the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub restart
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    return $self->_exec( $self->getInitScriptPath( $service ), 'restart' ) == 0 if $self->isRunning( $service );
    $self->_exec( $self->getInitScriptPath( $service ), 'start' ) == 0;
}

=item reload($service)

 Reload the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub reload
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    return $self->_exec( $self->getInitScriptPath( $service ), 'reload' ) == 0 if $self->isRunning( $service );
    $self->_exec( $self->getInitScriptPath( $service ), 'start' ) == 0;
}

=item isRunning($service)

 Does the given service is running?

 Param string $service Service name
 Return bool TRUE if the given service is running, FALSE otherwise

=cut

sub isRunning
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );

    if (defined $self->{'_pid_pattern'}) {
        my $ret = $self->_getPid( $self->{'_pid_pattern'} );
        $self->{'_pid_pattern'} = undef;
        return $ret;
    }

    $self->_exec( $self->getInitScriptPath( $service ), 'status' ) == 0;
}

=item getInitScriptPath($service)

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

=item setPidPattern($pattern)

 Set PID pattern for next _getPid() invocation

 Param string $pattern Process PID pattern
 Return int 0

=cut

sub setPidPattern
{
    my ($self, $pattern) = @_;

    defined $pattern or die( '$pattern parameter is not defined' );
    $self->{'_pid_pattern'} = $pattern;
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _isSysvinit($service)

 Does the given service is managed by a sysvinit script?

 Param string $service Service name
 Return bool TRUE if the given service is managed by a sysvinit script, FALSE otherwise

=cut

sub _isSysvinit
{
    my ($self, $service) = @_;

    local $@;
    eval { $self->_searchInitScript( $service ); };
}

=item searchInitScript($service)

 Search the init script which belongs to the given service in all available paths

 Param string $service Service name
 Return string Init script path on success, die on failure

=cut

sub _searchInitScript
{
    my ($self, $service) = @_;

    for my $path(@{$SYSVINITSCRIPTPATHS}) {
        my $filepath = File::Spec->join( $path, $service );
        return $filepath if -f $filepath;

        $filepath .= '.sh';
        return $filepath if -f $filepath;
    }

    die( sprintf( 'Could not find sysvinit script for the %s service', $service ) );
}

=item _exec($command)

 Execute the given command

 Return int Command exit status

=cut

sub _exec
{
    my ($self, @command) = @_;

    my $ret = execute( "@command", \ my $stdout, \ my $stderr );
    error( $stderr ) if $ret && $stderr;
    $ret;
}

=item _getPs()

 Get proper 'ps' invocation for the platform

 Return int Command exit status

=cut

sub _getPs
{
    my ($self) = shift;

    # Fixme: iMSCP::LsbRelease is Linux specific. We must rewrite it to support all platforms below.
    my $id = iMSCP::LsbRelease->getInstance()->getId( 'short' );
    if ($id eq 'OpenWrt') {
        'ps www';
    } elsif ($id =~ /^(?:FreeBSD|NetBSD|OpenBSD|Darwin|DragonFly)$/) {
        'ps auxwww';
    } else {
        'ps -ef'
    }
}

=item _getPid($pattern)

 Get the process ID for a running process

 Param string $pattern
 Return int|undef Process ID or undef if not found

=cut

sub _getPid
{
    my ($self, $pattern) = @_;

    defined $pattern or die( '$pattern parameter is not defined' );

    my $ps = $self->_getPs();
    open my $fh, '-|', $ps or die( sprintf( 'Could not open pipe to %s: %s', $ps, $! ) );

    my $regex = qr/$pattern/;
    while(<$fh>) {
        next unless /$regex/;
        debug( sprintf( 'Process matched line: %s', $_ ) );
        return (split /\s+/, s/^\s+//r)[1];
    }

    undef;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
