#!/usr/bin/perl

=head1 NAME

 autoinstaller::Functions - Functions for the i-MSCP autoinstaller

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2014 by internet Multi Server Control Panel
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
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package autoinstaller::Functions;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::Bootstrapper;
use iMSCP::Getopt;
use iMSCP::Dialog;
use iMSCP::LsbRelease;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Stepper;
use File::Find;
use Cwd;

use iMSCP::Getopt;

use parent 'Exporter';
our @EXPORT_OK = qw/loadConfig build install/;

my $autoinstallerAdapterInstance;
my $eventManager;

=head1 DESCRIPTION

 Common functions for the i-MSCP installer

=head1 PUBLIC FUNCTIONS

=over 4

=item loadConfig()

 Load main i-MSCP configuration

 Load both, the new imscp.conf file (upstream conffile) and the current imscp.conf file (old conffile) and merge them
together in the %main::imscpConfig variable. The old imscp.conf file is tied to the %main::imscpOldConfig variable
and set as readonly.

 Return undef

=cut

sub loadConfig
{
	my $distroConffile = "$FindBin::Bin/configs/" . lc(iMSCP::LsbRelease->getInstance()->getId(1)) . '/imscp.conf';
	my $defaultConffile = "$FindBin::Bin/configs/debian/imscp.conf";

	# Load new imscp.conf conffile from i-MSCP upstream source
	tie my %imscpNewConfig, 'iMSCP::Config', 'fileName' => (-f $distroConffile) ? $distroConffile : $defaultConffile;

	%main::imscpConfig = %imscpNewConfig;

	# Load old i-MSCP conffile as readonly if it exists
	if (-f "$imscpNewConfig{'CONF_DIR'}/imscp.conf") {
		tie %main::imscpOldConfig,
			'iMSCP::Config',
			'fileName' => "$imscpNewConfig{'CONF_DIR'}/imscp.conf",
			'readonly' => 1,
			'nowarn' => 1;

		# Merge old config with the new but do not write anything yet.
		for(keys %main::imscpOldConfig) {
			if(exists $main::imscpConfig{$_} && not $_ ~~  ['BuildDate', 'Version', 'CodeName', 'THEME_ASSETS_VERSION']) {
				$main::imscpConfig{$_} = $main::imscpOldConfig{$_};
			}
		}
	} else { # No conffile found, assumption is made that it's a new install
		%main::imscpOldConfig = ();
	}

	$eventManager = iMSCP::EventManager->getInstance();

	undef;
}

=item build()

 Process build tasks

 Return int 0 on success, other on failure

=cut

sub build
{
	if($main::skippackages && ! iMSCP::Getopt->preseed) {
		unless(
			$main::imscpConfig{'HTTPD_SERVER'} && $main::imscpConfig{'PO_SERVER'} && $main::imscpConfig{'MTA_SERVER'} &&
			$main::imscpConfig{'FTPD_SERVER'} && $main::imscpConfig{'NAMED_SERVER'} && $main::imscpConfig{'SQL_SERVER'}
		) {
			$main::noprompt = 0;
			$main::skippackages = 0;
		}
	}

	my $rs = _installPreRequiredPackages() unless $main::skippackages;
	return $rs if $rs;

	my $dialog = iMSCP::Dialog->getInstance();

	$dialog->set('ok-label', 'Ok');
	$dialog->set('yes-label', 'Yes');
	$dialog->set('no-label', 'No');
	$dialog->set('cancel-label', 'Back');

	unless($main::noprompt || $main::reconfigure ne 'none') {
		$rs = _showReadmeFile($dialog);
		return $rs if $rs;

		$rs = _askDistro($dialog);
		return $rs if $rs;
	}

	$rs = _askInstallMode($dialog) unless $main::noprompt || $main::buildonly || $main::reconfigure ne 'none';
	return $rs if $rs;

	newDebug('imscp-build.log');

	$rs = _getDistroAdapter()->preBuild();
	return $rs if $rs;

	my @steps = (
		[\&_processDistroPackages,     'Processing distro packages'],
		[\&_testRequirements,          'Testing requirements'],
		[\&_processDistroLayoutFile,   'Processing distro layout'],
		[\&_processDistroInstallFiles, 'Processing distro install files'],
		[\&_compileDaemon,             'Compiling daemon'],
		[\&_buildEngineFiles,          'Building engine files'],
		[\&_buildFrontendFiles,        'Building frontEnd files'],
		[\&_savePersistentData,        'Saving persistent data']
	);

	# Remove the distro packages step in case the --skippackages is set
	shift @steps if $main::skippackages;

	$rs = $eventManager->trigger('beforeBuild', \@steps);
	return $rs if $rs;

	my $step = 1;
	my $nbSteps = scalar @steps;

	for (@steps) {
		$rs = step($_->[0], $_->[1], $nbSteps, $step);
		error('An error occured while performing build steps') if $rs;
		return $rs if $rs;
		$step++;
	}

	$rs = $eventManager->trigger('afterBuild');
	return $rs if $rs;

	$rs = $eventManager->trigger('beforePostBuild');
	return $rs if $rs;

	$rs = _getDistroAdapter()->postBuild();
	return $rs if $rs;

	unless($main::skippackages) {
		# Add/update servers selection in imscp.conf file
		for('HTTPD', 'PO', 'MTA', 'FTPD', 'NAMED', 'SQL') {
			$main::imscpConfig{ $_ . '_SERVER' } = $main::questions{ $_ . '_SERVER' };
		}
	}

	# Backup current config if any
	if(-f "$main::imscpConfig{'CONF_DIR'}/imscp.conf") {
		$rs = iMSCP::File->new(
			'filename' => "$main::imscpConfig{'CONF_DIR'}/imscp.conf"
		)->copyFile(
			"$main::imscpConfig{'CONF_DIR'}/imscp.old.conf"
		);
		return $rs if $rs;
	}

	# Write new config file
	my %imscpConf = %main::imscpConfig;
	tie %main::imscpConfig, 'iMSCP::Config', 'fileName' => "$main::{'SYSTEM_CONF'}/imscp.conf";
	$main::imscpConfig{$_} = $imscpConf{$_} for keys %imscpConf;

	# Clean build directory (remove any .gitignore|empty-file)
	find(
		sub { unlink or fatal("Unable to remove $File::Find::name: $!") if  $_ eq '.gitignore' || $_ eq 'empty-file'; },
		$main::{'INST_PREF'}
	);

	$rs = $eventManager->trigger('afterPostBuild');
	return $rs if $rs;

	endDebug();
}

=item install()

 Process install tasks

 Return int 0 on success, other otherwise

=cut

sub install
{
	my $runningProcess = 0;
	my $bootstrapper = iMSCP::Bootstrapper->getInstance();

	for(
		'imscp-backup-all', 'imscp-backup-imscp', 'imscp-dsk-quota', 'imscp-srv-traff', 'imscp-vrl-traff',
		'awstats_updateall.pl', 'imscp'
	) {
		unless($bootstrapper->lock("/tmp/$_.lock", 'nowait')) {
			 $runningProcess = 1;
		}
	}

	if($runningProcess) {
		iMSCP::Dialog->getInstance()->msgbox(<<EOF);

One or many i-MSCP related processes are currently running on your system.

You must wait until the end of the processes and re-run the installer.
EOF
		return 1;
	}

	my @steps = (
		[\&_doImscpBackup,               'Backup existing installation if any'],
		[\&_installFiles,                'Installing files'],
		[\&main::setupBoot,              'Setup bootstrapping'],
		[\&main::setupRegisterListeners, 'Registering servers/packages event listeners'],
		[\&main::setupDialog,            'Processing setup dialog'],
		[\&main::setupTasks,             'Processing setup tasks'],
		[\&_deleteBuildDir,              'Deleting Build directory']
	);

	newDebug('imscp-setup.log');

	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeInstall', \@steps);
	return $rs if $rs;

	my $step = 1;
	my $nbSteps = scalar @steps;

	for (@steps) {
		$rs = step($_->[0], $_->[1], $nbSteps, $step);
		error('An error occured while performing installation steps') if $rs;
		return $rs if $rs;

		$step++;
	}

	iMSCP::Dialog->getInstance()->endGauge() if iMSCP::Dialog->getInstance()->hasGauge();

	$rs = iMSCP::EventManager->getInstance()->trigger('afterInstall');
	return $rs if $rs;

	my $port = ($main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} eq 'http://')
		? $main::imscpConfig{'BASE_SERVER_VHOST_HTTP_PORT'}
		: $main::imscpConfig{'BASE_SERVER_VHOST_HTTPS_PORT'};

	iMSCP::Dialog->getInstance()->msgbox(<<EOF);

\\Z1Congratulations\\Zn

i-MSCP has been successfully installed/updated.

Please connect to $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'}$main::imscpConfig{'BASE_SERVER_VHOST'}:$port and login with your administrator account.

Thank you for choosing i-MSCP.
EOF

	endDebug();
}

=back

=head1 PRIVATE FUNCTIONS

=over 4

=item _installPreRequiredPackages()

 Trigger pre-required package installation tasks from distro autoinstaller adapter

 Return int 0 on success, other otherwise

=cut

sub _installPreRequiredPackages
{
	_getDistroAdapter()->installPreRequiredPackages();
}

=item showReadmeFile(\%dialog)

 Show readme file

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other otherwise

=cut

sub _showReadmeFile
{
	my $dialog = $_[0];

	my $file = iMSCP::File->new('filename' => $FindBin::Bin . '/README');
	my $content = $file->get() or fatal("Unable to read $FindBin::Bin/README");

	$dialog->msgbox(<<EOF);

$content
EOF
}

=item _askDistro(\%dialog)

 Ask for distribution

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, 50 otherwise

=cut

sub _askDistro()
{
	my $dialog = $_[0];

	$dialog->infobox("\nDetecting target distribution...");

	my $lsbRelease = iMSCP::LsbRelease->getInstance();
	my $distribution = $lsbRelease->getId(1);
	my $codename = lc($lsbRelease->getCodename(1));
	my $release = $lsbRelease->getRelease(1);
	my $description = $lsbRelease->getDescription(1);
	my $packagesFile = "$FindBin::Bin/docs/$distribution/packages-$codename.xml";

	if($distribution ne 'n/a' && (lc($distribution) eq 'debian' || lc($distribution) eq 'ubuntu') && $codename ne 'n/a') {
		unless(-f $packagesFile) {
			iMSCP::Dialog->getInstance()->msgbox(<<EOF);

\\Z1$distribution $release ($codename) not supported yet\\Zn

We are sorry but the version of your distribution is not supported yet.

You can try to provide your own packages file by putting it into the \\Z4docs/$distribution\\Zn directory.

Thanks for choosing i-MSCP.
EOF

			return 50;
		}

		my $rs = $dialog->yesno(<<EOF);

$distribution $release ($codename) has been detected. Is this ok?
EOF

		iMSCP::Dialog->getInstance()->msgbox(<<EOF) if $rs;

\\Z1Distribution not supported\\Zn

We are sorry but the installer has failed to detect your distribution.

Only \\ZuDebian-like\\Zn operating systems are supported.

Thanks for choosing i-MSCP.
EOF

		return 50 if $rs;
	} else {
		$dialog->msgbox(<<EOF);

\\Z1Distribution not supported\\Zn

We are sorry but your distribution is not supported yet.

Only \\ZuDebian-like\\Zn operating systems are supported.

Thanks for choosing i-MSCP.
EOF

		return 50;
	}

	0;
}

=item _askInstallMode(\%dialog)

 Asks for install mode

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, 50 otherwise

=cut

sub _askInstallMode
{
	my $dialog = $_[0];

	$dialog->set('cancel-label', 'Abort');

	my ($rs, $mode) = $dialog->radiolist(<<EOF, ['Install', 'Build'], 'Install');

\\Z4\\Zb\\ZuInstaller Options\\Zn

Please, choose an option:

\\Z4Install:\\Zn Choose this option if you want install or update i-MSCP.
  \\Z4Build:\\Zn Choose this option if you want install i-MSCP manually or if you
         want migrate from ispCP (>= 1.0.7).
EOF

	$main::buildonly = ($mode eq 'Build') ? 1 : 0;

	$dialog->set('cancel-label', 'Back');

	return 50 if $rs;

	0;
}

=item _processDistroPackages()

 Trigger packages installation/uninstallation tasks from distro autoinstaller adapter

 Return int 0 on success, other on failure

=cut

sub _processDistroPackages
{
	_getDistroAdapter()->installPackages() || _getDistroAdapter()->uninstallPackages();
}


=item _testRequirements()

 Test for requirements

 Return undef if all requirements are meet, throw a fatal error otherwise

=cut

sub _testRequirements
{
	iMSCP::Requirements->new()->test('all');
}

=item _processDistroLayoutFile()

 Process distribution layout.xml file

 Return int 0 on success, other on failure

=cut

sub _processDistroLayoutFile()
{
	# Possible layout paths
	my $distroLayout = "$FindBin::Bin/autoinstaller/Layout/" . iMSCP::LsbRelease->getInstance()->getId(1) . '.xml';
	my $defaultLayout = "$FindBin::Bin/autoinstaller/Layout/Debian.xml";

	# Process layout
	_processXmlFile((-f $distroLayout) ? $distroLayout : $defaultLayout);
}

=item _processDistroInstallFiles()

 Process distribution install.xml files

 Return int 0 on success, other on failure

=cut

sub _processDistroInstallFiles
{
	# Possible config directory paths
	my $distroConfigDir = "$FindBin::Bin/configs/" . lc(iMSCP::LsbRelease->getInstance()->getId(1));
	my $defaultConfigDir = "$FindBin::Bin/configs/debian";

	# Determine config directory to use
	my $confDir = (-d $distroConfigDir) ? $distroConfigDir : $defaultConfigDir;

	unless(chdir($confDir)) {
		error("Unable to change directory to $confDir: $!");
		return 1;
	}

	# Determine install.xml file to process
	my $file = (-f "$distroConfigDir/install.xml") ? "$distroConfigDir/install.xml" : "$defaultConfigDir/install.xml";

	my $rs = _processXmlFile($file);
	return $rs if $rs;

	# Get list of sub config dir from default config directory (debian)
	my $dirDH = iMSCP::Dir->new('dirname' => $defaultConfigDir);
	my @configDirs = $dirDH->getDirs();

	for(@configDirs) {
		# Override sub config dir path if it is available in selected distro, else set it to default path
		$confDir = (-d "$distroConfigDir/$_") ? "$distroConfigDir/$_" : "$defaultConfigDir/$_";

		unless(chdir($confDir)) {
			error("Cannot change directory to $confDir: $!");
			return 1;
		}

		$file = (-f "$distroConfigDir/$_/install.xml")
			? "$distroConfigDir/$_/install.xml" : "$defaultConfigDir/$_/install.xml";

		if(-f $file) {
			$rs = _processXmlFile($file);
			return $rs if $rs;
		}
	}

	0;
}

=item _compileDaemon()

 Compile daemon

 Return int 0 on success, other on failure

=cut

sub _compileDaemon
{
	unless(chdir "$FindBin::Bin/daemon") {
		error("Unable to change dir to $FindBin::Bin/daemon");
		return 1;
	}

	my ($stdout, $stderr);

	my $rs = execute("$main::imscpConfig{'CMD_MAKE'} clean imscp_daemon", \$stdout, \$stderr);
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

	$rs;
}

=item _buildEngineFiles()

 Build engine files

 Return int 0 on success, other on failure

=cut

sub _buildEngineFiles
{
	unless(chdir "$FindBin::Bin/engine") {
		error("Unable to change dir to $FindBin::Bin/engine");
		return 1;
	}

	my $rs = _processXmlFile("$FindBin::Bin/engine/install.xml");
	return $rs if $rs;

	my $dir = iMSCP::Dir->new('dirname' => "$FindBin::Bin/engine");

	my @configDirs = $dir->getDirs();

	for(@configDirs) {
		if (-f "$FindBin::Bin/engine/$_/install.xml") {
			unless(chdir "$FindBin::Bin/engine/$_") {
				error("Unable to change dir to $FindBin::Bin/engine/$_");
				return 1;
			}

			$rs = _processXmlFile("$FindBin::Bin/engine/$_/install.xml") ;
			return $rs if $rs;
		}
	}

	0;
}

=item _buildFrontendFiles()

 Build frontEnd files

 Return int 0 on success, other on failure

=cut

sub _buildFrontendFiles
{
	my ($stdout, $stderr);
	my $rs = execute("$main::imscpConfig{'CMD_CP'} -fR $FindBin::Bin/gui $main::{'SYSTEM_ROOT'}", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _savePersistentData()

 Save persistent data

 Return int 0 on success, other on failure

=cut

sub _savePersistentData
{
	my $rs = 0;
	my ($stdout, $stderr);
	my $destdir = $main::{'INST_PREF'};

	# Save ISP logos
	if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/themes/user_logos") {
		$rs = execute(
			"$main::imscpConfig{'CMD_CP'} -fRT $main::imscpConfig{'ROOT_DIR'}/gui/themes/user_logos " .
				"$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent/ispLogos",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	# Save Web directories skeletons

	# Move old skel directory to new location
	if(-d "$main::imscpConfig{'CONF_DIR'}/apache/skel") {
		$rs = execute(
			"$main::imscpConfig{'CMD_MV'} $main::imscpConfig{'CONF_DIR'}/apache/skel " .
				"$main::imscpConfig{'CONF_DIR'}/skel",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	if(-d "$main::imscpConfig{'CONF_DIR'}/skel") {
		$rs = execute(
			"$main::imscpConfig{'CMD_CP'} -fRT $main::imscpConfig{'CONF_DIR'}/skel " .
				"$destdir$main::imscpConfig{'CONF_DIR'}/skel",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	# Move old listener files to new location
	if(-d "$main::imscpConfig{'CONF_DIR'}/hooks.d") {
		$rs = execute(
			"$main::imscpConfig{'CMD_MV'} $main::imscpConfig{'CONF_DIR'}/hooks.d " .
				"$main::imscpConfig{'CONF_DIR'}/listeners.d",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	# Remove old README file
	if(-f "$main::imscpConfig{'CONF_DIR'}/listeners.d/README") {
		$rs = execute(
			"$main::imscpConfig{'CMD_RM'} -f $main::imscpConfig{'CONF_DIR'}/listeners.d/README",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	#if(-d "$main::imscpConfig{'CONF_DIR'}/listeners.d") {
	#	$rs = execute(
	#		"$main::imscpConfig{'CMD_CP'} -fRTn $main::imscpConfig{'CONF_DIR'}/listeners.d " .
	#			"$destdir$main::imscpConfig{'CONF_DIR'}/listeners.d",
	#		\$stdout,
	#		\$stderr
	#	);
	#	debug($stdout) if $stdout;
	#	error($stderr) if $stderr && $rs;
	#	return $rs if $rs;
	#}

	# Save GUI logs
	if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/data/logs") {
		$rs = execute(
			"$main::imscpConfig{'CMD_CP'} -fRT $main::imscpConfig{'ROOT_DIR'}/gui/data/logs " .
				"$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/logs",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	# Save persistent data
	if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent") {
		$rs = execute(
			"$main::imscpConfig{'CMD_CP'} -fRT $main::imscpConfig{'ROOT_DIR'}/gui/data/persistent " .
				"$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	# save isp logos
	if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/data/ispLogos") {
		$rs = execute(
			"$main::imscpConfig{'CMD_CP'} -fRT $main::imscpConfig{'ROOT_DIR'}/gui/data/ispLogos " .
				"$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent/ispLogos",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	# Save software (older path ./gui/data/softwares) to new path (./gui/data/persistent/softwares)
	if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/data/softwares") {
		$rs = execute(
			"$main::imscpConfig{'CMD_CP'} -fRT $main::imscpConfig{'ROOT_DIR'}/gui/data/softwares " .
				"$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent/softwares",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	# Save plugins
	if(-d $main::imscpConfig{'PLUGINS_DIR'}) {
		$rs = execute(
			"$main::imscpConfig{'CMD_CP'} -fRT $main::imscpConfig{'PLUGINS_DIR'} $destdir$main::imscpConfig{'PLUGINS_DIR'}",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	# Move old package cache directory to new location
	if(-d  "$main::imscpConfig{'CACHE_DATA_DIR'}/addons") {
		$rs = execute(
			"$main::imscpConfig{'CMD_MV'} $main::imscpConfig{'CACHE_DATA_DIR'}/addons " .
				"$main::imscpConfig{'CACHE_DATA_DIR'}/packages",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
}

=item _doImscpBackup()

 Backup current i-MSCP installation (database and conffiles) if any

 Return int 0 on success, other on failure

=cut

sub _doImscpBackup
{
	if(-x "$main::imscpConfig{'ROOT_DIR'}/engine/backup/imscp-backup-imscp" && -f "$main::{'SYSTEM_CONF'}/imscp.conf") {
		iMSCP::Bootstrapper->getInstance()->unlock('/tmp/imscp-backup-imscp.lock');

		my ($stdout, $stderr);
		my $rs = execute("$main::imscpConfig{'ROOT_DIR'}/engine/backup/imscp-backup-imscp", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;

		iMSCP::Bootstrapper->getInstance()->lock('/tmp/imscp-backup-imscp.lock');

		iMSCP::Dialog->getInstance()->yesno(<<EOF) if $rs;

\\Z1Unable to create backups\\Zn

This is not a fatal error, and thus, the setup may continue. However, you'll not have recent backup.

Do you want to continue?
EOF
	} else {
		0;
	}
}

=item _installFiles()

 Install files from build directory

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
	# i-MSCP daemon must be stopped before changing any file on the files system
	if(-x "$main::imscpConfig{'INIT_SCRIPTS_DIR'}/$main::imscpConfig{'IMSCP_DAEMON_SNAME'}") {
		my $rs = iMSCP::Service->getInstance()->stop($main::imscpConfig{'IMSCP_DAEMON_SNAME'});
		error("Unable to stop $main::imscpConfig{'IMSCP_DAEMON_SNAME'} service") if $rs;
		return $rs if $rs ;
	}

	# Process cleanup to avoid any security risks and conflicts
	my ($stdout, $stderr);
	my $rs = execute(
		"$main::imscpConfig{'CMD_RM'} -fR " .
		"$main::imscpConfig{'ROOT_DIR'}/daemon " .
		"$main::imscpConfig{'ROOT_DIR'}/engine " .
		"$main::imscpConfig{'ROOT_DIR'}/gui ",
		\$stdout,
		\$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Install new i-MSCP files on the files system
	$rs = execute("$main::imscpConfig{'CMD_CP'} -fR $main::{'INST_PREF'}/* /", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _deleteBuildDir()

 Delete build directory

 Return int 0 on success, other on failure

=cut

sub _deleteBuildDir
{
	my ($stdout, $stderr);

	if($main::{'INST_PREF'} && -d $main::{'INST_PREF'}) {
		my $rs = execute("$main::imscpConfig{'CMD_RM'} -fR $main::{'INST_PREF'}", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
}

=item _processXmlFile($filepath)

 Process an install.xml file or distribution layout.xml file

 Param string $filepath xml file path
 Return int 0 on success, other on failure ; A fatal error is raised in case a variable cannot be exported

=cut

sub _processXmlFile
{
	my $file = $_[0];

	unless(-f $file) {
		error("$file doesn't exist");
		return 1;
	}

	eval "use XML::Simple; 1";
	fatal('Unable to load the XML::Simple perl module') if $@;

	my $xml = XML::Simple->new('ForceArray' => 1, 'ForceContent' => 1);

	my $data = eval { $xml->XMLin($file, 'VarAttr' => 'export') };

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

=item _expandVars($string)

 Expand variables in the given string

 Param string $string string containing variables to expands
 Return string

=cut

sub _expandVars
{
	my $string = $_[0] || '';

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

=item _processFolder(\%data)

 Process a folder node from an install.xml file

 Process the xml folder node by creating the described directory.

 Return int 0 on success, other on failure

=cut

sub _processFolder
{
	my $data = $_[0];

	my $dir = iMSCP::Dir->new('dirname' => $data->{'content'});

	# Needed to be sure to not keep any file from a previous build that has failed
	if(defined $main::{'INST_PREF'} && $main::{'INST_PREF'} eq $data->{'content'}) {
		my $rs = $dir->remove();
		return $rs if $rs;
	}

	debug("Creating $dir->{'dirname'} directory");

	my $options = { };

	$options->{'mode'} = oct($data->{'mode'}) if exists $data->{'mode'};
	$options->{'user'} = _expandVars($data->{'owner'}) if exists $data->{'owner'};
	$options->{'group'} = _expandVars($data->{'group'}) if exists $data->{'group'};

	$dir->make($options);
}

=item _copyConfig(\%data)

 Process a copy_config node from an install.xml file

 Return int 0 on success, other on failure

=cut

sub _copyConfig
{
	my $data = $_[0];

	my @parts = split '/', $data->{'content'};
	my $name = pop(@parts);
	my $path = join '/', @parts;
	my $distribution = lc(iMSCP::LsbRelease->getInstance()->getId(1));

	my $alternativeFolder = getcwd();
	$alternativeFolder =~ s/$distribution/debian/;

	my $source = -f $name ? $name : "$alternativeFolder/$name";

	my ($rs, $stdout, $stderr);

	if(-d $source) {
		debug("Copying $source directory in $path");
		$rs = execute("$main::imscpConfig{'CMD_CP'} -fR $source $path", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	} else {
		debug("Copying $source file in $path");
		$rs = execute("$main::imscpConfig{'CMD_CP'} -f $source $path", \$stdout, \$stderr);
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
			$data->{'user'} ? _expandVars($data->{'user'}) : -1, $data->{'group'} ? _expandVars($data->{'group'}) : -1
		) if $data->{'user'} || $data->{'group'};
		return $rs if $rs;
	}

	0;
}

=item _copy(\%data)

 Process the copy node from an install.xml file

 Return int 0 on success, other on failure

=cut

sub _copy
{
	my $data = $_[0];

	my @parts = split '/', $data->{'content'};
	my $name = pop(@parts);
	my $path = join '/', @parts;

	debug("Copy recursive $name in $path");

	my ($stdout, $stderr);
	my $rs = execute("$main::imscpConfig{'CMD_CP'} -fR $name $path", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	if($data->{'user'} || $data->{'group'} || $data->{'mode'}) {
		my $filename = -e "$path/$name" ? "$path/$name" : $path;

		my $file = iMSCP::File->new('filename' => $filename);
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

=item _createFile(\%$data)

 Create a file

 Return int 0 on success, other on failure

=cut

sub _createFile
{
	iMSCP::File->new('filename' => $_[0]->{'content'})->save();
}

=item _chownFile()

 Change file/directory owner and/or group recursively

 Return int 0 on success, other on failure

=cut

sub _chownFile
{
	my $data = $_[0];

	if($data->{'owner'} && $data->{'group'}) {
		my ($stdout, $stderr);
		my $rs = execute(
			"$main::imscpConfig{'CMD_CHOWN'} $data->{'owner'}:$data->{'group'} $data->{'content'}",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
}

=item _chmodFile(\%data)

 Process chmod_file from an install.xml file

 Return int 0 on success, other on failure

=cut

sub _chmodFile
{
	my $data = $_[0];

	if(exists $data->{'mode'}) {
		my ($stdout, $stderr);
		my $rs = execute("$main::imscpConfig{'CMD_CHMOD'} $data->{'mode'} $data->{'content'}", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
}

=item _getDistroAdapter()

 Return distro autoinstaller adapter instance

 Return autoinstaller::Adapter::Abstract

=cut

sub _getDistroAdapter
{
	unless(defined $autoinstallerAdapterInstance) {
		my $distribution = iMSCP::LsbRelease->getInstance()->getId(1);

		eval {
			my $file = "$FindBin::Bin/autoinstaller/Adapter/${distribution}Adapter.pm";
			my $adapterClass = "autoinstaller::Adapter::${distribution}Adapter";

			require $file;
			$autoinstallerAdapterInstance = $adapterClass->getInstance()
		};

		fatal("Unable to instantiate $distribution autoinstaller adapter: $@") if $@;
	}

	$autoinstallerAdapterInstance;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
