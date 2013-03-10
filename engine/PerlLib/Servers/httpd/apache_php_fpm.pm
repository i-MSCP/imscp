#!/usr/bin/perl

=head1 NAME

 Servers::httpd::apache_php_fpm - i-MSCP Apache PHP-FPM Server implementation

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
use iMSCP::Config;
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

 i-MSCP Apache PHP FPM Server implementation.

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
	my $rs = 0;

	$rs = $hooksManager->trigger('beforeHttpdRegisterSetupHooks', $hooksManager, 'apache_php_fpm');
	return $rs if $rs;

	require Servers::httpd::apache_php_fpm::installer;

	$rs = Servers::httpd::apache_php_fpm::installer->getInstance()->registerSetupHooks($hooksManager);
	return $rs if $rs;

	$hooksManager->trigger('afterHttpdRegisterSetupHooks', $hooksManager, 'apache_php_fpm');
}

=item preinstall()

 Process preinstall tasks.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdPreInstall', 'apache_php_fpm');
	return $rs if $rs;

	$rs = $self->stopPhpFpm();
	return $rs if $rs;

	$rs = $self->stopApache();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdPreInstall', 'apache_php_fpm');
}

=item install()

 Process install tasks.

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdInstall', 'apache_php_fpm');
	return $rs if $rs;

	require Servers::httpd::apache_php_fpm::installer;

	$rs = Servers::httpd::apache_php_fpm::installer->getInstance()->install();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdInstall', 'apache_php_fpm');
}

=item postinstall()

 Process postinstall tasks.

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdPostInstall', 'apache_php_fpm');
	return $rs if $rs;

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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdUninstall', 'apache_php_fpm');
	return $rs if $rs;

	$rs = $self->stopPhpFpm();
	return $rs if $rs;

	$rs = $self->stopApache();
	return $rs if $rs;

	require Servers::httpd::apache_php_fpm::uninstaller;

	$rs = Servers::httpd::apache_php_fpm::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$rs = $self->startPhpFpm();
	return $rs if $rs;

	$rs = $self->startApache();
	return $rs if $rs;

	$rs = $self->{'hooksManager'}->trigger('afterHttpdUninstall', 'apache_php_fpm');
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdAddUser');
	return $rs if $rs;

	my $homeDir = $data->{'HOME_DIR'};
	my $rootUser = $main::imscpConfig{'ROOT_USER'};
	my $rootGroup = $main::imscpConfig{'ROOT_GROUP'};
	my $apacheGroup = $self::apacheConfig{'APACHE_GROUP'};
	my ($stdout, $stderr);

	$self->{'data'} = $data;

	# mod_cband - begin

	$rs = $self->apacheBkpConfFile("$self->{'apacheWrkDir'}/00_modcband.conf");
	return $rs if $rs;

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

		$file = iMSCP::File->new('filename' => "$self->{'apacheWrkDir'}/00_modcband.conf");

		$rs = $file->set($content);
		return $rs if $rs;

		$rs = 1 if $file->save();
		return $rs if $rs;

		$rs = $self->installConfFile('00_modcband.conf');
		return $rs if $rs;

		$rs = $self->enableSite('00_modcband.conf');
		return $rs if $rs;

		unless(-f "$self::apacheConfig{'SCOREBOARDS_DIR'}/$data->{'USER'}"){
			$rs	=	iMSCP::File->new(
				filename => "$self::apacheConfig{'SCOREBOARDS_DIR'}/$data->{'USER'}"
			)->save();
			return $rs if $rs;
		}
	}

	# mod_cband - End

	# Common web files - Begin

	# error docs
	$rs = execute(
		"$main::imscpConfig{'CMD_CP'} -nRT $main::imscpConfig{'GUI_ROOT_DIR'}/public/errordocs $homeDir/errors",
		\$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = setRights(
		"$homeDir/errors",
		{
			'user' => $data->{'USER'},
			'group' => $apacheGroup,
			'filemode' => '0640',
			'dirmode' => '0750',
			'recursive' => 'yes'
		}
	);
	return $rs if $rs;

	for(
		"$homeDir/$self::apacheConfig{'HTACCESS_USERS_FILE_NAME'}",
		"$homeDir/$self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'}"
	) {
		my $fileH =	iMSCP::File->new('filename' => $_);

		$rs = $fileH->save() unless -f $_;
		return $rs if $rs;

		$rs = $fileH->mode(0640);
		return $rs if $rs;
	}

	# Common web files - End

	$self->{'restartApache'} = 'yes';

	$self->{'hooksManager'}->trigger('beforeHttpdAddUser');
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDelUser');
	return $rs if $rs;

	my $homeDir = $data->{'HOME_DIR'};

	$self->{'data'} = $data;

	# mod_cband - Begin

	$rs = $self->apacheBkpConfFile("$self->{'apacheWrkDir'}/00_modcband.conf");
	return $rs if $rs;

	my $filename = (
		-f "$self->{'apacheWrkDir'}/00_modcband.conf"
			? "$self->{'apacheWrkDir'}/00_modcband.conf" : "$self->{'apacheCfgDir'}/00_modcband.conf"
	);

	my $file = iMSCP::File->new('filename' => $filename);
	my $content	= $file->get();

	unless(defined $content) {
		error("Unable to read $filename");
		return $rs if $rs;
	} else {
		my $bUTag = "## SECTION $data->{'USER'} BEGIN.\n";
		my $eUTag = "## SECTION $data->{'USER'} END.\n";

		$content = replaceBloc($bUTag, $eUTag, '', $content, undef);

		$file = iMSCP::File->new('filename' => "$self->{'apacheWrkDir'}/00_modcband.conf");

		$rs = $file->set($content);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $self->installConfFile('00_modcband.conf');
		return $rs if $rs;

		$rs = $self->enableSite('00_modcband.conf');
		return $rs if $rs;

		if( -f "$self::apacheConfig{'SCOREBOARDS_DIR'}/$data->{'USER'}"){
			$rs = iMSCP::File->new('filename' => "$self::apacheConfig{'SCOREBOARDS_DIR'}/$data->{'USER'}")->delFile();
			return $rs if $rs;
		}
	}
	# mod_cband - End

	$self->{'restartApache'} = 'yes';

	$self->{'hooksManager'}->trigger('afterHttpdDelUser');
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
	my $rs = 0;

	$self->{'mode'} = 'dmn';

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdAddDmn');
	return $rs if $rs;

	$self->{'data'} = $data;

	$rs = $self->_addCfg($data);
	return $rs if $rs;

	$rs = $self->_addFiles($data) if $data->{'FORWARD'} eq 'no';
	return $rs if $rs;

	$self->{'restartPhpFpm'} = 'yes';
	$self->{'restartApache'} = 'yes';

	delete $self->{'data'};

	$self->{'hooksManager'}->trigger('afterHttpdAddDmn');
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDelDmn');
	return $rs if $rs;

	for("$data->{'DMN_NAME'}.conf", "$data->{'DMN_NAME'}_ssl.conf") {
		$rs = $self->disableSite($_) if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_";
		return $rs if $rs;

		$rs = $self->apacheBkpConfFile("$self->{'apacheWrkDir'}/$_");
		return $rs if $rs;
	}

	for(
		"$self::apacheConfig{'APACHE_SITES_DIR'}/$data->{'DMN_NAME'}.conf",
		"$self::apacheConfig{'APACHE_SITES_DIR'}/$data->{'DMN_NAME'}_ssl.conf",
		"$self::apacheConfig{'APACHE_CUSTOM_SITES_CONFIG_DIR'}/$data->{'DMN_NAME'}.conf",
		"$self->{'apacheWrkDir'}/$data->{'DMN_NAME'}.conf",
		"$self->{'apacheWrkDir'}/$data->{'DMN_NAME'}_ssl.conf",
		"$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}/$data->{'DMN_NAME'}.conf"
	) {
		$rs = iMSCP::File->new('filename' => $_)->delFile() if -f $_;
		return $rs if $rs;
	}

	$rs = iMSCP::Dir->new('dirname' => $data->{'HOME_DIR'})->remove() if -d $data->{'HOME_DIR'};
	return $rs if $rs;

	$self->{'restartPhpFpm'} = 'yes';
	$self->{'restartApache'} = 'yes';

	delete $self->{'data'};

	$self->{'hooksManager'}->trigger('afterHttpdDelDmn');
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

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDisableDmn');
	return $rs if $rs;

	$self->{'data'} = $data;

	$rs = $self->apacheBkpConfFile("$self->{'apacheWrkDir'}/$data->{'DMN_NAME'}.conf");
	return $rs if $rs;

	$rs = $self->buildConfFile(
		"$self->{'apacheTplDir'}/domain_disabled.tpl",
		{ 'destination' => "$self->{'apacheWrkDir'}/$data->{'DMN_NAME'}.conf" }
	);
	return $rs if $rs;

	$rs = $self->installConfFile("$data->{'DMN_NAME'}.conf");
	return $rs if $rs;

	$rs = $self->enableSite("$data->{'DMN_NAME'}.conf");
	return $rs if $rs;

	$self->{'restartPhpFpm'} = 'yes';
	$self->{'restartApache'} = 'yes';

	delete $self->{'data'};

	$self->{'hooksManager'}->trigger('afterHttpdDisableDmn');
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
	my $rs = 0;

	$self->{'mode'}	= 'sub';

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdAddSub');
	return $rs if $rs;

	$self->{'data'} = $data;

	$rs = $self->_addCfg($data);
	return $rs if $rs;

	$rs = $self->_addFiles($data) if $data->{'FORWARD'} eq 'no';
	return $rs if $rs;

	$self->{'restartPhpFpm'} = 'yes';
	$self->{'restartApache'} = 'yes';

	delete $self->{'data'};

	$self->{'hooksManager'}->trigger('afterHttpdAddSub');
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDelSub');
	return $rs if $rs;

	$rs = $self->delDmn($data);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdDelSub');
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDisableSub');
	return $rs if $rs;

	$rs = $self->disableDmn($data);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('beforeHttpdDisableSub');
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdAddHtuser');
	return $rs if $rs;

	my $fileName = $self::apacheConfig{'HTACCESS_USERS_FILE_NAME'};
	my $filePath = "$main::imscpConfig{'USER_HOME_DIR'}/$data->{'HTUSER_DMN'}/$fileName";
	my $fileH = iMSCP::File->new('filename' => $filePath);

	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if ! defined $fileContent;
	$fileContent =~ s/^$data->{'HTUSER_NAME'}:[^\n]*\n//gim;
	$fileContent .= "$data->{'HTUSER_NAME'}:$data->{'HTUSER_PASS'}\n";

	$rs = $fileH->set($fileContent);
	return $rs if $rs;

	$rs = $fileH->save();
	return $rs if $rs;

	$rs = $fileH->mode(0644);
	return $rs if $rs;

	$rs = $fileH->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdAddHtuser');
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDelHtuser');
	return $rs if $rs;

	my $fileName = $self::apacheConfig{'HTACCESS_USERS_FILE_NAME'};
	my $filePath = "$main::imscpConfig{'USER_HOME_DIR'}/$data->{'HTUSER_DMN'}/$fileName";
	my $fileH = iMSCP::File->new('filename' => $filePath);

	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if ! defined $fileContent;
	$fileContent =~ s/^$data->{'HTUSER_NAME'}:[^\n]*\n//gim;

	$rs = $fileH->set($fileContent);
	return $rs if $rs;

	$rs = $fileH->save();
	return $rs if $rs;

	$rs = $fileH->mode(0644);
	return $rs if $rs;

	$rs = $fileH->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdDelHtuser');
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdAddHtgroup');
	return $rs if $rs;

	my $fileName = $self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'};
	my $filePath = "$main::imscpConfig{'USER_HOME_DIR'}/$data->{'HTGROUP_DMN'}/$fileName";
	my $fileH = iMSCP::File->new('filename' => $filePath);

	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if ! defined $fileContent;
	$fileContent =~ s/^$data->{'HTGROUP_NAME'}:[^\n]*\n//gim;
	$fileContent .= "$data->{'HTGROUP_NAME'}:$data->{'HTGROUP_USERS'}\n";

	$rs = $fileH->set($fileContent);
	return $rs if $rs;

	$rs = $fileH->save();
	return $rs if $rs;

	$rs = $fileH->mode(0644);
	return $rs if $rs;

	$rs = $fileH->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdAddHtgroup');
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDelHtgroup');
	return $rs if $rs;

	my $fileName = $self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'};
	my $filePath = "$main::imscpConfig{'USER_HOME_DIR'}/$data->{'HTGROUP_DMN'}/$fileName";
	my $fileH = iMSCP::File->new('filename' => $filePath);

	my $fileContent	= $fileH->get() if -f $filePath;
	$fileContent = '' if ! defined $fileContent;
	$fileContent =~ s/^$data->{'HTGROUP_NAME'}:[^\n]*\n//img;

	$rs = $fileH->set($fileContent);
	return $rs if $rs;

	$rs = $fileH->save();
	return $rs if $rs;

	$rs = $fileH->mode(0644);
	return $rs if $rs;

	$rs = $fileH->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdDelHtgroup');
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdAddHtaccess');
	return $rs if $rs;

	my $fileUser = "$data->{'HOME_PATH'}/$self::apacheConfig{'HTACCESS_USERS_FILE_NAME'}";
	my $fileGroup = "$data->{'HOME_PATH'}/$self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'}";
	my $filePath = "$data->{'AUTH_PATH'}/.htaccess";
	my $fileH = iMSCP::File->new('filename' => $filePath);

	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if ! defined $fileContent;

	my $bTag = "\t\t### START i-MSCP PROTECTION ###\n";
	my $eTag = "\t\t### END i-MSCP PROTECTION ###\n";
	my $tag	 = "\t\tAuthType $data->{'AUTH_TYPE'}\n\t\tAuthName \"$data->{'AUTH_NAME'}\"\n\t\tAuthUserFile $fileUser\n";

	if($data->{'HTUSERS'} eq '') {
		$tag .=	"\t\tAuthGroupFile $fileGroup\n\t\tRequire group $data->{'HTGROUPS'}\n";
	} else {
		$tag .=	"\t\tRequire user $data->{'HTUSERS'}\n";
	}

	$fileContent = replaceBloc($bTag, $eTag, '', $fileContent, undef);
	$fileContent = $bTag . $tag . $eTag . $fileContent;

	$rs = $fileH->set($fileContent);
	return $rs if $rs;

	$rs = $fileH->save();
	return $rs if $rs;

	$rs = $fileH->mode(0644);
	return $rs if $rs;

	$rs = $fileH->owner($data->{'USER'}, $data->{'GROUP'});
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdAddHtaccess');
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDelHtaccess');

	my $fileUser = "$data->{'HOME_PATH'}/$self::apacheConfig{'HTACCESS_USERS_FILE_NAME'}";
	my $fileGroup = "$data->{'HOME_PATH'}/$self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'}";
	my $filePath = "$data->{'AUTH_PATH'}/.htaccess";
	my $fileH = iMSCP::File->new('filename' => $filePath);

	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if ! defined $fileContent;

	my $bTag = "\t\t### START i-MSCP PROTECTION ###\n";
	my $eTag = "\t\t### END i-MSCP PROTECTION ###\n";

	$fileContent = replaceBloc($bTag, $eTag, '', $fileContent, undef);

	if($fileContent ne '') {
		$rs = $fileH->set($fileContent);
		return $rs if $rs;

		$rs = $fileH->save();
		return $rs if $rs;

		$rs = $fileH->mode(0644);
		return $rs if $rs;

		$rs = $fileH->owner($data->{'USER'}, $data->{'GROUP'});
		return $rs if $rs;
	} else {
		$rs = $fileH->delFile() if -f $filePath;
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterHttpdDelHtaccess');
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdAddIps');
	return $rs if $rs;

	$rs = $self->apacheBkpConfFile("$self->{'apacheWrkDir'}/00_nameserver.conf");
	return $rs if $rs;

	my $filename = (
		-f "$self->{'apacheWrkDir'}/00_nameserver.conf"
			? "$self->{'apacheWrkDir'}/00_nameserver.conf"
			: "$self->{'apacheCfgDir'}/00_nameserver.conf"
	);

	my $file = iMSCP::File->new('filename' => $filename);
	my $content = $file->get();
	return 1 if !defined $content;
	$content =~ s/NameVirtualHost[^\n]+\n//gi;

	$content .= "NameVirtualHost $_:443\n" for @{$data->{'SSLIPS'}};
	$content .= "NameVirtualHost $_:80\n" for @{$data->{'IPS'}};

	$file = iMSCP::File->new('filename' => "$self->{'apacheWrkDir'}/00_nameserver.conf");

	$rs = $file->set($content);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $self->installConfFile('00_nameserver.conf');
	return $rs if $rs;

	$rs = $self->enableSite('00_nameserver.conf');
	return $rs if $rs;

	$self->{'restartApache'} = 'yes';

	delete $self->{'data'};

	$self->{'hooksManager'}->trigger('afterHttpdAddIps');
}

=item setGuiPermissions()

 Set Panel (GUI) directories and files permissions.

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = shift;

	require Servers::httpd::apache_php_fpm::installer;

	Servers::httpd::apache_php_fpm::installer->getInstance()->setGuiPermissions();
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

	return undef unless defined $cfgTpl;

	$self->{'tplValues'}->{$_} = $self->{'data'}->{$_} for keys %{$self->{'data'}};

	$self->{'hooksManager'}->trigger('beforeHttpdBuildConf', \$cfgTpl, $filename);

	$cfgTpl = process($self->{'tplValues'}, $cfgTpl);
	return undef if ! defined $cfgTpl;

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
	my $rs = 0;

	$options = {} if ref $options ne 'HASH';

	my ($filename, $directories, $suffix) = fileparse($file);

	$file = "$self->{'apacheCfgDir'}/$file" unless -d $directories && $directories ne './';

	my $fileH = iMSCP::File->new('filename' => $file);
	my $cfgTpl = $fileH->get();
	return 1 unless defined $cfgTpl;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildConfFile', \$cfgTpl, "$filename$suffix");
	return $rs if $rs;

	$cfgTpl = $self->buildConf($cfgTpl, "$filename$suffix");
	return 1 if ! defined $cfgTpl;

	$cfgTpl =~ s/\n{2,}/\n\n/g; # Remove duplicate blank lines

	$rs = $self->{'hooksManager'}->trigger('afterHttpdBuildConfFile', \$cfgTpl, "$filename$suffix");
	return $rs if $rs;

	$fileH = iMSCP::File->new(
		'filename' => ($options->{'destination'}
			? $options->{'destination'} : "$self->{'apacheWrkDir'}/$filename$suffix")
	);

	$rs = $fileH->set($cfgTpl);
	return $rs if $rs;

	$rs = $fileH->save();
	return $rs if $rs;

	$rs = $fileH->mode($options->{'mode'} ? $options->{'mode'} : 0644);
	return $rs if $rs;

	$fileH->owner(
		$options->{'user'} ? $options->{'user'} : $main::imscpConfig{'ROOT_USER'},
		$options->{'group'} ? $options->{'group'} : $main::imscpConfig{'ROOT_GROUP'}
	);
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
	my $rs = 0;

	$options = {} if ref $options ne 'HASH';

	my ($filename, $directories, $suffix) = fileparse($file);

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdInstallConfFile', "$filename$suffix");
	return $rs if $rs;

	$file = "$self->{'apacheWrkDir'}/$file" unless -d $directories && $directories ne './';

	my $fileH = iMSCP::File->new('filename' => $file);

	$rs = $fileH->mode($options->{'mode'} ? $options->{'mode'} : 0644);
	return $rs if $rs;

	$rs = $fileH->owner(
		$options->{'user'} ? $options->{'user'} : $main::imscpConfig{'ROOT_USER'},
		$options->{'group'} ? $options->{'group'} : $main::imscpConfig{'ROOT_GROUP'}
	);
	return $rs if $rs;

	$rs = $fileH->copyFile(
		$options->{'destination'} ? $options->{'destination'} : "$self::apacheConfig{'APACHE_SITES_DIR'}/$filename$suffix"
	);
	return $rs if $rs;

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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdSetData', $data);
	return $rs if $rs;

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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdRemoveSection', $sectionName, $cfgTpl);
	return $rs if $rs;

	my $bTag = "# SECTION $sectionName BEGIN.\n";
	my $eTag = "# SECTION $sectionName END.\n";

	debug("Removing useless section: $sectionName");

	$$cfgTpl = replaceBloc($bTag, $eTag, '', $$cfgTpl, undef);

	$self->{'hooksManager'}->trigger('afterHttpdRemoveSection', $sectionName, $cfgTpl);
}

=item

 Return traffic consumption for the given domain.

 Return int Traffic in bytes

=cut

sub getTraffic
{
	my $self = shift;
	my $who = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdGetTraffic') and return 0;

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

		if($rs || ! $stdout) {
			error('imscp-apache-logger is not running') unless $stderr;
		} else {
			while($stdout =~ m/^\s{0,}(\d+)(?!.*error)/mg){
				$rs = execute("kill -s HUP $1", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
			}
		}
	}

	if(-d "$trfDir.old" && -f "$trfDir.old/$who-traf.log") {
		my $content = iMSCP::File->new('filename' => "$trfDir.old/$who-traf.log")->get();

		if(defined $content) {
			my @lines = split("\n", $content);
			$traff += $_ for @lines;
		} else {
			error("Unable to read $trfDir.old/$who-traf.log");
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdGetTraffic') and return 0;

	$traff;
}

=item

 Remove Apache logs (logs older than 1 year).

 Return int 0 on success, other on failure

=cut

sub delOldLogs
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDelOldLogs');
	return $rs if $rs;

	my $logDir = $self::apacheConfig{'APACHE_LOG_DIR'};
	my $bLogDir = $self::apacheConfig{'APACHE_BACKUP_LOG_DIR'};
	my $uLogDir = $self::apacheConfig{'APACHE_USERS_LOG_DIR'};
	my ($stdout, $stderr);

	for ($logDir, $bLogDir, $uLogDir) {
		my $cmd = "nice -n 19 find $_ -maxdepth 1 -type f -name '*.log*' -mtime +365 -exec rm -v {} \\;";
		$rs = execute($cmd, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		error("Error while executing $cmd.\nReturned value is $rs") if ! $stderr && $rs;
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterHttpdDelOldLogs');
}

=item

 Delete temporary files (PHP session files).

 Return int 0 on success, other on failure

=cut

sub delTmp
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDelTmp');
	return $rs if $rs;

	# Get session.gc_maxlifetime value from global PHP FPM php.ini file
	my $max = 1440;

	unless(-f "$self::phpfpmConfig{'PHP_FPM_CONF_DIR'}/php.ini") {
		error("$self::phpfpmConfig{'PHP_FPM_CONF_DIR'}/php.ini doesn't exists");
		return $rs if $rs;
	} else {
		my $file = iMSCP::File->new('filename' => "$self::phpfpmConfig{'PHP_FPM_CONF_DIR'}/php.ini");
		my $fileContent = $file->get();

		unless(defined $fileContent) {
			error("Unable to read $self::phpfpmConfig{'PHP_FPM_CONF_DIR'}/php.ini");
			return $rs if $rs;
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
	return $rs if $rs;

	# customers sessions gc (TODO should we check for any maxlifetime overriden in pools configuration file ?)
	$cmd = "nice -n 19 find $main::imscpConfig{'USER_HOME_DIR'} -type f -path '*/phptmp/sess_*' -cmin +$max -exec rm -v {} \\;";
	$rs = execute($cmd, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Error while executing $cmd.\nReturned value is $rs") if ! $stderr && $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdDelTmp');
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdEnableSite', \$sites);
	return $rs if $rs;

	my ($stdout, $stderr);

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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDisableSite', \$sites);
	return $rs if $rs;

	my ($stdout, $stderr);

	for(split ' ', $sites) {
		if(-f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_"){
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdEnableMod', \$mods);
	return $rs if $rs;

	my ($stdout, $stderr);

	$rs = execute("a2enmod $mods", \$stdout, \$stderr);
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDisableMod', \$mods);
	return $rs if $rs;

	my ($stdout, $stderr);

	$rs = execute("a2dismod $mods", \$stdout, \$stderr);
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdStartPhpFpm');
	return $rs if $rs;

	my ($stdout, $stderr);
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdStopPhpFpm');
	return $rs if $rs;

	my ($stdout, $stderr);
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdRestartPhpFpm');
	return $rs if $rs;

	my ($stdout, $stderr);
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdStart');
	return $rs if $rs;

	my ($stdout, $stderr);
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdStop');
	return $rs if $rs;

	my ($stdout, $stderr);
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdRestart');
	return $rs if $rs;

	my ($stdout, $stderr);
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', $filepath);
	return $rs if $rs;

	if(-f $filepath) {
		my $file = iMSCP::File->new('filename' => $filepath);
		my ($name, $path, $suffix) = fileparse($filepath);

		if($system && ! -f "$self->{'apacheBkpDir'}/$prefix$name$suffix$system") {
			$rs = $file->copyFile("$self->{'apacheBkpDir'}/$prefix$name$suffix$system");
			return $rs if $rs;
		} else {
			$rs = $file->copyFile("$self->{'apacheBkpDir'}/$prefix$name$suffix." . time);
			return $rs if $rs;
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', $filepath);
	return $rs if $rs;

	if(-f $filepath) {
		my $file = iMSCP::File->new('filename' => $filepath);
		my ($name, $path, $suffix) = fileparse($filepath);

		if($system && ! -f "$self->{'phpfpmBkpDir'}/$prefix$name$suffix.system") {
			$rs = $file->copyFile("$self->{'phpfpmBkpDir'}/$prefix$name$suffix.system");
			return $rs if $rs;
		} else {
			$rs = $file->copyFile("$self->{'phpfpmBkpDir'}/$prefix$name$suffix." . time);
			return $rs if $rs;
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdBkpConfFile', $filepath);
}

=back

=head1 PRIVATE METHODS

=over 4

=item

 Called by getInstance(). Initialize instance.

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

	$conf = "$self->{'phpfpmCfgDir'}/phpfpm.data";
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdAddCfg');
	return $rs if $rs;

	my $poolLevel = $self::phpfpmConfig{'PHP_FPM_POOLS_LEVEL'};
	my $certPath = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs";
	my $certFile = "$certPath/$data->{'DMN_NAME'}.pem";

	$self->{'data'} = $data;

	# Disable and backup Apache sites if any
	for("$data->{'DMN_NAME'}.conf", "$data->{'DMN_NAME'}_ssl.conf"){
		$rs = $self->disableSite($_) if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_";
		return $rs if $rs;

		$rs = $self->apacheBkpConfFile("$self->{'apacheWrkDir'}/$_", '', 0);
		return $rs if $rs;
	}

	# Remove previous Apache sites if any
	for(
		"$self::apacheConfig{'APACHE_SITES_DIR'}/$data->{'DMN_NAME'}.conf",
		"$self::apacheConfig{'APACHE_SITES_DIR'}/$data->{'DMN_NAME'}_ssl.conf",
		"$self->{'apacheWrkDir'}/$data->{'DMN_NAME'}.conf",
		"$self->{'apacheWrkDir'}/$data->{'DMN_NAME'}_ssl.conf"
	) {
		$rs = iMSCP::File->new('filename' => $_)->delFile() if -f $_;
		return $rs if $rs;
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

			$rs = $self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('suexec', @_) }
			);
			return $rs if $rs;

			$rs = $self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('cgi_support', @_) }
			) unless ($data->{'have_cgi'} eq 'yes');
			return $rs if $rs;

			$rs = $self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('php_enabled', @_) }
			) unless ($data->{'have_php'} eq 'yes');
			return $rs if $rs;

			$rs = $self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('php_disabled', @_) }
			) if ($data->{'have_php'} eq 'yes');
			return $rs if $rs;

			$rs = $self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('fcgid', @_) }
			);
			return $rs if $rs;

			$rs = $self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('fastcgi', @_) }
			);
			return $rs if $rs;

			$rs = $self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('itk', @_) }
			);
			return $rs if $rs;
		}

		$rs = $self->buildConfFile(
			(
				$data->{'FORWARD'} eq 'no'
					? "$self->{'apacheTplDir'}/" . $configs{$_}->{'normal'}
					: "$self->{'apacheTplDir'}/" . $configs{$_}->{'redirect'}
			),
			{ 'destination' => "$self->{'apacheWrkDir'}/$_" }
		);
		return $rs if $rs;

		$rs = $self->installConfFile($_);
		return $rs if $rs;

	}
	# Build Apache sites - End

	# Build and install custom Apache configuration file
	$rs =	$self->buildConfFile(
		"$self->{'apacheTplDir'}/custom.conf.tpl",
		{ 'destination' => "$self::apacheConfig{'APACHE_CUSTOM_SITES_CONFIG_DIR'}/$data->{'DMN_NAME'}.conf" }
	) unless (-f "$self::apacheConfig{'APACHE_CUSTOM_SITES_CONFIG_DIR'}/$data->{'DMN_NAME'}.conf");
	return $rs if $rs;

	# Enable all Apache sites
	for(keys %configs) {
		$rs = $self->enableSite($_);
		return $rs if $rs;
	}

	# Build PHP FPM pool file - Begin

	# Backup older pool files if any
	$rs = $self->phpfpmBkpConfFile("$self->{'phpfpmWrkDir'}/$data->{'DMN_NAME'}.conf");
	return $rs if $rs;

	# Remove any previous pool file (needed in case pools level has been changed)
	for("$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}", $self->{'phpfpmWrkDir'}) {
		$rs = iMSCP::File->new(
			'filename' => "$_/$data->{'DMN_NAME'}.conf"
		)->delFile() if -f "$_/$data->{'DMN_NAME'}.conf";
		return $rs if $rs;
	}

	if(
		$data->{'FORWARD'} eq 'no' &&
		(
			$poolLevel eq 'per_site' ||
			($poolLevel eq 'per_domain' && $data->{'DMN_NAME'} eq $data->{'PARENT_DMN_NAME'}) ||
			($poolLevel eq 'per_user' && $data->{'DMN_NAME'} eq $data->{'ROOT_DMN_NAME'})
		)
	) {

		$rs = $self->buildConfFile(
			"$self->{'phpfpmTplDir'}/pool.conf",
			{ 'destination' => "$self->{'phpfpmWrkDir'}/$data->{'DMN_NAME'}.conf" }
		);
		return $rs if $rs;

		$rs = $self->installConfFile(
			"$self->{'phpfpmWrkDir'}/$data->{'DMN_NAME'}.conf",
			{ 'destination' => "$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}/$data->{'DMN_NAME'}.conf" }
		);
		return $rs if $rs;
	}

	# Build PHP FPM pool file - End,

	$self->{'hooksManager'}->trigger('afterHttpdAddCfg');
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdAddFiles');
	return $rs if $rs;

	my $homeDir = $data->{'HOME_DIR'};
	my $rootUser = $main::imscpConfig{'ROOT_USER'};
	my $rootGroup = $main::imscpConfig{'ROOT_GROUP'};
	my $apacheGroup = $self::apacheConfig{'APACHE_GROUP'};
	my $newHtdocs = -d "$homeDir/htdocs";
	my ($stdout, $stderr);

	for ($self->_dmnFolders($data)) {
		$rs = iMSCP::Dir->new(
			'dirname' => $_->[0]
		)->make(
			{ 'user' => $_->[1], 'group' => $_->[2], 'mode' => $_->[3] }
		);
		return $rs if $rs;
	}

	unless ($newHtdocs) {
		my $sourceDir = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/domain_default_page";
		my $dstDir = "$homeDir/htdocs/";
		my $fileSource =
		my $destFile = "$homeDir/htdocs/index.html";

		$rs = execute("$main::imscpConfig{'CMD_CP'} -nRT $sourceDir $dstDir", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		$rs = $self->buildConfFile($fileSource, { 'destination' => $destFile });
		return $rs if $rs;

		$rs = setRights(
			$dstDir,
			{
				'user' => $data->{'USER'},
				'group' => $apacheGroup,
				'filemode' => '0640',
				'dirmode' => '0750',
				'recursive' => 'yes'
			}
		);
		return $rs if $rs;
	}

	my $sourceDir = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/domain_disable_page";
	my $dstDir = "$homeDir/domain_disable_page";
	my $fileSource =
	my $destFile = "$homeDir/domain_disable_page/index.html";

	$rs = execute("$main::imscpConfig{'CMD_CP'} -nRT $sourceDir $dstDir", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = $self->buildConfFile($fileSource, { 'destination' => $destFile });
	return $rs if $rs;

	$rs = setRights(
		"$homeDir/cgi-bin", { 'user' => $data->{'USER'}, 'group' => $data->{'GROUP'}, 'recursive' => 'yes' }
	);
	return $rs if $rs;

	$rs = setRights(
		"$homeDir/domain_disable_page",
		{
			'user' => $rootUser,
			'group' => $apacheGroup,
			'filemode' => '0640',
			'dirmode' => '0710',
			'recursive' => 'yes'
		}
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdAddFiles');
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
	my ($stdout, $stderr);

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
	my $self = Servers::httpd::apache_php_fpm->getInstance();

	my $exitCode = $?;
	my $trfDir = "$self::apacheConfig{'APACHE_LOG_DIR'}/traff";
	my $rs = 0;

	if($self->{'startPhpFpm'} && $self->{'startPhpFpm'} eq 'yes'){
		$rs = $self->startPhpFpm();
	} elsif($self->{'restartPhpFpm'} && $self->{'restartPhpFpm'} eq 'yes') {
		$rs = $self->restartPhpFpm();
	}
	
	if($self->{'startApache'} && $self->{'startApache'} eq 'yes'){
		$rs = $self->startApache() if ! $rs;
	} elsif($self->{'restartApache'} && $self->{'restartApache'} eq 'yes') {
		$rs = $self->restartApache() if ! $rs;
	}

	$rs = iMSCP::Dir->new('dirname' => "$trfDir.old")->remove() if -d "$trfDir.old" && ! $rs;

	$? = $exitCode || $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
