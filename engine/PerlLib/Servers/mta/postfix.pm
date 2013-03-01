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
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::mta::postfix;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Config;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Dir;
use File::Basename;
use Tie::File;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger('beforeMtaInit', $self, 'postfix');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/postfix";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'commentChar'} = '#';

	tie %self::postfixConfig, 'iMSCP::Config','fileName' => "$self->{'cfgDir'}/postfix.data";
	$self->{$_} = $self::postfixConfig{$_} for keys %self::postfixConfig;

	$self->{'hooksManager'}->trigger('afterMtaInit', $self, 'postfix');

	$self;
}

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;
	my $rs = 0;

	$rs = $hooksManager->trigger('beforeMtaRegisterSetupHooks', $hooksManager, 'postfix');

	$rs |= $hooksManager->trigger('afterMtaRegisterSetupHooks', $hooksManager, 'postfix');

	$rs;
}

sub preinstall
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaPreInstall', 'postfix');

	require Servers::mta::postfix::installer;

	$rs |= Servers::mta::postfix::installer->new()->preinstall();

	$rs |= $self->{'hooksManager'}->trigger('afterMtaPreInstall', 'postfix');

	$rs;
}

sub install
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaInstall', 'postfix');

	require Servers::mta::postfix::installer;

	$rs |= Servers::mta::postfix::installer->new()->install();

	$rs |= $self->{'hooksManager'}->trigger('afterMtaInstall', 'postfix');

	$rs;
}

sub uninstall
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaUninstall', 'postfix');

	require Servers::mta::postfix::uninstaller;

	$rs |= Servers::mta::postfix::uninstaller->new()->uninstall();

	$rs |= $self->restart();

	$rs |= $self->{'hooksManager'}->trigger('afterMtaUninstall', 'postfix');

	$rs;
}

sub postinstall
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaPostinstall', 'postfix');

	$self->{'restart'} = 'yes';

	$rs = $self->{'hooksManager'}->trigger('afterMtaPostinstall', 'postfix');

	$rs;
}

sub setEnginePermissions
{
	my $self= shift;

	require Servers::mta::postfix::installer;

	Servers::mta::postfix::installer->new()->setEnginePermissions();
}

sub restart
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaRestart');

	# Reload config
	my ($stdout, $stderr);
	$rs |= execute("$self->{'CMD_MTA'} restart", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs |= $self->{'hooksManager'}->trigger('afterMtaRestart');

	$rs;
}

sub postmap
{
	my $self = shift;
	my $postmap	= shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaPostmap', \$postmap);

	# Reload config
	my ($stdout, $stderr);
	$rs |= execute("$self->{'CMD_POSTMAP'} $postmap", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs |= $self->{'hooksManager'}->trigger('afterMtaPostmap', $postmap);

	$rs;
}

sub addDmn
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	error('You must supply domain name!') unless $data->{'DMN_NAME'};
	return 1 unless $data->{'DMN_NAME'};

	$rs = $self->{'hooksManager'}->trigger('beforeMtaAddDmn', $data);
	return $rs if $rs;

	if($data->{'EXTERNAL_MAIL'} eq 'on') { # Mail for both domain and subdomains is managed by external server
		# Remove entry from the Postfix virtual_mailbox_domains map
		$rs |= $self->disableDmn($data);

		if($data->{'DMN_TYPE'} eq 'Dmn') {
			# Remove any previous entry of this domain from the Postfix relay_domains map
        	$rs |= $self->delFromRelayHash($data);

			# Add the domain entry to the Postfix relay_domain map
			$rs |= $self->addToRelayHash($data);
		}
	} elsif($data->{'EXTERNAL_MAIL'} eq 'wildcard') { # Only mail for in-existent subdomains is managed by external server
		# Add the domain or subdomain entry to the Postfix virtual_mailbox_domains map
		$rs |= $self->addToDomainHash($data);

		if($data->{'DMN_TYPE'} eq 'Dmn') {
			# Remove any previous entry of this domain from the Postfix relay_domains map
			$rs |= $self->delFromRelayHash($data);
			# Add the wildcard entry for in-existent subdomains to the Postfix relay_domain map
			$rs |= $self->addToRelayHash($data);
		}
	} else { # Mail for both domain and subdomains is managed by iMSCP mail host
		# Add domain or subdomain entry to the Postfix virtual_mailbox_domains map
		$rs |= $self->addToDomainHash($data);

		if($data->{'DMN_TYPE'} eq 'Dmn') {
			# Remove any previous entry of this domain from the Postfix relay_domains map
			$rs |= $self->delFromRelayHash($data);
		}
	}

	$rs |= $self->{'hooksManager'}->trigger('afterMtaAddDmn', $data);

	$rs;
}

sub addToRelayHash
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaAddToRelayHash', $data);
	return $rs if $rs;

	my $entry = "$data->{'DMN_NAME'}\t\t\tOK\n";

	if($data->{'EXTERNAL_MAIL'} eq 'wildcard') { # For wildcard MX, we add entry such as ".domain.tld"
		$entry = '.' . $entry;
	}

	$rs = 1 if(iMSCP::File->new('filename' => $self->{'MTA_RELAY_HASH'})->copyFile( "$self->{'bkpDir'}/relay_domains.".time));

	my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/relay_domains");
	my $content	= $file->get();

	if(! $content){
		error("Cannot read $self->{'wrkDir'}/relay_domains");
		return 1;
	}

	$content .= $entry unless $content =~ /^$entry/mg;

	$file->set($content);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs |= $file->copyFile( $self->{'MTA_RELAY_HASH'} );
	$self->{'postmap'}->{$self->{'MTA_RELAY_HASH'}} = $data->{'DMN_NAME'};

	$rs |= $self->{'hooksManager'}->trigger('afterMtaAddToRelayHash', $data);

	$rs;
}

sub delFromRelayHash
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaDelFromRelayHash', $data);
	return $rs if $rs;

	my $entry = "\.?$data->{'DMN_NAME'}\t\t\tOK\n"; # Match both "domain.tld" and ".domain.tld" entries

	$rs = 1 if(
		iMSCP::File->new(
			filename => $self->{'MTA_RELAY_HASH'}
		)->copyFile("$self->{'bkpDir'}/relay_domains.".time)
	);

	my $file= iMSCP::File->new('filename' => "$self->{'wrkDir'}/relay_domains");
	my $content = $file->get();

	if(! $content){
		error("Can not read $self->{'wrkDir'}/relay_domains");
		return 1;
	}

	$content =~ s/^$entry//mg;

	$file->set($content);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs |= $file->copyFile( $self->{'MTA_RELAY_HASH'} );
	$self->{'postmap'}->{$self->{'MTA_RELAY_HASH'}} = $data->{'DMN_NAME'};

	$rs |= $self->{'hooksManager'}->trigger('afterMtaDelFromRelayHash', $data);

	$rs;
}

sub addToDomainHash
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaAddToDomainHash', $data);
	return $rs if $rs;

	my $entry = "$data->{'DMN_NAME'}\t\t\t$data->{'TYPE'}\n";

	$rs = 1 if(
		iMSCP::File->new(
			filename => $self->{'MTA_VIRTUAL_DMN_HASH'}
		)->copyFile( "$self->{'bkpDir'}/domains.".time )
	);

	my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/domains");
	my $content	= $file->get();

	if(! $content){
		error("Cannot read $self->{'wrkDir'}/domains");
		return 1;
	}

	$content .= $entry unless $content =~ /^$entry/mg;

	$file->set($content);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs |= $file->copyFile( $self->{'MTA_VIRTUAL_DMN_HASH'} );
	$self->{'postmap'}->{$self->{'MTA_VIRTUAL_DMN_HASH'}} = $data->{'DMN_NAME'};

	$rs = iMSCP::Dir->new(
		dirname => "$self->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DMN_NAME'}"
	)->make(
		{ 'user' => $self->{'MTA_MAILBOX_UID_NAME'}, 'group' => $self->{'MTA_MAILBOX_GID_NAME'}, 'mode' => 0700 }
	);

	$rs |= $self->{'hooksManager'}->trigger('afterMtaAddToDomainHash', $data);

	$rs;
}

sub delDmn
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	error('You must supply domain name!') unless $data->{'DMN_NAME'};
	return 1 unless $data->{'DMN_NAME'};

	$rs = $self->{'hooksManager'}->trigger('beforeMtaDelDmn', $data);

	$rs |= $self->disableDmn($data);
	$rs |= iMSCP::Dir->new('dirname' => "$self->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DMN_NAME'}")->remove();

	$rs |= $self->{'hooksManager'}->trigger('afterMtaDelDmn', $data);

	$rs;
}

sub disableDmn
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	error('You must supply domain name!') unless $data->{'DMN_NAME'};
	return 1 unless $data->{'DMN_NAME'};

	$self->{'hooksManager'}->trigger('beforeMtaDisableDmn', $data);
	return $rs if $rs;

	my $entry = "$data->{'DMN_NAME'}\t\t\t$data->{'TYPE'}\n";

	if(iMSCP::File->new('filename' => $self->{'MTA_VIRTUAL_DMN_HASH'})->copyFile("$self->{'bkpDir'}/domains." . time)) {
		$rs = 1;
	}

	my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/domains");
	my $content = $file->get();

	if(! $content){
		error("Cannot read $self->{'wrkDir'}/domains");
		return 1;
	}

	$content =~ s/^$entry//mg;

	$file->set($content);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs |= $file->copyFile( $self->{'MTA_VIRTUAL_DMN_HASH'} );

	$self->{'postmap'}->{$self->{'MTA_VIRTUAL_DMN_HASH'}} = $data->{'DMN_NAME'};

	if($data->{'DMN_TYPE'} eq 'Dmn') {
		$rs |= $self->delFromRelayHash($data);
	}

	$rs |= $self->{'hooksManager'}->trigger('afterMtaDisableDmn', $data);

	$rs;
}

sub addSub
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaAddSub');

	$rs |= $self->addDmn(@_);

	$rs |= $self->{'hooksManager'}->trigger('afterMtaAddSub');

	$rs;
}

sub delSub
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaDelSub');

	$rs |= $self->delDmn(@_);

	$rs |= $self->{'hooksManager'}->trigger('afterMtaDelSub');

	$rs;
}

sub disableSub
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaDisableSub');

	$rs |= $self->disableDmn(@_);

	$rs |= $self->{'hooksManager'}->trigger('afterMtaDisableSub');

	$rs;
}

sub addMail
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my $errmsg = {
		'MAIL_ADDR'	=> 'You must supply mail address!',
		'MAIL_PASS'	=> 'You must supply account password!'
	};

	for(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	$rs = $self->{'hooksManager'}->trigger('beforeMtaAddMail', $data);
	return $rs if $rs;

	for($self->{'MTA_VIRTUAL_MAILBOX_HASH'}, $self->{'MTA_VIRTUAL_ALIAS_HASH'}, $self->{'MTA_TRANSPORT_HASH'}) {
		if(-f $_) {
			my $file = iMSCP::File->new('filename' => $_);
			my ($filename, $directories, $suffix) = fileparse($_);

			$rs |=	iMSCP::File->new(
				'filename' => $_
			)->copyFile(
				"$self->{'bkpDir'}/$filename$suffix.".time
			);
		}
	}

	$rs |= $self->addSaslData($data) if $data->{'MAIL_TYPE'} =~ m/_mail/;
	$rs |= $self->delSaslData($data) if $data->{'MAIL_TYPE'} !~ m/_mail/;

	$rs |= $self->addMailBox($data) if $data->{'MAIL_TYPE'} =~ m/_mail/;
	$rs |= $self->delMailBox($data) if $data->{'MAIL_TYPE'} !~ m/_mail/;

	$rs |= $self->addAutoRspnd($data) if $data->{'MAIL_HAS_AUTO_RSPND'}  eq 'yes';
	$rs |= $self->delAutoRspnd($data) if $data->{'MAIL_HAS_AUTO_RSPND'} eq 'no';

	$rs |= $self->addMailForward($data) if $data->{'MAIL_TYPE'} =~ m/_forward/;
	$rs |= $self->delMailForward($data) if $data->{'MAIL_TYPE'} !~ m/_forward/;

	$rs |= $self->addCatchAll($data) if $data->{'MAIL_HAS_CATCH_ALL'} eq 'yes';
	$rs |= $self->delCatchAll($data) if $data->{'MAIL_HAS_CATCH_ALL'} eq 'no';

	$rs |= $self->{'hooksManager'}->trigger('afterMtaAddMail', $data);

	$rs;
}

sub delMail
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my $errmsg = {
		'MAIL_ADDR'	=> 'You must supply mail address!',
		'MAIL_PASS'	=> 'You must supply account password!'
	};

	for(keys %{$errmsg}) {
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	$rs = $self->{'hooksManager'}->trigger('beforeMtaDelMail', $data);
	return $rs if $rs;

	for($self->{'MTA_VIRTUAL_MAILBOX_HASH'}, $self->{'MTA_VIRTUAL_ALIAS_HASH'}, $self->{'MTA_TRANSPORT_HASH'}) {
		if(-f $_) {
			my $file = iMSCP::File->new('filename' => $_);
			my ($filename, $directories, $suffix) = fileparse($_);
			$rs |=	iMSCP::File->new(
				filename => $_
			)->copyFile(
				"$self->{'bkpDir'}/$filename$suffix.".time
			);
		}
	}

	$rs |= $self->delSaslData($data);
	$rs |= $self->delMailBox($data);
	$rs |= $self->delMailForward($data);
	$rs |= $self->delAutoRspnd($data);
	$rs |= $self->delCatchAll($data);

	$rs |= $self->{'hooksManager'}->trigger('afterMtaDelMail', $data);

	$rs;
}

sub disableMail
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my $errmsg = {
		'MAIL_ADDR'	=> 'You must supply mail address!',
		'MAIL_PASS'	=> 'You must supply account password!'
	};

	for(keys %{$errmsg}) {
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	$rs = $self->{'hooksManager'}->trigger('beforeMtaDisableMail', $data);
	return $rs if $rs;

	for($self->{'MTA_VIRTUAL_MAILBOX_HASH'}, $self->{'MTA_VIRTUAL_ALIAS_HASH'}, $self->{'MTA_TRANSPORT_HASH'}){
		if(-f $_) {
			my $file = iMSCP::File->new('filename' => $_);
			my ($filename, $directories, $suffix) = fileparse($_);
			$rs |=	iMSCP::File->new(
				'filename' => $_
			)->copyFile(
				"$self->{'bkpDir'}/$filename$suffix.".time
			);
		}
	}

	$rs |= $self->delSaslData($data);
	$rs |= $self->disableMailBox($data);
	$rs |= $self->delMailForward($data);
	$rs |= $self->delAutoRspnd($data);
	$rs |= $self->delCatchAll($data);

	$rs |= $self->{'hooksManager'}->trigger('afterMtaDisableMail', $data);

	$rs;
}

sub delSaslData
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaDelSaslData', $data);
	return $rs if $rs;

	my ($stdout, $stderr);

	my $mailBox = $data->{'MAIL_ADDR'};
	$mailBox =~ s/\./\\\./g;

	my $sasldb = iMSCP::File->new('filename' => $self->{'ETC_SASLDB_FILE'});

	$rs |= $sasldb->save() unless -f $self->{'ETC_SASLDB_FILE'};
	$rs |= $sasldb->mode(0660);
	$rs |= $sasldb->owner($self->{'SASLDB_USER'}, $self->{'SASLDB_GROUP'});
	$rs |= execute("$self->{'CMD_SASLDB_LISTUSERS2'} -f $self->{'ETC_SASLDB_FILE'}", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr;

	if(!$rs && $stdout =~ m/^$mailBox:/mgi) {
		$rs |= execute("$self->{'CMD_SASLDB_PASSWD2'} -d -f $self->{'ETC_SASLDB_FILE'} -u $data->{'DMN_NAME'} $data->{'MAIL_ACC'}", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr;

		if($self->{'ETC_SASLDB_FILE'} ne $self->{'MTA_SASLDB_FILE'}){
			$rs |= execute("$main::imscpConfig{'CMD_CP'} -pf $self->{'ETC_SASLDB_FILE'} $self->{'MTA_SASLDB_FILE'}", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr;
		}
	}

	$rs |= $self->{'hooksManager'}->trigger('afterMtaDelSaslData', $data);

	$rs;
}

sub addSaslData
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaAddSaslData', $data);
	return $rs if $rs;

	my ($stdout, $stderr);

	my $mailBox	= $data->{'MAIL_ADDR'};
	$mailBox =~ s/\./\\\./g;

	my $sasldb = iMSCP::File->new('filename' => $self->{'ETC_SASLDB_FILE'});

	$rs |= $sasldb->save() unless(-f $self->{'ETC_SASLDB_FILE'});
	$rs |= $sasldb->mode(0660);
	$rs |= $sasldb->owner($self->{'SASLDB_USER'}, $self->{'SASLDB_GROUP'});

	$rs |= execute("$self->{'CMD_SASLDB_LISTUSERS2'} -f $self->{'ETC_SASLDB_FILE'}", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr;

	if(!$rs && $stdout =~ m/^$mailBox:/mgi){
		$rs |= execute("$self->{'CMD_SASLDB_PASSWD2'} -d -f $self->{'ETC_SASLDB_FILE'} -u $data->{'DMN_NAME'} $data->{'MAIL_ACC'}", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr;
	}

	$rs |= execute("$main::imscpConfig{'CMD_ECHO'} \"$data->{'MAIL_PASS'}\" | $self->{'CMD_SASLDB_PASSWD2'} -p -c -f $self->{'ETC_SASLDB_FILE'} -u $data->{'DMN_NAME'} $data->{'MAIL_ACC'}", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr;

	if($self->{'ETC_SASLDB_FILE'} ne $self->{'MTA_SASLDB_FILE'}){
		$rs |= execute("$main::imscpConfig{'CMD_CP'} -pf $self->{'ETC_SASLDB_FILE'} $self->{'MTA_SASLDB_FILE'}", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr;
	}

	$rs |= $self->{'hooksManager'}->trigger('afterMtaAddSaslData', $data);

	$rs;
}

sub delAutoRspnd
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaDelAutoRspnd', $data);
	return $rs if $rs;

	my $mTrsptHshFile = $self->{'MTA_TRANSPORT_HASH'};
	my ($filename, $directories, $suffix) = fileparse($mTrsptHshFile);
	my $wrkFileName	= "$self->{'wrkDir'}/$filename$suffix";
	my $wrkFile = iMSCP::File->new('filename' => $wrkFileName);
	my $wrkContent = $wrkFile->get();
	return 1 unless defined $wrkContent;

	my $trnsprt = "imscp-arpl.$data->{'DMN_NAME'}";
	$trnsprt =~ s/\./\\\./g;
	$wrkContent =~ s/^$trnsprt\t[^\n]*\n//gmi;
	$wrkFile->set($wrkContent);
	return 1 if $wrkFile->save();

	$rs |=	$wrkFile->mode(0644);
	$rs |=	$wrkFile->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs |= $wrkFile->copyFile($mTrsptHshFile);

	$self->{'postmap'}->{$self->{'MTA_TRANSPORT_HASH'}} = $data->{'MAIL_ADDR'};

	$rs |= $self->{'hooksManager'}->trigger('afterMtaDelAutoRspnd', $data);

	$rs;
}

sub addAutoRspnd
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaAddAutoRspnd', $data);
	return $rs if $rs;

	my $mTrsptHshFile = $self->{'MTA_TRANSPORT_HASH'};
	my ($filename, $directories, $suffix) = fileparse($mTrsptHshFile);
	my $wrkFileName = "$self->{'wrkDir'}/$filename$suffix";
	my $wrkFile = iMSCP::File->new('filename' => $wrkFileName);
	my $wrkContent = $wrkFile->get();
	return 1 unless defined $wrkContent;

	my $trnsprt = "imscp-arpl.$data->{'DMN_NAME'}";
	$trnsprt =~ s/\./\\\./g;
	$wrkContent =~ s/^$trnsprt\t[^\n]*\n//gmi;
	$wrkContent .= "imscp-arpl.$data->{'DMN_NAME'}\timscp-arpl:\n";
	$wrkFile->set($wrkContent);
	return 1 if $wrkFile->save();

	$rs |= $wrkFile->mode(0644);
	$rs |= $wrkFile->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs |= $wrkFile->copyFile($mTrsptHshFile);

	$self->{'postmap'}->{$self->{'MTA_TRANSPORT_HASH'}} = $data->{'MAIL_ADDR'};

	$rs |= $self->{'hooksManager'}->trigger('afterMtaAddAutoRspnd', $data);

	$rs;
}

sub delMailForward
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaDelMailForward', $data);
	return $rs if $rs;

	my $mFWDHshFile	= $self->{'MTA_VIRTUAL_ALIAS_HASH'};
	my ($filename, $directories, $suffix) = fileparse($mFWDHshFile);
	my $wrkFileName = "$self->{'wrkDir'}/$filename$suffix";
	my $wrkFile = iMSCP::File->new('filename' => $wrkFileName);
	my $wrkContent = $wrkFile->get();
	return 1 unless defined $wrkContent;

	my $mailbox = $data->{'MAIL_ADDR'};
	$mailbox =~ s/\./\\\./g;
	$wrkContent =~ s/^$mailbox\t[^\n]*\n//gmi;

	# handle normal mail accounts entries for which auto-responder is active
	if($data->{'MAIL_STATUS'} ne'delete') {
		my @line;

		# if auto-responder is activated, we must add the recipient as address to keep local copy of any forwarded mail
		push(@line, $data->{'MAIL_ADDR'}) if $data->{'MAIL_AUTO_RSPND'} && $data->{'MAIL_TYPE'} =~ m/_mail/;

		# if auto-responder is activated, we need an address such as user@imscp-arpl.domain.tld
		push(@line, "$data->{'MAIL_ACC'}\@imscp-arpl.$data->{'DMN_NAME'}")
			if $data->{'MAIL_AUTO_RSPND'} && $data->{'MAIL_TYPE'} =~ m/_mail/;

		$wrkContent .= "$data->{'MAIL_ADDR'}\t" . join(',', @line) . "\n" if scalar @line;
	}

	$wrkFile->set($wrkContent);
	return 1 if $wrkFile->save();

	$rs |= $wrkFile->mode(0644);
	$rs |= $wrkFile->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs |= $wrkFile->copyFile($mFWDHshFile);

	$self->{'postmap'}->{$self->{'MTA_VIRTUAL_ALIAS_HASH'}} = $data->{'MAIL_ADDR'};

	$rs |= $self->{'hooksManager'}->trigger('afterMtaDelMailForward', $data);

	$rs;
}

sub addMailForward
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaAddMailForward', $data);
	return $rs if $rs;

	my $mFWDHshFile = $self->{'MTA_VIRTUAL_ALIAS_HASH'};
	my ($filename, $directories, $suffix) = fileparse($mFWDHshFile);
	my $wrkFileName = "$self->{'wrkDir'}/$filename$suffix";
	my $wrkFile = iMSCP::File->new('filename' => $wrkFileName);
	my $wrkContent = $wrkFile->get();
	return 1 unless defined $wrkContent;

	my $mailbox = $data->{'MAIL_ADDR'};
	$mailbox =~ s/\./\\\./g;
	$wrkContent =~ s/^$mailbox\t[^\n]*\n//gmi;

	my @line;

	# for a normal+foward mail account, we must add the recipient as address to keep local copy of any forwarded mail
	push(@line, $data->{'MAIL_ADDR'}) if $data->{'MAIL_TYPE'} =~ m/_mail/;

	# add address(s) to which mail will be forwarded
	push(@line, $data->{'MAIL_FORWARD'});

	# if the auto-responder is activated, we must add an address such as user@imscp-arpl.domain.tld
	push(@line, "$data->{'MAIL_ACC'}\@imscp-arpl.$data->{'DMN_NAME'}") if $data->{'MAIL_AUTO_RSPND'};

	$wrkContent .= "$data->{'MAIL_ADDR'}\t" . join(',', @line) . "\n" if scalar @line;

	$wrkFile->set($wrkContent);
	return 1 if $wrkFile->save();

	$rs |= $wrkFile->mode(0644);
	$rs |= $wrkFile->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs |= $wrkFile->copyFile($mFWDHshFile);

	$self->{'postmap'}->{$self->{'MTA_VIRTUAL_ALIAS_HASH'}} = $data->{'MAIL_ADDR'};

	$rs |= $self->{'hooksManager'}->trigger('afterMtaAddMailForward', $data);

	$rs;
}

sub delMailBox
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaDelMailbox', $data);
	return $rs if $rs;

	$rs |= $self->disableMailBox($data);

	return $rs if !$data->{'MAIL_ACC'}; # catchall?

	my $mailDir = "$self->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DMN_NAME'}/$data->{'MAIL_ACC'}";

	$rs |=	iMSCP::Dir->new('dirname' => $mailDir)->remove();

	$rs |= $self->{'hooksManager'}->trigger('afterMtaDelMailbox', $data);

	$rs;
}

sub disableMailBox
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaDisableMailbox', $data);
	return $rs if $rs;

	my $mBoxHashFile = $self->{'MTA_VIRTUAL_MAILBOX_HASH'};
	my ($filename, $directories, $suffix) = fileparse($mBoxHashFile);
	my $wrkFileName	= "$self->{'wrkDir'}/$filename$suffix";
	my $wrkFile = iMSCP::File->new('filename' => $wrkFileName);
	my $wrkContent = $wrkFile->get();
	return 1 unless defined $wrkContent;

	my $mailbox = $data->{'MAIL_ADDR'};
	$mailbox =~ s/\./\\\./g;
	$wrkContent =~ s/^$mailbox\t[^\n]*\n//gmi;
	$wrkFile->set($wrkContent);
	return 1 if $wrkFile->save();
	$rs |= $wrkFile->mode(0644);
	$rs |= $wrkFile->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs |= $wrkFile->copyFile($mBoxHashFile);

	$self->{'postmap'}->{$self->{'MTA_VIRTUAL_MAILBOX_HASH'}} = $data->{'MAIL_ADDR'};

	$rs |= $self->{'hooksManager'}->trigger('afterMtaDisableMailbox', $data);

	$rs;
}

sub addMailBox
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaAddMailbox', $data);
	return $rs if $rs;

	my $mBoxHashFile = $self->{'MTA_VIRTUAL_MAILBOX_HASH'};
	my ($filename, $directories, $suffix) = fileparse($mBoxHashFile);
	my $wrkFileName = "$self->{'wrkDir'}/$filename$suffix";
	my $wrkFile = iMSCP::File->new('filename' => $wrkFileName);
	my $wrkContent = $wrkFile->get();
	return 1 unless defined $wrkContent;

	my $mailbox = $data->{'MAIL_ADDR'};
	$mailbox =~ s/\./\\\./g;
	$wrkContent =~ s/^$mailbox\t[^\n]*\n//gmi;
	$wrkContent .= "$data->{'MAIL_ADDR'}\t$data->{'DMN_NAME'}/$data->{'MAIL_ACC'}/\n";
	$wrkFile->set($wrkContent);
	return 1 if $wrkFile->save();
	$rs |=	$wrkFile->mode(0644);
	$rs |=	$wrkFile->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs |= $wrkFile->copyFile($mBoxHashFile);

	$self->{'postmap'}->{$self->{'MTA_VIRTUAL_MAILBOX_HASH'}} = $data->{'MAIL_ADDR'};

	my $mailDir = "$self->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DMN_NAME'}/$data->{'MAIL_ACC'}";

	$rs |=	iMSCP::Dir->new('dirname' => $mailDir)->make(
		{ 'user' => $self->{'MTA_MAILBOX_UID_NAME'}, 'group' => $self->{'MTA_MAILBOX_GID_NAME'}, 'mode' => 0700 }
	);

	for ("$mailDir", "$mailDir/.Drafts", "$mailDir/.Sent", "$mailDir/.Junk", "$mailDir/.Trash") {
		# Creating bal directory
		if(! -d $_) {
			$rs |= iMSCP::Dir->new('dirname' => $_)->make(
				{ 'user' => $self->{'MTA_MAILBOX_UID_NAME'}, 'group' => $self->{'MTA_MAILBOX_GID_NAME'}, 'mode' => 0700 }
			);
		}

		# Creating cur directory
		$rs |= iMSCP::Dir->new('dirname' => "$_/cur")->make(
			{ 'user' => $self->{'MTA_MAILBOX_UID_NAME'}, group => $self->{'MTA_MAILBOX_GID_NAME'}, 'mode' => 0700 }
		);

		# Creating new directory
		$rs |= iMSCP::Dir->new('dirname' => "$_/new")->make(
			{ 'user' => $self->{'MTA_MAILBOX_UID_NAME'}, 'group' => $self->{'MTA_MAILBOX_GID_NAME'}, 'mode' => 0700 }
		);

		# Creating tmp directory
		$rs |= iMSCP::Dir->new(dirname => "$_/tmp")->make(
			{ 'user' => $self->{'MTA_MAILBOX_UID_NAME'}, 'group' => $self->{'MTA_MAILBOX_GID_NAME'}, 'mode' => 0700 }
		);
	}

	# Creating subscriptions file

	my $subscriptionsFile;
	my $subscriptionsFileContent;

	if($main::imscpConfig{'PO_SERVER'} eq 'dovecot'){
		$subscriptionsFile = "$mailDir/subscriptions";
		$subscriptionsFileContent = "Drafts\nSent\nJunk\nTrash\n";
	} else {
		$subscriptionsFile = "$mailDir/courierimapsubscribed";
		$subscriptionsFileContent = "INBOX.Drafts\nINBOX.Sent\nINBOX.Junk\nINBOX.Trash\n";
	}

	$subscriptionsFile = iMSCP::File->new('filename' => $subscriptionsFile);
	$subscriptionsFile->set($subscriptionsFileContent) and return 1;
	$subscriptionsFile->save() and return 1;

	$rs |= $subscriptionsFile->mode(0600);
	$rs |= $subscriptionsFile->owner($self->{'MTA_MAILBOX_UID_NAME'}, $self->{'MTA_MAILBOX_GID_NAME'});

	$rs |= $self->{'hooksManager'}->trigger('afterMtaAddMailbox', $data);

	$rs;
}

sub addCatchAll
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaAddCatchAll', $data);
	return $rs if $rs;

	my $mFWDHshFile = $self->{'MTA_VIRTUAL_ALIAS_HASH'};
	my ($filename, $directories, $suffix) = fileparse($mFWDHshFile);
	my $wrkFileName	= "$self->{'wrkDir'}/$filename$suffix";
	my $wrkFile = iMSCP::File->new('filename' => $wrkFileName);
	my $wrkContent = $wrkFile->get();
	return 1 unless defined $wrkContent;

	for(@{$data->{'MAIL_ON_CATCHALL'}}){
		my $mailbox = $_;
		$mailbox =~ s/\./\\\./g;
		$wrkContent =~ s/^$mailbox\t$mailbox\n//gmi;
		$wrkContent .= "$_\t$_\n";
	}

	if($data->{'MAIL_TYPE'} =~ m/_catchall/) {
		my $catchAll = "\@$data->{'DMN_NAME'}";
		$catchAll =~ s/\./\\\./g;
		$wrkContent =~ s/^$catchAll\t[^\n]*\n//gmi;
		$wrkContent .= "\@$data->{'DMN_NAME'}\t$data->{'MAIL_CATCHALL'}\n";
	}

	$wrkFile->set($wrkContent);
	return 1 if $wrkFile->save();

	$rs |= $wrkFile->mode(0644);
	$rs |= $wrkFile->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs |= $wrkFile->copyFile($mFWDHshFile);

	$self->{'postmap'}->{$self->{'MTA_VIRTUAL_ALIAS_HASH'}} = $data->{'MAIL_ADDR'};

	$rs |= $self->{'hooksManager'}->trigger('afterMtaAddCatchAll', $data);

	$rs;
}

sub delCatchAll
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaDelCatchAll', $data);
	return $rs if $rs;

	my $mFWDHshFile	= $self->{'MTA_VIRTUAL_ALIAS_HASH'};
	my ($filename, $directories, $suffix) = fileparse($mFWDHshFile);
	my $wrkFileName = "$self->{'wrkDir'}/$filename$suffix";
	my $wrkFile = iMSCP::File->new('filename' => $wrkFileName);
	my $wrkContent = $wrkFile->get();
	return 1 unless defined $wrkContent;

	for(@{$data->{'MAIL_ON_CATCHALL'}}){
		my $mailbox = $_;
		$mailbox =~ s/\./\\\./g;
		$wrkContent =~ s/^$mailbox\t$mailbox\n//gmi;
	}

	my $catchAll = "\@$data->{'DMN_NAME'}";
	$catchAll =~ s/\./\\\./g;
	$wrkContent =~ s/^$catchAll\t[^\n]*\n//gmi;
	$wrkFile->set($wrkContent);
	return 1 if $wrkFile->save();

	$rs |= $wrkFile->mode(0644);
	$rs |= $wrkFile->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs |= $wrkFile->copyFile($mFWDHshFile);

	$self->{'postmap'}->{$self->{'MTA_VIRTUAL_ALIAS_HASH'}} = $data->{'MAIL_ADDR'};

	$rs |= $self->{'hooksManager'}->trigger('afterMtaDelCatchAll', $data);

	$rs;
}

sub getTraffic
{
	my $self = shift;
	my $who = shift;
	my $dbName = "$self->{'wrkDir'}/log.db";
	my $logFile = "$main::imscpConfig{'TRAFF_LOG_DIR'}/mail.log";
	my $wrkLogFile = "$main::imscpConfig{'LOG_DIR'}/mail.smtp.log";
	my ($rv, $rs, $stdout, $stderr);

	$self->{'hooksManager'}->trigger('beforeMtaGetTraffic') and return 0;

	# Only if files was not aleady parsed this session
	unless($self->{'logDb'}){
		#use a small conf file to memorize last line readed and his content
		tie %{$self->{'logDb'}}, 'iMSCP::Config','fileName' => $dbName, noerrors => 1;
		##first use? we zero line and content
		$self->{'logDb'}->{'line'} = 0 unless $self->{'logDb'}->{'line'};
		$self->{'logDb'}->{'content'} = '' unless $self->{'logDb'}->{'content'};
		my $lastLineNo = $self->{'logDb'}->{'line'};
		my $lastLine = $self->{'logDb'}->{'content'};

		# copy log file
		$rs = iMSCP::File->new('filename' => $logFile)->copyFile($wrkLogFile) if -f $logFile;
		return 0 if $rs; # return 0 traffic if we fail

		# link log file to array
		tie my @content, 'Tie::File', $wrkLogFile or return 0;

		# save last line
		$self->{'logDb'}->{'line'} = $#content;
		$self->{'logDb'}->{'content'} = @content[$#content];

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
			if($main::imscpConfig{'MAIL_LOG_INC_AMAVIS'}){
				if($5 ne '?' &&  !($3 =~ /localhost|127.0.0.1/ && $4 =~ /localhost|127.0.0.1/)){
					$self->{'traff'}->{$1} += $5;
					$self->{'traff'}->{$2} += $5;
				}
			} else {
				if($5 ne '?' && $4 !~ /virtual/ && !($3 =~ /localhost|127.0.0.1/ && $4 =~ /localhost|127.0.0.1/)){
					$self->{'traff'}->{$1} += $5;
					$self->{'traff'}->{$2} += $5;
				}
			}
		}
	}

	$self->{'hooksManager'}->trigger('afterMtaGetTraffic') and return 0;

	$self->{'traff'}->{$who} ? $self->{'traff'}->{$who} : 0;
}

END
{
	my $endCode = $?;
	my $self = Servers::mta::postfix->new();
	my $wrkLogFile = "$main::imscpConfig{'LOG_DIR'}/mail.smtp.log";
	my $rs = 0;

	if($self->{'restart'} && $self->{'restart'} eq 'yes'){
		$rs = $self->restart();
	} else {
		$rs |= $self->postmap($_) for keys %{$self->{'postmap'}};
	}

	$rs |= iMSCP::File->new('filename' => $wrkLogFile)->delFile() if -f $wrkLogFile;

	$? = $endCode || $rs;
}

1;
