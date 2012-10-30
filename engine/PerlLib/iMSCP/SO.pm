#!/usr/bin/perl

=head1 NAME

iMSCP::SO - Provides distribution-specific information

=cut

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
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::SO;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::LsbRelease;
use base 'Common::SingletonClass';

=head1 DESCRIPTION

This class provides distribution-specific information.

=head1 PUBLIC METHODS

=over 4

=item loadInfo($forceReload = false)

Make distribution-specific information available via the following public attributes.

 - Distribution: Holds the distributor ID such as 'Debian'
 - Version: Holds the distribution version such as '6.0.6'
 - CodeName: Holds the distribution codename such as 'squeeze'

You can force reload of distribution-specific information by passing a true value as
parameter. In case one of the information is not found, a fatal error is raised.

Return self Provides fluent interface

=cut

sub loadInfo
{
	my $self = shift;
	my $forceReload = shift;

	my $lsbRelease = iMSCP::LsbRelease->new();

	if($forceReload) {
		$lsbRelease->reset();
		$self->{Distribution} = undef;
		$self->{Version} = undef;
		$self->{CodeName} = undef;
	}

	$self->{Distribution} = $lsbRelease->getId(1);
	$self->{Version} = $lsbRelease->getRelease(1);
	$self->{CodeName} = $lsbRelease->getCodename(1);

	if($self->{Distribution} eq "n/a" || $self->{Version} eq "n/a" || $self->{CodeName} eq "n/a") {
		fatal('Can not guess distribution-specific information');
	}

	debug ("Found $self->{Distribution} $self->{Version} $self->{CodeName}");

	$self;
}

=item getDistribution()

Return distributor ID.

Return string

=cut

sub getDistribution
{
	my $self = shift;

	$self->loadInformation() if ! $self->{Distribution};
	$self->{Distribution};
}

=item getVersion()

Return distribution version.

Return string

=cut

sub getVersion
{
	my $self = shift;

	$self->loadInformation() if ! $self->{Version};
	$self->{Version};
}

=item getCodeName()

Returns distribution codename.

Return string

=cut

sub getCodeName
{
	my $self = shift;

	$self->loadInformation() if ! $self->{CodeName};
	$self->{CodeName};
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

Called by new(). Initialize instance.

=cut

sub _init()
{
	my $self = shift;

	$self->{Distribution} = undef;
	$self->{Version} = undef;
	$self->{CodeName} = undef;
}

=back

=head1 AUTHORS

 - Daniel Andreca <sci2tech@gmail.com>
 - Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
