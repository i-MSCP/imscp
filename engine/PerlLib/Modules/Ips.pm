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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category		i-MSCP
# @copyright	2010 - 2011 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::Ips;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;
use Data::Dumper;

use vars qw/@ISA/;

@ISA = ('Common::SimpleClass', 'Modules::Abstract');
use Common::SimpleClass;
use Modules::Abstract;

sub _init{
	my $self		= shift;
	$self->{type}	= 'Ips';
}

sub process{

	my $self	= shift;
	my $rs		= 0;

	my $sql = "
		SELECT `domain_ip_id` AS `ip_id`, `ip_number` FROM `domain`
		LEFT JOIN `server_ips` ON `domain`.`domain_ip_id` = `server_ips`.`ip_id`
		WHERE `domain_status` != 'delete'
		UNION
		SELECT `alias_ip_id` AS `ip_id`, `ip_number` FROM `domain_aliasses`
		LEFT JOIN `server_ips` ON `domain_aliasses`.`alias_ip_id` = `server_ips`.`ip_id`
		WHERE `alias_status` NOT IN ('delete', 'ordered')
	";

	my $rdata = iMSCP::Database->factory()->doQuery('ip_number', $sql);

	error("$rdata") and return 1 if(ref $rdata ne 'HASH');

	@{$self->{ips}} = keys %{$rdata};

	$rs = $self->add();

	$rs;
}

sub buildHTTPDData{

	my $self	= shift;

	$self->{httpd} = {
		IPS					=> $self->{ips}
	};

	0;
}

1;
