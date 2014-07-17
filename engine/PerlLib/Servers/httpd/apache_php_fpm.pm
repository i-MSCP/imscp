#!/usr/bin/perl

=head1 NAME

 Servers::httpd::apache_php_fpm - i-MSCP Apache2/PHP-FPM Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category     i-MSCP
# @copyright    2010-2014 by i-MSCP | http://i-mscp.net
# @author       Laurent Declercq <l.declercq@nuxwin.com>
# @link         http://i-mscp.net i-MSCP Home Site
# @license      http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache_php_fpm;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Config;
use iMSCP::Execute;
use iMSCP::TemplateParser;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Ext2Attributes qw(setImmutable clearImmutable isImmutable);
use iMSCP::Rights;
use iMSCP::Net;
use iMSCP::Service;
use File::Temp;
use File::Basename;

use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Apache2/PHP-FPM Server implementation

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks($hooksManager)

 Register setup hooks

 Param iMSCP::HooksManager $hooksManager Hooks manager instance
 Return int 0 on success, other on failure

=cut

sub registerSetupHooks($$)
{
	my (undef, $hooksManager) = @_;

	require Servers::httpd::apache_php_fpm::installer;
	Servers::httpd::apache_php_fpm::installer->getInstance()->registerSetupHooks($hooksManager);
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdPreInstall', 'apache_php_fpm');
	return $rs if $rs;

	$rs = $self->stopApache();
	return $rs if $rs;

	$rs = $self->stopPhpFpm();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdPreInstall', 'apache_php_fpm');
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	require Servers::httpd::apache_php_fpm::installer;
	Servers::httpd::apache_php_fpm::installer->getInstance()->install();
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdPostInstall', 'apache_php_fpm');
	return $rs if $rs;

	$self->{'hooksManager'}->register(
		'beforeSetupRestartServices', sub { push @{$_[0]}, [ sub { $self->startPhpFpm(); }, 'PHP5-FPM' ]; 0; }
	);

	$self->{'hooksManager'}->register(
		'beforeSetupRestartServices', sub { push @{$_[0]}, [ sub { $self->startApache(); }, 'HTTPD' ]; 0; }
	);

	$self->{'hooksManager'}->trigger('afterHttpdPostInstall', 'apache_php_fpm');
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->stopPhpFpm();
	return $rs if $rs;

	$rs = $self->stopApache();
	return $rs if $rs;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdUninstall', 'apache_php_fpm');
	return $rs if $rs;

	require Servers::httpd::apache_php_fpm::uninstaller;
	$rs = Servers::httpd::apache_php_fpm::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$rs = $self->{'hooksManager'}->trigger('afterHttpdUninstall', 'apache_php_fpm');
	return $rs if $rs;

	$rs = $self->startPhpFpm();
	return $rs if $rs;

	$self->startApache();
}

=item addUser(\%data)

 Process addUser tasks

 Param hash_ref $data Reference to a hash containing data as provided by User module
 Return int 0 on success, other on failure

=cut

sub addUser($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdAddUser', $data);
	return $rs if $rs;

	$self->setData($data);

	# Adding Apache user into i-MSCP virtual user group
	$rs = iMSCP::SystemUser->new('username' => $self->getRunningUser())->addToGroup($data->{'GROUP'});
	return $rs if $rs;

	$self->{'restart'} = 1;

	$self->flushData();

	$self->{'hooksManager'}->trigger('afterHttpdAddUser', $data);
}

=item deleteUser(\%data)

 Process deleteUser tasks

 Param hash_ref $data Reference to a hash containing data as provided by the module User
 Return int 0 on success, other on failure

=cut

sub deleteUser($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdDelUser', $data);
	return $rs if $rs;

	# Removing Apache user from i-MSCP virtual user group
	$rs = iMSCP::SystemUser->new('username' => $self->getRunningUser())->removeFromGroup($data->{'GROUP'});
	return $rs if $rs;

	$self->{'restart'} = 1;

	$self->{'hooksManager'}->trigger('afterHttpdDelUser', $data);
}

=item addDmn(\%data)

 Process addDmn tasks

 Param hash_ref $data Reference to a hash containing data as provided by Alias|Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub addDmn($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdAddDmn', $data);
	return $rs if $rs;

	$self->setData($data);

	$rs = $self->_addCfg($data);
	return $rs if $rs;

	$rs = $self->_addFiles($data);
	return $rs if $rs;

	$self->{'restart'} = 1;

	$self->flushData();

	$self->{'hooksManager'}->trigger('afterHttpdAddDmn', $data);
}

=item restoreDmn

 Process restoreDmn tasks

 Param hash_ref $data Reference to a hash containing data as provided by Alias|Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub restoreDmn($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdRestoreDmn', $data);
	return $rs if $rs;

	$self->setData($data);

	$rs = $self->_addFiles($data);
	return $rs if $rs;

	$self->flushData();

	$self->{'hooksManager'}->trigger('afterHttpdRestoreDmn', $data);
}

=item disableDmn(\%data)

 Process disableDmn tasks

 Param hash_ref $data Reference to a hash containing data as provided by Alias|Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub disableDmn($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdDisableDmn', $data);
	return $rs if $rs;

	$self->setData($data);

	my $ipMngr = iMSCP::Net->getInstance();

	$self->setData(
		{
			AUTHZ_ALLOW_ALL => (qv("v$self->{'config'}->{'HTTPD_VERSION'}") >= qv('v2.4.0'))
				? 'Require all granted' : 'Allow from all',
			HTTPD_LOG_DIR => $self->{'config'}->{'HTTPD_LOG_DIR'},
			DOMAIN_IP => ($ipMngr->getAddrVersion($data->{'DOMAIN_IP'}) eq 'ipv4')
				? $data->{'DOMAIN_IP'} : "[$data->{'DOMAIN_IP'}]"
		}
	);

	my @configTpl = ('');

	if($data->{'SSL_SUPPORT'}) {
		$self->setData({ CERTIFICATE => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$data->{'DOMAIN_NAME'}.pem" });
		push @configTpl, '_ssl';
	}

	for(@configTpl) {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'apacheWrkDir'}/$data->{'DOMAIN_NAME'}$_.conf"
		)->copyFile(
			"$self->{'apacheBkpDir'}/$data->{'DOMAIN_NAME'}$_.conf". time
		) if -f "$self->{'apacheWrkDir'}/$data->{'DOMAIN_NAME'}$_.conf";
		return $rs if $rs;

		$rs = $self->buildConfFile(
			"$self->{'apacheTplDir'}/domain_disabled$_.tpl",
			$data,
			{ 'destination' => "$self->{'apacheWrkDir'}/$data->{'DOMAIN_NAME'}$_.conf" }
		);
		return $rs if $rs;

		$rs = $self->installConfFile("$data->{'DOMAIN_NAME'}$_.conf");
		return $rs if $rs;
	}

	$self->{'restart'} = 1;

	$self->flushData();

	$self->{'hooksManager'}->trigger('afterHttpdDisableDmn', $data);
}

=item deleteDmn(\%data)

 Process deleteDmn tasks

 Param hash_ref $data Reference to a hash containing data as provided by Alias|Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub deleteDmn($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdDelDmn', $data);
	return $rs if $rs;

	# Disable apache site files
	for("$data->{'DOMAIN_NAME'}.conf", "$data->{'DOMAIN_NAME'}_ssl.conf") {
		$rs = $self->disableSites($_) if -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_";
		return $rs if $rs;
	}

	# Remove apache site files
	for(
		"$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}.conf",
		"$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf",
		"$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$data->{'DOMAIN_NAME'}.conf",
		"$self->{'apacheWrkDir'}/$data->{'DOMAIN_NAME'}.conf",
		"$self->{'apacheWrkDir'}/$data->{'DOMAIN_NAME'}_ssl.conf",
		"$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/$data->{'DOMAIN_NAME'}.conf"
	) {
		$rs = iMSCP::File->new('filename' => $_)->delFile() if -f $_;
		return $rs if $rs;
	}

	# Remove Web directory if needed

	my $webDir = $data->{'WEB_DIR'};

	if(-d $webDir) {
		if($data->{'DOMAIN_TYPE'} eq 'dmn') {
			# Unprotect Web root directory
			clearImmutable($webDir);

			$rs = iMSCP::Dir->new('dirname' => $webDir)->remove();
			return $rs if $rs;
		} else {
			my @sharedMountPoints = @{$data->{'SHARED_MOUNT_POINTS'}};

			# Normalize all mount points to match against
			s%(/.*?)/+$%$1% for @sharedMountPoints;

			# Normalize mount point
			(my $mountPoint = $data->{'MOUNT_POINT'}) =~ s%(/.*?)/+$%$1%;

			# We process only if we doesn't have other entity sharing identical mount point
			if($mountPoint ne '/' && not $mountPoint ~~ @sharedMountPoints) {

				# Unprotect Web root directory
				clearImmutable($webDir);

				my $skelDir;

				if($data->{'DOMAIN_TYPE'} eq 'dmn') {
					$skelDir = "$main::imscpConfig{'CONF_DIR'}/skel/domain";
				} elsif($data->{'DOMAIN_TYPE'} eq 'als') {
					$skelDir = "$main::imscpConfig{'CONF_DIR'}/skel/alias";
				} else {
					$skelDir = "$main::imscpConfig{'CONF_DIR'}/skel/subdomain";
				}

				for(iMSCP::Dir->new('dirname' => $skelDir)->getAll()) {
					if(-d "$webDir/$_") {
						$rs = iMSCP::Dir->new('dirname' => "$webDir/$_")->remove();
						return $rs if $rs;
					} elsif(-f _) {
						$rs = iMSCP::File->new('filename' => "$webDir/$_")->delFile();
						return $rs if $rs;
					}
				}

				(my $mountPointRootDir = $mountPoint) =~ s%^(/[^/]+).*%$1%;
				$mountPointRootDir = dirname("$data->{'HOME_DIR'}$mountPointRootDir");
				my $dirToRemove = $webDir;
				my $dirToRemoveParentDir;
				my $isProtectedDirToRemoveParentDir = 0;
				my $dirHandler = iMSCP::Dir->new();

				# Here, we loop over all directories, which are part of the mount point of the Web folder to remove.
				# In case a directory is not empty, we do not remove it since:
				# - Unknown directories/files are never removed (responsability is left to user)
				# - A directory can be the root directory of another mount point
				while($dirToRemove ne $mountPointRootDir && $dirHandler->isEmpty($dirToRemove)) {

					$dirToRemoveParentDir = dirname($dirToRemove);

					if(isImmutable($dirToRemoveParentDir)) {
						$isProtectedDirToRemoveParentDir = 1;
						clearImmutable($dirToRemoveParentDir);
					}

					clearImmutable($dirToRemove);

					$rs = $dirHandler->remove();
					return $rs if $rs;

					setImmutable($dirToRemoveParentDir) if $isProtectedDirToRemoveParentDir;

					$dirToRemove = $dirToRemoveParentDir;
				}
			}
		}
	}

	# Remove vlogger entry if any
	require iMSCP::Database;
	$rs = iMSCP::Database->factory()->doQuery(
		'dummy', 'DELETE FROM httpd_vlogger WHERE vhost = ?', $data->{'DOMAIN_NAME'}
	);
	unless(ref $rs eq 'HASH') {
		error("Unable to delete vlogger entry: $rs");
		return 1;
	}

	$self->{'restart'} = 1;

	$self->{'hooksManager'}->trigger('afterHttpdDelDmn', $data);
}

=item addSub(\%data)

 Process addSub tasks

 Param hash_ref $data Reference to a hash containing data as provided by Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub addSub($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdAddSub', $data);
	return $rs if $rs;

	$self->setData($data);

	$rs = $self->_addCfg($data);
	return $rs if $rs;

	$rs = $self->_addFiles($data);
	return $rs if $rs;

	$self->{'restart'} = 1;

	$self->flushData();

	$self->{'hooksManager'}->trigger('afterHttpdAddSub', $data);
}

=item restoreSub($\data)

 Process restoreSub tasks

 Param hash_ref $data Reference to a hash containing data as provided by Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub restoreSub($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdRestoreSub', $data);
	return $rs if $rs;

	$self->setData($data);

	$rs = $self->_addFiles($data);
	return $rs if $rs;

	$self->flushData();

	$self->{'hooksManager'}->trigger('afterHttpdRestoreSub', $data);

	0;
}

=item disableSub(\$data)

 Process disableSub tasks

 Param hash_ref $data Reference to a hash containing data as provided by Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub disableSub($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdDisableSub', $data);
	return $rs if $rs;

	$rs = $self->disableDmn($data);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdDisableSub', $data);
}

=item deleteSub(\%data)

 Process deleteSub tasks

 Param hash_ref $data Reference to a hash containing data as provided by the module Subdomain|SubAlias
 Return int 0 on success, other on failure

=cut

sub deleteSub($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdDelSub', $data);

	$rs = $self->deleteDmn($data);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdDelSub', $data);
}

=item AddHtuser(\%data)

 Process AddHtuser tasks

 Param hash_ref $data Reference to a hash containing data as provided by Htuser module
 Return int 0 on success, other on failure

=cut

sub addHtuser($$)
{
	my ($self, $data) = @_;

	my $webDir = $data->{'WEB_DIR'};
	my $fileName = $self->{'config'}->{'HTACCESS_USERS_FILENAME'};
	my $filePath = "$webDir/$fileName";

	# Unprotect root Web directory
	clearImmutable($webDir);

	my $file = iMSCP::File->new('filename' => $filePath);
	my $fileContent = $file->get() if -f $filePath;
	$fileContent = '' unless defined $fileContent;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdAddHtuser', \$fileContent, $data);
	return $rs if $rs;

	$fileContent =~ s/^$data->{'HTUSER_NAME'}:[^\n]*\n//gim;
	$fileContent .= "$data->{'HTUSER_NAME'}:$data->{'HTUSER_PASS'}\n";

	$rs = $self->{'hooksManager'}->trigger('afterHttpdAddHtuser', \$fileContent, $data);
	return $rs if $rs;

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $self->getRunningGroup());
	return $rs if $rs;

	# Protect root Web directory if needed
	setImmutable($webDir) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';

	0;
}

=item deleteHtuser(\%data)

 Process deleteHtuser tasks

 Param hash_ref $data Reference to a hash containing data as provided by Htuser module
 Return int 0 on success, other on failure

=cut

sub deleteHtuser($$)
{
	my ($self, $data) = @_;

	my $webDir = $data->{'WEB_DIR'};
	my $fileName = $self->{'config'}->{'HTACCESS_USERS_FILENAME'};
	my $filePath = "$webDir/$fileName";

	# Unprotect root Web directory
	clearImmutable($webDir);

	my $file = iMSCP::File->new('filename' => $filePath);
	my $fileContent = $file->get() if -f $filePath;
	$fileContent = '' unless defined $fileContent;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdDelHtuser', \$fileContent, $data);
	return $rs if $rs;

	$fileContent =~ s/^$data->{'HTUSER_NAME'}:[^\n]*\n//gim;

	$rs = $self->{'hooksManager'}->trigger('afterHttpdDelHtuser', \$fileContent, $data);
	return $rs if $rs;

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $self->getRunningGroup());
	return $rs if $rs;

	# Protect root Web directory if needed
	setImmutable($webDir) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';

	0;
}

=item addHtgroup(\%data)

 Process addHtgroup tasks

 Param hash_ref $data Reference to a hash containing data as provided by Htgroup module
 Return int 0 on success, other on failure

=cut

sub addHtgroup($$)
{
	my ($self, $data) = @_;;

	my $webDir = $data->{'WEB_DIR'};
	my $fileName = $self->{'config'}->{'HTACCESS_GROUPS_FILENAME'};
	my $filePath = "$webDir/$fileName";

	# Unprotect root Web directory
	clearImmutable($webDir);

	my $file = iMSCP::File->new('filename' => $filePath);
	my $fileContent = $file->get() if -f $filePath;
	$fileContent = '' unless defined $fileContent;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdAddHtgroup', \$fileContent, $data);
	return $rs if $rs;

	$fileContent =~ s/^$data->{'HTGROUP_NAME'}:[^\n]*\n//gim;
	$fileContent .= "$data->{'HTGROUP_NAME'}:$data->{'HTGROUP_USERS'}\n";

	$rs = $self->{'hooksManager'}->trigger('afterHttpdAddHtgroup', \$fileContent, $data);
	return $rs if $rs;

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $self->getRunningGroup());
	return $rs if $rs;

	# Protect root Web directory if needed
	setImmutable($webDir) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';

	0;
}

=item deleteHtgroup(\%data)

 Process deleteHtgroup tasks

 Param hash_ref $data Reference to a hash containing data as provided by Htgroup module
 Return int 0 on success, other on failure

=cut

sub deleteHtgroup
{
	my ($self, $data) = @_;

	my $webDir = $data->{'WEB_DIR'};
	my $fileName = $self->{'config'}->{'HTACCESS_GROUPS_FILENAME'};
	my $filePath = "$webDir/$fileName";

	# Unprotect root Web directory
	clearImmutable($webDir);

	my $file = iMSCP::File->new('filename' => $filePath);
	my $fileContent = $file->get() if -f $filePath;
	$fileContent = '' unless defined $fileContent;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdDelHtgroup', \$fileContent, $data);
	return $rs if $rs;

	$fileContent =~ s/^$data->{'HTGROUP_NAME'}:[^\n]*\n//gim;

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $self->{'hooksManager'}->trigger('afterHttpdDelHtgroup', \$fileContent, $data);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $self->getRunningGroup());
	return $rs if $rs;

	# Protect root Web directory if needed
	setImmutable($webDir) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';

	0;
}

=item addHtaccess(\%data)

 Process addHtaccess tasks

 Param hash_ref $data Reference to a hash containing data as provided by Htaccess module
 Return int 0 on success, other on failure

=cut

sub addHtaccess
{
	my ($self, $data) = @_;

	# Here we process only if AUTH_PATH directory exists
	# Note: It's temporary fix for 1.1.0-rc2 (See #749)
	if(-d $data->{'AUTH_PATH'}) {
		my $fileUser = "$data->{'HOME_PATH'}/$self->{'config'}->{'HTACCESS_USERS_FILENAME'}";
		my $fileGroup = "$data->{'HOME_PATH'}/$self->{'config'}->{'HTACCESS_GROUPS_FILENAME'}";
		my $filePath = "$data->{'AUTH_PATH'}/.htaccess";

		my $file = iMSCP::File->new('filename' => $filePath);
		my $fileContent = $file->get() if -f $filePath;
		$fileContent = '' unless defined $fileContent;

		my $rs = $self->{'hooksManager'}->trigger('beforeHttpdAddHtaccess', \$fileContent, $data);
		return $rs if $rs;

		my $bTag = "### START i-MSCP PROTECTION ###\n";
		my $eTag = "### END i-MSCP PROTECTION ###\n";
		my $tagContent = "AuthType $data->{'AUTH_TYPE'}\nAuthName \"$data->{'AUTH_NAME'}\"\nAuthUserFile $fileUser\n";

		if($data->{'HTUSERS'} eq '') {
			$tagContent .= "AuthGroupFile $fileGroup\nRequire group $data->{'HTGROUPS'}\n";
		} else {
			$tagContent .= "Require user $data->{'HTUSERS'}\n";
		}

		$fileContent = replaceBloc($bTag, $eTag, '', $fileContent);
		$fileContent = $bTag . $tagContent . $eTag . $fileContent;

		$rs = $self->{'hooksManager'}->trigger('afterHttpdAddHtaccess', \$fileContent, $data);
		return $rs if $rs;

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0640);
		return $rs if $rs;

		$file->owner($data->{'USER'}, $data->{'GROUP'});
	} else {
		0;
	}
}

=item deleteHtaccess(\%data)

 Process deleteHtaccess tasks

 Param hash_ref $data Reference to a hash containing data as provided by Htaccess module
 Return int 0 on success, other on failure

=cut

sub deleteHtaccess($$)
{
	my ($self, $data) = @_;

	# Here we process only if AUTH_PATH directory exists
	# Note: It's temporary fix for 1.1.0-rc2 (See #749)
	if(-d $data->{'AUTH_PATH'}) {
		my $fileUser = "$data->{'HOME_PATH'}/$self->{'config'}->{'HTACCESS_USERS_FILENAME'}";
		my $fileGroup = "$data->{'HOME_PATH'}/$self->{'config'}->{'HTACCESS_GROUPS_FILENAME'}";
		my $filePath = "$data->{'AUTH_PATH'}/.htaccess";

		my $file = iMSCP::File->new('filename' => $filePath);
		my $fileContent = $file->get() if -f $filePath;
		$fileContent = '' unless defined $fileContent;

		my $rs = $self->{'hooksManager'}->trigger('beforeHttpdDelHtaccess', \$fileContent, $data);
		return $rs if $rs;

		my $bTag = "### START i-MSCP PROTECTION ###\n";
		my $eTag = "### END i-MSCP PROTECTION ###\n";

		$fileContent = replaceBloc($bTag, $eTag, '', $fileContent);

		$rs = $self->{'hooksManager'}->trigger('afterHttpdDelHtaccess', \$fileContent, $data);
		return $rs if $rs;

		if($fileContent ne '') {
			$rs = $file->set($fileContent);
			return $rs if $rs;

			$rs = $file->save();
			return $rs if $rs;

			$rs = $file->mode(0640);
			return $rs if $rs;

			$rs = $file->owner($data->{'USER'}, $data->{'GROUP'});
			return $rs if $rs;
		} else {
			$rs = $file->delFile() if -f $filePath;
			return $rs if $rs;
		}
	}

	0;
}

=item addIps(\%data)

 Process addIps tasks

 Param hash_ref $data Reference to a hash containing data as provided by Ips module
 Return int 0 on success, other on failure

=cut

sub addIps($$)
{
	my ($self, $data) = @_;

	my $wrkFile = "$self->{'apacheWrkDir'}/00_nameserver.conf";

	# Backup current working file if any
	my $rs = $self->apacheBkpConfFile($wrkFile);
	return $rs if $rs;

	my $wrkFileH = iMSCP::File->new('filename' => $wrkFile);

	my $content = $wrkFileH->get();
	unless(defined $content) {
		error("Unable to read $wrkFile");
		return 1;
	}

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdAddIps', \$content, $data);
	return $rs if $rs;

	unless(qv("v$self->{'config'}->{'HTTPD_VERSION'}") >= qv('v2.4.0')) {
		$content =~ s/NameVirtualHost[^\n]+\n//gi;

		my $ipMngr = iMSCP::Net->getInstance();

		for(@{$data->{'SSLIPS'}}) {
			if($ipMngr->getAddrVersion($_) eq 'ipv4') {
				$content .= "NameVirtualHost $_:443\n";
			} else {
				$content .= "NameVirtualHost [$_]:443\n";
			}
		}

		for(@{$data->{'IPS'}}) {
			if($ipMngr->getAddrVersion($_) eq 'ipv4') {
				$content .= "NameVirtualHost $_:80\n";
			} else {
				$content .= "NameVirtualHost [$_]:80\n";
			}
		}
	} else {
		$content =~ s/\n# NameVirtualHost\n//;
	}

	$rs = $self->{'hooksManager'}->trigger('afterHttpdAddIps', \$content, $data);
	return $rs if $rs;

	$rs = $wrkFileH->set($content);
	return $rs if $rs;

	$rs = $wrkFileH->save();
	return $rs if $rs;

	$rs = $self->installConfFile('00_nameserver.conf');
	return $rs if $rs;

	$rs = $self->enableSites('00_nameserver.conf');
	return $rs if $rs;

	$self->{'restart'} = 1;

	delete $self->{'data'};

	0;
}

=item setGuiPermissions()

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	require Servers::httpd::apache_php_fpm::installer;
	Servers::httpd::apache_php_fpm::installer->getInstance()->setGuiPermissions();
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	require Servers::httpd::apache_php_fpm::installer;
	Servers::httpd::apache_php_fpm::installer->getInstance()->setEnginePermissions();
}

=item buildConf($cfgTpl, $filename, \%data)

 Build the given configuration template

 Param string $cfgTpl String representing content of the configuration template
 Param string $filename Configuration template name
 Param hash_ref $data Reference to a hash containing data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return string String representing content of configuration template or undef

=cut

sub buildConf($$$$)
{
	my ($self, $cfgTpl, $filename, $data) = @_;

	unless(defined $cfgTpl) {
		error('Empty configuration template...');
		return undef;
	}

	$self->{'hooksManager'}->trigger('beforeHttpdBuildConf', \$cfgTpl, $filename, $data);

	$cfgTpl = process($self->{'data'}, $cfgTpl);
	return undef if ! $cfgTpl;

	$self->{'hooksManager'}->trigger('afterHttpdBuildConf', \$cfgTpl, $filename, $data);

	$cfgTpl;
}

=item buildConfFile($file, \%data, [\%options = {}])

 Build the given configuration file

 Param string $file Absolute path to config file or config filename relative to the $self->{'apacheCfgDir'} directory
 Param hash_ref $data Reference to a hash containing data as provided by Alias|Domain|Subdomain|SubAlias modules
 Param hash_ref $options Reference to a hash containing options such as destination, mode, user and group for final file
 Return int 0 on success, other on failure

=cut

sub buildConfFile($$$;$)
{
	my ($self, $file, $data, $options) = @_;

	$options ||= {};

	my ($name, $path, $suffix) = fileparse($file);

	# Load template

	my $cfgTpl;
	my $rs = $self->{'hooksManager'}->trigger('onLoadTemplate', 'apache_php_fpm', $name, \$cfgTpl, $data);
	return $rs if $rs;

	unless(defined $cfgTpl) {
		$file = "$self->{'apacheCfgDir'}/$file" unless -d $path && $path ne './';

		$cfgTpl = iMSCP::File->new('filename' => $file)->get();
		unless(defined $cfgTpl) {
			error("Unable to read $file");
			return 1;
		}
	}

	# Build file

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildConfFile', \$cfgTpl, "$name$suffix", $data, $options);
	return $rs if $rs;

	$cfgTpl = $self->buildConf($cfgTpl, "$name$suffix", $data);
	return 1 unless defined $cfgTpl;

	$cfgTpl =~ s/\n{2,}/\n\n/g; # Remove any duplicate blank lines

	$rs = $self->{'hooksManager'}->trigger('afterHttpdBuildConfFile', \$cfgTpl, "$name$suffix", $data, $options);
	return $rs if $rs;

	# Store file

	my $fileHandler = iMSCP::File->new(
		'filename' => ($options->{'destination'} ? $options->{'destination'} : "$self->{'apacheWrkDir'}/$name$suffix")
	);

	$rs = $fileHandler->set($cfgTpl);
	return $rs if $rs;

	$rs = $fileHandler->save();
	return $rs if $rs;

	$rs = $fileHandler->mode($options->{'mode'} ? $options->{'mode'} : 0644);
	return $rs if $rs;

	$fileHandler->owner(
		$options->{'user'} ? $options->{'user'} : $main::imscpConfig{'ROOT_USER'},
		$options->{'group'} ? $options->{'group'} : $main::imscpConfig{'ROOT_GROUP'}
	);
}

=item installConfFile($file, [\%options = {}])

 Install the given configuration file

 Param string $file Absolute path to config file or config filename relative to the $self->{'apacheWrkDir'} directory
 Param hash_ref $options Reference to a hash containing options such as destination, mode, user and group for final file
 Return int 0 on success, other on failure

=cut

sub installConfFile($$;$)
{
	my ($self, $file, $options) = @_;

	$options ||= {};

	my ($name, $path, $suffix) = fileparse($file);

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdInstallConfFile', "$name$suffix", $options);
	return $rs if $rs;

	$file = "$self->{'apacheWrkDir'}/$file" unless -d $path && $path ne './';

	my $fileHandler = iMSCP::File->new('filename' => $file);

	$rs = $fileHandler->mode($options->{'mode'} ? $options->{'mode'} : 0644);
	return $rs if $rs;

	$rs = $fileHandler->owner(
		$options->{'user'} ? $options->{'user'} : $main::imscpConfig{'ROOT_USER'},
		$options->{'group'} ? $options->{'group'} : $main::imscpConfig{'ROOT_GROUP'}
	);
	return $rs if $rs;

	$rs = $fileHandler->copyFile(
		$options->{'destination'}
			? $options->{'destination'} : "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$name$suffix"
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdInstallConfFile', "$name$suffix", $options);
}

=item setData(\%data)

 Make the given data available for this server

 Param hash_ref $data Reference to a hash containing data to make available for this server
 Return int 0

=cut

sub setData($$)
{
	my ($self, $data) = @_;

	@{$self->{'data'}}{keys %{$data}} = values %{$data};

	0;
}

=item flushData()

 Flush all data set via the setData() method

 Return int 0

=cut

sub flushData()
{
	delete $_[0]->{'data'};

	0;
}

=item getTraffic($timestamp)

 Get httpd traffic data

 Param string $timestamp Timestamp
 Return hash_ref Traffic data or die on failure

=cut

sub getTraffic($$)
{
	my ($self, $timestamp) = @_;

	my $trafficDbPath = "$main::imscpConfig{'VARIABLE_DATA_DIR'}/http_traffic.db";

	# Load traffic database
	tie my %trafficDb, 'iMSCP::Config', 'fileName' => $trafficDbPath, 'noerrors' => 1;

	my $db = iMSCP::Database->factory();

	my $rawDb = $db->startTransaction();

	# Collect data from upstream traffic data source
	eval {
		require Date::Format;
		Date::Format->import();

		my $ldate = time2str('%Y%m%d', $timestamp);

		my $trafficData = $db->doQuery(
		 	'vhost', 'SELECT vhost, bytes FROM httpd_vlogger WHERE ldate <= ? FOR UPDATE', $ldate
		);

		if(%{$trafficData}) {
			# Getting HTTPD traffic
			$trafficDb{$_} += $trafficData->{$_}->{'bytes'} for keys %{$trafficData};

			# Deleting upstream source data
			$rawDb->do('DELETE FROM httpd_vlogger WHERE ldate <= ?', undef, $ldate);
		}

		$rawDb->commit();
	};

	if($@) {
		$rawDb->rollback();
		%trafficDb = ();
		$db->endTransaction();
		die("Unable to collect traffic data: $@");
	}

	$db->endTransaction();

	# Schedule deletion of traffic database. This is only done on success. On failure, the traffic database is
	# kept in place for later processing. In such case, data already processed (put in database) are zeroed by
	# the traffic processor script.
	$self->{'hooksManager'}->register(
		'afterVrlTraffic',
		sub {
			if(-f $trafficDbPath) {
				iMSCP::File->new('filename' => $trafficDbPath)->delFile();
			} else {
				0;
			}
		}
	) and die(iMSCP::Debug::getLastError());

	\%trafficDb;
}

=item deleteTmp()

 Delete temporary files (PHP session files)

 Return int 0 on success, other on failure

=cut

sub deleteTmp
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdDelTmp');
	return $rs if $rs;

	# Get session.gc_maxlifetime value from global PHP FPM php.ini file
	my $max = 1440;

	unless(-f "$self->{'phpfpmConfig'}->{'PHP_FPM_CONF_DIR'}/php.ini") {
		error("$self->{'phpfpmConfig'}->{'PHP_FPM_CONF_DIR'}/php.ini doesn't exist");
		return $rs if $rs;
	} else {
		my $file = iMSCP::File->new('filename' => "$self->{'phpfpmConfig'}->{'PHP_FPM_CONF_DIR'}/php.ini");
		my $fileContent = $file->get();

		unless(defined $fileContent) {
			error("Unable to read $self->{'phpfpmConfig'}->{'PHP_FPM_CONF_DIR'}/php.ini");
			return $rs if $rs;
		} else {
			$fileContent =~ m/^\s*session.gc_maxlifetime\s*=\s*([0-9]+).*$/gim;
			my $cur = $1 || 0;
			$max = $cur if $cur > $max;
		}
	}

	$max = POSIX::floor($max/60);

	my ($cmd, $stdout, $stderr);

	# panel sessions gc (Only for security since Zend_Session normaly take care of this)
	$cmd = "[ -d /var/www/imscp/gui/data/sessions ] && /usr/bin/find /var/www/imscp/gui/data/sessions/ -type f -cmin +$max -exec rm -v {} \\;";
	$rs = execute($cmd, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Error while executing $cmd.\nReturned value is $rs") if ! $stderr && $rs;
	return $rs if $rs;

	# customers sessions gc
	# TODO should we check for any maxlifetime overriden in pools configuration file?
	$cmd = "$main::imscpConfig{'CMD_NICE'} -n 19 /usr/bin/find $main::imscpConfig{'USER_WEB_DIR'} -type f -path '*/phptmp/sess_*' -cmin +$max -exec rm -v {} \\;";
	$rs = execute($cmd, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Error while executing $cmd.\nReturned value is $rs") if ! $stderr && $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdDelTmp');
}

=item getRunningUser()

 Get user name under which the Apache server is running

 Return string User name under which the apache server is running

=cut

sub getRunningUser
{
	$_[0]->{'config'}->{'HTTPD_USER'};
}

=item getRunningGroup()

 Get group name under which the Apache server is running.

 Return string Group name under which the apache server is running

=cut

sub getRunningGroup
{
	$_[0]->{'config'}->{'HTTPD_GROUP'};
}

=item enableSites($sites)

 Enable the given sites

 Param string $sites Names of sites to enable, each separated by a space
 Return int 0 on sucess, other on failure

=cut

sub enableSites($$)
{
	my ($self, $sites) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdEnableSites', \$sites);
	return $rs if $rs;

	my ($stdout, $stderr);

	for(split(' ', $sites)){
		if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_") {
			$rs = execute("$self->{'config'}->{'CMD_A2ENSITE'} $_", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;

			$self->{'restart'} = 1;
		} else {
			warning("Site $_ doesn't exist");
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdEnableSites', $sites);
}

=item disableSites($sites)

 Disable the given sites

 Param string $sites Names of sites to disable, each separated by a space
 Return int 0 on sucess, other on failure

=cut

sub disableSites($$)
{
	my ($self, $sites) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdDisableSites', \$sites);
	return $rs if $rs;

	my ($stdout, $stderr);

	for(split(' ', $sites)) {
		if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_") {
			$rs = execute("$self->{'config'}->{'CMD_A2DISSITE'} $_", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;

			$self->{'restart'} = 1;
		} else {
			warning("Site $_ doesn't exist");
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdDisableSites', $sites);
}

=item enableModules($modules)

 Enable the given Apache modules

 Param string $modules Names of Apache modules to enable, each separated by a space
 Return int 0 on sucess, other on failure

=cut

sub enableModules($$)
{
	my ($self, $modules) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdEnableModules', \$modules);
	return $rs if $rs;

	my ($stdout, $stderr);
	$rs = execute("$self->{'config'}->{'CMD_A2ENMOD'} $modules", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$self->{'restart'} = 1;

	$self->{'hooksManager'}->trigger('afterHttpdEnableModules', $modules);
}

=item disableModules($modules)

 Disable the given Apache modules

 Param string $modules Names of Apache modules to disable, each separated by a space
 Return int 0 on sucess, other on failure

=cut

sub disableModules($$)
{
	my ($self, $modules) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdDisableModules', \$modules);
	return $rs if $rs;

	my ($stdout, $stderr);
	$rs = execute("$self->{'config'}->{'CMD_A2DISMOD'} $modules", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$self->{'restart'} = 1;

	$self->{'hooksManager'}->trigger('afterHttpdDisableModules', $modules);
}

=item startPhpFpm()

 Start PHP FPM

 Return int 0 on success, 1 on failure

=cut

sub startPhpFpm
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdStartPhpFpm');
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->start($self->{'phpfpmConfig'}->{'PHP_FPM_SNAME'});

	if($rs) {
		# In case the service do not start, we must ensure that it's not because no conffile exists
		# By default (on new installs), no pool configuration file is created and so, the service cannot start)
		my @conffiles = iMSCP::Dir->new(
			'dirname' => $self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}, 'fileType' => '.conf'
		)->getFiles();

		if(@conffiles) {
			error("Unable to start $self->{'phpfpmConfig'}->{'PHP_FPM_SNAME'} service") if $rs;
			return $rs if $rs;
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdStartPhpFpm');
}

=item stopPhpFpm()

 Stop PHP FPM

 Return int 0 on success, 1 on failure

=cut

sub stopPhpFpm
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdStopPhpFpm');
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->stop($self->{'phpfpmConfig'}->{'PHP_FPM_SNAME'});
	error("Unable to stop $self->{'phpfpmConfig'}->{'PHP_FPM_SNAME'} service") if $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdStopPhpFpm');
}

=item restartPhpFpm()

 Restart or Reload PHP FPM

 Return int 0 on success, 1 on failure

=cut

sub restartPhpFpm
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdRestartPhpFpm');
	return $rs if $rs;

	if($self->{'forceRestart'}) {
		$rs = iMSCP::Service->getInstance()->restart($self->{'phpfpmConfig'}->{'PHP_FPM_SNAME'});
		error("Unable to restart $self->{'phpfpmConfig'}->{'PHP_FPM_SNAME'}") if $rs;
		return $rs if $rs;
	} else {
		$rs = iMSCP::Service->getInstance()->reload($self->{'phpfpmConfig'}->{'PHP_FPM_SNAME'});
		error("Unable to reload $self->{'phpfpmConfig'}->{'PHP_FPM_SNAME'} service") if $rs;
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterHttpdRestartPhpFpm');
}

=item forceRestart()

 Force Apache and/or PHP FPM to be restarted instead of simply reloaded

 Return int 0

=cut

sub forceRestart
{
	$_[0]->{'forceRestart'} = 1;

	0;
}

=item startApache()

 Start Apache

 Return int 0 on success, 1 on failure

=cut

sub startApache
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdStart');
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->start($self->{'config'}->{'HTTPD_SNAME'});
	error("Unable to start $self->{'config'}->{'HTTPD_SNAME'} service") if $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdStart');
}

=item stopApache()

 Stop Apache

 Return int 0 on success, 1 on failure

=cut

sub stopApache
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdStop');
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->stop($self->{'config'}->{'HTTPD_SNAME'});
	error("Unable to stop $self->{'config'}->{'HTTPD_SNAME'} service") if $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdStop');
}

=item restartApache()

 Restart or Reload Apache

 Return int 0 on success, 1 on failure

=cut

sub restartApache
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdRestart');
	return $rs if $rs;

	if($self->{'forceRestart'}) {
		$rs = iMSCP::Service->getInstance()->restart($self->{'config'}->{'HTTPD_SNAME'});
		error("Unable to restart $self->{'config'}->{'HTTPD_SNAME'}") if $rs;
		return $rs if $rs;
	} else {
		$rs = iMSCP::Service->getInstance()->reload($self->{'config'}->{'HTTPD_SNAME'});
		error("Unable to reload $self->{'config'}->{'HTTPD_SNAME'} service") if $rs;
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterHttpdRestart');
}

=item apacheBkpConfFile($filepath, [$prefix = ''], [$system = 0])

 Backup the given Apache configuration file

 Param string $filepath Configuration file path
 Param string $prefix Prefix to use
 Param bool $system Backup as system file (default false)
 Return 0 on success, other on failure

=cut

sub apacheBkpConfFile($$;$$)
{
	my ($self, $filepath, $prefix, $system) = @_;

	$prefix ||= '';
	$system ||= 0;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', $filepath, $prefix, $system);
	return $rs if $rs;

	if(-f $filepath) {
		my $file = iMSCP::File->new('filename' => $filepath);
		my $filename = fileparse($filepath);

		if($system && ! -f "$self->{'apacheBkpDir'}/$prefix$filename.system") {
			$rs = $file->copyFile("$self->{'apacheBkpDir'}/$prefix$filename.system");
			return $rs if $rs;
		} else {
			$rs = $file->copyFile("$self->{'apacheBkpDir'}/$prefix$filename." . time);
			return $rs if $rs;
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdBkpConfFile', $filepath, $prefix, $system);
}

=item phpfpmBkpConfFile($filepath, [$prefix = ''], [$system = 0])

 Backup the given PHP FPM configuration file

 Param string $filepath Configuration file path
 Param string $prefix Prefix to use
 Param bool $system Param int $system Backup as system file (default false)
 Return 0 on success, other on failure

=cut

sub phpfpmBkpConfFile($$;$$)
{
	my ($self, $filepath, $prefix, $system) = @_;

	$prefix ||= '';
	$system ||= 0;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', $filepath, $prefix, $system);
	return $rs if $rs;

	if(-f $filepath) {
		my $file = iMSCP::File->new('filename' => $filepath);
		my $filename = fileparse($filepath);

		if($system && ! -f "$self->{'phpfpmBkpDir'}/$prefix$filename.system") {
			$rs = $file->copyFile("$self->{'phpfpmBkpDir'}/$prefix$filename.system");
			return $rs if $rs;
		} else {
			$rs = $file->copyFile("$self->{'phpfpmBkpDir'}/$prefix$filename." . time);
			return $rs if $rs;
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdBkpConfFile', $filepath, $prefix, $system);
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance

 Return Servers::httpd::apache_php_fpm

=cut

sub _init
{
	my $self = $_[0];

	$self->{'start'} = 0;
	$self->{'restart'} = 0;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforeHttpdInit', $self, 'apache_php_fpm'
	) and fatal('apache_php_fpm - beforeHttpdInit hook has failed');

	$self->{'apacheCfgDir'} = "$main::imscpConfig{'CONF_DIR'}/apache";
	$self->{'apacheBkpDir'} = "$self->{'apacheCfgDir'}/backup";
	$self->{'apacheWrkDir'} = "$self->{'apacheCfgDir'}/working";
	$self->{'apacheTplDir'} = "$self->{'apacheCfgDir'}/parts";

	tie %{$self->{'config'}}, 'iMSCP::Config', 'fileName' => "$self->{'apacheCfgDir'}/apache.data";

	$self->{'phpfpmCfgDir'} = "$main::imscpConfig{'CONF_DIR'}/php-fpm";
	$self->{'phpfpmBkpDir'} = "$self->{'phpfpmCfgDir'}/backup";
	$self->{'phpfpmWrkDir'} = "$self->{'phpfpmCfgDir'}/working";
	$self->{'phpfpmTplDir'} = "$self->{'phpfpmCfgDir'}/parts";

	tie %{$self->{'phpfpmConfig'}}, 'iMSCP::Config', 'fileName' => "$self->{'phpfpmCfgDir'}/phpfpm.data";

	$self->{'hooksManager'}->trigger(
		'afterHttpdInit', $self, 'apache_php_fpm'
	) and fatal('apache_php_fpm - afterHttpdInit hook has failed');

	# Register event listener which is responsible to clean vhost template files
	$self->{'hooksManager'}->register('afterHttpdBuildConfFile', sub { $self->_cleanTemplate(@_)});

	$self;
}

=item _addCfg(\%data)

 Add configuration files for the given domain or subdomain

 Param hash_ref $data Reference to a hash containing data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _addCfg($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdAddCfg', $data);
	return $rs if $rs;

	$self->setData($data);

	# Disable and backup Apache sites if any
	for("$data->{'DOMAIN_NAME'}.conf", "$data->{'DOMAIN_NAME'}_ssl.conf"){
		$rs = $self->disableSites($_) if -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_";
		return $rs if $rs;

		$rs = $self->apacheBkpConfFile("$self->{'apacheWrkDir'}/$_", '', 0);
		return $rs if $rs;
	}

	# Remove previous Apache sites if any
	for(
		"$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}.conf",
		"$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf",
		"$self->{'apacheWrkDir'}/$data->{'DOMAIN_NAME'}.conf",
		"$self->{'apacheWrkDir'}/$data->{'DOMAIN_NAME'}_ssl.conf"
	) {
		$rs = iMSCP::File->new('filename' => $_)->delFile() if -f $_;
		return $rs if $rs;
	}

	# Build Apache sites - Begin

	my %configs;
	$configs{"$data->{'DOMAIN_NAME'}.conf"} = { 'redirect' => 'domain_redirect.tpl', 'normal' => 'domain.tpl' };

	if($data->{'SSL_SUPPORT'}) {
		$configs{"$data->{'DOMAIN_NAME'}_ssl.conf"} = {
			'redirect' => 'domain_redirect_ssl.tpl', 'normal' => 'domain_ssl.tpl'
		};

		$self->{'data'}->{'CERTIFICATE'} = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$data->{'DOMAIN_NAME'}.pem";
	}

	my $poolLevel = $self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_LEVEL'};

	if($data->{'FORWARD'} eq 'no') {
		# Dertermine pool name according pool level setting
		if($poolLevel eq 'per_user') {
			$self->setData({ POOL_NAME => $data->{'ROOT_DOMAIN_NAME'} });
		} elsif($poolLevel eq 'per_domain') {
			$self->setData({ POOL_NAME => $data->{'PARENT_DOMAIN_NAME'} });
		} elsif($poolLevel eq 'per_site') {
			$self->setData({ POOL_NAME => $data->{'DOMAIN_NAME'} });
		} else {
			error("Unknown php-fpm pool level: $poolLevel");
			return 1;
		}
	}

	my $apache24 = (qv("v$self->{'config'}->{'HTTPD_VERSION'}") >= qv('v2.4.0'));

	my $ipMngr = iMSCP::Net->getInstance();

	$self->setData(
		{
			HTTPD_LOG_DIR => $self->{'config'}->{'HTTPD_LOG_DIR'},
			HTTPD_CUSTOM_SITES_DIR => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'},
			AUTHZ_ALLOW_ALL => $apache24 ? 'Require all granted' : 'Allow from all',
			AUTHZ_DENY_ALL => $apache24 ? 'Require all denied' : 'Deny from all',
			DOMAIN_IP => ($ipMngr->getAddrVersion($data->{'DOMAIN_IP'}) eq 'ipv4')
				? $data->{'DOMAIN_IP'} : "[$data->{'DOMAIN_IP'}]"
		}
	);

	for(keys %configs) {
		$rs = $self->buildConfFile(
			$data->{'FORWARD'} eq 'no'
				? "$self->{'apacheTplDir'}/$configs{$_}->{'normal'}"
				: "$self->{'apacheTplDir'}/$configs{$_}->{'redirect'}",
			$data,
			{ 'destination' => "$self->{'apacheWrkDir'}/$_" }
		);
		return $rs if $rs;

		$rs = $self->installConfFile($_);
		return $rs if $rs;
	}

	# Build Apache sites - End

	# Build and install custom Apache configuration file
	$rs = $self->buildConfFile(
		"$self->{'apacheTplDir'}/custom.conf.tpl",
		$data,
		{ 'destination' => "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$data->{'DOMAIN_NAME'}.conf"}
	) unless (-f "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$data->{'DOMAIN_NAME'}.conf");
	return $rs if $rs;

	# Enable all Apache sites
	for(keys %configs) {
		$rs = $self->enableSites($_);
		return $rs if $rs;
	}

	# Build PHP FPM pool file - Begin

	# Backup older pool files if any
	$rs = $self->phpfpmBkpConfFile("$self->{'phpfpmWrkDir'}/$data->{'DOMAIN_NAME'}.conf");
	return $rs if $rs;

	my $domainType = $data->{'DOMAIN_TYPE'};

	if(
		$data->{'FORWARD'} eq 'no' &&
		(
			($poolLevel eq 'per_user' && $domainType eq 'dmn') ||
			($poolLevel eq 'per_domain' && ($domainType eq 'dmn' || $domainType eq 'als')) ||
			$poolLevel eq 'per_site'
		)
	) {
		$rs = $self->buildConfFile(
			"$self->{'phpfpmTplDir'}/pool.conf",
			$data,
			{ 'destination' => "$self->{'phpfpmWrkDir'}/$data->{'DOMAIN_NAME'}.conf" }
		);
		return $rs if $rs;

		$rs = $self->installConfFile(
			"$self->{'phpfpmWrkDir'}/$data->{'DOMAIN_NAME'}.conf",
			{ 'destination' => "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/$data->{'DOMAIN_NAME'}.conf" }
		);
		return $rs if $rs;
	} else {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'phpfpmWrkDir'}/$data->{'DOMAIN_NAME'}.conf"
		)->delFile() if -f "$self->{'phpfpmWrkDir'}/$data->{'DOMAIN_NAME'}.conf";
		return $rs if $rs;

		$rs = iMSCP::File->new(
			'filename' => "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/$data->{'DOMAIN_NAME'}.conf"
		)->delFile() if -f "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/$data->{'DOMAIN_NAME'}.conf";
		return $rs if $rs;
	}

	# Build PHP FPM pool file - End

	$self->{'hooksManager'}->trigger('afterHttpdAddCfg', $data);
}

=item _dmnFolders(\%data)

 Get Web folders list to create for the given domain or subdomain

 Param hash_ref $data Reference to a hash containing data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return list List of Web folders to create

=cut

sub _dmnFolders($$)
{
	my ($self, $data) = @_;

	my @folders;

	$self->{'hooksManager'}->trigger('beforeHttpdDmnFolders', \@folders);

	push(@folders, [
		"$self->{'config'}->{'HTTPD_LOG_DIR'}/$data->{'DOMAIN_NAME'}",
		$main::imscpConfig{'ROOT_USER'},
		$main::imscpConfig{'ROOT_GROUP'},
		0750
	]);

	$self->{'hooksManager'}->trigger('afterHttpdDmnFolders', \@folders);

	@folders;
}

=item _addFiles(\%data)

 Add default directories and files for the given domain or subdomain

 Param hash_ref $data Reference to a hash containing data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on sucess, other on failure

=cut

sub _addFiles($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdAddFiles', $data);
	return $rs if $rs;

	# Create directories as returned by the dmnFolders() method
	for ($self->_dmnFolders($data)) {
		$rs = iMSCP::Dir->new(
			'dirname' => $_->[0]
		)->make(
			{ 'user' => $_->[1], 'group' => $_->[2], 'mode' => $_->[3] }
		);
		return $rs if $rs;
	}

	# Create Web folder tree only if th domain is not forwarded
	if($data->{'FORWARD'} eq 'no') {
		my $webDir = $data->{'WEB_DIR'};

		# Build domain/subdomain Web directory tree using skeleton from (eg /etc/imscp/skel) - BEGIN

		my $skelDir;

		if($data->{'DOMAIN_TYPE'} eq 'dmn') {
			$skelDir = "$main::imscpConfig{'CONF_DIR'}/skel/domain";
		} elsif($data->{'DOMAIN_TYPE'} eq 'als') {
			$skelDir = "$main::imscpConfig{'CONF_DIR'}/skel/alias";
		} else {
			$skelDir = "$main::imscpConfig{'CONF_DIR'}/skel/subdomain";
		}

		my ($tmpDir, $stdout, $stderr);

		if(-d $skelDir) {
			$tmpDir = File::Temp->newdir();

			$rs = execute("$main::imscpConfig{'CMD_CP'} -RT $skelDir $tmpDir", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		} else {
			error("Skeleton directory $skelDir doesn't exist.");
			return 1;
		}

		# Build default domain/subdomain page if needed (if htdocs doesn't exist or is empty)
		# Remove it from Web directory tree otherwise
		if(! -d "$webDir/htdocs" || iMSCP::Dir->new('dirname' => "$webDir/htdocs")->isEmpty()) {
			if(-d "$tmpDir/htdocs") {
				# Test needed in case admin removed the index.html file from the skeleton
				if(-f "$tmpDir/htdocs/index.html") {
					my $fileSource = "$tmpDir/htdocs/index.html";
					$rs = $self->buildConfFile($fileSource, $data, { 'destination' => $fileSource });
					return $rs if $rs;
				}
			} else {
				# TODO should we just create it instead?
				error("Web folder skeleton $skelDir must provide the 'htdocs' directory.");
				return 1;
			}
		} else {
			$rs = iMSCP::Dir->new('dirname' => "$tmpDir/htdocs")->remove();
			return $rs if $rs;
		}

		if(
			$data->{'DOMAIN_TYPE'} eq 'dmn' && -d "$webDir/errors" &&
			! iMSCP::Dir->new('dirname' => "$webDir/errors")->isEmpty()
		) {
			if(-d "$tmpDir/errors") {
				$rs = iMSCP::Dir->new('dirname' => "$tmpDir/errors")->remove();
				return $rs if $rs;
			} else {
				warning("Web folder skeleton $skelDir should provide the 'errors' directory.");
			}
		}

		# Build domain/subdomain Web directory tree using skeleton from (eg /etc/imscp/skel) - END

		my $protectedParentDir = dirname($webDir);
		$protectedParentDir = dirname($protectedParentDir) while(! -d $protectedParentDir);
		my $isProtectedParentDir = 0;

		# Unprotect parent directory if needed
		if(isImmutable($protectedParentDir)) {
			$isProtectedParentDir = 1;
			clearImmutable($protectedParentDir);
		}

		# Unprotect Web root directory
		if(-d $webDir) {
			clearImmutable($webDir);
		} else {
			# Create Web directory
			$rs = iMSCP::Dir->new(
				'dirname' => $webDir
			)->make(
				{ 'user' => $data->{'USER'}, 'group' => $data->{'GROUP'}, 'mode' => 0750 }
			);
			return $rs if $rs;
		}

		# Copy Web directory tree to the Web directory
		$rs = execute("$main::imscpConfig{'CMD_CP'} -nRT $tmpDir $webDir", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		# Permissions, owner and group - Begin

		# Sets permissions for root of Web folder
		$rs = setRights($webDir, { 'user' => $data->{'USER'}, 'group' => $data->{'GROUP'}, 'mode' => '0750' });
		return $rs if $rs;

		# Get list of directories/files for which permissions, owner and group must be set
		my @items = iMSCP::Dir->new('dirname' => $skelDir)->getAll();

		# Set default owner and group recursively
		for(@items) {
			$rs = setRights(
				"$webDir/$_", { 'user' => $data->{'USER'}, 'group' => $data->{'GROUP'}, 'recursive' => 1 }
			) if -e "$webDir/$_";
			return $rs if $rs;
		}

		# Sets default permissions recursively, excepted for directories for which permissions of directories and files
		# they contain should be preserved
		for(@items) {
			$rs = setRights(
				"$webDir/$_",
				{
					'dirmode' => '0750',
					'filemode' => '0640',
					'recursive' => ($_ eq '00_private' || $_ eq 'cgi-bin' || $_ eq 'htdocs') ? 0 : 1
				}
			) if -d _;
			return $rs if $rs;
		}

		# Sets owner and group for files that should be hidden to user
		for('domain_disable_page', '.htgroup', '.htpasswd') {
			$rs = setRights(
				"$webDir/$_",
				{
					'user' => $main::imscpConfig{'ROOT_USER'},
					'group' => $self->getRunningGroup(),
					'recursive' => 1
				}
			) if -e "$webDir/$_";
			return $rs if $rs;
		}

		# Permissions, owner and group - Ending

		if($data->{'WEB_FOLDER_PROTECTION'} eq 'yes') {
			# Protect Web root directory
			setImmutable($webDir);

			# Protect parent directory if needed
			setImmutable($protectedParentDir) if $isProtectedParentDir;
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdAddFiles', $data);
}

=item _cleanTemplate($sectionName, \$cfgTpl)

 Event listener which is responsible to remove useless configuration snippets in vhost template files

 Param string_ref $cfgTpl Reference to template file content
 Param string $filename Template filename
 Param hash_ref $data Reference to a hash containing data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0

=cut

sub _cleanTemplate($$$)
{
	my ($self, $cfgTpl, $filename, $data) = @_;

	if($filename =~ /(?:domain\.tpl|domain_ssl\.tpl|00_master\.conf|00_master_ssl\.conf)/) {
		unless($data->{'CGI_SUPPORT'} eq 'yes') {
			$$cfgTpl = replaceBloc("# SECTION suexec BEGIN.\n", "# SECTION suexec END.\n", '', $$cfgTpl);
			$$cfgTpl = replaceBloc("# SECTION cgi_support BEGIN.\n", "# SECTION cgi_support END.\n", '', $$cfgTpl);
		}

		if($data->{'PHP_SUPPORT'} eq 'yes') {
			$$cfgTpl = replaceBloc("# SECTION php_disabled BEGIN.\n", "# SECTION php_disabled END.\n", '', $$cfgTpl);
		} else {
			$$cfgTpl = replaceBloc("# SECTION php_enabled BEGIN.\n", "# SECTION php_enabled END.\n", '', $$cfgTpl);
		}

		$$cfgTpl = replaceBloc("# SECTION fcgid BEGIN.\n", "# SECTION fcgid END.\n", '', $$cfgTpl);
		$$cfgTpl = replaceBloc("# SECTION itk BEGIN.\n", "# SECTION itk END.\n", '', $$cfgTpl);
	}

	0;
}

=item END

 Code triggered at the very end of script execution

 -  Start or restart PHP FPM if needed
 - Start or restart apache if needed

 Return int Exit code

=cut

END
{
	unless($main::execmode && $main::execmode eq 'setup') {
		my $exitCode = $?;
		my $self = Servers::httpd::apache_php_fpm->getInstance();
		my $rs = 0;

		if($self->{'start'}) {
			$rs = $self->startPhpFpm();
			$rs |= $self->startApache();
		} elsif($self->{'restart'}) {
			$rs = $self->restartPhpFpm();
			$rs |= $self->restartApache();
		}

		$? = $exitCode || $rs;
	}
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
