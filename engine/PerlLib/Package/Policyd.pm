#!/usr/bin/perl

=head1 NAME

Package::Policyd - i-MSCP Policyd Weight configurator package

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Package::Policyd;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Config;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This is the Policyd Weight configurator package for i-MSCP

 Perl policy daemon for the Postfix MTA Its intended to eliminate forged envelope senders and HELOs
(i.e. in bogus mails). It allows you to score DNSBLs (RBL/RHSBL), HELO, MAIL FROM and client IP addresses
before any queuing is done. It allows you to REJECT messages which have a score higher than allowed, providing
improved blocking of spam and virus mails. policyd-weight caches the most frequent client/sender combinations
(SPAM as well as HAM) to reduce the number of DNS queries.

 Project homepage: http://www.policyd-weight.org/

=head1 PUBLIC METHODS


=item registerSetupHooks(\%hooksManager)

 Register setup hook functions

 Param iMSCP::HooksManager instance
 Return int 0 on success, 1 on failure

=cut

sub registerSetupHooks($$)
{
	my ($self, $hooksManager) = @_;

	require Package::Policyd::Installer;
    Package::Policyd::Installer->getInstance()->registerSetupHooks($hooksManager);
}

=item install()

 Process install tasks

 Return int 0 on success, 1 on failure

=cut

sub install
{
	require Package::Policyd::Installer;
	Package::Policyd::Installer->getInstance()->install();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Package::Policyd

=cut

sub _init
{
	my $self = $_[0];

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/policyd";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	tie %{$self->{'config'}}, 'iMSCP::Config', 'fileName' => "$self->{'cfgDir'}/policyd.data";

	$self;
}

=back

=head1 AUTHORS

 - Daniel Andreca <sci2tech@gmail.com>
 - Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
