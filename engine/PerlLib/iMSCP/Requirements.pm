#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2015 by internet Multi Server Control Panel
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
# @category    i-MSCP
# @copyright   2010-2015 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

#####################################################################################
# Package description:
#
# Package that is responsible to check requirements for i-MSCP (such as perl modules
# availability, program availability and their versions, user that run the script...)

package iMSCP::Requirements;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::ProgramFinder;
use version;
use parent 'Common::Object';

# Initializer.
#
# @param self $self iMSCP::Requirements instance
# @return void

sub _init
{
	my $self = $_[0];

	# Required perl modules
	$self->{'perl_modules'} = {
		'Crypt::Blowfish' => '',
		'Crypt::CBC' => '',
		'Crypt::PasswdMD5' => '',
		'DBI' => '',
		'DBD::mysql' => '',
		'DateTime' => '',
		'Data::Validate::Domain' => 'qw(is_domain)',
		'Email::Simple' => '',
		'Email::Valid' => '',
		'File::Basename' => '',
		'File::Path' => '',
		'MIME::Base64' => '',
		'MIME::Entity' => '',
		'Net::LibIDN' => 'qw/idn_to_ascii idn_to_unicode/',
		'XML::Simple' => ''
	};

	# Required programs
	$self->{'programs'} = {
		'PHP' => {
			'version_command' => "$main::imscpConfig{'CMD_PHP'} -v",
			'version_regexp' => qr/PHP\s([\d.]+)/,
			'minimum_version' => '5.3.2'
		},
		'Perl' => {
			'version_command' => "$main::imscpConfig{'CMD_PERL'} -v",
			'version_regexp' => qr/v([\d.]+)/,
			'minimum_version' => '5.10.1'
		}
	};
}

# Checks for test availability.
#
# @throws fatal error if a test is not available
# @param self $self iMSCP::Requirements instance
# @return void
sub test
{
	my ($self, $test) = @_;

	if($self->can($test)) {
		$self->$test();
	} else {
		fatal("The test '$test' is not available.", 1);
	}
}

# Process all tests for requirements.
#
# @param self $self iMSCP::Requirements instance
# @return undef
sub all
{
	my $self = $_[0];

	$self->user();
	$self->_modules();
	$self->_externalProgram();

	undef;
}

# Checks for user that run the imscp-autoinstaller script.
#
# @throws fatal error if the script is not run as root user
# @param self $self iMSCP::Requirements instance
# @return void
sub user
{
	fatal('This script must be run as root user.') if $< != 0;
}

# Checks for perl module availability.
#
# @throws fatal error if a Perl module is missing
# @param self $self iMSCP::Requirements instance
# @return void
sub _modules
{
	my $self = $_[0];

	my @mod_missing = ();

	for my $mod (keys %{$self->{'perl_modules'}}) {
		if (eval "require $mod") {
			eval "use $mod $self->{'perl_modules'}->{$mod}";
			push(@mod_missing, $mod) if(@$);
		} else {
			push(@mod_missing, $mod);
		}
	}

	fatal("Modules [@mod_missing] were not found on your system.") if @mod_missing;
}

# Checks for external program availability and their versions.
#
# @throws fatal error if a program is not found on the system
# @throws fatal error if a program version is older than required
# @param self $self iMSCP::Requirements instance
# @return undef
sub _externalProgram
{
	my $self = $_[0];

	for my $program (keys %{$self->{'programs'}}) {
		my $lcProgram = lc($program);

		unless(iMSCP::ProgramFinder::find($lcProgram)) {
			fatal("Unable to find the $program command in current executable path");
		}

		if(exists $self->{'programs'}->{$program}->{'version_command'}) {
			my $result = $self->_programVersions(
				$program,
				$self->{'programs'}->{$program}->{'version_command'},
				$self->{'programs'}->{$program}->{'version_regexp'},
				$self->{'programs'}->{$program}->{'minimum_version'}
			);

			fatal "$program $result" if $result;
		}
	}

	undef;
}

# Check for program version.
#
# @throws fatal error if a program is not found on the system
# @access private
# @param self $self iMSCP::Requirements instance
# @param string $program Program name
# @param string $command Command to run to retrieve program version
# @param string $regexp Regular expression to find the program version
# @param string $minversion Program minimum version required
# @return mixed 0 on success, error string on error
sub _programVersions
{
	my ($self, $program, $command, $regexp, $minversion) = @_;

	my ($stdout, $stderr);
	execute($command, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr;

	fatal("Unable to find $program version: No output") if ! $stdout;

	if($regexp) {
		if($stdout =~ /$regexp/m) {
			$stdout = $1;
		} else {
			fatal("Unable to find $program version. Output was: $stdout");
		}
	}

	$self->checkVersion($stdout, $minversion);
}

# Checks for version.
#
# @param self $self iMSCP::Requirements instance
# @param string $version version to be checked
# @param string $minversion minimum accepted version
# @param string $maxversion OPTIONAL maximum accepted version
# @return mixed 0 on success, error string on failure
sub checkVersion
{
	my ($self, $version, $minversion, $maxversion) = @_;

	$maxversion ||= '';

	if(qv("v$version") < qv("v$minversion")) {
		return "$version is older then required version $minversion";
	}

	if($maxversion && qv("v$version") > qv("v$maxversion")) {
		return "$version is newer then required version $minversion";
	}

	0;
}

1;
