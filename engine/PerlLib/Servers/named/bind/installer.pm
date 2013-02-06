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
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::named::bind::installer;

use strict;
use warnings;
use iMSCP::Debug;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedInitInstaller', $self, 'bind');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/bind";
	$self->{'bkpDir'} = "$self->{cfgDir}/backup";
	$self->{'wrkDir'} = "$self->{cfgDir}/working";

	my $conf = "$self->{cfgDir}/bind.data";
	my $oldConf = "$self->{cfgDir}/bind.old.data";

	tie %self::bindConfig, 'iMSCP::Config','fileName' => $conf, noerrors => 1;

	if(-f $oldConf) {
		tie %self::bindOldConfig, 'iMSCP::Config','fileName' => $oldConf, noerrors => 1;
		%self::bindConfig = (%self::bindConfig, %self::bindOldConfig);
	}

	iMSCP::HooksManager->getInstance()->trigger('afterNamedInitInstaller', $self, 'bind');

	0;
}

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	$hooksManager->trigger('beforeNamedRegisterSetupHooks', $hooksManager, 'bind') and return 1;

	# Add bind installer dialog in setup dialog stack
	$hooksManager->register(
		'beforeSetupDialog',
		sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askMode(@_) }); 0; }
	) and return 1;

	$hooksManager->trigger('afterNamedRegisterSetupHooks', $hooksManager, 'bind');
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

			for(qw/BIND_MODE PRIMARY_DNS SECONDARY_DNS/){
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
			"\nDo you want add slave DNS server(s)?",
			['no', 'yes'],
			$slaveDns ne 'no' ? 'yes' : 'no'
		);

		if($rs != 30 && $out eq 'no') {
			$self::bindConfig{'PRIMARY_DNS'} = $main::imscpConfig{'BASE_SERVER_IP'}; # just to avoid empty value
			$self::bindConfig{'SECONDARY_DNS'} = 'no';
			return 0;
		}
	}

	if($rs != 30) {
		my @ips = ();

		use iMSCP::IP;
		my $ip = iMSCP::IP->new();
		my $msg = '';

		do {
			my $rmode = $mode eq 'master' ? 'slave' : 'master';
			my $ips = $rmode eq 'master'
				? $masterDns ne $main::imscpConfig{'BASE_SERVER_IP'} ? $masterDns : '' : $slaveDns;

			$ips = '' if $ips eq 'no';
			@ips = split ';', $ips if $ips;

			($rs, $_) = $dialog->inputbox(
				"\nPlease, enter IP address(es) for master DNS server(s), each separated by space: $msg", "@ips"
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

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedInstall', 'bind') and return 1;

	$rs |= $self->bkpConfFile($self::bindConfig{'BIND_CONF_FILE'});
	$rs |= $self->buildConf();
	$rs |= $self->addMasterZone();
	$rs |= $self->saveConf();

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterNamedInstall', 'bind');

	$rs;
}

sub bkpConfFile
{
	my $self = shift;
	my $cfgFile = shift;
	my $timestamp = time;

	use File::Basename;

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedBkpConfFile', $cfgFile) and return 1;

	if(-f $cfgFile){
		my $file = iMSCP::File->new( filename => $cfgFile );
		my ($filename, $directories, $suffix) = fileparse($cfgFile);

		if(! -f "$self->{bkpDir}/$filename$suffix.system") {
			$file->copyFile("$self->{bkpDir}/$filename$suffix.system") and return 1;
		} else {
			$file->copyFile("$self->{bkpDir}/$filename$suffix.$timestamp") and return 1;
		}
	}

	iMSCP::HooksManager->getInstance()->trigger('afterNamedBkpConfFile', $cfgFile);
}

sub buildConf
{
	my $self = shift;
	my ($rs, $rdata, $cfgTpl, $cfg, $err);

	use iMSCP::File;

	## Building new configuration file

	# Loading the system main configuration file named.conf.system if it exists
	if(-f "$self->{bkpDir}/named.conf.system") {
		$cfg = iMSCP::File->new(filename => "$self->{bkpDir}/named.conf.system")->get();
		return 1 if(!$cfg);

		# Adjusting the configuration if needed
		$cfg =~ s/listen-on ((.*) )?{ 127.0.0.1; };/listen-on $1 { any; };/;
		$cfg .= "\n";
	} else {
		warning("Can't find the default distribution file for named...");
		$cfg = '';
	}

	# Loading the template from /etc/imscp/bind/named.conf
	$cfgTpl = iMSCP::File->new(filename => "$self->{cfgDir}/named.conf")->get();
	return 1 if(!$cfgTpl);

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedBuildConf', \$cfgTpl, 'named.conf') and return 1;

	# Building new file
	$cfg .= $cfgTpl;

	iMSCP::HooksManager->getInstance()->trigger('afterNamedBuildConf', \$cfg, 'named.conf') and return 1;

	## Storage and installation of new file

	# Storing new file in the working directory
	my $file = iMSCP::File->new(filename => "$self->{wrkDir}/named.conf");
	$file->set($cfg) and return 1;
	$file->save() and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;
	$file->mode(0644) and return 1;

	# Install the new file in the production directory
	$file->copyFile($self::bindConfig{'BIND_CONF_FILE'}) and return 1;

	0;
}

sub addMasterZone
{
	my $self = shift;

	use Servers::named;

	my $named = Servers::named->factory();

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedAddMasterZone') and return 1;

	my $rs = $named->addDmn(
		{
			DMN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
			DMN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
			MX => ''
		}
	);
	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterNamedAddMasterZone');
}

sub saveConf
{
	my $self = shift;
	my $rs = 0;

	use iMSCP::File;

	my $file = iMSCP::File->new(filename => "$self->{cfgDir}/bind.data");

	$self::bindConfig{'BIND_MODE'} = $self::bindOldConfig{'BIND_MODE'}
		if $self::bindOldConfig{'BIND_MODE'} && $self::bindConfig{'BIND_MODE'} ne $self::bindOldConfig{'BIND_MODE'};

	$self::bindConfig{'PRIMARY_DNS'} = $self::bindOldConfig{'PRIMARY_DNS'}
		if $self::bindOldConfig{'PRIMARY_DNS'} && $self::bindConfig{'PRIMARY_DNS'} ne $self::bindOldConfig{'PRIMARY_DNS'};

	$self::bindConfig{'SECONDARY_DNS'} = $self::bindOldConfig{'SECONDARY_DNS'}
		if $self::bindOldConfig{'SECONDARY_DNS'} && $self::bindConfig{'SECONDARY_DNS'} ne $self::bindOldConfig{'SECONDARY_DNS'};

	my $cfg = $file->get() or return 1;

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedSaveConf', \$cfg, 'bind.old.data') and return 1;

	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$file = iMSCP::File->new(filename => "$self->{cfgDir}/bind.old.data");
	$rs |= $file->set($cfg);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterNamedSaveConf', 'bind.old.data');

	$rs;
}

1;
