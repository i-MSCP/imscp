=head1 NAME

Package::Webmail - i-MSCP Webmail package

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

package Package::Webmail;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::EventManager;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Webmail package.

 Wrapper that handles all available Webmail packages found in the Webmail directory.

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

    my $rs = $eventManager->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]}, sub { $self->showDialog( @_ ) };
            0;
        }
    );
    $rs ||= $eventManager->register( 'afterFrontEndPreInstall', sub { $self->preinstallListener(); } );
    $rs ||= $eventManager->register( 'afterFrontEndInstall', sub { $self->installListener(); } );
}

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub showDialog
{
    my ($self, $dialog) = @_;

    my $packages = [ split ',', main::setupGetQuestion( 'WEBMAIL_PACKAGES' ) ];
    my $rs = 0;

    if ($main::reconfigure =~ /^webmails|all|forced$/ || !@{$packages}
        || grep { my $__ = $_; !grep($_ eq $__, ( @{$self->{'PACKAGES'}}, 'No' )) } @{$packages}
    ) {
        ($rs, $packages) = $dialog->checkbox(
            <<"EOF", [ @{$self->{'PACKAGES'}} ], grep($_ eq 'No', @{$packages}) ? () : @{$packages} ? @{$packages} : @{$self->{'PACKAGES'}} );

Please select the webmail packages you want to install
EOF
    }

    return $rs unless $rs < 30;

    main::setupSetQuestion( 'WEBMAIL_PACKAGES', @{$packages} ? join ',', @{$packages} : 'No' );

    return $rs if grep($_ eq 'No', @{$packages});

    for my $package(@{$packages}) {
        $package = "Package::Webmail::${package}::${package}";
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

=item preinstallListener()

 Process preinstall tasks

 /!\ This method also trigger uninstallation of unselected webmail packages.

 Return int 0 on success, other on failure

=cut

sub preinstallListener
{
    my $self = shift;

    my @packages = split ',', main::setupGetQuestion( 'WEBMAIL_PACKAGES' );
    my $packagesToInstall = [ grep { $_ ne 'No'} @packages ];
    my $packagesToUninstall = [ grep { my $__ = $_; !grep($_ eq $__, @{$packagesToInstall}) } @{$self->{'PACKAGES'}} ];

    if (@{$packagesToUninstall}) {
        my $rs = $self->uninstall( @{$packagesToUninstall} );
        return $rs if $rs;
    }

    return 0 unless @{$packagesToInstall};

    for my $package(@{$packagesToInstall}) {
        $package = "Package::Webmail::${package}::${package}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            if ($package->can( 'preinstall' )) {
                debug( sprintf( 'Calling action preinstall on %s', ref $package ) );
                my $rs = $package->preinstall();
                return $rs if $rs;
            }
        } else {
            error( $@ );
            return 1;
        }
    }

    0;
}

=item installListener()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub installListener
{
    my @packages = split ',', main::setupGetQuestion( 'WEBMAIL_PACKAGES' );

    return 0 if grep($_ eq 'No', @packages);

    for my $package(@packages) {
        $package = "Package::Webmail::${package}::${package}";
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

=item uninstall( [ $package ])

 Process uninstall tasks

 Param list @packages OPTIONAL Packages to uninstall
 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self, @packages) = @_;

    @packages = split ',', $main::imscpConfig{'WEBMAIL_PACKAGES'} unless @packages;

    for my $package(@packages) {
        next unless grep($_ eq $package, @{$self->{'PACKAGES'}});
        $package = "Package::Webmail::${package}::${package}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            if ($package->can( 'uninstall' )) {
                debug( sprintf( 'Calling action uninstall on %s', ref $package ) );
                my $rs = $package->uninstall();
                return $rs if $rs;
            }
        } else {
            error( $@ );
            return 1;
        }
    }

    0;
}

=item setPermissionsListener()

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setPermissionsListener
{
    my $self = shift;

    my @packages = split ',', $main::imscpConfig{'WEBMAIL_PACKAGES'};

    for my $package(@packages) {
        next unless grep($_ eq $package, @{$self->{'PACKAGES'}});
        $package = "Package::Webmail::${package}::${package}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            if ($package->can( 'setGuiPermissions' )) {
                debug( sprintf( 'Calling action setGuiPermissions on %s', ref $package ) );
                my $rs = $package->setGuiPermissions();
                return $rs if $rs;
            }
        } else {
            error( $@ );
            return 1;
        }
    }

    0;
}

=item deleteMail(\%data)

 Process deleteMail tasks

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub deleteMail
{
    my ($self, $data) = @_;

    my @packages = split ',', $main::imscpConfig{'WEBMAIL_PACKAGES'};

    for my $package(@packages) {
        next unless grep($_ eq $package, @{$self->{'PACKAGES'}});
        $package = "Package::Webmail::${package}::${package}";
        eval "require $package";
        unless ($@) {
            $package = $package->getInstance();
            if ($package->can( 'deleteMail' )) {
                debug( sprintf( 'Calling action deleteMail on %s', ref $package ) );
                my $rs = $package->deleteMail( $data );
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

 Initialize insance

 Return Package::AntiRootkits

=cut

sub _init()
{
    my $self = shift;

    @{$self->{'PACKAGES'}} = iMSCP::Dir->new(
        dirname => "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webmail"
    )->getDirs();

    iMSCP::EventManager->getInstance()->register(
        'afterFrontendSetGuiPermissions', sub { $self->setPermissionsListener( @_ ); }
    );
    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
