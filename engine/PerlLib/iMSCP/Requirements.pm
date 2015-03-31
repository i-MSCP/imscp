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

package iMSCP::Requirements;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::ProgramFinder;
use Module::Load::Conditional 'check_install';
use version;
use parent 'Common::Object';

=head1 DESCRIPTION

 Requirement library

=head1 PUBLIC METHODS

=over 4

=item all

 Process all requirements checks

 Return undef on success, die on failure

=cut

sub all
{
	my $self = $_[0];

	$self->user();
	$self->_perlModules();
	$self->_externalPrograms();

	undef;
}

=item user

 Check user under which the script is running

 Return undef on success, die on failure

=cut

sub user
{
	die("This script must be run as root user.") if $< != 0;

	undef;
}

=item checkVersion($version, $minVersion [, $maxVersion])

 Checks for version

 Param string $version Version to match
 Param string $minVersion Min required version
 Param string $maxVersion Max required version
 Return undef on success, die on failure

=cut

sub checkVersion
{
	my ($self, $version, $minVersion, $maxVersion) = @_;

	if(version->parse($version) < version->parse($minVersion)) {
		die("$version is older then required version $minVersion");
	}

	if($maxVersion && version->parse($version) > version->parse($maxVersion)) {
		die("$version is newer then required max version $maxVersion");
	}

	undef;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return iMSCP::Requirements

=cut

sub _init
{
	my $self = $_[0];

	# Required Perl modules
	# TODO add required min versions
	$self->{'perl_modules'} = {
		'Bit::Vector' => undef,
		'Crypt::Blowfish' => undef,
		'Crypt::CBC' => undef,
		'Crypt::PasswdMD5' => undef,
		'DBI' => undef,
		'DBD::mysql' => undef,
		'DateTime' => undef,
		'Data::Validate::Domain' => undef,
		'Email::Valid' => undef,
		'File::Basename' => undef,
		'File::Path' => undef,
		'MIME::Base64' => undef,
		'MIME::Entity' => undef,
		'Net::LibIDN' => undef,
		'XML::Simple' => undef
	};

	# Required programs
	$self->{'programs'} = {
		'PHP' => {
			'version_command' => "$main::imscpConfig{'CMD_PHP'} -v",
			'version_regexp' => qr/PHP\s([\d.]+)/,
			'min_version' => '5.3.2'
		},
		'Perl' => {
			'version_command' => "$main::imscpConfig{'CMD_PERL'} -v",
			'version_regexp' => qr/v([\d.]+)/,
			'min_version' => '5.14.2'
		}
	};

	$self;
}

=item test($test)

 Run the given test if available

 Param string $test Test name
 Return undef on success, die on failure

=cut

sub test
{
	my ($self, $test) = @_;

	if($self->can($test)) {
		$self->$test();
	} else {
		die(sprintf("The test '%s' is not available.", $test));
	}

	undef;
}

=item _perlModules()

 Checks for perl module availability

 Return undef on success, die on failure

=cut

sub _perlModules
{
	my $self = $_[0];

	my @moduleNames = ();

	while ( my ($moduleName, $moduleVersion) = each %{$self->{'perl_modules'}}) {
		push(@moduleNames, $moduleName) unless check_install(module => $moduleName, version => $moduleVersion);
	}

	if(@moduleNames) {
		if(@moduleNames > 1) {
			die(sprintf("The following Perl modules are not installed: %s", join ', ', @moduleNames));
		} else {
			die(sprintf("The following Perl module is not installed: %s", "@moduleNames"));
		}
	}

	undef;
}

=item _externalPrograms()

 Checks for external program availability and their versions

 Return undef on success, die on failure

=cut

sub _externalPrograms
{
	my $self = $_[0];

	for my $program (keys %{$self->{'programs'}}) {
		my $lcProgram = lc($program);

		unless(iMSCP::ProgramFinder::find($lcProgram)) {
			die(sprintf("Unable to find the %s command in search path", $program));
		}

		if($self->{'programs'}->{$program}->{'version_command'}) {
			eval {
				my $result = $self->_programVersions(
					$self->{'programs'}->{$program}->{'version_command'},
					$self->{'programs'}->{$program}->{'version_regexp'},
					$self->{'programs'}->{$program}->{'min_version'}
				);
			};

			die(sprintf('%s: %s', $program, $@)) if $@;
		}
	}

	undef;
}

=item _programVersions($versionCommand, $versionRegexp, $minVersion [, $maxVersion])

 Check for program version

 Param string $versionCommand Command to execute to find program version
 Param regexp $versionRegexp Regexp to find version in command version output string
 Param $minVersion Min required version
 Param $maxVersion Max required version
 Return undef on success, die on failure

=cut

sub _programVersions
{
	my ($self, $versionCommand, $versionRegexp, $minversion, $maxVersion) = @_;

	my ($stdout, $stderr);
	execute($versionCommand, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr;

	die('Unable to find version. No output') unless $stdout;

	if($versionRegexp) {
		if($stdout =~ /$versionRegexp/m) {
			$stdout = $1;
		} else {
			die(sprintf('Unable to find version. Output was: %s', $stdout));
		}
	}

	$self->checkVersion($stdout, $minversion, $maxVersion);
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
