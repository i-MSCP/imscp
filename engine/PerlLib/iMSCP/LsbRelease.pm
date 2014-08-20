#/usr/bin/perl

=head1 NAME

 iMSCP::LsbRelease - Provides distribution-specific information

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::LsbRelease;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::File;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This class provides distribution-specific information as provided by the lsb_release command.

=head1 PUBLIC METHODS

=over 4

=item getId([$short = false])

 Return distributor ID

 You can get short value by passing a true value as parameter.

 Return string

=cut

sub getId
{
	my ($self, $short) = @_;

	if($short) {
		$self->{'lsbInfo'}->{'ID'} ? $self->{'lsbInfo'}->{'ID'} : "n/a" ;
	} else {
		sprintf("Distributor ID:\t%s", $self->{'lsbInfo'}->{'ID'} ? $self->{'lsbInfo'}->{'ID'} : "n/a");
	}
}

=item getDescription([$short = false])

 Returns description of the distribution

 You can get short value by passing a true value as parameter.

 Return string

=cut

sub getDescription
{
	my ($self, $short) = @_;

	if($short) {
		$self->{'lsbInfo'}->{'DESCRIPTION'} ? $self->{'lsbInfo'}->{'DESCRIPTION'} : "n/a" ;
	} else {
		sprintf("Description:\t%s", $self->{'lsbInfo'}->{'DESCRIPTION'} ? $self->{'lsbInfo'}->{'DESCRIPTION'} : "n/a");
	}
}

=item getRelease([$short = false])

 Return release number of the distribution

 You can get short value by passing a true value as parameter.

 Return string

=cut

sub getRelease()
{
	my ($self, $short) = @_;

	if($short) {
		$self->{'lsbInfo'}->{'RELEASE'} ? $self->{'lsbInfo'}->{'RELEASE'} : "n/a" ;
	} else {
		sprintf("Release:\t%s", $self->{'lsbInfo'}->{'RELEASE'} ? $self->{'lsbInfo'}->{'RELEASE'} : "n/a");
	}
}

=item getCodename([$short = false])

 Return code name of the distribution

 You can get short value by passing a true value as parameter.

 Return string

=cut

sub getCodename
{
	my ($self, $short) = @_;

	if($short) {
		$self->{'lsbInfo'}->{'CODENAME'} ? $self->{'lsbInfo'}->{'CODENAME'} : "n/a" ;
	} else {
		sprintf("Codename:\t%s", $self->{'lsbInfo'}->{'CODENAME'} ? $self->{'lsbInfo'}->{'CODENAME'} : "n/a");
	}
}

=item getAll([$short = false])

 Return all distribution-specific information

 You can get short values by passing a true value as parameter.

 Return string

=cut

sub getAll
{
	my ($self, $short) = @_;

	sprintf(
		"%s\n%s\n%s\n%s",
		$self->getId($short),
		$self->getDescription($short),
		$self->getRelease($short),
		$self->getCodename($short)
	);
}

=item getDistroInformation()

 Return distribution specific information as a hash such as:

	{
		'ID' => 'Debian',
		'RELEASE' => '6.0.6',
		'DESCRIPTION' => 'Debian GNU/Linux 6.0.6 (squeeze)',
		'CODENAME' => 'squeeze'
	}

 Return hash Pairs of fieldname/fieldvalue

=cut

sub getDistroInformation
{
	my $self = $_[0];

	unless($self->{'lsbInfo'}) {
		# Try to retrieve information from /etc/lsb-release first
		$self->{'lsbInfo'} = $self->_getLsbInformation();

		for (qw/ID RELEASE CODENAME DESCRIPTION/) {
			unless($self->{'lsbInfo'}->{$_}) {
				my $distinfo = $self->_guessDebianRelease();
				%{$self->{'lsbInfo'}} = (%$distinfo, %{$self->{'lsbInfo'}});
				last;
			}
		}
	}

	$self->{'lsbInfo'};
}

=item reset()

 Force reload of distribution-specific information

 Return iMSCP::LsbRelease

=cut

sub reset
{
	my $self = $_[0];

	$self->{'lsbInfo'} = $self->getDistroInformation();

	$self;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return iMSCP::LsbRelease

=cut

sub _init
{
	my $self = $_[0];

	$self->{'lsbInfo'} = $self->getDistroInformation() unless $self->{'lsbInfo'};

	$self;
}

=item _lookupCodename($release, [$unknown = undef])

 Lookup distribution codename

 Return string Distribution codename if found or $unknown value

=cut

# XXX: Update as needed
# This should really be included in apt-cache policy output... it is already
# in the Release file...
my $RELEASE_CODENAME_LOOKUP = {
	'1.1' => 'buzz',
	'1.2' => 'rex',
	'1.3' => 'bo',
	'2.0' => 'hamm',
	'2.1' => 'slink',
	'2.2' => 'potato',
	'3.0' => 'woody',
	'3.1' => 'sarge',
	'4.0' => 'etch',
	'5.0' => 'lenny',
	'6.0' => 'squeeze',
	'7' => 'wheezy',
	'8' => 'jessie'
};

sub _lookupCodename
{
	my ($self, $release, $unknown) = @_;

	return $unknown if $release !~ /(\d+)\.(\d+)(r(\d+))?/;

	my $shortRelease;

	if($1 < 7) {
		$shortRelease = sprintf '%s.%s', $1, $2;
	} else {
		$shortRelease = sprintf '%s', $1;
	}

	$$RELEASE_CODENAME_LOOKUP{$shortRelease} ? $$RELEASE_CODENAME_LOOKUP{$shortRelease} : $unknown;
}

=item _parsePolicyLine($data)

 Parse a line from the apt-cache policy command output to retrieve distribution version, origin, suite, component and
label field value

 Return hash Pairs of fieldname/fieldvalue

=cut

# map short field names to long field names
my $longnames = {'v' => 'version', 'o' => 'origin', 'a' => 'suite', 'c'  => 'component', 'l' => 'label'};

sub _parsePolicyLine
{
	my ($self, $data) = @_;

	my ($retval, @bits) = ({ }, split ',', $data);

	for(@bits) {
		my @kv = split('=', $_, 2);

		if(scalar @kv > 1) {
			my ($k, $v) = @kv;
			$$retval{$$longnames{$k}} = $v if $$longnames{$k}
		}
	}

	$retval;
}

=item _parseAptPolicy()

 Parse output from apt-cache policy command

 Return array

=cut

sub _parseAptPolicy
{
	my $self = $_[0];

	my $data = [];

	my ($rs, $stdout, $stderr);
	$rs = execute('LANG=C apt-cache policy', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to execute apt-cache policy') if $rs && ! $stderr;

	return [] if $rs;

	for(split "\n", $stdout) {
		s/^\s+|\s+$//g;
		my $priority = int $1 if /(\d+)/;

		if(/^release/) {
			my @bits = split ' ', $_ , 2;
			push @$data, [$priority, $self->_parsePolicyLine($bits[1])] if @bits > 1;
		}
	}

	$data;
}

=item _guessReleaseFromApt($origin = 'Debian', $component = 'main', $label = 'Debian')

 Retrieve distribution information by parsing output from the apt-cache policy command

 Return hash Pairs of fieldname/fieldvalue

=cut

sub _guessReleaseFromApt
{
	my $self = shift;
	my $origin = shift || 'Debian';
	my $component = shift || 'main';
	#my $ignoresuites = shift || ('experimental');
	my $label = shift || 'Debian';

	my $releases = $self->_parseAptPolicy();

	return undef unless scalar @$releases;

	# We only care about the specified origin, component, and label
	@$releases = grep {
		exists $$_[1]{'origin'} && $$_[1]{'origin'} eq $origin and
		exists $$_[1]{'component'} && $$_[1]{'component'} eq $component and
		exists $$_[1]{'label'} && $$_[1]{'label'} eq $label
	} @$releases;

 	# Check again to make sure we didn't wipe out all of the releases
	return undef unless scalar @$releases;

	@$releases = reverse sort @$releases;

    # We've sorted the list by descending priority, so the first entry should
    # be the "main" release in use on the system
	@$releases[0]->[1];
}

=item _guessDebianRelease()

 Return Debian distribution-specific information

 Return hash Pairs of fieldname/fieldvalue

=cut

my $TESTING_CODENAME = 'unknown.new.testing';

sub _guessDebianRelease
{
	my $self = $_[0];

	my $distinfo = { 'ID' => 'Debian' };
	my ($rs, $stdout, $stderr, $release, $codename);

	$rs = execute('/bin/uname', \$stdout, \$stderr); # We are safe here
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$stdout =~ s/^\s+|\s+$//g;

	if(grep $stdout, ('Linux', 'Hurd', 'NetBSD')) {
		$$distinfo{'OS'} = "GNU/$stdout";
	} elsif($stdout eq 'FreeBSD') {
		$$distinfo{'OS'} = "GNU/k$stdout";
	} else {
		$$distinfo{'OS'} = 'GNU';
	}

	$distinfo->{'DESCRIPTION'} = sprintf('%s %s', $$distinfo{'ID'}, $$distinfo{'OS'});

	if(-f '/etc/debian_version') {
		$release = iMSCP::File->new('filename' => '/etc/debian_version')->get();

		unless(defined $release) {
			error('Unable to read /etc/debian_version');
			$release = 'unknown';
		}

		$release =~ s/^\s+|\s+$//g;

		debug($release);

		if($release =~ /^[0-9]/) {
			# /etc/debian_version should be numeric
			$$distinfo{'RELEASE'} = $release;
			$$distinfo{'CODENAME'} = $self->_lookupCodename($release, "n/a");
		} elsif($release =~ m%(.*)/sid$%) {
			$TESTING_CODENAME = $1 if lc $1 ne 'testing';
			$$distinfo{'RELEASE'} = 'testing/unstable';
		} else {
			$$distinfo{'RELEASE'} = $release;
		}
	}

	# Only use apt information if we did not get the proper information
	# from /etc/debian_version or if we don't have a codename
	# (which will happen if /etc/debian_version doesn't contain a
	# number but some text like 'testing/unstable' or 'lenny/sid')
	#
	# This is slightly faster and less error prone in case the user
	# has an entry in his /etc/apt/sources.list but has not actually
	# upgraded the system.
	if(! $$distinfo{'CODENAME'} || $$distinfo{'CODENAME'} eq "n/a") {
		my $rinfo = $self->_guessReleaseFromApt();

		if($rinfo) {
			$release = $$rinfo{'version'};

			if($release) {
				$codename = $self->_lookupCodename($release, "n/a");
			} else {
				$release = $$rinfo{'suite'} || 'unstable';

				if($release eq 'testing') {
					$codename = $TESTING_CODENAME;
				} else {
					$codename = 'sid';
				}
			}

			$$distinfo{'RELEASE'} = $release;
			$$distinfo{'CODENAME'} = $codename;
		}
	}

	$$distinfo{'DESCRIPTION'} .= " $$distinfo{'RELEASE'}" if $$distinfo{'RELEASE'};
	$$distinfo{'DESCRIPTION'} .= " ($$distinfo{'CODENAME'})" if $$distinfo{'CODENAME'};

	$distinfo;
}

=item _getLsbInformation()

 Return lsb information from the lsb-release file if any

 Return hash Pairs of fieldname/fielvalue.

=cut

sub _getLsbInformation
{
	my $self = $_[0];

	my $distinfo = { };

	if(-f '/etc/lsb-release') {
		my $lsbReleaseFile = iMSCP::File->new('filename' => '/etc/lsb-release')->get();

		unless(defined $lsbReleaseFile) {
			error('Unable to read /etc/lsb-release')
		} else {
			debug("\n$lsbReleaseFile");

			for(split "\n", $lsbReleaseFile) {
				s/^\s+|\s+$//g;
				next if ! length || ! /=/; # Skip invalid lines

				my ($var, $arg) = split '=', $_, 2;

				if($var =~ /^DISTRIB_/) {
					$var = substr($var, 8);
					$arg = substr($arg, 1, -1) if substr($arg, 0, 1) eq '"'; # Remove quotes
					$$distinfo{$var} = $arg if length $arg; # Ignore empty arguments
				}
			}
		}
	}

	$distinfo;
}

=back

=head1 NOTE

 This is a re-implementation for i-MSCP of the lsb_release command as provided by the lsb-release Debian package

 Detection of systems using a mix of packages from various distributions or releases is something of a black art; the
current heuristic tends to assume that the installation is of the earliest distribution which is still being used by apt
but that heuristic is subject to error.

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
