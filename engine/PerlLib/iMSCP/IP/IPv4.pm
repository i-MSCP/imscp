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
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::IP::IPv4;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use Data::Dumper;

use vars qw/@ISA/;

@ISA = ('iMSCP::IP::abstractIP');
use iMSCP::IP::abstractIP;

sub parseIPs{
	my $self = shift;
	my $data = shift;

	unless($self->{_loadedIPs}){
		my $ips = {};

		while($data =~ m/^([^\s]+)\s{1,}[^\n]*\n(?:(?:\s[^\d]+:)?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})[^\n]*\n)?/mgi){
			my $_card	= $1;
			my $_ip		= $2;
			if($_card ne 'lo'){
				my @cards = split(':', $_card);
				my $card = shift(@cards);
				my $slot = shift(@cards) || 0;
				$slot = 0 if $slot !~ /^\d*$/;
				$self->{cards}->{$card}->{'1Slot'} = $slot + 1 if (!$self->{cards}->{$card}->{'1Slot'} || $self->{cards}->{$card}->{'1Slot'} <= $slot);
				if($_ip){
					$self->{ips}->{$_ip} = {} unless $self->{ips}->{$_ip} || $_ip;
					$self->{ips}->{$_ip}->{card} = $card;
					$self->{ips}->{$_ip}->{vcard} = $_card if $_card ne $card;
				}
			}
		}
		$self->{_loadedIPs} = 1;
	}
	0;
}

sub parseNetCards{
	my $self = shift;
	my $data = shift;

	unless($self->{_loadedCards}){

		while($data =~ m/^([^\s]+)\s{1,}[^\n]*\n/mgi){
			debug("$1") if $1;
			if($1 ne 'lo'){
				my @cards =split(':', $1);
				my $card = shift(@cards);
				$self->{cards}->{$card}->{up} = 'yes';
			}
		}

		$self->{_loadedCards} = 1;
	}
	0;
}

sub getCardByIP{
	my $self	= shift;
	my $ip		= shift;

	debug("Network card having ip $ip: ". (exists $self->{ips}->{$ip}->{card} ? $self->{ips}->{$ip}->{card} : 'not exists'));

	return (exists $self->{ips}->{$ip}->{card} ? $self->{ips}->{$ip}->{card} : 0);
}

sub addedToVCard{
	my $self	= shift;
	my $ip		= shift;

	debug("Virtual network card having ip $ip: ". (exists $self->{ips}->{$ip}->{vcard} ? $self->{ips}->{$ip}->{vcard} : 'not exists'));

	return (exists $self->{ips}->{$ip}->{vcard} ? $self->{ips}->{$ip}->{vcard} : 0);
}

sub isValidIp{
	my $self	= shift;
	my $ip		= shift;

	use Data::Validate::IP qw/is_ipv4/;

	debug("Ip is ipv4? ". (is_ipv4($ip) ? 'yes' : 'no'));
	return (is_ipv4($ip) ? 1 : 0);
}

sub _getFirstFreeSlotOnCard{

	my $self	= shift;
	my $card	= shift;
	my $reserve	= shift || 0;

	my $slot = $self->{cards}->{$card}->{'1Slot'};

	$self->{cards}->{$card}->{'1Slot'}++ if $reserve;

	debug("First slot on network card $card is $slot");

	$slot;
}

sub attachIpToNetCard{
	my $self	= shift;
	my $card	= shift;
	my $ip		= shift;

	my $fCard = $self->getCardByIP($ip);

	return 0 if($fCard eq $card);
	return 1 if($fCard && $fCard ne $card);
	return 1 unless($self->existsNetCard($card));
	return 1 unless($self->isValidIp($ip));

	my ($stdout, $stderr);

	my $slot = $self->_getFirstFreeSlotOnCard($card, 'reserve');

	my $rs = execute(
		"ifconfig ".
		"$card:$slot ".
		"$ip ".
		"netmask 255.255.255.255 ".
		"up",
		\$stdout,
		\$stderr
	);
	debug("$stdout")if $stdout;
	error("$stderr")if $stderr;

	$rs;
}

sub detachIpFromNetCard{
	my $self	= shift;
	my $ip		= shift;
	my $card;

	return 1 unless($self->getCardByIP($ip));
	return 1 unless($card = $self->addedToVCard($ip));
	return 1 unless($self->isValidIp($ip));

	my ($stdout, $stderr);

	my $rs = execute("ifconfig $card down", \$stdout, \$stderr);
	debug("$stdout")if $stdout;
	error("$stderr")if $stderr;

	$rs;
}

sub reset{
	my $self	= shift;

	delete $self->{_loadedIPs};
	delete $self->{_loadedCards};
}

1;
