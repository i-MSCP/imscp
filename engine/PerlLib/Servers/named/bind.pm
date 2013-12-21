#!/usr/bin/perl

=head1 NAME

 Servers::named::bind - i-MSCP Bind9 Server implementation

=cut

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
# @category    i-MSCP
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::named::bind;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Templator;
use iMSCP::IP;
use File::Basename;
use iMSCP::Config;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Bind9 Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks($hooksManager)

 Register setup hooks.

 Param iMSCP::HooksManager $hooksManager Hooks manager instance
 Return int 0 on success, other on failure

=cut

sub registerSetupHooks($$)
{
	my $self = shift;
	my $hooksManager = shift;

	require Servers::named::bind::installer;
	Servers::named::bind::installer->getInstance()->registerSetupHooks($hooksManager);
}

=item install()

 Process install tasks.

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	require Servers::named::bind::installer;
	Servers::named::bind::installer->getInstance()->install();
}

=item postinstall()

 Process postinstall tasks.

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedPostInstall');
	return $rs if $rs;

	$self->{'restart'} = 'yes';

	$self->{'hooksManager'}->trigger('afterNamedPostInstall');
}

=item uninstall()

 Process uninstall tasks.

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedUninstall', 'bind');
	return $rs if $rs;

	require Servers::named::bind::uninstaller;
	$rs = Servers::named::bind::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$rs = $self->restart();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterNamedUninstall', 'bind');
}

=item restart()

 Restart Bind9.

 Return int 0, other on failure

=cut

sub restart
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedRestart');
	return $rs if $rs;

	my ($stdout, $stderr);
	$rs = execute("$main::imscpConfig{'SERVICE_MNGR'} $self->{'config'}->{'NAMED_SNAME'} restart", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs > 1;
	return $rs if $rs > 1;

	$self->{'hooksManager'}->trigger('afterNamedRestart');
}

=item addDmn(\%data)

 Process addDmn tasks.

 Param hash_ref $data Reference to a hash containing data as provided by modules
 Return int 0 on success, other on failure

=cut

sub addDmn($$)
{
	my $self = shift;
	my $data = shift;

	$data = {} if ref $data ne 'HASH';

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedAddDmn', $data);
	return $rs if $rs;

	$rs = $self->_addDmnConfig($data);
	return $rs if $rs;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		$rs = $self->_addDmnDb($data);
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterNamedAddDmn', $data);
}

=item postaddDmn(\%data)

 Process postaddDmn tasks.

 Param hash_ref $data Reference to a hash containing data as provided by modules
 Return int 0 on success, other on failure

=cut

sub postaddDmn($$)
{
	my $self = shift;
	my $data = shift;

	$data = {} if ref $data ne 'HASH';

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedPostAddDmn', $data);
	return $rs if $rs;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		my $ipH = iMSCP::IP->new();

		# Add DNS entry for domain alternative URL in master zone file
		$rs = $self->addDmn(
			{
				DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
				DOMAIN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
				MX => '',
				DMN_ADD => {
					MANUAL_DNS_NAME => "$data->{'USER_NAME'}.$main::imscpConfig{'BASE_SERVER_VHOST'}.",
					MANUAL_DNS_CLASS => 'IN',
					MANUAL_DNS_TYPE => (lc($ipH->getIpType($data->{'DOMAIN_IP'})) eq 'ipv4' ? 'A' : 'AAAA'),
					MANUAL_DNS_DATA => $data->{'DOMAIN_IP'}
				}
			}
		);
		return $rs if $rs;
	}

	$rs = $self->{'hooksManager'}->trigger('afterNamedPostAddDmn', $data);
	return $rs if $rs;

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	0;
}

=item deleteDmn(\%data)

 Process deleteDmn tasks.

 Param hash_ref $data Reference to a hash containing data as provided by  modules
 Return int 0 on success, other on failure

=cut

sub deleteDmn($$)
{
	my $self = shift;
	my $data = shift;

	$data = {} if ref $data ne 'HASH';

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedDelDmn', $data);
	return $rs if $rs;

	# Removing zone from named configuration file
	$rs = $self->deleteDmnConfig($data);
	return $rs if $rs;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		# Removing working zone file
		$rs = iMSCP::File->new(
			'filename' => "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db"
		)->delFile() if -f "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db";
		return $rs if $rs;

		# Removing production zone file
		$rs = iMSCP::File->new(
			'filename' => "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db"
		)->delFile() if -f "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db";
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterNamedDelDmn', $data);
}

=item postdeleteDmn(\%data)

 Process postdeleteDmn tasks.

 Param hash_ref $data Reference to a hash containing data as provided by  modules
 Return int 0 on success, other on failure

=cut

sub postdeleteDmn($$)
{
	my $self = shift;
	my $data = shift;

	$data = {} if ref $data ne 'HASH';

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedPostDelDmn', $data);
	return $rs if $rs;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		# Removing DNS entry for domain alternative URL in master zone file
		$rs = $self->addDmn(
			{
				DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
				DOMAIN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
				MX => '',
				DMN_DEL => { MANUAL_DNS_NAME => "$data->{'USER_NAME'}.$main::imscpConfig{'BASE_SERVER_VHOST'}." }
			}
		);
		return $rs if $rs;
	}

	$rs = $self->{'hooksManager'}->trigger('afterNamedPostDelDmn', $data);
	return $rs if $rs;

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	0;
}

=item addSub(\%data)

 Process addSub tasks.

 Param hash_ref $data Reference to a hash containing data as provided by  modules
 Return int 0 on success, other on failure

=cut

sub addSub($$)
{
	my $self = shift;
	my $data = shift;

	$data = {} if ref $data ne 'HASH';

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedAddSub', $data);
	return $rs if $rs;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		my $zoneFile = "$self->{'wrkDir'}/$data->{'PARENT_DOMAIN_NAME'}.db";

		# Saving current wokring file if it exists
		$rs = iMSCP::File->new(
			'filename' => $zoneFile
		)->copyFile(
			"$self->{'bkpDir'}/$data->{'PARENT_DOMAIN_NAME'}.db." . time
		) if -f $zoneFile;
		return $rs if $rs;

		# Loading current working db file
		my $wrkFileContent = iMSCP::File->new('filename' => $zoneFile)->get();
		unless(defined $wrkFileContent){
			error("Unable to read $zoneFile");
			return 1;
		}

		$wrkFileContent = $self->_incTimeStamp($wrkFileContent, $data->{'PARENT_DOMAIN_NAME'});

		unless(defined $wrkFileContent) {
			error("Unable to update DNS timestamp for $data->{'PARENT_DOMAIN_NAME'}");
			return 1;
		}

		# SUBDOMAIN SECTION START

		my $cleanBTag = iMSCP::File->new('filename' => "$self->{'tplDir'}/db_sub_entry_b.tpl")->get();
		my $cleanTag = iMSCP::File->new('filename' => "$self->{'tplDir'}/db_sub_entry.tpl")->get();
		my $cleanETag = iMSCP::File->new('filename' => "$self->{'tplDir'}/db_sub_entry_e.tpl")->get();
		unless(defined $cleanBTag && defined $cleanTag && defined $cleanETag) {
			error('A template has not been found');
			return 1;
		}

		# SUBDOMAIN MX SECTION START

		my $bTag = "; sub MX entry BEGIN\n";
		my $eTag = "; sub MX entry END\n";
		my $mxBlock;

		if($data->{'MX'}) {
			my $cleanMXBlock = getBloc($bTag, $eTag, $cleanTag);

			$mxBlock .= process(
				{ MAIL_SERVER => $data->{'MX'}->{$_}->{'domain_text'} }, $cleanMXBlock
			) for keys %{$data->{'MX'}};

			$cleanTag = replaceBloc($bTag, $eTag, $mxBlock, $cleanTag);
		} else {
			$cleanTag = replaceBloc($bTag, $eTag, '', $cleanTag);
		}

		# SUBDOMAIN MX SECTION END

		$bTag = process({SUB_NAME => $data->{'DOMAIN_NAME'}}, $cleanBTag);
		$eTag = process({SUB_NAME => $data->{'DOMAIN_NAME'}}, $cleanETag);

		my $tag = process(
			{
				SUB_NAME => $data->{'DOMAIN_NAME'},
				DOMAIN_IP => $data->{'DOMAIN_IP'},
				PARENT_DOMAIN_NAME => $data->{'PARENT_DOMAIN_NAME'}
			},
			$cleanTag
		);

		$wrkFileContent = replaceBloc($bTag, $eTag, '', $wrkFileContent);
		$wrkFileContent = replaceBloc($cleanBTag, $cleanETag, "$bTag$tag$eTag", $wrkFileContent, 'preserve');

		# SUBDOMAIN SECTION END

		# Storing new file in working directory
		my $file = iMSCP::File->new('filename' => $zoneFile);

		$rs = $file->set($wrkFileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0640);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
		return $rs if $rs;

		# Installing new file in production directory (also cleanup file and perform entries checks)
		my ($stdout, $stderr);
		$rs = execute(
			"$self->{'config'}->{'CMD_NAMED_COMPILEZONE'} -i none -s relative " .
			"-o $self->{'config'}->{'BIND_DB_DIR'}/$data->{'PARENT_DOMAIN_NAME'}.db $data->{'PARENT_DOMAIN_NAME'} $zoneFile",
			\$stdout, \$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		error("Unable to install zone file $data->{'PARENT_DOMAIN_NAME'}.db") if $rs && ! $stderr;
		return $rs if $rs;

		$file = iMSCP::File->new('filename' => "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'PARENT_DOMAIN_NAME'}.db");

		$rs = $file->mode(0640);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterNamedAddSub', $data);
}

=item postaddSub(\%data)

 Process postaddSub tasks.

 Param hash_ref $data Reference to a hash containing data as provided by  modules
 Return int 0 on success, other on failure

=cut

sub postaddSub($$)
{
	my $self = shift;
	my $data = shift;

	$data = {} if ref $data ne 'HASH';

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedPostAddSub', $data);
	return $rs if $rs;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		my $ipH = iMSCP::IP->new();

		# Adding DNS entry for subdomain alternative URL in master zone file
		$rs = $self->addDmn(
			{
				DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
				DOMAIN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
				MX => '',
				DMN_ADD => {
					MANUAL_DNS_NAME => "$data->{'USER_NAME'}.$main::imscpConfig{'BASE_SERVER_VHOST'}.",
					MANUAL_DNS_CLASS => 'IN',
					MANUAL_DNS_TYPE => (lc($ipH->getIpType($data->{'DOMAIN_IP'})) eq 'ipv4' ? 'A' : 'AAAA'),
					MANUAL_DNS_DATA => $data->{'DOMAIN_IP'}
				}
			}
		);
		return $rs if $rs;
	}

	$rs = $self->{'hooksManager'}->trigger('afterNamedPostAddSub', $data);
	return $rs if $rs;

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	0;
}

=item deleteSub(\%data)

 Process deleteSub tasks.

 Param hash_ref $data Reference to a hash containing data as provided by  modules
 Return int 0 on success, other on failure

=cut

sub deleteSub($$)
{
	my $self = shift;
	my $data = shift;

	$data = {} if ref $data ne 'HASH';

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedDelSub', $data);
	return $rs if $rs;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		my $zoneFile = "$self->{'wrkDir'}/$data->{'PARENT_DOMAIN_NAME'}.db";

		# Saving working file if it exists
		$rs =iMSCP::File->new(
			'filename' => $zoneFile
		)->copyFile(
			"$self->{'bkpDir'}/$data->{'PARENT_DOMAIN_NAME'}.db." . time
		) if -f $zoneFile;
		return $rs if $rs;

		# Loading current working db file
		my $wrkFileContent = iMSCP::File->new('filename' => $zoneFile)->get();

		unless(defined $wrkFileContent) {
			error("Unable to read $zoneFile");
			return 1;
		}

		$wrkFileContent = $self->_incTimeStamp($wrkFileContent, $data->{'PARENT_DOMAIN_NAME'});

		unless(defined $wrkFileContent) {
			error("Unable to update timestamp for $data->{'PARENT_DOMAIN_NAME'}");
			return 1;
		}

		# SUBDOMAIN SECTION START

		my $cleanBTag = iMSCP::File->new('filename' => "$self->{'tplDir'}/db_sub_entry_b.tpl")->get();
		my $cleanETag = iMSCP::File->new('filename' => "$self->{'tplDir'}/db_sub_entry_e.tpl")->get();
		unless(defined $cleanBTag && defined $cleanETag) {
			error('A template has not been found');
			return 1;
		}

		my $bTag = process({ SUB_NAME => $data->{'DOMAIN_NAME'} }, $cleanBTag);
		my $eTag = process({ SUB_NAME => $data->{'DOMAIN_NAME'} }, $cleanETag);
		$wrkFileContent = replaceBloc($bTag, $eTag, '', $wrkFileContent);

		# SUBDOMAIN SECTION END

		# Storing new file in working directory
		my $file = iMSCP::File->new('filename' => $zoneFile);

		$rs = $file->set($wrkFileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0640);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
		return $rs if $rs;

		# Installing new file in production directory
		$rs = $file->copyFile($self->{'config'}->{'BIND_DB_DIR'});
		return $rs if $rs;

		# Installing new file in production directory (also cleanup file and perform entries checks)
		my ($stdout, $stderr);
		$rs = execute(
			"$self->{'config'}->{'CMD_NAMED_COMPILEZONE'} -i none -s relative " .
			"-o $self->{'config'}->{'BIND_DB_DIR'}/$data->{'PARENT_DOMAIN_NAME'}.db $data->{'PARENT_DOMAIN_NAME'} $zoneFile",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		error("Unable to install zone file $data->{'PARENT_DOMAIN_NAME'}.db") if $rs && ! $stderr;
		return $rs if $rs;

		$file = iMSCP::File->new('filename' => "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'PARENT_DOMAIN_NAME'}.db");

		$rs = $file->mode(0640);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterNamedDelSub', $data);
}

=item postdeleteSub(\%data)

 Process postdeleteSub tasks.

 Param hash_ref $data Reference to a hash containing data as provided by  modules
 Return int 0 on success, other on failure

=cut

sub postdeleteSub($$)
{
	my $self = shift;
	my $data = shift;

	$data = {} if ref $data ne 'HASH';

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedPostDelSub', $data);
	return $rs if $rs;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		# Removing DNS entry for subdomain alternative URL in master zone file
		$rs = $self->addDmn(
			{
				DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
				DOMAIN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
				MX => '',
				DMN_DEL => { MANUAL_DNS_NAME => "$data->{'USER_NAME'}.$main::imscpConfig{'BASE_SERVER_VHOST'}." }
			}
		);
		return $rs if $rs;
	}

	$rs = $self->{'hooksManager'}->trigger('afterNamedPostDelSub', $data);
	return $rs if $rs;

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance.

 Return Servers::named::bind

=cut

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforeNamedInit', $self, 'bind'
	) and fatal('bind - beforeNamedInit hook has failed');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/bind";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'tplDir'}	= "$self->{'cfgDir'}/parts";

	tie %{$self->{'config'}}, 'iMSCP::Config', 'fileName' => "$self->{'cfgDir'}/bind.data";

	$self->{'hooksManager'}->trigger(
		'afterNamedInit', $self, 'bind'
	) and fatal('bind - afterNamedInit hook has failed');

	$self;
}

=item _addDmnConfig(\%data)

 Add domain DNS configuration.

 Param hash_ref $data Reference to a hash containing data as provided by  modules
 Return int 0 on success, other on failure

=cut

sub _addDmnConfig($$)
{
	my $self = shift;
	my $data = shift;

	$data = {} if ref $data ne 'HASH';

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedAddDmnConfig', $data);
	return $rs if $rs;

	my ($file, $cfg);

	my ($confFileName, $confFileDirectory) = fileparse(
		$self->{'config'}->{'BIND_LOCAL_CONF_FILE'} || $self->{'config'}->{'BIND_CONF_FILE'}
	);

	# Backup config file

	if(-f "$self->{'wrkDir'}/$confFileName") {
		$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$confFileName");
		$rs = $file->copyFile("$self->{'bkpDir'}/$confFileName." . time);
		return $rs if $rs;
	} else {
		error("$self->{'wrkDir'}/$confFileName not found. Run the setup script to fix this error.");
		return 1;
	}

	# Building configuration file

	# Loading needed templates from /etc/imscp/bind/parts
	my $entry_b = iMSCP::File->new('filename' => "$self->{'tplDir'}/cfg_entry_b.tpl")->get();
	my $entry_e = iMSCP::File->new('filename' => "$self->{'tplDir'}/cfg_entry_e.tpl")->get();
	my $entry = iMSCP::File->new('filename' => "$self->{'tplDir'}/cfg_entry_$self->{'config'}->{'BIND_MODE'}.tpl")->get();
	unless(defined $entry_b && defined $entry_e && defined $entry) {
		error('A template has not been found');
		return 1
	}

	# Tags preparation
	my $tags_hash = { DB_DIR => $self->{'config'}->{'BIND_DB_DIR'} };

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		if($self->{'config'}->{'SECONDARY_DNS'} ne 'no') {
			$tags_hash->{'SECONDARY_DNS'} = join( '; ', split(';', $self->{'config'}->{'SECONDARY_DNS'})) . '; localhost;';
		} else {
			$tags_hash->{'SECONDARY_DNS'} = 'localhost;';
		}
	} else {
		$tags_hash->{'PRIMARY_DNS'} = join( '; ', split(';', $self->{'config'}->{'PRIMARY_DNS'})) . ';';
	}

	$tags_hash->{'DOMAIN_NAME'} = $data->{'DOMAIN_NAME'};

	my $entry_b_val = process($tags_hash, $entry_b);
	my $entry_e_val = process($tags_hash, $entry_e);
	my $entry_val = process($tags_hash, $entry);

	# Loading working file
	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$confFileName");
	$cfg = $file->get();
	unless(defined $cfg) {
		error("Unable to read $self->{'wrkDir'}/$confFileName");
		return 1;
	}

	# Building new entries

	my $entry_repl = "$entry_b_val$entry_val$entry_e_val$entry_b$entry_e";

	# Deleting old entries exist
	$cfg = replaceBloc($entry_b_val, $entry_e_val, '', $cfg);

	# Adding new entries
	$cfg = replaceBloc($entry_b, $entry_e, $entry_repl, $cfg);

	# Storing new file in the working directory
	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$confFileName");

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
	return $rs if $rs;

	# Installing new file in production directory
	$rs = $file->copyFile("$confFileDirectory$confFileName");
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterNamedAddDmnConfig', $data);
}

=item deleteDmnConfig(\%data)

 Delete domain DNS configuration.

 Param hash_ref $data Reference to a hash containing data as provided by  modules
 Return int 0 on success, other on failure

=cut

sub deleteDmnConfig($$)
{
	my $self = shift;
	my $data = shift;

	$data = {} if ref $data ne 'HASH';

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedDelDmnConfig', $data);
	return $rs if $rs;

	my ($confFileName, $confFileDirectory) = fileparse(
		$self->{'config'}->{'BIND_LOCAL_CONF_FILE'} || $self->{'config'}->{'BIND_CONF_FILE'}
	);

	my ($file, $cfg);

	# Backup config file
	if(-f "$self->{'wrkDir'}/$confFileName") {
		$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$confFileName");
		$rs = $file->copyFile("$self->{'bkpDir'}/$confFileName." . time);
		return $rs if $rs;
	} else {
		error("Unable to find the the $self->{'wrkDir'}/$confFileName file. Run setup again to fix this");
		return 1;
	}

	# Loading needed templates from /etc/imscp/bind/parts
	my ($bTag, $eTag);
	$bTag = iMSCP::File->new('filename' => "$self->{'tplDir'}/cfg_entry_b.tpl")->get();
	$eTag = iMSCP::File->new('filename' => "$self->{'tplDir'}/cfg_entry_e.tpl")->get();
	unless(defined $bTag && defined $eTag) {
		error('A template has not been found');
		return 1;
	}

	# Preparing tags
	my $tags_hash = { DOMAIN_NAME => $data->{'DOMAIN_NAME'} };

	$bTag = process($tags_hash, $bTag);
	$eTag = process($tags_hash, $eTag);

	# Loading working file
	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$confFileName");

	$cfg = $file->get();
	unless(defined $cfg) {
		error("Unable to read $self->{'wrkDir'}/$confFileName");
		return 1;
	}

	# Deleting entry
	$cfg = replaceBloc($bTag, $eTag, '', $cfg);

	# Storing new file in the working directory
	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$confFileName");

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
	return $rs if $rs;

	# Installing new file in production directory
	$rs = $file->copyFile("$confFileDirectory$confFileName");
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterNamedDelDmnConfig', $data);
}

=item _addDmnDb(\%data)

 Add domain DNS zone file.

 Param hash_ref $data Reference to a hash containing data as provided by  modules
 Return int 0 on success, other on failure

=cut

sub _addDmnDb($$)
{
	my $self = shift;
	my $data = shift;

	$data = {} if ref $data ne 'HASH';

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedAddDmnDb', $data);
	return $rs if $rs;

	my $zoneFile = "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db";

	# Saving current working file if it exists
	if(-f $zoneFile) {
		iMSCP::File->new(
			'filename' => $zoneFile
		)->copyFile(
			"$self->{'bkpDir'}/$data->{'DOMAIN_NAME'}.db." . time
		);
		return $rs if $rs;
	}

	# Loading current working db file
	my $wrkFileContent = iMSCP::File->new('filename' => $zoneFile)->get() if -f $zoneFile;

	# Building new configuration file

	# Loading needed template from /etc/imscp/bind/parts
	my $entries = iMSCP::File->new('filename' => "$self->{'tplDir'}/db_e.tpl")->get();
	unless(defined $entries) {
		error("Unable to read $self->{'tplDir'}/db_e.tpl");
		return 1;
	}

	# NS SECTION START

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
	my @ips = $self->{'config'}->{'SECONDARY_DNS'} eq 'no' ? () : split(';', $self->{'config'}->{'SECONDARY_DNS'});

	my $ipH = iMSCP::IP->new();

	for($data->{'DOMAIN_IP'}, @ips) {
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

	$entries = replaceBloc($A_Sec_b, $A_Sec_e, join("\n", @nsASection), $entries);
	$entries = replaceBloc($Decl_b, $Decl_e, join("\n", @nsDeclSection), $entries);

	# NS SECTION END

	# TIMESTAMP SECTION START

	my $domainIpType = $ipH->getIpType($data->{'DOMAIN_IP'}) eq 'ipv4' ? 'ip4' : 'ip6';
	my $serverIpType = $ipH->getIpType($main::imscpConfig{'BASE_SERVER_IP'}) eq 'ipv4' ? 'ip4' : 'ip6';

	my $tags = {
		MX => $data->{'MX'},
		DOMAIN_NAME => $data->{'DOMAIN_NAME'},
		DOMAIN_IP => $data->{'DOMAIN_IP'},
		IP_TYPE => $domainIpType eq 'ip4' ? 'A' : 'AAAA',
		TXT_DOMAIN_IP_TYPE => $domainIpType,
		TXT_SERVER_IP_TYPE => $serverIpType,
		BASE_SERVER_IP => $main::imscpConfig{'BASE_SERVER_IP'}
	};

	# Replacement tags
	$entries = process($tags, $entries);
	return 1 if ! defined $entries;

	$entries = $self->_incTimeStamp(($wrkFileContent ? $wrkFileContent : $entries), $data->{'DOMAIN_NAME'}, $entries);

	unless(defined $entries) {
		error("Unable to update timestamp for $data->{'DOMAIN_NAME'}");
		return 1;
	}

	# TIMESTAMP SECTION END

	# CUSTUMERS DATA SECTION START

	if($data->{'DMN_ADD'}) {
		my $bTag = "; ctm domain als entries BEGIN.\n";
		my $eTag = "; ctm domain als entries END.\n";
		my $fTag = iMSCP::File->new('filename' => "$self->{'tplDir'}/db_dns_entry.tpl")->get();
		unless(defined $fTag) {
			error("Unable to read $self->{'tplDir'}/db_dns_entry.tpl");
			return 1;
		}

		my $old = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db")->get() || '';

		$tags = {
			MANUAL_DNS_NAME => $data->{'DMN_ADD'}->{'MANUAL_DNS_NAME'},
			MANUAL_DNS_CLASS => $data->{'DMN_ADD'}->{'MANUAL_DNS_CLASS'},
			MANUAL_DNS_TYPE => $data->{'DMN_ADD'}->{'MANUAL_DNS_TYPE'},
			MANUAL_DNS_DATA => $data->{'DMN_ADD'}->{'MANUAL_DNS_DATA'}
		};

		my $toadd = process($tags, $fTag);
		my $custom = getBloc($bTag, $eTag, $old);
		$custom =~ s/$data->{'DMN_ADD'}->{'MANUAL_DNS_NAME'}\s[^\n]*\n//img;
		$custom = '' unless $custom;
		$custom = "$bTag$custom$toadd$eTag";

		$entries = replaceBloc($bTag, $eTag, $custom, $entries);
	}

	if($data->{'DMN_DEL'}) {
		my $bTag = "; ctm domain als entries BEGIN.\n";
		my $eTag = "; ctm domain als entries END.\n";
		my $old = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db")->get() || '';

		my $custom = getBloc($bTag, $eTag, $old);
		$custom =~ s/$data->{'DMN_DEL'}->{'MANUAL_DNS_NAME'}\s[^\n]*\n//gim;
		$custom = '' unless $custom;
		$custom = "$bTag$custom$eTag";

		$entries = replaceBloc($bTag, $eTag, $custom, $entries);
	}

	# CUSTUMERS DATA SECTION END

	# CUSTOM DATA SECTION START

	if(keys(%{$data->{'DMN_CUSTOM'}}) > 0 ) {
		my $bTag = iMSCP::File->new('filename' => "$self->{'tplDir'}/db_dns_entry_b.tpl")->get();
		my $eTag = iMSCP::File->new('filename' =>"$self->{'tplDir'}/db_dns_entry_e.tpl")->get();
		unless(defined $bTag && defined $eTag) {
			error('A template has not been found');
			return 1;
		}

		my $FormatTag = iMSCP::File->new('filename' => "$self->{'tplDir'}/db_dns_entry.tpl")->get();
		my $custom = '';

		for(keys %{$data->{'DMN_CUSTOM'}}) {
			next unless
				$data->{'DMN_CUSTOM'}->{$_}->{'domain_text'} &&
				$data->{'DMN_CUSTOM'}->{$_}->{'domain_class'} &&
				$data->{'DMN_CUSTOM'}->{$_}->{'domain_type'};

			$tags = {
				MANUAL_DNS_NAME => $data->{'DMN_CUSTOM'}->{$_}->{'domain_dns'},
				MANUAL_DNS_CLASS => $data->{'DMN_CUSTOM'}->{$_}->{'domain_class'},
				MANUAL_DNS_TYPE => $data->{'DMN_CUSTOM'}->{$_}->{'domain_type'},
				MANUAL_DNS_DATA => $data->{'DMN_CUSTOM'}->{$_}->{'domain_text'}
			};

			$custom .= process($tags, $FormatTag);
		}

		$entries = replaceBloc($bTag, $eTag, $custom, $entries);

	}

	# CUSTOM DATA SECTION END

	# Storing new file in working directory
	my $file = iMSCP::File->new('filename' => $zoneFile);

	$rs = $file->set($entries);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
	return $rs if $rs;

	# Installing new file in production directory (also cleanup file and perform entries checks)
	my ($stdout, $stderr);
	$rs = execute(
		"$self->{'config'}->{'CMD_NAMED_COMPILEZONE'} -i none -s relative " .
		"-o $self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db $data->{'DOMAIN_NAME'} $zoneFile",
		\$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Unable to install zone file $data->{'DOMAIN_NAME'}.db") if $rs && ! $stderr;
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db");

	$rs = $file->mode(0640);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterNamedAddDmnDb', $data);
}

=item _incTimeStamp($oldZoneFile, $dmnName, [$newZoneFile = $oldZoneFile])

 Update timestamp.

 Param string $oldZoneFile Old zone file content
 Param string $dmnName Domain name
 Param string $newZoneFile New zone file content
 Return int 0 on success, other on failure

=cut

sub _incTimeStamp($$$;$)
{
	my $self = shift;
	my $oldZoneFile	= shift;
	my $dmnName = shift;
	my $newZoneFile	= shift || $oldZoneFile;

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedIncTimeStamp');
	return undef if $rs;

	# Create or Update serial number according RFC 1912

	# Loading needed template from /etc/imscp/bind/parts
	my $entries = iMSCP::File->new('filename' => "$self->{'tplDir'}/db_e.tpl")->get();
	unless(defined $entries) {
		error("Unable to read $self->{'tplDir'}/db_e.tpl");
		return 1;
	}

	my $tags = { 'DOMAIN_NAME' => $dmnName };
	my $cleanBTag = iMSCP::File->new('filename' => "$self->{'tplDir'}/db_time_b.tpl")->get();
	my $cleanETag = iMSCP::File->new('filename' => "$self->{'tplDir'}/db_time_e.tpl")->get();
	unless(defined $cleanBTag && defined $cleanETag) {
		error("A template has not been found");
		return 1;
	}

	my $bTag = process($tags, $cleanBTag);
	my $eTag = process($tags, $cleanETag);
	return undef if ! defined $bTag || ! defined $eTag;

	my $timeStampBlock = getBloc($bTag, $eTag, $oldZoneFile);
	my $cleanTimeStampBlock	= getBloc($cleanBTag, $cleanETag, $entries);
	my $timestamp;

	my $regExp = '[\s](?:(\d{4})(\d{2})(\d{2})(\d{2})|(\{TIMESTAMP\}))';
	my (undef, undef, undef, $day, $mon, $year) = localtime;

	if((my $tyear, my $tmon, my $tday, my $nn, my $setup) = ($timeStampBlock =~ /$regExp/)) {
		if($setup) {
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
		error("Unable to find DNS timestamp entry for $dmnName");
		return undef;
	}

	$newZoneFile = replaceBloc($bTag, $eTag, "$bTag$timeStampBlock$eTag", $newZoneFile);

	$rs = $self->{'hooksManager'}->trigger('afterNamedIncTimeStamp');
	return undef if $rs;

	$newZoneFile;
}

END
{
	my $exitCode = $?;
	my $self = Servers::named::bind->getInstance();
	my $rs = 0;

	$rs = $self->restart() if defined $self->{'restart'} && $self->{'restart'} eq 'yes';

	$? = $exitCode || $rs;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
