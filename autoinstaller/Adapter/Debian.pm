#!/usr/bin/perl

=head1 NAME

 autoinstaller::Adapter::Debian - Debian autoinstaller adapter class

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010 - 2012 by internet Multi Server Control Panel
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
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package autoinstaller::Adapter::Debian;

use strict;
use warnings;
use Symbol;
use iMSCP::Debug;
use iMSCP::Execute 'execute';
use iMSCP::Dialog;
use iMSCP::File;
use autoinstaller::Common 'checkCommandAvailability';
use parent 'autoinstaller::Adapter::Abstract';

=head1 DESCRIPTION

 i-MSCP distro autoinstaller adapter implementation for Debian.

=head1 PUBLIC METHODS

=over 4

=item installPreRequiredPackages()

 Install pre-required packages.

 Return int - 0 on success, other on failure

=cut

sub installPreRequiredPackages
{
	my $self = shift;
	my($rs, $stdout, $stderr);

	fatal('Not a Debian like system') if checkCommandAvailability('apt-get');

	my $command = 'apt-get';

	if(! %main::preseed && ! checkCommandAvailability('debconf-apt-progress')) {
		$command = 'debconf-apt-progress --logstderr -- ' . $command;
	}

	$rs = execute("$command -y install wget dialog libxml-simple-perl", (%main::preseed) ? \$stdout : undef, \$stderr);
	debug($stdout) if $stdout;
	error("Unable to install pre-required Debian packages: $stderr") if $rs;

	$rs;
}

=item preBuild()

 Process preBuild tasks.

 Return int - 0 on success, other on failure

=cut

sub preBuild
{
	my $self = shift;
	my $rs = 0;

	$rs |= $self->_updateAptSourceList() if ! $main::skippackages;
	$rs |= $self->_updatePackagesIndex() if ! $main::skippackages;
	$rs |= $self->_preparePackagesList() if ! $main::skippackages;

	$rs;
}

=item installPackages()

 Install Debian packages for i-MSCP.

 Return int - 0 on success, other on failure

=cut

sub installPackages
{
	my $self = shift;

	my ($stdout, $stderr);
	my $command = 'apt-get';

	iMSCP::Dialog->factory()->endGauge(); # Really needed !

	if(! %main::preseed && ! checkCommandAvailability('debconf-apt-progress')) {
		$command = 'debconf-apt-progress --logstderr -- ' . $command;
	}

	my $rs = execute("$command -y install $self->{toInstall}", (%main::preseed) ? \$stdout : undef, \$stderr);
	debug($stdout) if $stdout;
	if($rs) {
		error("Unable to install Debian packages: $stderr");
		return $rs;
	}

	0;
}

=item postBuild()

 Process postBuild tasks.

 Return int - 0 on success, other on failure

=cut

sub postBuild
{
	my $self = shift;

	# Add user server selection in imscp.conf file by creating/updating server variables
	$main::imscpConfig{uc($_) . '_SERVER'} = lc($self->{'userSelection'}->{$_}) for(keys %{$self->{'userSelection'}});

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by new(). Initialize instance.

 Return autoinstaller::Adapter::Debian

=cut

sub _init
{
	my $self = shift;

	$self->{'nonfree'} = 'non-free';

	$self;
}

=item _updatePackagesIndex()

 Update Debian packages index.

 Return int - 0 on success, other on failure

=cut

sub _updatePackagesIndex
{
	my ($rs, $stdout, $stderr);
	my $command = 'apt-get';

	if(! %main::preseed && ! checkCommandAvailability('debconf-apt-progress')) {
		$command = 'debconf-apt-progress --logstderr -- ' . $command;
	}

	$rs = execute("$command -y update", (%main::preseed) ? \$stdout : undef, \$stderr);
	debug($stdout) if $stdout;
	if($rs) {
		error('Unable to update package index from remote repository: $stderr');
		return $rs;
	}

	0;
}

=item _updateAptSourceList()

 Add non-free component in Debian apt sources.list file.

 Return int - 0 on success, other on failure

=cut

sub _updateAptSourceList
{
	my $self = shift;

	iMSCP::Dialog->factory()->infobox("\nProcessing apt sources list");

	my $file = iMSCP::File->new(filename => '/etc/apt/sources.list');

	$file->copyFile('/etc/apt/sources.list.bkp') unless( -f '/etc/apt/sources.list.bkp');
	my $content = $file->get();

	unless ($content){
		error('Unable to read /etc/apt/sources.list file');
		return 1;
	}

	my ($foundNonFree, $needUpdate, $rs, $stdout, $stderr);

	while($content =~ /^deb\s+(?<uri>(?:https?|ftp)[^\s]+)\s+(?<distrib>[^\s]+)\s+(?<components>.+)$/mg){
		my %repos = %+;

		# is non-free repository available?
		unless($repos{'components'} =~ /\s?$self->{'nonfree'}(\s|$)/ ){
			my $uri = "$repos{uri}/dists/$repos{distrib}/$self->{nonfree}/";
			$rs = execute("wget --spider $uri", \$stdout, \$stderr);
			debug("$stdout") if $stdout;
			debug("$stderr") if $stderr;

			unless ($rs){
				$foundNonFree = 1;
				debug("Enabling non free section on $repos{uri}");
				$content =~ s/^($&)$/$1 $self->{'nonfree'}/mg;
				$needUpdate = 1;
			}
		} else {
			debug("Non free section is already enabled on $repos{uri}");
			$foundNonFree = 1;
		}
	}

	unless($foundNonFree){
		error('Unable to found repository that support non-free packages');
		return 1;
	}

	if($needUpdate){
		$file->set($content);
		$file->save() and return 1;

		$rs = $self->_updatePackagesIndex();
		return $rs if $rs;
	}

	0;
}

=item _preparePackagesList()

 Prepare list of Debuian packages to be installed.

 Return int - 0 on success, other on failure

=cut

sub _preparePackagesList
{
	my $self = shift;
	my $lsbRelease = iMSCP::LsbRelease->new();
	my $distribution = lc($lsbRelease->getId(1));
	my $codename = lc($lsbRelease->getCodename(1));
	my $packagesFile = "$FindBin::Bin/docs/" . ucfirst($distribution) . '/' . $distribution . '-packages-' . $codename . '.xml';

	eval "use XML::Simple; 1";
	fatal('Unable to load perl module XML::Simple') if($@);

	my $xml = XML::Simple->new(NoEscape => 1);
	my $data = eval { $xml->XMLin($packagesFile, KeyAttr => 'name') };

	for(keys %{$data}){
		if(ref($data->{$_}) eq 'ARRAY'){
			$self->_parseArray($data->{$_});
		} else {
			if($data->{$_}->{'alternative'}) {
				my $service  = $_;
				my @alternative = keys %{$data->{$service}->{'alternative'}};

				my $serviceName = uc($service) . '_SERVER';
				my $oldServer = $main::preseed{'SERVERS'}->{$serviceName} || $main::imscpConfig{$serviceName}; # string or undef
				my $server = undef;

				# Only ask for server to use if not already defined or not found in list of available server
				# or if user asked for reconfiguration
				if($main::reconfigure || ! $oldServer || ! ($oldServer ~~ @alternative)) {
					if(@alternative > 1) { # Do no ask for server if only one is available
						for (my $index = $#alternative; $index >= 0; --$index) {
							my $defServer = $alternative[$index];

							if($oldServer && $defServer eq $oldServer) { # Make old server at first position
								splice @alternative, $index, 1 ;
								unshift(@alternative, $defServer);
								last;
							}
						}

						do {
							iMSCP::Dialog->factory->set('no-cancel', '');
							$server = iMSCP::Dialog->factory()->radiolist(
"
\\Z4\\Zu" . uc($service) . " service\\Zn

Please, choose the server you want use for the $service service:
",
								[@alternative],
								$oldServer
								# uncoment after dependencies check is implemented
								#'Not Used'
							);
						} while (! $server);
					} else {
						$server = pop(@alternative);
					}
				} else {
					$server = $oldServer;
				}

				$self->{'userSelection'}->{$service} = lc($server) eq 'not used' ? 'no' : $server;

				for(@alternative) {
					delete($data->{$service}->{'alternative'}->{$_}) if $_ ne $server;
				}
			}

			$self->_parseHash($data->{$_});
		}
	}

	0;
}

=item _trim($string)

 Trim the given string

 Param SCALAR - String to trim
 Return SCALAR - Trimmed string

=cut

sub _trim
{
	my $var = shift;
	$var =~ s/^\s+//;
	$var =~ s/\s+$//;
	$var;
}

=item _parseHash(\$hash)

 Parse the given hash and put result in the toInstall attribute.

 Param HASH Reference
 Return undef
=cut

sub _parseHash
{
	my $self = shift;
	my $hash = shift;

	for(values %{$hash}) {
		if(ref($_) eq 'HASH') {
			$self->_parseHash($_);
		} elsif(ref($_) eq 'ARRAY') {
			$self->_parseArray($_);
		} else {
			$self->{'toInstall'} .= " " . _trim($_);
		}
	}

	undef;
}

=item _parseArray(\$array)

 Parse the given array and put the result in the toInstall attribute.

 Param ARRAY Reference
 Return undef
=cut

sub _parseArray
{
	my $self = shift;
	my $array = shift;

	for(@{$array}){
		if(ref($_) eq 'HASH') {
			$self->_parseHash($_);
		} elsif(ref($_) eq 'ARRAY') {
			$self->_parseArray($_);
		} else {
			$self->{toInstall} .= " " . _trim($_);
		}
	}

	undef;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut


1;
