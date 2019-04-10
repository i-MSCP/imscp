=head1 NAME

 Package::AbstractPackageCollection - Abstract Package Collection

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Package::AbstractPackageCollection;

use strict;
use warnings;
use Array::Utils qw/ array_diff array_minus intersect /;
use File::Basename 'dirname';
use iMSCP::Boolean;
use iMSCP::Debug  'error';
use iMSCP::Dialog;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute 'execute';
use iMSCP::Getopt;
use parent 'Common::SingletonClass';
use subs qw/
    registerSetupListeners

    preinstall install postinstall uninstall

    setGuiPermissions setEnginePermissions

    preaddDomain preaddCustomDNS preaddFtpUser preaddHtaccess preaddHtgroup preaddHtpasswd preaddMail preaddServerIP preaddSSLcertificate preaddSub preaddUser
    addDomain addCustomDNS addFtpUser addHtaccess addHtgroup addHtpasswd addMail addServerIP addSSLcertificate addSub addUser
    postaddDomain postaddCustomDNS postaddFtpUser postaddHtaccess postaddHtgroup postaddHtpasswd postaddMail postaddServerIP postaddSSLcertificate postaddSub postaddUser

    predeleteDmn predeleteCustomDNS predeleteFtpUser predeleteHtaccess predeleteHtgroup predeleteHtpasswd predeleteMail predeleteServerIP predeleteSSLcertificate predeleteSub predeleteUser
    deleteDmn deleteCustomDNS deleteFtpUser deleteHtaccess deleteHtgroup deleteHtpasswd deleteMail deleteServerIP deleteSSLcertificate deleteSub deleteUser
    postdeleteDmn postdeleteCustomDNS postdeleteFtpUser postdeleteHtaccess postdeleteHtgroup postdeleteHtpasswd postdeleteMail postdeleteServerIP postdeleteSSLcertificate postdeleteSub postdeleteUser

    prerestoreDmn prerestoreCustomDNS prerestoreFtpUser prerestoreHtaccess prerestoreHtgroup prerestoreHtpasswd prerestoreMail prerestoreServerIP prerestoreSSLcertificate prerestoreSub prerestoreUser
    restoreDmn restoreCustomDNS restoreFtpUser restoreHtaccess restoreHtgroup restoreHtpasswd restoreMail restoreServerIP restoreSSLcertificate restoreSub restoreUser
    postrestoreDmn postrestoreCustomDNS postrestoreFtpUser postrestoreHtaccess postrestoreHtgroup postrestoreHtpasswd postrestoreMail postrestoreServerIP postrestoreSSLcertificate postrestoreSub postrestoreUser

    predisableDmn predisableCustomDNS predisableFtpUser predisableHtaccess predisableHtgroup predisableHtpasswd predisableMail predisableServerIP predisableSSLcertificate predisableSub predisableUser
    disableDmn disableCustomDNS disableFtpUser disableHtaccess disableHtgroup disableHtpasswd disableMail disableServerIP disableSSLcertificate disableSub disableUser
    postdisableDmn postdisableCustomDNS postdisableFtpUser postdisableHtaccess postdisableHtgroup postdisableHtpasswd postdisableMail postdisableServerIP dpostisableSSLcertificate postdisableSub postdisableUser
/;

=head1 DESCRIPTION

 Abstract package collection.

=head1 PUBLIC METHODS

=over 4

=item getPriority( )

 Get package priority

 Return int package priority

=cut

sub getPriority
{
    0;
}

=item registerSetupListeners( \%em )

 Register setup event listeners

 Param iMSCP::EventManager \%em
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( $self, $em ) = @_;

    my $rs = $em->registerOne( 'beforeSetupDialog', sub {
        push @{ $_[0] }, sub { $self->setupDialog( @_ ) };
        0;
    } );
    $rs ||= $em->registerOne( 'beforeSetupServersAndPackages', sub {
        my @selectedPackages = split ',', ::setupGetQuestion( $self->getConfVarname());

        for my $package ( @selectedPackages ) {
            next if $package eq 'No';

            local $@;
            my $packageInstance = eval { $self->_getPackage( $package ); };
            if ( $@ ) {
                error( $@ );
                return 1;
            }

            ( my $sub = $packageInstance->can( 'registerSetupListeners' ) ) or next;
            $rs = $sub->( $packageInstance, $em );
            return $rs if $rs;
        }

        my @distributionPackages;

        for my $package ( array_diff( @selectedPackages, @{ $self->{'PACKAGES'} } ) ) {
            next if $package eq 'No';

            local $@;
            my $packageInstance = eval { $self->_getPackage( $package ); };
            if ( $@ ) {
                error( $@ );
                return 1;
            }

            if ( my $sub = $packageInstance->can( 'uninstall' ) ) {
                $rs = $sub->( $packageInstance );
                return $rs if $rs;
            }

            ( my $sub = $packageInstance->can( 'getDistributionPackages' ) ) or next;
            push @distributionPackages, $sub->( $packageInstance );
        }

        $self->_purgeDistributionPackages( @distributionPackages );
    } );
}

=item setupDialog( \%dialog )

 Setup dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 NEXT, 1 FAILURE, 30 BACKUP, 50 ESC

=cut

sub setupDialog
{
    my ( $self, $dialog ) = @_;

    my @selectedPackages = split ',', ::setupGetQuestion( $self->getConfVarname());

    if ( $::reconfigure =~ /^(?:@{ [ $self->getOptName() ] }|addons|all|forced)$/
        || !@selectedPackages
        || array_minus( @selectedPackages, @{ $self->{'PACKAGES'} } )
    ) {
        ( my $rs, my $packages ) = $dialog->checkbox(
            <<"EOF", [ grep ( $_ ne 'No', @{ $self->{'PACKAGES'} } ) ], intersect( @{ $self->{'PACKAGES'} }, @selectedPackages ));

Please select the @{ [ $self->getPackageHumanName() ] } you want to install:
EOF
        return $rs if $rs >= 30;
        @selectedPackages = @{ $packages } ? @{ $packages } : 'No';
    }

    ::setupSetQuestion( $self->getConfVarname(), join ',', @selectedPackages );

    for my $package ( @selectedPackages ) {
        next if $package eq 'No';

        local $@;
        my $packageInstance = eval { $self->_getPackage( $package ); };
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        if ( my $sub = $packageInstance->can( 'setupDialog' ) ) {
            my $rs = $sub->( $packageInstance, $dialog );
            return $rs if $rs;
        }
    }

    0;
}

=item preinstall( )

 Process pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    my @distributionPackages;

    for my $package ( split ',', $::imscpConfig{ $self->getConfVarname() } ) {
        next if $package eq 'No';

        local $@;
        my $packageInstance = eval { $self->_getPackage( $package ); };
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        if ( my $sub = $packageInstance->can( 'preinstall' ) ) {
            my $rs = $sub->( $packageInstance );
            return $rs if $rs;
        }

        unless ( $::skippackages ) {
            ( my $sub = $packageInstance->can( 'getDistributionPackages' ) ) or next;
            push @distributionPackages, $sub->( $packageInstance );
        }
    }

    return 0 if $::skippackages;

    $self->_installDistributionPackages( @distributionPackages );
}

=item getConfVarname( )

 Get package configuration variable name

 Return string

=cut

sub getConfVarname
{
    my ( $self ) = @_;

    die( "The @{ [ ref $self ] } package must implements the getConfVarname() method." );
}

=item getOptName( )

 Get package option name

 Return string

=cut

sub getOptName
{
    my ( $self ) = @_;

    die( "The @{ [ ref $self ] } package must implements the getOptName() method." );
}

=item getPackageHumanName( )

 Get package human name

 Return string

=cut

sub getPackageHumanName
{
    my ( $self ) = @_;

    die( "The @{ [ ref $self ] } package must implements the getPackageHumanName() method." );
}

=item AUTOLOAD( )

 Proxy to package methods

 Return int 0 on success, other on failure

=cut

sub AUTOLOAD
{
    my $self = shift;
    ( my $method = our $AUTOLOAD ) =~ s/.*:://;

    for my $package ( split ',', $::imscpConfig{ $self->getConfVarname() } ) {
        next if $package eq 'No';

        local $@;
        my $packageInstance = eval { $self->_getPackage( $package ); };
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        ( my $sub = $packageInstance->can( $method ) ) or next;
        my $rs = $sub->( $packageInstance, @_ );
        return $rs if $rs;
    }
}

=back

=head1 PRIVATE METHODS

=over 4

=item init( )

 Initialize instance

 Return Package::AbstractPackageCollection

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    @{ $self->{'PACKAGES'} } = (
        iMSCP::Dir->new( dirname => "@{ [ dirname __FILE__ ] }/@{ [ ( ref $self ) =~ s/.*:://r ] }" )->getDirs(), 'No'
    );
    $self;
}

=item _installDistributionPackages( @packages )

 Install distribution packages

 Param list @packages List of packages to install
 Return int 0 on success, other on failure

=cut

sub _installDistributionPackages
{
    my ( undef, @packages ) = @_;

    return unless @packages;

    iMSCP::Dialog->getInstance->endGauge();

    local $ENV{'UCF_FORCE_CONFFNEW'} = TRUE;
    local $ENV{'UCF_FORCE_CONFFMISS'} = TRUE;

    my ( $aptVersion ) = `apt-get --version` =~ /^apt\s+([\d.]+)/;
    my $stdout;
    my $rs = execute(
        [
            ( !iMSCP::Getopt->noprompt ? ( 'debconf-apt-progress', '--logstderr', '--' ) : () ),
            '/usr/bin/apt-get', '--assume-yes', '--option', 'DPkg::Options::=--force-confnew',
            '--option', 'DPkg::Options::=--force-confmiss', '--option', 'Dpkg::Options::=--force-overwrite',
            ( $::forcereinstall ? '--reinstall' : () ), '--auto-remove', '--purge', '--no-install-recommends',
            ( ( version->parse( $aptVersion ) < version->parse( '1.1.0' ) ) ? '--force-yes' : '--allow-downgrades' ),
            'install', @packages
        ],
        ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \$stdout : undef ),
        \my $stderr
    );
    error( sprintf( "Couldn't install packages: %s", $stderr || 'Unknown error' )) if $rs;
    $rs;
}

=item _purgeDistributionPackages( @packages )

 Remove distribution packages

 Param list @packages Packages to remove
 Return int 0 on success, other on failure

=cut

sub _purgeDistributionPackages
{
    my ( undef, @packages ) = @_;

    return 0 unless @packages;

    # Do not try to remove packages that are not available
    my $rs = execute( "/usr/bin/dpkg-query -W -f='\${Package}\\n' @packages 2>/dev/null", \my $stdout );
    @packages = split /\n/, $stdout;
    return 0 unless @packages;

    iMSCP::Dialog->getInstance()->endGauge();

    $rs = execute(
        [
            ( !iMSCP::Getopt->noprompt ? ( 'debconf-apt-progress', '--logstderr', '--' ) : () ),
            '/usr/bin/apt-get', '--assume-yes', '--auto-remove', '--purge', '--no-install-recommends', 'remove', @packages
        ],
        ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? \$stdout : undef ),
        \my $stderr
    );
    error( sprintf( "Couldn't purge packages: %s", $stderr || 'Unknown error' )) if $rs;
    $rs;
}

=item _getPackage( $package )

 Get instance of the given package

 Param string $package Package short name
 Return Package::AbstractPackageCollection, die on failure

=cut

sub _getPackage
{
    my ( $self, $package ) = @_;

    $self->{'_packages'}->{$package} //= do {
        $package = "@{ [ ref $self ] }::$package::$package";
        eval "require $package";
        die if $@;
        $package->getInstance();
    };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
