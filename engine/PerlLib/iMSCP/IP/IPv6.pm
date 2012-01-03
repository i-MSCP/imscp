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

package iMSCP::IP::IPv6;

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

		while($data =~ m/^(([^\s]+).*?)\n(\n|$)/smgi){
			my $netCardName = (split(':', $2))[0];
			my $netCardData = $1;
			$self->{cards}->{$netCardName} = {} if $netCardName ne 'lo';
			while($netCardData =~ /\s+[^\s]+\s[^\s:]+:\s([0-9a-f:]+)(\/[^s]+)?\s/img){
				my $ip = $self->normalize($1);
				push(@{$self->{cards}->{$netCardName}->{ips}}, $ip) if $netCardName ne 'lo';
				$self->{ips}->{$ip} = $netCardName if $netCardName ne 'lo';
			}
		}
		$self->{_loadedIPs} = 1
	}
	0;
}

sub normalize{
	my $self	= shift;
	my $ip		= lc(shift);
	my @result;

	my @parts = split(':', $ip);
	foreach(@parts){
		my $segment = $_;
		unless ($segment eq ''){
			$segment = "0$segment" for((length($_)+1)..4);
			push(@result, $segment);
		} else {
			push(@result, '0000') for((@parts) .. 8);
		}
	}
	join(':', @result);
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

		$self->{_loadedCards} = 1
	}
	0;
}

sub addedToVCard{
	0;
}

sub getCardByIP{
	my $self	= shift;
	my $ip		= shift;

	$ip = $self->normalize($ip);

	debug("Network card having ip $ip: ". (exists $self->{ips}->{$ip} ? $self->{ips}->{$ip} : 'not exists'));

	return (exists $self->{ips}->{$ip} ? $self->{ips}->{$ip} : 0);
}

sub isValidIp{
	my $self	= shift;
	my $ip		= shift;

	use Data::Validate::IP qw/is_ipv6/;

	debug("Ip is ipv6? ". (is_ipv6($ip) ? 'yes' : 'no'));
	return (is_ipv6($ip) ? 1 : 0);
}

sub attachIpToNetCard{
	my $self	= shift;
	my $card	= shift;
	my $ip		= shift;

	$ip = $self->normalize($ip);

	my $fCard = $self->getCardByIP($ip);

	return 0 if($fCard eq $card);
	return 1 if($fCard && $fCard ne $card);
	return 1 unless($self->existsNetCard($card));
	return 1 unless($self->isValidIp($ip));

	my ($stdout, $stderr);

	my $rs = execute("$main::imscpConfig{'CMD_IFCONFIG'} $card inet6 add $ip/64", \$stdout, \$stderr);
	debug("$stdout")if $stdout;
	error("$stderr")if $stderr;

	$rs;
}

sub detachIpFromNetCard{
	my $self	= shift;
	my $ip		= shift;
	my $card;

	$ip = $self->normalize($ip);

	my $card = $self->getCardByIP($ip);

	return 1 unless($card);
	return 1 unless($self->isValidIp($ip));

	my ($stdout, $stderr);

	my $rs = execute("$main::imscpConfig{'CMD_IFCONFIG'} $card inet6 del $ip/64", \$stdout, \$stderr);
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
