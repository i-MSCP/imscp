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

package Servers::mta::postfix;

use strict;
use warnings;
use iMSCP::Debug;
use Data::Dumper;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{
	my $self	= shift;

	$self->{cfgDir} = "$main::imscpConfig{'CONF_DIR'}/postfix";
	$self->{bkpDir} = "$self->{cfgDir}/backup";
	$self->{wrkDir} = "$self->{cfgDir}/working";

	$self->{commentChar} = '#';

	tie %self::postfixConfig, 'iMSCP::Config','fileName' => "$self->{cfgDir}/postfix.data";
	$self->{$_} = $self::postfixConfig{$_} foreach(keys %self::postfixConfig);

	0;
}

sub preinstall{

	use Servers::mta::postfix::installer;

	my $self	= shift;
	my $rs		= Servers::mta::postfix::installer->preinstall();

	$rs;
}

sub install{

	use Servers::mta::postfix::installer;

	my $self	= shift;
	my $rs		= Servers::mta::postfix::installer->new()->install();

	$rs;
}

sub uninstall{

	use Servers::mta::postfix::uninstaller;

	my $self	= shift;
	my $rs		= Servers::mta::postfix::uninstaller->new()->uninstall();
	$rs |= $self->restart();

	$rs;
}

sub postinstall{

	my $self	= shift;
	$self->{restart} = 'yes';

	0;
}

sub setEnginePermissions{

	use Servers::mta::postfix::installer;

	my $self	= shift;
	my $rs = Servers::mta::postfix::installer->new()->setEnginePermissions();

	$rs;
}

sub registerPreHook{

	my $self		= shift;
	my $fname		= shift;
	my $callback	= shift;

	debug("Attaching to $fname...");

	my $installer	= Servers::mta::postfix::installer->new();

	push (@{$installer->{preCalls}->{fname}}, $callback)
		if (ref $callback eq 'CODE' && $installer->can($fname));

	push (@{$self->{preCalls}->{fname}}, $callback)
		if (ref $callback eq 'CODE' && $self->can($fname));

	0;
}

sub registerPostHook{

	my $self		= shift;
	my $fname		= shift;
	my $callback	= shift;

	debug("Attaching to $fname...");

	my $installer	= Servers::mta::postfix::installer->new();

	push (@{$installer->{postCalls}->{$fname}}, $callback)
		if (ref $callback eq 'CODE' && $installer->can($fname));

	push (@{$self->{postCalls}->{$fname}}, $callback)
		if (ref $callback eq 'CODE' && $self->can($fname));

	0;
}

sub restart{

	my $self	= shift;
	my $rs		= 0;
	my ($stdout, $stderr);

	use iMSCP::Execute;

	# Reload config
	$rs = execute("$self->{CMD_MTA} restart", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;

	$rs;
}

sub postmap{

	use iMSCP::Execute;

	my $self	= shift;
	my $postmap	= shift;
	my $rs		= 0;
	my ($stdout, $stderr);

	# Reload config
	$rs = execute("$self->{CMD_POSTMAP} $postmap", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;

	$rs;
}

sub addDmn{

	use iMSCP::File;
	use iMSCP::Dir;

	my $self	= shift;
	my $data	= shift;
	my $rs		= 0;

	local $Data::Dumper::Terse = 1;
	debug("Data: ". (Dumper $data));

	error('You must supply domain name!') unless $data->{DMN_NAME};
	return 1 unless $data->{DMN_NAME};

	my $entry = "$data->{DMN_NAME}\t\t\t$data->{TYPE}\n";

	if(
		iMSCP::File->new(
			filename => $self->{MTA_VIRTUAL_DMN_HASH}
		)->copyFile( "$self->{bkpDir}/domains.".time )
	){
		$rs = 1;
	}

	my $file	= iMSCP::File->new( filename => "$self->{wrkDir}/domains");
	my $content	= $file->get();

	if(!$content){

		error("Can not read $self->{wrkDir}/domains");
		return 1;

	}

	$content .= $entry unless $content =~ /^$entry/mg;

	$file->set($content);
	$rs |=	$file->save();
	$rs |=	$file->mode(0644);
	$rs |=	$file->owner(
				$main::imscpConfig{'ROOT_USER'},
				$main::imscpConfig{'ROOT_GROUP'}
			);
	$rs |= $file->copyFile( $self->{MTA_VIRTUAL_DMN_HASH} );
	$self->{postmap}->{$self->{MTA_VIRTUAL_DMN_HASH}} = $data->{DMN_NAME};

	$rs =	iMSCP::Dir->new(
				dirname => "$self->{MTA_VIRTUAL_MAIL_DIR}/$data->{DMN_NAME}"
			)->make({
				user	=> $self->{MTA_MAILBOX_UID_NAME},
				group	=> $self->{MTA_MAILBOX_GID_NAME},
				mode	=> 0700
			});

	$rs;
}

sub delDmn{

	use iMSCP::File;
	use iMSCP::Dir;

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	local $Data::Dumper::Terse = 1;
	debug("Data: ". (Dumper $data));

	error('You must supply domain name!') unless $data->{DMN_NAME};
	return 1 unless $data->{DMN_NAME};

	$rs |= $self->disableDmn($data);

	$rs |= iMSCP::Dir->new(
			dirname => "$self->{MTA_VIRTUAL_MAIL_DIR}/$data->{DMN_NAME}"
		)->remove();

	$rs;
}

sub disableDmn{

	use iMSCP::File;
	use iMSCP::Dir;

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	local $Data::Dumper::Terse = 1;
	debug("Data: ". (Dumper $data));

	error('You must supply domain name!') unless $data->{DMN_NAME};
	return 1 unless $data->{DMN_NAME};

	my $entry = "$data->{DMN_NAME}\t\t\t$data->{TYPE}\n";

	if(
		iMSCP::File->new(
			filename => $self->{MTA_VIRTUAL_DMN_HASH}
		)->copyFile( "$self->{bkpDir}/domains.".time )
	){
		$rs = 1;
	}

	my $file	= iMSCP::File->new( filename => "$self->{wrkDir}/domains");
	my $content	= $file->get();

	if(!$content){

		error("Can not read $self->{wrkDir}/domains");
		return 1;

	}

	$content =~ s/^$entry//mg;

	$file->set($content);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner(
				$main::imscpConfig{'ROOT_USER'},
				$main::imscpConfig{'ROOT_GROUP'}
			);
	$rs |= $file->copyFile( $self->{MTA_VIRTUAL_DMN_HASH} );

	$self->{postmap}->{$self->{MTA_VIRTUAL_DMN_HASH}} = $data->{DMN_NAME};

	$rs;
}

sub addSub{
	my $self = shift;
	return $self->addDmn(@_);
}

sub delSub{
	my $self = shift;
	return $self->delDmn(@_);
}

sub disableSub{
	my $self = shift;
	return $self->disableDmn(@_);
}

sub addMail{

	use File::Basename;
	use iMSCP::File;

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	local $Data::Dumper::Terse = 1;
	debug("Data: ". (Dumper $data));

	my $errmsg = {
		'MAIL_ADDR'	=> 'You must supply mail address!',
		'MAIL_PASS'	=> 'You must supply account password!'
	};

	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	for($self->{MTA_VIRTUAL_MAILBOX_HASH}, $self->{MTA_VIRTUAL_ALIAS_HASH}, $self->{MTA_TRANSPORT_HASH}){
		if(-f $_){
			my $file = iMSCP::File->new(filename => $_);
			my (
				$filename,
				$directories,
				$suffix
			) = fileparse($_);
			$rs |=	iMSCP::File->new(
						filename => $_
					)->copyFile(
						"$self->{bkpDir}/$filename$suffix.".time
					)
			;
		}
	}

	$rs |= $self->addSaslData($data) if $data->{MAIL_TYPE} =~ m/_mail/;
	$rs |= $self->delSaslData($data) if $data->{MAIL_TYPE} !~ m/_mail/;

	$rs |= $self->addMailBox($data) if $data->{MAIL_TYPE} =~ m/_mail/;
	$rs |= $self->delMailBox($data) if $data->{MAIL_TYPE} !~ m/_mail/;

	$rs |= $self->addAutoRspnd($data) if $data->{MAIL_AUTO_RSPND};
	$rs |= $self->delAutoRspnd($data) unless $data->{MAIL_AUTO_RSPND};

	$rs |= $self->addMailForward($data) if $data->{MAIL_TYPE} =~ m/_forward/;
	$rs |= $self->delMailForward($data) if $data->{MAIL_TYPE} !~ m/_forward/;

	$rs |= $self->addCatchAll($data) if $data->{MAIL_TYPE} =~ m/_catchall/;
	#$rs |= $self->delCatchAll($data) if $data->{MAIL_TYPE} !~ m/_catchall/;

	$rs;
}

sub delMail{

	use File::Basename;
	use iMSCP::File;

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	local $Data::Dumper::Terse = 1;
	debug("Data: ". (Dumper $data));

	my $errmsg = {
		'MAIL_ADDR'	=> 'You must supply mail address!',
		'MAIL_PASS'	=> 'You must supply account password!'
	};

	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	for($self->{MTA_VIRTUAL_MAILBOX_HASH}, $self->{MTA_VIRTUAL_ALIAS_HASH}, $self->{MTA_TRANSPORT_HASH}){
		if(-f $_){
			my $file = iMSCP::File->new(filename => $_);
			my (
				$filename,
				$directories,
				$suffix
			) = fileparse($_);
			$rs |=	iMSCP::File->new(
						filename => $_
					)->copyFile(
						"$self->{bkpDir}/$filename$suffix.".time
					)
			;
		}
	}

	$rs |= $self->delSaslData($data);
	$rs |= $self->delMailBox($data);
	$rs |= $self->delMailForward($data);
	$rs |= $self->delAutoRspnd($data);
	$rs |= $self->delCatchAll($data);

	$rs;
}

sub disableMail{

	use File::Basename;
	use iMSCP::File;

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	local $Data::Dumper::Terse = 1;
	debug("Data: ". (Dumper $data));

	my $errmsg = {
		'MAIL_ADDR'	=> 'You must supply mail address!',
		'MAIL_PASS'	=> 'You must supply account password!'
	};

	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	for($self->{MTA_VIRTUAL_MAILBOX_HASH}, $self->{MTA_VIRTUAL_ALIAS_HASH}, $self->{MTA_TRANSPORT_HASH}){
		if(-f $_){
			my $file = iMSCP::File->new(filename => $_);
			my (
				$filename,
				$directories,
				$suffix
			) = fileparse($_);
			$rs |=	iMSCP::File->new(
						filename => $_
					)->copyFile(
						"$self->{bkpDir}/$filename$suffix.".time
					)
			;
		}
	}

	$rs |= $self->delSaslData($data);
	$rs |= $self->disableMailBox($data);
	$rs |= $self->delMailForward($data);
	$rs |= $self->delAutoRspnd($data);
	$rs |= $self->delCatchAll($data);

	$rs;
}

sub delSaslData{

	use File::Basename;
	use iMSCP::Execute;
	use iMSCP::File;

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my ($stdout, $stderr);

	my $mailBox		= $data->{MAIL_ADDR};
	$mailBox		=~ s/\./\\\./g;

	my $sasldb = iMSCP::File->new(filename => $self->{ETC_SASLDB_FILE});

	$rs |=	$sasldb->save() unless(-f $self->{ETC_SASLDB_FILE});
	$rs |=	$sasldb->mode(0660);
	$rs |=	$sasldb->owner(
			$self->{SASLDB_USER},
			$self->{SASLDB_GROUP}
		);

	$rs |= execute("$self->{CMD_SASLDB_LISTUSERS2} -f $self->{ETC_SASLDB_FILE}", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr;

	if(!$rs && $stdout =~ m/^$mailBox:/mgi){

		$rs |= execute("$self->{CMD_SASLDB_PASSWD2} -d -f $self->{ETC_SASLDB_FILE} -u $data->{DMN_NAME} $data->{MAIL_ACC}", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr;

		if($self->{ETC_SASLDB_FILE} ne $self->{MTA_SASLDB_FILE}){
			$rs |= execute("$main::imscpConfig{'CMD_CP'} -pf $self->{ETC_SASLDB_FILE} $self->{MTA_SASLDB_FILE}", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr;
		}
	}

	$rs;
}

sub addSaslData{

	use File::Basename;
	use iMSCP::Execute;
	use iMSCP::File;

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my ($stdout, $stderr);

	my $mailBox	= $data->{MAIL_ADDR};
	$mailBox	=~ s/\./\\\./g;

	my $sasldb = iMSCP::File->new(filename => $self->{ETC_SASLDB_FILE});

	$rs |=	$sasldb->save() unless(-f $self->{ETC_SASLDB_FILE});
	$rs |=	$sasldb->mode(0660);
	$rs |=	$sasldb->owner(
			$self->{SASLDB_USER},
			$self->{SASLDB_GROUP}
		);

	$rs |= execute("$self->{CMD_SASLDB_LISTUSERS2} -f $self->{ETC_SASLDB_FILE}", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr;

	if(!$rs && $stdout =~ m/^$mailBox:/mgi){
		$rs |= execute("$self->{CMD_SASLDB_PASSWD2} -d -f $self->{ETC_SASLDB_FILE} -u $data->{DMN_NAME} $data->{MAIL_ACC}", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr;
	}

	$rs |= execute("$main::imscpConfig{'CMD_ECHO'} \"$data->{MAIL_PASS}\" | $self->{CMD_SASLDB_PASSWD2} -p -c -f $self->{ETC_SASLDB_FILE} -u $data->{DMN_NAME} $data->{MAIL_ACC}", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr;

	if($self->{ETC_SASLDB_FILE} ne $self->{MTA_SASLDB_FILE}){
		$rs |= execute("$main::imscpConfig{'CMD_CP'} -pf $self->{ETC_SASLDB_FILE} $self->{MTA_SASLDB_FILE}", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr;
	}

	$rs;
}

sub delAutoRspnd{

	use File::Basename;
	use iMSCP::File;

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my $mTrsptHshFile	= $self->{MTA_TRANSPORT_HASH};
	my ($filename, $directories, $suffix) = fileparse($mTrsptHshFile);
	my $wrkFileName	= "$self->{wrkDir}/$filename$suffix";
	my $wrkFile		= iMSCP::File->new(filename => $wrkFileName);
	my $wrkContent		= $wrkFile->get();
	return 1 unless defined $wrkContent;

	my $trnsprt		= "imscp-arpl.$data->{DMN_NAME}";
	$trnsprt		=~ s/\./\\\./g;
	$wrkContent		=~ s/^$trnsprt\t[^\n]*\n//gmi;
	$wrkFile->set($wrkContent);
	return 1 if $wrkFile->save();
	$rs |=	$wrkFile->mode(0644);
	$rs |=	$wrkFile->owner(
				$main::imscpConfig{ROOT_USER},
				$main::imscpConfig{ROOT_GROUP}
			);
	$rs |= $wrkFile->copyFile($mTrsptHshFile);

	$self->{postmap}->{$self->{MTA_TRANSPORT_HASH}} = $data->{MAIL_ADDR};

	$rs;
}

sub addAutoRspnd{

	use File::Basename;
	use iMSCP::File;

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my $mTrsptHshFile	= $self->{MTA_TRANSPORT_HASH};
	my ($filename, $directories, $suffix) = fileparse($mTrsptHshFile);
	my $wrkFileName		= "$self->{wrkDir}/$filename$suffix";
	my $wrkFile			= iMSCP::File->new(filename => $wrkFileName);
	my $wrkContent		= $wrkFile->get();
	return 1 unless defined $wrkContent;

	my $trnsprt		= "imscp-arpl.$data->{DMN_NAME}";
	$trnsprt		=~ s/\./\\\./g;
	$wrkContent		=~ s/^$trnsprt\t[^\n]*\n//gmi;
	$wrkContent		.= "imscp-arpl.$data->{DMN_NAME}\timscp-arpl:\n";
	$wrkFile->set($wrkContent);
	return 1 if $wrkFile->save();
	$rs |=	$wrkFile->mode(0644);
	$rs |=	$wrkFile->owner(
				$main::imscpConfig{ROOT_USER},
				$main::imscpConfig{ROOT_GROUP}
			);
	$rs |= $wrkFile->copyFile($mTrsptHshFile);

	$self->{postmap}->{$self->{MTA_TRANSPORT_HASH}} = $data->{MAIL_ADDR};

	$rs;
}

sub delMailForward{

	use File::Basename;
	use iMSCP::File;

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my $mFWDHshFile	= $self->{MTA_VIRTUAL_ALIAS_HASH};
	my ($filename, $directories, $suffix) = fileparse($mFWDHshFile);
	my $wrkFileName	= "$self->{wrkDir}/$filename$suffix";
	my $wrkFile		= iMSCP::File->new(filename => $wrkFileName);
	my $wrkContent	= $wrkFile->get();
	return 1 unless defined $wrkContent;

	my $mailbox		= $data->{MAIL_ADDR};
	$mailbox		=~ s/\./\\\./g;
	$wrkContent		=~ s/^$mailbox\t[^\n]*\n//gmi;
	#if we have an autoresponder or a catch all we re-add entry
	#but only if we not delete mail
	if($data->{MAIL_STATUS} ne'delete'){
		#for catch all we need a line like a@aa.aa\ta@aa.aa
		my @line;
		push(@line, $data->{MAIL_ADDR}) if ($data->{MAIL_HAVE_CATCH_ALL} eq 'yes' || $data->{MAIL_AUTO_RSPND}) && $data->{MAIL_TYPE} =~ m/_mail/;
		#for catch all we need a line like a@aa.aa\t[...]a@imscp-arpl.aa.aa
		push(@line, "$data->{MAIL_ACC}\@imscp-arpl.$data->{DMN_NAME}")if $data->{MAIL_AUTO_RSPND} && $data->{MAIL_TYPE} =~ m/_mail/;
		$wrkContent .= "$data->{MAIL_ADDR}\t".join(',', @line)."\n" if scalar @line;
	}

	$wrkFile->set($wrkContent);
	return 1 if $wrkFile->save();
	$rs |=	$wrkFile->mode(0644);
	$rs |=	$wrkFile->owner(
				$main::imscpConfig{ROOT_USER},
				$main::imscpConfig{ROOT_GROUP}
			);
	$rs |= $wrkFile->copyFile($mFWDHshFile);

	$self->{postmap}->{$self->{MTA_VIRTUAL_ALIAS_HASH}} = $data->{MAIL_ADDR};

	$rs;
}

sub addMailForward{

	use File::Basename;
	use iMSCP::File;

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my $mFWDHshFile	= $self->{MTA_VIRTUAL_ALIAS_HASH};
	my ($filename, $directories, $suffix) = fileparse($mFWDHshFile);
	my $wrkFileName	= "$self->{wrkDir}/$filename$suffix";
	my $wrkFile		= iMSCP::File->new(filename => $wrkFileName);
	my $wrkContent	= $wrkFile->get();
	return 1 unless defined $wrkContent;

	my $mailbox		= $data->{MAIL_ADDR};
	$mailbox		=~ s/\./\\\./g;
	$wrkContent		=~ s/^$mailbox\t[^\n]*\n//gmi;

	my @line;
	push(@line, $data->{MAIL_ADDR}) if $data->{MAIL_TYPE} =~ m/_mail/;

	push(@line, $data->{MAIL_FORWARD});
	#for catch all we need a line like a@aa.aa\t[...]a@imscp-arpl.aa.aa
	push(@line, "$data->{MAIL_ACC}\@imscp-arpl.$data->{DMN_NAME}")if $data->{MAIL_AUTO_RSPND};
	$wrkContent .= "$data->{MAIL_ADDR}\t".join(',', @line)."\n" if scalar @line;

	$wrkFile->set($wrkContent);
	return 1 if $wrkFile->save();
	$rs |=	$wrkFile->mode(0644);
	$rs |=	$wrkFile->owner(
				$main::imscpConfig{ROOT_USER},
				$main::imscpConfig{ROOT_GROUP}
			);
	$rs |= $wrkFile->copyFile($mFWDHshFile);

	$self->{postmap}->{$self->{MTA_VIRTUAL_ALIAS_HASH}} = $data->{MAIL_ADDR};

	$rs;
}

sub delMailBox{

	use iMSCP::Dir;

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs |= $self->disableMailBox($data);

	return $rs if !$data->{MAIL_ACC}; #catchall?

	my $mailDir = "$self->{MTA_VIRTUAL_MAIL_DIR}/$data->{DMN_NAME}/$data->{MAIL_ACC}";

	$rs |=	iMSCP::Dir->new(dirname => $mailDir)->remove();

	$rs;
}

sub disableMailBox{

	use File::Basename;
	use iMSCP::File;

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my $mBoxHashFile	= $self->{MTA_VIRTUAL_MAILBOX_HASH};
	my ($filename, $directories, $suffix) = fileparse($mBoxHashFile);
	my $wrkFileName	= "$self->{wrkDir}/$filename$suffix";
	my $wrkFile		= iMSCP::File->new(filename => $wrkFileName);
	my $wrkContent	= $wrkFile->get();
	return 1 unless defined $wrkContent;

	my $mailbox		= $data->{MAIL_ADDR};
	$mailbox		=~ s/\./\\\./g;
	$wrkContent		=~ s/^$mailbox\t[^\n]*\n//gmi;
	$wrkFile->set($wrkContent);
	return 1 if $wrkFile->save();
	$rs |=	$wrkFile->mode(0644);
	$rs |=	$wrkFile->owner(
				$main::imscpConfig{ROOT_USER},
				$main::imscpConfig{ROOT_GROUP}
			);
	$rs |= $wrkFile->copyFile($mBoxHashFile);

	$self->{postmap}->{$self->{MTA_VIRTUAL_MAILBOX_HASH}} = $data->{MAIL_ADDR};

	$rs;
}

sub addMailBox{

	use File::Basename;
	use iMSCP::File;
	use iMSCP::Dir;

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my $mBoxHashFile	= $self->{MTA_VIRTUAL_MAILBOX_HASH};
	my ($filename, $directories, $suffix) = fileparse($mBoxHashFile);
	my $wrkFileName	= "$self->{wrkDir}/$filename$suffix";
	my $wrkFile		= iMSCP::File->new(filename => $wrkFileName);
	my $wrkContent	= $wrkFile->get();
	return 1 unless defined $wrkContent;

	my $mailbox		= $data->{MAIL_ADDR};
	$mailbox		=~ s/\./\\\./g;
	$wrkContent		=~ s/^$mailbox\t[^\n]*\n//gmi;
	$wrkContent		.= "$data->{MAIL_ADDR}\t$data->{DMN_NAME}/$data->{MAIL_ACC}/\n";
	$wrkFile->set($wrkContent);
	return 1 if $wrkFile->save();
	$rs |=	$wrkFile->mode(0644);
	$rs |=	$wrkFile->owner(
				$main::imscpConfig{ROOT_USER},
				$main::imscpConfig{ROOT_GROUP}
			);
	$rs |= $wrkFile->copyFile($mBoxHashFile);

	$self->{postmap}->{$self->{MTA_VIRTUAL_MAILBOX_HASH}} = $data->{MAIL_ADDR};

	my $mailDir = "$self->{MTA_VIRTUAL_MAIL_DIR}/$data->{DMN_NAME}/$data->{MAIL_ACC}";

	$rs |=	iMSCP::Dir->new(dirname => $mailDir)->make({
				user	=> $self->{MTA_MAILBOX_UID_NAME},
				group	=> $self->{MTA_MAILBOX_GID_NAME},
				mode	=> 0700
			});

	for ("$mailDir/cur", "$mailDir/tmp", "$mailDir/new"){
		$rs |= iMSCP::Dir->new(dirname => $_)->make({
			user	=> $self->{MTA_MAILBOX_UID_NAME},
			group	=> $self->{MTA_MAILBOX_GID_NAME},
			mode	=> 0700
		});
	}

	$rs;
}

sub addCatchAll{

	use File::Basename;
	use iMSCP::File;

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my $mFWDHshFile	= $self->{MTA_VIRTUAL_ALIAS_HASH};
	my ($filename, $directories, $suffix) = fileparse($mFWDHshFile);
	my $wrkFileName	= "$self->{wrkDir}/$filename$suffix";
	my $wrkFile		= iMSCP::File->new(filename => $wrkFileName);
	my $wrkContent	= $wrkFile->get();
	return 1 unless defined $wrkContent;

	for(@{$data->{MAIL_ON_CATCHALL}}){
		my $mailbox		= $_;
		$mailbox		=~ s/\./\\\./g;
		$wrkContent		=~ s/^$mailbox\t$mailbox\n//gmi;
		$wrkContent		.= "$_\t$_\n";
	}

	my $catchAll	= "\@$data->{DMN_NAME}";
	$catchAll		=~ s/\./\\\./g;
	$wrkContent		=~ s/^$catchAll\t[^\n]*\n//gmi;
	$wrkContent		.= "\@$data->{DMN_NAME}\t$data->{MAIL_CATCHALL}\n";
	$wrkFile->set($wrkContent);
	return 1 if $wrkFile->save();
	$rs |=	$wrkFile->mode(0644);
	$rs |=	$wrkFile->owner(
				$main::imscpConfig{ROOT_USER},
				$main::imscpConfig{ROOT_GROUP}
			);
	$rs |= $wrkFile->copyFile($mFWDHshFile);

	$self->{postmap}->{$self->{MTA_VIRTUAL_ALIAS_HASH}} = $data->{MAIL_ADDR};

	$rs;
}

sub delCatchAll{

	use File::Basename;
	use iMSCP::File;

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my $mFWDHshFile	= $self->{MTA_VIRTUAL_ALIAS_HASH};
	my ($filename, $directories, $suffix) = fileparse($mFWDHshFile);
	my $wrkFileName	= "$self->{wrkDir}/$filename$suffix";
	my $wrkFile		= iMSCP::File->new(filename => $wrkFileName);
	my $wrkContent	= $wrkFile->get();
	return 1 unless defined $wrkContent;

	for(@{$data->{MAIL_ON_CATCHALL}}){
		my $mailbox		= $_;
		$mailbox		=~ s/\./\\\./g;
		$wrkContent		=~ s/^$mailbox\t$mailbox\n//gmi;
	}

	my $catchAll	= "\@$data->{DMN_NAME}";
	$catchAll		=~ s/\./\\\./g;
	$wrkContent		=~ s/^$catchAll\t[^\n]*\n//gmi;
	$wrkFile->set($wrkContent);
	return 1 if $wrkFile->save();
	$rs |=	$wrkFile->mode(0644);
	$rs |=	$wrkFile->owner(
				$main::imscpConfig{ROOT_USER},
				$main::imscpConfig{ROOT_GROUP}
			);
	$rs |= $wrkFile->copyFile($mFWDHshFile);

	$self->{postmap}->{$self->{MTA_VIRTUAL_ALIAS_HASH}} = $data->{MAIL_ADDR};

	$rs;
}

sub getTraffic{

	use iMSCP::Execute;
	use iMSCP::File;
	use iMSCP::Config;
	use Tie::File;

	my $self		= shift;
	my $who			= shift;
	my $dbName		= "$self->{wrkDir}/log.db";
	my $logFile		= "$main::imscpConfig{TRAFF_LOG_DIR}/mail.log";
	my $wrkLogFile	= "$main::imscpConfig{LOG_DIR}/mail.smtp.log";
	my ($rv, $rs, $stdout, $stderr);

	##only if files was not aleady parsed this session
	unless($self->{logDb}){
		#use a small conf file to memorize last line readed and his content
		tie %{$self->{logDb}}, 'iMSCP::Config','fileName' => $dbName, noerrors => 1;
		##first use? we zero line and content
		$self->{logDb}->{line} = 0 unless $self->{logDb}->{line};
		$self->{logDb}->{content} = '' unless $self->{logDb}->{content};
		my $lastLineNo	= $self->{logDb}->{line};
		my $lastLine	= $self->{logDb}->{content};
		##copy log file
		$rs = iMSCP::File->new(filename => $logFile)->copyFile($wrkLogFile) if -f $logFile;
		#retunt 0 traffic if we fail
		return 0 if $rs;
		#link log file to array
		tie my @content, 'Tie::File', $wrkLogFile or return 0;
		##save last line
		$self->{logDb}->{line} = $#content;
		$self->{logDb}->{content} = @content[$#content];
		#test for logratation
		if(@content[$lastLineNo] && @content[$lastLineNo] eq $lastLine){
			## No logratation ocure. We zero already readed files
			(tied @content)->defer;
			@content = @content[$lastLineNo + 1 .. $#content];
			(tied @content)->flush;
		}
		$rs = execute("$main::imscpConfig{'CMD_GREP'} 'postfix' $wrkLogFile | $main::imscpConfig{'CMD_PFLOGSUM'} standard", \$stdout, \$stderr);
		error($stderr) if $stderr;
		return 0 if $rs;
		while($stdout =~ m/^[^\s]+\s[^\s]+\s[^\s\@]+\@([^\s]+)\s[^\s\@]+\@([^\s]+)\s([^\s]+)\s([^\s]+)\s[^\s]+\s[^\s]+\s[^\s]+\s(.*)$/mg){
						 #  date    time    mailfrom @ domain   mailto   @ domain    relay_s   relay_r   SMTP  extinfo  code     size
						 #                                1                  2         3         4                                 5
			if($main::imscpConfig{MAIL_LOG_INC_AMAVIS}){
				if($5 ne '?' &&  !($3 =~ /localhost|127.0.0.1/ && $4 =~ /localhost|127.0.0.1/)){
					$self->{traff}->{$1} += $5;
					$self->{traff}->{$2} += $5;
				}
			} else {
				if($5 ne '?' && $4 !~ /virtual/ && !($3 =~ /localhost|127.0.0.1/ && $4 =~ /localhost|127.0.0.1/)){
					$self->{traff}->{$1} += $5;
					$self->{traff}->{$2} += $5;
				}
			}
		}
	}
	$self->{traff}->{$who} ? $self->{traff}->{$who} : 0;
}

END{

	use iMSCP::File;

	my $endCode		= $?;
	my $self		= Servers::mta::postfix->new();
	my $wrkLogFile	= "$main::imscpConfig{LOG_DIR}/mail.smtp.log";
	my $rs			= 0;

	if($self->{restart} && $self->{restart} eq 'yes'){
		$rs = $self->restart();
	} else {
		$rs |= $self->postmap($_) foreach(keys %{$self->{postmap}});
	}

	$rs |= iMSCP::File->new(filename => $wrkLogFile)->delFile() if -f $wrkLogFile;

	$? = $endCode || $rs;
}

1;
