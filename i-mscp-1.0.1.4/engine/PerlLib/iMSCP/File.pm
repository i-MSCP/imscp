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

package iMSCP::File;


use strict;
use warnings;
use iMSCP::Debug;

use vars qw/@ISA $AUTOLOAD/;
use Common::SimpleClass;

@ISA = ('Common::SimpleClass');

sub AUTOLOAD {
	my $self = shift;
	my $name = $AUTOLOAD;
	$name =~ s/.*:://;
	return if $name eq 'DESTROY';
	debug((caller(0))[3].': Starting...');
	$self->{$name} = shift if @_;
	unless (exists $self->{$name}) {
		error((caller(0))[3].": Can't find '$name'.");
		return undef;
	}
	debug((caller(0))[3].': Ending...');
	return $self->{$name};
}

sub _init{
	my $self = shift;
	debug((caller(0))[3].': Starting...');
	for my $conf (keys %{$self->{args}}){
		$self->{$conf} = $self->{args}->{$conf};
	}
	debug((caller(0))[3].': Ending...');
}

sub mode{

	my $self		= shift;
	my $fileMode	= shift;

	debug((caller(0))[3].': Starting...');

	if(!$self->{filename}){
		error((caller(0))[3].": File name not set!");
		return 1;
	}

	debug((caller(0))[3]. sprintf ": Change mode mode: %o for '$self->{filename}'", $fileMode);
	unless (chmod($fileMode, $self->{filename})){
		error((caller(0))[3].": Cannot change permissions of file '$self->{filename}': $!");
		return 1;
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub owner{

	my $self		= shift;
	my $fileOwner	= shift;
	my $fileGroup	= shift;

	debug((caller(0))[3].': Starting...');

	if(!$self->{filename}){
		error((caller(0))[3].": File name not set!");
		return 1;
	}

	my $uid = ($fileGroup =~ /^\d+$/) ? $fileOwner : getpwnam($fileOwner);
	$uid = -1 unless (defined $uid);

	my $gid = ($fileGroup =~ /^\d+$/) ? $fileGroup : getgrnam($fileGroup);
	$gid = -1 unless (defined $gid);

	debug((caller(0))[3].": Change owner uid:$uid, gid:$gid for '$self->{filename}'");

	unless (chown($uid, $gid, $self->{filename})){
		error((caller(0))[3].": Cannot change owner of file '$self->{filename}': $!");
		return 1;
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub get{
	my $self = shift;
	use FileHandle;

	debug((caller(0))[3].': Starting...');
	my @lines;

	if(!$self->{filename}){
		error((caller(0))[3].": File name not set!");
		return undef;
	}

	if(! $self->{fileHandle}){
		$self->{fileHandle} = FileHandle->new($self->{filename}, "r") or delete($self->{fileHandle});
		error((caller(0))[3].": Can`t open $self->{filename}!") if(!$self->{fileHandle});
		return undef if(!$self->{fileHandle});
	}

	if(! $self->{fileContent}) {
		my $fh = $self->{fileHandle};
		@{$self->{fileContent}} = <$fh>;
	}

	debug((caller(0))[3].': Ending...');

	return join('', @{$self->{fileContent}});
}

sub copyFile{
	my $self	= shift;
	my $dest	= shift;

	debug((caller(0))[3].': Starting...');

	use File::Copy;
	use File::Basename;

	if(!$self->{filename} || !-e $self->{filename}){
		error((caller(0))[3].": ".($self->{filename} ? "File $self->{filename} do not exits" : "File name not set!"));
		return 1;
	}

	debug((caller(0))[3].": Copy $self->{filename} to $dest");

	my $fileMode	= (stat($self->{filename}))[2] & 00777;
	my $owner		= (stat($self->{filename}))[4];
	my $group		= (stat($self->{filename}))[5];

	if(! copy ($self->{filename}, $dest) ) {
		error((caller(0))[3].": Copy $self->{filename} to $dest failed: $!");
		return 1;
	}
	if( -d $dest){
		my ($name,$path,$suffix) = fileparse($self->{filename});
		$dest .= "/$name$suffix";
	}
	debug((caller(0))[3]. sprintf ": Change mode mode: %o for '$dest'", $fileMode);
	unless (chmod($fileMode, $dest)){
		error((caller(0))[3].": Cannot change permissions of file '$dest': $!");
		return 1;
	}
	unless (chown($owner, $group, $dest)){
		error((caller(0))[3].": Cannot change permissions of file '$dest': $!");
		return 1;
	}

	debug((caller(0))[3].': Ending...');

	0;
}

sub moveFile{
	my $self	= shift;
	my $dest	= shift;

	debug((caller(0))[3].': Starting...');

	if(!$self->{filename} || !-e $self->{filename}){
		error((caller(0))[3].": ".($self->{filename} ? "File $self->{filename} do not exits" : "File name not set!"));
		return 1;
	}

	debug((caller(0))[3].": Move $self->{filename} to $dest");
	use File::Copy ;

	if(! move ($self->{filename}, $dest)){
		error((caller(0))[3].": Move $self->{filename} to $dest failed: $!");
		return 1;
	}

	debug((caller(0))[3].': Ending...');

	0;
}

sub delFile{
	my $self	= shift;

	debug((caller(0))[3].': Starting...');

	if(!$self->{filename}){
		error((caller(0))[3].": File name not set!");
		return 1;
	}

	debug((caller(0))[3].": Delete $self->{filename}");
	use File::Copy ;

	if(! unlink ($self->{filename}) && -e $self->{filename}){
		error((caller(0))[3].": Delete $self->{filename} failed: $!");
		return 1;
	}

	debug((caller(0))[3].': Ending...');

	0;
}


sub save{
	my $self = shift;

	use FileHandle;

	debug((caller(0))[3].': Starting...');

	if(!$self->{filename}){
		error((caller(0))[3].": File name not set!");
		return 1;
	}

	$self->{fileHandle}->close() if($self->{fileHandle});

	$self->{fileHandle} = FileHandle->new($self->{filename}, "w");
	if(! defined $self->{fileHandle}){
		error((caller(0))[3].": Can`t open $self->{filename}!");
		return 1;
	}

	print {$self->{fileHandle}} $self->{fileContent};

	$self->{fileHandle}->close();

	debug((caller(0))[3].': Ending...');
	0;
}

sub set{
	my $self = shift;
	my $content = shift || '';

	debug((caller(0))[3].': Starting...');

	use FileHandle;

	$self->{fileContent} = $content;

	debug((caller(0))[3].': Ending...');

	0;
}

sub DESTROY  {
	my $self = shift;
	if($self->{fileHandle}){
		$self->{fileHandle}->close();
	}

}

1;

__END__
