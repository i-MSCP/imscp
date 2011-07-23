#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 by internet Multi Server Control Panel
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
# @version		SVN: $Id: imscp-setup 4677 2011-06-23 19:01:39Z sci2tech $
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::debian_autoinstall;

use strict;
use warnings;

use iMSCP::Debug;
use Symbol;
use iMSCP::Execute qw/execute/;

use vars qw/@ISA/;
@ISA = ("Common::SingletonClass");
use Common::SingletonClass;

sub _init{

	my $self = shift;
	debug((caller(0))[3].': Starting...');

	$self->{nonfree} = 'non-free';

	debug((caller(0))[3].': Ending...');
	0;
}

sub installDialog{
	debug((caller(0))[3].': Starting...');

	my $self = shift;

	use iMSCP::Execute;
	use iMSCP::Dialog;

	iMSCP::Dialog->factory()->infobox("Installing Dialog");

	my($rs, $stderr);

	$rs = execute("apt-get -y install dialog", undef, \$stderr);
	error((caller(0))[3]. ": $stderr") if $stderr;
	error((caller(0))[3].": Can not install packages.") if $rs && ! $stderr;
	return $rs if $rs;

	#force dialog now
	iMSCP::Dialog->reset();

	debug((caller(0))[3].': Ending...');
	0;
}

sub preBuild{
	debug((caller(0))[3].': Starting...');

	my $self = shift;
	my $rs;

	$rs = $self->installDialog();
	return $rs if $rs;

	$rs = $self->load_old_imscp_cfg();

	$rs = $self->processAptList();
	return $rs if $rs;

	$rs = $self->readPackages();
	return $rs if $rs;

	$rs = $self->installPackages();
	return $rs if $rs;

	debug((caller(0))[3].': Ending...');
	0;
}

################################################################################
# Load old i-MSCP main configuration file
#
# @return void
#
sub load_old_imscp_cfg {

	debug((caller(0))[3].': Starting...');

	use iMSCP::Config;

	$main::imscpConfigOld = {};

	$main::imscpConfigOld = {};
	my $oldConf = "$main::defaultConf{'CONF_DIR'}/imscp.old.conf";

	tie %main::imscpConfigOld, 'iMSCP::Config', 'fileName' => $oldConf, noerrors => 1 if (-f $oldConf);

	debug((caller(0))[3].': Ending...');

	0;
}

sub processAptList{

	debug((caller(0))[3].': Starting...');

	my $self = shift;

	use iMSCP::File;
	use iMSCP::Dialog;

	iMSCP::Dialog->factory()->infobox("Processing apt sources");

	my $file = iMSCP::File->new(filename => '/etc/apt/sources.list');

	$file->copyFile('/etc/apt/sources.list.bkp') unless( -f '/etc/apt/sources.list.bkp');
	my $content = $file->get();

	unless ($content){
		error((caller(0))[3].': Can not read /etc/apt/sources.list');
		return 1;
	}

	my ($foundNonFree, $rs, $stdout, $stderr);

	while($content =~ /^deb\s+(?<uri>(?:https?|ftp)[^\s]+)\s+(?<distrib>[^\s]+)\s+(?<components>.+)$/mg){

		my %repos = %+;
		#is non-free enabled?
		unless($repos{'components'} =~ /\s?$self->{nonfree}(\s|$)/ ){
			my $uri = "$repos{uri}/dists/$repos{distrib}/$self->{nonfree}/";
			$rs = execute("wget --spider $uri", \$stdout, \$stderr);
			debug((caller(0))[3].": $stdout") if $stdout;
			debug((caller(0))[3].": $stderr") if $stderr;
			unless ($rs){
				$foundNonFree	= 1;
				debug((caller(0))[3].": Enable non free on $repos{uri}");
				$content =~ s/^($&)$/$1 $self->{nonfree}/mg;
			}
		} else {
			debug((caller(0))[3].": Non free already enabled on $repos{uri}");
			$foundNonFree = 1;
		}

	}

	unless($foundNonFree){
		error((caller(0))[3].': Cound not found repository that support non-free packages');
		return 1;
	}

	$file->set($content);
	$file->save() and return 1;

	iMSCP::Dialog->factory()->infobox("Update apt sources");

	$rs = execute('apt-get update', \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout") if $stdout;
	error((caller(0))[3].": $stderr") if $stderr;
	error((caller(0))[3].": Can not get update from repository") if $rs && !$stderr;
	return $rs if $rs;

	debug((caller(0))[3].': Ending...');
	0;
}

sub readPackages{

	debug((caller(0))[3].': Starting...');
	my $self = shift;
	my $SO = iMSCP::SO->new();
	my $confile = "$FindBin::Bin/docs/Debian/debian-packages-".lc($SO->{CodeName}).".xml";

	fatal("Debian $SO->{CodeName} is not supported!") if (! -f  $confile);

	use XML::Simple;
	my $xml = XML::Simple->new(NoEscape => 1);

	my $data = eval { $xml->XMLin($confile, KeyAttr => 'name') };

	foreach(keys %{$data}){
		if(ref($data->{$_}) eq 'ARRAY'){
			$self->parseArray($data->{$_});
		} else {
			if($data->{$_}->{alternative}){
				my $server  = $_;
				my @alternative = keys %{$data->{$server}->{alternative}};

				for ( my $index = $#alternative; $index >= 0; --$index ){
					my $defServer = $alternative[$index];
					my $oldServer = $main::imscpConfigOld{uc($server).'_SERVER'};
					if($@){
						error((caller(0))[3]." :$@");
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
						"Choose server $server",
						@alternative,
						'Not Used'
					);
				}while (!$rs);
				$self->{userSelection}->{$server} = $rs;
				foreach(@alternative){
					delete($data->{$server}->{alternative}->{$_}) if($_ ne $rs);
				}
			}
			$self->parseHash($data->{$_});
		}
	};

	debug((caller(0))[3].': Ending...');
	0;
}

sub trim{
	my $var = shift;
	$var =~ s/^\s+//;
	$var =~ s/\s+$//;
	$var;
}

sub parseHash{
	my $self = shift;
	my $hash = shift;
	foreach(values %{$hash}){
		if(ref($_) eq 'HASH'){
			$self->parseHash($_);
		}elsif(ref($_) eq 'ARRAY'){
			$self->parseArray($_);
		} else {
			$self->{toInstall} .= " ".trim($_);
		}
	}
}

sub parseArray{
	my $self = shift;
	my $array = shift;
	foreach(@{$array}){
		if(ref($_) eq 'HASH'){
			$self->parseHash($_);
		}elsif(ref($_) eq 'ARRAY'){
			$self->parseArray($_);
		} else {
			$self->{toInstall} .= " ".trim($_);
		}
	}
}

sub installPackages{
	debug((caller(0))[3].': Starting...');

	my $self = shift;

	use iMSCP::Execute;
	use iMSCP::Dialog;

	iMSCP::Dialog->factory()->infobox("Installing needed packages");

	my($rs, $stderr);

	$rs = execute("apt-get -y install $self->{toInstall}", undef, \$stderr);
	error((caller(0))[3]. ": $stderr") if $stderr;
	error((caller(0))[3].": Can not install packages.") if $rs && ! $stderr;
	return $rs if $rs;

	debug((caller(0))[3].': Ending...');
	0;
}

sub postBuild{
	debug((caller(0))[3].': Starting...');

	my $self = shift;

	my $x = qualify_to_ref("SYSTEM_CONF", 'main');

	my $nextConf = $$$x."/imscp.conf";
	tie %main::nextConf, 'iMSCP::Config', 'fileName' => $nextConf;

	$main::nextConf{uc($_)."_SERVER"} = lc($self->{userSelection}->{$_}) foreach(keys %{$self->{userSelection}});

	debug((caller(0))[3].': Ending...');
	0;
}
1;
