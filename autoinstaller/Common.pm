#!/usr/bin/perl

=head1 NAME

 autoinstaller::Common - Common functions for the i-MSCP autoinstaller

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2013 by internet Multi Server Control Panel
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
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package autoinstaller::Common;

use strict;
use warnings;
use Cwd;
use iMSCP::Debug;
use iMSCP::Dialog;
use iMSCP::Config;
use iMSCP::LsbRelease;
use iMSCP::HooksManager;
use iMSCP::Execute;
use iMSCP::Dir;
use iMSCP::File;

use parent 'Exporter';
our @EXPORT = qw(
	loadConfig installPreRequiredPackages checkDistribution preBuild uninstallPackages installPackages testRequirements
	processConfFile processSpecificConfFile buildImscpDaemon installEngine installGui installDistMaintainerScripts
	postBuild doImscpBackup saveGuiPersistentData installTmp removeTmp checkCommandAvailability
);

=head1 DESCRIPTION

 Common functions for autoinstaller.

=head1 EXPORTED FUNCTIONS

=over 4

=item loadConfig()

 Load main i-MSCP configuration.

 Load both the new imscp.conf file (upstread conffile) and the current imscp.conf file (old conffile) and merge them
together in the %main::imscpConfig variable. The old imscp.conf file is tied to the %main::imscpOldConfig variable
and set as readonly.

 Return int - 0

=cut

sub loadConfig
{
	# Load news imscp.conf conffile from i-MSCP upstream source (tie it to the %main::imscpNewConfig variable)
	tie
		%main::imscpNewConfig,
		'iMSCP::Config',
		'fileName' => "$FindBin::Bin/configs/" . lc(iMSCP::LsbRelease->getInstance()->getId(1)) . '/imscp.conf';

	# Load current i-MSCP conffile as readonly if it exists (tie it to the %main::imscpOldConfig variable)
	if (-f "$main::imscpNewConfig{'CONF_DIR'}/imscp.conf") {
		tie
			%main::imscpOldConfig,
			'iMSCP::Config',
			fileName => "$main::imscpNewConfig{'CONF_DIR'}/imscp.conf",
			readonly => 1;
	} else { # No conffile found, assumption is made that it's a new install
		%main::imscpOldConfig = %main::imscpNewConfig;
	}

	# Merge current config with the new but do not write anything yet (see postBuild step).
	%main::imscpConfig = (%main::imscpNewConfig, %main::imscpOldConfig);

	# Update needed variables with newest values
	$main::imscpConfig{'BuildDate'} = $main::imscpNewConfig{'BuildDate'} if exists $main::imscpNewConfig{'BuildDate'};
	$main::imscpConfig{'Version'} = $main::imscpNewConfig{'Version'} if exists $main::imscpNewConfig{'Version'};
	$main::imscpConfig{'CodeName'} = $main::imscpNewConfig{'CodeName'} if exists $main::imscpNewConfig{'CodeName'};
	$main::imscpConfig{'DistName'} = $main::imscpNewConfig{'DistName'} if exists $main::imscpNewConfig{'DistName'};

	# No longer needed
	untie %main::imscpNewConfig;
	undef %main::imscpNewConfig;

	0;
}

=item installPreRequiredPackages

 Trigger pre-required package installation from distro autoinstaller adapter.

 Return int - 0 on success, other otherwise

=cut

sub installPreRequiredPackages
{
	_getDistroAdapter()->installPreRequiredPackages();
}

=item checkDistribution()

 Check distribution.

 Return int - 0 on success, 1 on failure

=cut

sub checkDistribution()
{
	my $self = shift;

	iMSCP::Dialog->factory()->infobox("\nDetecting target distribution...");

	my $lsbRelease = iMSCP::LsbRelease->getInstance();
	my $distribution = $lsbRelease->getId(1);
	my $codename = lc($lsbRelease->getCodename(1));
	my $release = $lsbRelease->getRelease(1);
	my $description = $lsbRelease->getDescription(1);
	my $packagesFile = "$FindBin::Bin/docs/$distribution/packages-$codename.xml";

	if($distribution ne "n/a" && (lc($distribution) eq 'debian' || lc($distribution) eq 'ubuntu') && $codename ne "n/a") {
		if(! -f $packagesFile) {
			iMSCP::Dialog->factory()->msgbox(
"
\\Z1$distribution $release ($codename) not supported yet\\Zn

We are sorry but the version of your distribution is not supported yet.

You can try to provide your own packages file by putting it into the
\\Z4docs/$distribution\\Zn directory and try again, or ask the i-MSCP team to add it for you.

Thanks for using i-MSCP.
"
			);

			return 1;
		}

		my $rs = iMSCP::Dialog->factory()->yesno("\n$distribution $release ($codename) has been detected. Is this ok?");

		iMSCP::Dialog->factory()->msgbox(
"
\\Z1Distribution not supported\\Zn

We are sorry but the installer has failed to detect your distribution, or
process has been aborted by user.

Only \\ZuDebian-like\\Zn operating systems are supported.

Thanks for using i-MSCP.
"
		) if $rs;

		return 1 if $rs;
	} else {
		iMSCP::Dialog->factory()->msgbox(
"
\\Z1Distribution not supported\\Zn

We are sorry but your distribution is not supported yet.

Only \\ZDebian-like\\Zn operating systems are supported.

Thanks for using i-MSCP.
"
		);

		return 1;
	}

	0;
}

=item preBuild()

 Trigger pre-build tasks from distro autoinstaller adapter.

 Return int - 0 on success, other on failure

=cut

sub preBuild
{
	my $rs = 0;

	iMSCP::HooksManager->getInstance()->trigger('beforePreBuild');
	return $rs if $rs;

	$rs = _getDistroAdapter()->preBuild();
	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterPreBuild');
}

=item uninstallPackages()

 Trigger packages uninstallation from distro autoinstaller adapter.

 Return int - 0 on success, other on failure

=cut

sub uninstallPackages
{
	my $rs = 0;

	iMSCP::HooksManager->getInstance()->trigger('beboreUninstallPackages');
	return $rs if $rs;

	$rs = _getDistroAdapter()->uninstallPackages();
	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterUninstallPackages');

}

=item installPackages()

 Trigger packages installation from distro autoinstaller adapter.

 Return int - 0 on success, other on failure

=cut

sub installPackages
{
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beboreInstallPackages');
	return $rs if $rs;

	$rs = _getDistroAdapter()->installPackages();
	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterInstallPackages');
}

=item testRequirements()

 Test for i-MSCP requirements.

 Return int 0 - On error, a fatal error is raised

=cut

sub testRequirements
{
	iMSCP::Requirements->new()->test('all');
}

=item processConfFile()

 Process all xml nodes from the given install.xml file or distribution variables.xml file.

 Return int - 0 on success, other on failure ; A fatal error is raised in case a variable cannot be exported
 TODO The chown nodes are not processed...

=cut

sub processConfFile
{
	my $conffile = shift;

	$conffile = "$FindBin::Bin/autoinstaller/Adapter/" . iMSCP::LsbRelease->getInstance()->getId(1) . '/variables.xml'
		unless $conffile;

	unless(-f $conffile) {
		error("$conffile doesn't exists");
		return 1;
	}

	# Loading XML::Simple package
	eval "use XML::Simple; 1";
	fatal('Unable to load the XML::Simple perl module') if $@;

	# Creating XML object
	my $xml = XML::Simple->new('ForceArray' => 1, 'ForceContent' => 1);

	# Reading XML file
	my $data = eval { $xml->XMLin($conffile, 'VarAttr' => 'export') };

	if ($@) {
		error($@);
		return 1;
	}

	my $rs = 0;

	# Process xml 'folders' nodes if any
	for(@{$data->{'folders'}}) {
		$_->{'content'} = _expandVars($_->{'content'}) if exists $_->{'content'};
		$main::{$_->{'export'}} = $_->{'content'} if $_->{'export'};
		$rs = _processFolder($_) if exists $_->{'content'};
		return $rs if $rs;
	}

	# Process xml 'copy_config' nodes if any
	for(@{$data->{'copy_config'}}) {
		$_->{'content'} = _expandVars($_->{'content'}) if exists $_->{'content'};
		$rs = _copyConfig($_) if exists $_->{'content'};
		return $rs if $rs;
	}

	# Process xml 'copy' nodes if any
	for(@{$data->{'copy'}}) {
		$_->{'content'} = _expandVars($_->{'content'}) if exists $_->{'content'};
		$rs = _copy($_) if exists $_->{'content'};
		return $rs if $rs;
	}

	# Process xml 'create_file' nodes if any
	for(@{$data->{'create_file'}}) {
		$_->{'content'} = _expandVars($_->{'content'}) if exists $_->{'content'};
		$rs = _createFile($_) if exists $_->{'content'};
		return $rs if $rs;
	}

	# Process xml 'chmod_file' nodes if any
	for(@{$data->{'chmod_file'}}) {
		$_->{'content'} = _expandVars($_->{'content'}) if exists $_->{'content'};
		$rs = _chmodFile($_) if $_->{'content'};
		return $rs if $rs;
	}

	# Process xml 'chmod_file' nodes if any
	for(@{$data->{'chown_file'}}) {
		$_->{'content'} = _expandVars($_->{'content'}) if exists $_->{'content'};
		$rs = _chownFile($_) if exists $_->{'content'};
		return $rs if $rs;
	}

	0;
}

=item processSpecificConfFile()

 Process distribution specific install.xml configuration files.

 Return int - 0 on success, other on failure

=cut

sub processSpecificConfFile
{
	my $specificPath = "$FindBin::Bin/configs/" . lc(iMSCP::LsbRelease->getInstance()->getId(1));
	my $commonPath = "$FindBin::Bin/configs/debian";
	my $path = -d $specificPath ? $specificPath : $commonPath;

	unless(chdir($path)) {
		error("Unable to change path to $path: $!");
		return 1;
	}

	my $file = -f "$specificPath/install.xml" ? "$specificPath/install.xml" : "$commonPath/install.xml";

	my $rs = processConfFile($file);
	return $rs if $rs;

	# eg. /configs/debian
	my $dir = iMSCP::Dir->new('dirname' => $commonPath);
	my @configs = $dir->getDirs();

	for(@configs) {
		$path = -d "$specificPath/$_" ? "$specificPath/$_" : "$commonPath/$_";

		unless(chdir($path)) {
			error("Cannot change path to $path: $!");
			return 1;
		}

		$file = -f "$specificPath/$_/install.xml" ? "$specificPath/$_/install.xml" : "$commonPath/$_/install.xml";

		$rs = processConfFile($file);
		return $rs if $rs;
	}

	0;
}

=item

 Build i-MSCP daemon

 Return int - 0 on success, other on failure.
=cut

# Build the i-MSCP daemon by running make.
#
# @return 0 on success
sub buildImscpDaemon
{
	unless(chdir "$FindBin::Bin/daemon") {
		error("Unable to change path to $FindBin::Bin/daemon");
		return 1;
	}

	my ($rs, $stdout, $stderr);

	$rs = execute("$main::imscpConfig{'CMD_MAKE'} clean imscp_daemon", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to build i-MSCP daemon') if $rs;
	return $rs if $rs;


	my $dir = iMSCP::Dir->new('dirname' => "$main::{'SYSTEM_ROOT'}/daemon");
	$rs = $dir->make();
	return $rs if $rs;

	my $file = iMSCP::File->new('filename' => 'imscp_daemon');
	$rs = $file->copyFile("$main::{'SYSTEM_ROOT'}/daemon");
	return $rs if $rs;

	$rs = execute("$main::imscpConfig{'CMD_MAKE'} clean", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Cannot clean i-MSCP daemon artifacts') if $rs;
	return $rs if $rs;

	0;
}

=item installEngine()

 Install engine files in build directory.

 Return int - 0 on success, other on failure

=cut

sub installEngine
{
	unless(chdir "$FindBin::Bin/engine") {
		error("Cannot change path to $FindBin::Bin/engine");
		return 1;
	}

	my $rs = processConfFile("$FindBin::Bin/engine/install.xml");
	return $rs if $rs;

	my $dir = iMSCP::Dir->new('dirname' => "$FindBin::Bin/engine");

	my @configs = $dir->getDirs();

	for(@configs) {
		if (-f "$FindBin::Bin/engine/$_/install.xml") {
			unless(chdir "$FindBin::Bin/engine/$_") {
				error("Unable to change path to $FindBin::Bin/engine/$_");
				return 1;
			}

			$rs = processConfFile("$FindBin::Bin/engine/$_/install.xml") ;
			return $rs if $rs;
		}
	}

	0;
}

=item installGui()

 Install GUI files in build directory.

 Return int - 0 on success, other on failure

=cut
sub installGui
{
	my $rs = 0;
	my ($stdout, $stderr);

	$rs = execute("$main::imscpConfig{'CMD_CP'} -R $FindBin::Bin/gui $main::{'SYSTEM_ROOT'}", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item installDistMaintainerScripts()

 Install distribution maintainer scripts in build folder.

 Some distribution can require pre and post installation tasks managed by maintainers scripts (preinst.DISTNAME or
postinst.DISTNAME) written in Shell, PHP or Perl. If a script is found for the current distribution, it will be intalled
in the setup directory with the distribution maintainer helper library (for shell scripts).

 Return int - 0

=cut

sub installDistMaintainerScripts
{
	my $rs = 0;
	my $distribution = lc(iMSCP::LsbRelease->getInstance()->getId(1));

	for("$FindBin::Bin/maintscripts/preinst.$distribution", "$FindBin::Bin/maintscripts/postinst.$distribution") {
		next if ! -f $_;
		my $file = iMSCP::File->new('filename' => $_);
		$rs = $file->mode(0750);
		return $rs if $rs;
		$rs = $file->owner(0, 0);
		return $rs if $rs;
		$rs = $file->copyFile("$main::{'SYSTEM_ROOT'}/engine/setup/");
		return $rs if $rs;
	}

	if(-f "$FindBin::Bin/maintscripts/preinst.$distribution" || -f "$FindBin::Bin/maintscripts/postinst.$distribution") {
		my $file = iMSCP::File->new('filename' => "$FindBin::Bin/maintscripts/maintainer-helper.sh");
		$rs = $file->mode(0750);
		return $rs if $rs;
		$rs = $file->owner(0, 0);
		return $rs if $rs;
		$rs = $file->copyFile("$main::{'SYSTEM_ROOT'}/engine/setup/");
		return $rs if $rs;
	}

	0;
}

=item postBuild()

 Process post-build tasks.

 Trigger post-build tasks from distro autoinstaller adapter and save i-MSCP main configuration file.

 Return int - 0 on success, other on failure

=cut

sub postBuild
{
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforePostBuild');
	return $rs if $rs;

	$rs = _getDistroAdapter()->postBuild();
	return $rs if $rs;

	# Backup current config if any
	if(-f "$main::imscpConfig{'CONF_DIR'}/imscp.conf") {
		my $file = iMSCP::File->new('filename' => "$main::imscpConfig{'CONF_DIR'}/imscp.conf");
		my $cfg = $file->get() or return 1;

		$file = iMSCP::File->new('filename' => "$main::imscpConfig{'CONF_DIR'}/imscp.old.conf");
		$rs = $file->set($cfg);
		return $rs if $rs;
		$rs = $file->save;
		return $rs if $rs;
		$rs = $file->mode(0660);
		return $rs if $rs;
		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'MASTER_GROUP'});
		return $rs if $rs;
	}

	# Write new config file into build directory

	my %imscpConf = %main::imscpConfig;
	tie %main::imscpConfig, 'iMSCP::Config', 'fileName' => "$main::{'SYSTEM_CONF'}/imscp.conf";

	%main::imscpConfig = (%main::imscpConfig, %imscpConf);

	iMSCP::HooksManager->getInstance()->trigger('afterPostBuild');
}

=item doImscpBackup

 Backup current i-MSCP installation (database and conffiles) if any.

 Return int - 0 on success, other on failure

=cut

sub doImscpBackup
{
	my $rs = 0;
	my ($stdout, $stderr);

	if(-x "$main::imscpConfig{'ROOT_DIR'}/engine/backup/imscp-backup-imscp") {
		$rs = execute("$main::imscpConfig{'ROOT_DIR'}/engine/backup/imscp-backup-imscp noreport", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		warning($stderr) if $stderr && $rs;
		error('Unable to backup previous i-MSCP installation') if $rs;

		$rs = iMSCP::Dialog->factory()->yesno(
"
\\Z1Unable to create backups\\Zn

This is not a fatal error, setup may continue, but you will not have a backup (unless you have previously builded one).

Do you want to continue?
"
		) if $rs;
	}

	$rs;
}

=item saveGuiPersistentData()

 Save GUI persistent data in build directory.

 Return int - 0 on success, other on failure

=cut

sub saveGuiPersistentData
{
	my $rs = 0;
	my ($stdout, $stderr);
	my $destdir = $main::{'INST_PREF'};

	# i-MSCP versions >= 1.0.4
	if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/data") {
		# Save i-MSCP GUI data
		$rs = execute(
			"$main::imscpConfig{'CMD_CP'} -TRf $main::imscpConfig{'ROOT_DIR'}/gui/data " .
			"$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data",
			\$stdout, \$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		# Save filemanager data (ajaxplorer)
		if(! exists $main::imscpConfig{'FILEMANAGER_ADDON'} || $main::imscpConfig{'FILEMANAGER_ADDON'} eq 'AjaxPlorer') {
			if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/public/tools/filemanager/data") {
				my $dir = iMSCP::Dir->new(
					'dirname' => "$destdir$main::imscpConfig{'ROOT_DIR'}/gui/public/tools/filemanager/data"
				);
				$rs = $dir->make();
				return $rs if $rs;

				$rs = execute(
					"$main::imscpConfig{'CMD_CP'} -RTf $main::imscpConfig{'ROOT_DIR'}/gui/public/tools/filemanager/data " .
					"$destdir$main::imscpConfig{'ROOT_DIR'}/gui/public/tools/filemanager/data",
					\$stdout, \$stderr
				);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs;
			}
		}

		# Save GUI plugins
		if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/plugins") {
			$rs = execute(
				"$main::imscpConfig{'CMD_CP'} -RTf $main::imscpConfig{'ROOT_DIR'}/gui/plugins " .
				"$destdir$main::imscpConfig{'ROOT_DIR'}/gui/plugins",
				\$stdout, \$stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}

	# i-MSCP versions prior 1.0.4
	} else {
		# Save i-MSCP GUI data (isp logos)
		if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/themes/user_logos") {
			$rs = execute(
				"$main::imscpConfig{'CMD_CP'} -RTf $main::imscpConfig{'ROOT_DIR'}/gui/themes/user_logos " .
				"$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/ispLogos",
				\$stdout, \$stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}

		# Save i-MSCP GUI data (isp domain default index.html page)
		if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/domain_default_page") {
			$rs = execute(
				"$main::imscpConfig{'CMD_CP'} -RTf $main::imscpConfig{'ROOT_DIR'}/gui/domain_default_page " .
				"$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/domain_default_page",
				\$stdout, \$stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}

		# Save i-MSCP GUI data (isp domain default index.html page for disabled domains)
		if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/domain_disable_page") {
			$rs = execute(
				"$main::imscpConfig{'CMD_CP'} -RTf $main::imscpConfig{'ROOT_DIR'}/gui/domain_disable_page " .
				"$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/domain_disable_page",
				\$stdout, \$stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}
	}

	0;
}

=item installTmp()

 Install files from build directory on file system.

 Return int - 0 on success, other on failure

=cut

sub installTmp
{
	my $rs = 0;
	my ($stdout, $stderr);
	my $destdir = $main::{'INST_PREF'};

	# i-MSCP daemon must be stopped before changing any file on the files system
	if(-f '/etc/init.d/imscp_daemon' && -f "$main::imscpConfig{'ROOT_DIR'}/daemon/imscp_daemon") {
		$rs = execute('/etc/init.d/imscp_daemon stop', \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	# Session files must not be saved to prevent any troubles after update.
	$rs = execute(
		"$main::imscpConfig{'CMD_RM'} -fr $destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/sessions/*",
		\$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Cache files must not be saved to prevent any troubles after update.
	$rs = execute(
		"$main::imscpConfig{'CMD_RM'} -fr $destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/cache/*",
		\$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Process cleanup to avoid any security risks and conflicts
	$rs = execute(
		"$main::imscpConfig{'CMD_RM'} -fr " . "$main::imscpConfig{'ROOT_DIR'}/daemon " .
		"$main::imscpConfig{'ROOT_DIR'}/engine " . "$main::imscpConfig{'ROOT_DIR'}/gui ",
		\$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Install new i-MSCP files on the files system
	$rs = execute("$main::imscpConfig{'CMD_CP'} -Rf $destdir/* /", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	0;
}

=item

 Delete build directory.

 Return int - 0 on success, other on failure

=cut

sub removeTmp
{
	my $rs = 0;
	my ($stdout, $stderr);

	if($main::{'INST_PREF'} && -d $main::{'INST_PREF'}) {
		$rs = execute("$main::imscpConfig{'CMD_RM'} -fr $main::{'INST_PREF'}", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
}

=item checkCommandAvailability()

 Check availability of the given command.

 Return int - 0 if the given command is available, 1 othewise

=cut

sub checkCommandAvailability($)
{
	my $command = shift;
	my $rs = 0;
	my ($stdout, $stderr);

	$rs = execute("$main::imscpConfig{'CMD_WHICH'} $command", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=back

=head1 PRIVATES FUNCTIONS

=over 4

=item _expandVars()

 Expand variables in the given string.

 Return string

=cut

sub _expandVars
{
	my $string = shift || '';

	debug("Input: $string");

	for($string =~ /\$\{([^\}]+)\}/g) {
		if(exists $main::{$_}) {
			$string =~ s/\$\{$_\}/$main::{$_}/g;
		} elsif(exists $main::imscpConfig{$_}) {
			$string =~ s/\$\{$_\}/$main::imscpConfig{$_}/g;
		} else {
			fatal("Unable to expand variable \${$_}. Variable not found.");
		}
	}

	debug("Output: $string");

	$string;
}

=item _processFolder()

 Process a 'folder' node from an install.xml file.

 Process the xml 'folder' node by creating the described directory.

 Return int - 0 on success, other on failure

=cut


sub _processFolder
{
	my $data = shift;
	my $rs = 0;
	my $dir = iMSCP::Dir->new('dirname' => $data->{'content'});

	# Really needed to be sure to not keep any file from a previous build (Will normally only acts on INST_PREF)
	if(-d $data->{'content'}) {
		$rs = $dir->remove();
		return $rs if $rs;
	}


	debug("Creating $dir->{'dirname'} directory");

	my $options = {};

	$options->{'mode'} = oct($data->{'mode'}) if exists $data->{'mode'};
	$options->{'user'} = _expandVars($data->{'owner'}) if exists $data->{'owner'};
	$options->{'group'} = _expandVars($data->{'group'}) if exists $data->{'group'};

	$dir->make($options);
}

=item

 Process a 'copy_config' node from an install.xml file.

 Return int - 0 on success, other on failure

=cut

sub _copyConfig
{
	my $data = shift;

	my @parts = split '/', $data->{'content'};
	my $name = pop(@parts);
	my $path = join '/', @parts;
	my $distribution = lc(iMSCP::LsbRelease->getInstance()->getId(1));

	my $alternativeFolder = getcwd();
	$alternativeFolder =~ s!\/$distribution!\/debian!;

	my $source = -e $name ? $name : "$alternativeFolder/$name";

	my $rs = 0;
	my ($stdout, $stderr);

	if(-d $source) {
		debug("Copying $source directory in $path");
		$rs = execute("$main::imscpConfig{'CMD_CP'} -R $source $path", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	} else {
		debug("Copying $source in $path");
		$rs = execute("$main::imscpConfig{'CMD_CP'} $source $path", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	if($data->{'user'} || $data->{'group'} || $data->{'mode'}) {
		my $filename = -e "$path/$name" ? "$path/$name" : $path;

		my $file = iMSCP::File->new('filename' => $filename);
		$rs = $file->mode(oct($data->{'mode'})) if $data->{'mode'};
		return $rs if $rs;

		$rs = $file->owner(
			$data->{'user'} ? _expandVars($data->{'user'}) : -1,
			$data->{'group'} ? _expandVars($data->{'group'}) : -1
		) if($data->{'user'} || $data->{'group'});
		return $rs if $rs;
	}

	0;
}

=item

 Process the 'copy' node from an install.xml file.

 Return int - 0 on success, other on failure

=cut

sub _copy
{
	my $data = shift;
	my @parts = split '/', $data->{'content'};
	my $name = pop(@parts);
	my $path = join '/', @parts;

	debug("Copy recursive $name in $path");

	my $rs = 0;
	my ($stdout, $stderr);
	$rs = execute("$main::imscpConfig{'CMD_CP'} -R $name $path", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	if($data->{'user'} || $data->{'group'} || $data->{'mode'}) {
		my $filename = -e "$path/$name" ? "$path/$name" : $path;

		my $file = iMSCP::File->new(filename => $filename);
		$rs = $file->mode(oct($data->{'mode'})) if $data->{'mode'};
		return $rs if $rs;

		$rs = $file->owner(
			$data->{'user'} ? _expandVars($data->{'user'}) : -1,
			$data->{'group'} ? _expandVars($data->{'group'}) : -1
		) if $data->{'user'} || $data->{'group'};
		return $rs if $rs;
	}

	0;
}

=item _createFile()

 Create a file.

 Return int - 0 on success, other on failure

=cut

sub _createFile
{
	my $data = shift;

	iMSCP::File->new('filename' => $data->{'content'})->save();
}

=item _chownFile()

 Change file/directory owner and/or group recursively.

 Return int - 0 on success, other on failure

=cut

sub _chownFile
{
	my $data = shift;

	if($data->{'owner'} && $data->{'group'}) {
		my $rs = 0;
		my ($stdout, $stderr);
		$rs = execute(
			"$main::imscpConfig{'CMD_CHOWN'} -R $data->{'owner'}:$data->{'group'} $data->{'content'}",
			\$stdout, \$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
}

=item _chmodFile()

 Process chmod_file from an install.xml file.

 Return int - 0 on success, other on failure

=cut

sub _chmodFile
{
	debug('Starting...');

	my $data = shift;

	if(exists $data->{'mode'}) {
		my $rs = 0;
		my ($stdout, $stderr);
		$rs = execute("$main::imscpConfig{'CMD_CHMOD'} -R $data->{'mode'} $data->{'content'}", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	debug('Ending...');

	0;
}

=item _getDistroAdapter()

 Return distro autoinstaller adapter instance.

 Return autoinstaller::Adapter::Abstract
 TODO check that adapter is an instance of autoinstaller::Adapter::Abstract

=cut

sub _getDistroAdapter
{
	if(! defined $main::autoinstallerAdapter) {
		my $distribution = iMSCP::LsbRelease->getInstance()->getId(1);
		my $file = "$FindBin::Bin/autoinstaller/Adapter/$distribution.pm";
		my $adapterClass = "autoinstaller::Adapter::$distribution";

		if(-f $file) {
			require $file;
			$main::autoinstallerAdapter = $adapterClass->getInstance();
		} else {
			fatal('Distro autoinstaller adapter not found');
		}
	}

	$main::autoinstallerAdapter;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
