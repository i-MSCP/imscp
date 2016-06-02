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

 Handles Anti-Rootkits packages found in the AntiRootkits directory.

=head1 PUBLIC METHODS

=over

=item registerSetupListeners(\%eventManager)

 Register setup event listeners

 Param iMSCP::EventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self, $eventManager) = @_;

    $eventManager->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]}, sub { $self->showDialog( @_ ) };
            0;
        }
    );
}

=item askAntiRootkits(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub showDialog
{
    my ($self, $dialog) = @_;

    my %selectedPackages;
    @{selectedPackages}{ split ',', main::setupGetQuestion( 'ANTI_ROOTKITS_PACKAGES' ) } = ();

    my $rs = 0;
    if ($main::reconfigure =~ /^(?:antirootkits|all|forced)$/ || !%selectedPackages
        || grep { !exists $self->{'PACKAGES'}->{$_} && $_ ne 'No' } keys %selectedPackages
    ) {
        ($rs, my $packages) = $dialog->checkbox(
            <<"EOF", [ keys %{$self->{'PACKAGES'}} ], grep { exists $self->{'PACKAGES'}->{$_} && $_ ne 'No' } keys %selectedPackages );

Please select the Anti-Rootkits packages you want to install:
EOF
        @{selectedPackages}{@{$packages}} = ();
    }

    return $rs unless $rs < 30;

    main::setupSetQuestion( 'ANTI_ROOTKITS_PACKAGES', %selectedPackages ? join ',', keys %selectedPackages : 'No' );

    for (keys %{$self->{'PACKAGES'}}) {
        next unless exists $selectedPackages{$_};
        my $package = "Package::AntiRootkits::${_}::${_}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            next unless $package->can( 'showDialog' );
            debug( sprintf( 'Calling action showDialog on %s', ref $package ) );
            $rs = $package->showDialog( $dialog );
            return $rs if $rs;
        } else {
            error( $@ );
            return 1;
        }
    }

    0;
}

=item preinstall()

 Process preinstall tasks

 /!\ This method also trigger uninstallation of unselected Anti-Rootkits packages.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my $self = shift;

    my %selectedPackages;
    @{selectedPackages}{ split ',', main::setupGetQuestion( 'ANTI_ROOTKITS_PACKAGES' ) } = ();

    my @distroPackages = ();
    for(keys %{$self->{'PACKAGES'}}) {
        next if exists $selectedPackages{$_};
        my $package = "Package::AntiRootkits::${_}::${_}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            next unless $package->can( 'uninstall' );
            debug( sprintf( 'Calling action uninstall on %s', ref $package ) );
            my $rs = $package->uninstall();
            return $rs if $rs;

            next unless $package->can( 'getDistroPackages' );
            debug( sprintf( 'Calling action getDistroPackages on %s', ref $package ) );
            push @distroPackages, $package->getDistroPackages();
        } else {
            error( $@ );
            return 1;
        }
    }

    if (defined $main::skippackages && !$main::skippackages && @distroPackages) {
        my $rs = $self->_removePackages( @distroPackages );
        return $rs if $rs;
    }

    @distroPackages = ();
    for (keys %{$self->{'PACKAGES'}}) {
        next unless exists $selectedPackages{$_};
        my $package = "Package::AntiRootkits::${_}::${_}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            next unless $package->can( 'preinstall' );
            debug( sprintf( 'Calling action preinstall on %s', ref $package ) );
            my $rs = $package->preinstall();
            return $rs if $rs;

            next unless $package->can( 'getDistroPackages' );
            debug( sprintf( 'Calling action getDistroPackages on %s', ref $package ) );
            push @distroPackages, $package->getDistroPackages();
        } else {
            error( $@ );
            return 1;
        }
    }

    if (defined $main::skippackages && !$main::skippackages && @distroPackages) {
        my $rs = $self->_installPackages( @distroPackages );
        return $rs if $rs;
    }

    0;
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $self = shift;

    my %selectedPackages;
    @{selectedPackages}{ split ',', main::setupGetQuestion( 'ANTI_ROOTKITS_PACKAGES' ) } = ();

    for (keys %{$self->{'PACKAGES'}}) {
        next unless exists $selectedPackages{$_} && $_ ne 'No';
        my $package = "Package::AntiRootkits::${_}::${_}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            next unless $package->can( 'install' );
            debug( sprintf( 'Calling action install on %s', ref $package ) );
            my $rs = $package->install();
            return $rs if $rs;
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

    my @distroPackages = ();
    for (keys %{$self->{'PACKAGES'}}) {
        my $package = "Package::AntiRootkits::${_}::${_}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            next unless $package->can( 'uninstall' );
            debug( sprintf( 'Calling action uninstall on %s', ref $package ) );
            my $rs = $package->uninstall();
            return $rs if $rs;

            next unless $package->can( 'getDistroPackages' );
            debug( sprintf( 'Calling action getDistroPackages on %s', ref $package ) );
            push @distroPackages, $package->getDistroPackages();
        } else {
            error( $@ );
            return 1;
        }
    }

    $self->_removePackages( @distroPackages );
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my $self = shift;

    my %selectedPackages;
    @{selectedPackages}{ split ',', main::setupGetQuestion( 'ANTI_ROOTKITS_PACKAGES' ) } = ();

    for (keys %{$self->{'PACKAGES'}}) {
        next unless exists $selectedPackages{$_};
        my $package = "Package::AntiRootkits::${_}::${_}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            next unless $package->can( 'setEnginePermissions' );
            debug( sprintf( 'Calling action setEnginePermissions on %s', ref $package ) );
            my $rs = $package->setEnginePermissions();
            return $rs if $rs;
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
    @{$self->{'PACKAGES'}}{
        iMSCP::Dir->new( dirname => "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/AntiRootkits" )->getDirs()
    } = ();
    $self;
}

=item _installPackages(@packages)

 Install distribution packages

 Param list @packages List of distribution packages to install
 Return int 0 on success, other on failure

=cut

sub _installPackages
{
    my ($self, @packages) = @_;

    my $cmd = '';
    unless (iMSCP::Getopt->noprompt) {
        iMSCP::Dialog->getInstance->endGauge();
        $cmd = 'debconf-apt-progress --logstderr --';
    }

    $cmd = "UCF_FORCE_CONFFMISS=1 $cmd"; # Force installation of missing conffiles which are managed by UCF
    if ($main::forcereinstall) {
        $cmd .= " apt-get -y -o DPkg::Options::='--force-confnew' -o DPkg::Options::='--force-confmiss'".
            " --reinstall --auto-remove --purge --no-install-recommends install @packages";
    } else {
        $cmd .= " apt-get -y -o DPkg::Options::='--force-confnew' -o DPkg::Options::='--force-confmiss'".
            " --auto-remove --purge --no-install-recommends install @packages";
    }

    my $stdout;
    my $rs = execute( $cmd, iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \$stdout : undef, \ my $stderr );
    error( sprintf( 'Could not install packages: %s', $stderr || 'Unknown error' ) ) if $rs;
    return $rs if $rs;
    $rs;
}

=item _removePackages(@packages)

 Remove distribution packages

 Param list @packages List of distribution packages to remove
 Return int 0 on success, other on failure

=cut

sub _removePackages
{
    my ($self, @packages) = @_;

    # Do not try to uninstall packages that are not available
    my $rs = execute( "dpkg-query -W -f='\${Package}\\n' @packages 2>/dev/null", \ my $stdout );
    @{$packages} = split /\n/, $stdout;
    return 0 unless @{$packages};

    my $cmd = "apt-get -y --auto-remove --purge --no-install-recommends remove @packages";
    unless (iMSCP::Getopt->noprompt) {
        iMSCP::Dialog->getInstance->endGauge();
        $cmd = "debconf-apt-progress --logstderr -- $cmd";
    }

    $rs = execute( $cmd, iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \$stdout : undef, \ my $stderr );
    error( sprintf( 'Could not remove packages: %s', $stderr || 'Unknown error' ) ) if $rs;
    $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
