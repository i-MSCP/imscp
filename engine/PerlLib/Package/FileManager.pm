=head1 NAME

 Package::FileManager - i-MSCP FileManager package

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

package Package::FileManager;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP FileManager package.

 Handles FileManager packages found in the FileManager directory.

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

    my $package = main::setupGetQuestion( 'FILEMANAGER_PACKAGE' );

    my $rs = 0;
    if ( $main::reconfigure =~ /^(?:filemanager|all|forced)$/ || !$package || !exists $self->{'PACKAGES'}->{$package} ) {
        ( $rs, $package ) = $dialog->radiolist(
            <<"EOF", [ keys %{$self->{'PACKAGES'}} ], exists $self->{'PACKAGES'}->{$package} ? $package : ( keys %{$self->{'PACKAGES'}} )[0] );

Please select the Ftp Web file manager package you want to install:
EOF
    }

    return $rs unless $rs < 30;

    main::setupSetQuestion( 'FILEMANAGER_PACKAGE', $package );

    $package = "Package::FileManager::${package}::${package}";
    eval "require $package";
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    return 0 unless my $subref = $package->can( 'showDialog' );
    debug( sprintf( 'Executing showDialog action on %s', $package ));
    $subref->( $package->getInstance(), $dialog );
}

=item preinstall( )

 Process preinstall tasks

 /!\ This method also trigger uninstallation of unselected file manager packages.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    my $oldPackage = exists $main::imscpOldConfig{'FILEMANAGER_ADDON'}
        ? $main::imscpOldConfig{'FILEMANAGER_ADDON'} # backward compatibility with 1.1.x Serie (upgrade process)
        : $main::imscpOldConfig{'FILEMANAGER_PACKAGE'};

    # Ensure backward compatibility
    if ( $oldPackage eq 'AjaXplorer' ) {
        $oldPackage = 'Pydio';
    } elsif ( $oldPackage eq 'Net2FTP' ) {
        $oldPackage = 'Net2ftp';
    }

    my $package = main::setupGetQuestion( 'FILEMANAGER_PACKAGE' );
    if ( $oldPackage ne '' && $oldPackage ne $package ) {
        my $rs = $self->uninstall( $oldPackage );
        return $rs if $rs;
    }

    $package = "Package::FileManager::${package}::${package}";
    eval "require $package";
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    return 0 unless my $subref = $package->can( 'preinstall' );
    debug( sprintf( 'Executing preinstall action on %s', $package ));
    $subref->( $package->getInstance());
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $package = main::setupGetQuestion( 'FILEMANAGER_PACKAGE' );
    $package = "Package::FileManager::${package}::${package}";
    eval "require $package";
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    return 0 unless my $subref = $package->can( 'install' );
    debug( sprintf( 'Executing install action on %s', $package ));
    $subref->( $package->getInstance());
}

=item uninstall( [ $package ])

 Process uninstall tasks

 Param string $package OPTIONAL Package to uninstall
 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my (undef, $package) = @_;

    $package ||= $main::imscpConfig{'FILEMANAGER_PACKAGE'};
    return 0 unless $package;

    $package = "Package::FileManager::${package}::${package}";
    eval "require $package";
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    return 0 unless my $subref = $package->can( 'uninstall' );
    debug( sprintf( 'Executing uninstall action on %s', $package ));
    $subref->( $package->getInstance());
}

=item getPriority( )

 Get package priority

 Return int package priority

=cut

sub getPriority
{
    0;
}

=item setGuiPermissions( )

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFileManagerSetGuiPermissions' );
    return $rs if $rs;

    my $package = $main::imscpConfig{'FILEMANAGER_PACKAGE'};
    return 0 unless exists $self->{'PACKAGES'}->{$package};

    $package = "Package::FileManager::${package}::${package}";
    eval "require $package";
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    return 0 unless my $subref = $package->can( 'setGuiPermissions' );

    debug( sprintf( 'Executing setGuiPermissions action on %s', $package ));
    $rs = $subref->( $package->getInstance());
    $rs ||= $self->{'eventManager'}->trigger( 'afterFileManagerSetGuiPermissions' );
}

=back

=head1 PRIVATE METHODS

=over 4

=item init( )

 Initialize insance

 Return Package::AntiRootkits

=cut

sub _init
{
    my ($self) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    @{$self->{'PACKAGES'}}{
        iMSCP::Dir->new( dirname => "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/FileManager" )->getDirs()
    } = ();

    # Quick fix for disabling Pydio package if PHP >= 7 is detected
    if ( defined $main::execmode && $main::execmode eq 'setup' ) {
        delete $self->{'PACKAGES'}->{'Pydio'} if version->parse( $self->_getPhpVersion()) >= version->parse( '7.0.0' );
    }

    $self;
}

=item _getPhpVersion( )

 Get PHP version

 Return int PHP version on success, die on failure

=cut

sub _getPhpVersion
{
    my $rs = execute( 'php -nv', \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    $stdout =~ /PHP\s+([\d.]+)/ or die(
        sprintf( "Couldn't find PHP version from `php -nv` command output: %s", $stdout )
    );
    $1;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
