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
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
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
	$self->{$name} = shift if @_;
	unless (exists $self->{$name}) {
		error("Can't find '$name'.");
		return undef;
	}
	return $self->{$name};
}

sub _init{
	my $self = shift;
	for my $conf (keys %{$self->{args}}){
		$self->{$conf} = $self->{args}->{$conf};
	}
}

sub mode{

	my $self		= shift;
	my $fileMode	= shift;

	if(!$self->{filename}){
		error("File name not set!");
		return 1;
	}

	debug( sprintf ": Change mode mode: %o for '$self->{filename}'", $fileMode);
	unless (chmod($fileMode, $self->{filename})){
		error("Cannot change permissions of file '$self->{filename}': $!");
		return 1;
	}

	0;
}

sub owner{

	my $self		= shift;
	my $fileOwner	= shift;
	my $fileGroup	= shift;

	if(!$self->{filename}){
		error("File name not set!");
		return 1;
	}

	my $uid = ($fileGroup =~ /^\d+$/) ? $fileOwner : getpwnam($fileOwner);
	$uid = -1 unless (defined $uid);

	my $gid = ($fileGroup =~ /^\d+$/) ? $fileGroup : getgrnam($fileGroup);
	$gid = -1 unless (defined $gid);

	debug("Change owner uid:$uid, gid:$gid for '$self->{filename}'");

	unless (chown($uid, $gid, $self->{filename})){
		error("Cannot change owner of file '$self->{filename}': $!");
		return 1;
	}

	0;
}

sub get{
	my $self = shift;
	use FileHandle;

	my @lines;

	if(!$self->{filename}){
		error("File name not set!");
		return undef;
	}

	if(! $self->{fileHandle}){
		$self->{fileHandle} = FileHandle->new($self->{filename}, "r") or delete($self->{fileHandle});
		error("Can`t open $self->{filename}!") if(!$self->{fileHandle});
		return undef if(!$self->{fileHandle});
	}

	if(! $self->{fileContent}) {
		my $fh = $self->{fileHandle};
		@{$self->{fileContent}} = <$fh>;
	}

	return join('', @{$self->{fileContent}});
}

sub copyFile{
	my $self	= shift;
	my $dest	= shift;
	my $option	= shift;

	$option = {} if(ref $option ne 'HASH');

	use File::Copy;
	use File::Basename;

	if(!$self->{filename} || !-e $self->{filename}){
		error("".($self->{filename} ? "File $self->{filename} do not exits" : "File name not set!"));
		return 1;
	}

	debug("Copy $self->{filename} to $dest");

	if(! copy ($self->{filename}, $dest) ) {
		error("Copy $self->{filename} to $dest failed: $!");
		return 1;
	}

	if( -d $dest){
		my ($name,$path,$suffix) = fileparse($self->{filename});
		$dest .= "/$name$suffix";
	}

	if(!$option->{preserve} || (lc($option->{preserve}) ne 'no')){

		my $fileMode	= (stat($self->{filename}))[2] & 00777;
		my $owner		= (stat($self->{filename}))[4];
		my $group		= (stat($self->{filename}))[5];

		debug( sprintf ": Change mode mode: %o for '$dest'", $fileMode);
		unless (chmod($fileMode, $dest)){
			error("Cannot change permissions of file '$dest': $!");
			return 1;
		}
		debug( sprintf ": Change owner: %s:%s for '$dest'", $owner, $group);
		unless (chown($owner, $group, $dest)){
			error("Cannot change permissions of file '$dest': $!");
			return 1;
		}
	}

	0;
}

sub moveFile{
	my $self	= shift;
	my $dest	= shift;

	if(!$self->{filename} || !-e $self->{filename}){
		error("".($self->{filename} ? "File $self->{filename} do not exits" : "File name not set!"));
		return 1;
	}

	debug("Move $self->{filename} to $dest");
	use File::Copy ;

	if(! move ($self->{filename}, $dest)){
		error("Move $self->{filename} to $dest failed: $!");
		return 1;
	}

	0;
}

sub delFile{
	use File::Copy ;

	my $self	= shift;

	if(!$self->{filename}){
		error("File name not set!");
		return 1;
	}

	debug("Delete $self->{filename}");

	if(! unlink ($self->{filename}) && -e $self->{filename}){
		error("Delete $self->{filename} failed: $!");
		return 1;
	}

	0;
}


sub save{
	my $self = shift;

	use FileHandle;

	if(!$self->{filename}){
		error("File name not set!");
		return 1;
	}

	debug("Save $self->{filename}");

	$self->{fileHandle}->close() if($self->{fileHandle});

	$self->{fileHandle} = FileHandle->new($self->{filename}, "w");
	if(! defined $self->{fileHandle}){
		error("Can`t open $self->{filename}!");
		return 1;
	}

	$self->{fileContent} = '' unless $self->{fileContent};

	print {$self->{fileHandle}} $self->{fileContent};

	$self->{fileHandle}->close();

	0;
}

sub set{
	my $self = shift;
	my $content = shift || '';

	use FileHandle;

	$self->{fileContent} = $content;

	0;
}

sub DESTROY  {
	my $self = shift;
	if($self->{fileHandle}){
		$self->{fileHandle}->close();
	}
	0;
}

1;
