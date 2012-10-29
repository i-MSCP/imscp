#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010 - 2012 by internet Multi Server Control Panel
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
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

#####################################################################################
# Package description:
#
# This package provides a class that is responsible to install all dependencies
# (libraries, tools and softwares) required by i-MSCP on Debian like operating systems.
#

package library::debian_autoinstall;

use strict;
use warnings;

use iMSCP::Debug;
use Symbol;
use iMSCP::Execute qw/execute/;
use iMSCP::Dialog;

use vars qw/@ISA/;
@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

# Initializer.
#
# @param self $self iMSCP::debian_autoinstall instance
# @return int 0
sub _init {
	debug('Starting...');

	my $self = shift;

	$self->{nonfree} = 'non-free';

	debug('Ending...');

	0;
}

# Process pre-build tasks.
#
# @param self $self iMSCP::debian_autoinstall instance
# @return int 0 on success, other on failure
sub preBuild {

	debug('Starting...');

	my $self = shift;
	my $rs;

	$rs = $self->updateSystemPackagesIndex();
	return $rs if $rs;

	$rs = $self->preRequish();
	return $rs if $rs;

	$self->loadOldImscpConfigFile();

	$rs = $self->UpdateAptSourceList();
	return $rs if $rs;

	$rs = $self->readPackagesList();
	return $rs if $rs;

	$rs = $self->installPackagesList();
	return $rs if $rs;

	debug('Ending...');

	0;
}

# Updates system packages index from remote repository.
#
# @return int 0 on success, other on failure
sub updateSystemPackagesIndex {

	debug('Starting...');

	iMSCP::Dialog->factory()->infobox('Updating system packages index');

	my ($rs, $stdout, $stderr);

	$rs = execute('apt-get update', \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	error('Unable to update package index from remote repository') if $rs && !$stderr;
	return $rs if $rs;

	debug('Ending...');

	0;
}

# Installs pre-required packages.
#
# @param self $self iMSCP::debian_autoinstall instance
# @return int 0 on success, other on failure
sub preRequish {

	debug('Starting...');

	my $self = shift;

	#iMSCP::Dialog->factory()->infobox('Installing pre-required packages');

	my($rs, $stderr);

	$rs = execute('debconf-apt-progress -- apt-get -y install dialog libxml-simple-perl', undef, \$stderr);
	error("$stderr") if $stderr;
	error('Unable to install pre-required packages.') if $rs && ! $stderr;
	return $rs if $rs;

	# Force dialog now
	#iMSCP::Dialog->reset();

	debug('Ending...');

	0;
}

# Load old i-MSCP main configuration file.
#
# @return int 0
sub loadOldImscpConfigFile {

	debug('Starting...');

	use iMSCP::Config;

	$main::imscpConfigOld = {};

	my $oldConf = "$main::defaultConf{'CONF_DIR'}/imscp.old.conf";

	tie %main::imscpConfigOld, 'iMSCP::Config', 'fileName' => $oldConf, noerrors => 1 if (-f $oldConf);

	debug('Ending...');

	0;
}

# Process apt source list.
#
# This subroutine parse the apt source list file to ensure presence of the non-free
# packages availability. If non-free section is not already enabled, this method try
# to find in on the remote repository and add it to the current Debian repository URI.
#
# @param self $self iMSCP::debian_autoinstall instance
# @return int 0 on success, other on failure
sub UpdateAptSourceList {

	debug('Starting...');

	my $self = shift;

	use iMSCP::File;

	iMSCP::Dialog->factory()->infobox('Processing apt sources list');

	my $file = iMSCP::File->new(filename => '/etc/apt/sources.list');

	$file->copyFile('/etc/apt/sources.list.bkp') unless( -f '/etc/apt/sources.list.bkp');
	my $content = $file->get();

	unless ($content){
		error('Unable to read /etc/apt/sources.list file');
		return 1;
	}

	my ($foundNonFree, $needUpdate, $rs, $stdout, $stderr);

	while($content =~ /^deb\s+(?<uri>(?:https?|ftp)[^\s]+)\s+(?<distrib>[^\s]+)\s+(?<components>.+)$/mg){
		my %repos = %+;

		# is non-free repository available?
		unless($repos{'components'} =~ /\s?$self->{nonfree}(\s|$)/ ){
			my $uri = "$repos{uri}/dists/$repos{distrib}/$self->{nonfree}/";
			$rs = execute("wget --spider $uri", \$stdout, \$stderr);
			debug("$stdout") if $stdout;
			debug("$stderr") if $stderr;

			unless ($rs){
				$foundNonFree = 1;
				debug("Enabling non free section on $repos{uri}");
				$content =~ s/^($&)$/$1 $self->{nonfree}/mg;
				$needUpdate = 1;
			}
		} else {
			debug("Non free section is already enabled on $repos{uri}");
			$foundNonFree = 1;
		}

	}

	unless($foundNonFree){
		error('Unable to found repository that support non-free packages');
		return 1;
	}

	if($needUpdate){
		$file->set($content);
		$file->save() and return 1;

		$rs = $self->updateSystemPackagesIndex();
		return $rs if $rs;
	}

	debug('Ending...');

	0;
}

# Reads packages list to be installed.
#
# @param self $self iMSCP::debian_autoinstall instance
# @return int 0 on success, other on failure
sub readPackagesList {

	debug('Starting...');

	my $self = shift;
	my $SO = iMSCP::SO->new();
	my $conffile = "$FindBin::Bin/docs/" . ucfirst($SO->{Distribution}) . '/' . lc($SO->{Distribution}) . '-packages-' .
		lc($SO->{CodeName}) . '.xml';

	fatal(ucfirst($SO->{Distribution}) . " $SO->{CodeName} is not supported!") if (! -f  $conffile);

	eval "use XML::Simple; 1";

	fatal('Unable to load perl
	module XML::Simple...') if($@);

	my $xml = XML::Simple->new(NoEscape => 1);
	my $data = eval { $xml->XMLin($conffile, KeyAttr => 'name') };

	foreach(keys %{$data}){
		if(ref($data->{$_}) eq 'ARRAY'){
			$self->_parseArray($data->{$_});
		} else {
			if($data->{$_}->{alternative}){
				my $server  = $_;
				my @alternative = keys %{$data->{$server}->{alternative}};

				for (my $index = $#alternative; $index >= 0; --$index){
					my $defServer = $alternative[$index];
					my $oldServer = $main::imscpConfigOld{uc($server) . '_SERVER'};

					if($@){
						error("$@");
						return 1;
					}

					if($oldServer && $defServer eq $oldServer){
						splice @alternative, $index, 1 ;
						unshift(@alternative, $defServer);
						last;
					}
				}

				my $rs;

				do{
					$rs = iMSCP::Dialog->factory()->radiolist(
						"Choose server type to use for $server",
						@alternative,
						#uncoment after dependencies check is implemented
						#'Not Used'
					);
				} while (!$rs);

				$self->{userSelection}->{$server} = lc($rs) eq 'not used' ? 'no' : $rs;

				foreach(@alternative){
					delete($data->{$server}->{alternative}->{$_}) if($_ ne $rs);
				}
			}

			$self->_parseHash($data->{$_});
		}
	}

	debug('Ending...');

	0;
}

# Install Debian packages list required by i-MSCP.
#
# @param self $self iMSCP::debian_autoinstall instance
# @return in 0 on success, other on failure
sub installPackagesList {

	debug('Starting...');

	my $self = shift;

	#iMSCP::Dialog->factory()->infobox('Installing needed packages');

	my($rs, $stderr);

	$rs = execute("debconf-apt-progress -- apt-get -y install $self->{toInstall}", undef, \$stderr);
	error("$stderr") if $stderr && $rs;
	error('Can not install packages.') if $rs && ! $stderr;
	return $rs if $rs;

	debug('Ending...');

	0;
}

# Perfomr post-build tasks.
#
# @param self $self iMSCP::debian_autoinstall instance
# @return in 0 on success, other on failure
sub postBuild {

	debug('Starting...');

	my $self = shift;

	my $x = qualify_to_ref("SYSTEM_CONF", 'main');

	my $nextConf = $$$x . '/imscp.conf';
	tie %main::nextConf, 'iMSCP::Config', 'fileName' => $nextConf;

	$main::nextConf{uc($_) . "_SERVER"} = lc($self->{userSelection}->{$_}) foreach(keys %{$self->{userSelection}});

	debug('Ending...');

	0;
}

# Trim a string.
#
# @access private
# @param string $var String to be trimmed
# @return string
sub _trim {

	my $var = shift;
	$var =~ s/^\s+//;
	$var =~ s/\s+$//;
	$var;
}

# Parse hash.
#
# @access private
# @param self $self iMSCP::debian_autoinstall instance
# @param HASH $hash Hash to be parsed
# @return void
sub _parseHash {

	my $self = shift;
	my $hash = shift;

	foreach(values %{$hash}) {
		if(ref($_) eq 'HASH') {
			$self->_parseHash($_);
		} elsif(ref($_) eq 'ARRAY') {
			$self->_parseArray($_);
		} else {
			$self->{toInstall} .= " " . _trim($_);
		}
	}
}

# Parse array
#
# @access private
# @param self $self iMSCP::debian_autoinstall instance
# @param ARRAY $array Array to be parsed
# @return void
sub _parseArray {
	my $self = shift;
	my $array = shift;

	foreach(@{$array}){
		if(ref($_) eq 'HASH') {
			$self->_parseHash($_);
		}elsif(ref($_) eq 'ARRAY') {
			$self->_parseArray($_);
		} else {
			$self->{toInstall} .= " " . _trim($_);
		}
	}
}

1;
