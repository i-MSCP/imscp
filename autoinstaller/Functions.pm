=head1 NAME

 autoinstaller::Functions - Functions for the i-MSCP autoinstaller

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2015 by internet Multi Server Control Panel
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
use iMSCP::Rights;
use iMSCP::Stepper;
use File::Basename;
use File::Find;
use Cwd;
use version;
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
	tie my %imscpNewConfig, 'iMSCP::Config', fileName => (-f $distroConffile) ? $distroConffile : $defaultConffile;

	%main::imscpConfig = %imscpNewConfig;

	# Load old i-MSCP conffile as readonly if it exists
	if (-f "$imscpNewConfig{'CONF_DIR'}/imscp.conf") {
		tie %main::imscpOldConfig, 'iMSCP::Config', fileName => "$imscpNewConfig{'CONF_DIR'}/imscp.conf", readonly => 1,
			nowarn => 1;

		# Merge old config with the new but do not write anything yet.
		for my $paramName(keys %main::imscpOldConfig) {
			if(
				exists $main::imscpConfig{$paramName} &&
				not $paramName ~~  [ 'BuildDate', 'Version', 'CodeName', 'THEME_ASSETS_VERSION' ]
			) {
				$main::imscpConfig{$paramName} = $main::imscpOldConfig{$paramName};
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
	newDebug('imscp-build.log');

	if($main::skippackages && !iMSCP::Getopt->preseed) {
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
		$rs = _showWelcomeMsg($dialog);
		return $rs if $rs;

		if(%main::imscpOldConfig) {
			$rs = _showUpdateNotices($dialog);
			return $rs if $rs;
		}

		$rs = _confirmDistro($dialog);
		return $rs if $rs;
	}

	$rs = _askInstallMode($dialog) unless $main::noprompt || $main::buildonly || $main::reconfigure ne 'none';
	return $rs if $rs;

	$rs = _getDistroAdapter()->preBuild();
	return $rs if $rs;

	my @steps = (
		[ \&_processDistroPackages,  'Processing distro packages' ],
		[ \&_testRequirements,       'Checking in requirements' ],
		[ \&_buildDistributionFiles, 'Building distribution files' ]
	);

	# Remove the distro packages step in case the --skippackages is set
	shift @steps if $main::skippackages;

	$rs = $eventManager->trigger('beforeBuild', \@steps);
	return $rs if $rs;

	my $cStep = 1;
	my $nbSteps = scalar @steps;

	for my $step(@steps) {
		$rs = step($step->[0], $step->[1], $nbSteps, $cStep);
		error('An error occurred while performing build steps') if $rs;
		return $rs if $rs;
		$cStep++;
	}

	$rs = $eventManager->trigger('afterBuild');
	return $rs if $rs;

	$rs = $eventManager->trigger('beforePostBuild');
	return $rs if $rs;

	$rs = _getDistroAdapter()->postBuild();
	return $rs if $rs;

	unless($main::skippackages) {
		# Add/update servers selection in imscp.conf file
		for my $server('HTTPD', 'PO', 'MTA', 'FTPD', 'NAMED', 'SQL') {
			$main::imscpConfig{ $server . '_SERVER' } = $main::questions{ $server . '_SERVER' };
		}
	}

	# Backup current configuration file if any
	if(-f "$main::imscpConfig{'CONF_DIR'}/imscp.conf") {
		$rs = iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/imscp.conf" )->copyFile(
			"$main::imscpConfig{'CONF_DIR'}/imscp.old.conf"
		);
		return $rs if $rs;
	}

	# Write new config file
	my %imscpConf = %main::imscpConfig;
	tie %main::imscpConfig, 'iMSCP::Config', fileName => "$main::{'SYSTEM_CONF'}/imscp.conf";
	$main::imscpConfig{$_} = $imscpConf{$_} for keys %imscpConf;

	# Clean build directory (remove any .gitignore|empty-file)
	find(
		sub { unlink or die("Unable to remove $File::Find::name: $!") if $_ eq '.gitignore' || $_ eq 'empty-file'; },
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
	newDebug('imscp-setup.log');

	my $runningProcess = 0;
	my $bootstrapper = iMSCP::Bootstrapper->getInstance();

	for my $lockFile(
		'imscp-backup-all', 'imscp-backup-imscp', 'imscp-dsk-quota', 'imscp-srv-traff', 'imscp-vrl-traff',
		'awstats_updateall.pl', 'imscp'
	) {
		unless($bootstrapper->lock("/tmp/$lockFile.lock", 'nowait')) {
			iMSCP::Dialog->getInstance()->msgbox(<<EOF);

One or many i-MSCP related processes are currently running on your system.

You must wait until the end of the processes and re-run the installer.
EOF
		return 1;
		}
	}

	my @steps = (
		[ \&_installFiles,                'Installing files' ],
		[ \&main::setupBoot,              'Setup bootstrapping' ],
		[ \&main::setupRegisterListeners, 'Registering servers/packages event listeners' ],
		[ \&main::setupDialog,            'Processing setup dialog' ],
		[ \&main::setupTasks,             'Processing setup tasks' ],
		[ \&_deleteBuildDir,              'Deleting Build directory' ]
	);

	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeInstall', \@steps);
	return $rs if $rs;

	my $cStep = 1;
	my $nbSteps = scalar @steps;

	for my $step(@steps) {
		$rs = step($step->[0], $step->[1], $nbSteps, $cStep);
		error('An error occurred while performing installation steps') if $rs;
		return $rs if $rs;

		$cStep++;
	}

	iMSCP::Dialog->getInstance()->endGauge() if iMSCP::Dialog->getInstance()->hasGauge();

	$rs = iMSCP::EventManager->getInstance()->trigger('afterInstall');
	return $rs if $rs;

	my $port = ($main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} eq 'http://')
		? $main::imscpConfig{'BASE_SERVER_VHOST_HTTP_PORT'}
		: $main::imscpConfig{'BASE_SERVER_VHOST_HTTPS_PORT'};

	iMSCP::Dialog->getInstance()->infobox(<<EOF);

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

=item _showWelcomeMsg(\%dialog)

 Show welcome message

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other otherwise

=cut

sub _showWelcomeMsg
{
	my $dialog = shift;

	$dialog->msgbox(<<EOF);

\\Zb\\Z4i-MSCP - internet Multi Server Control Panel
============================================\\Zn\\ZB

Welcome to the i-MSCP setup dialog.

i-MSCP (internet Multi Server Control Panel) is an open-source software which allows to manage shared hosting environments on Linux servers.

i-MSCP aims to provide an easy-to-use Web interface for end-users, and to manage servers without any manual intervention on the filesystem.

i-MSCP was designed for professional Hosting Service Providers (HSPs), Internet Service Providers (ISPs) and IT professionals.

\\Zb\\Z4License\\Zn\\ZB

Unless otherwise stated all code is licensed under GPL 2.0 and has the following copyright:

        \\ZbCopyright 2010-2015 by i-MSCP Team - All rights reserved\\ZB

\\Zb\\Z4Credits\\Zn\\ZB

i-MSCP is a project of i-MSCP | internet Multi Server Control Panel.
i-MSCP and the i-MSCP logo are trademarks of the i-MSCP | internet Multi Server Control Panel project team.
EOF
}

=item _showUpdateNotices(\%dialog)

 Show update notices

 Return 0 on success, other on failure or when user is aborting

=cut

sub _showUpdateNotices
{
	my $dialog = shift;

	my $noticesDir = "$FindBin::Bin/autoinstaller/UpdateNotices";
	my $imscpVersion = $main::imscpOldConfig{'Version'};
	my $notices = '';

	if($imscpVersion !~ /git/i) {
		if($imscpVersion =~ /^(\d+\.\d+\.\d+)/) {
			$imscpVersion = $1;

			my @noticeFiles = iMSCP::Dir->new( dirname => $noticesDir )->getFiles();

			if(@noticeFiles) {
				@noticeFiles = reverse sort @noticeFiles;

				for my $noticeFile(@noticeFiles) {
					(my $noticeVersion = $noticeFile) =~ s/\.txt$//;

					if(version->parse($imscpVersion) < version->parse($noticeVersion)) {
						my $noticeBody = iMSCP::File->new( filename => "$noticesDir/$noticeFile" )->get();
						unless(defined $noticeBody) {
							error("Unable to read $noticesDir/$noticeFile file");
							return 1;
						}

						$notices .= "\n$noticeBody";
					}
				}
			}
		} else {
			error('Could not parse i-MSCP version from your imscp.conf file.');
			return 1;
		}
	} else {
		$notices = <<EOF;

The installer detected that you're using the \\ZbGit\\ZB version of i-MSCP. Before continue, be sure to have read the errata file:

    \\Zbhttps://github.com/i-MSCP/imscp/blob/1.2.x/docs/1.2.x_errata.md\\ZB

We would remind you that the Git version can be highly unstable and that the i-MSCP team do not provides any support for it.
EOF
	}

	unless($notices eq '') {
		$dialog->set('yes-label', 'Continue');
		$dialog->set('no-label', 'Abort');
		my $rs = $dialog->yesno(<<EOF);

Please read carefully before continue.

\\ZbNote:\\ZB Use the \\ZbPage Down\\ZB key from your keyboard to scroll down.
$notices
You can now either continue or abort if needed.
EOF

		$dialog->resetLabels();
		return 50 if $rs;
	}

	0;
}

=item _confirmDistro(\%dialog)

 Distribution confirmation dialog

 Param iMSCP::Dialog \%dialog
 Return 0 on success, other on failure on when user is aborting

=cut

sub _confirmDistro
{
	my $dialog = shift;

	$dialog->infobox("\nDetecting target distribution...");

	my $lsbRelease = iMSCP::LsbRelease->getInstance();
	my $distribution = $lsbRelease->getId(1);
	my $codename = lc($lsbRelease->getCodename(1));
	my $release = $lsbRelease->getRelease(1);
	my $description = $lsbRelease->getDescription(1);
	my $packagesFile = "$FindBin::Bin/docs/$distribution/packages-$codename.xml";

	if(
		$distribution ne 'n/a' && (lc($distribution) eq 'debian' || lc($distribution) eq 'ubuntu') &&
		$codename ne 'n/a'
	) {
		unless(-f $packagesFile) {
			$dialog->msgbox(<<EOF);

\\Z1$distribution $release ($codename) not supported yet\\Zn

We are sorry but your $distribution version is not supported.

Thanks for choosing i-MSCP.
EOF

			return 50;
		}

		my $rs = $dialog->yesno(<<EOF);

$distribution $release ($codename) has been detected. Is this ok?
EOF

		$dialog->msgbox(<<EOF) if $rs;

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
	my $dialog = shift;

	$dialog->set('cancel-label', 'Abort');

	my ($rs, $mode) = $dialog->radiolist(<<EOF, [ 'Install', 'Build' ], 'Install');

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
	my $rs = _getDistroAdapter()->installPackages();
	$rs ||= _getDistroAdapter()->uninstallPackages();
}

=item _testRequirements()

 Test for requirements

 Return undef if all requirements are meet, throw a fatal error otherwise

=cut

sub _testRequirements
{
	iMSCP::Requirements->new()->all();
}

=item _buildDistributionFiles

 Build distribution files

 Return int 0 on success, other on failure

=cut

sub _buildDistributionFiles
{
	my $rs = _buildLayout();
	return $rs if $rs;

	$rs = _buildConfigFiles();
	return $rs if $rs;

	$rs = _buildEngineFiles();
	return $rs if $rs;

	$rs = _buildDaemon();
	return $rs if $rs;

	$rs = _buildFrontendFiles();
	return $rs if $rs;

	_savePersistentData();
}

=item _buildLayout()

 Build layout

 Return int 0 on success, other on failure

=cut

sub _buildLayout
{
	my $distroLayout = "$FindBin::Bin/autoinstaller/Layout/" . iMSCP::LsbRelease->getInstance()->getId(1) . '.xml';
	my $defaultLayout = "$FindBin::Bin/autoinstaller/Layout/Debian.xml";

	_processXmlFile(-f $distroLayout ? $distroLayout : $defaultLayout);
}

=item _buildConfigFiles()

 Build configuration files by processing install.xml file inside configuration directories

 Return int 0 on success, other on failure

=cut

sub _buildConfigFiles
{
	# Possible config directories
	my $distroConfdir = "$FindBin::Bin/configs/" . lc(iMSCP::LsbRelease->getInstance()->getId(1));
	my $defaultConfdir = "$FindBin::Bin/configs/debian";

	# Process root install.xml file
	my $rs = _processXmlFile((-f "$distroConfdir/install.xml" ? $distroConfdir : $defaultConfdir) . '/install.xml');
	return $rs if $rs;

	# Process install.xml files from subconfig directories
	for my $subdir(iMSCP::Dir->new( dirname => $defaultConfdir )->getDirs()) {
		$rs = _processXmlFile(
			(
				-f "$distroConfdir/$subdir/install.xml" ? "$distroConfdir/$subdir" : "$defaultConfdir/$subdir"
			) . '/install.xml'
		);
		return $rs if $rs;
	}

	0;
}

=item _buildEngineFiles()

 Build engine files by processing install.xml files inside engine directory

 Return int 0 on success, other on failure

=cut

sub _buildEngineFiles
{
	my $engineDir = "$FindBin::Bin/engine";

	my $rs = _processXmlFile("$engineDir/install.xml");
	return $rs if $rs;

	for my $subdir(iMSCP::Dir->new( dirname => $engineDir )->getDirs()) {
		if (-f "$engineDir/$subdir/install.xml") {
			$rs = _processXmlFile("$engineDir/$subdir/install.xml") ;
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
	iMSCP::Dir->new( dirname => "$FindBin::Bin/gui" )->rcopy("$main::{'SYSTEM_ROOT'}/gui");
}

=item _buildDaemon()

 Build daemon

 Return int 0 on success, other on failure

=cut

sub _buildDaemon
{
	unless(chdir "$FindBin::Bin/daemon") {
		error(sprintf('Unable to change dir to %s', "$FindBin::Bin/daemon"));
		return 1;
	}

	my $rs = execute('make clean imscp_daemon', \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Could not to build i-MSCP daemon') if $rs && !$stderr;
	return $rs if $rs;

	iMSCP::Dir->new( dirname => "$main::{'SYSTEM_ROOT'}/daemon" )->make();
	iMSCP::File->new( filename => 'imscp_daemon' )->copyFile("$main::{'SYSTEM_ROOT'}/daemon", { preserve => 1 });
}

=item _savePersistentData()

 Save persistent data

 Return int 0 on success, other on failure

=cut

sub _savePersistentData
{
	# Save Web directories skeletons

	# Remove deprecated phptmp directories
	for my $skelDir('alias', 'subdomain') {
		iMSCP::Dir->new( dirname => "$main::imscpConfig{'CONF_DIR'}/apache/skel/$skelDir/phptmp" )->remove();
	}

	# Move skel directory to new location
	if(-d "$main::imscpConfig{'CONF_DIR'}/apache/skel") {
		iMSCP::Dir->new( dirname => "$main::imscpConfig{'CONF_DIR'}/apache/skel" )->moveDir(
			"$main::imscpConfig{'CONF_DIR'}/skel"
		);
	}

	# Save skel directory
	if(-d "$main::imscpConfig{'CONF_DIR'}/skel") {
		iMSCP::Dir->new( dirname => "$main::imscpConfig{'CONF_DIR'}/skel" )->rcopy(
			"$main::{'INST_PREF'}$main::imscpConfig{'CONF_DIR'}/skel"
		);
	}

	# Move listener files to new location
	if(-d "$main::imscpConfig{'CONF_DIR'}/hooks.d") {
		iMSCP::Dir->new( dirname => "$main::imscpConfig{'CONF_DIR'}/hooks.d")->moveDir(
			"$main::imscpConfig{'CONF_DIR'}/listeners.d"
		);
	}

	# Remove old README file
	if(-f "$main::imscpConfig{'CONF_DIR'}/listeners.d/README") {
		my $rs = iMSCP::file->new( filename => "$main::imscpConfig{'CONF_DIR'}/listeners.d/README" )->delFile();
		return $rs if $rs;
	}

	# Move package cache directory to new location
	if(-d  "$main::imscpConfig{'CACHE_DATA_DIR'}/addons") {
		iMSCP::Dir->new( dirname => "$main::imscpConfig{'CACHE_DATA_DIR'}/addons")->moveDir(
			"$main::imscpConfig{'CACHE_DATA_DIR'}/packages"
		);
	}

	# Move ISP logos to new location
	if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/themes/user_logos") {
		iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/gui/themes/user_logos" )->rcopy(
			"$main::{'INST_PREF'}$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent/ispLogos"
		);
	}

	# Save ISP logos
	if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/data/ispLogos") {
		iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/gui/data/ispLogos" )->rcopy(
			"$main::{'INST_PREF'}$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent/ispLogos"
		);
	}

	# Save logs
	if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/data/logs") {
		iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/gui/data/logs" )->rcopy(
			"$main::{'INST_PREF'}$main::imscpConfig{'ROOT_DIR'}/gui/data/logs"
		);
	}

	# Save persistent data
	if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent") {
		iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent" )->rcopy(
			"$main::{'INST_PREF'}$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent"
		);
	}

	# Move software to new location
	if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/data/softwares") {
		iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/gui/data/softwares" )->rcopy(
			"$main::{'INST_PREF'}$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent/softwares"
		);
	}

	# Save plugins
	if(-d $main::imscpConfig{'PLUGINS_DIR'}) {
		iMSCP::Dir->new( dirname => "$main::imscpConfig{'PLUGINS_DIR'}" )->rcopy(
			"$main::{'INST_PREF'}$main::imscpConfig{'PLUGINS_DIR'}"
		);
	}

	# Quick fix for #IP-1340 (Removes old filemanager directory which is no longer used)
	iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/gui/public/tools/filemanager" )->remove();

	# Save tools
	if(-d "$main::imscpConfig{'ROOT_DIR'}/gui/public/tools") {
		iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/gui/public/tools" )->rcopy(
			"$main::{'INST_PREF'}$main::imscpConfig{'ROOT_DIR'}/gui/public/tools"
		);
	}

	0;
}

=item _installFiles()

 Install files from build directory

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
	my $serviceMngr = iMSCP::Service->getInstance();

	# i-MSCP daemon must be stopped before changing any file on the files system
	if($serviceMngr->isRunning('imscp_daemon')) {
		$serviceMngr->stop('imscp_daemon');
	}

	# Process cleanup before copying newest files
	iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/$_" )->remove() for qw/daemon engine gui/;

	# Install new files
	iMSCP::Dir->new( dirname => $main::{'INST_PREF'} )->rcopy('/', 1);
}

=item _deleteBuildDir()

 Delete build directory

 Return int 0 on success, other on failure

=cut

sub _deleteBuildDir
{
	iMSCP::Dir->new( dirname => $main::{'INST_PREF'})->remove() if $main::{'INST_PREF'};
}

=item _processXmlFile($xmlFilePath)

 Process all elements from the given XML file

 Param string $xmlFilePath Path of XML file to be processed
 Return int 0 on success, die on failure

=cut

sub _processXmlFile
{
	my ($xmlFilePath, $distribution) = (shift, lc(iMSCP::LsbRelease->getInstance()->getId(1)));
	my $workingDir = dirname($xmlFilePath);

	my $xmlElements = eval {
		require XML::Simple;
		XML::Simple->new( ForceArray => 1, ForceContent => 1 )->XMLin( $xmlFilePath, VarAttr => 'export' );
	} or die(
		sprintf('Could not parse the %s file: %s', $xmlFilePath, $@)
	);

	# Process xml 'create_dir' elements
	for my $xmlElement(@{$xmlElements->{'create_dir'}}) {
		$xmlElement->{'content'} = _expandVars($xmlElement->{'content'}, $xmlElement->{'export'});
		_createDirFromXmlElement($xmlElement);
	}

	# Process xml 'copy_confdir' elements
	for my $xmlElement(@{$xmlElements->{'copy_confdir'}}) {
		$xmlElement->{'content'} = _expandVars($xmlElement->{'content'});
		_copyConfdirFromXmlElement($workingDir, $xmlElement, $distribution);
	}

	# Process xml 'copy_conffile' elements
	for my $xmlElement(@{$xmlElements->{'copy_conffile'}}) {
		$xmlElement->{'content'} = _expandVars($xmlElement->{'content'});
		_copyConffileFromXmlElement($workingDir, $xmlElement, $distribution);
	}

	# Process xml 'copy_dir' elements
	for my $xmlElement(@{$xmlElements->{'copy_dir'}}) {
		$xmlElement->{'content'} = _expandVars($xmlElement->{'content'});
		_copyDirFromXmlElement($workingDir, $xmlElement);
	}

	# Process xml 'copy_file' elements
	for my $xmlElement(@{$xmlElements->{'copy_file'}}) {
		$xmlElement->{'content'} = _expandVars($xmlElement->{'content'});
		_copyFileFromXmlElement($workingDir, $xmlElement);
	}

	0;
}

=item _expandVars($string [, $exportIn ])

 Expands all variables in the given string and optionally, exports the resulting string in a new variable

 Param string $exportIn OPTIONAL Name of variable into which the resulting string be exported
 Param string $string string containing variables to expands
 Return string

=cut

sub _expandVars
{
	my ($string, $exportIn) = @_;

	for my $var($string =~ /\$\{([^\}]+)\}/g) {
		if(exists $main::{$var}) {
			$string =~ s/\$\{$var\}/$main::{$var}/g;
		} elsif(exists $main::imscpConfig{$var}) {
			$string =~ s/\$\{$var\}/$main::imscpConfig{$var}/g;
		} else {
			fatal("Could not expand the \${$var} variable. Variable not found.");
		}
	}

	$main::{$exportIn} = $string if $exportIn;

	$string;
}

=item _createDirFromXmlElement(\%element)

 Create a directory as described by given XML element

 Param \%element XML element
 Return int 0 on success, die on failure

=cut

sub _createDirFromXmlElement
{
	my $element = shift;

	my $dirH = iMSCP::Dir->new( dirname => $element->{'content'} );

	# Needed to be sure to not keep any file from a previous build that has failed
	if(defined $main::{'INST_PREF'} && $main::{'INST_PREF'} eq $element->{'content'}) {
		$dirH->remove();
	}

	my $options = { };
	$options->{'user'} = _expandVars($element->{'owner'}) if $element->{'owner'};
	$options->{'group'} = _expandVars($element->{'group'}) if $element->{'group'};
	$options->{'mode'} = oct($element->{'mode'}) if $element->{'mode'};

	$dirH->make($options);
}

=item _copyConfdirFromXmlElement($workingDir, $distribution, \%element)

 Copy a configuration directory as described by the given XML element

 Param string $workingDir Working directory
 Param hash \%element XML element describing the configuration directory to be copied
 Param string $distribution Distribution identifer
 Return int 0 on success, die on failure

=cut

sub _copyConfdirFromXmlElement
{
	my ($workingDir, $element, $distribution) = @_;

	my $target = $element->{'content'};
	my $targetBasename = basename($target);
	my $source = "$workingDir/$targetBasename";

	unless(-d $source) {
		(my $defaultConfigFolder = $workingDir) =~ s/$distribution/debian/;
		$source = "$defaultConfigFolder/$targetBasename";
	}

	iMSCP::Dir->new( dirname => $source )->rcopy($target);
}

=item _copyConffileFromXmlElement($workingDir, \%element, $distribution)

 Copy a configuration file as described by the given XML element

 Param string $workingDir Working directory
 Param hash \%element XML element describing the configuration directory to be copied
 Param string $distribution Distribution identifer
 Return int 0 on success, die on failure

=cut

sub _copyConffileFromXmlElement
{
	my ($workingDir, $element, $distribution) = @_;

	my $target = $element->{'content'};
	my $targetBasename = basename($target);
	my $source = "$workingDir/$targetBasename";

	unless(-f $source) {
		(my $defaultConfigFolder = $workingDir) =~ s/$distribution/debian/;
		$source = "$defaultConfigFolder/$targetBasename";
	}

	my $rs = iMSCP::File->new( filename => $source )->copyFile($target, { preserve => 'no' });
	return $rs if $rs;

	if($element->{'user'} || $element->{'group'} || $element->{'mode'}) {
		my $file = iMSCP::File->new( filename => $target );
		$rs = $file->mode(oct($element->{'mode'})) if $element->{'mode'};
		return $rs if $rs;

		if($element->{'user'} || $element->{'group'}) {
			$rs = $file->owner(
				$element->{'user'} ? _expandVars($element->{'user'}) : -1,
				$element->{'group'} ? _expandVars($element->{'group'}) : -1
			);
			return $rs if $rs;
		}
	}

	0;
}

=item _copyDirFromXmlElement($workingDir, \%element)

 Copy a directory as described by the given XML element

 Param string $workingDir Working directory
 Param \%element XML element describing the configuration directory to be copied
 Return int 0 on success, die on failure

=cut

sub _copyDirFromXmlElement
{
	my ($workingDir, $element) = @_;

	my $target = $element->{'content'};
	my $targetBasename = basename($target);
	my $source = "$workingDir/$targetBasename";

	iMSCP::Dir->new( dirname => $source )->rcopy($target);
}

=item _copyFileFromXmlElement($workingDir, \%element)

 Copy a file as described by the given XML element

 Param string $workingDir Working directory
 Param hash \%element XML element describing the configuration directory to be copied
 Param string $distribution Distribution identifer
 Return int 0 on success, die on failure

=cut

sub _copyFileFromXmlElement
{
	my ($workingDir, $element, $distribution) = @_;

	my $target = $element->{'content'};
	my $targetBasename = basename($target);
	my $source = "$workingDir/$targetBasename";

	my $rs = iMSCP::File->new( filename => $source )->copyFile($target, { preserve => 'no' });
	return $rs if $rs;

	if($element->{'user'} || $element->{'group'} || $element->{'mode'}) {
		my $file = iMSCP::File->new( filename => $target );
		$rs = $file->mode(oct($element->{'mode'})) if $element->{'mode'};
		return $rs if $rs;

		if($element->{'user'} || $element->{'group'}) {
			$rs = $file->owner(
				$element->{'user'} ? _expandVars($element->{'user'}) : -1,
				$element->{'group'} ? _expandVars($element->{'group'}) : -1
			);
			return $rs if $rs;
		}
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

		fatal(sprintf('Unable to instantiate %s autoinstaller adapter: %s', $distribution, $@)) if $@;
	}

	$autoinstallerAdapterInstance;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
