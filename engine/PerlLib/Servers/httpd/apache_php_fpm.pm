#!/usr/bin/perl

=head1 NAME

 Servers::httpd::apache_php_fpm - i-MSCP Apache PHP-FPM Server

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache_php_fpm;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Templator;
use iMSCP::Dir;
use iMSCP::Rights;

use File::Basename;
use POSIX;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Apache PHP FPM Server.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks($hooksManager)

 Register setup hooks.

 Param iMSCP::HooksManager $hooksManager Hooks manager instance
 Return int 0 on success, other on failure

=cut

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	require Servers::httpd::apache_php_fpm::installer;
	Servers::httpd::apache_php_fpm::installer->new()->registerSetupHooks($hooksManager);
}

=item preinstall()

 Process preinstall tasks.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdPreInstall', 'apache_php_fpm') and return 1;

	$self->stopPhpFpm() and return 1;
	$self->stopApache() and return 1;

	$self->{'hooksManager'}->trigger('afterHttpdPreInstall', 'apache_php_fpm');
}

=item install()

 Process install tasks.

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	require Servers::httpd::apache_php_fpm::installer;
	Servers::httpd::apache_php_fpm::installer->new()->install();
}

=item postinstall()

 Process postinstall tasks.

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdPostInstall', 'apache_php_fpm') and return 1;

	$self->{'startPhpFpm'} = 'yes';
	$self->{'startApache'} = 'yes';

	$self->{'hooksManager'}->trigger('afterHttpdPostInstall', 'apache_php_fpm');
}

=item uninstall()

 Process uninstall tasks.

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdUninstall', 'apache_php_fpm') and return 1;

	$self->stopPhpFpm() and return 1;
	$self->stopApache() and return 1;

	require Servers::httpd::apache_php_fpm::uninstaller;
	Servers::httpd::apache_php_fpm::uninstaller->new()->uninstall() and return 1;

	$self->startPhpFpm() and return 1;
	$self->startApache() and return 1;

	$self->{'hooksManager'}->trigger('afterHttpdUninstall', 'apache_php_fpm') and return 1;
}

=item addUser(\%data)

 Add mod_cband directive snippet and common web files for the given user.

 Param hash_ref $data Reference to a hash containing data as provided by the Modules::User package.
 Return int 0 on success, other on failure

=cut

sub addUser
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdAddUser') and return 1;

	my $homeDir = $data->{'HOME_DIR'};
	my $rootUser = $main::imscpConfig{'ROOT_USER'};
	my $rootGroup = $main::imscpConfig{'ROOT_GROUP'};
	my $apacheGroup = $self::apacheConfig{'APACHE_GROUP'};
	my ($rs, $stdout, $stderr);

	$self->{'data'} = $data;

	# mod_cband - begin

	$self->apacheBkpConfFile("$self->{'apacheWrkDir'}/00_modcband.conf") and return 1;

	my $filename = (
		-f "$self->{'apacheWrkDir'}/00_modcband.conf"
			? "$self->{'apacheWrkDir'}/00_modcband.conf" : "$self->{'apacheCfgDir'}/00_modcband.conf"
	);

	my $file = iMSCP::File->new(filename => $filename);
	my $content	= $file->get();

	unless($content) {
		error("Cannot read $filename");
		$rs = 1;
	} else {
		my $bTag = "## SECTION {USER} BEGIN.\n";
		my $eTag = "## SECTION {USER} END.\n";
		my $bUTag = "## SECTION $data->{'USER'} BEGIN.\n";
		my $eUTag = "## SECTION $data->{'USER'} END.\n";

		my $entry = getBloc($bTag, $eTag, $content);
		chomp($entry);
		$entry =~ s/#//g;

		$content = replaceBloc($bUTag, $eUTag, '', $content, undef);
		chomp($content);

		$self->{'data'}->{'BWLIMIT_DISABLED'} = ($data->{'BWLIMIT'} ? '' : '#');

		$entry = $self->buildConf($bTag . $entry . $eTag);
		$content = replaceBloc($bTag, $eTag, $entry, $content, 'yes');

		$file = iMSCP::File->new(filename => "$self->{'apacheWrkDir'}/00_modcband.conf");
		$file->set($content);
		$rs = 1 if $file->save();

		$rs |= $self->installConfFile('00_modcband.conf');
		$rs |= $self->enableSite('00_modcband.conf');

		unless(-f "$self::apacheConfig{'SCOREBOARDS_DIR'}/$data->{'USER'}"){
			$rs	|=	iMSCP::File->new(
				filename => "$self::apacheConfig{'SCOREBOARDS_DIR'}/$data->{'USER'}"
			)->save();
		}
	}

	# mod_cband - End

	# Common web files - Begin

	# error docs
	$rs |= execute(
		"$main::imscpConfig{'CMD_CP'} -vnRT $main::imscpConfig{'GUI_ROOT_DIR'}/public/errordocs $homeDir/errors",
		\$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr;

	$rs |= setRights(
		"$homeDir/errors",
		{
			'user' => $data->{'USER'},
			'group' => $apacheGroup,
			'filemode' => '0640',
			'dirmode' => '0750',
			'recursive' => 'yes'
		}
	);

	for(
		"$homeDir/$self::apacheConfig{'HTACCESS_USERS_FILE_NAME'}",
		"$homeDir/$self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'}"
	) {
		my $fileH =	iMSCP::File->new('filename' => $_);
		$rs |= $fileH->save() unless -f $_;
		$rs |= $fileH->mode(0640);
	}

	# Common web files - End

	$self->{'restartApache'} = 'yes';

	$rs |= $self->{'hooksManager'}->trigger('beforeHttpdAddUser');

	$rs;
}

=item delUser(\%data)

 Delete mod_cband directive snippet for the given user.

 Param hash_ref $data Reference to a hash containing data as provided by the module User
 Return int 0 on success, other on failure

=cut

sub delUser
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdDelUser') and return 1;

	my $homeDir = $data->{'HOME_DIR'};
	my $rs = 0;

	$self->{'data'} = $data;

	# mod_cband - Begin

	$self->apacheBkpConfFile("$self->{'apacheWrkDir'}/00_modcband.conf") and return 1;

	my $filename = (
		-f "$self->{'apacheWrkDir'}/00_modcband.conf"
			? "$self->{'apacheWrkDir'}/00_modcband.conf" : "$self->{'apacheCfgDir'}/00_modcband.conf"
	);

	my $file = iMSCP::File->new('filename' => $filename);
	my $content	= $file->get();

	unless($content) {
		error("Cannot read $filename");
		$rs = 1;
	} else {
		my $bUTag = "## SECTION $data->{'USER'} BEGIN.\n";
		my $eUTag = "## SECTION $data->{'USER'} END.\n";

		$content = replaceBloc($bUTag, $eUTag, '', $content, undef);

		$file = iMSCP::File->new(filename => "$self->{'apacheWrkDir'}/00_modcband.conf");
		$file->set($content);

		$rs |= $file->save();
		$rs |= $self->installConfFile('00_modcband.conf');
		$rs |= $self->enableSite('00_modcband.conf');

		if( -f "$self::apacheConfig{'SCOREBOARDS_DIR'}/$data->{'USER'}"){
			$rs |= iMSCP::File->new('filename' => "$self::apacheConfig{'SCOREBOARDS_DIR'}/$data->{'USER'}")->delFile();
		}
	}
	# mod_cband - End

	$self->{'restartApache'} = 'yes';

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDelUser');

	$rs;
}

=item addDmn(\%data)

 Add the given domain.

 Param hash_ref $data Reference to a hash containing data as provided by the module Domain|Alias|Subdomain|SubAlias
 Return int 0 on success, other on failure

=cut

sub addDmn
{
	my $self = shift;
	my $data = shift;

	$self->{'mode'} = 'dmn';

	$self->{'hooksManager'}->trigger('beforeHttpdAddDmn') and return 1;

	$self->{'data'} = $data;

	my $rs = $self->_addCfg($data);
	$rs |= $self->_addFiles($data) if $data->{'FORWARD'} eq 'no';

	$self->{'restartPhpFpm'} = 'yes';
	$self->{'restartApache'} = 'yes';

	delete $self->{'data'};

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdAddDmn');

	$rs;
}

=item delDmn(\%data)

 Delete the given domain.

 Param hash_ref $data Reference to a hash containing data as provided by the module Domain|Alias|Subdomain|SubAlias
 Return int 0 on success, other on failure

=cut

sub delDmn
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdDelDmn') and return 1;

	my $rs = 0;

	for("$data->{'DMN_NAME'}.conf", "$data->{'DMN_NAME'}_ssl.conf") {
		$rs |= $self->disableSite($_) if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_";
		$self->apacheBkpConfFile("$self->{'apacheWrkDir'}/$_") and return 1;
	}

	for(
		"$self::apacheConfig{'APACHE_SITES_DIR'}/$data->{'DMN_NAME'}.conf",
		"$self::apacheConfig{'APACHE_SITES_DIR'}/$data->{'DMN_NAME'}_ssl.conf",
		"$self::apacheConfig{'APACHE_CUSTOM_SITES_CONFIG_DIR'}/$data->{'DMN_NAME'}.conf",
		"$self->{apacheWrkDir}/$data->{'DMN_NAME'}.conf",
		"$self->{apacheWrkDir}/$data->{'DMN_NAME'}_ssl.conf",
	) {
		$rs |= iMSCP::File->new('filename' => $_)->delFile() if -f $_;
	}

	# Remove home dir
	$rs |= iMSCP::Dir->new('dirname' => $data->{'HOME_DIR'})->remove() if -d $data->{'HOME_DIR'};

	# Remove pool file
	$rs |= iMSCP::File->new(
		'filename' => "$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}/$data->{'DMN_NAME'}"
	)->delFile() if -f "$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}/$data->{'DMN_NAME'}";

	$self->{'restartPhpFpm'} = 'yes';
	$self->{'restartApache'} = 'yes';

	delete $self->{'data'};

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDelDmn');

	$rs;
}

=item disableDmn(\%data)

 Disable the given domain.

 Param hash_ref $data Reference to a hash containing data as provided by the module Domain|Alias|Subdomain|SubAlias
 Return int 0 on success, other on failure

=cut

sub disableDmn
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$self->{'hooksManager'}->trigger('beforeHttpdDisableDmn') and return 1;

	$self->{'data'} = $data;

	$self->apacheBkpConfFile("$self->{'apacheWrkDir'}/$data->{'DMN_NAME'}.conf") and return 1;

	$rs |= $self->buildConfFile(
		"$self->{'apacheTplDir'}/domain_disabled.tpl",
		{ 'destination' => "$self->{'apacheWrkDir'}/$data->{'DMN_NAME'}.conf" }
	);

	$rs |= $self->installConfFile("$data->{'DMN_NAME'}.conf");

	$rs |= $self->enableSite("$data->{'DMN_NAME'}.conf");
	return $rs if $rs;

	$self->{'restartPhpFpm'} = 'yes';
	$self->{'restartApache'} = 'yes';

	delete $self->{'data'};

	$self->{'hooksManager'}->trigger('afterHttpdDisableDmn');

	0;
}

=item addSub(\%data)

 Add the given subdomains.

 Param hash_ref $data Reference to a hash containing data as provided by the module Subdomain|SubAlias
 Return int 0 on success, other on failure

=cut

sub addSub
{
	my $self = shift;
	my $data = shift;

	$self->{'mode'}	= 'sub';

	$self->{'hooksManager'}->trigger('beforeHttpdAddSub') and return 1;

	$self->{'data'} = $data;

	my $rs = $self->_addCfg($data);
	$rs |= $self->_addFiles($data) if $data->{'FORWARD'} eq 'no';

	$self->{'restartPhpFpm'} = 'yes';
	$self->{'restartApache'} = 'yes';

	delete $self->{'data'};

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdAddSub');

	$rs;
}

=item delSub(\%data)

 Delete the given subdomain.

 Param hash_ref $data Reference to a hash containing data as provided by the module Subdomain|SubAlias
 Return int 0 on success, other on failure

=cut

sub delSub
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdDelSub') and return 1;

	my $rs = $self->delDmn($data);

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDelSub');

	$rs;
}

=item disableSub(\%data)

 Disable the given subdomain.

 Param hash_ref $data Reference to a hash containing data as provided by the module Subdomain|SubAlias
 Return int 0 on success, other on failure

=cut

sub disableSub
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdDisableSub') and return 1;

	my $rs = $self->disableDmn($data);

	$rs |= $self->{'hooksManager'}->trigger('beforeHttpdDisableSub');

	$rs;
}

=item AddHtuser(\%data)

 Add the given Htuser.

 Param hash_ref $data Reference to a hash containing data as provided by the module Htuser
 Return int 0 on success, other on failure

=cut

sub addHtuser
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdAddHtuser') and return 1;

	my $rs = 0;
	my $fileName = $self::apacheConfig{'HTACCESS_USERS_FILE_NAME'};
	my $filePath = "$main::imscpConfig{'USER_HOME_DIR'}/$data->{'HTUSER_DMN'}/$fileName";
	my $fileH = iMSCP::File->new('filename' => $filePath);

	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if ! $fileContent;
	$fileContent =~ s/^$data->{'HTUSER_NAME'}:[^\n]*\n//img;
	$fileContent .= "$data->{'HTUSER_NAME'}:$data->{'HTUSER_PASS'}\n";

	$rs |= $fileH->set($fileContent);
	$rs |= $fileH->save();
	$rs |= $fileH->mode(0644);
	$rs |= $fileH->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdAddHtuser');

	$rs;
}

=item delHtuser(\%data)

 Delete the given Htuser.

 Param hash_ref $data Reference to a hash containing data as provided by the module Htuser
 Return int 0 on success, other on failure

=cut

sub delHtuser
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdDelHtuser') and return 1;

	my $rs = 0;
	my $fileName = $self::apacheConfig{'HTACCESS_USERS_FILE_NAME'};
	my $filePath = "$main::imscpConfig{'USER_HOME_DIR'}/$data->{'HTUSER_DMN'}/$fileName";
	my $fileH = iMSCP::File->new('filename' => $filePath);

	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if !$fileContent;
	$fileContent =~ s/^$data->{'HTUSER_NAME'}:[^\n]*\n//img;

	$rs |= $fileH->set($fileContent);
	$rs |= $fileH->save();
	$rs |= $fileH->mode(0644);
	$rs |= $fileH->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDelHtuser');

	$rs;
}

=item addHtgroup(\%data)

 Add the given Htgroup.

 Param hash_ref $data Reference to a hash containing data as provided by the module Htgroup
 Return int 0 on success, other on failure

=cut

sub addHtgroup
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdAddHtgroup') and return 1;

	my $rs = 0;
	my $fileName = $self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'};
	my $filePath = "$main::imscpConfig{'USER_HOME_DIR'}/$data->{'HTGROUP_DMN'}/$fileName";
	my $fileH = iMSCP::File->new(filename => $filePath);

	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if ! $fileContent;
	$fileContent =~ s/^$data->{'HTGROUP_NAME'}:[^\n]*\n//img;
	$fileContent .= "$data->{'HTGROUP_NAME'}:$data->{'HTGROUP_USERS'}\n";

	$rs |= $fileH->set($fileContent);
	$rs |= $fileH->save();
	$rs |= $fileH->mode(0644);
	$rs |= $fileH->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdAddHtgroup');

	$rs;
}

=item delHtgroup(\%data)

 Delete the given Htgroup.

 Param hash_ref $data Reference to a hash containing data as provided by the module Htgroup
 Return int 0 on success, other on failure

=cut

sub delHtgroup
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdDelHtgroup') and return 1;

	my $rs = 0;
	my $fileName = $self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'};
	my $filePath = "$main::imscpConfig{'USER_HOME_DIR'}/$data->{'HTGROUP_DMN'}/$fileName";
	my $fileH = iMSCP::File->new('filename' => $filePath);

	my $fileContent	= $fileH->get() if -f $filePath;
	$fileContent = '' if !$fileContent;
	$fileContent =~ s/^$data->{'HTGROUP_NAME'}:[^\n]*\n//img;

	$rs |= $fileH->set($fileContent);
	$rs |= $fileH->save();
	$rs |= $fileH->mode(0644);
	$rs |= $fileH->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDelHtgroup');

	$rs;
}

=item addHtaccess(\%data)

 Add the given Htaccess.

 Param hash_ref $data Reference to a hash containing data as provided by the module Htaccess
 Return int 0 on success, other on failure

=cut

sub addHtaccess
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdAddHtaccess') and return 1;

	my $rs = 0;
	my $fileUser = "$data->{'HOME_PATH'}/$self::apacheConfig{'HTACCESS_USERS_FILE_NAME'}";
	my $fileGroup = "$data->{'HOME_PATH'}/$self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'}";
	my $filePath = "$data->{'AUTH_PATH'}/.htaccess";
	my $fileH = iMSCP::File->new(filename => $filePath);

	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if !$fileContent;

	my $bTag = "\t\t### START i-MSCP PROTECTION ###\n";
	my $eTag = "\t\t### END i-MSCP PROTECTION ###\n";
	my $tag	 = "\t\tAuthType $data->{'AUTH_TYPE'}\n\t\tAuthName \"$data->{'AUTH_NAME'}\"\n\t\tAuthUserFile $fileUser\n";

	if($data->{'HTUSERS'} eq '') {
		$tag .=	"\t\tAuthGroupFile $fileGroup\n\t\tRequire group $data->{HTGROUPS}\n";
	} else {
		$tag .=	"\t\tRequire user $data->{HTUSERS}\n";
	}

	$fileContent = replaceBloc($bTag, $eTag, '', $fileContent, undef);
	$fileContent = $bTag . $tag . $eTag . $fileContent;

	$rs |= $fileH->set($fileContent);
	$rs |= $fileH->save();
	$rs |= $fileH->mode(0644);
	$rs |= $fileH->owner($data->{'USER'}, $data->{'GROUP'});

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdAddHtaccess');

	$rs;
}

=item delHtaccess(\%data)

 Delete the given Htaccess.

 Param hash_ref $data Reference to a hash containing data as provided by the module Htaccess
 Return int 0 on success, other on failure

=cut

sub delHtaccess
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdDelHtaccess') and return 1;

	my $rs = 0;
	my $fileUser = "$data->{'HOME_PATH'}/$self::apacheConfig{'HTACCESS_USERS_FILE_NAME'}";
	my $fileGroup = "$data->{'HOME_PATH'}/$self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'}";
	my $filePath = "$data->{'AUTH_PATH'}/.htaccess";
	my $fileH = iMSCP::File->new('filename' => $filePath);

	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if ! $fileContent;

	my $bTag = "\t\t### START i-MSCP PROTECTION ###\n";
	my $eTag = "\t\t### END i-MSCP PROTECTION ###\n";

	$fileContent = replaceBloc($bTag, $eTag, '', $fileContent, undef);

	if($fileContent ne '') {
		$rs |= $fileH->set($fileContent);
		$rs |= $fileH->save();
		$rs |= $fileH->mode(0644);
		$rs |= $fileH->owner($data->{'USER'}, $data->{'GROUP'});
	} else {
		$rs |= $fileH->delFile() if -f $filePath;
	}

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDelHtaccess');

	$rs;
}

=item addIps(\%data)

 Add Apache NameVirtualHost for the given adresses IP.

 Param hash_ref $data Reference to a hash containing data as provided by the module Ips
 Return int 0 on success, other on failure

=cut

sub addIps
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdAddIps') and return 1;

	my $rs = 0;

	$self->apacheBkpConfFile("$self->{'apacheWrkDir'}/00_nameserver.conf") and return 1;

	my $filename = (
		-f "$self->{'apacheWrkDir'}/00_nameserver.conf" ?
			"$self->{'apacheWrkDir'}/00_nameserver.conf" : "$self->{'apacheCfgDir'}/00_nameserver.conf"
	);

	my $file = iMSCP::File->new('filename' => $filename);
	my $content = $file->get();
	$content =~ s/NameVirtualHost[^\n]+\n//gi;

	$content .= "NameVirtualHost $_:443\n" for @{$data->{'SSLIPS'}};
	$content .= "NameVirtualHost $_:80\n" for @{$data->{'IPS'}};

	$file = iMSCP::File->new(filename => "$self->{'apacheWrkDir'}/00_nameserver.conf");
	$file->set($content);
	$file->save() and return 1;

	$rs = $self->installConfFile('00_nameserver.conf');
	return $rs if $rs;

	$rs |= $self->enableSite('00_nameserver.conf');

	$self->{'restartApache'} = 'yes';

	delete $self->{'data'};

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdAddIps');

	$rs;
}

=item setGuiPermissions()

 Set Panel (GUI) directories and files permissions.

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = shift;

	require Servers::httpd::apache_php_fpm::installer;
	Servers::httpd::apache_php_fpm::installer->new()->setGuiPermissions();
}

=item buildConf($cfgTpl, $filename)

 Build the given configuration template.

 Param string $cfgTpl String containing content of the configuration template
 Param string $filename Configuration template name
 Return string String representing builded configuration template or undef

=cut

sub buildConf($ $ $)
{
	my $self = shift;
	my $cfgTpl = shift;
	my $filename = shift || '';

	error('Empty config template...') unless $cfgTpl;
	return undef unless $cfgTpl;

	$self->{'tplValues'}->{$_} = $self->{'data'}->{$_} for keys %{$self->{'data'}};

	$self->{'hooksManager'}->trigger('beforeHttpdBuildConf', \$cfgTpl, $filename);

	$cfgTpl = process($self->{'tplValues'}, $cfgTpl);
	return undef if ! $cfgTpl;

	$self->{'hooksManager'}->trigger('afterHttpdBuildConf', \$cfgTpl, $filename);

	$cfgTpl;
}


=item buildConfFile($file, \%options)

 Build the given configuration file.

 Param string $file Absolute path to config file or config filename relative to the $self->{'apacheCfgDir'} directory
 Param hash_ref $options Reference to a hash containing options such as destination, mode, user and group for final file
 Return int 0 on success, other on failure

=cut

sub buildConfFile
{
	my $self = shift;
	my $file = shift;
	my $options = shift;

	$options = {} if ref $options ne 'HASH';

	my ($filename, $directories, $suffix) = fileparse($file);

	$file = "$self->{'apacheCfgDir'}/$file" unless -d $directories && $directories ne './';

	my $fileH = iMSCP::File->new(filename => $file);
	my $cfgTpl = $fileH->get();
	error("Empty configuration template $file...") unless $cfgTpl;
	return 1 unless $cfgTpl;

	$self->{'hooksManager'}->trigger('beforeHttpdBuildConfFile', \$cfgTpl, "$filename$suffix") and return 1;

	$cfgTpl = $self->buildConf($cfgTpl, "$filename$suffix");
	return 1 if (! $cfgTpl);

	$cfgTpl =~ s/\n{2,}/\n\n/g; # Remove duplicate blank lines

	$self->{'hooksManager'}->trigger('afterHttpdBuildConfFile', \$cfgTpl, "$filename$suffix") and return 1;

	$fileH = iMSCP::File->new(
		filename => ($options->{'destination'} ? $options->{'destination'} : "$self->{'apacheWrkDir'}/$filename$suffix")
	);
	$fileH->set($cfgTpl) and return 1;
	$fileH->save() and return 1;
	$fileH->mode($options->{'mode'} ? $options->{'mode'} : 0644) and return 1;
	$fileH->owner(
		$options->{'user'} ? $options->{'user'} : $main::imscpConfig{'ROOT_USER'},
		$options->{'group'} ? $options->{'group'} : $main::imscpConfig{'ROOT_GROUP'}
	) and return 1;

	0;
}

=item installConfFile($file, \%options)

 Install the given configuration file.

 Param string $file Absolute path to config file or config filename relative to the $self->{'apacheWrkDir'} directory
 Param hash_ref $options Reference to a hash containing options such as destination, mode, user and group for final file
 Return int 0 on success, other on failure

=cut

sub installConfFile
{
	my $self = shift;
	my $file = shift;
	my $options = shift;

	$options = {} if ref $options ne 'HASH';

	my ($filename, $directories, $suffix) = fileparse($file);

	$self->{'hooksManager'}->trigger('beforeHttpdInstallConfFile', "$filename$suffix") and return 1;

	$file = "$self->{'apacheWrkDir'}/$file" unless -d $directories && $directories ne './';

	my $fileH = iMSCP::File->new(filename => $file);

	$fileH->mode($options->{'mode'} ? $options->{'mode'} : 0644) and return 1;
	$fileH->owner(
		$options->{'user'} ? $options->{'user'} : $main::imscpConfig{'ROOT_USER'},
		$options->{'group'} ? $options->{'group'} : $main::imscpConfig{'ROOT_GROUP'}
	) and return 1;

	$fileH->copyFile(
		$options->{'destination'} ? $options->{'destination'} : "$self::apacheConfig{'APACHE_SITES_DIR'}/$filename$suffix"
	) and return 1;

	$self->{'hooksManager'}->trigger('afterHttpdInstallConfFile', "$filename$suffix");
}

=item setData(\%data)

 Make the given data available for this server.

 Param hash_ref $data Reference to a hash containing data to make available for this server.
 Return int 0 on success, other on failure

=cut

sub setData
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdSetData', $data) and return 1;

	$data = {} if ref $data ne 'HASH';
	$self->{'data'} = $data;

	$self->{'hooksManager'}->trigger('afterHttpdSetData');
}

=item removeSection($sectionName, \$cfgTpl)

 Remove the given section in the given configuration template string.

 Param string $sectionName Name of section to remove
 Param string_ref $cfgTpl Reference to configuration template string
 Return int 0

=cut

sub removeSection
{
	my $self = shift;
	my $sectionName = shift;
	my $cfgTpl = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdRemoveSection', $sectionName, $cfgTpl);

	my $bTag = "# SECTION $sectionName BEGIN.\n";
	my $eTag = "# SECTION $sectionName END.\n";

	debug("Removing useless section: $sectionName");

	$$cfgTpl = replaceBloc($bTag, $eTag, '', $$cfgTpl, undef);

	$self->{'hooksManager'}->trigger('afterHttpdRemoveSection', $sectionName, $cfgTpl);

	0;
}

=item

 Return traffic consumption for the given domain.

 Return int Traffic in bytes

=cut

sub getTraffic
{
	my $self = shift;
	my $who = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdGetTraffic');

	my $traff = 0;
	my $trfDir = "$self::apacheConfig{'APACHE_LOG_DIR'}/traff";
	my ($rv, $rs, $stdout, $stderr);

	unless($self->{'logDb'}) {
		$self->{'logDb'} = 1;

		$rs = execute("$main::imscpConfig{'CMD_PS'} -o pid,args -C 'imscp-apache-logger'", \$stdout, \$stderr);
		error($stderr) if $stderr && $rs;

		my $rv = iMSCP::Dir->new('dirname' => $trfDir)->moveDir("$trfDir.old") if -d $trfDir;

		if($rv) {
			delete $self->{'logDb'};
			return 0;
		}

		if($rs || ! $stdout){
			error('imscp-apache-logger is not running') unless $stderr;
		} else {
			while($stdout =~ m/^\s{0,}(\d+)(?!.*error)/mg){
				$rs = execute("kill -s HUP $1", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr;
			}
		}
	}

	if(-d "$trfDir.old" && -f "$trfDir.old/$who-traf.log"){
		my $content = iMSCP::File->new('filename' => "$trfDir.old/$who-traf.log")->get();

		if($content){
			my @lines = split("\n", $content);
			$traff += $_ for @lines;
		} else {
			error("Cannot read $trfDir.old/$who-traf.log");
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdGetTraffic');

	$traff;
}

=item

 Remove Apache logs (logs older than 1 year).

 Return int 0 on success, other on failure

=cut

sub delOldLogs
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdDelOldLogs') and return 1;

	my $rs = 0;
	my $logDir = $self::apacheConfig{'APACHE_LOG_DIR'};
	my $bLogDir = $self::apacheConfig{'APACHE_BACKUP_LOG_DIR'};
	my $uLogDir = $self::apacheConfig{'APACHE_USERS_LOG_DIR'};
	my ($stdout, $stderr);

	for ($logDir, $bLogDir, $uLogDir){
		my $cmd = "nice -n 19 find $_ -maxdepth 1 -type f -name '*.log*' -mtime +365 -exec rm -v {} \\;";
		$rs |= execute($cmd, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr;
		error("Error while executing $cmd.\nReturned value is $rs") if ! $stderr && $rs;
	}

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDelOldLogs');

	$rs;
}

=item

 Delete temporary files (PHP session files).

 Return int 0 on success, other on failure

=cut

sub delTmp
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdDelTmp') and return 1;

	my $rs = 0;

	# Get session.gc_maxlifetime value from global PHP FPM php.ini file
	my $max = 1440;

	unless(-f "$self::phpfpmConfig{'PHP_FPM_CONF_DIR'}/php.ini") {
		error("$self::phpfpmConfig{'PHP_FPM_CONF_DIR'}/php.ini doesn't exists");
		$rs = 1;
	} else {
		my $file = iMSCP::File->new('filename' => "$self::phpfpmConfig{'PHP_FPM_CONF_DIR'}/php.ini");
		my $fileContent = $file->get();

		unless($fileContent) {
			error("Cannot read $self::phpfpmConfig{'PHP_FPM_CONF_DIR'}/php.ini");
			$rs = 1;
		} else {
			$fileContent =~ m/^\s*session.gc_maxlifetime\s*=\s*([0-9]+).*$/gim;
			my $cur = $1 || 0;
			$max = $cur if $cur > $max;
		}
	}

	$max = floor($max/60);

	my ($cmd, $stdout, $stderr);

	# panel sessions gc (Only for security since Zend_Session normaly take care of this)
	$cmd = "[ -d /var/www/imscp/gui/data/sessions ] && find /var/www/imscp/gui/data/sessions/ -type f -cmin +$max -exec rm -v {} \\;";
	$rs = execute($cmd, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Error while executing $cmd.\nReturned value is $rs") if ! $stderr && $rs;

	# customers sessions gc (TODO should we check for any maxlifetime overriden in pools configuration file ?)
	$cmd = "nice -n 19 find $main::imscpConfig{'USER_HOME_DIR'} -type f -path '*/phptmp/sess_*' -cmin +$max -exec rm -v {} \\;";
	$rs = execute($cmd, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Error while executing $cmd.\nReturned value is $rs") if ! $stderr && $rs;

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDelTmp');

	$rs;
}

=item getRunningUser

 Get user name under which the Apache server is running.

 Return string User name under which the apache server is running.

=cut

sub getRunningUser
{
	$self::apacheConfig{'APACHE_USER'};
}

=item getRunningUser

 Get group name under which the Apache server is running.

 Return string Group name under which the apache server is running.

=cut

sub getRunningGroup
{
	$self::apacheConfig{'APACHE_GROUP'};
}

=item enableSite($sites)

 Enable the given Apache sites.

 Param string $site String containing names of Apache sites to enable, each separated by a space
 Return int 0 on sucess, other on failure

=cut

sub enableSite
{
	my $self = shift;
	my $sites = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdEnableSite', \$sites) and return 1;

	my ($rs, $stdout, $stderr);

	for(split ' ', $sites) {
		if(-f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_"){
			$rs = execute("a2ensite $_", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			warning($stderr) if $stderr && ! $rs;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		} else {
			warning("Site $_ doesn't exists");
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdEnableSite', $sites);
}

=item disableSite($sites)

 Disable the given Apache sites.

 Param string $sitse String containing names of Apache sites to disable, each separated by a space
 Return int 0 on sucess, other on failure

=cut

sub disableSite
{
	my $self = shift;
	my $sites = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdDisableSite', \$sites) and return 1;

	my ($rs, $stdout, $stderr);

	for(split ' ', $sites) {
		if(-f "$self::apacheConfig{APACHE_SITES_DIR}/$_"){
			$rs = execute("a2dissite $_", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			warning($stderr) if $stderr && ! $rs;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		} else {
			warning("Site $_ doesn't exists");
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdDisableSite', $sites);
}

=item enableMod($mods)

 Enable the given Apache modules.

 Param string $mods String containing names of Apache modules to enable, each separated by a space
 Return int 0 on sucess, other on failure

=cut

sub enableMod
{
	my $self = shift;
	my $mods = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdEnableMod', \$mods) and return 1;

	my ($stdout, $stderr);

	my $rs = execute("a2enmod $mods", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdEnableMod', $mods);
}

=item disableMod($mods)

 Disable the given Apache modules.

 Param string $mods String containing names of Apache modules to disable, each separated by a space
 Return int 0 on sucess, other on failure

=cut

sub disableMod
{
	my $self = shift;
	my $mods = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdDisableMod', \$mods) and return 1;

	my ($stdout, $stderr);

	my $rs = execute("a2dismod $mods", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdDisableMod', $mods);
}

=item forceRestartPhpFpm()

 Schedule PHP FPM restart.

 Return int 0

=cut

sub forceRestartPhpFpm
{
	my $self = shift;

	$self->{'forceRestartPhpFpm'} = 'yes';

	0;
}

=item startPhpFpm()

 Start PHP FPM.

 Return int 0, other on failure

=cut

sub startPhpFpm
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdStartPhpFpm') and return 1;

	my ($rs, $stdout, $stderr);
	$rs = execute("$self::phpfpmConfig{'CMD_PHP_FPM'} start", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	error('Error while starting PHP FPM') if $rs && ! $stderr;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdStartPhpFpm');
}

=item stopPhpFpm()

 Stop PHP FPM.

 Return int 0, other on failure

=cut

sub stopPhpFpm
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdStopPhpFpm') and return 1;

	my ($rs, $stdout, $stderr);
	$rs = execute("$self::phpfpmConfig{'CMD_PHP_FPM'} stop", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	error('Error while stopping PHP FPM') if $rs && ! $stderr;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdStopPhpFpm');
}

=item restartPhpFpm()

 Restart or Reload PHP FPM.

 Return int 0, other on failure

=cut

sub restartPhpFpm
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdRestartPhpFpm') and return 1;

	my ($rs, $stdout, $stderr);
	$rs = execute(
		"$self::phpfpmConfig{'CMD_PHP_FPM'} " . ($self->{'forceRestart'} ? 'restart' : 'reload'), \$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	error('Error while restarting PHP FPM') if $rs && ! $stderr;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdRestartPhpFpm');
}

=item forceRestartApache()

 Schedule Apache restart.

 Return int 0

=cut

sub forceRestartApache
{
	my $self = shift;

	$self->{'forceRestartApache'} = 'yes';

	0;
}

=item startApache()

 Start Apache.

 Return int 0, other on failure

=cut

sub startApache
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdStart') and return 1;

	my ($rs, $stdout, $stderr);
	$rs = execute("$self::apacheConfig{'CMD_HTTPD'} start", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	error('Error while starting Apache') if $rs && ! $stderr;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdStart');
}

=item stopApache()

 Stop Apache.

 Return int 0, other on failure

=cut

sub stopApache
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdStop') and return 1;

	my ($rs, $stdout, $stderr);
	$rs = execute("$self::apacheConfig{'CMD_HTTPD'} stop", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	error('Error while stopping Apache') if $rs && ! $stderr;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdStop');
}

=item restartApache()

 Restart or Reload Apache.

 Return int 0, other on failure

=cut

sub restartApache
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdRestart') and return 1;

	my ($rs, $stdout, $stderr);
	$rs = execute(
		"$self::apacheConfig{'CMD_HTTPD'} " . ($self->{'forceRestart'} ? 'restart' : 'reload'), \$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	error('Error while restating Apache') if $rs && ! $stderr;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdRestart');
}

=item apacheBkpConfFile($filepath)

 Backup the given Apache configuration file.

 Param string $filepath Configuration file path
 Param string $prefix Prefix to use
 Param bool $system Backup as system file (default false)
 Return 0 on success, other on failure

=cut

sub apacheBkpConfFile
{
	my $self = shift;
	my $filepath = shift;
	my $prefix = shift || '';
	my $system = shift || 0;

	$self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', $filepath) and return 1;

	if(-f $filepath) {
		my $file = iMSCP::File->new('filename' => $filepath);
		my ($name, $path, $suffix) = fileparse($filepath);

		if($system && ! -f "$self->{'apacheBkpDir'}/$prefix$name$suffix$system") {
			$file->copyFile("$self->{'apacheBkpDir'}/$prefix$name$suffix$system") and return 1;
		} else {
			$file->copyFile("$self->{'apacheBkpDir'}/$prefix$name$suffix." . time) and return 1;
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdBkpConfFile', $filepath);
}

=item phpfpmBkpConfFile($filepath)

 Backup the given PHP FPM configuration file.

 Param string $filepath Configuration file path
 Param string $prefix Prefix to use
 Param bool $system  Param int $system Backup as system file (default false)
 Return 0 on success, other on failure

=cut

sub phpfpmBkpConfFile
{
	my $self = shift;
	my $filepath = shift;
	my $prefix = shift || '';
	my $system = shift || 0;

	$self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', $filepath) and return 1;

	if(-f $filepath) {
		my $file = iMSCP::File->new('filename' => $filepath);
		my ($name, $path, $suffix) = fileparse($filepath);

		if($system && ! -f "$self->{'phpfpmBkpDir'}/$prefix$name$suffix.system") {
			$file->copyFile("$self->{'phpfpmBkpDir'}/$prefix$name$suffix.system") and return 1;
		} else {
			$file->copyFile("$self->{'phpfpmBkpDir'}/$prefix$name$suffix." . time) and return 1;
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdBkpConfFile', $filepath);
}

=back

=head1 PRIVATE METHODS

=over 4

=item

 Called by new(). Initialize instance.

 Return Servers::httpd::apache_php_fpm

=cut

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforeHttpdInit', $self, 'apache_php_fpm'
	) and fatal('apache_php_fpm - beforeHttpdInit hook has failed');

	$self->{'apacheCfgDir'} = "$main::imscpConfig{'CONF_DIR'}/apache";
	$self->{'apacheBkpDir'} = "$self->{'apacheCfgDir'}/backup";
	$self->{'apacheWrkDir'} = "$self->{'apacheCfgDir'}/working";
	$self->{'apacheTplDir'} = "$self->{'apacheCfgDir'}/parts";

	my $conf = "$self->{'apacheCfgDir'}/apache.data";
	tie %self::apacheConfig, 'iMSCP::Config','fileName' => $conf;

	$self->{'tplValues'}->{$_} = $self::apacheConfig{$_} for keys %self::apacheConfig;

	$self->{'phpfpmCfgDir'} = "$main::imscpConfig{'CONF_DIR'}/php-fpm";
	$self->{'phpfpmBkpDir'} = "$self->{'phpfpmCfgDir'}/backup";
	$self->{'phpfpmWrkDir'} = "$self->{'phpfpmCfgDir'}/working";
	$self->{'phpfpmTplDir'} = "$self->{'phpfpmCfgDir'}/parts";

	my $conf = "$self->{'phpfpmCfgDir'}/phpfpm.data";
	tie %self::phpfpmConfig, 'iMSCP::Config', 'fileName' => $conf;

	$self->{'tplValues'}->{$_} = $self::phpfpmConfig{$_} for keys %self::phpfpmConfig;

	$self->{'hooksManager'}->trigger(
		'afterHttpdInit', $self, 'apache_php_fpm'
	) and fatal('apache_php_fpm - afterHttpdInit hook has failed');

	$self;
}

=item _addCfg(\%data)

 Add configuration files for the given entity (domain, subdomain...)

 Param hash_ref Reference to a hash containing needed data
 Return int 0 on success, other on failure

=cut

sub _addCfg
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdAddCfg') and return 1;

	my $rs = 0;
	my $poolLevel = $self::phpfpmConfig{'PHP_FPM_POOLS_LEVEL'};
	my $certPath = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs";
	my $certFile = "$certPath/$data->{'DMN_NAME'}.pem";

	$self->{'data'} = $data;

	# Disable and backup Apache sites if any
	for("$data->{'DMN_NAME'}.conf", "$data->{'DMN_NAME'}_ssl.conf"){
		$rs |= $self->disableSite($_) if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_";
		$rs |= $self->apacheBkpConfFile("$self->{'apacheWrkDir'}/$_", '', 0);
	}

	# Remove previous Apache sites if any
	for(
		"$self::apacheConfig{'APACHE_SITES_DIR'}/$data->{'DMN_NAME'}.conf",
		"$self::apacheConfig{'APACHE_SITES_DIR'}/$data->{'DMN_NAME'}_ssl.conf",
		"$self->{'apacheWrkDir'}/$data->{'DMN_NAME'}.conf",
		"$self->{'apacheWrkDir'}/$data->{'DMN_NAME'}_ssl.conf"
	) {
		$rs |= iMSCP::File->new(filename => $_)->delFile() if -f $_;
	}

	# Build Apache vhost files - Begin
	my %configs;
	$configs{"$data->{'DMN_NAME'}.conf"} = { 'redirect' => 'domain_redirect.tpl', 'normal' => 'domain.tpl' };

	if($data->{'have_cert'}) {
		$configs{"$data->{'DMN_NAME'}_ssl.conf"} = {
			'redirect' => 'domain_redirect_ssl.tpl', 'normal' => 'domain_ssl.tpl'
		};

		$self->{'data'}->{'CERT'} = $certFile;
	}

	if($data->{'FORWARD'} eq 'no') {
		# Dertermine pool name according pool level setting
		if($poolLevel eq 'per_site') {
			$self->{'data'}->{'POOL_NAME'} = $data->{'DMN_NAME'}
		} elsif($poolLevel eq 'per_domain') {
			$self->{'data'}->{'POOL_NAME'} = $data->{'PARENT_DMN_NAME'}
		} else {
			$self->{'data'}->{'POOL_NAME'} = $data->{'ROOT_DMN_NAME'}
		}
	}

	for(keys %configs) {

		# Schedule deletion of useless sections if needed
		if($data->{'FORWARD'} eq 'no') {

			$self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('suexec', @_) }
			) and return;

			$self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('cgi_support', @_) }
			) and return 1 unless ($data->{'have_cgi'} eq 'yes');

			$self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('php_enabled', @_) }
			) and return 1 unless ($data->{'have_php'} eq 'yes');

			$self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('php_disabled', @_) }
			) and return 1 if ($data->{'have_php'} eq 'yes');

			$self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('fcgid', @_) }
			) and return 1;

			$self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('fastcgi', @_) }
			) and return 1;

			$self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('itk', @_) }
			) and return 1;

		}

		$rs |= $self->buildConfFile(
			(
				$data->{'FORWARD'} eq 'no'
					? "$self->{'apacheTplDir'}/" . $configs{$_}->{'normal'}
					: "$self->{'apacheTplDir'}/" . $configs{$_}->{'redirect'}
			),
			{ 'destination' => "$self->{'apacheWrkDir'}/$_" }
		);

		$rs |= $self->installConfFile($_);

	}
	# Build Apache sites - End

	# Build and install custom Apache configuration file
	$rs |=	$self->buildConfFile(
		"$self->{'apacheTplDir'}/custom.conf.tpl",
		{ 'destination' => "$self::apacheConfig{'APACHE_CUSTOM_SITES_CONFIG_DIR'}/$data->{'DMN_NAME'}.conf" }
	) unless (-f "$self::apacheConfig{'APACHE_CUSTOM_SITES_CONFIG_DIR'}/$data->{'DMN_NAME'}.conf");

	# Enable all Apache sites
	$rs |= $self->enableSite($_) for keys %configs;

	# Build PHP FPM pool file - Begin

	# Backup older pool files if any
	$rs |= $self->phpfpmBkpConfFile("$self->{'phpfpmWrkDir'}/$data->{'DMN_NAME'}.conf");

	# Remove any previous pool file (needed in case pools level has been changed)
	for("$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}", $self->{'phpfpmWrkDir'}) {
		$rs |= iMSCP::File->new(
			'filename' => "$_/$data->{'DMN_NAME'}.conf"
		)->delFile() if -f "$_/$data->{'DMN_NAME'}.conf";
	}

	if(
		$data->{'FORWARD'} eq 'no' &&
		(
			$poolLevel eq 'per_site' ||
			($poolLevel eq 'per_domain' && $data->{'DMN_NAME'} eq $data->{'PARENT_DMN_NAME'}) ||
			($poolLevel eq 'per_user' && $data->{'DMN_NAME'} eq $data->{'ROOT_DMN_NAME'})
		)
	) {

		$rs |= $self->buildConfFile(
			"$self->{'phpfpmTplDir'}/pool.conf",
			{ 'destination' => "$self->{'phpfpmWrkDir'}/$data->{'DMN_NAME'}.conf" }
		);

		$rs |= $self->installConfFile(
			"$self->{'phpfpmWrkDir'}/$data->{'DMN_NAME'}.conf",
			{ 'destination' => "$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}/$data->{'DMN_NAME'}.conf" }
		);
	}

	# Build PHP FPM pool file - End,

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdAddCfg');

	$rs;
}

=item _addFiles(\%data)

 Add default directories and files for the given entity (domain, subdomain...).

 Param hash_ref Reference to a hash containing needed data
 Return int 0 on sucess, other on failure

=cut

sub _addFiles
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdAddFiles') and return 1;

	my $homeDir = $data->{'HOME_DIR'};
	my $rootUser = $main::imscpConfig{'ROOT_USER'};
	my $rootGroup = $main::imscpConfig{'ROOT_GROUP'};
	my $apacheGroup = $self::apacheConfig{'APACHE_GROUP'};
	my $newHtdocs = -d "$homeDir/htdocs";
	my ($rs, $stdout, $stderr);

	for ($self->_dmnFolders($data)){
		$rs |= iMSCP::Dir->new( dirname => $_->[0])->make(
			{ user => $_->[1], group => $_->[2], mode => $_->[3] }
		);
	}

	unless ($newHtdocs) {
		my $sourceDir = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/domain_default_page";
		my $dstDir = "$homeDir/htdocs/";
		my $fileSource =
		my $destFile = "$homeDir/htdocs/index.html";

		$rs |= execute("$main::imscpConfig{'CMD_CP'} -vnRT $sourceDir $dstDir", \$stdout, \$stderr);
		debug("$stdout") if $stdout;
		error("$stderr") if $stderr;

		$rs |= $self->buildConfFile($fileSource, { 'destination' => $destFile });
		$rs |= setRights(
			$dstDir,
			{
				'user' => $data->{'USER'},
				'group' => $apacheGroup,
				'filemode' => '0640',
				'dirmode' => '0750',
				'recursive' => 'yes'
			}
		);
	}

	my $sourceDir = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/domain_disable_page";
	my $dstDir = "$homeDir/domain_disable_page";
	my $fileSource =
	my $destFile = "$homeDir/domain_disable_page/index.html";

	$rs |= execute("$main::imscpConfig{'CMD_CP'} -vnRT $sourceDir $dstDir", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;

	$rs |= $self->buildConfFile($fileSource, { 'destination' => $destFile });

	$rs |= setRights(
		"$homeDir/cgi-bin", { 'user' => $data->{'USER'}, 'group' => $data->{'GROUP'}, 'recursive' => 'yes' }
	);

	$rs |= setRights(
		"$homeDir/domain_disable_page",
		{
			'user' => $rootUser,
			'group' => $apacheGroup,
			'filemode' => '0640',
			'dirmode' => '0710',
			'recursive' => 'yes'
		}
	);

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdAddFiles');

	$rs;
}

=item _dmnFolders(\%data)

 Get Web folders list to create for the given entity (domain, subdomain...).

 Param hash_ref Reference to a hash containing needed data
 Return array_ref Reference to an array containing list of web folder to create

=cut

sub _dmnFolders
{
	my $self = shift;
	my $data = shift;
	my $homeDir = $data->{'HOME_DIR'};
	my $rootUser = $main::imscpConfig{'ROOT_USER'};
	my $rootGroup = $main::imscpConfig{'ROOT_GROUP'};
	my $apacheGroup = $self::apacheConfig{'APACHE_GROUP'};
	my $newHtdocs = -d "$homeDir/htdocs";
	my ($rs, $stdout, $stderr);

	my @folders = (
		["$homeDir", $data->{'USER'}, $apacheGroup, 0710],
		["$homeDir/htdocs", $data->{'USER'}, $apacheGroup, 0750],
		["$homeDir/cgi-bin", $data->{'USER'}, $data->{'GROUP'}, 0751],
		# TODO phptmp creation should be based on pools level
		["$homeDir/phptmp", $data->{'USER'}, $data->{'GROUP'}, 0770]
	);

	$self->{'hooksManager'}->trigger('beforeHttpdDmnFolders', \@folders);

	push(@folders, ["$homeDir/errors", $data->{'USER'}, $apacheGroup, 0710]) if $self->{'mode'} eq 'dmn';

	$self->{'hooksManager'}->trigger('afterHttpdDmnFolders', \@folders);

	@folders;
}

=item END

 Code triggered at the very end of script execution.

-  Start or restart PHP FPM if needed
 - Start or restart apache if needed
 - Remove old traffic logs file if exists

 Return int Exit code

=cut

END
{
	my $exitCode = $?;
	my $rs = 0;
	my $trfDir = "$self::apacheConfig{'APACHE_LOG_DIR'}/traff";
	
	my $self = Servers::httpd::apache_php_fpm->new();

	if($self->{'startPhpFpm'} && $self->{'startPhpFpm'} eq 'yes'){
		$rs = $self->startPhpFpm();
	} elsif($self->{'restartPhpFpm'} && $self->{'restartPhpFpm'} eq 'yes') {
		$rs = $self->restartPhpFpm();
	}
	
	if($self->{'startApache'} && $self->{'startApache'} eq 'yes'){
		$rs = $self->startApache();
	} elsif($self->{'restartApache'} && $self->{'restartApache'} eq 'yes') {
		$rs = $self->restartApache();
	}

	$rs |= iMSCP::Dir->new(dirname => "$trfDir.old")->remove() if -d "$trfDir.old";

	$? = $exitCode || $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
