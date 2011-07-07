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
use iMSCP::Execute qw/execute/;

use vars qw/@ISA/;
@ISA = ("Common::SingletonClass");
use Common::SingletonClass;

sub preBuild{
	debug((caller(0))[3].': Starting...');

	my $self = shift;
	my $rs;

	$rs = $self->processAptList();
	return $rs if $rs;

	$rs = $self->readPackages();
	return $rs if $rs;

	$rs = $self->installPackages();
	return $rs if $rs;

	#force dialog now
	iMSCP::Dialog->reset();

	debug((caller(0))[3].': Ending...');
	0;
}

sub processAptList{
	debug((caller(0))[3].': Starting...');
	use iMSCP::File;
	my $file = iMSCP::File->new(filename => '/etc/apt/sources.list');
	$file->copyFile('/etc/apt/sources.list.backup') if(! -f '/etc/apt/sources.list.bkp');
	my $content = $file->get();
	return 1 if !$content;
	$content =~ s/^(deb(?:\s|\t{1,})(?:[^\s\t]{1,})(?:\s|\t{1,})(?:[^\s\t]{1,})(?:\s|\t{1,})main(?:\s|\t{0,}))$/$1 contrib non-free/mg;
	$file->set($content);
	$file->save() and return 1;
	debug((caller(0))[3].': Ending...');
	0;
}

sub readPackages{

	debug((caller(0))[3].': Starting...');
	my $self = shift;
	my $SO = iMSCP::SO->new();
	my $confile = "$FindBin::Bin/docs/Debian/debian-packages-".lc($SO->{CodeName}).".xml";

	fatal("Debian $SO->{CodeName} is not supported!") if (! -f  $confile);
	# create object
	use XML::Simple;
	my $xml = XML::Simple->new(NoEscape => 1);

	# read XML file
	my $data = eval { $xml->XMLin($confile, KeyAttr => 'name') };
	use Data::Dumper;
	#fatal(Dumper($data));
	foreach(keys %{$data}){
		if(ref($data->{$_}) eq 'ARRAY'){
			$self->parseArray($data->{$_});
		} else {
			if($data->{$_}->{alternative}){
				my $server  = $_;
				my @alternative = keys %{$data->{$server}->{alternative}};
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

	my($rs, $stderr);

	$rs = execute("apt-get -y install $self->{toInstall}", undef, \$stderr);
	error((caller(0))[3]. ": $stderr") if $stderr;
	error((caller(0))[3].": Can not install packages") if $rs;
	return $rs if $rs;

	debug((caller(0))[3].': Ending...');
	0;
}

sub postBuild{
	debug((caller(0))[3].': Starting...');

	my $self = shift;


	debug((caller(0))[3].': Ending...');
	0;
}
1;
