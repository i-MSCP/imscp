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
use iMSCP::Dir;
use File::Basename;
use iMSCP::Templator;
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

	tie %self::bindConfig, 'iMSCP::Config','fileName' => $conf, 'noerrors' => 1;

	if(-f $oldConf) {
		tie %self::bindOldConfig, 'iMSCP::Config','fileName' => $oldConf, noerrors => 1;
		%self::bindConfig = (%self::bindConfig, %self::bindOldConfig);
	}

	$self->{'hooksManager'}->trigger('afterNamedInitInstaller', $self, 'bind');

	$self;
}

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	# Adding bind installer dialog in setup dialog stack
	$hooksManager->register(
		'beforeSetupDialog', sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askMode(@_) }); 0; }
	);
}

sub askMode
{
	my $self = shift;
	my $dialog = shift;
	my $mode = $main::preseed{'BIND_MODE'} || $self::bindConfig{'BIND_MODE'};

	my $primaryDnsIps = ($mode eq 'slave')
		? $main::preseed{'PRIMARY_DNS'} || $self::bindConfig{'PRIMARY_DNS'} : $main::imscpConfig{'BASE_SERVER_IP'};

	my $secondaryDnsIps = ($mode eq 'master')
		? $main::preseed{'SECONDARY_DNS'} || $self::bindConfig{'SECONDARY_DNS'} : 'no';

	my $ip = iMSCP::IP->new();
	my @ips = ();
	my $rs = 0;

	# Retrieving master DNS server ips if any
	@ips = (@ips, split(';', $primaryDnsIps)) if $primaryDnsIps;

	# Retrieving slave DNS server ips if any
	@ips = (@ips, split(';', $secondaryDnsIps)) if $secondaryDnsIps && $secondaryDnsIps ne 'no';

	# In case slave mode is selected, we must have a least one IP address in the stack.
	# If it's not the case, we force dialog to be show
	$mode = '' if $mode eq 'slave' && ! @ips;

	# Checl each IP address. If one is invalid, we force dialog to be show
	for (@ips) {
		if($_ && $_ ne 'no' && ! $ip->isValidIp($_)) {
			debug("$_ is invalid ip");

			$self::bindConfig{'BIND_MODE'} = '';
			$self::bindConfig{'PRIMARY_DNS'} = '';
			$self::bindConfig{'SECONDARY_DNS'} = '';

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
	} elsif(defined $main::preseed['BIND_MODE']) {
		$self::bindConfig{'BIND_MODE'} = $mode;
		$self::bindConfig{'PRIMARY_DNS'} = $primaryDnsIps;
		$self::bindConfig{'SECONDARY_DNS'} = $secondaryDnsIps;
	}

	$rs;
}

sub askOtherDns
{
	my $self = shift;
	my $dialog = shift;
	my $mode = $self::bindConfig{'BIND_MODE'};

	my $primaryDnsIps = ($mode eq 'slave')
		? ($self::bindConfig{'PRIMARY_DNS'} ne $main::imscpConfig{'BASE_SERVER_IP'})
			? $self::bindConfig{'PRIMARY_DNS'}
			: ''
		: $main::imscpConfig{'BASE_SERVER_IP'};

	my $secondaryDnsIps = ($mode eq 'master') ? $self::bindConfig{'SECONDARY_DNS'} : 'no';

	my ($rs, $out) = (0, '');

	if($mode eq 'master') {
		($rs, $out) = $dialog->radiolist(
			"\nDo you want add slave DNS server(s)?", ['no', 'yes'], $secondaryDnsIps ne 'no' ? 'yes' : 'no'
		);

		if($rs != 30 && $out eq 'no') {
			$self::bindConfig{'PRIMARY_DNS'} = $primaryDnsIps;
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
			my $ips = $mode eq 'slave'
				? $primaryDnsIps
				: $secondaryDnsIps ne 'no' ? $secondaryDnsIps : '';

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
				$self::bindConfig{'PRIMARY_DNS'} = $primaryDnsIps;
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

	for(
		$self::bindConfig{'BIND_CONF_FILE'},
		$self::bindConfig{'BIND_LOCAL_CONF_FILE'},
		$self::bindConfig{'BIND_OPTIONS_CONF_FILE'}
	) {
		$rs = $self->_bkpConfFile($_);
		return $rs if $rs;
	}

	$rs = $self->_switchTasks();
	return $rs if $rs;

	$rs = $self->_buildConf();
	return $rs if $rs;

	$rs = $self->_addMasterZone();
	return $rs if $rs;

	$self->_saveConf();
}

sub _bkpConfFile
{
	my $self = shift;
	my $cfgFile = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeNamedBkpConfFile', $cfgFile);
	return $rs if $rs;

	if(-f $cfgFile) {
		my $file = iMSCP::File->new('filename' => $cfgFile);
		my $filename = fileparse($cfgFile);

		if(! -f "$self->{'bkpDir'}/$filename.system") {
			$rs = $file->copyFile("$self->{'bkpDir'}/$filename.system");
			return $rs if $rs;
		} else {
			$rs = $file->copyFile("$self->{'bkpDir'}/$filename." . time);
			return $rs if $rs;
		}
	}

	$self->{'hooksManager'}->trigger('afterNamedBkpConfFile', $cfgFile);
}

sub _switchTasks
{
	my $self = shift;
	my $rs = 0;

	my $slaveDbDir = iMSCP::Dir->new('dirname' => "$self::bindConfig{'BIND_DB_DIR'}/slave");

	if($self::bindConfig{'BIND_MODE'} eq 'slave') {
		$rs = $slaveDbDir->make(
			{
				'user' => $main::imscpConfig{'ROOT_USER'},
				'group' => $self::bindConfig{'BIND_GROUP'},
				'mode' => '0755'
			}
		);
		return $rs if $rs;

		my ($stdout, $stderr);

		$rs = execute("$main::imscpConfig{'CMD_RM'} -f $self->{'wrkDir'}/*.db", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		$rs = execute("$main::imscpConfig{'CMD_RM'} -f $self::bindConfig{'BIND_DB_DIR'}/*.db", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	} else {
		$rs = $slaveDbDir->remove() if -d "$self::bindConfig{'BIND_DB_DIR'}/slave";
	}

	$rs;
}

sub _buildConf
{
	my $self = shift;
	my $rs = 0;

	for('BIND_CONF_FILE', 'BIND_LOCAL_CONF_FILE', 'BIND_OPTIONS_CONF_FILE') {

		# Handle case were the file is not provided by specfic distro
		next if ! defined $self::bindConfig{$_} || $self::bindConfig{$_} eq '';

		# Retrieving file basename
		my $filename = fileparse($self::bindConfig{$_});

		# Loading the template file
		my $cfgTpl = iMSCP::File->new('filename' => "$self->{'cfgDir'}/$filename")->get();
		unless(defined $cfgTpl) {
			error("Unable to read $self->{'cfgDir'}/$filename");
			return 1;
		}

		$rs = $self->{'hooksManager'}->trigger('beforeNamedBuildConf', \$cfgTpl, $filename);
		return $rs if $rs;

		# Re-add custom bind data snippet
		if(-f "$self->{'wrkDir'}/$filename") {
			my $wrkFile = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$filename");

			my $wrkFileContent = $wrkFile->get();
			unless(defined $wrkFileContent) {
				error("Unable to read $self->{'wrkDir'}/$filename");
				return 1;
			}

			my $customBindDataBeginTag = "// bind custom data BEGIN.\n";
			my $customBindDataEndTag = "// bind custom data END.\n";
			my $customBindDataBlock = getBloc(
				$customBindDataBeginTag, $customBindDataEndTag, $wrkFileContent, 'includeTags'
			);

			if($customBindDataBlock ne '') {
				$cfgTpl = replaceBloc($customBindDataBeginTag, $customBindDataEndTag, $customBindDataBlock, $cfgTpl);
			}
		}

		if($filename eq 'named.conf' && ! -f "$self::bindConfig{'BIND_CONF_DIR'}/bind.keys") {
			$cfgTpl =~ s%include "$self::bindConfig{'BIND_CONF_DIR'}/bind.keys";\n%%;
		}

		$rs = $self->{'hooksManager'}->trigger('afterNamedBuildConf', \$cfgTpl, $filename);
		return $rs if $rs;

		# Storing new file in working directory
		my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$filename");

		$rs = $file->set($cfgTpl);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $self::bindConfig{'BIND_GROUP'});
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;

		# Installing new file in production directory
		$rs = $file->copyFile($self::bindConfig{$_});
		return $rs if $rs;
	}

	$rs;
}

sub _addMasterZone
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeNamedAddMasterZone');
	return $rs if $rs;

	require Servers::named::bind;

	$rs = Servers::named::bind->getInstance()->addDmn(
		{
			DMN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
			DMN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
			MX => ''
		}
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterNamedAddMasterZone');
}

sub _saveConf
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
