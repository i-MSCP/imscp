#!/usr/bin/perl

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
# @author		Daniel Andreca <sci2tech@gmail.com>
# @author		Laurent Declercq <l;declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache_fcgi;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Config;
use iMSCP::Dir;
use iMSCP::Execute;
use File::Basename;
use iMSCP::File;
use iMSCP::Templator;
use iMSCP::Rights;
use POSIX;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger('beforeHttpdInit', $self, 'apache_fcgi');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/apache";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'tplDir'} = "$self->{'cfgDir'}/parts";

	my $conf = "$self->{'cfgDir'}/apache.data";
	tie %self::apacheConfig, 'iMSCP::Config', 'fileName' => $conf;

	$self->{'tplValues'}->{$_} = $self::apacheConfig{$_} for keys %self::apacheConfig;

	$self->{'hooksManager'}->trigger('afterHttpdInit', $self, 'apache_fcgi');

	$self;
}

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	require Servers::httpd::apache_fcgi::installer;

	Servers::httpd::apache_fcgi::installer->getInstance()->registerSetupHooks($hooksManager);
}

sub preinstall
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdPreinstall');
	return $rs if $rs;

	$rs = $self->stop();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('after HttpdPreinstall');
}

sub install
{
	my $self = shift;

	require Servers::httpd::apache_fcgi::installer;

	Servers::httpd::apache_fcgi::installer->getInstance()->install();
}

sub uninstall
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->stop();
	return $rs if $rs;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdUninstall', 'apache_fcgi');
	return $rs if $rs;

	require Servers::httpd::apache_fcgi::uninstaller;

	$rs = Servers::httpd::apache_fcgi::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$rs = $self->{'hooksManager'}->trigger('afterHttpdUninstall', 'apache_fcgi');
	return $rs if $rs;

	$self->start();
}

sub postinstall
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdPostInstall', 'apache_fcgi');
	return $rs if $rs;

	$self->{'start'} = 'yes';

	$self->{'hooksManager'}->trigger('afterHttpdPostInstall', 'apache_fcgi');
}

sub setGuiPermissions
{
	my $self = shift;

	require Servers::httpd::apache_fcgi::installer;

	Servers::httpd::apache_fcgi::installer->getInstance()->setGuiPermissions();
}

sub enableSite
{
	my $self = shift;
	my $sites = shift;
	my $rs = 0;
	my ($stdout, $stderr);

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdEnableSite', \$sites);
	return $rs if $rs;

	for(split(' ', $sites)){
		if(-f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_"){
			$rs = execute("a2ensite $_", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		} else {
			warning("Site $_ doesn't exists");
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdEnableSite', $sites);
}

sub disableSite
{
	my $self = shift;
	my $sites = shift;
	my $rs = 0;
	my ($stdout, $stderr);

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDisableSite', \$sites);
	return $rs if $rs;

	for(split(' ', $sites)){
		if(-f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_"){
			$rs = execute("a2dissite $_", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		} else {
			warning("Site $_ doesn't exists");
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdDisableSite', $sites);
}

sub enableMod
{
	my $self = shift;
	my $mod = shift;
	my $rs = 0;
	my ($stdout, $stderr);

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdEnableMod', \$mod);
	return $rs if $rs;

	$rs = execute("a2enmod $mod", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdEnableMod', $mod);
}

sub disableMod
{
	my $self = shift;
	my $mod = shift;
	my $rs = 0;
	my ($stdout, $stderr);

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDisableMod', \$mod);
	return $rs if $rs;

	$rs = execute("a2dismod $mod", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdDisableMod', $mod);
}

sub forceRestart
{
	my $self = shift;

	$self->{'forceRestart'} = 'yes';

	0;
}

sub start
{
	my $self = shift;
	my $rs = 0;
	my ($stdout, $stderr);

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdStart');
	return $rs if $rs;

	$rs = execute("$self->{'tplValues'}->{'CMD_HTTPD'} start", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	error("Error while stating") if $rs && ! $stderr;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdStart');
}

sub stop
{
	my $self = shift;
	my $rs = 0;
	my ($stdout, $stderr);

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdStop');
	return $rs if $rs;

	$rs = execute("$self->{'tplValues'}->{'CMD_HTTPD'} stop", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	error("Error while stoping") if $rs && ! $stderr;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdStop');
}

sub restart
{
	my $self = shift;
	my $rs = 0;
	my ($stdout, $stderr);

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdRestart');
	return $rs if $rs;

	$rs = execute(
		"$self->{'tplValues'}->{'CMD_HTTPD'} " . ($self->{'forceRestart'} ? 'restart' : 'reload'),
		\$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	error("Error while restarting") if $rs && ! $stderr;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdRestart');
}

sub buildConf($ $ $)
{
	my $self = shift;
	my $cfgTpl = shift;
	my $filename = shift || '';

	error('Empty config template...') unless $cfgTpl;
	return undef unless defined $cfgTpl;

	$self->{'tplValues'}->{$_} = $self->{'data'}->{$_} for keys %{$self->{'data'}};
	warning('Nothing to do...') unless keys %{$self->{'tplValues'}} > 0;

	$self->{'hooksManager'}->trigger('beforeHttpdBuildConf', \$cfgTpl, $filename);

	$cfgTpl = process($self->{'tplValues'}, $cfgTpl);
	return undef if ! $cfgTpl;

	$self->{'hooksManager'}->trigger('afterHttpdBuildConf', \$cfgTpl, $filename);

	$cfgTpl;
}

sub buildConfFile
{
	my $self = shift;
	my $file = shift;
	my $option = shift;
	my $rs = 0;

	$option = {} if ref $option ne 'HASH';

	my ($filename, $directories, $suffix) = fileparse($file);

	$file = "$self->{'cfgDir'}/$file" unless -d $directories && $directories ne './';

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
		'filename' => ($option->{'destination'} ? $option->{'destination'} : "$self->{'wrkDir'}/$filename$suffix")
	);

	$rs = $fileH->set($cfgTpl);
	return $rs if $rs;

	$rs = $fileH->save();
	return $rs if $rs;

	$rs = $fileH->mode($option->{'mode'} ? $option->{'mode'} : 0644);
	return $rs if $rs;

	$fileH->owner(
		$option->{'user'} ? $option->{'user'} : $main::imscpConfig{'ROOT_USER'},
		$option->{'group'} ? $option->{'group'} : $main::imscpConfig{'ROOT_GROUP'}
	);
}

sub installConfFile
{
	my $self = shift;
	my $file = shift;
	my $option = shift;
	my $rs = 0;

	$option = {} if ref $option ne 'HASH';

	my ($filename, $directories, $suffix) = fileparse($file);

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdInstallConfFile', "$filename$suffix");
	return $rs if $rs;

	$file = "$self->{'wrkDir'}/$file" unless -d $directories && $directories ne './';

	my $fileH = iMSCP::File->new('filename' => $file);

	$rs = $fileH->mode($option->{'mode'} ? $option->{'mode'} : 0644);
	return $rs if $rs;

	$rs = $fileH->owner(
		$option->{'user'} ? $option->{'user'} : $main::imscpConfig{'ROOT_USER'},
		$option->{'group'} ? $option->{'group'} : $main::imscpConfig{'ROOT_GROUP'}
	);
	return $rs if $rs;

	$rs = $fileH->copyFile(
		$option->{'destination'} ? $option->{'destination'} : "$self::apacheConfig{'APACHE_SITES_DIR'}/$filename$suffix"
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdInstallConfFile', "$filename$suffix");
}

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

sub getRunningUser
{
	$self::apacheConfig{'APACHE_USER'};
}

sub getRunningGroup
{
	$self::apacheConfig{'APACHE_GROUP'};
}

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

	$$cfgTpl = replaceBloc($bTag, $eTag, '', $$cfgTpl);

	$self->{'hooksManager'}->trigger('afterHttpdRemoveSection', $sectionName, $cfgTpl);
}

sub buildPHPini
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;
	my $php5Dir = "$self::apacheConfig{'PHP_STARTER_DIR'}/$data->{'DMN_NAME'}";

	$self->{'hooksManager'}->trigger('beforeHttpdBuildPhpIni') and return 1;

	# FCGID wrapper setup
	my $fileSource = "$main::imscpConfig{'CONF_DIR'}/fcgi/parts/php5-fcgid-starter.tpl";
	my $destFile = "$php5Dir/php5-fcgid-starter";

	$rs = $self->buildConfFile($fileSource, { 'destination' => $destFile });
	return $rs if $rs;

	$rs = setRights(
		$destFile,
		{ 'user' => $data->{'USER'}, 'group' => $data->{'GROUP'}, 'mode' => '0550' }
	);
	return $rs if $rs;

	# FASTCGI wrapper setup
	$fileSource = "$main::imscpConfig{'CONF_DIR'}/fcgi/parts/php5-fastcgi-starter.tpl";
	$destFile = "$php5Dir/php5-fastcgi-starter";

	$rs = $self->buildConfFile($fileSource, { 'destination' => $destFile });
	return $rs if $rs;

	$rs = setRights(
		$destFile,
		{ 'user' => $data->{'USER'}, 'group' => $data->{'GROUP'}, 'mode' => '0550' }
	);
	return $rs if $rs;

	$fileSource	= "$main::imscpConfig{'CONF_DIR'}/fcgi/parts/php5/php.ini";
	$destFile = "$php5Dir/php5/php.ini";

	$rs = $self->buildConfFile($fileSource, { 'destination' => $destFile });
	return $rs if $rs;

	$rs = setRights(
		$destFile,
		{ 'user' => $data->{'USER'}, 'group' => $data->{'GROUP'}, 'mode' => '0440' }
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdBuildPhpIni');
}

sub addUser
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdAddUser');
	return $rs if $rs;

	my $hDir = $data->{'HOME_DIR'};
	my $rootUser = $main::imscpConfig{'ROOT_USER'};
	my $rootGroup = $main::imscpConfig{'ROOT_GROUP'};
	my $apacheGroup = $self::apacheConfig{'APACHE_GROUP'};
	my $php5Dir = "$self::apacheConfig{'PHP_STARTER_DIR'}/$data->{'DMN_NAME'}";
	my ($stdout, $stderr);

	my $errmsg = {
		'USER' => 'You must supply user name!',
		'BWLIMIT' => 'You must supply a bandwidth limit!'
	};

	for(keys %{$errmsg}) {
		error("$errmsg->{$_}") unless exists $data->{$_};
		return 1 unless exists $data->{$_};
	}

	$self->{'data'} = $data;

	########################## START MOD CBAND SECTION ##############################

	$rs = iMSCP::File->new(
		'filename' => "$self->{'cfgDir'}/00_modcband.conf"
	)->copyFile(
		"$self->{'bkpDir'}/00_modcband.conf." . time
	) if (-f "$self->{'cfgDir'}/00_modcband.conf");
	return $rs if $rs;

	my $filename = (
		-f "$self->{'wrkDir'}/00_modcband.conf" ? "$self->{'wrkDir'}/00_modcband.conf" : "$self->{'cfgDir'}/00_modcband.conf"
	);

	my $file = iMSCP::File->new('filename' => $filename);
	my $content	= $file->get();

	unless(defined $content) {
		error("Unable to read $filename");
		return $rs if $rs;
	} else {
		my $bTag = "## SECTION {USER} BEGIN.\n";
		my $eTag = "## SECTION {USER} END.\n";
		my $bUTag = "## SECTION $data->{'USER'} BEGIN.\n";
		my $eUTag = "## SECTION $data->{'USER'} END.\n";

		my $entry = getBloc($bTag, $eTag, $content);
		chomp($entry);
		$entry =~ s/#//g;

		$content = replaceBloc($bUTag, $eUTag, '', $content);
		chomp($content);

		$self->{'data'}->{'BWLIMIT_DISABLED'} = ($data->{'BWLIMIT'} ? '' : '#');

		$entry = $self->buildConf($bTag.$entry.$eTag);
		$content = replaceBloc($bTag, $eTag, $entry, $content, 'preserve');

		$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/00_modcband.conf");
		$rs = $file->set($content);
		return $rs if $rs;

		$rs =  $file->save();
		return $rs if $rs;

		$rs = $self->installConfFile('00_modcband.conf');
		return $rs if $rs;

		$rs = $self->enableSite('00_modcband.conf');
		return $rs if $rs;

		unless( -f "$self::apacheConfig{'SCOREBOARDS_DIR'}/$data->{'USER'}") {
			$rs = iMSCP::File->new('filename' => "$self::apacheConfig{'SCOREBOARDS_DIR'}/$data->{'USER'}")->save();
			return $rs if $rs;
		}
	}

	########################### END MOD CBAND SECTION ###############################

	########################## START PHP.INI for user ###############################

	if($self::apacheConfig{'INI_LEVEL'} =~ /^per_user$/i){
		for (
			["$php5Dir", $data->{'USER'}, $data->{'GROUP'}, 0555],
			["$php5Dir/php5", $data->{'USER'}, $data->{'GROUP'}, 0550]
		) {
			$rs = iMSCP::Dir->new( dirname => $_->[0])->make(
				{ 'user' => $_->[1], 'group' => $_->[2], 'mode' => $_->[3] }
			);
			return $rs if $rs;
		}

		$rs = $self->buildPHPini($data);
		return $rs if $rs;
	}
	########################### END PHP.INI for user ################################

	##################### START COMMON FILES IN USER FOLDER #########################

	# ERROR DOCS
	$rs = execute("$main::imscpConfig{'CMD_CP'} -nRT $main::imscpConfig{'GUI_ROOT_DIR'}/public/errordocs $hDir/errors", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = setRights(
		"$hDir/errors",
		{ user => $data->{'USER'}, group => $apacheGroup, filemode => '0640', dirmode => '0750', recursive => 'yes' }
	);
	return $rs if $rs;

	for("$hDir/$self::apacheConfig{'HTACCESS_USERS_FILE_NAME'}", "$hDir/$self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'}") {
		my $fileH = iMSCP::File->new('filename' => $_);
		$rs = $fileH->save() unless -f $_;
		return $rs if $rs;

		$rs = $fileH->mode(0640);
		return $rs if $rs;
	}

	###################### END COMMON FILES IN USER FOLDER ##########################

	$self->{'restart'} = 'yes';

	$self->{'hooksManager'}->trigger('beforeHttpdAddUser');
}

sub delUser
{
	my $self = shift;
	my $data = shift;
	my $hDir = $data->{'HOME_DIR'};
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDelUser');
	return $rs if $rs;

	my $errmsg = { 'USER' => 'You must supply user name!'};

	for(keys %{$errmsg}){
		error("$errmsg->{$_}") unless exists $data->{$_};
		return 1 unless exists $data->{$_};
	}

	$self->{'data'} = $data;

	########################## START MOD CBAND SECTION ##############################

	$rs = iMSCP::File->new(
		'filename' => "$self->{'cfgDir'}/00_modcband.conf"
	)->copyFile(
		"$self->{'bkpDir'}/00_modcband.conf." . time
	) if (-f "$self->{'cfgDir'}/00_modcband.conf");
	return $rs if $rs;

	my $filename = (
		-f "$self->{'wrkDir'}/00_modcband.conf"
			? "$self->{'wrkDir'}/00_modcband.conf" : "$self->{'cfgDir'}/00_modcband.conf"
	);

	my $file = iMSCP::File->new('filename' => $filename);
	my $content	= $file->get();

	unless(defined $content) {
		error("Unable to read $filename");
		return $rs if $rs;
	} else {
		my $bUTag = "## SECTION $data->{'USER'} BEGIN.\n";
		my $eUTag = "## SECTION $data->{'USER'} END.\n";

		$content = replaceBloc($bUTag, $eUTag, '', $content);

		$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/00_modcband.conf");
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
	########################### END MOD CBAND SECTION ###############################

	for("$self::apacheConfig{'PHP_STARTER_DIR'}/$data->{'DMN_NAME'}", $hDir) {
		$rs = iMSCP::Dir->new(dirname => $_)->remove() if -d $_;
		return $rs if $rs;
	}

	$self->{'restart'} = 'yes';

	$self->{'hooksManager'}->trigger('afterHttpdDelUser');
}

sub addDmn
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$self->{'mode'} = 'dmn';

	my $errmsg = {
		'DMN_NAME' => 'You must supply domain name!',
		'DMN_IP' => 'You must supply ip for domain!'
	};

	for(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdAddDmn');
	return $rs if $rs;

	$self->{'data'} = $data;

	$rs = $self->addCfg($data);
	return $rs if $rs;

	$rs = $self->addFiles($data) if $data->{'FORWARD'} eq 'no';
	return $rs if $rs;

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	$self->{'hooksManager'}->trigger('afterHttpdAddDmn');
}

sub addCfg
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;
	my $certPath = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs";
	my $certFile = "$certPath/$data->{'DMN_NAME'}.pem";

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdAddCfg');
	return $rs if $rs;

	$self->{'data'} = $data;

	# Disable and backup Apache sites if any
	for("$data->{'DMN_NAME'}.conf", "$data->{'DMN_NAME'}_ssl.conf") {
		$rs = $self->disableSite($_) if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_";
		return $rs if $rs;

		$rs = iMSCP::File->new(
			'filename' => "$self::apacheConfig{'APACHE_SITES_DIR'}/$_"
		)->copyFile(
			"$self->{'bkpDir'}/$_." . time
		) if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_";
		return $rs if $rs;
	}

	# Remove previous Apache sites if any
	for(
		"$self::apacheConfig{'APACHE_SITES_DIR'}/$data->{'DMN_NAME'}.conf",
		"$self::apacheConfig{'APACHE_SITES_DIR'}/$data->{'DMN_NAME'}_ssl.conf",
		"$self->{'wrkDir'}/$data->{'DMN_NAME'}.conf",
		"$self->{'wrkDir'}/$data->{'DMN_NAME'}_ssl.conf"
	) {
		$rs = iMSCP::File->new('filename' => $_)->delFile() if -f $_;
		return $rs if $rs;
	}

	# Build Apache vhost files - Begin
	my %configs;
	$configs{"$data->{'DMN_NAME'}.conf"} = { 'redirect' => 'domain_redirect.tpl', 'normal' => 'domain.tpl' };

	if($data->{'have_cert'}) {
		$configs{"$data->{'DMN_NAME'}_ssl.conf"} = { redirect => 'domain_redirect_ssl.tpl', normal => 'domain_ssl.tpl' };
		$self->{'data'}->{'CERT'} = $certFile;
	}

	for(keys %configs) {
		# Schedule deletion of useless sections if needed
		if($data->{'FORWARD'} eq 'no') {

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

			if($self::apacheConfig{'PHP_FASTCGI'} eq 'fastcgi') {
				$rs = $self->{'hooksManager'}->register(
					'beforeHttpdBuildConfFile', sub { $self->removeSection('fcgid', @_) }
				);
				return $rs if $rs;
			} else {
				$rs = $self->{'hooksManager'}->register(
					'beforeHttpdBuildConfFile', sub { $self->removeSection('fastcgi', @_) }
				);
				return $rs if $rs;
			}

			$rs = $self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('php_fpm', @_) }
			);
			return $rs if $rs;

			$rs = $self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('itk', @_) }
			) if ($data->{'have_php'} eq 'yes');
			return $rs if $rs;
		}

		$self->{'data'}->{'FCGID_NAME'} = $data->{'ROOT_DMN_NAME'}
			if($self::apacheConfig{'INI_LEVEL'} =~ /^per_user$/i);

		$self->{'data'}->{'FCGID_NAME'} = $data->{'PARENT_DMN_NAME'}
			if($self::apacheConfig{'INI_LEVEL'} =~ /^per_domain$/i);

		$self->{'data'}->{'FCGID_NAME'} = $data->{'DMN_NAME'}
			if($self::apacheConfig{'INI_LEVEL'} =~ /^per_vhost$/i);

		$rs |= $self->buildConfFile(
			(
				$data->{'FORWARD'} eq 'no'
					? "$self->{'tplDir'}/" . $configs{$_}->{'normal'}
					: "$self->{'tplDir'}/" . $configs{$_}->{'redirect'}
			),
			{ 'destination' => "$self->{'wrkDir'}/$_" }
		);

		$rs = $self->installConfFile($_);
		return $rs if $rs;
	}

	# Build Apache sites - End

	# Build and install custom Apache configuration file
	$rs = $self->buildConfFile(
		"$self->{'tplDir'}/custom.conf.tpl",
		{ 'destination' => "$self::apacheConfig{'APACHE_CUSTOM_SITES_CONFIG_DIR'}/$data->{'DMN_NAME'}.conf" }
	) unless (-f "$self::apacheConfig{'APACHE_CUSTOM_SITES_CONFIG_DIR'}/$data->{'DMN_NAME'}.conf");
	return $rs if $rs;

	# Enable all Apache sites
	for(keys %configs) {
		$rs = $self->enableSite($_);
		return $rs if $rs;
	}


	$self->{'hooksManager'}->trigger('afterHttpdAddCfg');
}

sub dmnFolders
{
	my $self = shift;
	my $data = shift;
	my $hDir = $data->{'HOME_DIR'};
	my $rootUser = $main::imscpConfig{'ROOT_USER'};
	my $rootGroup = $main::imscpConfig{'ROOT_GROUP'};
	my $apacheGroup = $self::apacheConfig{'APACHE_GROUP'};
	my $newHtdocs = -d "$hDir/htdocs";
	my $php5Dir	= "$self::apacheConfig{'PHP_STARTER_DIR'}/$data->{'DMN_NAME'}";
	my ($stdout, $stderr);

	my @folders = (
		["$hDir", $data->{'USER'}, $apacheGroup, 0710],
		["$hDir/htdocs", $data->{'USER'}, $apacheGroup, 0750],
		["$hDir/cgi-bin", $data->{'USER'}, $data->{'GROUP'}, 0751],
		["$hDir/phptmp", $data->{'USER'}, $data->{'GROUP'}, 0770]
	);

	$self->{'hooksManager'}->trigger('beforeHttpdDmnFolders', \@folders);

	push(@folders, ["$hDir/errors", $data->{'USER'}, $apacheGroup, 0710]) if $self->{'mode'} eq 'dmn';

	push(@folders, ["$php5Dir", $data->{'USER'}, $data->{'GROUP'}, 0550]) if $self->{'mode'} eq 'dmn' &&
		$self::apacheConfig{'INI_LEVEL'} =~ /^per_domain$/i || $self::apacheConfig{'INI_LEVEL'} =~ /^per_vhost$/i;

	push(@folders, ["$php5Dir/php5", $data->{'USER'}, $data->{'GROUP'}, 0550]) if $self->{'mode'} eq 'dmn' &&
		$self::apacheConfig{'INI_LEVEL'} =~ /^per_domain$/i || $self::apacheConfig{'INI_LEVEL'} =~ /^per_vhost$/i;

	$self->{'hooksManager'}->trigger('afterHttpdDmnFolders', \@folders);

	@folders;
}

sub addFiles
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;
	my $hDir = $data->{'HOME_DIR'};
	my $rootUser = $main::imscpConfig{'ROOT_USER'};
	my $rootGroup = $main::imscpConfig{'ROOT_GROUP'};
	my $apacheGroup	= $self::apacheConfig{'APACHE_GROUP'};
	my $newHtdocs = -d "$hDir/htdocs";
	my ($stdout, $stderr);

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdAddFiles');
	return $rs if $rs;

	for ($self->dmnFolders($data)){
		$rs = iMSCP::Dir->new( dirname => $_->[0])->make(
			{ 'user' => $_->[1], 'group' => $_->[2], 'mode' => $_->[3] }
		);
		return $rs if $rs;
	}

	unless ($newHtdocs) {
		my $sourceDir = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/domain_default_page";
		my $dstDir = "$hDir/htdocs/";
		my $fileSource =
		my $destFile = "$hDir/htdocs/index.html";

		$rs = execute("$main::imscpConfig{'CMD_CP'} -nRT $sourceDir $dstDir", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		$rs = $self->buildConfFile($fileSource, { 'destination' => $destFile});
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
	my $dstDir = "$hDir/domain_disable_page";
	my $fileSource =
	my $destFile = "$hDir/domain_disable_page/index.html";

	$rs = execute("$main::imscpConfig{'CMD_CP'} -nRT $sourceDir $dstDir", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = $self->buildConfFile($fileSource, { 'destination' => $destFile });
	return $rs if $rs;

	$rs = setRights(
		"$hDir/cgi-bin",
		{ user => $data->{'USER'}, group => $data->{'GROUP'}, recursive => 'yes' }
	);
	return $rs if $rs;

	$rs = setRights(
		"$hDir/domain_disable_page",
		{ user => $rootUser, group => $apacheGroup, filemode => '0640', dirmode => '0710', recursive => 'yes' }
	);
	return $rs if $rs;

	$rs = $self->buildPHPini($data) if(
			$self::apacheConfig{'INI_LEVEL'} =~ /^per_domain$/i &&
			$self->{'mode'} eq "dmn" ||
			$self::apacheConfig{'INI_LEVEL'} =~ /^per_vhost$/i
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdAddFiles');
}

sub delDmn
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	error('You must supply domain name!') unless $data->{'DMN_NAME'};
	return 1 unless $data->{'DMN_NAME'};

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDelDmn');
	return $rs if $rs;

	for("$data->{'DMN_NAME'}.conf", "$data->{'DMN_NAME'}_ssl.conf") {
		$rs = $self->disableSite($_) if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_";
		return $rs if $rs;
	}

	for(
		"$self::apacheConfig{'APACHE_SITES_DIR'}/$data->{'DMN_NAME'}.conf",
		"$self::apacheConfig{'APACHE_SITES_DIR'}/$data->{'DMN_NAME'}_ssl.conf",
		"$self::apacheConfig{'APACHE_CUSTOM_SITES_CONFIG_DIR'}/$data->{'DMN_NAME'}.conf",
		"$self->{'wrkDir'}/$data->{'DMN_NAME'}.conf",
		"$self->{'wrkDir'}/$data->{'DMN_NAME'}_ssl.conf",
	) {
		$rs = iMSCP::File->new('filename' => $_)->delFile() if -f $_;
		return $rs if $rs;
	}

	my $hDir = $data->{'HOME_DIR'};

	for("$self::apacheConfig{'PHP_STARTER_DIR'}/$data->{'DMN_NAME'}", "$hDir") {
		$rs = iMSCP::Dir->new('dirname' => $_)->remove() if -d $_;
		return $rs if $rs;
	}

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	$self->{'hooksManager'}->trigger('afterHttpdDelDmn');
}

sub disableDmn
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my $errmsg = {
		'DMN_NAME' => 'You must supply domain name!',
		'DMN_IP' => 'You must supply ip for domain!'
	};

	for(keys %{$errmsg}) {
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDisableDmn');
	return $rs if $rs;

	$self->{'data'} = $data;

	iMSCP::File->new(
		'filename' => "$self->{'cfgDir'}/$data->{'DMN_NAME'}.conf"
	)->copyFile(
		"$self->{'bkpDir'}/$data->{'DMN_NAME'}.conf". time
	) if (-f "$self->{'cfgDir'}/$data->{'DMN_NAME'}.conf");
	return $rs if $rs;

	$rs = $self->buildConfFile(
		"$self->{'tplDir'}/domain_disabled.tpl",
		{ 'destination' => "$self->{'wrkDir'}/$data->{'DMN_NAME'}.conf" }
	);
	return $rs if $rs;

	$rs = $self->installConfFile("$data->{'DMN_NAME'}.conf");
	return $rs if $rs;

	$rs = $self->enableSite("$data->{'DMN_NAME'}.conf");
	return $rs if $rs;

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	$self->{'hooksManager'}->trigger('afterHttpdDisableDmn');
}

sub addSub
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;
	$self->{'mode'} = 'sub';

	my $errmsg = {
		'DMN_NAME' => 'You must supply subdomain name!',
		'DMN_IP' => 'You must supply ip for subdomain!'
	};

	for(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	$self->{'hooksManager'}->trigger('beforeHttpdAddSub') and return 1;

	$self->{'data'} = $data;

	$rs = $self->addCfg($data);
	return $rs if $rs;

	$rs = $self->addFiles($data) if $data->{'FORWARD'} eq 'no';
	return $rs if $rs;

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	$self->{'hooksManager'}->trigger('afterHttpdAddSub');
}

sub delSub
{
	my $self = shift;

	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDelSub');

	$rs = $self->delDmn(@_);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdDelSub');
}

sub disableSub
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDisableSub');
	return $rs if $rs;

	$rs = $self->disableDmn(@_);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('beforeHttpdDisableSub');
}

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
	$fileContent =~ s/^$data->{'HTUSER_NAME'}:[^\n]*\n//img;
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
	my $fileContent	= $fileH->get() if -f $filePath;
	$fileContent = '' if ! defined $fileContent;
	$fileContent =~ s/^$data->{'HTUSER_NAME'}:[^\n]*\n//img;

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
	$fileContent =~ s/^$data->{'HTGROUP_NAME'}:[^\n]*\n//img;
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
	my $fileContent = $fileH->get() if -f $filePath;
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

sub addHtaccess
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$self->{'hooksManager'}->trigger('beforeHttpdAddHtaccess') and return 1;

	my $fileUser = "$data->{'HOME_PATH'}/$self::apacheConfig{'HTACCESS_USERS_FILE_NAME'}";
	my $fileGroup = "$data->'{HOME_PATH'}/$self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'}";
	my $filePath = "$data->{'AUTH_PATH'}/.htaccess";
	my $fileH = iMSCP::File->new('filename' => $filePath);
	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if ! defined $fileContent;

	my $bTag = "\t\t### START i-MSCP PROTECTION ###\n";
	my $eTag = "\t\t### END i-MSCP PROTECTION ###\n";
	my $tag = "\t\tAuthType $data->{'AUTH_TYPE'}\n\t\tAuthName \"$data->{'AUTH_NAME'}\"\n\t\tAuthUserFile $fileUser\n";

	if($data->{'HTUSERS'} eq '') {
		$tag .= "\t\tAuthGroupFile $fileGroup\n\t\tRequire group $data->{'HTGROUPS'}\n";
	} else {
		$tag .=	"\t\tRequire user $data->{'HTUSERS'}\n";
	}

	$fileContent = replaceBloc($bTag, $eTag, '', $fileContent);
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

sub delHtaccess
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$self->{'hooksManager'}->trigger('beforeHttpdDelHtaccess') and return 1;

	my $fileUser = "$data->{'HOME_PATH'}/$self::apacheConfig{'HTACCESS_USERS_FILE_NAME'}";
	my $fileGroup = "$data->'{HOME_PATH'}/$self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'}";
	my $filePath = "$data->{'AUTH_PATH'}/.htaccess";
	my $fileH = iMSCP::File->new('filename' => $filePath);
	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if ! defined $fileContent;

	my $bTag = "\t\t### START i-MSCP PROTECTION ###\n";
	my $eTag = "\t\t### END i-MSCP PROTECTION ###\n";

	$fileContent = replaceBloc($bTag, $eTag, '', $fileContent);

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

sub addIps
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdAddIps');
	return $rs if $rs;

	unless ($data->{'IPS'} && ref $data->{'IPS'} eq 'ARRAY') {
		error("You must provide ip list");
		return 1;
	}

	$rs = iMSCP::File->new(
		'filename' => "$self->{'cfgDir'}/00_nameserver.conf"
	)->copyFile(
		"$self->{'bkpDir'}/00_nameserver.conf.". time
	) if (-f "$self->{'cfgDir'}/00_nameserver.conf");
	return $rs if $rs;

	my $filename = (
		-f "$self->{'wrkDir'}/00_nameserver.conf"
		? "$self->{'wrkDir'}/00_nameserver.conf" : "$self->{'cfgDir'}/00_nameserver.conf"
	);

	my $file = iMSCP::File->new('filename' => $filename);

	my $content = $file->get();
	return 1 if ! defined $content;

	$content =~ s/NameVirtualHost[^\n]+\n//gi;

	$content.= "NameVirtualHost $_:443\n" for @{$data->{'SSLIPS'}};
	$content.= "NameVirtualHost $_:80\n" for @{$data->{'IPS'}};

	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/00_nameserver.conf");

	$rs = $file->set($content);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $self->installConfFile('00_nameserver.conf');
	return $rs if $rs;

	$rs = $self->enableSite('00_nameserver.conf');
	return $rs if $rs;

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	$self->{'hooksManager'}->trigger('afterHttpdAddIps');
}

sub getTraffic
{
	my $self = shift;
	my $who	 = shift;
	my $traff = 0;
	my $trfDir = "$self::apacheConfig{'APACHE_LOG_DIR'}/traff";
	my ($rv, $rs, $stdout, $stderr);

	$self->{'hooksManager'}->trigger('beforeHttpdGetTraffic');

	unless($self->{'logDb'}) {
		$self->{'logDb'} = 1;

		$rs = execute("$main::imscpConfig{'CMD_PS'} -o pid,args -C 'imscp-apache-logger'", \$stdout, \$stderr);
		error($stderr) if $stderr && $rs;

		my $rv = iMSCP::Dir->new(dirname => $trfDir)->moveDir("$trfDir.old") if -d $trfDir;
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

		if($content){
			my @lines = split("\n", $content);
			$traff += $_ foreach @lines;
		} else {
			error("Cannot read $trfDir.old/$who-traf.log");
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdGetTraffic');

	$traff;
}

sub delOldLogs
{
	my $self = shift;
	my $rs = 0;
	my $logDir = $self::apacheConfig{'APACHE_LOG_DIR'};
	my $bLogDir = $self::apacheConfig{'APACHE_BACKUP_LOG_DIR'};
	my $uLogDir = $self::apacheConfig{'APACHE_USERS_LOG_DIR'};
	my ($stdout, $stderr);

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDelOldLogs');
	return $rs if $rs;

	for ($logDir, $bLogDir, $uLogDir) {
		my $cmd = "nice -n 19 find $_ -maxdepth 1 -type f -name '*.log*' -mtime +365 -exec rm -v {} \\;";
		$rs = execute($cmd, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		error("Error while executing $cmd.\nReturned value is $rs") if $rs && ! $stderr;
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterHttpdDelOldLogs');

}

sub delTmp
{
	my $self = shift;
	my $rs = 0;
	my ($stdout, $stderr);

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDelTmp');
	return $rs if $rs;

	# panel sessions gc
	if(-d "$self::apacheConfig{'PHP_STARTER_DIR'}/master") {
		unless (-f "$self::apacheConfig{'PHP_STARTER_DIR'}/master/php5/php.ini") {
			error("$self::apacheConfig{'PHP_STARTER_DIR'}/master/php5/php.ini doesn't exists");
			return $rs if $rs;
		} else {
			my $hFile = iMSCP::File->new('filename' => "$self::apacheConfig{'PHP_STARTER_DIR'}/master/php5/php.ini");
			my $file = $hFile->get();
			unless (defined $file) {
				error("Unable to read $self::apacheConfig{'PHP_STARTER_DIR'}/master/php5/php.ini!");
				return $rs if $rs;;
			} else {
				my $max = 0;
				$file =~ m/^\s*session.gc_maxlifetime\s*=\s*([0-9]+).*$/mgi;
				$max = floor($1/60) if $1 && $max < floor($1/60);
				$max = 24 unless $max;
				my $cmd = "[ -d /var/www/imscp/gui/data/sessions/ ] && find /var/www/imscp/gui/data/sessions/ -type f -cmin +$max -delete";
				$rs = execute($cmd, \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				error("Error while executing $cmd.\nReturned value is $rs") if $rs && ! $stderr;
				return $rs if $rs;
			}
		}
	}

	# Customers sessions gc
	my $hDMN = iMSCP::Dir->new('dirname' => "$main::imscpConfig{'USER_HOME_DIR'}");

	my @domains	= $hDMN->getDirs();

	for (@domains){
		my $dmn = $_;

		if(-d "$self::apacheConfig{'PHP_STARTER_DIR'}/$_") {
			my $hPHPINI	= iMSCP::Dir->new('dirname' => "$self::apacheConfig{'PHP_STARTER_DIR'}/$dmn");
			my @phpInis = $hPHPINI->getDirs();
			my $max = 0;

			for(@phpInis) {
				unless (-f "$self::apacheConfig{'PHP_STARTER_DIR'}/$dmn/$_/php.ini") {
					error("File $self::apacheConfig{'PHP_STARTER_DIR'}/$dmn/$_/php.ini doesn't exists");
					return $rs if $rs;
				}

				my $hFile = iMSCP::File->new('filename' => "$self::apacheConfig{'PHP_STARTER_DIR'}/$dmn/$_/php.ini");
				my $file = $hFile->get();

				unless (defined $file) {
					error("Cannot read $self::apacheConfig{'PHP_STARTER_DIR'}/$dmn/$_/php.ini");
					return $rs if $rs;
				}

				$file =~ m/^\s*session.gc_maxlifetime\s*=\s*([0-9]+).*$/mgi;
				$max = floor($1/60) if $1 && $max < floor($1/60);
			}

			$max = 24 unless $max;
			my $cmd = "nice -n 19 find $main::imscpConfig{'USER_HOME_DIR'}/$dmn -type f -path '*/phptmp/sess_*' -cmin +$max -exec rm -v {} \\;";
			$rs = execute($cmd, \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			error("Error while executing $cmd.\nReturned value is $rs") if $rs && ! $stderr;
			return $rs if $rs;
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdDelTmp');
}

END
{
	my $endCode = $?;
	my $self = Servers::httpd::apache_fcgi->getInstance();
	my $rs = 0;
	my $trfDir	= "$self::apacheConfig{'APACHE_LOG_DIR'}/traff";

	if($self->{'start'} && $self->{'start'} eq 'yes'){
		$rs = $self->start();
	} elsif($self->{'restart'} && $self->{'restart'} eq 'yes') {
		$rs = $self->restart();
	}

	$rs = iMSCP::Dir->new('dirname' => "$trfDir.old")->remove() if -d "$trfDir.old" && ! $rs;

	$? = $endCode || $rs;
}

1;
