=head1 NAME

 Servers::httpd::apache_php_fpm - i-MSCP Apache2/PHP-FPM Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Servers::httpd::apache_php_fpm;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Config;
use iMSCP::Execute;
use iMSCP::TemplateParser;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Ext2Attributes qw(setImmutable clearImmutable isImmutable);
use iMSCP::Rights;
use iMSCP::Mount qw /mount umount/;
use iMSCP::Net;
use iMSCP::ProgramFinder;
use iMSCP::Service;
use File::Temp;
use File::Basename;
use Scalar::Defer;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Apache2/PHP-FPM Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners(\%eventManager)

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
	my (undef, $eventManager) = @_;

	require Servers::httpd::apache_php_fpm::installer;
	Servers::httpd::apache_php_fpm::installer->getInstance()->registerSetupListeners($eventManager);
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeHttpdPreInstall', 'apache_php_fpm');
	$self->stop();
	$self->{'eventManager'}->trigger('afterHttpdPreInstall', 'apache_php_fpm');
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeHttpdInstall', 'apache_php_fpm');

	require Servers::httpd::apache_php_fpm::installer;
	my $rs = Servers::httpd::apache_php_fpm::installer->getInstance()->install();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterHttpdInstall', 'apache_php_fpm');
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeHttpdPostInstall', 'apache_php_fpm');

	my $serviceMngr = iMSCP::Service->getInstance();
	$serviceMngr->enable($self->{'phpfpmConfig'}->{'PHP_FPM_SNAME'});
	$serviceMngr->enable($self->{'config'}->{'HTTPD_SNAME'});

	$self->{'eventManager'}->register(
		'beforeSetupRestartServices', sub { push @{$_[0]}, [ sub { $self->start(); }, 'Apache2/php5-fpm httpd server' ]; 0; }
	);
	$self->{'eventManager'}->trigger('afterHttpdPostInstall', 'apache_php_fpm');
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeHttpdUninstall', 'apache_php_fpm');

	require Servers::httpd::apache_php_fpm::uninstaller;
	my $rs = Servers::httpd::apache_php_fpm::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterHttpdUninstall', 'apache_php_fpm');
	$self->restart();
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeHttpdSetEnginePermissions');

	require Servers::httpd::apache_php_fpm::installer;
	my $rs = Servers::httpd::apache_php_fpm::installer->getInstance()->setEnginePermissions();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterHttpdSetEnginePermissions');
}

=item addUser(\%data)

 Process addUser tasks

 Param hash \%data User data
 Return int 0 on success, other on failure

=cut

sub addUser
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdAddUser', $data);
	$self->setData($data);

	my $rs = iMSCP::SystemUser->new( username => $self->getRunningUser() )->addToGroup($data->{'GROUP'});
	return $rs if $rs;

	$self->{'restart'} = 1;
	$self->flushData();
	$self->{'eventManager'}->trigger('afterHttpdAddUser', $data);
}

=item deleteUser(\%data)

 Process deleteUser tasks

 Param hash \%data User data
 Return int 0 on success, other on failure

=cut

sub deleteUser
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdDelUser', $data);

	my $rs = iMSCP::SystemUser->new( username => $self->getRunningUser() )->removeFromGroup($data->{'GROUP'});
	return $rs if $rs;

	$self->{'restart'} = 1;
	$self->{'eventManager'}->trigger('afterHttpdDelUser', $data);
}

=item addDmn(\%data)

 Process addDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdAddDmn', $data);
	$self->setData($data);

	my $rs = $self->_addCfg($data);
	return $rs if $rs;

	$rs = $self->_addFiles($data);
	return $rs if $rs;

	$self->{'restart'} = 1;
	$self->flushData();
	$self->{'eventManager'}->trigger('afterHttpdAddDmn', $data);
}

=item restoreDmn(\%data)

 Process restoreDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub restoreDmn
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdRestoreDmn', $data);
	$self->setData($data);

	my $rs = $self->_addFiles($data);
	return $rs if $rs;

	$self->flushData();
	$self->{'eventManager'}->trigger('afterHttpdRestoreDmn', $data);
}

=item disableDmn(\%data)

 Process disableDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub disableDmn
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdDisableDmn', $data);
	$self->setData($data);

	my $ipMngr = iMSCP::Net->getInstance();
	my $version = $self->{'config'}->{'HTTPD_VERSION'};

	$self->setData({
		BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
		AUTHZ_ALLOW_ALL => (version->parse($version) >= version->parse('2.4.0'))
			? 'Require all granted' : 'Allow from all',
		HTTPD_LOG_DIR => $self->{'config'}->{'HTTPD_LOG_DIR'},
		DOMAIN_IP => ($ipMngr->getAddrVersion($data->{'DOMAIN_IP'}) eq 'ipv4')
			? $data->{'DOMAIN_IP'} : "[$data->{'DOMAIN_IP'}]"
	});

	my %configTpls = ( '' => (!$data->{'HSTS_SUPPORT'}) ? 'domain_disabled.tpl' : 'domain_redirect.tpl' );

	if($data->{'SSL_SUPPORT'}) {
		$self->setData({ CERTIFICATE => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$data->{'DOMAIN_NAME'}.pem" });
		$configTpls{'_ssl'} =  'domain_disabled_ssl.tpl';
	}

	for my $configTplType(keys %configTpls) {
		my $rs = $self->buildConfFile("$self->{'apacheTplDir'}/$configTpls{$configTplType}", $data, {
			destination => "$self->{'apacheWrkDir'}/$data->{'DOMAIN_NAME'}$configTplType.conf" }
		);
		return $rs if $rs;

		$rs = $self->installConfFile("$data->{'DOMAIN_NAME'}$configTplType.conf");
		return $rs if $rs;
	}

	$self->{'restart'} = 1;
	$self->flushData();
	$self->{'eventManager'}->trigger('afterHttpdDisableDmn', $data);
}

=item deleteDmn(\%data)

 Process deleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdDelDmn', $data);

	for my $conffile("$data->{'DOMAIN_NAME'}.conf", "$data->{'DOMAIN_NAME'}_ssl.conf") {
		if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$conffile") {
			my $rs = $self->disableSites($conffile);
			return $rs if $rs;
		}
	}

	for my $conffile(
		"$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}.conf",
		"$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf",
		"$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$data->{'DOMAIN_NAME'}.conf",
		"$self->{'apacheWrkDir'}/$data->{'DOMAIN_NAME'}.conf",
		"$self->{'apacheWrkDir'}/$data->{'DOMAIN_NAME'}_ssl.conf",
		"$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/$data->{'DOMAIN_NAME'}.conf"
	) {
		if(-f $conffile) {
			iMSCP::File->new( filename => $conffile )->delFile();
		}
	}

	my $rs = $self->umountLogsFolder($data);
	return $rs if $rs;

	unless($data->{'SHARED_MOUNT_POINT'} || ! -d $data->{'WEB_DIR'}) {
		(my $userWebDir = $main::imscpConfig{'USER_WEB_DIR'}) =~ s%/+$%%;
		my $parentDir = dirname($data->{'WEB_DIR'});

		clearImmutable($parentDir);
		clearImmutable($data->{'WEB_DIR'}, 'recursive');

		iMSCP::Dir->new( dirname => $data->{'WEB_DIR'} )->remove();;

		if($parentDir ne $userWebDir) {
			my $dir = iMSCP::Dir->new( dirname => $parentDir );

			if($dir->isEmpty()) {
				clearImmutable(dirname($parentDir));
				$dir->remove();
			}
		}

		if($data->{'WEB_FOLDER_PROTECTION'} eq 'yes' && $parentDir ne $userWebDir) {
			do {
				setImmutable($parentDir) if -d $parentDir;
			} while (($parentDir = dirname($parentDir)) ne $userWebDir);
		}
	}

	iMSCP::Dir->new( dirname => "$data->{'HOME_DIR'}/logs/$data->{'DOMAIN_NAME'}" )->remove();
	iMSCP::Dir->new( dirname => "$self->{'config'}->{'HTTPD_LOG_DIR'}/$data->{'DOMAIN_NAME'}" )->remove();

	$self->{'restart'} = 1;
	$self->{'eventManager'}->trigger('afterHttpdDelDmn', $data);
}

=item addSub(\%data)

 Process addSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdAddSub', $data);
	$self->setData($data);

	my $rs = $self->_addCfg($data);
	return $rs if $rs;

	$rs = $self->_addFiles($data);
	return $rs if $rs;

	$self->{'restart'} = 1;
	$self->flushData();
	$self->{'eventManager'}->trigger('afterHttpdAddSub', $data);
}

=item restoreSub(\%data)

 Process restoreSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub restoreSub
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdRestoreSub', $data);
	$self->setData($data);

	my $rs = $self->_addFiles($data);
	return $rs if $rs;

	$self->flushData();
	$self->{'eventManager'}->trigger('afterHttpdRestoreSub', $data);
}

=item disableSub(\%data)

 Process disableSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub disableSub
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdDisableSub', $data);

	my $rs = $self->disableDmn($data);
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterHttpdDisableSub', $data);
}

=item deleteSub(\%data)

 Process deleteSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdDelSub', $data);

	my $rs = $self->deleteDmn($data);
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterHttpdDelSub', $data);
}

=item AddHtuser(\%data)

 Process AddHtuser tasks

 Param hash \%data Htuser data
 Return int 0 on success, other on failure

=cut

sub addHtuser
{
	my ($self, $data) = @_;

	my $webDir = $data->{'WEB_DIR'};
	my $fileName = $self->{'config'}->{'HTACCESS_USERS_FILENAME'};
	my $filePath = "$webDir/$fileName";

	clearImmutable($webDir);

	my $file = iMSCP::File->new( filename => $filePath );
	my $fileContent = $file->get() if -f $filePath;

	$self->{'eventManager'}->trigger('beforeHttpdAddHtuser', \$fileContent, $data);
	$fileContent =~ s/^$data->{'HTUSER_NAME'}:[^\n]*\n//gim;
	$fileContent .= "$data->{'HTUSER_NAME'}:$data->{'HTUSER_PASS'}\n";
	$self->{'eventManager'}->trigger('afterHttpdAddHtuser', \$fileContent, $data);

	$file->set($fileContent);
	$file->save();
	$file->mode(0640);
	$file->owner($main::imscpConfig{'ROOT_USER'}, $self->getRunningGroup());

	setImmutable($webDir) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';
	0;
}

=item deleteHtuser(\%data)

 Process deleteHtuser tasks

 Param hash \%data Htuser data
 Return int 0 on success, other on failure

=cut

sub deleteHtuser
{
	my ($self, $data) = @_;

	my $webDir = $data->{'WEB_DIR'};
	my $fileName = $self->{'config'}->{'HTACCESS_USERS_FILENAME'};
	my $filePath = "$webDir/$fileName";

	clearImmutable($webDir);

	my $file = iMSCP::File->new( filename => $filePath );
	my $fileContent = $file->get() if -f $filePath;

	$self->{'eventManager'}->trigger('beforeHttpdDelHtuser', \$fileContent, $data);
	$fileContent =~ s/^$data->{'HTUSER_NAME'}:[^\n]*\n//gim;
	$self->{'eventManager'}->trigger('afterHttpdDelHtuser', \$fileContent, $data);

	$file->set($fileContent);
	$file->save();
	$file->mode(0640);
	$file->owner($main::imscpConfig{'ROOT_USER'}, $self->getRunningGroup());

	setImmutable($webDir) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';
	0;
}

=item addHtgroup(\%data)

 Process addHtgroup tasks

 Param hash \%data Htgroup data
 Return int 0 on success, other on failure

=cut

sub addHtgroup
{
	my ($self, $data) = @_;;

	my $webDir = $data->{'WEB_DIR'};
	my $fileName = $self->{'config'}->{'HTACCESS_GROUPS_FILENAME'};
	my $filePath = "$webDir/$fileName";

	clearImmutable($webDir);

	my $file = iMSCP::File->new( filename => $filePath );
	my $fileContent = $file->get() if -f $filePath;

	$self->{'eventManager'}->trigger('beforeHttpdAddHtgroup', \$fileContent, $data);
	$fileContent =~ s/^$data->{'HTGROUP_NAME'}:[^\n]*\n//gim;
	$fileContent .= "$data->{'HTGROUP_NAME'}:$data->{'HTGROUP_USERS'}\n";
	$self->{'eventManager'}->trigger('afterHttpdAddHtgroup', \$fileContent, $data);

	$file->set($fileContent);
	$file->save();
	$file->mode(0640);
	$file->owner($main::imscpConfig{'ROOT_USER'}, $self->getRunningGroup());

	setImmutable($webDir) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';
	0;
}

=item deleteHtgroup(\%data)

 Process deleteHtgroup tasks

 Param hash \%data Htgroup data
 Return int 0 on success, other on failure

=cut

sub deleteHtgroup
{
	my ($self, $data) = @_;

	my $webDir = $data->{'WEB_DIR'};
	my $fileName = $self->{'config'}->{'HTACCESS_GROUPS_FILENAME'};
	my $filePath = "$webDir/$fileName";

	clearImmutable($webDir);

	my $file = iMSCP::File->new( filename => $filePath );
	my $fileContent = $file->get() if -f $filePath;

	$self->{'eventManager'}->trigger('beforeHttpdDelHtgroup', \$fileContent, $data);
	$fileContent =~ s/^$data->{'HTGROUP_NAME'}:[^\n]*\n//gim;
	$self->{'eventManager'}->trigger('afterHttpdDelHtgroup', \$fileContent, $data);

	$file->set($fileContent);
	$file->save();
	$file->mode(0640);
	$file->owner($main::imscpConfig{'ROOT_USER'}, $self->getRunningGroup());

	setImmutable($webDir) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';
	0;
}

=item addHtaccess(\%data)

 Process addHtaccess tasks

 Param hash \%data Htaccess data
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

		my $file = iMSCP::File->new( filename => $filePath );
		my $fileContent = $file->get() if -f $filePath;

		$self->{'eventManager'}->trigger('beforeHttpdAddHtaccess', \$fileContent, $data);

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

		$self->{'eventManager'}->trigger('afterHttpdAddHtaccess', \$fileContent, $data);

		$file->set($fileContent);
		$file->save();
		$file->mode(0640);
		$file->owner($data->{'USER'}, $data->{'GROUP'});
	} else {
		0;
	}
}

=item deleteHtaccess(\%data)

 Process deleteHtaccess tasks

 Param hash \%data Htaccess data
 Return int 0 on success, other on failure

=cut

sub deleteHtaccess
{
	my ($self, $data) = @_;

	# Here we process only if AUTH_PATH directory exists
	# Note: It's temporary fix for 1.1.0-rc2 (See #749)
	if(-d $data->{'AUTH_PATH'}) {
		my $fileUser = "$data->{'HOME_PATH'}/$self->{'config'}->{'HTACCESS_USERS_FILENAME'}";
		my $fileGroup = "$data->{'HOME_PATH'}/$self->{'config'}->{'HTACCESS_GROUPS_FILENAME'}";
		my $filePath = "$data->{'AUTH_PATH'}/.htaccess";

		my $file = iMSCP::File->new( filename => $filePath );
		my $fileContent = $file->get() if -f $filePath;

		$self->{'eventManager'}->trigger('beforeHttpdDelHtaccess', \$fileContent, $data);

		my $bTag = "### START i-MSCP PROTECTION ###\n";
		my $eTag = "### END i-MSCP PROTECTION ###\n";

		$fileContent = replaceBloc($bTag, $eTag, '', $fileContent);

		$self->{'eventManager'}->trigger('afterHttpdDelHtaccess', \$fileContent, $data);

		if($fileContent ne '') {
			$file->set($fileContent);
			$file->save();
			$file->mode(0640);
			$file->owner($data->{'USER'}, $data->{'GROUP'});
		} else {
			$file->delFile() if -f $filePath;
		}
	}

	0;
}

=item addIps(\%data)

 Process addIps tasks

 Param hash \%data Ips data
 Return int 0 on success, other on failure

=cut

sub addIps
{
	my ($self, $data) = @_;

	my $version = $self->{'config'}->{'HTTPD_VERSION'};

	unless(version->parse($version) >= version->parse('2.4.0')) {
		my $file = iMSCP::File->new( filename => "$self->{'apacheWrkDir'}/00_nameserver.conf" );
		my $fileContent = $file->get();

		$self->{'eventManager'}->trigger('beforeHttpdAddIps', \$fileContent, $data);

		my $ipMngr = iMSCP::Net->getInstance();
		my $confSnippet = "\n";

		for my $ipAddr(@{$data->{'SSL_IPS'}}) {
			if($ipMngr->getAddrVersion($ipAddr) eq 'ipv4') {
				$confSnippet .= "NameVirtualHost $ipAddr:443\n";
			} else {
				$confSnippet .= "NameVirtualHost [$ipAddr]:443\n";
			}
		}

		for my $ipAddr(@{$data->{'IPS'}}) {
			if($ipMngr->getAddrVersion($ipAddr) eq 'ipv4') {
				$confSnippet .= "NameVirtualHost $ipAddr:80\n";
			} else {
				$confSnippet .= "NameVirtualHost [$ipAddr]:80\n";
			}
		}

		$fileContent .= $confSnippet;

		$self->{'eventManager'}->trigger('afterHttpdAddIps', \$fileContent, $data);

		$file->set($fileContent);
		$file->save();

		my $rs = $self->installConfFile('00_nameserver.conf');
		return $rs if $rs;

		$rs = $self->enableSites('00_nameserver.conf');
		return $rs if $rs;

		$self->{'restart'} = 1;
	}

	0;
}

=item buildConf($cfgTpl, $filename [, \%data ])

 Build the given configuration template

 Param string $cfgTpl Template content
 Param string $filename Template filename
 Param hash \%data OPTIONAL Data as provided by Alias|Domain|Subdomain|SubAlias modules or installer
 Return string Template content or undef on failure

=cut

sub buildConf
{
	my ($self, $cfgTpl, $filename, $data) = @_;

	$data ||= { };
	$self->{'eventManager'}->trigger('beforeHttpdBuildConf', \$cfgTpl, $filename, $data);
	$cfgTpl = process($self->{'data'}, $cfgTpl);
	$self->{'eventManager'}->trigger('afterHttpdBuildConf', \$cfgTpl, $filename, $data);
	$cfgTpl;
}

=item buildConfFile($file [, \%data = { } [, \%options = { } ]])

 Build the given configuration file

 Param string $file Absolute path to config file or config filename relative to the i-MSCP apache config directory
 Param hash \%data OPTIONAL Data as provided by Alias|Domain|Subdomain|SubAlias modules or installer
 Param hash \%options OPTIONAL Options such as destination, mode, user and group for final file
 Return int 0 on success, other on failure

=cut

sub buildConfFile
{
	my ($self, $file, $data, $options) = @_;

	$data ||= { };
	$options ||= { };

	my ($filename, $path) = fileparse($file);

	$self->{'eventManager'}->trigger('onLoadTemplate', 'apache_php_fpm', $filename, \my $cfgTpl, $data);

	unless(defined $cfgTpl) {
		$file = "$self->{'apacheCfgDir'}/$file" unless -d $path && $path ne './';
		$cfgTpl = iMSCP::File->new( filename => $file )->get();
	}

	$self->{'eventManager'}->trigger('beforeHttpdBuildConfFile', \$cfgTpl, $filename, $data, $options);
	$cfgTpl = $self->buildConf($cfgTpl, $filename, $data);
	$self->{'eventManager'}->trigger('afterHttpdBuildConfFile', \$cfgTpl, $filename, $data, $options);

	my $fileHandler = iMSCP::File->new(
		filename => ($options->{'destination'}) ? $options->{'destination'} : "$self->{'apacheWrkDir'}/$filename"
	);

	$fileHandler->set($cfgTpl);
	$fileHandler->save();
	$fileHandler->mode($options->{'mode'} ? $options->{'mode'} : 0644);
	$fileHandler->owner(
		$options->{'user'} ? $options->{'user'} : $main::imscpConfig{'ROOT_USER'},
		$options->{'group'} ? $options->{'group'} : $main::imscpConfig{'ROOT_GROUP'}
	);
}

=item installConfFile($file [, \%options = { } ])

 Install the given configuration file

 Param string $file Absolute path to config file or config filename relative to the i-MSCP apache working directory
 Param hash \%options OPTIONAL Options such as destination, mode, user and group for final file
 Return int 0 on success, other on failure

=cut

sub installConfFile
{
	my ($self, $file, $options) = @_;

	$options ||= { };

	my ($filename, $path) = fileparse($file);

	$self->{'eventManager'}->trigger('beforeHttpdInstallConfFile', $filename, $options);

	$file = "$self->{'apacheWrkDir'}/$file" unless -d $path && $path ne './';

	my $fileHandler = iMSCP::File->new( filename => $file );
	$fileHandler->mode($options->{'mode'} ? $options->{'mode'} : 0644);
	$fileHandler->owner(
		$options->{'user'} ? $options->{'user'} : $main::imscpConfig{'ROOT_USER'},
		$options->{'group'} ? $options->{'group'} : $main::imscpConfig{'ROOT_GROUP'}
	);
	$fileHandler->copyFile(
		($options->{'destination'}) ?
			$options->{'destination'} : "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$filename"
	);

	$self->{'eventManager'}->trigger('afterHttpdInstallConfFile', $filename, $options);
}

=item setData(\%data)

 Make the given data available for this server

 Param hash \%data Server data
 Return int 0

=cut

sub setData
{
	my ($self, $data) = @_;

	@{$self->{'data'}}{keys %{$data}} = values %{$data};
	0;
}

=item flushData()

 Flush all data set via the setData() method

 Return int 0

=cut

sub flushData
{
	my $self = shift;

	delete $self->{'data'};
	0;
}

=item getTraffic($timestamp)

 Get httpd traffic data

 Return hash Traffic data or die on failure

=cut

sub getTraffic
{
	my $self = shift;

	my $timestamp = time();
	my $trafficDbPath = "$main::imscpConfig{'VARIABLE_DATA_DIR'}/http_traffic.db";

	# Load traffic database
	tie my %trafficDb, 'iMSCP::Config', fileName => $trafficDbPath, nowarn => 1;

	require Date::Format;
	Date::Format->import();
	my $ldate = time2str('%Y%m%d', $timestamp);

	my $db = iMSCP::Database->factory();
	my $dbh = $db->startTransaction();

	eval {
		# Collect traffic data
		my $sth = $dbh->prepare('SELECT vhost, bytes FROM httpd_vlogger WHERE ldate <= ? FOR UPDATE');
		$sth->execute($ldate);

		while (my $row = $sth->fetchrow_hashref()) {
			$trafficDb{$row->{'vhost'}} += $row->{'bytes'}
		}

		# Delete traffic data source
		$dbh->do('DELETE FROM httpd_vlogger WHERE ldate <= ?', undef, $ldate);
		$dbh->commit();
	};

	if($@) {
		$dbh->rollback();
		%trafficDb = ();
		$db->endTransaction();
		die("Unable to collect traffic data: $@");
	}

	$db->endTransaction();

	# Schedule deletion of full traffic database. This is only done on success. On failure, the traffic database is kept
	# in place for later processing. In such case, data already processed are zeroed by the traffic processor script.
	$self->{'eventManager'}->register(
		'afterVrlTraffic', sub { (-f $trafficDbPath) ? iMSCP::File->new( filename => $trafficDbPath )->delFile() : 0; }
	);

	\%trafficDb;
}

=item getRunningUser()

 Get user name under which the Apache server is running

 Return string User name under which the apache server is running

=cut

sub getRunningUser
{
	my $self = shift;

	$self->{'config'}->{'HTTPD_USER'};
}

=item getRunningGroup()

 Get group name under which the Apache server is running

 Return string Group name under which the apache server is running

=cut

sub getRunningGroup
{
	my $self = shift;

	$self->{'config'}->{'HTTPD_GROUP'};
}

=item enableSites($sites)

 Enable the given sites

 Param string $sites Names of sites to enable, each space separated
 Return int 0 on sucess, other on failure

=cut

sub enableSites
{
	my ($self, $sites) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdEnableSites', \$sites);

	for my $site(split(' ', $sites)){
		if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$site") {
			my $rs = execute("a2ensite $site", \my $stdout, \my $stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;

			$self->{'restart'} = 1;
		} else {
			warning("Site $site doesn't exist");
		}
	}

	$self->{'eventManager'}->trigger('afterHttpdEnableSites', $sites);
}

=item disableSites($sites)

 Disable the given sites

 Param string $sites Names of sites to disable, each space separated
 Return int 0 on sucess, other on failure

=cut

sub disableSites
{
	my ($self, $sites) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdDisableSites', \$sites);

	for my $site(split(' ', $sites)) {
		if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$site") {
			my $rs = execute("a2dissite $site", \my $stdout, \my $stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;

			$self->{'restart'} = 1;
		} else {
			warning("Site $site doesn't exist");
		}
	}

	$self->{'eventManager'}->trigger('afterHttpdDisableSites', $sites);
}

=item enableModules($modules)

 Enable the given Apache modules

 Param string $modules Names of Apache modules to enable, each space separated
 Return int 0 on sucess, other on failure

=cut

sub enableModules
{
	my ($self, $modules) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdEnableModules', \$modules);

	my $rs = execute("a2enmod $modules", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$self->{'restart'} = 1;
	$self->{'eventManager'}->trigger('afterHttpdEnableModules', $modules);
}

=item disableModules($modules)

 Disable the given Apache modules

 Param string $modules Names of Apache modules to disable, each space separated
 Return int 0 on sucess, other on failure

=cut

sub disableModules
{
	my ($self, $modules) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdDisableModules', \$modules);

	my $rs = execute("a2dismod $modules", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$self->{'restart'} = 1;
	$self->{'eventManager'}->trigger('afterHttpdDisableModules', $modules);
}

=item enableConfs($conffiles)

 Enable the given configuration files

 Param string $conffiles Names of configuration files to enable, each space separated
 Return int 0 on sucess, other on failure

=cut

sub enableConfs
{
	my ($self, $conffiles) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdEnableConfs', \$conffiles);

	if(iMSCP::ProgramFinder::find('a2enconf')) {
		if(-d "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available") {
			for my $conffile(split(' ', $conffiles)) {
				if(-f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available/$conffile") {
					my $rs = execute("a2enconf $conffile", \my $stdout, \my $stderr);
					debug($stdout) if $stdout;
					error($stderr) if $stderr && $rs;
					return $rs if $rs;

					$self->{'restart'} = 1;
				} else {
					warning("Configuration file $conffile doesn't exist");
				}
			}
		}
	}

	$self->{'eventManager'}->trigger('afterHttpdEnableConfs', $conffiles);
}

=item disableConfs($conffiles)

 Disable the given configuration files

 Param string $conffiles Names of configuration files to disable, each space separated
 Return int 0 on sucess, other on failure

=cut

sub disableConfs
{
	my ($self, $conffiles) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdDisableConfs', \$conffiles);

	if(iMSCP::ProgramFinder::find('a2disconf')) {
		if(-d "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available") {
			for my $conffile(split(' ', $conffiles)) {
				if(-f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available/$conffile") {
					my $rs = execute("a2disconf $conffile", \my $stdout, \my $stderr);
					debug($stdout) if $stdout;
					error($stderr) if $stderr && $rs;
					return $rs if $rs;

					$self->{'restart'} = 1;
				} else {
					warning("Configuration file $conffile doesn't exist");
				}
			}
		}
	}

	$self->{'eventManager'}->trigger('afterHttpdDisableConfs', $conffiles);
}

=item start()

 Start httpd service

 Return int 0 on success, other on failure

=cut

sub start
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeHttpdStart');

	# In case no pool file is available we must no try to reload the PHP-FPM service because it will fail
	my $isEmptyPoolDir = iMSCP::Dir->new( dirname => $self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'})->isEmpty();
	my $serviceMngr = iMSCP::Service->getInstance();

	unless($isEmptyPoolDir) {
		$serviceMngr->start($self->{'phpfpmConfig'}->{'PHP_FPM_SNAME'});
	} else {
		$serviceMngr->stop($self->{'phpfpmConfig'}->{'PHP_FPM_SNAME'});
	}

	$serviceMngr->start($self->{'config'}->{'HTTPD_SNAME'});

	$self->{'eventManager'}->trigger('afterHttpdStart');
}

=item stop()

 Stop httpd service

 Return int 0 on success, other on failure

=cut

sub stop
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeHttpdStop');

	my $serviceMngr = iMSCP::Service->getInstance();
	$serviceMngr->stop($self->{'phpfpmConfig'}->{'PHP_FPM_SNAME'});
	$serviceMngr->stop($self->{'config'}->{'HTTPD_SNAME'});

	$self->{'eventManager'}->trigger('afterHttpdStop');
}

=item forceRestart()

 Force httpd service to be restarted

 Return int 0

=cut

sub forceRestart
{
	my $self = shift;

	$self->{'forceRestart'} = 1;
	0;
}

=item restart()

 Restart or reload httpd service

 Return int 0 on success, other on failure

=cut

sub restart
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeHttpdRestart');

	# In case no pool file is available we must no try to reload the PHP-FPM service because it will fail
	my $isEmptyPoolDir = iMSCP::Dir->new( dirname => $self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'})->isEmpty();
	my $serviceMngr = iMSCP::Service->getInstance();

	if($self->{'forceRestart'}) {
		unless($isEmptyPoolDir) {
			$serviceMngr->restart($self->{'phpfpmConfig'}->{'PHP_FPM_SNAME'});
		} else {
			$serviceMngr->stop($self->{'phpfpmConfig'}->{'PHP_FPM_SNAME'});
		}

		$serviceMngr->restart($self->{'config'}->{'HTTPD_SNAME'});
	} else {
		unless($isEmptyPoolDir) {
			$serviceMngr->reload($self->{'phpfpmConfig'}->{'PHP_FPM_SNAME'});
		} else {
			$serviceMngr->stop($self->{'phpfpmConfig'}->{'PHP_FPM_SNAME'});
		}

		$serviceMngr->reload($self->{'config'}->{'HTTPD_SNAME'});
	}

	$self->{'eventManager'}->trigger('afterHttpdRestart');
}

=item apacheBkpConfFile($filepath [, $prefix = '' [, $system = 0 ]])

 Backup the given Apache configuration file

 Param string $filepath Configuration file path
 Param string $prefix Prefix to use
 Param bool $system Backup as system file (default false)
 Return 0 on success, other on failure

=cut

sub apacheBkpConfFile
{
	my ($self, $filepath, $prefix, $system) = @_;

	$prefix ||= '';
	$system ||= 0;

	$self->{'eventManager'}->trigger('beforeHttpdBkpConfFile', $filepath, $prefix, $system);

	if(-f $filepath) {
		my $file = iMSCP::File->new( filename => $filepath );
		my $filename = fileparse($filepath);

		if($system && ! -f "$self->{'apacheBkpDir'}/$prefix$filename.system") {
			$file->copyFile("$self->{'apacheBkpDir'}/$prefix$filename.system");
		} else {
			$file->copyFile("$self->{'apacheBkpDir'}/$prefix$filename." . time);
		}
	}

	$self->{'eventManager'}->trigger('afterHttpdBkpConfFile', $filepath, $prefix, $system);
}

=item phpfpmBkpConfFile($filepath [, $prefix = '' [, $system = 0 ]])

 Backup the given PHP FPM configuration file

 Param string $filepath Configuration file path
 Param string $prefix Prefix to use
 Param bool $system Param int $system Backup as system file (default false)
 Return 0 on success, other on failure

=cut

sub phpfpmBkpConfFile
{
	my ($self, $filepath, $prefix, $system) = @_;

	$prefix ||= '';
	$system ||= 0;

	$self->{'eventManager'}->trigger('beforeHttpdBkpConfFile', $filepath, $prefix, $system);

	if(-f $filepath) {
		my $file = iMSCP::File->new( filename => $filepath );
		my $filename = fileparse($filepath);

		if($system && ! -f "$self->{'phpfpmBkpDir'}/$prefix$filename.system") {
			$file->copyFile("$self->{'phpfpmBkpDir'}/$prefix$filename.system");;
		} else {
			$file->copyFile("$self->{'phpfpmBkpDir'}/$prefix$filename." . time);
		}
	}

	$self->{'eventManager'}->trigger('afterHttpdBkpConfFile', $filepath, $prefix, $system);
}

=item mountLogsFolder(\%data)

 Mount logs folder which belong to the given domain into customer's logs folder

 Param hash \%data Domain data
 Return int 0 on success, die on failure

=cut

sub mountLogsFolder
{
	my ($self, $data) = @_;

	my $mountOptions = {
		fs_spec => "$self->{'config'}->{'HTTPD_LOG_DIR'}/$data->{'DOMAIN_NAME'}",
		fs_file => "$data->{'HOME_DIR'}/logs/$data->{'DOMAIN_NAME'}",
		fs_vfstype => 'none',
		fs_mntops => 'bind'
	};

	$self->{'eventManager'}->trigger('beforeMountLogsFolder', $mountOptions);
	mount($mountOptions);
	$self->{'eventManager'}->trigger('afterMountLogsFolder', $mountOptions);
}

=item umountLogsFolder(\%data)

 Umount logs folder which belong to the given domain from customer's logs folder

 Param hash \%data Domain data
 Return int 0 on success, die on failure

=cut

sub umountLogsFolder
{
	my ($self, $data) = @_;

	my $fsFile = "$data->{'HOME_DIR'}/logs/$data->{'DOMAIN_NAME'}";
	$self->{'eventManager'}->trigger('beforeUnmountLogsFolder', $fsFile);
	umount($fsFile);
	$self->{'eventManager'}->trigger('afterUmountMountLogsFolder', $fsFile);

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::httpd::apache_php_fpm

=cut

sub _init
{
	my $self = shift;

	defined $self->{'cfgDir'} or die(sprintf('cfgDir attribute is not defined in %s: %s', ref $self));
	defined $self->{'eventManager'} or die(sprintf('eventManager attribute is not defined in %s', ref $self));

	$self->{'start'} = 0;
	$self->{'restart'} = 0;
	$self->{'apacheCfgDir'} = "$self->{'cfgDir'}/apache";
	$self->{'apacheBkpDir'} = "$self->{'apacheCfgDir'}/backup";
	$self->{'apacheWrkDir'} = "$self->{'apacheCfgDir'}/working";
	$self->{'apacheTplDir'} = "$self->{'apacheCfgDir'}/parts";
	$self->{'config'} = lazy { tie my %c, 'iMSCP::Config', fileName => "$self->{'apacheCfgDir'}/apache.data"; \%c; };
	$self->{'phpfpmCfgDir'} = "$self->{'cfgDir'}/php-fpm";
	$self->{'phpfpmBkpDir'} = "$self->{'phpfpmCfgDir'}/backup";
	$self->{'phpfpmWrkDir'} = "$self->{'phpfpmCfgDir'}/working";
	$self->{'phpfpmTplDir'} = "$self->{'phpfpmCfgDir'}/parts";
	$self->{'phpfpmConfig'} = lazy { tie my %c, 'iMSCP::Config', fileName => "$self->{'phpfpmCfgDir'}/phpfpm.data"; \%c; };
	$self->{'eventManager'}->register('afterHttpdBuildConfFile', sub { $self->_cleanTemplate(@_)});
	$self;
}

=item _addCfg(\%data)

 Add configuration files for the given domain

 Param hash \%data Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _addCfg
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdAddCfg', $data);
	$self->setData($data);

	for my $conffile("$data->{'DOMAIN_NAME'}.conf", "$data->{'DOMAIN_NAME'}_ssl.conf") {
		if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$conffile") {
			my $rs = $self->disableSites($conffile);
			return $rs if $rs;
		}
	}

	for my $conffile(
		"$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}.conf",
		"$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf",
		"$self->{'apacheWrkDir'}/$data->{'DOMAIN_NAME'}.conf",
		"$self->{'apacheWrkDir'}/$data->{'DOMAIN_NAME'}_ssl.conf"
	) {
		if(-f $conffile) {
			iMSCP::File->new( filename => $conffile )->delFile();
		}
	}

	my @templates = ({
		tplFile => ($data->{'FORWARD'} eq 'no' && !$data->{'HSTS_SUPPORT'}) ? 'domain.tpl' : 'domain_redirect.tpl',
		siteFile => "$data->{'DOMAIN_NAME'}.conf"
	});

	if($data->{'SSL_SUPPORT'}) {
		push @templates, {
			tplFile => ($data->{'FORWARD'} eq 'no') ? 'domain_ssl.tpl' : 'domain_redirect_ssl.tpl',
			siteFile => "$data->{'DOMAIN_NAME'}_ssl.conf"
		};

		$self->setData({ CERTIFICATE => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$data->{'DOMAIN_NAME'}.pem" });
	}

	my $poolLevel = $self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_LEVEL'};
	my $poolName;

	if($data->{'FORWARD'} eq 'no') {
		if($poolLevel eq 'per_user') { # One pool configuration file for all domains
			$poolName = $data->{'ROOT_DOMAIN_NAME'};
		} elsif($poolLevel eq 'per_domain') { # One pool configuration file for each domains (including subdomains)
			$poolName = $data->{'PARENT_DOMAIN_NAME'};
		} elsif($poolLevel eq 'per_site') { # One pool conifguration file for each domain
			$poolName = $data->{'DOMAIN_NAME'};
		} else {
			error("Unknown php-fpm pool level: $poolLevel");
			return 1;
		}
	}

	my $version = $self->{'config'}->{'HTTPD_VERSION'};
	my $apache24 = (version->parse($version) >= version->parse('2.4.0'));
	my $ipMngr = iMSCP::Net->getInstance();

	$self->setData({
		BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
		HTTPD_LOG_DIR => $self->{'config'}->{'HTTPD_LOG_DIR'},
		HTTPD_CUSTOM_SITES_DIR => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'},
		AUTHZ_ALLOW_ALL => ($apache24) ? 'Require all granted' : 'Allow from all',
		AUTHZ_DENY_ALL => ($apache24) ? 'Require all denied' : 'Deny from all',
		DOMAIN_IP => ($ipMngr->getAddrVersion($data->{'DOMAIN_IP'}) eq 'ipv4')
			? $data->{'DOMAIN_IP'} : "[$data->{'DOMAIN_IP'}]",
		POOL_NAME => $poolName
	});

	for my $template(@templates) {
		my $rs = $self->buildConfFile("$self->{'apacheTplDir'}/$template->{'tplFile'}", $data, {
			destination => "$self->{'apacheWrkDir'}/$template->{'siteFile'}"
		});

		$rs = $self->installConfFile($template->{'siteFile'});
		return $rs if $rs;
	}

	unless(-f "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$data->{'DOMAIN_NAME'}.conf") {
		my $rs = $self->buildConfFile(
			"$self->{'apacheTplDir'}/custom.conf.tpl",
			$data,
			{ destination => "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$data->{'DOMAIN_NAME'}.conf" }
		);
		return $rs if $rs;
	}

	for my $template(@templates) {
		my $rs = $self->enableSites($template->{'siteFile'});
		return $rs if $rs;
	}

	my $rs = $self->_buildPHPConfig($data);
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterHttpdAddCfg', $data);
}

=item _dmnFolders(\%data)

 Get Web folders list to create for the given domain

 Param hash \%data Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return array List of Web folders to create

=cut

sub _dmnFolders
{
	my ($self, $data) = @_;

	my @folders = ();

	$self->{'eventManager'}->trigger('beforeHttpdDmnFolders', \@folders);

	push(@folders, [
		"$self->{'config'}->{'HTTPD_LOG_DIR'}/$data->{'DOMAIN_NAME'}",
		$main::imscpConfig{'ROOT_USER'},
		$main::imscpConfig{'ADM_GROUP'},
		0755
	]);

	$self->{'eventManager'}->trigger('afterHttpdDmnFolders', \@folders);
	@folders;
}

=item _addFiles(\%data)

 Add default directories and files for the given domain

 Param hash \%data Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on sucess, other on failure

=cut

sub _addFiles
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdAddFiles', $data);

	for my $folderDef($self->_dmnFolders($data)) {
		iMSCP::Dir->new( dirname => $folderDef->[0] )->make({
			user => $folderDef->[1], group => $folderDef->[2], mode => $folderDef->[3]
		});
	}

	if($data->{'FORWARD'} eq 'no') {
		my $webDir = $data->{'WEB_DIR'};
		my $skelDir;

		if($data->{'DOMAIN_TYPE'} eq 'dmn') {
			$skelDir = "$main::imscpConfig{'CONF_DIR'}/skel/domain";
		} elsif($data->{'DOMAIN_TYPE'} eq 'als') {
			$skelDir = "$main::imscpConfig{'CONF_DIR'}/skel/alias";
		} else {
			$skelDir = "$main::imscpConfig{'CONF_DIR'}/skel/subdomain";
		}

		my $tmpDir;

		if(-d $skelDir) {
			$tmpDir = File::Temp->newdir();

			my $rs = execute("cp -RT $skelDir $tmpDir", \my $stdout, \my $stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		} else {
			error("Skeleton directory $skelDir doesn't exist.");
			return 1;
		}

		# Build default page if needed ( if htdocs doesn't exist or is empty )
		if(! -d "$webDir/htdocs" || iMSCP::Dir->new( dirname => "$webDir/htdocs")->isEmpty()) {
			if(-d "$tmpDir/htdocs") {
				# Test needed in case admin removed the index.html file from the skeleton
				if(-f "$tmpDir/htdocs/index.html") {
					my $fileSource = "$tmpDir/htdocs/index.html";
					my $rs = $self->buildConfFile($fileSource, $data, { destination => $fileSource });
					return $rs if $rs;
				}
			} else {
				error("Web folder skeleton $skelDir must provide the 'htdocs' directory.");
				return 1;
			}
		} else {
			iMSCP::Dir->new( dirname => "$tmpDir/htdocs" )->remove();
		}

		if(
			$data->{'DOMAIN_TYPE'} eq 'dmn' && -d "$webDir/errors" &&
			! iMSCP::Dir->new( dirname => "$webDir/errors" )->isEmpty()
		) {
			if(-d "$tmpDir/errors") {
				iMSCP::Dir->new( dirname => "$tmpDir/errors" )->remove();
			} else {
				warning("Web folder skeleton $skelDir should provide the 'errors' directory.");
			}
		}

		my $parentDir = dirname($webDir);

		# Fix #1327 - Ensure that parent Web folder exists
		unless(-d $parentDir) {
			clearImmutable(dirname($parentDir));
			iMSCP::Dir->new( dirname => $parentDir )->make({
				user => $data->{'USER'}, group => $data->{'GROUP'}, mode => 0750
			});
		} else {
			clearImmutable($parentDir);
		}

		if(-d $webDir) {
			clearImmutable($webDir);
		} else {
			iMSCP::Dir->new( dirname => $webDir )->make({
				user => $data->{'USER'}, group => $data->{'GROUP'}, mode => 0750
			});;
		}

		my $rs = execute("cp -nRT $tmpDir $webDir", \my $stdout, \my $stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		setRights($webDir, { user => $data->{'USER'}, group => $data->{'GROUP'}, mode => '0750' });

		my @files = iMSCP::Dir->new( dirname => $skelDir )->getAll();

		for my $file(@files) {
			if(-e "$webDir/$file") {
				setRights("$webDir/$file", { user => $data->{'USER'}, group => $data->{'GROUP'}, recursive => 1 });
			}
		}

		for my $file(@files) {
			if(-d "$webDir/$file") {
				setRights("$webDir/$file", {
					dirmode => '0750',
					filemode => '0640',
					recursive => ($file ~~ [ '00_private', 'cgi-bin', 'htdocs' ]) ? 0 : 1
				});
			}
		}

		for my $file('domain_disable_page', '.htgroup', '.htpasswd') {
			if(-e "$webDir/$file") {
				setRights("$webDir/$file", {
					user => $main::imscpConfig{'ROOT_USER'}, group => $self->getRunningGroup(), recursive => 1
				});
			}
		}

		if($data->{'DOMAIN_TYPE'} eq 'dmn' && -d "$webDir/logs") {
			setRights("$webDir/logs", {
				user => $main::imscpConfig{'ROOT_USER'},
				group => $main::imscpConfig{'ADM_GROUP'},
				dirmode => '0755',
				filemode => '0644',
				recursive => 1
			});
		}

		if($data->{'DOMAIN_TYPE'} ne 'dmn' && ! $data->{'SHARED_MOUNT_POINT'}) {
			iMSCP::Dir->new( dirname => "$webDir/phptmp" )->remove();
		}

		if($data->{'WEB_FOLDER_PROTECTION'} eq 'yes') {
			(my $userWebDir = $main::imscpConfig{'USER_WEB_DIR'}) =~ s%/+$%%;
			do {
				setImmutable($webDir);
			} while (($webDir = dirname($webDir)) ne $userWebDir);
		}
	}

	my $rs = $self->mountLogsFolder($data);
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterHttpdAddFiles', $data);
}

=item _buildPHPConfig(\%data)

 Build PHP related configuration files

 Param hash \%data Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on sucess, other on failure

=cut

sub _buildPHPConfig
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdBuildPhpConf', $data);

	my $poolLevel = $self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_LEVEL'};
	my $domainType = $data->{'DOMAIN_TYPE'};

	my ($poolName, $emailDomain);
	if($poolLevel eq 'per_user') { # One pool configuration file for all domains
		$poolName = $data->{'ROOT_DOMAIN_NAME'};
		$emailDomain = $data->{'ROOT_DOMAIN_NAME'};
	} elsif ($poolLevel eq 'per_domain') { # One pool configuration file for each domains (including subdomains)
		$poolName = $data->{'PARENT_DOMAIN_NAME'};
		$emailDomain = $data->{'DOMAIN_NAME'};
	} elsif($poolLevel eq 'per_site') { # One pool conifguration file for each domain
		$poolName = $data->{'DOMAIN_NAME'};
		$emailDomain = $data->{'DOMAIN_NAME'};
	} else {
		error("Unknown PHP-FPM pool level: $poolLevel");
		return 1;
	}

	if($data->{'FORWARD'} eq 'no' && $data->{'PHP_SUPPORT'} eq 'yes') {
		$self->setData({
			POOL_NAME => $poolName,
			TMPDIR => $data->{'HOME_DIR'} . '/phptmp',
			EMAIL_DOMAIN => $emailDomain
		});

		my $rs = $self->buildConfFile("$self->{'phpfpmTplDir'}/pool.conf", $data, {
			destination => "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/$poolName.conf",
			user => $main::imscpConfig{'ROOT_USER'},
			group => $main::imscpConfig{'ROOT_GROUP'},
			mode => 0644
		});
		return $rs if $rs;
	} elsif(
		$data->{'PHP_SUPPORT'} ne 'yes' || (
			($poolLevel eq 'per_user' && $domainType ne 'dmn') ||
			($poolLevel eq 'per_domain' && not $domainType ~~ [ 'dmn', 'als' ]) ||
			$poolLevel eq 'per_site'
		)
	) {
		if(-f "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/$data->{'DOMAIN_NAME'}.conf") {
			iMSCP::File->new(
				filename => "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/$data->{'DOMAIN_NAME'}.conf"
			)->delFile();
		}
	}

	$self->{'eventManager'}->trigger('afterHttpdBuildPhpConf', $data);
}

=item _cleanTemplate(\$cfgTpl, $filename, \%data)

 Event listener which is responsible to remove useless configuration snippets in vhost template files

 Param string \$cfgTpl Template content
 Param string $filename Template filename
 Param hash \%data Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0

=cut

sub _cleanTemplate
{
	my ($self, $cfgTpl, $filename, $data) = @_;

	if($filename =~ /^domain(?:_ssl)?\.tpl$/) {
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

		if((version->parse("$self->{'config'}->{'HTTPD_VERSION'}") < version->parse('2.4.10'))) {
			$$cfgTpl = replaceBloc("# SECTION mod_proxy_fcgi BEGIN.\n", "# SECTION mod_proxy_fcgi END.\n", '', $$cfgTpl);
		} else {
			$$cfgTpl = replaceBloc("# SECTION mod_fastcgi BEGIN.\n", "# SECTION mod_fastcgi END.\n", '', $$cfgTpl);
		}
	}

	if($data->{'HSTS_SUPPORT'}) {
		$$cfgTpl = replaceBloc("# SECTION hsts_disabled BEGIN.\n", "# SECTION hsts_disabled END.\n", '', $$cfgTpl);
	} else {
		$$cfgTpl = replaceBloc("# SECTION hsts_enabled BEGIN.\n", "# SECTION hsts_enabled END.\n", '', $$cfgTpl);
	}

	$$cfgTpl =~ s/^[ \t]+#.*?(?:BEGIN|END)\.\n//gmi;
	$$cfgTpl =~ s/\n{3}/\n\n/g;

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
