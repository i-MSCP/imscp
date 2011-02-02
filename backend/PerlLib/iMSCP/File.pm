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
# @copyright	2010 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@i-mscp.net>
# @version		SVN: $Id: imscp-build 3933 2010-12-01 19:35:32Z sci2tech $
# @link			http://i-mscp.net i-MSCP Home Site
# @license      http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::File;

use strict;
use warnings;

use iMSCP::Debug;


use vars qw/@ISA/;

@ISA = ("Common::SimpleClass");
use Common::SimpleClass;

#simple setter
sub set{
	debug((caller(0))[3].': Starting...');
	my $self			= shift;
	my $variable		= shift || fatal('No variable provided');
	my $value			= shift;
	$self->{$variable}	= $value;
	debug((caller(0))[3].': Ending...');
}

sub _setMode{
	my $self			= shift;
	my $fileMode		= shift;
	my $fileName		= shift;
	debug((caller(0))[3].': Starting...');

	debug((caller(0))[3].": change mode mode: $fileMode for '$fileName'");
	unless (chmod(oct($fileMode), $fileName)){
		error((caller(0))[3].": cannot change permissions of file '$fileName': $!");
		return 1;
	}

	debug((caller(0))[3].': Ending...');
	0;
}
sub _setOwner{
	my $self			= shift;
	my $fileOwner		= shift;
	my $fileGroup		= shift;
	my $fileName		= shift;
	debug((caller(0))[3].': Starting...');

	my $uid = ($fileGroup =~ /^\d+$/) ? $fileOwner : getpwnam($fileOwner);
	$uid = -1 unless (defined $uid);

	my $gid = ($fileGroup =~ /^\d+$/) ? $fileGroup : getgrnam($fileGroup);
	$gid = -1 unless (defined $gid);

	debug((caller(0))[3].": change owner uid:$uid, gid:$gid for '$fileName'");

	unless (chown($uid, $gid, $fileName)){
		error((caller(0))[3].": cannot change owner of file '$fileName': $!");
		return 1;
	}

	debug((caller(0))[3].': Ending...');
	0;
}
sub _setRecursive{
	my $self		= shift;
	my $dir	= shift || undef;
	debug((caller(0))[3].': Starting...');

	debug "Setting recursive rights for '$dir'";
	unless (opendir(DIRH, $dir)){
		error('Cannot open directory');
		return 1;
	}

	my @content= readdir(DIRH);
	closedir(DIRH);
	foreach (@content){
		next if($_ eq '.' || $_ eq '..');
		$self->_setRights("${dir}/$_");
	}

	debug((caller(0))[3].': Ending...');
	0;
}
sub _setRights{
	my $self		= shift;
	my $fileName	= shift || undef;
	my $rv		= 0;
	debug((caller(0))[3].': Starting...');

	unless (defined $fileName && -e $fileName){
		debug("Nothing to do: file / folder '".(defined $fileName ? $fileName : 'N/A' )."' do not exists!");
	} else {
		if(
			$self->{mode} eq 'both'
			||
			(-f $fileName && $self->{mode} eq 'file')
			||
			(-d $fileName && $self->{mode} eq 'dirs')
		){
			$rv = $self->_setOwner(
				defined $self->{fileOwner} ? $self->{fileOwner} : -1,
				defined $self->{fileGroup} ? $self->{fileGroup} : -1,
				$fileName
			)if(defined $self->{fileOwner} || defined $self->{fileGroup});
			$rv |=  $self->_setMode($self->{fileMode}, $fileName) if(defined $self->{fileMode});
		}
		$rv |= $self->_setRecursive($fileName) if(-d $fileName);
	}

	debug((caller(0))[3].': Ending...');
	$rv;
}
# Set recursive ownership and access mode for files and folders
sub setRights {
	my $self	= shift;
	my $rv		= 0;
	debug((caller(0))[3].': Starting...');
	$self->{mode} = 'both' unless (defined $self->{mode});
	unless (defined $self->{fileName} && -e $self->{fileName}){
		debug("Nothing to do: file / folder '".(defined $self->{fileName} ? $self->{fileName} : 'N/A' )."' do not exists!");
	} else {
		$rv = $self->_setRights($self->{fileName});
	}
	debug((caller(0))[3].': Ending...');
	$rv;
}
# Set recursive ownership and access mode only for files in folder and subfolders
sub setFileRights {
	my $self	= shift;
	debug((caller(0))[3].': Starting...');
	$self->{mode} = 'file';
	debug((caller(0))[3].': Ending...');
	return $self->setRights();
}
# Set recursive ownership and access mode only for folders and subfolders
sub setDirRights {
	my $self	= shift;
	debug((caller(0))[3].': Starting...');
	$self->{mode} = 'dirs';
	debug((caller(0))[3].': Ending...');
	return $self->setRights();
}
1;

__END__
