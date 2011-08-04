#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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

use strict;
use warnings;

# builddaemon
#
# Build daemon
# @return	void
sub builddaemon {
	debug((caller(0))[3].': Starting...');

	unless(chdir "$FindBin::Bin/daemon"){
		error((caller(0))[3].": Can not change path to $FindBin::Bin/daemon");
		return 1;
	}

	my ($rs, $stdout, $stderr);
	my $return = 0;

	$rs = execute("make clean imscp_daemon", \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout") if $stdout;
	error((caller(0))[3].": $stderr") if $stderr;
	error((caller(0))[3].": Can not build daemon") if $rs;
	$return |= $rs;

	unless($rs){
		my $dir = iMSCP::Dir->new();
		$dir->{dirname} = "$main::SYSTEM_ROOT/daemon";
		$dir->make() and return 1;

		my $file =  iMSCP::File->new();
		$file->{filename} = 'imscp_daemon';
		$file->copyFile("$main::SYSTEM_ROOT/daemon");
	} else {
		error("Fail build daemon");
		return 1;
	}

	$rs = execute("make clean", \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout") if $stdout;
	error((caller(0))[3].": $stderr") if $stderr;
	error((caller(0))[3].": Can not clean daemon artifacts") if $rs;
	$return |= $rs;

	debug((caller(0))[3].': Ending...');
	$return;
}

# engine
#
# Parse engine folders and if file named install is found file is proccesed
# @return	void
# @see process_install
sub engine {
	debug((caller(0))[3].': Starting...');

	unless(chdir "$FindBin::Bin/engine"){
		error((caller(0))[3].": Can not change path to $FindBin::Bin/engine");
		return 1;
	}

	my $rs = processConfFile("$FindBin::Bin/engine/install.xml");
	return $rs if $rs;

	my $dir = iMSCP::Dir->new();

	$dir->{dirname} = "$FindBin::Bin/engine";
	my @configs = $dir->getDirs();

	foreach(@configs){
		if($_ eq '.svn'){
			warning("You should remove .svn folders (you can ignore this, we will take care for this)");
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


	debug((caller(0))[3].': Ending...');
	0;
}

# gui
#
# Copy gui files
#
# @return	void
sub gui{
	debug((caller(0))[3].': Starting...');

	my ($rs, $stdout, $stderr);

	$rs = execute("cp -R $FindBin::Bin/gui $main::SYSTEM_ROOT", \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout") if $stdout;
	error((caller(0))[3].": $stderr") if $stderr;

	debug((caller(0))[3].': Ending...');
	$rs;
}

# maintainer
#
# Copy maintainer script for current distribution if exists
# @return	void
sub maintainer {

	debug((caller(0))[3].': Starting...');
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

	if(
			-f "$FindBin::Bin/maintscripts/preinst.$SO->{Distribution}"
		||
			-f "$FindBin::Bin/maintscripts/postinst.$SO->{Distribution}"
	){
		my $file = iMSCP::File->new();
		$file->{filename} = "$FindBin::Bin/maintscripts/maintainer-helper.sh";
		$file->mode(0750) and return 1;
		$file->owner(0, 0) and return 1;
		$file->copyFile("$main::SYSTEM_ROOT/engine/setup/") and return 1;
	}

	debug((caller(0))[3].': Ending...');
	0;

}

sub processSpecificConfFile{
	debug((caller(0))[3].': Starting...');

	use iMSCP::Dir;
	use iMSCP::SO;

	my $SO = iMSCP::SO->new();

	my $specificPath	= "$FindBin::Bin/configs/".lc($SO->{Distribution});
	my $commonPath		="$FindBin::Bin/configs/debian";

	my $path = -d $specificPath ? $specificPath : $commonPath;
	unless(chdir($path)){
		error((caller(0))[3].": Can not change path to $path:  $!");
		return 1;
	}

	my $file = -f "$specificPath/install.xml" ? "$specificPath/install.xml" : "$commonPath/install.xml";
	my $rs = processConfFile($file);
	return $rs if $rs;

	my $dir = iMSCP::Dir->new();

	$dir->{dirname} = $commonPath;
	my @configs = $dir->getDirs();

	foreach(@configs){
		if($_ eq '.svn'){
			warning("You should remove .svn folders (you can ignore this, we will take care for this)");
			next;
		}
		$path = -d "$specificPath/$_" ? "$specificPath/$_" : "$commonPath/$_";
		unless(chdir($path)){
			error((caller(0))[3].": Can not change path to $path: $!");
			return 1;
		}
		$file = -f "$specificPath/$_/install.xml" ? "$specificPath/$_/install.xml" : "$commonPath/$_/install.xml";
		$rs = processConfFile($file);
		return $rs if $rs;
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub processConfFile{

	debug((caller(0))[3].': Starting...');

	use iMSCP::SO;

	my $confile = shift;

	$confile = "$FindBin::Bin/library/".lc(iMSCP::SO->new()->{Distribution}).'-variable.xml' unless $confile;

	unless( -f $confile){
		error((caller(0))[3].": Error $confile not found");
		return 1;
	}

	# create object
	my $xml = XML::Simple->new(ForceArray => 1, ForceContent => 1);

	# read XML file
	my $data = eval { $xml->XMLin($confile, VarAttr => 'export') };
	if ($@){
		error((caller(0))[3].": $@");
		return 1;
	}

	my $rs;

	foreach(@{$data->{folders}}){
		$_->{content} = expandVars($_->{content}) if($_->{content});

		eval("our \$".$_->{export}." = \"".$_->{content}."\";") if($_->{export});
		fatal((caller(0))[3].": $@")	if($@);
		return $rs if $rs;

		$rs = processFolder($_) if($_->{content});
		return $rs if $rs;
	}

	foreach(@{$data->{copy_config}}){
		$_->{content} = expandVars($_->{content}) if($_->{content});
		$rs = copy_config($_) if($_->{content});
		return $rs if $rs;
	}

	foreach(@{$data->{copy}}){
		$_->{content} = expandVars($_->{content}) if($_->{content});
		$rs = copy($_) if($_->{content});
		return $rs if $rs;
	}

	foreach(@{$data->{create_file}}){
		$_->{content} = expandVars($_->{content}) if($_->{content});
		$rs = create_file($_) if($_->{content});
		return $rs if $rs;
	}

	foreach(@{$data->{chmod_file}}){
		$_->{content} = expandVars($_->{content}) if($_->{content});
		$rs = chmod_file($_) if($_->{content});
		return $rs if $rs;
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub expandVars{

	debug((caller(0))[3].': Starting...');

	my $var = shift;

	use Symbol;

	debug((caller(0))[3].": Input... $var");

	if($var =~ m/\$\{([^\}]{1,})\}/g){
		my $x = qualify_to_ref("$1");
		$var =~ s/\$\{$1\}/$$$x/g;
	}

	debug((caller(0))[3].": Expanded... $var");

	debug((caller(0))[3].': Ending...');
	$var;
}

sub processFolder{
	debug((caller(0))[3].': Starting...');

	my $data = shift;

	use iMSCP::Dir;

	my $dir  = iMSCP::Dir->new();
	$dir->{dirname} = $data->{content};
	debug((caller(0))[3].": Create $dir->{dirname}");

	my $options = {};

	$options->{mode}	= oct($data->{mode}) if($data->{mode});
	$options->{user}	= expandVars($data->{owner}) if($data->{owner});
	$options->{group}	= expandVars($data->{group}) if($data->{group});
	debug $options->{group} if $options->{group};

	my $rs = $dir->make($options);
	return $rs if $rs;

	debug((caller(0))[3].': Ending...');
	0;
}

sub copy_config{
	debug((caller(0))[3].': Starting...');

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

	my $alternativeFolder = my $currentFolder = getcwd();
	$alternativeFolder =~ s!\/$distro!\/debian!;

	my $source = -e $name ? $name : "$alternativeFolder/$name";

	debug((caller(0))[3].": Copy recursive $source in $path");

	my ($rs, $stdout, $stderr);
	$rs = execute("cp -R $source $path", \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout") if $stdout;
	error((caller(0))[3].": $stderr") if $stderr;
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

	debug((caller(0))[3].': Ending...');
	0;
}

sub copy{
	debug((caller(0))[3].': Starting...');

	use iMSCP::Execute;
	use iMSCP::File;

	my $data = shift;

	my @parts = split '/', $data->{content};
	my $name = pop(@parts);
	my $path = join '/', @parts;

	debug((caller(0))[3].": Copy recursive $name in $path");

	my ($rs, $stdout, $stderr);
	$rs = execute("cp -R $name $path", \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout") if $stdout;
	error((caller(0))[3].": $stderr") if $stderr;
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

	debug((caller(0))[3].': Ending...');
	0;
}

sub create_file{
	debug((caller(0))[3].': Starting...');

	my $data = shift;
	use Data::Dumper;
	fatal('a'.Dumper($data));

	debug((caller(0))[3].': Ending...');
	0;
}

sub chown_file{
	debug((caller(0))[3].': Starting...');

	my $data = shift;
	if($data->{owner} || $data->{group}){
		my ($rs, $stdout, $stderr);
		$rs = execute("chmod -R $data->{mode} $data->{content}", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if $stderr;
		return $rs if $rs;
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub chmod_file{
	debug((caller(0))[3].': Starting...');

	my $data = shift;

	if($data->{mode}){
		my ($rs, $stdout, $stderr);
		$rs = execute("chmod -R $data->{mode} $data->{content}", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if $stderr;
		return $rs if $rs;
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub installDepends{
	debug((caller(0))[3].': Starting...');

	my $autoinstallFile = "$FindBin::Bin/library/".lc(iMSCP::SO->new()->{Distribution}).'_autoinstall.pm';
	my $class = 'iMSCP::'.lc(iMSCP::SO->new()->{Distribution}).'_autoinstall';

	if(-f $autoinstallFile){
		require $autoinstallFile ;
		$main::autoInstallClass = $class->new();
		my $rs = $main::autoInstallClass->preBuild() if $main::autoInstallClass->can('preBuild');
		return $rs if $rs;
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub finishBuild{
	debug((caller(0))[3].': Starting...');
	my $rs = 0;
	$rs = $main::autoInstallClass->postBuild() if( defined  $main::autoInstallClass && $main::autoInstallClass->can('postBuild'));
	return $rs if $rs;
	debug((caller(0))[3].': Ending...');
	0;
}

sub testRequirements{
	debug((caller(0))[3].': Starting...');
	iMSCP::Requirements->new()->test('all');
	debug((caller(0))[3].': Ending...');
	0;
}

sub setup{
	debug((caller(0))[3].': Starting...');

	my ($rs, $stdout, $stderr);

	if( -x "$main::defaultConf{'ROOT_DIR'}/engine/setup/imscp-setup"){
		$rs = execute("$main::defaultConf{'ROOT_DIR'}/engine/setup/imscp-setup");
		error(
			(caller(0))[3].": ".
			"Error while running $main::defaultConf{'ROOT_DIR'}/engine/setup/imscp-setup\n\n".
			"Full log can be found $main::defaultConf{LOG_DIR}/imscp-setup.log"
		) if $rs;
		return $rs if $rs;
	} else {
		fatal((caller(0))[3].": Can`t find $main::defaultConf{'ROOT_DIR'}/engine/setup/imscp-setup");
		return 1;
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub backup{
	debug((caller(0))[3].': Starting...');

	my ($rs, $stdout, $stderr);

	if( -x "$main::defaultConf{'ROOT_DIR'}/engine/backup/imscp-backup-imscp"){
		$rs = execute("$main::defaultConf{'ROOT_DIR'}/engine/backup/imscp-backup-imscp", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		warning((caller(0))[3].": $stderr") if $stderr;
		error((caller(0))[3].": Could not create backups") if $rs;
		$rs = iMSCP::Dialog->factory()->yesno(
			"\n\n\\Z1Could not create backups\\Zn\n\n".
			"This is not a fatal error, setup may continue, but ".
			"you will not have a backup (unless you already builded one)\n\n".
			"Do you want to continue?"
		) if $rs;
	}

	debug((caller(0))[3].': Ending...');
	$rs;
}

sub cleanUp{
	debug((caller(0))[3].': Starting...');
	my $tmp = qualify_to_ref('INST_PREF', 'main');
	my ($rs, $stdout, $stderr);
	$rs = execute("find $$$tmp -type d -name '.svn' -print0 |xargs -0 -r rm -fr", \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout") if $stdout;
	error((caller(0))[3].": $stderr") if $stderr;
	return $rs if $rs;

	debug((caller(0))[3].': Ending...');
	0;
}

sub saveCustom{
	debug((caller(0))[3].': Starting...');

	my ($rs, $stdout, $stderr);
	my $tmp = qualify_to_ref('INST_PREF', 'main');

	if(-f "/etc/init.d/imscp_daemon" && -f "$main::defaultConf{'ROOT_DIR'}/daemon/imscp_daemon"){
		$rs = execute("/etc/init.d/imscp_daemon stop", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if $stderr;
		return $rs if $rs;
	}

	if(-d "$main::defaultConf{'ROOT_DIR'}/gui/data"){
		$rs = execute("rm -fr $main::defaultConf{'ROOT_DIR'}/gui/data/sessions", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if $stderr;
		return $rs if $rs;

		$rs = execute("cp -Rf $main::defaultConf{'ROOT_DIR'}/gui/data $$$tmp$main::defaultConf{'ROOT_DIR'}/gui/", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if $stderr;
		return $rs if $rs;

		$rs = execute("cp -Rf $main::defaultConf{'ROOT_DIR'}/gui/public/tools/webmail/data $$$tmp$main::defaultConf{'ROOT_DIR'}/gui/public/tools/webmail/", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if $stderr;
		return $rs if $rs;

		$rs = execute("rm -fr $main::defaultConf{'ROOT_DIR'}/{daemon,engine,gui}", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if $stderr;
		return $rs if $rs;
	} elsif(-d "$main::defaultConf{'ROOT_DIR'}/gui/themes/user_logos"){
		$rs = execute("cp -Rf $main::defaultConf{'ROOT_DIR'}/gui/themes/user_logos/* $$$tmp$main::defaultConf{'ROOT_DIR'}/gui/themes/user_logos/", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if $stderr;
		return $rs if $rs;

		$rs = execute("cp -Rf $main::defaultConf{'ROOT_DIR'}/gui/tools/webmail/data/* $$$tmp$main::defaultConf{'ROOT_DIR'}/gui/tools/webmail/data/", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if $stderr;
		return $rs if $rs;

		$rs = execute("cp -TRf $main::defaultConf{'ROOT_DIR'}/gui/domain_default_page $$$tmp$main::defaultConf{'ROOT_DIR'}/gui/domain_default_page", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if $stderr;
		return $rs if $rs;

		$rs = execute("rm -fr $main::defaultConf{'ROOT_DIR'}/{daemon,engine,gui}", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if $stderr;
		return $rs if $rs;
	}

	$rs = execute("cp -Rf $$$tmp/* /", \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout") if $stdout;
	error((caller(0))[3].": $stderr") if $stderr;
	return $rs if $rs;

	debug((caller(0))[3].': Ending...');
	0;
}

sub cleanTMP{
	debug((caller(0))[3].': Starting...');

	my ($rs, $stdout, $stderr);
	my $tmp = qualify_to_ref('INST_PREF', 'main');

	if($$$tmp && -d $$$tmp){
		$rs = execute("rm -fr $$$tmp", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if $stderr;
		return $rs if $rs;
	}

	debug((caller(0))[3].': Ending...');
	0;
}
1;
