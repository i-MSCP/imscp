=head1 NAME

 autoinstaller::Adapter::UbuntuAdapter - Ubuntu autoinstaller adapter class

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package autoinstaller::Adapter::UbuntuAdapter;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::EventManager;
use iMSCP::Getopt;
use version;
use parent 'autoinstaller::Adapter::DebianAdapter';

=head1 DESCRIPTION

 i-MSCP autoinstaller adapter implementation for Ubuntu.

 See the autoinstaller::Adapter::Debian autoinstaller adapter for more information.

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return autoinstaller::Adapter::UbuntuAdapter

=cut

sub _init
{
    my $self = shift;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'repositorySections'} = [ 'main', 'universe', 'multiverse' ];
    $self->{'preRequiredPackages'} = [
        'binutils', 'debconf-utils', 'dialog', 'devscripts', 'fakeroot', 'libbit-vector-perl',
        'libclass-insideout-perl', 'liblist-moreutils-perl', 'libscalar-defer-perl', 'libsort-versions-perl',
        'libxml-simple-perl', 'wget', 'rsync'
    ];
    $self->{'aptRepositoriesToRemove'} = [ ];
    $self->{'aptRepositoriesToAdd'} = [ ];
    $self->{'aptPreferences'} = [ ];
    $self->{'packagesToInstall'} = [ ];
    $self->{'packagesToInstallDelayed'} = [ ];
    $self->{'packagesToPreUninstall'} = [ ];
    $self->{'packagesToUninstall'} = [ ];
    $self->{'packagesToRebuild'} = { };

    delete $ENV{'DEBCONF_FORCE_DIALOG'};
    $ENV{'DEBIAN_FRONTEND'} = 'noninteractive' if iMSCP::Getopt->noprompt;
    delete $ENV{'UPSTART_SESSION'}; # See IP-1514
    $ENV{'DEBFULLNAME'} = 'i-MSCP Installer';
    $ENV{'DEBEMAIL'} = 'team@i-mscp.net';

    unless ($main::skippackages) {
        $self->_setupInitScriptPolicyLayer( 'enable' ) == 0 or die( 'Could not setup initscript policy layer' );
        $self->_updateAptSourceList() == 0 or die( 'Could not configure APT packages manager' );
    }

    $self;
}

=back

=head1 Author

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
