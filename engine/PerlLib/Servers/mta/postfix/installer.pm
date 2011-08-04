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
# @version		SVN: $Id: installer.pm 4856 2011-07-11 08:48:54Z sci2tech $
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2


package Servers::mta::postfix::installer;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Templator;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{
	debug((caller(0))[3].': Starting...');

	my $self		= shift;

	$self->{cfgDir}	= "$main::imscpConfig{'CONF_DIR'}/postfix";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";
	$self->{vrlDir} = "$self->{cfgDir}/imscp";

	my $conf		= "$self->{cfgDir}/postfix.data";
	my $oldConf		= "$self->{cfgDir}/postfix.old.data";

	tie %self::postfixConfig, 'iMSCP::Config','fileName' => $conf;
	tie %self::postfixOldConfig, 'iMSCP::Config','fileName' => $oldConf if -f $oldConf;

	debug((caller(0))[3].': Ending...');
	0;
}

sub install{
	debug((caller(0))[3].': Starting...');

	my $self = shift;

	# Saving all system configuration files if they exists
	for ((
		$self::postfixConfig{'POSTFIX_CONF_FILE'},
		$self::postfixConfig{'POSTFIX_MASTER_CONF_FILE'},
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'}.'/aliases',
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'}.'/domains',
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'}.'/mailboxes',
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'}.'/transport',
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'}.'/sender-access'
	)) {
		$self->bkpConfFile($_) and return 1;
	}

	$self->addUsers() and return 1;
	$self->makeDirs() and return 1;

	$self->buildConf() and return 1;
	$self->buildLookup() and return 1;
	$self->buildAliasses() and return 1;
	$self->arplSetup() and return 1;

	$self->saveConf() and return 1;

	$self->oldEngineCompatibility() and return 1;

	debug((caller(0))[3].': Ending...');
	0;
}

sub oldEngineCompatibility{
	debug((caller(0))[3].': Starting...');

	$main::imscpConfig{$_} = $self::postfixConfig{$_} foreach(keys %self::postfixConfig);

	my $gid	= getgrnam($self::postfixConfig{'MTA_MAILBOX_GID_NAME'});
	my $uid	= getpwnam($self::postfixConfig{'MTA_MAILBOX_UID_NAME'});

	$main::imscpConfig{'MTA_MAILBOX_MIN_UID'}	= $uid if !$main::imscpConfig{'MTA_MAILBOX_MIN_UID'} || $main::imscpConfig{'MTA_MAILBOX_MIN_UID'} != $uid;
	$main::imscpConfig{'MTA_MAILBOX_UID'}		= $uid if !$main::imscpConfig{'MTA_MAILBOX_UID'} || $main::imscpConfig{'MTA_MAILBOX_UID'} != $uid;
	$main::imscpConfig{'MTA_MAILBOX_GID'}		= $gid if !$main::imscpConfig{'MTA_MAILBOX_GID'} || $main::imscpConfig{'MTA_MAILBOX_GID'} != $gid;

	debug((caller(0))[3].': Ending...');
	0;
}

sub makeDirs{
	debug((caller(0))[3].': Starting...');

	use iMSCP::Dir;

	for (
		[$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'},	$main::imscpConfig{'ROOT_USER'},	$main::imscpConfig{'ROOT_GROUP'}],
		[$self::postfixConfig{'MTA_VIRTUAL_MAIL_DIR'},	$main::imscpConfig{'ROOT_USER'},	$main::imscpConfig{'ROOT_GROUP'}],
	) {
		iMSCP::Dir->new(dirname => $_->[0])->make({ user => $_->[1], group => $_->[2], mode => 0755}) and return 1;
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub addUsers{
	debug((caller(0))[3].': Starting...');

	use Modules::SystemGroup;

	my $group = Modules::SystemGroup->new();

	$group->{system}	= 'yes';
	$group->addSystemGroup($self::postfixConfig{'MTA_MAILBOX_GID_NAME'}) and return 1;

	use Modules::SystemUser;
	my $user = Modules::SystemUser->new();

	$user->{comment}	= 'vmail-user';
	$user->{home}		= $self::postfixConfig{'MTA_VIRTUAL_MAIL_DIR'};
	$user->{group}		= $self::postfixConfig{'MTA_MAILBOX_GID_NAME'};
	$user->{system}		= 'yes';

	$user->addSystemUser($self::postfixConfig{'MTA_MAILBOX_UID_NAME'}) and return 1;
	$user->addToGroup($main::imscpConfig{'MASTER_GROUP'}) and return 1;

	debug((caller(0))[3].': Ending...');
	0;
}

sub buildAliasses{
	debug((caller(0))[3].': Starting...');

	my ($rs, $stdout, $stderr);

	# Rebuilding the database for the mail aliases file - Begin
	$rs = execute("$self::postfixConfig{'CMD_NEWALIASES'}", \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout");
	error((caller(0))[3].": $stderr") if($stderr);
	error((caller(0))[3].": Error while executing $self::postfixConfig{'CMD_NEWALIASES'}") if(!$stderr && $rs);

	debug((caller(0))[3].': Ending...');
	$rs;
}

sub arplSetup{
	debug((caller(0))[3].': Starting...');

	my $file;

	$file = iMSCP::File->new(filename => "$main::imscpConfig{'ROOT_DIR'}/engine/messenger/imscp-arpl-msgr");
	$file->mode(0755) and return 1;
	$file->owner($self::postfixConfig{'MTA_MAILBOX_UID_NAME'}, $self::postfixConfig{'MTA_MAILBOX_GID_NAME'}) and return 1;

	debug((caller(0))[3].': Ending...');
	0;
}

sub buildLookup{
	debug((caller(0))[3].': Starting...');

	my $self = shift;
	my ($rs, $stdout, $stderr, $file);

	use iMSCP::File;
	use iMSCP::Execute;

	for (qw/aliases domains mailboxes transport sender-access/) {
		# Storing the new files in the working directory
		$file = iMSCP::File->new(filename => "$self->{vrlDir}/$_");
		$file->copyFile("$self->{wrkDir}") and return 1;

		# Install the files in the production directory
		$file->copyFile("$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'}") and return 1;

		# Creating/updating databases for all lookup tables
		$rs = execute("$self::postfixConfig{'CMD_POSTMAP'} $self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'}/$_", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout");
		error((caller(0))[3].": $stderr") if($rs);
		return $rs if ($rs);
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub bkpConfFile{

	use File::Basename;

	use Data::Dumper;

	my $self		= shift;
	my $cfgFile		= shift;
	my $timestamp	= time;

	if(-f $cfgFile){
		my $file	= iMSCP::File->new( filename => $cfgFile );
		my ($filename, $directories, $suffix) = fileparse($cfgFile);
		if(!-f "$self->{bkpDir}/$filename$suffix.system") {
			$file->copyFile("$self->{bkpDir}/$filename$suffix.system") and return 1;
		} else {
			$file->copyFile("$self->{bkpDir}/$filename$suffix.$timestamp") and return 1;
		}
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub saveConf{

	debug((caller(0))[3].': Starting...');

	my $self = shift;

	use iMSCP::File;

	my$file = iMSCP::File->new(filename => "$self->{cfgDir}/postfix.data");
	my $cfg = $file->get() or return 1;

	$file = iMSCP::File->new(filename => "$self->{cfgDir}/postfix.old.data");
	$file->set($cfg) and return 1;
	$file->save and return 1;
	$file->mode(0640) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	debug((caller(0))[3].': Ending...');
	0;
}

sub buildConf{
	debug((caller(0))[3].': Starting...');

	my $self = shift;
	my $rs;

	$rs = $self->buildMain();
	return $rs if $rs;

	$rs = $self->buildMaster();
	return $rs if $rs;

	debug((caller(0))[3].': Ending...');
	0;
}

sub buildMaster{
	debug((caller(0))[3].': Starting...');

	my $self = shift;

	use iMSCP::File;
	use iMSCP::Templator;

	# Storing the new file in the working directory
	my $file = iMSCP::File->new(filename => "$self->{cfgDir}/master.cf");
	my $cfgTpl	= $file->get();
	return 1 if (!$cfgTpl);
	$cfgTpl = iMSCP::Templator::process(
		{
			ARPL_USER					=> $self::postfixConfig{'MTA_MAILBOX_UID_NAME'},
			ARPL_GROUP					=> $main::imscpConfig{'MASTER_GROUP'},
			ARPL_PATH					=> $main::imscpConfig{'ROOT_DIR'}."/engine/messenger/imscp-arpl-msgr"
		},
		$cfgTpl
	);
	return 1 if (!$cfgTpl);

	foreach(@{$self->{postCalls}->{buildConf}}){
		eval {$cfgTpl = &$_($cfgTpl);};
		error((caller(0))[3].": $@") if ($@);
		return 1 if $@;
	}

	$file = iMSCP::File->new(filename => "$self->{wrkDir}/master.cf");
	$file->set($cfgTpl) and return 1;
	$file->save() and return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Installing the new file in the production dir
	$file->copyFile($self::postfixConfig{'POSTFIX_MASTER_CONF_FILE'}) and return 1;

	debug((caller(0))[3].': Ending...');
	0;
}

sub buildMain{
	debug((caller(0))[3].': Starting...');

	my $self = shift;

	use iMSCP::File;
	use iMSCP::Templator;

	# Loading the template from /etc/imscp/postfix/
	my $file	= iMSCP::File->new(filename => "$self->{cfgDir}/main.cf");
	my $cfgTpl	= $file->get();
	return 1 if (!$cfgTpl);

	foreach(@{$self->{preCalls}->{buildConf}}){
		eval {$cfgTpl = &$_($cfgTpl);};
		error((caller(0))[3].": $@") if ($@);
		return 1 if $@;
	}

	# Building the file
	my $hostname = $main::imscpConfig{'SERVER_HOSTNAME'};
	my $gid	= getgrnam($self::postfixConfig{'MTA_MAILBOX_GID_NAME'});
	my $uid	= getpwnam($self::postfixConfig{'MTA_MAILBOX_UID_NAME'});

	$cfgTpl = iMSCP::Templator::process(
		{
			MTA_HOSTNAME				=> $hostname,
			MTA_LOCAL_DOMAIN			=> "$hostname.local",
			MTA_VERSION					=> $main::imscpConfig{'Version'},
			MTA_TRANSPORT_HASH			=> $self::postfixConfig{'MTA_TRANSPORT_HASH'},
			MTA_LOCAL_MAIL_DIR			=> $self::postfixConfig{'MTA_LOCAL_MAIL_DIR'},
			MTA_LOCAL_ALIAS_HASH		=> $self::postfixConfig{'MTA_LOCAL_ALIAS_HASH'},
			MTA_VIRTUAL_MAIL_DIR		=> $self::postfixConfig{'MTA_VIRTUAL_MAIL_DIR'},
			MTA_VIRTUAL_DMN_HASH		=> $self::postfixConfig{'MTA_VIRTUAL_DMN_HASH'},
			MTA_VIRTUAL_MAILBOX_HASH	=> $self::postfixConfig{'MTA_VIRTUAL_MAILBOX_HASH'},
			MTA_VIRTUAL_ALIAS_HASH		=> $self::postfixConfig{'MTA_VIRTUAL_ALIAS_HASH'},
			MTA_MAILBOX_MIN_UID			=> $uid,
			MTA_MAILBOX_UID				=> $uid,
			MTA_MAILBOX_GID				=> $gid,
			PORT_POSTGREY				=> $main::imscpConfig{'PORT_POSTGREY'},
			GUI_CERT_DIR				=> $main::imscpConfig{'GUI_CERT_DIR'},
			SSL							=> ($main::imscpConfig{'SSL_ENABLED'} eq 'yes' ? '' : '#')
		},
		$cfgTpl
	);
	return 1 if (!$cfgTpl);

	foreach(@{$self->{postCalls}->{buildConf}}){
		eval {$cfgTpl = &$_($cfgTpl);};
		error((caller(0))[3].": $@") if ($@);
		return 1 if $@;
	}

	# Storing the new file in working directory
	$file = iMSCP::File->new(filename => "$self->{wrkDir}/main.cf");
	$file->set($cfgTpl) and return 1;
	$file->save() and return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Installing the new file in production directory
	$file->copyFile($self::postfixConfig{'POSTFIX_CONF_FILE'}) and return 1;

	debug((caller(0))[3].': Ending...');
	0;
}
1;
