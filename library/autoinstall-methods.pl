#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010 - 2011 by internet Multi Server Control Panel
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
# @copyright	2010 - 2011 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

#####################################################################################
# File description:
#
# This file contains all subroutines used by the imscp-autoinstal script.
#

use strict;
use warnings;

#
# Hight level subroutines
#

# Install pre-required packages.
#
# Ensure that 'lsb-release' and 'dialog' tools are installed on the system.
#
# @return int 0 on success, other on failure
sub preInstall {
	debug((caller(0))[3] . ': Starting...');

	use iMSCP::Execute;

	my ($rs, $stdout, $stderr);

	fatal((caller(0))[3] . ': Not a Debian like system') if(_checkPkgManager() != 0);

	my @pkg = ();
	push @pkg, 'lsb_release' if(execute("which lsb_release", \$stdout, \$stderr));
	push @pkg, 'dialog' if(execute("which dialog", \$stdout, \$stderr));


	if(scalar @pkg){
		$rs = execute("apt-get -y install @pkg", \$stdout, \$stderr);
		debug((caller(0))[3] . ": $stdout") if $stdout;
		error((caller(0))[3] . ": $stderr") if $stderr;
		error((caller(0))[3] . ": Unable to install the @pkg package(s)") if $rs && !$stderr;

		return $rs if $rs;
	}

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Installs i-MSCP dependencies (required libraries, tools and softwares).
#
# This subroutine load specific distribution autoinstall class that is responsible to
# install all dependencies for i-MSCP.
#
# @return int 0 on success, other on failure
sub installDependencies {
	debug((caller(0))[3] . ': Starting...');

	my $autoinstallFile = "$FindBin::Bin/library/" .
		lc(iMSCP::SO->new()->{Distribution}) .'_autoinstall.pm';

	my $class = 'library::' . lc(iMSCP::SO->new()->{Distribution}) . '_autoinstall';

	if(-f $autoinstallFile){
		require $autoinstallFile ;
		$main::autoInstallClass = $class->new();
		my $rs = $main::autoInstallClass->preBuild() if $main::autoInstallClass->can('preBuild');
		return $rs if $rs;
	}

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Tests for i-MSCP requirements.
#
# @throw fatal error if a requirement is not meet
# @See Requirements.pm
# @return int 0
sub testRequirements {
	debug((caller(0))[3] . ': Starting...');

	iMSCP::Requirements->new()->test('all');

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Process all xml nodes from an install.xml files.
#
# Note: If $conffile is not provided, the subroutine search for the
# DISTNAME-variable.xml file.
#
# @throwns fatal error if a variable cannot be exported
# @param string $conffile OPTIONAL XML configuration file path to be processed
# @return int 0 on success, other on failure
# @todo The chown nodes are not processed...
sub processConfFile {

	debug((caller(0))[3] . ': Starting...');

	use iMSCP::SO;

	my $confFile = shift;

	$confFile = "$FindBin::Bin/library/" . lc(iMSCP::SO->new()->{Distribution}) .
		'-variable.xml' unless $confFile;

	unless(-f $confFile) {
		error((caller(0))[3] . ": Error $conffile not found");
		return 1;
	}

	# Creating XML object
	my $xml = XML::Simple->new(ForceArray => 1, ForceContent => 1);

	# Reading XML file
	my $data = eval { $xml->XMLin($confFile, VarAttr => 'export') };

	if ($@) {
		error((caller(0))[3] . ": $@");
		return 1;
	}

	my $rs;

	# Process xml 'folders' nodes
	foreach(@{$data->{folders}}) {
		$_->{content} = expandVars($_->{content}) if($_->{content});
		eval("our \$" . $_->{export} . " = \"" . $_->{content} . "\";") if($_->{export});
		fatal((caller(0))[3] . ": $@") if($@);
		return $rs if $rs;

		$rs = _processFolder($_) if($_->{content});
		return $rs if $rs;
	}

	# Process xml 'copy_config' nodes
	foreach(@{$data->{copy_config}}) {
		$_->{content} = _expandVars($_->{content}) if($_->{content});
		$rs = _copyConfig($_) if($_->{content});
		return $rs if $rs;
	}

	# process xml 'copy' nodes
	foreach(@{$data->{copy}}) {
		$_->{content} = _expandVars($_->{content}) if($_->{content});
		$rs = _copy($_) if($_->{content});
		return $rs if $rs;
	}

	# process xml 'create_file' nodes (Doesn't work for now - See the _createFile subroutine)
	foreach(@{$data->{create_file}}) {
		$_->{content} = _expandVars($_->{content}) if($_->{content});
		$rs = _createFile($_) if($_->{content});
		return $rs if $rs;
	}

	# process xml 'chmod_file' nodes
	foreach(@{$data->{chmod_file}}) {
		$_->{content} = _expandVars($_->{content}) if($_->{content});
		$rs = _chmodFile($_) if($_->{content});
		return $rs if $rs;
	}

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Process distribution specific configuration install.xml files.
#
# @see processConfFile
# @return int 0 on success, other on failure
sub processSpecificConfFile {
	debug((caller(0))[3] . ': Starting...');

	use iMSCP::Dir;
	use iMSCP::SO;

	my $SO = iMSCP::SO->new();
	my $specificPath = "$FindBin::Bin/configs/" . lc($SO->{Distribution});
	my $commonPath = "$FindBin::Bin/configs/debian";
	my $path = -d $specificPath ? $specificPath : $commonPath;

	unless(chdir($path)){
		error((caller(0))[3] . ": Unable to change path to $path: $!");
		return 1;
	}

	my $file = -f "$specificPath/install.xml"
		? "$specificPath/install.xml" : "$commonPath/install.xml";

	my $rs = processConfFile($file);
	return $rs if $rs;

	my $dir = iMSCP::Dir->new();

	# /configs/debian
	$dir->{dirname} = $commonPath;


	my @configs = $dir->getDirs();

	foreach(@configs){
		if($_ eq '.svn'){
			# Review recommendations by nuxwin:
			# If the system is able to manage this without any problems, I recommends
			# to remove the warning that become useless and can disturb some users...
			warning("You should remove .svn folders (you can ignore this, we will take care for this)");
			next;
		}

		$path = -d "$specificPath/$_" ? "$specificPath/$_" : "$commonPath/$_";

		unless(chdir($path)){
			error((caller(0))[3] . ": Can not change path to $path: $!");
			return 1;
		}


		$file = -f "$specificPath/$_/install.xml"
			? "$specificPath/$_/install.xml" : "$commonPath/$_/install.xml";

		$rs = processConfFile($file);

		return $rs if $rs;
	}

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Build the i-MSCP daemon by running make.
#
# @return void
sub buildImscpDaemon {
	debug((caller(0))[3] . ': Starting...');

	unless(chdir "$FindBin::Bin/daemon"){
		error((caller(0))[3] . ": Unable to change path to $FindBin::Bin/daemon");
		return 1;
	}

	my ($rs, $stdout, $stderr);
	my $return = 0;

	$rs = execute("make clean imscp_daemon", \$stdout, \$stderr);
	debug((caller(0))[3] . ": $stdout") if $stdout;
	error((caller(0))[3] . ": $stderr") if $stderr;
	error((caller(0))[3] . ": Can not build daemon") if $rs;
	$return |= $rs;

	unless($rs) {
		my $dir = iMSCP::Dir->new();
		$dir->{dirname} = "$main::SYSTEM_ROOT/daemon";
		$dir->make() and return 1;

		my $file = iMSCP::File->new();
		$file->{filename} = 'imscp_daemon';
		$file->copyFile("$main::SYSTEM_ROOT/daemon");
	} else {
		error("Fail build daemon");
		return 1;
	}

	$rs = execute('make clean', \$stdout, \$stderr);
	debug((caller(0))[3] . ": $stdout") if $stdout;
	error((caller(0))[3] . ": $stderr") if $stderr;
	error((caller(0))[3] . ': Can not clean daemon artifacts') if $rs;
	$return |= $rs;

	debug((caller(0))[3] . ': Ending...');

	$return;
}

# Install the engine files by processing all install.xml files.
#
# @see processConfFile
# @return int 0 on success, other on failure
sub installEngine {
	debug((caller(0))[3] . ': Starting...');

	unless(chdir "$FindBin::Bin/engine"){
		error((caller(0))[3] . ": Cannot change path to $FindBin::Bin/engine");
		return 1;
	}

	my $rs = processConfFile("$FindBin::Bin/engine/install.xml");
	return $rs if $rs;

	my $dir = iMSCP::Dir->new();

	$dir->{dirname} = "$FindBin::Bin/engine";
	my @configs = $dir->getDirs();

	foreach(@configs){
		# Review recommendations by nuxwin:
		# If the system is able to manage this without any problems, I recommend to
		# remove the warning that become useless and can disturb some users...
		if($_ eq '.svn'){
			warning("You should remove .svn folders (you can ignore this, the program will take care of this.)");
			next;
		}

		if (-f "$FindBin::Bin/engine/$_/install.xml"){
			unless(chdir "$FindBin::Bin/engine/$_"){
				error((caller(0))[3].": Can not change path to $FindBin::Bin/engine/$_");
				return 1;
			}

			$rs = processConfFile("$FindBin::Bin/engine/$_/install.xml") ;
			return $rs if $rs;
		}
	}

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Install GUI files in temporary folder.
#
# @return int
sub installGui {
	debug((caller(0))[3] . ': Starting...');

	my ($rs, $stdout, $stderr);

	$rs = execute("cp -R $FindBin::Bin/gui $main::SYSTEM_ROOT", \$stdout, \$stderr);
	debug((caller(0))[3] . ": $stdout") if $stdout;
	error((caller(0))[3] . ": $stderr") if $stderr;
	debug((caller(0))[3] . ': Ending...');

	$rs;
}

# Install distribution maintainer scripts in temporary folder.
#
# Some distribution can require pre and post installation tasks managed by maintainers
# scripts (preinst.DISTNAME or postinst.DISTNAME) written in Shell, PHP or Perl.
# If a script is found for the current distribution, it will be intalled in the setup
# directory with the distribution maintainer helper library (for shell scripts).
#
# @return void
sub InstallDistMaintainerScripts {
	debug((caller(0))[3] . ': Starting...');

	my $SO = iMSCP::SO->new();
	my $dist = lc($SO->{Distribution});

	foreach(
		"$FindBin::Bin/maintscripts/preinst.$dist",
		"$FindBin::Bin/maintscripts/postinst.$dist"
	){
		next if (! -f $_);
		my $file = iMSCP::File->new();
		$file->{filename} = $_;
		$file->mode(0750) and return 1;
		$file->owner(0, 0) and return 1;
		$file->copyFile("$main::SYSTEM_ROOT/engine/setup/") and return 1;
	}

	if(-f "$FindBin::Bin/maintscripts/preinst.$SO->{Distribution}" ||
		-f "$FindBin::Bin/maintscripts/postinst.$SO->{Distribution}"
	) {
		my $file = iMSCP::File->new();
		$file->{filename} = "$FindBin::Bin/maintscripts/maintainer-helper.sh";
		$file->mode(0750) and return 1;
		$file->owner(0, 0) and return 1;
		$file->copyFile("$main::SYSTEM_ROOT/engine/setup/") and return 1;
	}

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Must be documented
#
# @return int 0 on success, other on failure
sub finishBuild {
	debug((caller(0))[3] . ': Starting...');

	my $rs = 0;
	$rs = $main::autoInstallClass->postBuild() if( defined $main::autoInstallClass &&
		$main::autoInstallClass->can('postBuild'));

	return $rs if $rs;

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Cleanup temporary folder by removing uselless directories (eg .svn).
#
# @return int 0 on success, other on failure
sub cleanUpTmp {
	debug((caller(0))[3] . ': Starting...');

	my $tmp = qualify_to_ref('INST_PREF', 'main');
	my ($rs, $stdout, $stderr);

	$rs = execute(
		"find $$$tmp -type d -name '.svn' -print0 |xargs -0 -r rm -fr",
		\$stdout, \$stderr
	);

	debug((caller(0))[3] . ": $stdout") if $stdout;
	error((caller(0))[3] . ": $stderr") if $stderr;

	return $rs if $rs;

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Process i-MSCP backup.
#
# @return int 0 on success, other on failure
sub doImscpBackup {
	debug((caller(0))[3].': Starting...');

	my ($rs, $stdout, $stderr);

	if( -x "$main::defaultConf{'ROOT_DIR'}/engine/backup/imscp-backup-imscp noreport") {
		$rs = execute(
			"$main::defaultConf{'ROOT_DIR'}/engine/backup/imscp-backup-imscp",
			\$stdout, \$stderr
		);

		debug((caller(0))[3] . ": $stdout") if $stdout;
		warning((caller(0))[3] . ": $stderr") if $stderr;
		error((caller(0))[3] . ': Could not create backups') if $rs;

		$rs = iMSCP::Dialog->factory()->yesno(
			"\n\n\\Z1Unable to create backups\\Zn\n\n".
			'This is not a fatal error, setup may continue, but '.
			"you will not have a backup (unless you have previously builded one)\n\n".
			'Do you want to continue?'
		) if $rs;
	}

	debug((caller(0))[3] . ': Ending...');

	$rs;
}

# Saves GUI working data in temporary folder.
#
# @return int 0 on success, other on failure
sub saveGuiWorkingData {
	debug((caller(0))[3] . ': Starting...');

	my ($rs, $stdout, $stderr);
	my $tmp = qualify_to_ref('INST_PREF', 'main');

	# For imscp versions >= 1.0.4
	if(-d "$main::defaultConf{'ROOT_DIR'}/gui/data") {
		# Save i-MSCP GUI data
		$rs = execute(
			"cp -vTRf $main::defaultConf{'ROOT_DIR'}/gui/data $$$tmp$main::defaultConf{'ROOT_DIR'}/gui/data",
			\$stdout, \$stderr
		);

		debug((caller(0))[3] . ": $stdout") if $stdout;
		error((caller(0))[3] . ": $stderr") if $stderr;
		return $rs if $rs;

		# Save webmail data (Squirrel)
		$rs = execute(
			"cp -vRTf $main::defaultConf{'ROOT_DIR'}/gui/public/tools/webmail/data $$$tmp$main::defaultConf{'ROOT_DIR'}/gui/public/tools/webmail/data",
			\$stdout, \$stderr
		);

		debug((caller(0))[3] . ": $stdout") if $stdout;
		error((caller(0))[3] . ": $stderr") if $stderr;
		return $rs if $rs;

	# For i-MSCP versions prior 1.0.4
	} elsif(-d "$main::defaultConf{'ROOT_DIR'}/gui/themes/user_logos") {
		# Save i-MSCP GUI data (isp logos)
		$rs = execute(
			"cp -TvRf $main::defaultConf{'ROOT_DIR'}/gui/themes/user_logos $$$tmp$main::defaultConf{'ROOT_DIR'}/gui/data/ispLogos",
			\$stdout, \$stderr
		);

		debug((caller(0))[3] . ": $stdout") if $stdout;
		error((caller(0))[3] . ": $stderr") if $stderr;
		return $rs if $rs;

		# Save webmail data (Squirrel)
		$rs = execute(
			"cp -RTvf $main::defaultConf{'ROOT_DIR'}/gui/tools/webmail/data $$$tmp$main::defaultConf{'ROOT_DIR'}/gui/public/tools/webmail/data",
			\$stdout, \$stderr
		);

		debug((caller(0))[3] . ": $stdout") if $stdout;
		error((caller(0))[3] . ": $stderr") if $stderr;
		return $rs if $rs;

		# Save i-MSCP GUI data (isp domain default index.html page)
		$rs = execute(
			"cp -TRfv $main::defaultConf{'ROOT_DIR'}/gui/domain_default_page $$$tmp$main::defaultConf{'ROOT_DIR'}/gui/data/domain_default_page",
			\$stdout, \$stderr
		);

		debug((caller(0))[3] . ": $stdout") if $stdout;
		error((caller(0))[3] . ": $stderr") if $stderr;
		return $rs if $rs;
	}

	0;
}

# Install temporary folder on file system.
#
# @return int 0 on success, other on failure
sub installTmp {
	debug((caller(0))[3] . ': Starting...');

	my ($rs, $stdout, $stderr);
	my $tmp = qualify_to_ref('INST_PREF', 'main');

	# i-MSCP daemon must be stopped before changing any file on the files system
	if(-f "/etc/init.d/imscp_daemon" && -f "$main::defaultConf{'ROOT_DIR'}/daemon/imscp_daemon") {
		$rs = execute("/etc/init.d/imscp_daemon stop", \$stdout, \$stderr);
		debug((caller(0))[3] . ": $stdout") if $stdout;
		error((caller(0))[3] . ": $stderr") if $stderr;
		return $rs if $rs;
	}

	# Session files must not be saved to prevent any troubles after upate.
	$rs = execute(
		"rm -fr $$$tmp$main::defaultConf{'ROOT_DIR'}/gui/data/sessions",
		\$stdout, \$stderr
	);

	debug((caller(0))[3] . ": $stdout") if $stdout;
	error((caller(0))[3] . ": $stderr") if $stderr;
	return $rs if $rs;

	# Process cleanup to avoid any security risks and conflicts
	$rs = execute(
		"rm -fr $main::defaultConf{'ROOT_DIR'}/{daemon,engine,gui}",
		\$stdout, \$stderr
	);

	debug((caller(0))[3] . ": $stdout") if $stdout;
	error((caller(0))[3] . ": $stderr") if $stderr;
	return $rs if $rs;

	# Copy new i-MSCP files on the files system
	$rs = execute("cp -Rf $$$tmp/* /", \$stdout, \$stderr);
	debug((caller(0))[3] . ": $stdout") if $stdout;
	error((caller(0))[3] . ": $stderr") if $stderr;
	return $rs if $rs;

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Running i-MSCP setup script.
#
# Note: The imscp-setup script is used for both setup and update process.
#
# @return int 0 on success, other otherwise
sub setup {
	debug((caller(0))[3] . ': Starting...');

	my ($rs, $stdout, $stderr);

	if( -x "$main::defaultConf{'ROOT_DIR'}/engine/setup/imscp-setup"){
		$rs = execute("$main::defaultConf{'ROOT_DIR'}/engine/setup/imscp-setup");
		error(
			(caller(0))[3] . ': ' .
			"Error while running $main::defaultConf{'ROOT_DIR'}/engine/setup/imscp-setup setup script\n\n".
			"Full log can be found $main::defaultConf{LOG_DIR}/imscp-setup.log"
		) if $rs;
		return $rs if $rs;
	} else {
		fatal((caller(0))[3] . ": Unable to find the $main::defaultConf{'ROOT_DIR'}/engine/setup/imscp-setup script");
		return 1;
	}

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Removes temporary folder.
#
# @return int 0 on success, other on failure
sub removeTmp {
	debug((caller(0))[3] . ': Starting...');

	my ($rs, $stdout, $stderr);
	my $tmp = qualify_to_ref('INST_PREF', 'main');

	if($$$tmp && -d $$$tmp){
		$rs = execute("rm -fr $$$tmp", \$stdout, \$stderr);
		debug((caller(0))[3] . ": $stdout") if $stdout;
		error((caller(0))[3] . ": $stderr") if $stderr;
		return $rs if $rs;
	}

	debug((caller(0))[3] . ': Ending...');

	0;
}

#
# Low level subroutines
#

# Expands a variable.
#
# @param string $var variable to be expanded
# @return string expanded variable
sub _expandVars {
	debug((caller(0))[3] . ': Starting...');

	my $var = shift;

	use Symbol;

	debug((caller(0))[3] . ": Input... $var");

	if($var =~ m/\$\{([^\}]{1,})\}/g) {
		my $x = qualify_to_ref("$1");
		$var =~ s/\$\{$1\}/$$$x/g;
	}

	debug((caller(0))[3] . ": Expanded... $var");
	debug((caller(0))[3] . ': Ending...');

	$var;
}

# Process a 'folder' node from an install.xml file.
#
# Process the xml 'folder' node by creating the described directory
#
# @return int 0 on success, other on failure
sub _processFolder {
	debug((caller(0))[3] . ': Starting...');

	my $data = shift;

	use iMSCP::Dir;

	my $dir  = iMSCP::Dir->new();
	$dir->{dirname} = $data->{content};
	debug((caller(0))[3] . ": Create $dir->{dirname}");

	my $options = {};

	$options->{mode} = oct($data->{mode}) if($data->{mode});
	$options->{user} = expandVars($data->{owner}) if($data->{owner});
	$options->{group} = expandVars($data->{group}) if($data->{group});
	debug $options->{group} if $options->{group};

	my $rs = $dir->make($options);
	return $rs if $rs;

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Process a 'copy_config' node from an install.xml file.
#
# @return int 0 on success, other on failure
sub _copyConfig {
	debug((caller(0))[3] . ': Starting...');

	use Cwd;
	use iMSCP::SO;
	use iMSCP::Execute;
	use iMSCP::File;

	my $SO = iMSCP::SO->new();

	my $data = shift;

	my @parts = split '/', $data->{content};
	my $name = pop(@parts);
	my $path = join '/', @parts;

	my $distro = lc($SO->{Distribution});

	my $alternativeFolder = my $currentFolder = getcwd(); /upstream
	$alternativeFolder =~ s!\/$distro!\/debian!;

	my $source = -e $name ? $name : "$alternativeFolder/$name";

	debug((caller(0))[3] . ": Copy recursive $source in $path");

	my ($rs, $stdout, $stderr);
	$rs = execute("cp -R $source $path", \$stdout, \$stderr);
	debug((caller(0))[3] . ": $stdout") if $stdout;
	error((caller(0))[3] . ": $stderr") if $stderr;

	return $rs if $rs;

	if($data->{user} || $data->{group} || $data->{mode}) {
		my $filename = -e "$path/$name" ? "$path/$name" : $path;

		my $file = iMSCP::File->new(filename => $filename);
		$file->mode(oct($data->{mode})) and return 1 if $data->{mode};

		$file->owner(
			$data->{user} ? $data->{user} : -1,
			$data->{group} ? $data->{group} : -1
		)  and return 1 if($data->{user} || $data->{group});
	}

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Process the 'copy' node from an install.xml file.
#
# @return int 0 on success, other on failure
sub _copy {
	debug((caller(0))[3] . ': Starting...');

	use iMSCP::Execute;
	use iMSCP::File;

	my $data = shift;
	my @parts = split '/', $data->{content};
	my $name = pop(@parts);
	my $path = join '/', @parts;

	debug((caller(0))[3] . ": Copy recursive $name in $path");

	my ($rs, $stdout, $stderr);
	$rs = execute("cp -R $name $path", \$stdout, \$stderr);
	debug((caller(0))[3] . ": $stdout") if $stdout;
	error((caller(0))[3] . ": $stderr") if $stderr;
	return $rs if $rs;

	if($data->{user} || $data->{group} || $data->{mode}){

		my $filename = -e "$path/$name" ? "$path/$name" : $path;

		my $file = iMSCP::File->new(filename => $filename);
		$file->mode(oct($data->{mode})) and return 1 if $data->{mode};
		$file->owner(
			$data->{user} ? $data->{user} : -1,
			$data->{group} ? $data->{group} : -1
		)  and return 1 if($data->{user} || $data->{group});

	}

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Create a file
#
# @param XML object $data XML create_file node
# @return int 0 on success, other on failure
sub _createFile {
	debug((caller(0))[3] . ': Starting...');

	my $data = shift;
	use Data::Dumper;
	fatal('a'.Dumper($data));

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Change file/directory owner and/or group recursively.
#
# @param XML object $data XML chown_file node
# @return int 0 on success, other on failure
sub _chownFile {
	debug((caller(0))[3] . ': Starting...');

	my $data = shift;

	if($data->{owner} || $data->{group}){
		my ($rs, $stdout, $stderr);
		$rs = execute("chmod -R $data->{mode} $data->{content}", \$stdout, \$stderr);
		debug((caller(0))[3] . ": $stdout") if $stdout;
		error((caller(0))[3] . ": $stderr") if $stderr;
		return $rs if $rs;
	}

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Process chmod_file from an install.xml file.
#
# @return int 0 on success, other on failure
sub _chmodFile {
	debug((caller(0))[3] . ': Starting...');

	my $data = shift;

	if($data->{mode}) {
		my ($rs, $stdout, $stderr);
		$rs = execute("chmod -R $data->{mode} $data->{content}", \$stdout, \$stderr);
		debug((caller(0))[3] . ": $stdout") if $stdout;
		error((caller(0))[3] . ": $stderr") if $stderr;
		return $rs if $rs;
	}

	debug((caller(0))[3] . ': Ending...');

	0;
}

# Checks for debian packager availability.
#
# @access private
# @return int 0 on success, other on failure
sub _checkPkgManager {
	debug((caller(0))[3] . ': Starting...');

	use iMSCP::Execute;

	my ($rs, $stdout, $stderr);

	debug((caller(0))[3] . ': Ending...');

	return execute('which apt-get', \$stdout, \$stderr);
}

1;
