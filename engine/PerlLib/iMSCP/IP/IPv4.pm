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
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::IP::IPv4;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub loadIpConfiguredIps{
	my $self = shift;

	unless($self->{loadedIP}){

		my ($rs, $stdout, $stderr);
		my $ips = {};

		$rs = execute("$main::imscpConfig{'CMD_IFCONFIG'} -a", \$stdout, \$stderr);
		debug("$stdout") if ($stdout);
		error("$stderr") if ($stderr);
		return $rs if $rs;

		while($stdout =~ m/^([^\s]+)\s{1,}[^\n]*\n(?:(?:\s[^\d]+:)?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})[^\n]*\n)?/mgi){
			if($1 ne 'lo'){
				my @cards =split(':', $1);
				my $card = shift(@cards);
				my $slot = shift(@cards) || 0;
				$self->{cards}->{$card}->{'1Slot'} = $slot + 1 if (!$self->{cards}->{$card}->{'1Slot'} || $self->{cards}->{$card}->{'1Slot'} <= $slot);
				if($2){
					$self->{ips}->{$2} = {} unless $self->{ips}->{$2} || $2;
					$self->{ips}->{$2}->{card} = $card;
					$self->{ips}->{$2}->{vcard} = $1 if $1 ne $card;
				}
			}
		}

		$self->{loadedIP} = 1
	}
	0;
}

sub loadNetworkCards{
	my $self = shift;

	my ($rs, $stdout, $stderr);

	unless($self->{loadedCards}){

		$self->loadIpConfiguredIps();

		$rs = execute("$main::imscpConfig{'CMD_IFCONFIG'}", \$stdout, \$stderr);
		debug("$stdout") if ($stdout);
		error("$stderr") if ($stderr);
		return $rs if $rs;

		while($stdout =~ m/^([^\s]+)\s{1,}[^\n]*\n/mgi){
			debug("$1") if $1;
			if($1 ne 'lo'){
				my @cards =split(':', $1);
				my $card = shift(@cards);
				$self->{cards}->{$card}->{up} = 'yes';
			}
		}

		$self->{loadedCards} = 1
	}

	0;
}

sub getIPs{
	my $self = shift;


	$self->loadIpConfiguredIps();

	debug("Ip`s: ". join( ' ', keys %{$self->{ips}} ));

	return (wantarray ? keys %{$self->{ips}} : join( ' ', keys %{$self->{ips}} ));
}

sub getNetworkCards{
	my $self = shift;

	$self->loadNetworkCards();

	debug("Network cards`s: ". join( ' ', keys %{$self->{cards}} ));

	return (wantarray ? keys %{$self->{cards}} : join( ' ', keys %{$self->{cards}} ));
}

sub getCardByIP{

	my $self	= shift;
	my $ip		= shift;

	$self->loadIpConfiguredIps();

	debug("Network card having ip $ip: ". (exists $self->{ips}->{$ip} ? $self->{ips}->{$ip} : 'not exists'));

	return (exists $self->{ips}->{$ip} ? $self->{ips}->{$ip} : 0);
}

sub addedToVCard{

	my $self = shift;
	my $ip		= shift;

	$self->loadIpConfiguredIps();

	debug("Virtual network card having ip $ip: ". (exists $self->{ips}->{$ip}->{vcard} ? $self->{ips}->{$ip}->{vcard} : 'not exists'));

	return (exists $self->{ips}->{$ip}->{vcard} ? $self->{ips}->{$ip}->{vcard} : 0);
}

sub existsNetCard{

	my $self	= shift;
	my $card	= shift;

	$self->loadNetworkCards();

	debug("Network card $card exists? ". (exists $self->{cards}->{$card} ? 'yes' : 'no'));

	return (exists $self->{cards}->{$card});
}

sub getFirstFreeSlotOnCard{

	my $self	= shift;
	my $card	= shift;
	my $reserve	= shift || 0;

	$self->loadNetworkCards();

	my $slot = $self->{cards}->{$card}->{'1Slot'};

	$self->{cards}->{$card}->{'1Slot'}++ if $reserve;

	debug("First slot on network card $card is $slot");

	$slot;
}

sub isCardUp{
	my $self	= shift;
	my $card	= shift;

	$self->loadNetworkCards();

	debug("Network card $card is up? ". (exists $self->{cards}->{$card}->{up} ? 'yes' : 'no'));

	return (exists $self->{cards}->{$card}->{up});
}

sub isValidIp{
	my $self	= shift;
	my $ip	= shift;

	use Data::Validate::IP qw/is_ipv4/;

	debug("Ip is ipv4? ". (is_ipv4($ip) ? 'yes' : 'no'));

	return (is_ipv4($ip));
}

1;
