#!/usr/bin/perl

=head1 NAME

 Servers::named::bind - i-MSCP Bind9 Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
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
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::named::bind;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::HooksManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::TemplateParser;
use iMSCP::Net;
use iMSCP::Service;
use File::Basename;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Bind9 Server implementation

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks($hooksManager)

 Register setup hooks

 Param iMSCP::HooksManager $hooksManager Hooks manager instance
 Return int 0 on success, other on failure

=cut

sub registerSetupHooks($$)
{
	my ($self, $hooksManager) = @_;

	require Servers::named::bind::installer;
	Servers::named::bind::installer->getInstance()->registerSetupHooks($hooksManager);
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	require Servers::named::bind::installer;
	Servers::named::bind::installer->getInstance()->install();
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedPostInstall');
	return $rs if $rs;

	$self->{'hooksManager'}->register(
		'beforeSetupRestartServices', sub { push @{$_[0]}, [ sub { $self->restart(); }, 'DNS' ]; 0; }
	) if $main::imscpConfig{'NAMED_SERVER'} ne 'external_server';

	$self->{'hooksManager'}->trigger('afterNamedPostInstall');
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedUninstall', 'bind');
	return $rs if $rs;

	require Servers::named::bind::uninstaller;
	$rs = Servers::named::bind::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$rs = $self->restart();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterNamedUninstall', 'bind');
}

=item addDmn(\%data)

 Process addDmn tasks

 Param hash_ref $data Reference to a hash containing data as provided by the Domain|Alias modules
 Return int 0 on success, other on failure

=cut

sub addDmn($$)
{
	my ($self, $data) = @_;

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

 Process postaddDmn tasks

 Param hash_ref $data Reference to a hash containing data as provided by the Domain|Alias modules
 Return int 0 on success, other on failure

=cut

sub postaddDmn($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedPostAddDmn', $data);
	return $rs if $rs;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		my $ipMngr = iMSCP::Net->getInstance();

		my $domainIp = ($ipMngr->getAddrType($data->{'DOMAIN_IP'}) eq 'PUBLIC')
			? $data->{'DOMAIN_IP'}
			: $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'};

		# Add DNS entry for domain alternative URL in master zone file
		$rs = $self->addDmn(
			{
				DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
				DOMAIN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
				MAIL_ENABLED => 1,
				CTM_ALS_ENTRY_ADD => {
					NAME => $data->{'USER_NAME'},
					CLASS => 'IN',
					TYPE => ($ipMngr->getAddrVersion($domainIp) eq 'ipv4') ? 'A' : 'AAAA',
					DATA => $domainIp
				}
			}
		);
		return $rs if $rs;
	}

	$rs = $self->{'hooksManager'}->trigger('afterNamedPostAddDmn', $data);
	return $rs if $rs;

	$self->{'restart'} = 1;

	0;
}

=item deleteDmn(\%data)

 Process deleteDmn tasks

 Param hash_ref $data Reference to a hash containing data as provided by the Domain|Alias modules
 Return int 0 on success, other on failure

=cut

sub deleteDmn($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedDelDmn', $data);
	return $rs if $rs;

	# Removing zone from named configuration file
	$rs = $self->_deleteDmnConfig($data);
	return $rs if $rs;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		# Removing working db file
		$rs = iMSCP::File->new(
			'filename' => "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db"
		)->delFile() if -f "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db";
		return $rs if $rs;

		# Removing production db file
		$rs = iMSCP::File->new(
			'filename' => "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db"
		)->delFile() if -f "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db";
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterNamedDelDmn', $data);
}

=item postdeleteDmn(\%data)

 Process postdeleteDmn tasks

 Param hash_ref $data Reference to a hash containing data as provided by the Domain|Alias modules
 Return int 0 on success, other on failure

=cut

sub postdeleteDmn($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedPostDelDmn', $data);
	return $rs if $rs;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		# Removing DNS entry for domain alternative URL in master zone file
		$rs = $self->addDmn(
			{
				DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
				DOMAIN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
				MAIL_ENABLED => 1,
				CTM_ALS_ENTRY_DEL => { NAME => $data->{'USER_NAME'} }
			}
		);
		return $rs if $rs;
	}

	$rs = $self->{'hooksManager'}->trigger('afterNamedPostDelDmn', $data);
	return $rs if $rs;

	$self->{'restart'} = 1;

	0;
}

=item addSub(\%data)

 Process addSub tasks

 Param hash_ref $data Reference to a hash containing data as provided by the Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub addSub($$)
{
	my ($self, $data) = @_;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		my $wrkDbFile = "$self->{'wrkDir'}/$data->{'PARENT_DOMAIN_NAME'}.db";

		if(-f $wrkDbFile) {
			$wrkDbFile = iMSCP::File->new('filename' => $wrkDbFile);

			# Saving current working file
			my $rs = $wrkDbFile->copyFile($self->{'bkpDir'} . '/' . basename($wrkDbFile->{'filename'}) . '.' . time);
			return $rs if $rs;

			# Loading current working db file
			my $wrkDbFileContent = $wrkDbFile->get();
			unless(defined $wrkDbFileContent) {
				error("Unable to read $wrkDbFile->{'filename'}");
				return 1;
			}

			# Loading subdomain entry template
			my $subEntry;
			$rs = $self->{'hooksManager'}->trigger('onLoadTemplate', 'bind', 'db_sub.tpl', \$subEntry, $data);
			return $rs if $rs;

			unless(defined $subEntry) {
				$subEntry = iMSCP::File->new('filename' => "$self->{'tplDir'}/db_sub.tpl")->get();
				unless(defined $subEntry) {
					error("Unable to read $self->{'tplDir'}/db_sub.tpl file");
					return 1;
				}
			}

			$rs = $self->{'hooksManager'}->trigger('beforeNamedAddSub', \$wrkDbFileContent, \$subEntry, $data);
			return $rs if $rs;

			# Updating timestamp entry
			$wrkDbFileContent = $self->_generateSoalSerialNumber($wrkDbFileContent);
			unless(defined $wrkDbFileContent) {
				error('Unable to update SOA Serial');
				return 1;
			}

			# Process MX and SPF entries

			if($data->{'MAIL_ENABLED'}) {
				my $subMailEntry = getBloc("; sub MX entry BEGIN\n", "; sub MX entry ENDING\n", $subEntry);
				my $subMailEntryContent = '';

				for(keys %{$data->{'MAIL_DATA'}}) {
					$subMailEntryContent .= process({ MX_DATA => $data->{'MAIL_DATA'}->{$_} }, $subMailEntry);
				}

				$subEntry = replaceBloc(
					"; sub MX entry BEGIN\n", "; sub MX entry ENDING\n", $subMailEntryContent, $subEntry
				);

				$subEntry = replaceBloc(
					"; sub SPF entry BEGIN\n",
					"; sub SPF entry ENDING\n",
					process(
						{ DOMAIN_NAME => $data->{'PARENT_DOMAIN_NAME'} },
						getBloc("; sub SPF entry BEGIN\n", "; sub SPF entry ENDING\n", $subEntry)
					),
					$subEntry
				);
			} else {
				$subEntry = replaceBloc("; sub MX entry BEGIN\n", "; sub MX entry ENDING\n", '', $subEntry);
				$subEntry = replaceBloc("; sub SPF entry BEGIN\n", "; sub SPF entry ENDING\n", '', $subEntry);
			}

			my $ipMngr = iMSCP::Net->getInstance();

			my $domainIp = ($ipMngr->getAddrType($data->{'DOMAIN_IP'}) eq 'PUBLIC')
				? $data->{'DOMAIN_IP'}
				: $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'};

			# Process other entries
			$subEntry = process(
				{
					SUBDOMAIN_NAME => $data->{'DOMAIN_NAME'},
					IP_TYPE => ($ipMngr->getAddrVersion($domainIp) eq 'ipv4') ? 'A' : 'AAAA',
					DOMAIN_IP => $domainIp
				},
				$subEntry
			);

			# Remove previous entry with same ID if any
			$wrkDbFileContent = replaceBloc(
				"; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
				"; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
				'',
				$wrkDbFileContent
			);

			# Adding new entry
			$wrkDbFileContent = replaceBloc(
				"; sub [{SUBDOMAIN_NAME}] entry BEGIN\n",
				"; sub [{SUBDOMAIN_NAME}] entry ENDING\n",
				$subEntry,
				$wrkDbFileContent,
				'preserve'
			);

			$rs = $self->{'hooksManager'}->trigger('afterNamedAddSub', \$wrkDbFileContent, $data);
			return $rs if $rs;

			# Updating working file content
			$rs = $wrkDbFile->set($wrkDbFileContent);
			return $rs if $rs;

			$rs = $wrkDbFile->save();
			return $rs if $rs;

			# Installing new working file in production directory
			my ($stdout, $stderr);
			$rs = execute(
				"$self->{'config'}->{'CMD_NAMED_COMPILEZONE'} -i none -s relative " .
					"-o $self->{'config'}->{'BIND_DB_DIR'}/$data->{'PARENT_DOMAIN_NAME'}.db " .
					"$data->{'PARENT_DOMAIN_NAME'} $wrkDbFile->{'filename'}",
				\$stdout, \$stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			error("Unable to install $data->{'PARENT_DOMAIN_NAME'}.db") if $rs && ! $stderr;
			return $rs if $rs;

			my $prodFile = iMSCP::File->new(
				'filename' => "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'PARENT_DOMAIN_NAME'}.db"
			);

			$rs = $prodFile->mode(0640);
			return $rs if $rs;

			$rs = $prodFile->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
			return $rs if $rs;
		} else {
			error("File $wrkDbFile not found. Please run the i-MSCP setup script.");
			return 1;
		}
	}

	0;
}

=item postaddSub(\%data)

 Process postaddSub tasks

 Param hash_ref $data Reference to a hash containing data as provided by the Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub postaddSub($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedPostAddSub', $data);
	return $rs if $rs;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		my $ipMngr = iMSCP::Net->getInstance();

		my $domainIp = ($ipMngr->getAddrType($data->{'DOMAIN_IP'}) eq 'PUBLIC')
			? $data->{'DOMAIN_IP'}
			: $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'};

		# Adding DNS entry for subdomain alternative URL in master zone file
		$rs = $self->addDmn(
			{
				DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
				DOMAIN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
				MAIL_ENABLED => 1,
				CTM_ALS_ENTRY_ADD => {
					NAME => $data->{'USER_NAME'},
					CLASS => 'IN',
					TYPE => ($ipMngr->getAddrVersion($domainIp) eq 'ipv4') ? 'A' : 'AAAA',
					DATA => $domainIp
				}
			}
		);
		return $rs if $rs;
	}

	$rs = $self->{'hooksManager'}->trigger('afterNamedPostAddSub', $data);
	return $rs if $rs;

	$self->{'restart'} = 1;

	0;
}

=item deleteSub(\%data)

 Process deleteSub tasks

 Param hash_ref $data Reference to a hash containing data as provided by the Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub deleteSub($$)
{
	my ($self, $data) = @_;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		my $wrkDbFile = "$self->{'wrkDir'}/$data->{'PARENT_DOMAIN_NAME'}.db";

		if(-f $wrkDbFile) {
			$wrkDbFile = iMSCP::File->new('filename' => $wrkDbFile);

			# Backup current working file
			my $rs = $wrkDbFile->copyFile($self->{'bkpDir'} . '/' . basename($wrkDbFile->{'filename'}) . '.' . time);
			return $rs if $rs;

			# Loading current working file
			my $wrkDbFileContent = $wrkDbFile->get();

			unless(defined $wrkDbFileContent) {
				error("Unable to read $wrkDbFile->{'filename'}");
				return 1;
			}

			$rs = $self->{'hooksManager'}->trigger('beforeNamedDelSub', \$wrkDbFileContent, $data);
			return $rs if $rs;

			# Udapting timestamp entry
			$wrkDbFileContent = $self->_generateSoalSerialNumber($wrkDbFileContent);
			unless(defined $wrkDbFileContent) {
				error('Unable to update SOA Serial');
				return 1;
			}

			# Removing subdomain entry
			$wrkDbFileContent = replaceBloc(
				"; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
				"; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
				'',
				$wrkDbFileContent
			);

			$rs = $self->{'hooksManager'}->trigger('afterNamedDelSub', \$wrkDbFileContent, $data);
			return $rs if $rs;

			# Saving working file

			$rs = $wrkDbFile->set($wrkDbFileContent);
			return $rs if $rs;

			$rs = $wrkDbFile->save();
			return $rs if $rs;

			# Compiling new production file from working file
			my ($stdout, $stderr);
			$rs = execute(
				"$self->{'config'}->{'CMD_NAMED_COMPILEZONE'} -i none -s relative " .
					"-o $self->{'config'}->{'BIND_DB_DIR'}/$data->{'PARENT_DOMAIN_NAME'}.db " .
					"$data->{'PARENT_DOMAIN_NAME'} $wrkDbFile->{'filename'}",
				\$stdout,
				\$stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			error("Unable to install $data->{'PARENT_DOMAIN_NAME'}.db") if $rs && ! $stderr;
			return $rs if $rs;

			my $prodFile = iMSCP::File->new(
				'filename' => "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'PARENT_DOMAIN_NAME'}.db"
			);

			$rs = $prodFile->mode(0640);
			return $rs if $rs;

			$rs = $prodFile->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
			return $rs if $rs;
		} else {
			error("File $wrkDbFile not found. Please run the i-MSCP setup script.");
			return 1;
		}
	}

	0;
}

=item postdeleteSub(\%data)

 Process postdeleteSub tasks

 Param hash_ref $data Reference to a hash containing data as provided by the Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub postdeleteSub($$)
{
	my ($self, $data) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedPostDelSub', $data);
	return $rs if $rs;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		# Removing DNS entry for subdomain alternative URL in master zone file
		$rs = $self->addDmn(
			{
				DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
				DOMAIN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
				MAIL_ENABLED => 1,
				CTM_ALS_ENTRY_DEL => { NAME => $data->{'USER_NAME'} }
			}
		);
		return $rs if $rs;
	}

	$rs = $self->{'hooksManager'}->trigger('afterNamedPostDelSub', $data);
	return $rs if $rs;

	$self->{'restart'} = 1;

	0;
}

=item restart()

 Restart Bind9

 Return int 0 other on failure

=cut

sub restart
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeNamedRestart');
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->restart($self->{'config'}->{'NAMED_SNAME'}, 'named');
	error("Unable to restart $self->{'config'}->{'NAMED_SNAME'} service") if $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterNamedRestart');
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance

 Return Servers::named::bind

=cut

sub _init
{
	my $self = $_[0];

	$self->{'restart'} = 0;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforeNamedInit', $self, 'bind'
	) and fatal('bind - beforeNamedInit hook has failed');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/bind";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'tplDir'} = "$self->{'cfgDir'}/parts";

	tie %{$self->{'config'}}, 'iMSCP::Config', 'fileName' => "$self->{'cfgDir'}/bind.data";

	$self->{'hooksManager'}->trigger(
		'afterNamedInit', $self, 'bind'
	) and fatal('bind - afterNamedInit hook has failed');

	$self;
}

=item _addDmnConfig(\%data)

 Add domain DNS configuration

 Param hash_ref $data Reference to a hash containing data as provided by the Domain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _addDmnConfig($$)
{
	my ($self, $data) = @_;

	my ($cfgFileName, $cfgFileDir) = fileparse(
		$self->{'config'}->{'BIND_LOCAL_CONF_FILE'} || $self->{'config'}->{'BIND_CONF_FILE'}
	);

	if(-f "$self->{'wrkDir'}/$cfgFileName") {
		my $cfgFile = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$cfgFileName");

		# Backup current working file
		my $rs = $cfgFile->copyFile("$self->{'bkpDir'}/$cfgFileName." . time);
		return $rs if $rs;

		# Loading current working file
		my $cfgWrkFileContent = $cfgFile->get();
		unless(defined $cfgWrkFileContent) {
			error("Unable to read $self->{'wrkDir'}/$cfgFileName");
			return 1;
		}

		if(defined $self->{'config'}->{'BIND_MODE'} && $self->{'config'}->{'BIND_MODE'} ne '') {
			# Load template
			my $tplCfgEntryContent;
			$rs = $self->{'hooksManager'}->trigger(
				'onLoadTemplate', 'bind', "cfg_$self->{'config'}->{'BIND_MODE'}.tpl", \$tplCfgEntryContent, $data
			);
			return $rs if $rs;

			unless(defined $tplCfgEntryContent) {
				$tplCfgEntryContent = iMSCP::File->new(
					'filename' => "$self->{'tplDir'}/cfg_$self->{'config'}->{'BIND_MODE'}.tpl"
				)->get();
				unless(defined $tplCfgEntryContent) {
					error("Unable to read $self->{'tplDir'}/cfg_$self->{'config'}->{'BIND_MODE'}.tpl");
					return 1;
				}
			}

			$rs = $self->{'hooksManager'}->trigger(
				'beforeNamedAddDmnConfig', \$cfgWrkFileContent, \$tplCfgEntryContent, $data
			);
			return $rs if $rs;

			my $tags = {
				DB_DIR => $self->{'config'}->{'BIND_DB_DIR'},
				DOMAIN_NAME => $data->{'DOMAIN_NAME'}
			};

			if($self->{'config'}->{'BIND_MODE'} eq 'master') {
				if($self->{'config'}->{'SECONDARY_DNS'} ne 'no') {
					$tags->{'SECONDARY_DNS'} = join(
						'; ', split(';', $self->{'config'}->{'SECONDARY_DNS'})
					) . '; localhost;';
				} else {
					$tags->{'SECONDARY_DNS'} = 'localhost;';
				}
			} else {
				$tags->{'PRIMARY_DNS'} = join('; ', split(';', $self->{'config'}->{'PRIMARY_DNS'})) . ';';
			}

			$tplCfgEntryContent =
				"// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n" .
				process($tags, $tplCfgEntryContent) .
				"// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n";

			# Deleting old entry if any
			$cfgWrkFileContent = replaceBloc(
				"// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
				"// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n",
				'',
				$cfgWrkFileContent
			);

			# Adding new entry
			$cfgWrkFileContent = replaceBloc(
				"// imscp [{ENTRY_ID}] entry BEGIN\n",
				"// imscp [{ENTRY_ID}] entry ENDING\n",
				$tplCfgEntryContent,
				$cfgWrkFileContent,
				'preserve'
			);

			$rs = $self->{'hooksManager'}->trigger('afterNamedAddDmnConfig', \$cfgWrkFileContent, $data);
			return $rs if $rs;

			# Updating working file

			$rs = $cfgFile->set($cfgWrkFileContent);
			return $rs if $rs;

			$rs = $cfgFile->save();
			return $rs if $rs;

			$rs = $cfgFile->mode(0644);
			return $rs if $rs;

			$rs = $cfgFile->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
			return $rs if $rs;

			# Installing new working file in production directory
			$rs = $cfgFile->copyFile("$cfgFileDir$cfgFileName");
			return $rs if $rs;
		} else {
			error('Bind mode is not defined. Please rerun the i-MSCP setup script.');
			return 1;
		}
	} else {
		error("File $self->{'wrkDir'}/$cfgFileName not found. Please rerun the i-MSCP setup script.");
		return 1;
	}

	0;
}

=item _deleteDmnConfig(\%data)

 Delete domain DNS configuration

 Param hash_ref $data Reference to a hash containing data as provided by the Domain|Alias modules
 Return int 0 on success, other on failure

=cut

sub _deleteDmnConfig($$)
{
	my ($self, $data) = @_;

	my ($cfgFileName, $cfgFileDir) = fileparse(
		$self->{'config'}->{'BIND_LOCAL_CONF_FILE'} || $self->{'config'}->{'BIND_CONF_FILE'}
	);

	if(-f "$self->{'wrkDir'}/$cfgFileName") {
		my $cfgFile = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$cfgFileName");

		# Backup current working file
		my $rs = $cfgFile->copyFile("$self->{'bkpDir'}/$cfgFileName." . time);
		return $rs if $rs;

		# Loading current working file
		my $cfgWrkFileContent = $cfgFile->get();
		unless(defined $cfgWrkFileContent) {
			error("Unable to read $self->{'wrkDir'}/$cfgFileName");
			return 1;
		}

		$rs = $self->{'hooksManager'}->trigger('beforeNamedDelDmnConfig', \$cfgWrkFileContent, $data);
		return $rs if $rs;

		# Deleting entry
		$cfgWrkFileContent = replaceBloc(
			"// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
			"// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n",
			'',
			$cfgWrkFileContent
		);

		$rs = $self->{'hooksManager'}->trigger('afterNamedDelDmnConfig', \$cfgWrkFileContent, $data);
		return $rs if $rs;

		# Updating working file
		$rs = $cfgFile->set($cfgWrkFileContent);
		return $rs if $rs;

		$rs = $cfgFile->save();
		return $rs if $rs;

		$rs = $cfgFile->mode(0644);
		return $rs if $rs;

		$rs = $cfgFile->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
		return $rs if $rs;

		# Installing new working file in production directory
		$rs = $cfgFile->copyFile("$cfgFileDir$cfgFileName");
		return $rs if $rs;
	} else {
		error("File $self->{'wrkDir'}/$cfgFileName not found. Please rerun the i-MSCP setup script.");
		return 1;
	}
}

=item _addDmnDb(\%data)

 Add domain DNS zone file

 Param hash_ref $data Reference to a hash containing data as provided by the Domain|Alias modules
 Return int 0 on success, other on failure

=cut

sub _addDmnDb($$)
{
	my ($self, $data) = @_;

	my $wrkDbFile = "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db";
	my $wrkDbFileContent;

	if(-f $wrkDbFile) {
		# Saving current working file
		$wrkDbFile = iMSCP::File->new('filename' => $wrkDbFile);

		my $rs = $wrkDbFile->copyFile($self->{'bkpDir'} . '/' . basename($wrkDbFile->{'filename'}) . '.' . time);
		return $rs if $rs;

		# Getting current working file content
		$wrkDbFileContent = $wrkDbFile->get();
		unless(defined $wrkDbFileContent) {
			error("Unable to read $wrkDbFile->{'filename'}");
			return 1;
		}
	} else {
		$wrkDbFile = iMSCP::File->new('filename' => $wrkDbFile);
	}

	# Load template

	my $tplDbFileContent;
	my $rs = $self->{'hooksManager'}->trigger('onLoadTemplate', 'bind', 'db.tpl', \$tplDbFileContent, $data);
	return $rs if $rs;

	unless(defined $tplDbFileContent) {
		$tplDbFileContent = iMSCP::File->new('filename' => "$self->{'tplDir'}/db.tpl")->get();
		unless(defined $tplDbFileContent) {
			error("Unable to read $self->{'tplDir'}/db.tpl");
			return 1;
		}
	}

	$rs = $self->{'hooksManager'}->trigger('beforeNamedAddDmnDb', \$tplDbFileContent, $data);
	return $rs if $rs;

	# Process timestamp entry
	$tplDbFileContent = $self->_generateSoalSerialNumber(
		$tplDbFileContent, (defined $wrkDbFileContent) ? $wrkDbFileContent : undef
	);
	unless(defined $tplDbFileContent) {
		error('Unable to add/update SOA Serial');
		return 1;
	}

	# Process domain NS entries and domain NS A entries

	my $dmnNsEntry = getBloc("; dmn NS entry BEGIN\n", "; dmn NS entry ENDING\n", $tplDbFileContent);
	my $dmnNsAEntry = getBloc("; dmn NS A entry BEGIN\n", "; dmn NS A entry ENDING\n", $tplDbFileContent);

	my $ipMngr = iMSCP::Net->getInstance();

	my $domainIp = ($ipMngr->getAddrType($data->{'DOMAIN_IP'}) eq 'PUBLIC')
		? $data->{'DOMAIN_IP'}
		: $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'};

	my @nsIPs = (
		$domainIp,
		($self->{'config'}->{'SECONDARY_DNS'} eq 'no') ? () : split ';', $self->{'config'}->{'SECONDARY_DNS'}
	);

	my ($dmnNsEntries, $dmnNsAentries, $nsNumber) = (undef, undef, 1);

	for(@nsIPs) {
		$dmnNsEntries .= process({ NS_NUMBER => $nsNumber }, $dmnNsEntry);
		$dmnNsAentries .= process(
			{
				NS_NUMBER => $nsNumber,
				NS_IP_TYPE  => ($ipMngr->getAddrVersion($_) eq 'ipv4') ? 'A' : 'AAAA',
				NS_IP => $_
			},
			$dmnNsAEntry
		);

		$nsNumber++;
	}

	$tplDbFileContent = replaceBloc(
		"; dmn NS entry BEGIN\n", "; dmn NS entry ENDING\n", $dmnNsEntries, $tplDbFileContent
	);

	$tplDbFileContent = replaceBloc(
		"; dmn NS A entry BEGIN\n", "; dmn NS A entry ENDING\n", $dmnNsAentries, $tplDbFileContent
	);

	# Process domain MAIL entries

	my $dmnMailEntry = '';

	if($data->{'MAIL_ENABLED'}) {
		my $baseServerIp = ($ipMngr->getAddrType($main::imscpConfig{'BASE_SERVER_IP'}) eq 'PUBLIC')
			? $main::imscpConfig{'BASE_SERVER_IP'}
			: $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'};

		$dmnMailEntry = process(
			{
				BASE_SERVER_IP_TYPE => ($ipMngr->getAddrVersion($baseServerIp) eq 'ipv4')
					? 'A' : 'AAAA',
				BASE_SERVER_IP => $baseServerIp
			},
			getBloc("; dmn MAIL entry BEGIN\n", "; dmn MAIL entry ENDING\n", $tplDbFileContent)
		)
	}

	$tplDbFileContent = replaceBloc(
		"; dmn MAIL entry BEGIN\n", "; dmn MAIL entry ENDING\n", $dmnMailEntry, $tplDbFileContent
	);

	# Process custom DNS records entries

	my $customDnsEntries = '';

	if ($data->{'CUSTOM_DNS_RECORD'}) {
		for(keys %{$data->{'CUSTOM_DNS_RECORD'}}) {
			$customDnsEntries .= process(
				{
					NAME => $data->{'CUSTOM_DNS_RECORD'}->{$_}->{'domain_dns'},
					CLASS => $data->{'CUSTOM_DNS_RECORD'}->{$_}->{'domain_class'},
					TYPE => $data->{'CUSTOM_DNS_RECORD'}->{$_}->{'domain_type'},
					DATA => $data->{'CUSTOM_DNS_RECORD'}->{$_}->{'domain_text'}
				},
				"{NAME}\t{CLASS}\t{TYPE}\t{DATA}\n"
			);
		}
	}

	$tplDbFileContent = replaceBloc(
		"; custom DNS entries BEGIN\n", "; custom DNS entries ENDING\n", $customDnsEntries, $tplDbFileContent
	);

	# Process customer als entries if any

	if(defined $wrkDbFileContent) {
		if(exists $data->{'CTM_ALS_ENTRY_ADD'}) {
			# Remove previous entry if any
			$wrkDbFileContent =~ s/^$data->{'CTM_ALS_ENTRY_ADD'}->{'NAME'}\s+[^\n]*\n//m;

			# Adding new entry
			$tplDbFileContent = replaceBloc(
				"; ctm als entries BEGIN\n",
				"; ctm als entries ENDING\n",
				"; ctm als entries BEGIN\n" .
				getBloc("; ctm als entries BEGIN\n", "; ctm als entries ENDING\n", $wrkDbFileContent) .
				process(
					{
						NAME => $data->{'CTM_ALS_ENTRY_ADD'}->{'NAME'},
						CLASS => $data->{'CTM_ALS_ENTRY_ADD'}->{'CLASS'},
						TYPE => $data->{'CTM_ALS_ENTRY_ADD'}->{'TYPE'},
						DATA => $data->{'CTM_ALS_ENTRY_ADD'}->{'DATA'}
					},
					"{NAME}\t{CLASS}\t{TYPE}\t{DATA}\n"
				) .
				"; ctm als entries ENDING\n",
				$tplDbFileContent
			);
		} else {
			# Readd current entries
			$tplDbFileContent = replaceBloc(
				"; ctm als entries BEGIN\n",
				"; ctm als entries ENDING\n",
				getBloc("; ctm als entries BEGIN\n", "; ctm als entries ENDING\n", $wrkDbFileContent, 1),
				$tplDbFileContent
			);

			if(exists $data->{'CTM_ALS_ENTRY_DEL'}) {
				# Removing entry
				$tplDbFileContent =~ s/^$data->{'CTM_ALS_ENTRY_DEL'}->{'NAME'}\s+[^\n]*\n//m ;
			}
		}
	}

	# Process any other variable
	$tplDbFileContent = process(
		{
			DOMAIN_NAME => $data->{'DOMAIN_NAME'},
			IP_TYPE => ($ipMngr->getAddrVersion($domainIp) eq 'ipv4') ? 'A' : 'AAAA',
			DOMAIN_IP => $domainIp
		},
		$tplDbFileContent
	);

	$rs = $self->{'hooksManager'}->trigger('afterNamedAddDmnDb', \$tplDbFileContent, $data);
	return $rs if $rs;

	# Storing new file in working directory

	$rs = $wrkDbFile->set($tplDbFileContent);
	return $rs if $rs;

	$rs = $wrkDbFile->save();
	return $rs if $rs;

	# Installing new working file in production directory
	my ($stdout, $stderr);
	$rs = execute(
		"$self->{'config'}->{'CMD_NAMED_COMPILEZONE'} -i none -s relative " .
		"-o $self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db $data->{'DOMAIN_NAME'} " .
		$wrkDbFile->{'filename'},
		\$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Unable to install $data->{'DOMAIN_NAME'}.db") if $rs && ! $stderr;
	return $rs if $rs;

	my $prodFile = iMSCP::File->new('filename' => "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db");

	$rs = $prodFile->mode(0640);
	return $rs if $rs;

	$prodFile->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
}

=item _generateSoalSerialNumber($newDbFile, [$oldDbFile = undef])

 Generate SOA Serial Number according RFC 1912

 Param string $newDbFile New DB file content
 Param string|undef $oldDbFile Old DB file content
 Return string|undef

=cut

sub _generateSoalSerialNumber($$;$)
{
	my ($self, $newDbFile, $oldDbFile) = @_;

	$oldDbFile ||= $newDbFile;

	if(
		(my $tyear, my $tmon, my $tday, my $nn, my $placeholder) = (
			$oldDbFile =~ /^[\s]+(?:(\d{4})(\d{2})(\d{2})(\d{2})|(\{TIMESTAMP\}))/m
		)
	) {
		my (undef, undef, undef, $day, $mon, $year) = localtime;
		my ($newSerial, $oldSerial);

		if($placeholder) {
			$newSerial = sprintf('%04d%02d%02d00', $year + 1900, $mon + 1, $day);
		} else {
			$oldSerial = "$tyear$tmon$tday$nn";
			$nn++;

			if($nn >= 99) {
				$nn = 0;
				$tday++;
			}

			$newSerial = ((($year + 1900) * 10000 + ($mon + 1) * 100 + $day) > ($tyear * 10000 + $tmon * 100 + $tday))
				? (sprintf '%04d%02d%02d00', $year + 1900, $mon + 1, $day)
				: (sprintf '%04d%02d%02d%02d', $tyear, $tmon, $tday, $nn);
		}

		$newDbFile =~ s/$oldSerial/$newSerial/ if defined $oldSerial;
		$newDbFile = process({ TIMESTAMP => $newSerial }, $newDbFile);
	} else {
		$newDbFile = undef;
	}

	$newDbFile;
}

END
{
	unless($main::execmode && $main::execmode eq 'setup' || $main::imscpConfig{'NAMED_SERVER'} eq 'external_server') {
		my $exitCode = $?;
		my $self = Servers::named::bind->getInstance();
		my $rs = 0;

		if($self->{'restart'} && $main::imscpConfig{'NAMED_SERVER'} ne 'external_server') {
			$rs = $self->restart();
		}

		$? = $exitCode || $rs;
	}
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
