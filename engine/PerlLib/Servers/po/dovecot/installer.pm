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
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::po::dovecot::installer;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Execute;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{
	debug((caller(0))[3].': Starting...');

	my $self		= shift;
	$self->{cfgDir}	= "$main::imscpConfig{'CONF_DIR'}/dovecot";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";

	my $conf		= "$self->{cfgDir}/dovecot.data";
	my $oldConf		= "$self->{cfgDir}/dovecot.old.data";

	tie %self::dovecotConfig, 'iMSCP::Config','fileName' => $conf;
	tie %self::dovecotOldConfig, 'iMSCP::Config','fileName' => $oldConf if -f $oldConf;

	debug((caller(0))[3].': Ending...');
	0;
}

sub install{
	debug((caller(0))[3].': Starting...');

	my $self = shift;

	$self->getVersion() and return 1;

	# Saving all system configuration files if they exists
	for ((
		'dovecot.conf',
		'dovecot-sql.conf'
	)) {
		$self->bkpConfFile($_) and return 1;
	}

	$self->setupDB() and return 1;
	$self->buildConf() and return 1;
	$self->saveConf() and return 1;
	$self->oldEngineCompatibility() and return 1;

	$self->migrateMailboxes() and return 1;

	debug((caller(0))[3].': Ending...');
	0;
}

sub migrateMailboxes{
	debug((caller(0))[3].': Starting...');

	if(
		$main::imscpConfigOld{PO_SERVER} eq 'courier'
		&&
		$main::imscpConfig{PO_SERVER}  eq 'dovecot'
	){
		use iMSCP::Execute;
		use FindBin;
		use Servers::mta;

		my $mta	= Servers::mta->factory($main::imscpConfig{MTA_SERVER});
		my ($rs, $stdout, $stderr);
		my $binPath = "$FindBin::Bin/../PerlVendor/courier-dovecot-migrate.pl";
		my $mailPath = "$mta->{'MTA_VIRTUAL_MAIL_DIR'}";

		$rs = execute("$binPath --to-dovecot --convert --recursive $mailPath", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout...") if $stdout;
		error((caller(0))[3].": $stderr") if $stderr;
		error((caller(0))[3].": Error while converting mails") if !$stderr && $rs;
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub oldEngineCompatibility{
	debug((caller(0))[3].': Starting...');

	$main::imscpConfig{CMD_MAKEUSERDB}	= '/bin/true';
	$main::imscpConfig{CMD_AUTHD}		= '/bin/true';
	$main::imscpConfig{CMD_IMAP}		= '/bin/true';
	$main::imscpConfig{CMD_IMAP_SSL}	= '/bin/true';
	$main::imscpConfig{CMD_POP}			= '/bin/true';
	$main::imscpConfig{CMD_POP_SSL}		= '/bin/true';

	use iMSCP::Dir;
	iMSCP::Dir->new(dirname => $main::imscpConfig{AUTHLIB_CONF_DIR})->make() and return 1;

	use iMSCP::File;
	my $file = iMSCP::File->new(filename => "$main::imscpConfig{'CONF_DIR'}/courier/userdb");
	$file->copyFile("$main::imscpConfig{AUTHLIB_CONF_DIR}/userdb") and return 1;
	$file->copyFile("$main::imscpConfig{'CONF_DIR'}/courier/working") and return 1;

	debug((caller(0))[3].': Ending...');
	0;
}

sub getVersion{
	debug((caller(0))[3].': Starting...');

	my $self = shift;
	my ($rs, $stdout, $stderr);

	$rs = execute('dovecot --version', \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout") if $stdout;
	error((caller(0))[3].": $stderr") if $stderr;
	error((caller(0))[3].": Can't read dovecot version") if !$stderr and $rs;
	return $rs if $rs;

	chomp($stdout);
	$self->{version} = $stdout;

	debug((caller(0))[3].': Ending...');
	0;
}

sub saveConf{

	debug((caller(0))[3].': Starting...');

	use iMSCP::File;

	my $self		= shift;
	my $file = iMSCP::File->new(filename => "$self->{cfgDir}/dovecot.data");
	my $cfg = $file->get() or return 1;

	$file = iMSCP::File->new(filename => "$self->{cfgDir}/dovecot.old.data");
	$file->set($cfg) and return 1;
	$file->save and return 1;
	$file->mode(0640) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	debug((caller(0))[3].': Ending...');

	0;
}


sub bkpConfFile{
	debug((caller(0))[3].': Starting...');

	my $self		= shift;
	my $cfgFile		= shift;
	my $timestamp	= time;

	if(-f "$self::dovecotConfig{'DOVECOT_CONF_DIR'}/$cfgFile"){
		my $file	= iMSCP::File->new(
						filename => "$self::dovecotConfig{'DOVECOT_CONF_DIR'}/$cfgFile"
					);
		if(!-f "$self->{bkpDir}/$cfgFile.system") {
			$file->copyFile("$self->{bkpDir}/$cfgFile.system") and return 1;
		} else {
			$file->copyFile("$self->{bkpDir}/$cfgFile.$timestamp") and return 1;
		}
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub buildConf{
	debug((caller(0))[3].': Starting...');

	use Servers::mta;

	my $self		= shift;
	my $mta	= Servers::mta->factory($main::imscpConfig{MTA_SERVER});

	my $cfg = {
		DATABASE_TYPE		=> $main::imscpConfig{DATABASE_TYPE},
		DATABASE_HOST		=> (
									$main::imscpConfig{DATABASE_PORT}
									?
									"$main::imscpConfig{DATABASE_HOST} port=$main::imscpConfig{DATABASE_PORT}"
									:
									$main::imscpConfig{DATABASE_HOST}
								),
		DATABASE_USER		=> $self::dovecotConfig{DATABASE_USER},
		DATABASE_PASSWORD	=> $self::dovecotConfig{DATABASE_PASSWORD},
		DATABASE_NAME		=> $main::imscpConfig{DATABASE_NAME},
		GUI_CERT_DIR		=> $main::imscpConfig{GUI_CERT_DIR},
		HOST_NAME			=> $main::imscpConfig{SERVER_HOSTNAME},
		DOVECOT_SSL			=> $main::imscpConfig{SSL_ENABLED} ? '' : '#',
		MAIL_USER			=> $mta->{'MTA_MAILBOX_UID_NAME'},
		MAIL_GROUP			=> $mta->{'MTA_MAILBOX_GID_NAME'},
		vmailUID			=> scalar getpwnam($mta->{'MTA_MAILBOX_UID_NAME'}),
		mailGID				=> scalar getgrnam($mta->{'MTA_MAILBOX_GID_NAME'}),
		DOVECOT_CONF_DIR	=> $self::dovecotConfig{DOVECOT_CONF_DIR}
	};

	use version;
	my $cfgFiles = {
		'dovecot.conf'		=>(
								version->new($self->{version}) < version->new('2.0.0')
								?
								'dovecot.conf.1'
								:
								'dovecot.conf.2'
		),
		'dovecot-sql.conf'	=> 'dovecot-sql.conf',
		'dovecot-dict-sql.conf'	=> 'dovecot-dict-sql.conf'
	};

	for (keys %{$cfgFiles}) {
		my $file	= iMSCP::File->new(filename => "$self->{cfgDir}/$cfgFiles->{$_}");
		my $cfgTpl	= $file->get();
		return 1 if (!$cfgTpl);
		$cfgTpl = iMSCP::Templator::process($cfg, $cfgTpl);
		return 1 if (!$cfgTpl);
		$file = iMSCP::File->new(filename => "$self->{wrkDir}/$_");
		$file->set($cfgTpl) and return 1;
		$file->save() and return 1;
		$file->mode(0640) and return 1;
		$file->owner($main::imscpConfig{'ROOT_USER'}, $mta->{'MTA_MAILBOX_GID_NAME'}) and return 1;
		$file->copyFile($self::dovecotConfig{'DOVECOT_CONF_DIR'}) and return 1;
	}

	my $file	= iMSCP::File->new(filename => "$self::dovecotConfig{'DOVECOT_CONF_DIR'}/dovecot.conf");
	$file->mode(0644) and return 1;


	debug((caller(0))[3].': Ending...');
	0;
}

sub setupDB{
	debug((caller(0))[3].': Starting...');

	my $self		= shift;
	my $connData;

	if(!$self->check_sql_connection
		(
			$self::dovecotConfig{'DATABASE_USER'} || '',
			$self::dovecotConfig{'DATABASE_PASSWORD'} || ''
		)
	){
		$connData = 'yes';
	}elsif($self::dovecotOldConfig{'DATABASE_USER'} && !$self->check_sql_connection
		(
			$self::dovecotOldConfig{'DATABASE_USER'} || '',
			$self::dovecotOldConfig{'DATABASE_PASSWORD'} || ''
		)
	){
		$self::dovecotConfig{'DATABASE_USER'}		= $self::dovecotOldConfig{'DATABASE_USER'};
		$self::dovecotConfig{'DATABASE_PASSWORD'}	= $self::dovecotOldConfig{'DATABASE_PASSWORD'};
		$connData = 'yes';
	} else {
		my $dbUser = 'dovecot_user';
		do{
			$dbUser = iMSCP::Dialog->factory()->inputbox("Please enter database user name (default dovecot_user)", $dbUser);
		} while (!$dbUser);

		iMSCP::Dialog->factory()->set('cancel-label','Autogenerate');
		my $dbPass;
		$dbPass = iMSCP::Dialog->factory()->inputbox("Please enter database password (leave blank for autogenerate)", $dbPass);
		if(!$dbPass){
			$dbPass = iMSCP::Crypt::randomString(8);
		}
		$dbPass =~ s/('|"|`|#|;|\s)/_/g;
		iMSCP::Dialog->factory()->msgbox("Your password is '".$dbPass."' (we have stripped not allowed chars)");
		iMSCP::Dialog->factory()->set('cancel-label');
		$self::dovecotConfig{'DATABASE_USER'}		= $dbUser;
		$self::dovecotConfig{'DATABASE_PASSWORD'}	= $dbPass;
	}

	#restore db connection
	my $crypt = iMSCP::Crypt->new();
	my $err = $self->check_sql_connection(
			$main::imscpConfig{'DATABASE_USER'},
			$main::imscpConfig{'DATABASE_PASSWORD'} ? $crypt->decrypt_db_password($main::imscpConfig{'DATABASE_PASSWORD'}) : ''
	);
	if ($err){
		error((caller(0))[3].": $err");
		return 1;
	}

	if(!$connData) {
		my $database = iMSCP::Database->new(db => $main::imscpConfig{DATABASE_TYPE})->factory();

		## We ensure that new data doesn't exist in database
		$err = $database->doQuery(
			'dummy',
			"
				DELETE FROM
					`mysql`.`tables_priv`
				WHERE
					`Host` = ?
				AND
					`Db` = ?
				AND
					`User` = ?;
			", $main::imscpConfig{'DATABASE_HOST'}, $main::imscpConfig{'DATABASE_NAME'}, $self::dovecotConfig{'DATABASE_USER'}
		);
		return $err if (ref $err ne 'HASH');

		$err = $database->doQuery(
			'dummy',
			"
				DELETE FROM
					`mysql`.`user`
				WHERE
					`Host` = ?
				AND
					`User` = ?;
			", $main::imscpConfig{'DATABASE_HOST'}, $self::dovecotConfig{'DATABASE_USER'}
		);
		return $err if (ref $err ne 'HASH');


		$err = $database->doQuery('dummy', 'FLUSH PRIVILEGES');
		return $err if (ref $err ne 'HASH');

		## Inserting new data into the database
		$err = $database->doQuery(
			'dummy',
			"
				GRANT SELECT ON `$main::imscpConfig{DATABASE_NAME}`.*
				TO ?@?
				IDENTIFIED BY ?;
			", $self::dovecotConfig{DATABASE_USER}, $main::imscpConfig{DATABASE_HOST}, $self::dovecotConfig{DATABASE_PASSWORD}
		);
		return $err if (ref $err ne 'HASH');

		$err = $database->doQuery(
			'dummy',
			"
				GRANT SELECT,INSERT,UPDATE,DELETE ON `$main::imscpConfig{DATABASE_NAME}`.`quota_dovecot`
				TO ?@?
			", $self::dovecotConfig{DATABASE_USER}, $main::imscpConfig{DATABASE_HOST}
		);
		return $err if (ref $err ne 'HASH');
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub check_sql_connection{

	debug((caller(0))[3].': Starting...');

	use iMSCP::Database;

	my ($self, $dbUser, $dbPass) = (@_);
	my $database = iMSCP::Database->new(db => $main::imscpConfig{DATABASE_TYPE})->factory();
	$database->set('DATABASE_USER',		$dbUser);
	$database->set('DATABASE_PASSWORD',	$dbPass);

	debug((caller(0))[3].': Ending...');
	return $database->connect();
}

sub registerHooks{
	debug((caller(0))[3].': Starting...');
	my $self = shift;

	use Servers::mta;

	my $mta = Servers::mta->factory($main::imscpConfig{MTA_SERVER});

	$mta->registerPostHook('buildConf', sub { return $self->mtaConf(@_); } );

	debug((caller(0))[3].': Ending...');
	0;
}

sub mtaConf{
	debug((caller(0))[3].': Starting...');
	my $self	= shift;
	my $content	= shift || '';

	use iMSCP::Templator;
	use Servers::mta;

	my $mta	= Servers::mta->factory($main::imscpConfig{MTA_SERVER});

	my $poBloc = getBloc(
		"$mta->{commentChar} dovecot begin",
		"$mta->{commentChar} dovecot end",
		$content
	);

	$content = replaceBloc(
		"$mta->{commentChar} po setup begin",
		"$mta->{commentChar} po setup end",
		$poBloc,
		$content,
		undef
	);

	debug((caller(0))[3].': Ending...');
	$content;
}
1;
