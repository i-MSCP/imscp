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
# @license      http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Requirements;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute qw/execute/;

use vars qw/@ISA/;
@ISA = ("Common::SimpleClass");
use Common::SimpleClass;

sub _init{
	my$self = shift;

	$self->{needed} = {
		#'IO::Socket'				=> '',
		'DBI'						=> '',
		#'DBD::mysql'				=> '',
		'MIME::Entity'				=> '',
		#'MIME::Parser'				=> '',
		'Crypt::CBC'				=> '',
		#'Crypt::Blowfish'			=> '',
		'Crypt::PasswdMD5'			=> '',
		'MIME::Base64'				=> '',
		'Term::ReadKey'				=> '',
		#'Term::ReadPassword'		=> '',
		#'File::Basename'			=> '',
		'File::Path'				=> '',
		#'HTML::Entities'			=> '',
		#'File::Temp'				=> 'qw(tempdir)',
		#'File::Copy::Recursive'	=> 'qw(rcopy)',
		'Net::LibIDN'				=> 'qw/idn_to_ascii idn_to_unicode/',
		#'XML::Simple'				=> '',
		'DateTime'					=> '',
		'Data::Validate::Domain'	=> 'qw(is_domain)',
		'Data::Validate::IP'		=> 'qw(is_ipv4 is_ipv6)',
		'Email::Valid'				=> '',
	};

	$self->{programs} = {
		'php'	=> {version	=> 'php -v',	regexp	=> 'PHP ([\d.]+)',	minversion => '5.3.2'},
		'perl'	=> {version	=> 'perl -v',	regexp	=> 'v([\d.]+)',	minversion => '5.10.1'}
	};
}

sub test{
	my $self = shift;
	my $test = shift;

	debug((caller(0))[3].': Starting...');

	if($self->can($test)){
		$self->$test();
	} else {
		fatal("Test $test is not available", 1);
	}

	debug((caller(0))[3].': Ending...');
}

sub all{
	my $self = shift;

	debug((caller(0))[3].': Starting...');

	$self->user();
	$self->_modules();
	$self->_externalProgram();

	debug((caller(0))[3].': Ending...');
}

sub user{
	my $self = shift;

	debug((caller(0))[3].': Starting...');

	fatal('Must run as root') if( $< != 0 );

	debug((caller(0))[3].': Ending...');
}

sub _modules{
	my $self = shift;

	debug((caller(0))[3].': Starting...');

	my ($mod, $mod_missing) = (undef, undef);

	for $mod (keys(%{$self->{needed}})) {
		ITER: {
			foreach my $prefix (@INC) {
				my $realfilename = "$prefix/$mod.pm";
				$realfilename =~ s!::!/!g;
				if (-f $realfilename) {
					$INC{$mod} = $realfilename;
					eval "use $mod $self->{needed}->{$mod}";
					if($@){
						$mod_missing .= ($mod_missing ? ', ' : '').$mod;
					}
					last ITER;
				}
			}
			$mod_missing .= ($mod_missing ? ', ' : '').$mod;
		}
	}

	debug((caller(0))[3].': Ending...');

	fatal("Modules [$mod_missing] WAS NOT FOUND in your system...") if ($mod_missing) ;
}

sub _externalProgram{
	my $self = shift;
	my ($rv, $output, $error);

	debug((caller(0))[3].': Starting...');
	fatal("Can't find which program") if(execute('which which', \$output, \$error));

	for my $program (keys %{$self->{programs}}){
		$rv = execute("which $program", \$output, \$error);
		fatal("Can't find $program") if $rv;
		if($self->{programs}->{$program}->{version}){
			my $result = $self->_programVersions(
							$self->{programs}->{$program}->{version},
							$self->{programs}->{$program}->{regexp},
							$self->{programs}->{$program}->{minversion}
						);
			fatal "$program $result" if $result;
		}
	}

	debug((caller(0))[3].': Ending...');
}

sub _programVersions{
	my ($self, $program, $regexp, $minversion) = @_;
	my ($rv, $output, $error);

	debug((caller(0))[3].': Starting...');

	execute("$program", \$output, \$error) && fatal("Can't find $program");
	if($regexp){
		$output =~ m!$regexp!;
		$output = $1;
	}
	my $result = $self->checkVersion($output, $minversion);

	debug((caller(0))[3].': Ending...');
	$result;
}

sub checkVersion{
	my $self		= shift;
	my $version		= shift;
	my $minversion	= shift;
	my $maxversion	= shift || '';

	use version;

	if(version->new($version) < version->new($minversion)){
		return "$version is older then required version $minversion";
	}

	if($maxversion && version->new($version) > version->new($maxversion)){
		return "$version is newer then required version $minversion";
	}

	0;
}

1;

__END__
