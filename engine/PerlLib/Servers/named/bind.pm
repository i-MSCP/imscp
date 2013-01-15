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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::named::bind;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::HooksManager;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedInit', $self, 'bind');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/bind";
	$self->{'bkpDir'} = "$self->{cfgDir}/backup";
	$self->{'wrkDir'} = "$self->{cfgDir}/working";
	$self->{'tplDir'}	= "$self->{cfgDir}/parts";

	$self->{'commentChar'} = '#';

	tie %self::bindConfig, 'iMSCP::Config','fileName' => "$self->{cfgDir}/bind.data", noerrors => 1;
	$self->{$_} = $self::bindConfig{$_} for keys %self::bindConfig;

	iMSCP::HooksManager->getInstance()->trigger('afterNamedInit', $self, 'bind');

	$self;
}

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	use Servers::named::bind::installer;
	Servers::named::bind::installer->new()->registerSetupHooks($hooksManager);
}

sub install
{
	my $self = shift;
	my $rs = 0;

	use Servers::named::bind::installer;

	Servers::named::bind::installer->new()->install();
}

sub uninstall
{
	my $self = shift;
	my $rs = 0;

	use Servers::named::bind::uninstaller;

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedUninstall', 'bind') and return 1;

	$rs |= Servers::named::bind::uninstaller->new()->uninstall();

	$rs |= $self->restart();

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterNamedUninstall', 'bind');

	$rs;
}

sub postinstall
{
	my $self = shift;

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedPostinstall') and return 1;

	$self->{'restart'} = 'yes';

	iMSCP::HooksManager->getInstance()->trigger('afterNamedPostinstall')
}

sub restart
{
	my $self = shift;
	my ($rs, $stdout, $stderr);

	use iMSCP::Execute;

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedRestart') and return 1;

	# Reload config
	$rs = execute("$self->{CMD_NAMED} restart", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterNamedRestart');
}

sub incTimeStamp
{
	my $self = shift;
	my $oldZoneFile	= shift;
	my $dmnName = shift;
	my $newZoneFile	= shift || $oldZoneFile;

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedIncTimeStamp');

	use iMSCP::File;
	use iMSCP::Templator;

	# Create or Update serial number according RFC 1912

	# Loading the template from /etc/imscp/bind/parts
	my $entries = iMSCP::File->new(filename => "$self->{tplDir}/db_e.tpl")->get();
	return undef if (!$entries);

	my $tags = { DMN_NAME => $dmnName };
	my $cleanBTag = iMSCP::File->new(filename => "$self->{tplDir}/db_time_b.tpl")->get();
	my $cleanETag = iMSCP::File->new(filename => "$self->{tplDir}/db_time_e.tpl")->get();
	my $bTag = process($tags, $cleanBTag);
	my $eTag = process($tags, $cleanETag);
	return undef if(! $cleanBTag || ! $bTag || ! $cleanETag || ! $eTag);

	my $timeStampBlock = getBloc($bTag, $eTag, $oldZoneFile);
	my $cleanTimeStampBlock	= getBloc($cleanBTag, $cleanETag, $entries);
	my $timestamp;

	my $regExp = '[\s](?:(\d{4})(\d{2})(\d{2})(\d{2})|(\{TIMESTAMP\}))';
	my (undef, undef, undef, $day, $mon, $year) = localtime;
	if((my $tyear, my $tmon, my $tday, my $nn, my $setup) = ($timeStampBlock =~ /$regExp/)) {
		if($setup){
			$timestamp = sprintf '%04d%02d%02d00', $year + 1900, $mon + 1, $day;
		} else {
			$nn++;

			if($nn >= 99){
				$nn = 0;
				$tday++;
			}

			$timestamp = ((($year + 1900) * 10000 + ($mon + 1) * 100 + $day) > ($tyear * 10000 + $tmon * 100 + $tday))
				? (sprintf '%04d%02d%02d00', $year + 1900, $mon + 1, $day)
				: (sprintf '%04d%02d%02d%02d', $tyear, $tmon, $tday, $nn);
		}
		$timeStampBlock = process({ TIMESTAMP => $timestamp}, $cleanTimeStampBlock);
	} else {
		error("Can not find timestamp for $dmnName");
		return undef;
	}

	$newZoneFile = replaceBloc($bTag, $eTag, "$bTag$timeStampBlock$eTag", $newZoneFile, undef);

	iMSCP::HooksManager->getInstance()->trigger('afterNamedIncTimeStamp');

	$newZoneFile;
}

sub addDmnDb
{
	my $self = shift;
	my $option = shift;
	my $zoneFile = "$self::bindConfig{BIND_DB_DIR}/$option->{DMN_NAME}.db";

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedAddDmnDb') and return 1;

	use iMSCP::Dialog;
	use iMSCP::File;
	use iMSCP::Templator;
	use iMSCP::IP;

	my $ipH = iMSCP::IP->new();

	# Saving the current production file if it exists
	if(-f $zoneFile) {
		iMSCP::File->new(
			filename => $zoneFile
		)->copyFile(
			"$self->{bkpDir}/$option->{DMN_NAME}.db." . time
		) and return 1;
	}

	# Load the current working db file
	my $wrkCfg = "$self->{wrkDir}/$option->{DMN_NAME}.db";
	my $wrkFileContent = iMSCP::File->new(filename => $wrkCfg)->get() if -f $wrkCfg;

	## Building new configuration file

	# Loading the template from /etc/imscp/bind/parts
	my $entries = iMSCP::File->new(filename => "$self->{tplDir}/db_e.tpl")->get();
	return 1 if ! $entries;

	########################## NS SECTION START #################################

	my $A_Sec_b = "; ns A SECTION BEGIN\n";
	my $A_Sec_e = "; ns A SECTION END\n";
	my $nsATpl = getBloc($A_Sec_b, $A_Sec_e, $entries);
	chomp $nsATpl;

	my $Decl_b = "; ns DECLARATION SECTION BEGIN\n";
	my $Decl_e = "; ns DECLARATION SECTION END\n";
	my $nsDeclTpl = getBloc($Decl_b, $Decl_e, $entries);
	chomp $nsDeclTpl;

	my $ns = 1;
	my (@nsASection, @nsDeclSection) = ((), ());
	my @ips = $self::bindConfig{'SECONDARY_DNS'} eq 'no' ? () : split(';', $self::bindConfig{'SECONDARY_DNS'});

	for($option->{'DMN_IP'}, @ips) {
		push(
			@nsASection,
			process(
				{
					NS_NUMBER => $ns,
					NS_IP => $_,
					NS_IP_TYPE	=> (lc($ipH->getIpType($_)) eq 'ipv4' ? 'A' : 'AAAA')
				},
				$nsATpl
			)
		);

		push(
			@nsDeclSection,
			process(
				{
					NS_NUMBER => $ns,
					NS_IP => $_,
					NS_IP_TYPE => (lc($ipH->getIpType($_)) eq 'ipv4' ? 'A' : 'AAAA')
				},
				$nsDeclTpl
			)
		);
		$ns++;
	}

	$entries = replaceBloc($A_Sec_b, $A_Sec_e, join("\n",@nsASection), $entries, undef);
	$entries = replaceBloc($Decl_b, $Decl_e, join("\n",@nsDeclSection),	$entries, undef);

	########################### NS SECTION END ##################################

	####################### TIMESTAMP SECTION START #############################

	my $domainIpType = $ipH->getIpType($option->{'DMN_IP'}) eq 'ipv4' ? 'ip4' : 'ip6';
	my $serverIpType = $ipH->getIpType($main::imscpConfig{'BASE_SERVER_IP'}) eq 'ipv4' ? 'ip4' : 'ip6';

	my $tags = {
		MX => $option->{'MX'},
		DMN_NAME => $option->{'DMN_NAME'},
		DMN_IP => $option->{'DMN_IP'},
		IP_TYPE => $domainIpType eq 'ip4' ? 'A' : 'AAAA',
		TXT_DMN_IP_TYPE => $domainIpType,
		TXT_SERVER_IP_TYPE => $serverIpType,
		BASE_SERVER_IP => $main::imscpConfig{'BASE_SERVER_IP'}
	};

	# Replacement tags
	$entries = process($tags, $entries);
	return 1 if (!$entries);

	$entries = $self->incTimeStamp(($wrkFileContent ? $wrkFileContent : $entries), $option->{'DMN_NAME'}, $entries);

	if(! $entries) {
		error("Cannot update timestamp for $option->{DMN_NAME}");
		return 1;
	}

	######################## TIMESTAMP SECTION END #################################

	###################### CUSTUMERS DATA SECTION START ############################

	if( $option->{'DMN_ADD'} ) {
		my $bTag = "; ctm domain als entries BEGIN.\n";
		my $eTag = "; ctm domain als entries END.\n";
		my $fTag = iMSCP::File->new(filename => "$self->{tplDir}/db_dns_entry.tpl")->get();
		my $old = iMSCP::File->new(filename => "$self->{wrkDir}/$option->{DMN_NAME}.db")->get() || '';

		$tags = {
			MANUAL_DNS_NAME => $option->{'DMN_ADD'}->{'MANUAL_DNS_NAME'},
			MANUAL_DNS_CLASS => $option->{'DMN_ADD'}->{'MANUAL_DNS_CLASS'},
			MANUAL_DNS_TYPE => $option->{'DMN_ADD'}->{'MANUAL_DNS_TYPE'},
			MANUAL_DNS_DATA => $option->{'DMN_ADD'}->{'MANUAL_DNS_DATA'}
		};

		my $toadd = process($tags, $fTag);
		my $custom = getBloc($bTag, $eTag, $old);
		$custom =~ s/$option->{'DMN_ADD'}->{'MANUAL_DNS_NAME'}\s[^\n]*\n//img;
		$custom = '' unless $custom;
		$custom = "$bTag$custom$toadd$eTag";

		$entries = replaceBloc($bTag, $eTag, $custom, $entries, undef);
	}

	if($option->{'DMN_DEL'}) {
		my $bTag = "; ctm domain als entries BEGIN.\n";
		my $eTag = "; ctm domain als entries END.\n";
		my $old = iMSCP::File->new(filename => "$self->{wrkDir}/$option->{DMN_NAME}.db")->get() || '';

		my $custom = getBloc($bTag, $eTag, $old);
		$custom =~ s/$option->{'DMN_DEL'}->{'MANUAL_DNS_NAME'}\s[^\n]*\n//img;
		$custom = '' unless $custom;
		$custom = "$bTag$custom$eTag";

		$entries = replaceBloc($bTag, $eTag, $custom, $entries, undef);
	}

	####################### CUSTUMERS DATA SECTION END #############################

	##################### CUSTOM DATA SECTION START ##########################

	if(keys(%{$option->{'DMN_CUSTOM'}}) > 0 ) {
		my $bTag = iMSCP::File->new(filename => "$self->{tplDir}/db_dns_entry_b.tpl")->get();
		my $eTag = iMSCP::File->new(filename =>"$self->{tplDir}/db_dns_entry_e.tpl")->get();
		my $FormatTag = iMSCP::File->new(filename => "$self->{tplDir}/db_dns_entry.tpl")->get();
		my $custom = '';

		for(keys %{$option->{'DMN_CUSTOM'}}) {
			next unless
				$option->{'DMN_CUSTOM'}->{$_}->{'domain_text'} &&
				$option->{'DMN_CUSTOM'}->{$_}->{'domain_class'} &&
				$option->{'DMN_CUSTOM'}->{$_}->{'domain_type'};

			$tags = {
				MANUAL_DNS_NAME => $option->{'DMN_CUSTOM'}->{$_}->{'domain_dns'},
				MANUAL_DNS_CLASS => $option->{'DMN_CUSTOM'}->{$_}->{'domain_class'},
				MANUAL_DNS_TYPE => $option->{'DMN_CUSTOM'}->{$_}->{'domain_type'},
				MANUAL_DNS_DATA => $option->{'DMN_CUSTOM'}->{$_}->{'domain_text'}
			};

			$custom .= process($tags, $FormatTag);
		}

		$entries = replaceBloc($bTag, $eTag, $custom, $entries, undef);

	}

	####################### CUSTOM DATA SECTION END ##########################

	# Store the file in the working directory
	my $file = iMSCP::File->new(filename => $wrkCfg);
	$file->set($entries) and return 1;
	$file->save() and return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Install the file in the production directory
	$file->copyFile($self::bindConfig{'BIND_DB_DIR'}) and return 1;

	iMSCP::HooksManager->getInstance()->trigger('afterNamedAddDmnDb');
}

sub addDmnConfig
{
	my $self = shift;
	my $option = shift;
	my $rs = 0;
	my ($rdata, $cfg, $file);

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedAddDmnConfig') and return 1;

	use iMSCP::File;
	use iMSCP::Templator;
	use File::Basename;

	# backup config file
	my $timestamp = time();

	if(-f "$self->{wrkDir}/named.conf"){
		my $file = iMSCP::File->new( filename => "$self->{wrkDir}/named.conf" );
		my ($filename, $directories, $suffix) = fileparse("$self->{wrkDir}/named.conf");
		$file->copyFile("$self->{bkpDir}/$filename$suffix.$timestamp") and return 1;
	} else {
		error("$self->{wrkDir}/named.conf not found. Run the setup script to fix this");
		return 1;
	}

	# Building of new configuration file

	# Loading all needed templates from /etc/imscp/bind/parts
	my ($entry_b, $entry_e, $entry) = ('', '', '');
	$entry_b = iMSCP::File->new(filename => "$self->{tplDir}/cfg_entry_b.tpl")->get();
	$entry_e = iMSCP::File->new(filename => "$self->{tplDir}/cfg_entry_e.tpl")->get();
	$entry = iMSCP::File->new(filename => "$self->{tplDir}/cfg_entry_$self::bindConfig{BIND_MODE}.tpl")->get();
	return 1 if(!defined $entry_b ||!defined $entry_e ||!defined $entry);

	# Tags preparation
	my $tags_hash = { DB_DIR => $self::bindConfig{'BIND_DB_DIR'} };

	if($self::bindConfig{'BIND_MODE'} =~ /^slave$/i) {
		$tags_hash->{'PRIMARY_DNS'} = join( '; ', split(';', $self::bindConfig{'PRIMARY_DNS'})) . ';';
	}

	$tags_hash->{'DMN_NAME'} = $option->{'DMN_NAME'};

	my $entry_b_val = process($tags_hash, $entry_b);
	my $entry_e_val = process($tags_hash, $entry_e);
	my $entry_val = process($tags_hash, $entry);

	# Loading working file from /etc/imscp/bind/working/named.conf
	$file = iMSCP::File->new(filename => "$self->{wrkDir}/named.conf");
	$cfg = $file->get();
	return 1 if (!$cfg);

	# Building the new configuration file
	my $entry_repl = "$entry_b_val$entry_val$entry_e_val$entry_b$entry_e";

	# delete old if exist
	$cfg = replaceBloc($entry_b_val, $entry_e_val, '', $cfg, undef);

	# add new
	$cfg = replaceBloc($entry_b, $entry_e, $entry_repl, $cfg, undef);

	## Storage and installation of new file - Begin

	# Store the new builded file in the working directory
	$file = iMSCP::File->new(filename => "$self->{wrkDir}/named.conf");
	$rs |= $file->set($cfg);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	# Install the new file in the production directory
	$rs |= $file->copyFile($self::bindConfig{'BIND_CONF_FILE'});

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterNamedAddDmnConfig');

	$rs;
}

sub addDmn
{
	my $self = shift;
	my $option = shift;

	$option = {} if ref $option ne 'HASH';

	my $errmsg = {
		'DMN_NAME' => 'You must supply domain name!',
		'DMN_IP' => 'You must supply ip for domain!'
	};

	for(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $option->{$_};
		return 1 unless $option->{$_};
	}

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedAddDmn') and return 1;

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedAddDmn', \$option);

	$self->addDmnConfig($option) and return 1;
	$self->addDmnDb($option) and return 1 if $self::bindConfig{'BIND_MODE'} =~ /^master$/i;

	iMSCP::HooksManager->getInstance()->trigger('afterNamedAddDmn', $option);

	$self->{'restart'} = 'yes';

	iMSCP::HooksManager->getInstance()->trigger('afterNamedAddDmn');
}

sub postaddDmn
{
	my $self = shift;
	my $option = shift;
	my $rs = 0;

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedPostAddDmn') and return 1;

	use iMSCP::IP;

	my $ipH = iMSCP::IP->new();

	$option = {} if ref $option ne 'HASH';

	my $errmsg = {
		'DMN_NAME' => 'You must supply domain name!',
		'DMN_IP' => 'You must supply ip for domain!'
	};

	for(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $option->{$_};
		return 1 unless $option->{$_};
	}

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedPostAddDmn', \$option);

	$rs |= $self->addDmn(
		{
			DMN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
			DMN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
			MX => '',
			DMN_ADD => {
				MANUAL_DNS_NAME => "$option->{USER_NAME}.$main::imscpConfig{BASE_SERVER_VHOST}.",
				MANUAL_DNS_CLASS => 'IN',
				MANUAL_DNS_TYPE => (lc($ipH->getIpType($option->{'DMN_IP'})) eq 'ipv4' ? 'A' : 'AAAA'),
				MANUAL_DNS_DATA => $option->{'DMN_IP'}
			}
		}
	);

	iMSCP::HooksManager->getInstance()->trigger('afterNamedPostAddDmn', $option);

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterNamedPostAddDmn');

	$rs;
}

sub delDmnConfig
{
	my $self = shift;
	my $option = shift;
	my ($rdata, $cfg, $file);
	my $rs = 0;

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedDelDmnConfig') and return 1;

	use iMSCP::File;
	use iMSCP::Templator;
	use File::Basename;

	# backup config file
	if(-f "$self->{wrkDir}/named.conf"){
		my $file = iMSCP::File->new(filename => "$self->{wrkDir}/named.conf");
		$file->copyFile("$self->{bkpDir}/named.conf.".time) and return 1;
	} else {
		error("$self->{wrkDir}/named.conf not found. Run setup again to fix this");
		return 1;
	}

	# Loading all needed templates from /etc/imscp/bind/parts
	my ($bTag, $eTag);
	$bTag = iMSCP::File->new(filename => "$self->{tplDir}/cfg_entry_b.tpl")->get();
	$eTag = iMSCP::File->new(filename => "$self->{tplDir}/cfg_entry_e.tpl")->get();
	return 1 unless( $bTag && $eTag);

	# Preparation tags
	my $tags_hash = { DMN_NAME => $option->{'DMN_NAME'} };

	$bTag = process($tags_hash, $bTag);
	$eTag = process($tags_hash, $eTag);

	# Loading working file from /etc/imscp/bind/working/named.conf
	$file = iMSCP::File->new(filename => "$self->{wrkDir}/named.conf");
	$cfg = $file->get();
	return 1 if (!$cfg);

	# delete
	$cfg = replaceBloc($bTag, $eTag, '', $cfg, undef);

	# Store the new builded file in the working directory
	$file = iMSCP::File->new(filename => "$self->{wrkDir}/named.conf");
	$rs |= $file->set($cfg);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	# Install the new file in the production directory
	$rs |= $file->copyFile($self::bindConfig{'BIND_CONF_FILE'});

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterNamedDelDmnConfig');

	$rs;
}

sub delDmn
{
	my $self = shift;
	my $option = shift;
	my $rs = 0;

	$option = {} if ref $option ne 'HASH';

	error('You must supply domain name!') unless $option->{'DMN_NAME'};
	return 1 unless $option->{'DMN_NAME'};

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedDelDmn', $option) and return 1;

	$rs |= $self->delDmnConfig($option);

	my $zoneFile = "$self::bindConfig{BIND_DB_DIR}/$option->{DMN_NAME}.db";

	$rs |= iMSCP::File->new(filename => $zoneFile)->delFile() if -f $zoneFile;

	$rs |= iMSCP::File->new(
		filename => "$self->{wrkDir}/$option->{DMN_NAME}.db"
	)->delFile() if -f "$self->{wrkDir}/$option->{DMN_NAME}.db";

	$zoneFile = "$self->{wrkDir}/$main::imscpConfig{BASE_SERVER_VHOST}.db";
	$zoneFile = "$self::bindConfig{BIND_DB_DIR}/$main::imscpConfig{BASE_SERVER_VHOST}.db" unless -f $zoneFile;

	unless(-f $zoneFile) {
		error("$main::imscpConfig{BASE_SERVER_VHOST}.db do not exists");
		return 1;
	}

	my $zContent = iMSCP::File->new( filename => $zoneFile )->get();

	unless($zContent) {
		error("$main::imscpConfig{BASE_SERVER_VHOST}.db is empty");
		return 1;
	}

	$zContent =~ s/$option->{'USER_NAME'}\.$main::imscpConfig{'BASE_SERVER_VHOST'}\.\s[^\n]*\n//gmi;

	# Store the new builded file in the working directory
	my $file = iMSCP::File->new(filename => "$self->{wrkDir}/$main::imscpConfig{BASE_SERVER_VHOST}.db");
	$rs |= $file->set($zContent);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	# Install the new file in the production directory
	$rs |= $file->copyFile($self::bindConfig{'BIND_DB_DIR'});

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterNamedDelDmn', $option);

	$rs;
}

sub postdelDmn
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedPostDelDmn', $data);

	$rs |= $self->addDmn(
		{
			DMN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
			DMN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
			MX => '',
			DMN_DEL => {
				MANUAL_DNS_NAME => "$data->{USER_NAME}.$main::imscpConfig{BASE_SERVER_VHOST}.",
			}
		}
	);

	iMSCP::HooksManager->getInstance()->trigger('afterNamedPostDelDmn', \$data);

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	$rs;
}

sub addSub
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	use iMSCP::File;
	use iMSCP::Templator;

	my $errmsg = {
		'DMN_NAME' => 'You must supply domain name!',
		'DMN_IP' => 'You must supply ip for domain!'
	};

	for(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedAddSub', $data) and return 1;

	my $zoneFile = "$self::bindConfig{BIND_DB_DIR}/$data->{PARENT_DMN_NAME}.db";

	# Saving the current production file if it exists
	$rs |= iMSCP::File->new(
		filename => $zoneFile
	)->copyFile(
		"$self->{bkpDir}/$data->{PARENT_DMN_NAME}.db." . time
	) if(-f $zoneFile);

	# Load the current working db file
	my $wrkCfg = "$self->{wrkDir}/$data->{PARENT_DMN_NAME}.db";
	my $wrkFileContent = iMSCP::File->new(filename => $wrkCfg)->get();

	if(! $wrkFileContent){
		error("Can not load $wrkCfg");
		return 1;
	}

	$wrkFileContent = $self->incTimeStamp($wrkFileContent, $data->{'PARENT_DMN_NAME'});

	if(! $wrkFileContent) {
		error("Can not update timestamp for $data->{PARENT_DMN_NAME}");
		return 1;
	}

	######################### SUBDOMAIN SECTION START ###############################
	my $cleanBTag = iMSCP::File->new(filename => "$self->{tplDir}/db_sub_entry_b.tpl")->get();
	my $cleanTag = iMSCP::File->new(filename => "$self->{tplDir}/db_sub_entry.tpl")->get();
	my $cleanETag = iMSCP::File->new(filename => "$self->{tplDir}/db_sub_entry_e.tpl")->get();

	######################### SUBDOMAIN MX SECTION START ###############################
	my $bTag = "; sub MX entry BEGIN\n";
	my $eTag = "; sub MX entry END\n";
	my $mxBlock;

	if($data->{'MX'}) {
		my $cleanMXBlock = getBloc($bTag, $eTag, $cleanTag);
		$mxBlock .= process({ MAIL_SERVER => $data->{'MX'}->{$_}->{'domain_text'} }, $cleanMXBlock) for keys %{$data->{'MX'}};
		$cleanTag = replaceBloc($bTag, $eTag, $mxBlock, $cleanTag, undef);
	} else {
		$cleanTag = replaceBloc($bTag, $eTag, '', $cleanTag, undef);
	}
	########################## SUBDOMAIN MX SECTION END ################################

	$bTag = process({SUB_NAME => $data->{'DMN_NAME'}}, $cleanBTag);
	$eTag = process({SUB_NAME => $data->{'DMN_NAME'}}, $cleanETag);

	my $tag = process(
		{
			SUB_NAME => $data->{'DMN_NAME'},
			DMN_IP => $data->{'DMN_IP'},
			PARENT_DMN_NAME => $data->{'PARENT_DMN_NAME'}
		},
		$cleanTag
	);

	$wrkFileContent = replaceBloc($bTag, $eTag, '', $wrkFileContent, undef);
	$wrkFileContent = replaceBloc($cleanBTag, $cleanETag, "$bTag$tag$eTag", $wrkFileContent, 'keep');
	########################## SUBDOMAIN SECTION END ################################

	# Store the file in the working directory
	my $file = iMSCP::File->new(filename => $wrkCfg);
	$rs |= $file->set($wrkFileContent);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	# Install the file in the production directory
	$rs |= $file->copyFile($self::bindConfig{'BIND_DB_DIR'});

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterNamedAddSub', $data);

	$rs;
}

sub postaddSub
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	use iMSCP::IP;

	my $ipH = iMSCP::IP->new();

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedPostAddSub', $data) and return 1;

	$rs |= $self->addDmn(
		{
			DMN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
			DMN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
			MX => '',
			DMN_ADD => {
				MANUAL_DNS_NAME => "$data->{USER_NAME}.$main::imscpConfig{BASE_SERVER_VHOST}.",
				MANUAL_DNS_CLASS => 'IN',
				MANUAL_DNS_TYPE => (lc($ipH->getIpType($data->{'DMN_IP'})) eq 'ipv4' ? 'A' : 'AAAA'),
				MANUAL_DNS_DATA => $data->{'DMN_IP'}
			}
		}
	);

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterNamedPostAddSub', $data);

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	$rs;
}

sub delSub
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	use iMSCP::Dialog;
	use iMSCP::File;
	use iMSCP::Templator;

	my $errmsg = {
		'DMN_NAME' => 'You must supply domain name!',
		'DMN_IP' => 'You must supply ip for domain!',
		'PARENT_DMN_NAME' => 'You must supply parent domain name!'
	};

	for(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedDelSub', $data) and return 1;

	my $zoneFile = "$self::bindConfig{BIND_DB_DIR}/$data->{PARENT_DMN_NAME}.db";

	#Saving the current production file if it exists
	$rs |=	iMSCP::File->new(
		filename => $zoneFile
	)->copyFile(
		"$self->{bkpDir}/$data->{PARENT_DMN_NAME}.db." . time
	) if(-f $zoneFile);

	# Load the current working db file
	my $wrkCfg = "$self->{wrkDir}/$data->{PARENT_DMN_NAME}.db";
	my $wrkFileContent = iMSCP::File->new(filename => $wrkCfg)->get();

	if(!$wrkFileContent){
		error("Can not load $wrkCfg");
		return 1;
	}

	$wrkFileContent = $self->incTimeStamp($wrkFileContent, $data->{'PARENT_DMN_NAME'});

	if(!$wrkFileContent) {
		error("Can not update timestamp for $data->{PARENT_DMN_NAME}");
		return 1;
	}

	######################### SUBDOMAIN SECTION START ###############################

	my $cleanBTag = iMSCP::File->new(filename => "$self->{tplDir}/db_sub_entry_b.tpl")->get();
	my $cleanETag = iMSCP::File->new(filename => "$self->{tplDir}/db_sub_entry_e.tpl")->get();
	my $bTag = process({SUB_NAME => $data->{'DMN_NAME'}}, $cleanBTag);
	my $eTag = process({SUB_NAME => $data->{'DMN_NAME'}}, $cleanETag);
	$wrkFileContent = replaceBloc($bTag, $eTag, '', $wrkFileContent, undef);

	########################## SUBDOMAIN SECTION END ################################

	# Store the file in the working directory
	my $file = iMSCP::File->new(filename => $wrkCfg);
	$rs |= $file->set($wrkFileContent);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	# Install the file in the production directory
	$rs |= $file->copyFile($self::bindConfig{'BIND_DB_DIR'});

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterNamedDelSub', $data);

	$rs;
}

sub postdelSub
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	iMSCP::HooksManager->getInstance()->trigger('beforeNamedPostDelSub', $data) and return 1;

	$rs |= $self->addDmn(
		{
			DMN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
			DMN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
			MX => '',
			DMN_DEL => {
				MANUAL_DNS_NAME => "$data->{USER_NAME}.$main::imscpConfig{BASE_SERVER_VHOST}.",
			}
		}
	);

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterNamedPostDelSub', $data);

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	$rs;
}

END
{
	my $endCode	= $?;
	my $self = Servers::named::bind->new();
	my $rs = 0;

	$rs	= $self->restart() if $self->{'restart'} && $self->{'restart'} eq 'yes';
	$? = $endCode || $rs;
}

1;
