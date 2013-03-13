#!/usr/bin/perl

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
# @author		Laurent Declercq <l;declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::named::bind::installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Config;
use iMSCP::IP;
use iMSCP::File;
use File::Basename;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger('beforeNamedInitInstaller', $self, 'bind');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/bind";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	my $conf = "$self->{'cfgDir'}/bind.data";
	my $oldConf = "$self->{'cfgDir'}/bind.old.data";

	tie %self::bindConfig, 'iMSCP::Config','fileName' => $conf, noerrors => 1;

	if(-f $oldConf) {
		tie %self::bindOldConfig, 'iMSCP::Config','fileName' => $oldConf, noerrors => 1;
		%self::bindConfig = (%self::bindConfig, %self::bindOldConfig);
	}

	$self->{'hooksManager'}->trigger('afterNamedInitInstaller', $self, 'bind');

	0;
}

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	# Add bind installer dialog in setup dialog stack
	$hooksManager->register(
		'beforeSetupDialog', sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askMode(@_) }); 0; }
	);
}

sub askMode
{
	my $self = shift;
	my $dialog = shift;
	my $mode = $main::preseed{'BIND_MODE'} || $self::bindConfig{'BIND_MODE'} || $self::bindOldConfig{'BIND_MODE'} || '';

	my $primaryDnsIps = $main::preseed{'PRIMARY_DNS'} || $self::bindConfig{'PRIMARY_DNS'} ||
		$self::bindOldConfig{'PRIMARY_DNS'} || '';

	my $secondaryDnsIps = $main::preseed{'SECONDARY_DNS'} || $self::bindConfig{'SECONDARY_DNS'} ||
		$self::bindOldConfig{'SECONDARY_DNS'} || '';

	my $ip = iMSCP::IP->new();
	my @ips = ();
	my $rs = 0;

	# Retrieve master DNS server ips if any
	@ips = (@ips, split(';', $primaryDnsIps)) if $primaryDnsIps;

	# Retrieve slave DNS server ips if any
	@ips = (@ips, split(';', $secondaryDnsIps)) if $secondaryDnsIps && $secondaryDnsIps ne 'no';

	if($mode eq 'slave' && ! @ips) {
		unshift(@ips, 'wrongip');
    }

	for (@ips) {
		if($_ && ! $ip->isValidIp($_)) {
			debug("$_ is invalid ip");

			for(qw/BIND_MODE PRIMARY_DNS SECONDARY_DNS/) {
				$self::bindConfig{$_} = '';
				$self::bindOldConfig{$_} = '';
			}

			last;
		}
	}

	if($main::reconfigure ~~ ['named', 'servers', 'all', 'forced'] || $mode !~ /^master|slave$/) {
		($rs, $mode) = $dialog->radiolist(
			"\nSelect bind mode", ['master', 'slave'], $mode eq 'slave' ? 'slave' : 'master'
		);

		if($rs != 30) {
			$self::bindConfig{'BIND_MODE'} = $mode;
			$rs = $self->askOtherDns($dialog);
		}
	}

	$self::bindConfig{'BIND_MODE'} = $mode if $rs != 30; # Really needed for preseed mode

	$rs;
}

sub askOtherDns
{
	my $self = shift;
	my $dialog = shift;
	my $mode = $self::bindConfig{'BIND_MODE'};
	my $masterDns = $main::preseed{'PRIMARY_DNS'} || $self::bindConfig{'PRIMARY_DNS'} || 'no';
	my $slaveDns = $main::preseed{'SECONDARY_DNS'} || $self::bindConfig{'SECONDARY_DNS'} || 'no';

	my ($rs, $out) = (0, '');

	if($mode eq 'master') {
		($rs, $out) = $dialog->radiolist(
			"\nDo you want add slave DNS server(s)?", ['no', 'yes'], $slaveDns ne 'no' ? 'yes' : 'no'
		);

		if($rs != 30 && $out eq 'no') {
			$self::bindConfig{'PRIMARY_DNS'} = $main::imscpConfig{'BASE_SERVER_IP'}; # just to avoid empty value
			$self::bindConfig{'SECONDARY_DNS'} = 'no';
			return 0;
		}
	}

	if($rs != 30) {
		my @ips = ();
		my $ip = iMSCP::IP->new();
		my $msg = '';

		my $trMode = ($mode eq 'slave') ? 'master' : 'slave';

		do {
			my $ips = ($mode eq 'master') ? ($masterDns ne $main::imscpConfig{'BASE_SERVER_IP'}) ? $masterDns : '' : $slaveDns;

			$ips = '' if $ips eq 'no';
			@ips = split ';', $ips if $ips;

			($rs, $_) = $dialog->inputbox(
				"\nPlease, enter IP address(es) for $trMode DNS server(s), each separated by space: $msg", "@ips"
			);

			$msg = '';
			@ips = split;

			if($rs != 30) {
				if("@ips" eq '') {
					$msg = "\n\n\\Z1You must enter a least one IP address.\\Zn\n\nPlease, try again:";
				} else {
					for(@ips) {
						$rs = 1 if ! $ip->isValidIp($_) || $_ eq '127.0.0.1';

						if($rs) {
							$msg = "\n\n\\Z1Wrong IP address found.\\Zn\n\nPlease, try again:";
							last if $rs;
						}
					}
				}
			}
		} while($rs != 30 && $msg);

		if($rs != 30) {
			if($mode eq 'master') {
				$self::bindConfig{'PRIMARY_DNS'} = $main::imscpConfig{'BASE_SERVER_IP'}; # just to avoid empty value
				$self::bindConfig{'SECONDARY_DNS'} = join ';', @ips;
			} else { # Only slave server
				$self::bindConfig{'PRIMARY_DNS'} = join ';', @ips;
        		$self::bindConfig{'SECONDARY_DNS'} = 'no';
			}
		}
	}

	$rs;
}

sub install
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->bkpConfFile($self::bindConfig{'BIND_CONF_FILE'});
	return $rs if $rs;

	$rs = $self->buildConf();
	return $rs if $rs;

	$rs = $self->addMasterZone();
	return $rs if $rs;

	$self->saveConf();
}

sub bkpConfFile
{
	my $self = shift;
	my $cfgFile = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeNamedBkpConfFile', $cfgFile);
	return $rs if $rs;

	if(-f $cfgFile){
		my $file = iMSCP::File->new('filename' => $cfgFile );
		my ($filename, $directories, $suffix) = fileparse($cfgFile);

		if(! -f "$self->{'bkpDir'}/$filename$suffix.system") {
			$rs = $file->copyFile("$self->{'bkpDir'}/$filename$suffix.system");
			return $rs if $rs;
		} else {
			my $timestamp = time;
			$rs = $file->copyFile("$self->{'bkpDir'}/$filename$suffix.$timestamp");
			return $rs if $rs;
		}
	}

	$self->{'hooksManager'}->trigger('afterNamedBkpConfFile', $cfgFile);
}

sub buildConf
{
	my $self = shift;
	my $rs = 0;
	my ($rdata, $cfgTpl, $cfg, $err);

	## Building new configuration file

	# Loading the system main configuration file named.conf.system if it exists
	if(-f "$self->{'bkpDir'}/named.conf.system") {
		$cfg = iMSCP::File->new('filename' => "$self->{'bkpDir'}/named.conf.system")->get();
		return 1 if ! defined $cfg;

		# Adjusting the configuration if needed
		$cfg =~ s/listen-on ((.*) )?{ 127.0.0.1; };/listen-on $1 { any; };/;
		$cfg .= "\n";
	} else {
		warning("Unable to find the default distribution file for named...");
		$cfg = '';
	}

	# Loading the template from /etc/imscp/bind/named.conf
	$cfgTpl = iMSCP::File->new('filename' => "$self->{'cfgDir'}/named.conf")->get();
	return 1 if ! defined $cfgTpl;

	$rs = $self->{'hooksManager'}->trigger('beforeNamedBuildConf', \$cfgTpl, 'named.conf');
	return $rs if $rs;

	# Building new file
	$cfg .= $cfgTpl;

	$rs = $self->{'hooksManager'}->trigger('afterNamedBuildConf', \$cfg, 'named.conf');
	return $rs if $rs;

	## Storage and installation of new file

	# Storing new file in the working directory
	my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/named.conf");

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	# Install the new file in the production directory
	$file->copyFile($self::bindConfig{'BIND_CONF_FILE'});
}

sub addMasterZone
{
	my $self = shift;
	my $rs = 0;

	require Servers::named;

	my $named = Servers::named->factory();

	$rs = $self->{'hooksManager'}->trigger('beforeNamedAddMasterZone');
	return $rs if $rs;

	$rs = $named->addDmn(
		{
			DMN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
			DMN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
			MX => ''
		}
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterNamedAddMasterZone');
}

sub saveConf
{
	my $self = shift;
	my $rs = 0;

	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/bind.data");

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	my $cfg = $file->get();
	unless(defined $cfg) {
		error("Unable to read $self->{'cfgDir'}/bind.data");
		return 1;
	}

	$rs = $self->{'hooksManager'}->trigger('beforeNamedSaveConf', \$cfg, 'bind.old.data');
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/bind.old.data");

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterNamedSaveConf', 'bind.old.data');
}

1;
