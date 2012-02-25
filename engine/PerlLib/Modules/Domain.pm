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
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::Domain;

use strict;
use warnings;
use iMSCP::Debug;
use Data::Dumper;

use vars qw/@ISA/;

@ISA = ('Common::SimpleClass', 'Modules::Abstract');
use Common::SimpleClass;
use Modules::Abstract;

sub _init{
	my $self		= shift;
	$self->{type}	= 'Dmn';
}

sub loadData{

	my $self = shift;

	my $sql = "
		SELECT
			`domain`.*,
			`ips`.`ip_number`,
			`mail_count`.`mail_on_domain`,
			`ips_count`.`domains_on_ip`
		FROM
			`domain` AS `domain`
		LEFT JOIN
			`server_ips` AS `ips`
		ON
			`domain`.`domain_ip_id` = `ips`.`ip_id`
		LEFT JOIN
			(SELECT `domain_id` AS `id`, COUNT( `domain_id` ) AS `mail_on_domain` FROM `mail_users` WHERE `sub_id`= 0 GROUP BY `domain_id`) AS `mail_count`
		ON
			`domain`.`domain_id` = `mail_count`.`id`
		LEFT JOIN
			(SELECT `domain_ip_id` AS `ip_id`, COUNT( `domain_ip_id` ) AS `domains_on_ip` FROM `domain` WHERE `domain_status` != 'delete'GROUP BY `domain_ip_id`) AS `ips_count`
		ON
			`domain`.`domain_ip_id` = `ips_count`.`ip_id`
		WHERE
			`domain_id` = ?
	";

	my $rdata = iMSCP::Database->factory()->doQuery('domain_id', $sql, $self->{dmnId});

	error("$rdata") and return 1 if(ref $rdata ne 'HASH');
	error("No domain has id = $self->{dmnId}") and return 1 unless(exists $rdata->{$self->{dmnId}});

	$self->{$_} = $rdata->{$self->{dmnId}}->{$_} for keys %{$rdata->{$self->{dmnId}}};

	use Modules::User;

	my $rs = Modules::User->new()->process($self->{domain_admin_id});

	0;
}

sub process{

	my $self		= shift;
	$self->{dmnId}	= shift;

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;

	if($self->{domain_status} =~ /^toadd|change|toenable|dnschange$/){
		$rs = $self->add();
		@sql = (
			"UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?",
			($rs ? scalar getMessageByType('ERROR') : 'ok'),
			$self->{domain_id}
		);
	}elsif($self->{domain_status} =~ /^delete$/){
		$rs = $self->delete();
		if($rs){
			@sql = (
				"UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?",
				scalar getMessageByType('ERROR'),
				$self->{domain_id}
			);
		}else {
			@sql = ("DELETE FROM `domain` WHERE `domain_id` = ?", $self->{domain_id});
		}
	}elsif($self->{domain_status} =~ /^todisable$/){
		$rs = $self->disable();
		@sql = (
			"UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?",
			($rs ? scalar getMessageByType('ERROR') : 'disabled'),
			$self->{domain_id}
		);
	}elsif($self->{domain_status} =~ /^restore$/){
		$rs = $self->restore();
		@sql = (
			"UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?",
			($rs ? scalar getMessageByType('ERROR') : 'ok'),
			$self->{domain_id}
		);
	}

	my $rdata = iMSCP::Database->factory()->doQuery('delete', @sql);
	error("$rdata") and return 1 if(ref $rdata ne 'HASH');

	$rs;
}

sub restore{

	use iMSCP::Execute;
	use iMSCP::Dir;
	use iMSCP::Database;
	use iMSCP::Rights;
	use Servers::httpd;

	my $self		= shift;
	$self->{mode}	= 'restore';
	my ($rs, $stdout, $stderr);

	my $dmn_dir		= "$main::imscpConfig{'USER_HOME_DIR'}/$self->{domain_name}";
	my $dmn_bk_dir	= "$dmn_dir/backups";
	my $cmd;

	my $dir	= iMSCP::Dir->new(dirname => $dmn_bk_dir);
	return 1 if $dir->get();

	my @bkpFiles	= $dir->getFiles();

	return 0 unless (scalar @bkpFiles);

	foreach (@bkpFiles) {
		if(/^(.+?)\.sql\.(bz2|gz|lzma|xz)$/) {
			my $sql = "
				SELECT
					*
				FROM
					`sql_database`, `sql_user`
				WHERE
					`sql_database`.`domain_id` = ?
				AND
					`sql_user`.`sqld_id` = `sql_database`.`sqld_id`
				AND
					`sql_database`.`sqld_name` = ?
				;
			";
			my $rdata = iMSCP::Database->factory()->doQuery('sqld_name', $sql, $self->{domain_id}, $1);

			error("$rdata") and return 1 if(ref $rdata ne 'HASH');
			error("No owned database has name = $1") and return 1 unless(exists $rdata->{$1});

			if(scalar keys %{$rdata}) {
				map { s/"/\\"/g }
					my $dbuser = $rdata->{$1}->{sqlu_name},
					my $dbpass = $rdata->{$1}->{sqlu_pass},
					my $dbname = $1,
					$_ = $_;

				if($2 eq 'bz2') {
					$cmd = qq!$main::imscpConfig{'CMD_BZCAT'} -d "$dmn_bk_dir/$_"!;
				} elsif($2 eq 'gz') {
					$cmd = qq!$main::imscpConfig{'CMD_GZCAT'} -d "$dmn_bk_dir/$_"!;
				} elsif($2 eq 'lzma') {
					$cmd = qq!$main::imscpConfig{'CMD_LZMA'} -dc "$dmn_bk_dir/$_"!;
				} elsif($2 eq 'xz') {
					$cmd = qq!$main::imscpConfig{'CMD_XZ'} -dc "$dmn_bk_dir/$_"!;
				}

				$cmd .= qq! | $main::imscpConfig{'CMD_MYSQL'} --user="$dbuser"! .
					qq! --password="$dbpass" --database="$dbname"!;

				$rs = execute($cmd, \$stdout, \$stderr);
				debug("$stdout") if $stdout;
				error("$stderr") if $stderr;
				return $rs if $rs;
			}
		} elsif(/^.+?\.tar\.(bz2|gz|lzma|xz)$/) { # Restore dmn files

			my $type = $1;

			if($type eq 'bz2') {
				$type = 'bzip2';
			} elsif($type eq 'gz') {
				$type = 'gzip';
			}

			my $cmd = "$main::imscpConfig{'CMD_TAR'} -x -p --$type -C '$dmn_dir' -f $dmn_bk_dir/$_";
			$rs = execute($cmd, \$stdout, \$stderr);
			debug("$stdout") if $stdout;
			error("$stderr") if $stderr;
			return $rs if $rs;
		}
	}

	my $httpdGroup = (
			Servers::httpd->factory()->can('getRunningGroup')
			?
			Servers::httpd->factory()->getRunningGroup()
			:
			'0'
		);

	$cmd	= "$main::imscpConfig{'CMD_CHOWN'} -R $self->{domain_uid}:$httpdGroup $dmn_dir";
	$rs		|= execute($cmd, \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;

	$rs |= setRights(
		"$dmn_dir/domain_disable_page",
		{
			user		=> $main::imscpConfig{ROOT_USER},
			group		=> $httpdGroup,
			filemode	=> '0640',
			dirmode		=> '0750',
			recursive	=> 'yes'
		}
	);

	$rs |= setRights(
		"$dmn_dir/backups",
		{
			user		=> $main::imscpConfig{ROOT_USER},
			group		=> $main::imscpConfig{ROOT_GROUP},
			filemode	=> '0640',
			dirmode		=> '0750',
			recursive	=> 'yes'
		}
	);

	$rs;
}

sub buildHTTPDData{

	my $self	= shift;
	my $groupName	=
	my $userName	=
			$main::imscpConfig{SYSTEM_USER_PREFIX}.
			($main::imscpConfig{SYSTEM_USER_MIN_UID} + $self->{domain_admin_id});
	my $hDir 		= "$main::imscpConfig{'USER_HOME_DIR'}/$self->{domain_name}";
	$hDir			=~ s~/+~/~g;
	my $pDir 		= $hDir;

	my $sql = "SELECT * FROM `config` WHERE `name` LIKE 'PHPINI%'";
	my $rdata = iMSCP::Database->factory()->doQuery('name', $sql);
	error("$rdata") and return 1 if(ref $rdata ne 'HASH');
	debug(Dumper($rdata).'');

	$sql			= "SELECT * FROM `php_ini` WHERE `domain_id` = ?";
	my $phpiniData	= iMSCP::Database->factory()->doQuery('domain_id', $sql, $self->{domain_id});
	error("$phpiniData") and return 1 if(ref $phpiniData ne 'HASH');
	debug(Dumper($phpiniData).'');

	$sql			= "SELECT * FROM `ssl_certs` WHERE `id` = ? AND `type` = ? AND `status` = ?";
	my $certData	= iMSCP::Database->factory()->doQuery('id', $sql, $self->{domain_id}, 'dmn', 'ok');
	error("$certData") and return 1 if(ref $certData ne 'HASH');

	my $haveCert = exists $certData->{$self->{domain_id}} && !$self->testCert($self->{domain_name});

	$self->{httpd} = {
		DMN_NAME					=> $self->{domain_name},
		DOMAIN_NAME					=> $self->{domain_name},
		ROOT_DMN_NAME				=> $self->{domain_name},
		PARENT_DMN_NAME				=> $self->{domain_name},
		DMN_IP						=> $self->{ip_number},
		WWW_DIR						=> $main::imscpConfig{'USER_HOME_DIR'},
		HOME_DIR					=> $hDir,
		PARENT_DIR					=> $pDir,
		PEAR_DIR					=> $main::imscpConfig{'PEAR_DIR'},
		PHP_TIMEZONE				=> $main::imscpConfig{'PHP_TIMEZONE'},
		PHP_VERSION					=> $main::imscpConfig{'PHP_VERSION'},
		BASE_SERVER_VHOST_PREFIX	=> $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'},
		BASE_SERVER_VHOST			=> $main::imscpConfig{'BASE_SERVER_VHOST'},
		USER						=> $userName,
		GROUP						=> $groupName,
		have_php					=> $self->{domain_php},
		have_cgi					=> $self->{domain_cgi},
		have_cert					=> $haveCert,
		BWLIMIT						=> $self->{domain_traffic_limit},
		IP_ON_DOMAIN				=> (defined $self->{domains_on_ip} ? $self->{domains_on_ip} : 0),
		ALIAS						=> $userName,
		FORWARD						=> 'no',
		DISABLE_FUNCTIONS			=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{disable_functions} : $rdata->{PHPINI_DISABLE_FUNCTIONS}->{value}),
		MAX_EXECUTION_TIME			=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{max_execution_time} : $rdata->{PHPINI_MAX_EXECUTION_TIME}->{value}),
		MAX_INPUT_TIME				=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{max_input_time} : $rdata->{PHPINI_MAX_INPUT_TIME}->{value}),
		MEMORY_LIMIT				=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{memory_limit} : $rdata->{PHPINI_MEMORY_LIMIT}->{value}),
		ERROR_REPORTING				=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{error_reporting} : $rdata->{PHPINI_ERROR_REPORTING}->{value}),
		DISPLAY_ERRORS				=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{display_errors} : $rdata->{PHPINI_DISPLAY_ERRORS}->{value}),
		REGISTER_GLOBALS			=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{register_globals} : $rdata->{PHPINI_REGISTER_GLOBALS}->{value}),
		POST_MAX_SIZE				=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{post_max_size} : $rdata->{PHPINI_POST_MAX_SIZE}->{value}),
		UPLOAD_MAX_FILESIZE			=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{upload_max_filesize} : $rdata->{PHPINI_UPLOAD_MAX_FILESIZE}->{value}),
		ALLOW_URL_FOPEN				=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{allow_url_fopen} : $rdata->{PHPINI_ALLOW_URL_FOPEN}->{value}),
		PHPINI_OPEN_BASEDIR			=> (exists $phpiniData->{$self->{domain_id}}->{PHPINI_OPEN_BASEDIR} ? ':'.$phpiniData->{$self->{domain_id}}->{PHPINI_OPEN_BASEDIR} : $rdata->{PHPINI_OPEN_BASEDIR}->{value} ? ':'.$rdata->{PHPINI_OPEN_BASEDIR}->{value} : '')
	};

	0;
}

sub buildMTAData{

	my $self	= shift;

	if(
		$self->{mode} ne 'add'
		||
		defined $self->{mail_on_domain} && $self->{mail_on_domain} > 0
		||
		defined $self->{domain_mailacc_limit} && $self->{domain_mailacc_limit} >=0
	){
		$self->{mta} = {
			DMN_NAME	=> $self->{domain_name},
			TYPE		=> 'vdmn_entry'
		};
	}

	0;
}

sub buildNAMEDData{

	use iMSCP::Database;

	my $self	= shift;

	if($self->{mode} eq 'add' && $self->{domain_dns} eq 'yes'){
		my $sql = "
			SELECT
				*
			FROM
				`domain_dns`
			WHERE
				`domain_dns`.`alias_id` = ?
			AND
				`domain_dns`.`domain_id` = ?
		";

		my $rdata = iMSCP::Database->factory()->doQuery('domain_dns_id', $sql, 0, $self->{domain_id});
		error("$rdata") and return 1 if(ref $rdata ne 'HASH');

		$self->{named}->{DMN_CUSTOM}->{$_} = $rdata->{$_} for keys %$rdata;

		if(scalar keys %$rdata){
			my $sql = "
				UPDATE
					`subdomain`
				SET
					`subdomain_status` = ?
				WHERE
					`subdomain_status` = ?
				AND
					`domain_id` = ?
			";

			my $rdata = iMSCP::Database->factory()->doQuery('update', $sql, 'change', 'ok', $self->{domain_id});
			error("$rdata") and return 1 if(ref $rdata ne 'HASH');
		}
	}

	my $groupName	=
	my $userName	=
			$main::imscpConfig{SYSTEM_USER_PREFIX}.
			($main::imscpConfig{SYSTEM_USER_MIN_UID} + $self->{domain_admin_id});

	$self->{named}->{DMN_NAME}	= $self->{domain_name};
	$self->{named}->{DMN_IP}	= $self->{ip_number};
	$self->{named}->{USER_NAME}	= $userName;
	$self->{named}->{MX}		= ($self->{mail_on_domain} || $self->{domain_mailacc_limit} >= 0 ? '' : ';');

	0;
}

sub buildADDONData{

	my $self	= shift;

	my $groupName	=
	my $userName	=
						$main::imscpConfig{SYSTEM_USER_PREFIX}.
						($main::imscpConfig{SYSTEM_USER_MIN_UID} + $self->{domain_admin_id});

	my $hDir 		= "$main::imscpConfig{'USER_HOME_DIR'}/$self->{domain_name}";
	$hDir			=~ s~/+~/~g;


	$self->{AddonsData} = {
		DMN_NAME	=> $self->{domain_name},
		USER		=> $userName,
		GROUP		=> $groupName,
		HOME_DIR	=> $hDir
	};

	0;
}

1;
