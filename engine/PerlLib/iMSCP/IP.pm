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
# @license      http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::IP;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;
use Data::Dumper;

use vars qw/@ISA/;
@ISA = ('Common::SimpleClass');
use Common::SimpleClass;

sub loadIPs{
	my $self	= shift;
	my $rs		= 0;

	my ($netCardUp, $configuredIPs, $stderr);

	unless ($self->{_loaded}){
		$rs = execute("$main::imscpConfig{'CMD_IFCONFIG'}", \$netCardUp, \$stderr);
		debug("$netCardUp") if ($netCardUp);
		error("$stderr") if ($stderr);
		return $rs if $rs;

		$rs = execute("$main::imscpConfig{'CMD_IFCONFIG'} -a", \$configuredIPs, \$stderr);
		debug("$configuredIPs") if ($configuredIPs);
		error("$stderr") if ($stderr);
		return $rs if $rs;

		for('IPv4', 'IPv6'){
			my $file	= "iMSCP/IP/$_.pm";
			my $class	= "iMSCP::IP::$_";
			require $file;
			$self->{$_} = $class->new();
			$rs |= $self->{$_}->parseIPs($configuredIPs);
			$rs |= $self->{$_}->parseNetCards($netCardUp);
		}
		$self->{_loaded} = 1;
	}
	$rs;
}

sub getIPs{
	my $self	= shift;
	my $rs		= 0;
	my @ips		= ();

	$rs = $self->loadIPs() unless $self->{_loaded};
	return (wantarray ? () : '') if $rs;

	@ips = (@ips, ($self->{$_}->getIPs())) for('IPv4', 'IPv6');

	debug("Ip`s: ". join( ' ', @ips ));

	return (wantarray ? @ips : join( ' ', @ips ));
}

sub getNetCards{
	my $self	= shift;
	my $rs		= 0;
	my %cards;

	$rs = $self->loadIPs() unless $self->{_loaded};
	return (wantarray ? () : '') if $rs;

	%cards = (%cards, map { $_ => undef }($self->{$_}->getNetCards())) for('IPv4', 'IPv6');

	debug("Network cards`s: ". join(' ', keys %cards));

	return (wantarray ? keys %cards : join(' ', keys %cards));
}

sub getCardByIP{
	my $self	= shift;
	my $ip		= shift;
	my $rs		= 0;
	my $card;

	$rs = $self->loadIPs() unless $self->{_loaded};
	return (wantarray ? () : '') if $rs;

	for('IPv4', 'IPv6'){
		$card = $self->{$_}->getCardByIP($ip);
		last if $card;
	}

	debug("Network card having ip $ip: ". ($card ? $card : 'not exists'));
	return ($card ? $card : '');
}

sub addedToVCard{
	my $self	= shift;
	my $ip		= shift;
	my $rs		= 0;

	$rs = $self->loadIPs() unless $self->{_loaded};
	return (wantarray ? () : '') if $rs;

	for('IPv4', 'IPv6'){
		$rs = $self->{$_}->addedToVCard($ip);
		last if $rs;
	}

	debug("Card having ip $ip: ". ($rs ? $rs : 'not exists'));
	$rs;
}

sub existsNetCard{
	my $self	= shift;
	my $card	= shift;
	my $rs		= 0;

	$rs = $self->loadIPs() unless $self->{_loaded};
	return (wantarray ? () : '') if $rs;

	for('IPv4', 'IPv6'){
		$rs |= $self->{$_}->existsNetCard($card);
		last if $rs;
	}

	debug("Network card $card exists? ". ($rs ? 'yes' : 'no'));
	$rs;
}


sub isCardUp{
	my $self	= shift;
	my $card	= shift;
	my $rs		= 0;

	$rs = $self->loadIPs() unless $self->{_loaded};
	return (wantarray ? () : '') if $rs;

	for('IPv4', 'IPv6'){
		$rs |= $self->{$_}->isCardUp($card);
		last if $rs;
	}

	debug("Network card $card is up? ". ($rs ? 'yes' : 'no'));
	$rs;
}

sub isValidIp{
	my $self	= shift;
	my $ip		= shift;
	my $rs		= 0;

	$rs = $self->loadIPs() unless $self->{_loaded};
	return (wantarray ? () : '') if $rs;

	for('IPv4', 'IPv6'){
		$rs |= $self->{$_}->isValidIp($ip);
		last if $rs;
	}

	debug("Ip is valid? ". ($rs ? 'yes' : 'no'));
	$rs;
}

sub getIpType{
	my $self	= shift;
	my $ip		= shift;

	my $rs = $self->loadIPs() unless $self->{_loaded};
	return (wantarray ? () : undef) if $rs;

	debug("Ip $ip is ".($self->{IPv4}->isValidIp($ip) ? 'ipv4' : 'ipv6'));
	$self->{IPv4}->isValidIp($ip) ? 'ipv4' : 'ipv6';
}

sub normalize{
	my $self	= shift;
	my $ip		= shift;
	return $ip if $self->{IPv4}->isValidIp($ip);
	return  $self->{IPv6}->normalize($ip) if $self->{IPv6}->isValidIp($ip);
}

sub attachIpToNetCard{
	my $self	= shift;
	my $card	= shift;
	my $ip		= shift;
	my $rs		= 0;

	for('IPv4', 'IPv6'){
		$rs = $self->{$_}->attachIpToNetCard($card, $ip);
		last unless $rs;
	}

	$rs;
}

sub detachIpFromNetCard{
	my $self	= shift;
	my $ip		= shift;
	my $rs		= 0;

	for('IPv4', 'IPv6'){
		$rs = $self->{$_}->detachIpFromNetCard($ip);
		last unless $rs;
	}
	debug("Succesfully detached $ip ") unless $rs;
	error("Can not detach $ip") if $rs;

	$rs;
}

sub reset{
	my $self	= shift;

	for('IPv4', 'IPv6'){
		$self->{$_}->reset();
	}
	delete $self->{_loaded};
	return $self->loadIPs();
}

1;
