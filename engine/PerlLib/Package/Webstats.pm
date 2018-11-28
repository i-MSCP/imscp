=head1 NAME

 Package::Webstats - i-MSCP Webstats package

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
use iMSCP::EventManager;
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

=item registerSetupListeners( \%eventManager )

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

=item showDialog( \%dialog )

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
    if ( $main::reconfigure =~ /^(?:webstats|all|forced)$/ || !%selectedPackages
        || grep { !exists $self->{'PACKAGES'}->{$_} && $_ ne 'No' } keys %selectedPackages
    ) {
        ( $rs, my $packages ) = $dialog->checkbox(
            <<'EOF', [ keys %{$self->{'PACKAGES'}} ], grep { exists $self->{'PACKAGES'}->{$_} && $_ ne 'No' } keys %selectedPackages );

Please select the Webstats packages you want to install
EOF
        %selectedPackages = ();
        @{selectedPackages}{@{$packages}} = ();
    }

    return $rs unless $rs < 30;

    main::setupSetQuestion( 'WEBSTATS_PACKAGES', %selectedPackages ? join ',', keys %selectedPackages : 'No' );

    for ( keys %{$self->{'PACKAGES'}} ) {
        next unless exists $selectedPackages{$_};
        my $package = "Package::Webstats::${_}::${_}";
        eval "require $package";
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        return 0 unless my $subref = $package->can( 'showDialog' );
        debug( sprintf( 'Executing showDialog action on %s', $package ));
        $rs = $subref->( $package->getInstance(), $dialog );
        return $rs if $rs;
    }

    0;
}

=item preinstall( )

 Process preinstall tasks

 /!\ This method also triggers uninstallation of unselected Webstats packages.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    my %selectedPackages;
    @{selectedPackages}{ split ',', main::setupGetQuestion( 'WEBSTATS_PACKAGES' ) } = ();

    my @distroPackages = ();
    for( keys %{$self->{'PACKAGES'}} ) {
        next if exists $selectedPackages{$_};
        my $package = "Package::Webstats::${_}::${_}";
        eval "require $package";
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        if ( my $subref = $package->can( 'uninstall' ) ) {
            debug( sprintf( 'Executing uninstall action on %s', $package ));
            my $rs = $subref->( $package->getInstance());
            return $rs if $rs;
        }

        ( my $subref = $package->can( 'getDistroPackages' ) ) or next;
        debug( sprintf( 'Executing getDistroPackages action on %s', $package ));
        push @distroPackages, $subref->( $package->getInstance());
    }

    if ( defined $main::skippackages && !$main::skippackages && @distroPackages ) {
        my $rs = $self->_removePackages( @distroPackages );
        return $rs if $rs;
    }

    @distroPackages = ();
    for ( keys %{$self->{'PACKAGES'}} ) {
        next unless exists $selectedPackages{$_};
        my $package = "Package::Webstats::${_}::${_}";
        eval "require $package";
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        if ( my $subref = $package->can( 'preinstall' ) ) {
            debug( sprintf( 'Executing preinstall action on %s', $package ));
            my $rs = $subref->( $package->getInstance());
            return $rs if $rs;
        }

        ( my $subref = $package->can( 'getDistroPackages' ) ) or next;
        debug( sprintf( 'Executing getDistroPackages action on %s', $package ));
        push @distroPackages, $subref->( $package->getInstance());
    }

    if ( defined $main::skippackages && !$main::skippackages && @distroPackages ) {
        my $rs = $self->_installPackages( @distroPackages );
        return $rs if $rs;
    }

    0;
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my %selectedPackages;
    @{selectedPackages}{ split ',', main::setupGetQuestion( 'WEBSTATS_PACKAGES' ) } = ();

    for ( keys %{$self->{'PACKAGES'}} ) {
        next unless exists $selectedPackages{$_} && $_ ne 'No';
        my $package = "Package::Webstats::${_}::${_}";
        eval "require $package";
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        ( my $subref = $package->can( 'install' ) ) or next;
        debug( sprintf( 'Executing install action on %s', $package ));
        my $rs = $subref->( $package->getInstance());
        return $rs if $rs;
    }

    0;
}

=item postinstall( )

 Process post install tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    my %selectedPackages;
    @{selectedPackages}{ split ',', main::setupGetQuestion( 'WEBSTATS_PACKAGES' ) } = ();

    for ( keys %{$self->{'PACKAGES'}} ) {
        next unless exists $selectedPackages{$_} && $_ ne 'No';
        my $package = "Package::Webstats::${_}::${_}";
        eval "require $package";
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        ( my $subref = $package->can( 'postinstall' ) ) or next;
        debug( sprintf( 'Executing postinstall action on %s', $package ));
        my $rs = $subref->( $package->getInstance());
        return $rs if $rs;
    }

    0;
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    my @distroPackages = ();
    for ( keys %{$self->{'PACKAGES'}} ) {
        my $package = "Package::Webstats::${_}::${_}";
        eval "require $package";
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        if ( my $subref = $package->can( 'uninstall' ) ) {
            debug( sprintf( 'Executing uninstall action on %s', $package ));
            my $rs = $subref->( $package->getInstance());
            return $rs if $rs;
        }

        ( my $subref = $package->can( 'getDistroPackages' ) ) or next;
        debug( sprintf( 'Executing getDistroPackages action on %s', $package ));
        push @distroPackages, $subref->( $package->getInstance());
    }

    $self->_removePackages( @distroPackages );
}

=item getPriority( )

 Get package priority

 Return int package priority

=cut

sub getPriority
{
    0;
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ($self) = @_;

    my %selectedPackages;
    @{selectedPackages}{ split ',', $main::imscpConfig{'WEBSTATS_PACKAGES'} } = ();

    for ( keys %{$self->{'PACKAGES'}} ) {
        next unless exists $selectedPackages{$_};
        my $package = "Package::Webstats::${_}::${_}";
        eval "require $package";
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        ( my $subref = $package->can( 'setEnginePermissions' ) ) or next;
        debug( sprintf( 'Executing setEnginePermissions action on %s', $package ));
        my $rs = $subref->( $package->getInstance());
        return $rs if $rs;
    }

    0;
}

=item addUser( \%data )

 Process addUser tasks

 Param hash \%data User data
 Return int 0 on success, other on failure

=cut

sub addUser
{
    my ($self, $data) = @_;

    my %selectedPackages;
    @{selectedPackages}{ split ',', $main::imscpConfig{'WEBSTATS_PACKAGES'} } = ();

    for ( keys %{$self->{'PACKAGES'}} ) {
        next unless exists $selectedPackages{$_};
        my $package = "Package::Webstats::${_}::${_}";
        eval "require $package";
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        ( my $subref = $package->can( 'addUser' ) ) or next;
        debug( sprintf( 'Executing addUser action on %s', $package ));
        my $rs = $subref->( $package->getInstance(), $data );
        return $rs if $rs;
    }

    0;
}

=item preaddDmn( \%data )

 Process preAddDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub preaddDmn
{
    my ($self, $data) = @_;

    my %selectedPackages;
    @{selectedPackages}{ split ',', $main::imscpConfig{'WEBSTATS_PACKAGES'} } = ();

    for ( keys %{$self->{'PACKAGES'}} ) {
        next unless exists $selectedPackages{$_};
        my $package = "Package::Webstats::${_}::${_}";
        eval "require $package";
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        ( my $subref = $package->can( 'preaddDmn' ) ) or next;
        debug( sprintf( 'Executing preaddDmn action on %s', $package ));
        my $rs = $subref->( $package->getInstance(), $data );
        return $rs if $rs;
    }

    0;
}

=item addDmn( \%data )

 Process addDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ($self, $data) = @_;

    my %selectedPackages;
    @{selectedPackages}{ split ',', $main::imscpConfig{'WEBSTATS_PACKAGES'} } = ();

    for ( keys %{$self->{'PACKAGES'}} ) {
        next unless exists $selectedPackages{$_};
        my $package = "Package::Webstats::${_}::${_}";
        eval "require $package";
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        ( my $subref = $package->can( 'addDmn' ) ) or next;
        debug( sprintf( 'Executing addDmn action on %s', $package ));
        my $rs = $subref->( $package->getInstance(), $data );
        return $rs if $rs;
    }

    0;
}

=item deleteDmn( \%data )

 Process deleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ($self, $data) = @_;

    my %selectedPackages;
    @{selectedPackages}{ split ',', $main::imscpConfig{'WEBSTATS_PACKAGES'} } = ();

    for ( keys %{$self->{'PACKAGES'}} ) {
        next unless exists $selectedPackages{$_};
        my $package = "Package::Webstats::${_}::${_}";
        eval "require $package";
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        ( my $subref = $package->can( 'deleteDmn' ) ) or next;
        debug( sprintf( 'Executing deleteDmn action on %s', $package ));
        my $rs = $subref->( $package->getInstance(), $data );
        return $rs if $rs;
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

=item addSub( \%data )

 Process addSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ($self, $data) = @_;

    $self->addDmn( $data );
}

=item deleteSub( \%data )

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

=item init( )

 Initialize instance

 Return Package::Webstats

=cut

sub _init
{
    my ($self) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    @{$self->{'PACKAGES'}}{
        iMSCP::Dir->new( dirname => "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats" )->getDirs()
    } = ();
    $self;
}

=item _installPackages( @packages )

 Install distribution packages

 Param list @packages List of packages to install
 Return int 0 on success, other on failure

=cut

sub _installPackages
{
    my (undef, @packages) = @_;

    iMSCP::Dialog->getInstance->endGauge();

    local $ENV{'UCF_FORCE_CONFFNEW'} = 1;
    local $ENV{'UCF_FORCE_CONFFMISS'} = 1;

    my ($aptVersion) = `apt-get --version` =~ /^apt\s+([\d.]+)/;
    my $stdout;
    my $rs = execute(
        [
            ( !iMSCP::Getopt->noprompt ? ( 'debconf-apt-progress', '--logstderr', '--' ) : () ),
            'apt-get', '--assume-yes', '--option', 'DPkg::Options::=--force-confnew',
            '--option', 'DPkg::Options::=--force-confmiss', '--option', 'Dpkg::Options::=--force-overwrite',
            ( $main::forcereinstall ? '--reinstall' : () ), '--auto-remove', '--purge', '--no-install-recommends',
            ( ( version->parse( $aptVersion ) < version->parse( '1.1.0' ) ) ? '--force-yes' : '--allow-downgrades' ),
            'install', @packages
        ],
        ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \$stdout : undef ),
        \ my $stderr
    );
    error( sprintf( "Couldn't install packages: %s", $stderr || 'Unknown error' )) if $rs;
    $rs;
}

=item _removePackages( @packages )

 Remove distribution packages

 Param list @packages Packages to remove
 Return int 0 on success, other on failure

=cut

sub _removePackages
{
    my (undef, @packages) = @_;

    return 0 unless @packages;

    # Do not try to remove packages that are not available
    my $rs = execute( "dpkg-query -W -f='\${Package}\\n' @packages 2>/dev/null", \ my $stdout );
    @packages = split /\n/, $stdout;
    return 0 unless @packages;

    iMSCP::Dialog->getInstance()->endGauge();

    $rs = execute(
        [
            ( !iMSCP::Getopt->noprompt ? ( 'debconf-apt-progress', '--logstderr', '--' ) : () ),
            'apt-get', '--assume-yes', '--auto-remove', '--purge', '--no-install-recommends', 'remove', @packages
        ],
        ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \ $stdout : undef ),
        \my $stderr
    );
    error( sprintf( "Couldn't remove packages: %s", $stderr || 'Unknown error' )) if $rs;
    $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
