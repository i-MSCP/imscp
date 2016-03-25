=head1 NAME

Package::AntiRootkits - i-MSCP Anti-Rootkits package

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

package Package::AntiRootkits;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Dialog;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::Getopt;
use iMSCP::ProgramFinder;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Anti-Rootkits package.

 Wrapper that handles all available Anti-Rootkits packages found in the AntiRootkits directory.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners(\%eventManager)

 Register setup event listeners

 Param iMSCP::EventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self, $eventManager) = @_;

    $eventManager->register( 'beforeSetupDialog', sub {
            push @{$_[0]}, sub { $self->showDialog( @_ ) };
            0;
        } );
}

=item askAntiRootkits(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub showDialog
{
    my ($self, $dialog) = @_;

    my $packages = [ split ',', main::setupGetQuestion( 'ANTI_ROOTKITS_PACKAGES' ) ];
    my $rs = 0;

    if (grep($_ eq $main::reconfigure, ( 'antirootkits', 'all', 'forced' ))
        || !@{$packages}
        || grep { my $__ = $_;
        !grep $_ eq $__, ( @{$self->{'PACKAGES'}}, 'No' ) } @{$packages}
    ) {
        ($rs, $packages) = $dialog->checkbox(
            <<"EOF", [ @{$self->{'PACKAGES'}} ], grep($_ eq 'No', @{$packages}) ? () : @{$packages} ? @{$packages} : @{$self->{'PACKAGES'}} );

Please select the Anti-Rootkits packages you want to install:
EOF
    }

    return $rs unless $rs < 30;

    main::setupSetQuestion( 'ANTI_ROOTKITS_PACKAGES', @{$packages} ? join ',', @{$packages} : 'No' );

    return $rs if grep($_ eq 'No', @{$packages});

    for my $package(@{$packages}) {
        $package = "Package::AntiRootkits::${package}::${package}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            if ($package->can( 'showDialog' )) {
                debug( sprintf( 'Calling action showDialog on %s', ref $package ) );
                $rs = $package->showDialog( $dialog );
                return $rs if $rs;
            }
        } else {
            error( $@ );
            return 1;
        }
    }

    $rs;
}

=item preinstall()

 Process preinstall tasks

 /!\ This method also trigger uninstallation of unselected Anti-Rootkits packages.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my $self = shift;

    my $rs = 0;
    my @packages = split ',', main::setupGetQuestion( 'ANTI_ROOTKITS_PACKAGES' );
    my $packagesToInstall = [ grep { $_ ne 'No' } @packages ];
    my $packagesToUninstall = [ grep { my $__ = $_;
        !grep($_ eq $__, @{$packagesToInstall}) } @{$self->{'PACKAGES'}} ];

    if (@{$packagesToUninstall}) {
        my $packages = [ ];
        for my $package(@{$packagesToUninstall}) {
            $package = "Package::AntiRootkits::${package}::${package}";
            eval "require $package";
            unless ($@) {
                $package = $package->getInstance();
                if ($package->can( 'uninstall' )) {
                    debug( sprintf( 'Calling action uninstall on %s', ref $package ) );
                    $rs = $package->uninstall();
                    return $rs if $rs;
                }

                if ($package->can( 'getDistroPackages' )) {
                    debug( sprintf( 'Calling action getDistroPackages on %s', ref $package ) );
                    @{$packages} = (@{$packages}, @{$package->getDistroPackages()});
                }
            } else {
                error( $@ );
                return 1;
            }
        }

        if (defined $main::skippackages && !$main::skippackages && @{$packages}) {
            $rs = $self->_removePackages( $packages );
            return $rs if $rs;
        }
    }

    return $rs unless @{$packagesToInstall};

    my $packages = [ ];
    for my $package(@{$packagesToInstall}) {
        $package = "Package::AntiRootkits::${package}::${package}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            if ($package->can( 'preinstall' )) {
                debug( sprintf( 'Calling action preinstall on %s', ref $package ) );
                $rs = $package->preinstall();
                return $rs if $rs;
            }

            if ($package->can( 'getDistroPackages' )) {
                debug( sprintf( 'Calling action getDistroPackages on %s', ref $package ) );
                @{$packages} = (@{$packages}, @{$package->getDistroPackages()});
            }
        } else {
            error( $@ );
            return 1;
        }
    }

    if (defined $main::skippackages && !$main::skippackages && @{$packages}) {
        $rs = $self->_installPackages( $packages );
        return $rs if $rs;
    }

    $rs;
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my @packages = split ',', main::setupGetQuestion( 'ANTI_ROOTKITS_PACKAGES' );

    return 0 if grep($_ eq 'No', @packages);

    for my $package(@packages) {
        $package = "Package::AntiRootkits::${package}::${package}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            if ($package->can( 'install' )) {
                debug( sprintf( 'Calling action install on %s', ref $package ) );
                my $rs = $package->install();
                return $rs if $rs;
            }
        } else {
            error( $@ );
            return 1;
        }
    }

    0;
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my $self = shift;

    my @packages = split ',', $main::imscpConfig{'ANTI_ROOTKITS_PACKAGES'};

    my $packages = [ ];
    my $rs = 0;

    for my $package(@packages) {
        next unless grep($_ eq $package, @{$self->{'PACKAGES'}});

        $package = "Package::AntiRootkits::${package}::${package}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            if ($package->can( 'uninstall' )) {
                debug( sprintf( 'Calling action uninstall on %s', ref $package ) );
                $rs = $package->uninstall();
                return $rs if $rs;
            }

            if ($package->can( 'getDistroPackages' )) {
                debug( sprintf( 'Calling action getDistroPackages on %s', ref $package ) );
                @{$packages} = (@{$packages}, @{$package->getDistroPackages()});
            }
        } else {
            error( $@ );
            return 1;
        }
    }

    if (defined $main::skippackages && !$main::skippackages && @{$packages}) {
        $rs = $self->_removePackages( $packages );
    }

    $rs;
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my $self = shift;

    my @packages = split ',', $main::imscpConfig{'ANTI_ROOTKITS_PACKAGES'};

    for my $package(@packages) {
        next unless grep($_ eq $package, @{$self->{'PACKAGES'}});

        $package = "Package::AntiRootkits::${package}::${package}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            if ($package->can( 'setEnginePermissions' )) {
                debug( sprintf( 'Calling action setEnginePermissions on %s', ref $package ) );
                my $rs = $package->setEnginePermissions();
                return $rs if $rs;
            }
        } else {
            error( $@ );
            return 1;
        }
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item init()

 Initialize instance

 Return Package::AntiRootkits

=cut

sub _init()
{
    my $self = shift;

    # Find list of available AntiRootkits packages
    @{$self->{'PACKAGES'}} = iMSCP::Dir->new(
        dirname => "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/AntiRootkits"
    )->getDirs();

    $self;
}

=item _installPackages(\@packages)

 Install packages

 Param array \@packages List of packages to install
 Return int 0 on success, other on failure

=cut

sub _installPackages
{
    my ($self, $packages) = @_;

    my @command = ();

    unless (iMSCP::Getopt->preseed || iMSCP::Getopt->noprompt || !iMSCP::ProgramFinder::find( 'debconf-apt-progress' )) {
        iMSCP::Dialog->getInstance()->endGauge();
        push @command, 'debconf-apt-progress --logstderr --';
    }

    unshift @command, 'UCF_FORCE_CONFFMISS=1 '; # Force installation of missing conffiles which are managed by UCF

    if ($main::forcereinstall) {
        push @command, "apt-get -y -o DPkg::Options::='--force-confnew' -o DPkg::Options::='--force-confmiss' ".
                "--reinstall --auto-remove --purge --no-install-recommends install @{$packages}";
    } else {
        push @command, "apt-get -y -o DPkg::Options::='--force-confnew' -o DPkg::Options::='--force-confmiss' ".
                "--auto-remove --purge --no-install-recommends install @{$packages}";
    }

    my ($stdout, $stderr);
    my $rs = execute( "@command", (iMSCP::Getopt->preseed || iMSCP::Getopt->noprompt) ? \$stdout : undef, \$stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    error( 'Could not install anti-rootkits distro packages' ) if $rs && !$stderr;
    $rs;
}

=item _removePackages(\@packages)

 Remove packages

 Param array \@packages List of packages to remove
 Return int 0 on success, other on failure

=cut

sub _removePackages
{
    my ($self, $packages) = @_;

    # Do not try to remove packages which are no longer available on the system or not installed
    my $rs = execute( "LANG=C dpkg-query -W -f='\${Package}/\${Status}\n' @{$packages}", \my $stdout, \my $stderr );
    error( $stderr ) if $stderr && $rs > 1;
    return $rs if $rs > 1;

    @{$packages} = grep { m%^(.*?)/install% && ($_ = $1) } split /\n/, $stdout;

    return 0 unless @{$packages};

    my @command = ();

    unless (iMSCP::Getopt->preseed || iMSCP::Getopt->noprompt || !iMSCP::ProgramFinder::find( 'debconf-apt-progress' )) {
        iMSCP::Dialog->getInstance()->endGauge();
        push @command, 'debconf-apt-progress --logstderr -- ';
    }

    push @command, "apt-get -y --auto-remove --purge --no-install-recommends remove @{$packages}";

    $rs = execute( "@command", (iMSCP::Getopt->preseed || iMSCP::Getopt->noprompt) ? \$stdout : undef, \$stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    error( 'Could not remove anti-rootkits distro packages' ) if $rs && !$stderr;
    $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
