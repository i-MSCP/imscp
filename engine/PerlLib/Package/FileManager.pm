=head1 NAME

Package::FileManager - i-MSCP FileManager package

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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
#
# @category    i-MSCP
# @copyright   2010-2015 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Package::FileManager;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::EventManager;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP FileManager package. This is a wrapper that handle all available FileManager packages found in the FileManager
directory.

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

	my $rs = $eventManager->register('beforeSetupDialog', sub { push @{$_[0]}, sub { $self->showDialog(@_) }; 0; });
	return $rs if $rs;

	# preinstall tasks must be processed after frontEnd preInstall tasks
	$rs = $eventManager->register('afterFrontEndPreInstall', sub { $self->preinstallListener(); } );
	return $rs if $rs;

	# install tasks must be processed after frontEnd install tasks
	$eventManager->register('afterFrontEndInstall', sub { $self->installListener(); });
}

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub showDialog
{
	my ($self, $dialog) = @_;

	my $package = main::setupGetQuestion('FILEMANAGER_PACKAGE');
	my $rs = 0;

	if(
		$main::reconfigure ~~ ['filemanager', 'all', 'forced'] || ! $package || not $package ~~ @{$self->{'PACKAGES'}}
	) {
		($rs, $package) = $dialog->radiolist(
			"\nPlease select the Ftp Web file manager package you want to install:",
			$self->{'PACKAGES'},
			($package ne '' && $package ~~ $self->{'PACKAGES'} ) ? $package : @{$self->{'PACKAGES'}}[0]
		);
	}

	if($rs != 30) {
		main::setupSetQuestion('FILEMANAGER_PACKAGE', $package);

		$package = "Package::FileManager::${package}::${package}";
		eval "require $package";

		unless($@) {
			$package = $package->getInstance();
			$rs = $package->showDialog($dialog) if $package->can('showDialog');
			last if $rs;
		} else {
			error($@);
			return 1;
		}
	}

	$rs;
}

=item preinstallListener()

 Process preinstall tasks

 /!\ This method also trigger uninstallation of previous filemanager if needed.

 Return int 0 on success, other on failure

=cut

sub preinstallListener
{
	my $self = $_[0];

	my $oldPackage = $main::imscpOldConfig{'FILEMANAGER_PACKAGE'};

	# Ensure backward compatibility ( See #IP-1249 )
	if($oldPackage && $oldPackage eq 'AjaXplorer') {
		$oldPackage = 'Pydio';
	}

	my $package = main::setupGetQuestion('FILEMANAGER_PACKAGE');

	# Uninstall previous installed package if not identical
	if($oldPackage && $oldPackage ne $package) {
		my $rs = $self->uninstall($oldPackage);
		return $rs if $rs;
	}

	$package = "Package::FileManager::${package}::${package}";
	eval "require $package";

	unless($@) {
		$package = $package->getInstance();
		my $rs = $package->preinstall() if $package->can('preinstall');
		return $rs if $rs;
	} else {
		error($@);
		return 1;
	}

	0;
}

=item installListener()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub installListener
{
	my $self = $_[0];

	my $package = main::setupGetQuestion('FILEMANAGER_PACKAGE');

	$package = "Package::FileManager::${package}::${package}";
	eval "require $package";

	unless($@) {
		$package = $package->getInstance();
		my $rs = $package->install() if $package->can('install');
		return $rs if $rs;
	} else {
		error($@);
		return 1;
	}

	0;
}

=item uninstall( [ $package ])

 Process uninstall tasks

 Param string $package OPTIONAL Package to uninstall
 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my ($self, $package) = @_;

	$package ||= $main::imscpConfig{'FILEMANAGER_PACKAGE'};

	if($package) {
		$package = "Package::FileManager::${package}::${package}";
		eval "require $package";

		unless($@) {
			$package = $package->getInstance();
			my $rs = $package->uninstall() if $package->can('uninstall');
			return $rs if $rs;
		} else {
			error($@);
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
	my $self = $_[0];

	my $package = $main::imscpConfig{'FILEMANAGER_PACKAGE'};

	if($package ~~ @{$self->{'PACKAGES'}}) {
		my $package = "Package::FileManager::${package}::${package}";
		eval "require $package";

		unless($@) {
			$package = $package->getInstance();
			my $rs = $package->setGuiPermissions() if $package->can('setGuiPermissions');
			return $rs if $rs;
		} else {
			error($@);
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
	my $self = $_[0];

	# Find list of available FileManager packages
	@{$self->{'PACKAGES'}} = iMSCP::Dir->new(
		dirname => "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/FileManager"
	)->getDirs();

	# Permissions must be set after FrontEnd base permissions
	iMSCP::EventManager->getInstance()->register(
		'afterFrontendSetGuiPermissions', sub { $self->setPermissionsListener(@_); }
	);

	$self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
