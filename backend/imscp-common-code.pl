#!/usr/bin/perl
#
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

use strict;
use warnings;
use Carp;
use Symbol;
use Log::Message::Simple;

BEGIN{

	$Log::Message::Simple::STACKTRACE_ON_ERROR = 0;

	$SIG{__DIE__} = sub {
		eval{croak (@_);};
		error("Developer dump:", 1);
		error($@, 1);
		#Carp::confess @_;
		exit 1;
	};

	$SIG{__WARN__} = sub{
		error("@_", 1);
	};

}

%main::needed =(
	#'IO::Socket'			=> '',
	'DBI'					=> '',
	#'DBD::mysql'			=> '',
	#'MIME::Entity'			=> '',
	#'MIME::Parser'			=> '',
	'Crypt::CBC'			=> '',
	#'Crypt::Blowfish'		=> '',
	#'Crypt::PasswdMD5'		=> '',
	'MIME::Base64'			=> '',
	#'Term::ReadPassword'	=> '',
	#'File::Basename'		=> '',
	#'File::Path'			=> '',
	#'HTML::Entities'		=> '',
	#'File::Temp'			=> 'qw(tempdir)',
	#'File::Copy::Recursive'	=> 'qw(rcopy)',
	#'Net::LibIDN'			=> 'qw/idn_to_ascii idn_to_unicode/'
	'XML::Simple'			=> ''
);

sub flushLogs{
	my $display = $main::configs{debug};
	my $log=Log::Message::Simple->stack_as_string;

	if($main::output){
		my $opened = open(DEBUG, '>', $main::output);

		if( ! $opened ){
			print STDERR "Can't save file".$main::output."!" ;
		} else {
			print DEBUG $log;
			close(DEBUG);
		}
	}

	if(!defined($display)||$display){
		print "===================DEBUG ".((caller(0))[1])."=======================\n";
		print $log."\n";
	}

	Log::Message::Simple->flush();
}

1;

END{
	$main::output = undef;
	flushLogs();
}
