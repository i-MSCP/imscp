=head1 NAME

Package::Webstats - i-MSCP Webstats package

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

package Package::Webstats;

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

 Webstats package for i-MSCP

 i-MSCP Webstats package.

 Handles Webstats packages found in the Webstats directory.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners(\%eventManager)

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
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

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub showDialog
{
    my ($self, $dialog) = @_;

    my %selectedPackages;
    @{selectedPackages}{ split ',', main::setupGetQuestion( 'WEBSTATS_PACKAGES' ) } = ();

    my $rs = 0;
    if ($main::reconfigure =~ /^(?:webstats|all|forced)$/ || !%selectedPackages
        || grep { !exists $self->{'PACKAGES'}->{$_} && $_ ne 'No' } keys %selectedPackages
    ) {
        ($rs, my $packages) = $dialog->checkbox(
            <<"EOF", [ keys %{$self->{'PACKAGES'}} ], grep { exists $self->{'PACKAGES'}->{$_} && $_ ne 'No' } keys %selectedPackages );

Please select the Webstats packages you want to install
EOF
        @{selectedPackages}{@{$packages}} = ();
    }

    return $rs unless $rs < 30;

    main::setupSetQuestion( 'WEBSTATS_PACKAGES', %selectedPackages ? join ',', keys %selectedPackages : 'No' );

    for (keys %{$self->{'PACKAGES'}}) {
        next unless exists $selectedPackages{$_};
        my $package = "Package::Webstats::${_}::${_}";
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

 /!\ This method also trigger uninstallation of unselected Webstats packages.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my $self = shift;

    my %selectedPackages;
    @{selectedPackages}{ split ',', main::setupGetQuestion( 'WEBSTATS_PACKAGES' ) } = ();

    my @distroPackages = ();
    for(keys %{$self->{'PACKAGES'}}) {
        next if exists $selectedPackages{$_};
        my $package = "Package::Webstats::${_}::${_}";
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
        my $package = "Package::Webstats::${_}::${_}";
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
    @{selectedPackages}{ split ',', main::setupGetQuestion( 'WEBSTATS_PACKAGES' ) } = ();

    for (keys %{$self->{'PACKAGES'}}) {
        next unless exists $selectedPackages{$_} && $_ ne 'No';
        my $package = "Package::Webstats::${_}::${_}";
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
        my $package = "Package::Webstats::${_}::${_}";
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
    
    @{selectedPackages}{ split ',', $main::imscpConfig{'WEBSTATS_PACKAGES'} } = ();

    for (keys %{$self->{'PACKAGES'}}) {
        next unless exists $selectedPackages{$_};
        my $package = "Package::Webstats::${_}::${_}";
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

=item preaddDmn(\%data)

 Process preAddDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub preaddDmn
{
    my ($self, $data) = @_;

    return 0 unless $data->{'FORWARD'} eq 'no';

    my %selectedPackages;
    @{selectedPackages}{ split ',', $main::imscpConfig{'WEBSTATS_PACKAGES'} } = ();

    for (keys %{$self->{'PACKAGES'}}) {
        next unless exists $selectedPackages{$_};
        my $package = "Package::Webstats::${_}::${_}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            next unless $package->can( 'preaddDmn' );
            debug( sprintf( 'Calling action preaddDmn on %s', ref $package ) );
            my $rs = $package->preaddDmn( $data );
            return $rs if $rs;
        } else {
            error( $@ );
            return 1;
        }
    }

    0;
}

=item addDmn(\%data)

 Process addDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ($self, $data) = @_;

    return 0 unless $data->{'FORWARD'} eq 'no';

    my %selectedPackages;
    @{selectedPackages}{ split ',', $main::imscpConfig{'WEBSTATS_PACKAGES'} } = ();

    for (keys %{$self->{'PACKAGES'}}) {
        next unless exists $selectedPackages{$_};
        my $package = "Package::Webstats::${_}::${_}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            next unless $package->can( 'preaddDmn' );
            debug( sprintf( 'Calling action addDmn on %s', ref $package ) );
            my $rs = $package->addDmn( $data );
            return $rs if $rs;
        } else {
            error( $@ );
            return 1;
        }
    }

    0;
}

=item deleteDmn(\%data)

 Process deleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ($self, $data) = @_;

    return 0 unless $data->{'FORWARD'} eq 'no';

    my %selectedPackages;
    @{selectedPackages}{ split ',', $main::imscpConfig{'WEBSTATS_PACKAGES'} } = ();

    for (keys %{$self->{'PACKAGES'}}) {
        next unless exists $selectedPackages{$_};
        my $package = "Package::Webstats::${_}::${_}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            next unless $package->can( 'deleteDmn' );
            debug( sprintf( 'Calling action addDmn on %s', ref $package ) );
            my $rs = $package->deleteDmn( $data );
            return $rs if $rs;
        } else {
            error( $@ );
            return 1;
        }
    }

    0;
}

=item preaddSub(\%data)

 Process preaddSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub preaddSub
{
    my ($self, $data) = @_;

    $self->preaddDmn( $data );
}

=item addSub(\%data)

 Process addSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ($self, $data) = @_;

    $self->addDmn( $data );
}

=item deleteSub(\%data)

 Process deleteSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
    my ($self, $data) = @_;

    $self->deleteDmn( $data );
}

=back

=head1 PRIVATE METHODS

=over 4

=item init()

 Initialize instance

 Return Package::Webstats

=cut

sub _init
{
    my $self = shift;

    # Find list of available AntiRootkits packages
    @{$self->{'PACKAGES'}}{
        iMSCP::Dir->new( dirname => "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats" )->getDirs()
    } = ();
    $self;
}

=item _installPackages(@packages)

 Install distribution packages

 Param list @packages List of packages to install
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
    $rs;
}

=item _removePackages(\@packages)

 Remove distribution packages

 Param list @packages Packages to remove
 Return int 0 on success, other on failure

=cut

sub _removePackages
{
    my ($self, @packages) = @_;

    # Do not try to uninstall packages that are not available
    my $rs = execute( "dpkg-query -W -f='\${Package}\\n' @packages 2>/dev/null", \ my $stdout );
    @packages = split /\n/, $stdout;
    return 0 unless @packages;

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
