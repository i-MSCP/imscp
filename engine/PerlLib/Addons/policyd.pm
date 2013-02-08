#!/usr/bin/perl

=head1 NAME

Addons::policyd - i-MSCP Policyd Weight configurator addon

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::policyd;

use strict;
use warnings;
use iMSCP::Debug;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This is the Policyd Weight configurator addon for i-MSCP.

 Perl policy daemon for the Postfix MTA Its intended to eliminate forged envelope senders and HELOs
(i.e. in bogus mails). It allows you to score DNSBLs (RBL/RHSBL), HELO, MAIL FROM and client IP addresses
before any queuing is done. It allows you to REJECT messages which have a score higher than allowed, providing
improved blocking of spam and virus mails. policyd-weight caches the most frequent client/sender combinations
(SPAM as well as HAM) to reduce the number of DNS queries.

 Project homepage: http://www.policyd-weight.org/

=head1 CLASS METHODS

=over 4

=item factory()

 Implement singleton design pattern. Return instance of this class.

 Return Addons::policyd - Instance of the Addons::policyd class

=cut

sub factory
{
	Addons::policyd->new();
}

=item registerSetupHooks($hooksManager)

 Register setup hook functions.

 Param iMSCP::HooksManager instance
 Return int 0 on success, 1 on failure

=cut

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	require Addons::policyd::installer;
    Addons::policyd::installer->new()->registerSetupHooks($hooksManager);
}

=item install()

 Run the install method on the policyd addon installer.

 Return int 0 on success, 1 on failure

=cut

sub install
{
	my $self = shift;

	require Addons::policyd::installer;
	Addons::policyd::installer->new()->install();
}

=back

=head1 AUTHORS

 - Daniel Andreca <sci2tech@gmail.com>

=cut

1;
