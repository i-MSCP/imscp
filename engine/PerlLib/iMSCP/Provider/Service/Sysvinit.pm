=head1 NAME

 iMSCP::Provider::Service::Sysvinit - Base service provider for `sysvinit` scripts

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
use iMSCP::Debug 'error';
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::LsbRelease;
use Scalar::Defer;

# Paths in which sysvinit script must be searched
my $initScriptPaths = lazy
    {
        my $id = iMSCP::LsbRelease->getInstance()->getId( 'short' );

        if ($id =~ /^FreeBSD|DragonFly$/) {
            [ '/etc/rc.d', '/usr/local/etc/rc.d' ];
        } elsif ($id eq 'HP-UX') {
            [ '/sbin/init.d' ];
        } elsif ($id eq 'Archlinux') {
            [ '/etc/rc.d' ];
        } else {
            [ '/etc/init.d' ];
        }
    };

# Cache for init script paths
my %initScriptPathsCache = ();

=head1 DESCRIPTION

 Base service provider for `sysvinit` scripts.

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
    ${$instance} = bless ( \my $this, $self ) unless defined ${$instance};
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
    if (my $initScriptPath = eval { $self->getInitScriptPath( $service ); }) {
        delete $initScriptPathsCache{$service};
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

    return $initScriptPathsCache{$service} if $initScriptPathsCache{$service};

    for my $path(@{$initScriptPaths}) {
        my $filepath = File::Spec->join( $path, $service );
        $initScriptPathsCache{$service} = $filepath if -f $filepath;

        unless ($initScriptPathsCache{$service}) {
            $filepath .= '.sh';
            $initScriptPathsCache{$service} = $filepath if -f $filepath;
        }

        return $initScriptPathsCache{$service} if $initScriptPathsCache{$service};
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

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
