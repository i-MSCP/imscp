=head1 NAME

 Servers::named::bind - i-MSCP Bind9 Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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

package Servers::named::bind;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::ProgramFinder;
use iMSCP::TemplateParser;
use iMSCP::Net;
use iMSCP::Service;
use File::Basename;
use Scalar::Defer;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Bind9 Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners(\%eventManager)

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
	my ($self, $eventManager) = @_;

	require Servers::named::bind::installer;
	Servers::named::bind::installer->getInstance()->registerSetupListeners($eventManager);
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeNamedPreInstall', 'bind');
	$self->{'eventManager'}->trigger('afterNamedPreInstall', 'bind');
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeNamedInstall', 'bind');

	require Servers::named::bind::installer;
	my $rs = Servers::named::bind::installer->getInstance()->install();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterNamedInstall', 'bind');
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeNamedPostInstall');

	iMSCP::Service->getInstance()->enable($self->{'config'}->{'NAMED_SNAME'});

	$self->{'eventManager'}->register(
		'beforeSetupRestartServices', sub { push @{$_[0]}, [ sub { $self->restart(); }, 'Bind9 DNS server' ]; 0 }
	);
	$self->{'eventManager'}->trigger('afterNamedPostInstall');
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeNamedUninstall', 'bind');

	require Servers::named::bind::uninstaller;
	my $rs = Servers::named::bind::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	if(iMSCP::ProgramFinder::find($self->{'config'}->{'NAMED_BNAME'})) {
		$rs = $self->restart();
		return $rs if $rs;
	}

	$self->{'eventManager'}->trigger('afterNamedUninstall', 'bind');
}

=item addDmn(\%data)

 Process addDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeNamedAddDmn', $data);

	my $rs = $self->_addDmnConfig($data);
	return $rs if $rs;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		$rs = $self->_addDmnDb($data);
		return $rs if $rs;
	}

	$self->{'eventManager'}->trigger('afterNamedAddDmn', $data);
}

=item postaddDmn(\%data)

 Process postaddDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub postaddDmn
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeNamedPostAddDmn', $data);

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		my $domainIp = ($main::imscpConfig{'BASE_SERVER_IP'} eq $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'})
			? $data->{'DOMAIN_IP'} : $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'};

		my $rs = $self->addDmn({
			DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
			DOMAIN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
			MAIL_ENABLED => 1,
			CTM_ALS_ENTRY_ADD => {
				NAME => $data->{'USER_NAME'},
				CLASS => 'IN',
				TYPE => (iMSCP::Net->getInstance()->getAddrVersion($domainIp) eq 'ipv4') ? 'A' : 'AAAA',
				DATA => $domainIp
			}
		});
		return $rs if $rs;
	}

	$self->{'reload'} = 1;
	$self->{'eventManager'}->trigger('afterNamedPostAddDmn', $data);
}

=item disableDmn(\%data)

 Process disableDmn tasks

 When a domain is being disabled, we must ensure that the DNS data are still present for it (eg: when doing a full
upgrade or reconfiguration). This explain here why we are calling the addDmn() method.

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub disableDmn
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeNamedDisableDmn', $data);

	my $rs = $self->addDmn($data);
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterNamedDisableDmn', $data);
}

=item postdisableDmn(\%data)

 Process postdisableDmn tasks

 See the disableDmn() method for explaination.

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub postdisableDmn
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeNamedPostDisableDmn', $data);

	my $rs = $self->postaddDmn($data);
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterNamedPostDisableDmn', $data);
}

=item deleteDmn(\%data)

 Process deleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeNamedDelDmn', $data);

	my $rs = $self->_deleteDmnConfig($data);
	return $rs if $rs;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		for my $file(
			"$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db",
			"$self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db"
		) {
			if(-f $file) {
				iMSCP::File->new( filename => $file )->delFile();
			}
		}
	}

	$self->{'eventManager'}->trigger('afterNamedDelDmn', $data);
}

=item postdeleteDmn(\%data)

 Process postdeleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub postdeleteDmn
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeNamedPostDelDmn', $data);

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		my $rs = $self->addDmn({
			DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
			DOMAIN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
			MAIL_ENABLED => 1,
			CTM_ALS_ENTRY_DEL => { NAME => $data->{'USER_NAME'} }
		});
		return $rs if $rs;
	}

	$self->{'reload'} = 1;
	$self->{'eventManager'}->trigger('afterNamedPostDelDmn', $data);
}

=item addSub(\%data)

 Process addSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
	my ($self, $data) = @_;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		my $wrkDbFile = "$self->{'wrkDir'}/$data->{'PARENT_DOMAIN_NAME'}.db";

		if(-f $wrkDbFile) {
			$wrkDbFile = iMSCP::File->new( filename => $wrkDbFile );

			my $wrkDbFileContent = $wrkDbFile->get();

			$self->{'eventManager'}->trigger('onLoadTemplate', 'bind', 'db_sub.tpl', \my $subEntry, $data);

			unless(defined $subEntry) {
				$subEntry = iMSCP::File->new( filename => "$self->{'tplDir'}/db_sub.tpl" )->get();
				unless(defined $subEntry) {
					error("Unable to read $self->{'tplDir'}/db_sub.tpl file");
					return 1;
				}
			}

			$self->{'eventManager'}->trigger('beforeNamedAddSub', \$wrkDbFileContent, \$subEntry, $data);

			$wrkDbFileContent = $self->_generateSoalSerialNumber($wrkDbFileContent);
			unless(defined $wrkDbFileContent) {
				error('Unable to update SOA Serial');
				return 1;
			}

			if($data->{'MAIL_ENABLED'}) {
				my $subMailEntry = getBloc("; sub MX entry BEGIN\n", "; sub MX entry ENDING\n", $subEntry);
				my $subMailEntryContent = '';

				for my $entry(keys %{$data->{'MAIL_DATA'}}) {
					$subMailEntryContent .= process({ MX_DATA => $data->{'MAIL_DATA'}->{$entry} }, $subMailEntry);
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

			my $domainIp = ($main::imscpConfig{'BASE_SERVER_IP'} eq $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'})
				? $data->{'DOMAIN_IP'} : $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'};

			my $ipMngr = iMSCP::Net->getInstance();

			$subEntry = process(
				{
					SUBDOMAIN_NAME => $data->{'DOMAIN_NAME'},
					IP_TYPE => ($ipMngr->getAddrVersion($domainIp) eq 'ipv4') ? 'A' : 'AAAA',
					DOMAIN_IP => $domainIp
				},
				$subEntry
			);

			$wrkDbFileContent = replaceBloc(
				"; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
				"; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
				'',
				$wrkDbFileContent
			);

			$wrkDbFileContent = replaceBloc(
				"; sub [{SUBDOMAIN_NAME}] entry BEGIN\n",
				"; sub [{SUBDOMAIN_NAME}] entry ENDING\n",
				$subEntry,
				$wrkDbFileContent,
				'preserve'
			);

			$self->{'eventManager'}->trigger('afterNamedAddSub', \$wrkDbFileContent, $data);

			$wrkDbFile->set($wrkDbFileContent);
			$wrkDbFile->save();

			my $rs = execute(
				'named-compilezone -i none -s relative ' .
					"-o $self->{'config'}->{'BIND_DB_DIR'}/$data->{'PARENT_DOMAIN_NAME'}.db " .
					"$data->{'PARENT_DOMAIN_NAME'} $wrkDbFile->{'filename'}",
				\my $stdout, \my $stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			error("Unable to install $data->{'PARENT_DOMAIN_NAME'}.db") if $rs && ! $stderr;
			return $rs if $rs;

			my $prodFile = iMSCP::File->new(
				filename => "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'PARENT_DOMAIN_NAME'}.db"
			);
			$prodFile->mode(0640);
			$prodFile->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
		} else {
			error("File $wrkDbFile not found. Please run the i-MSCP setup script.");
			return 1;
		}
	}

	0;
}

=item postaddSub(\%data)

 Process postaddSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub postaddSub
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeNamedPostAddSub', $data);

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		my $domainIp = (($main::imscpConfig{'BASE_SERVER_IP'} eq $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'}))
			? $data->{'DOMAIN_IP'} : $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'};

		my $rs = $self->addDmn({
			DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
			DOMAIN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
			MAIL_ENABLED => 1,
			CTM_ALS_ENTRY_ADD => {
				NAME => $data->{'USER_NAME'},
				CLASS => 'IN',
				TYPE => (iMSCP::Net->getInstance()->getAddrVersion($domainIp) eq 'ipv4') ? 'A' : 'AAAA',
				DATA => $domainIp
			}
		});
		return $rs if $rs;
	}

	$self->{'reload'} = 1;
	$self->{'eventManager'}->trigger('afterNamedPostAddSub', $data);
}

=item disableSub(\%data)

 Process disableSub tasks

 When a subdomain is being disabled, we must ensure that the DNS data are still present for it (eg: when doing a full
upgrade or reconfiguration). This explain here why we are calling the addSub() method.

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub disableSub
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeNamedDisableSub', $data);

	my $rs = $self->addSub($data);
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterNamedDisableSub', $data);
}

=item postdisableSub(\%data)

 Process postdisableSub tasks

 See the disableSub() method for explaination.

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub postdisableSub
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeNamedPostDisableSub', $data);

	my $rs = $self->postaddSub($data);
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterNamedPostDisableSub', $data);
}

=item deleteSub(\%data)

 Process deleteSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
	my ($self, $data) = @_;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		my $wrkDbFile = "$self->{'wrkDir'}/$data->{'PARENT_DOMAIN_NAME'}.db";

		if(-f $wrkDbFile) {
			$wrkDbFile = iMSCP::File->new( filename => $wrkDbFile );

			my $wrkDbFileContent = $wrkDbFile->get();

			$self->{'eventManager'}->trigger('beforeNamedDelSub', \$wrkDbFileContent, $data);

			$wrkDbFileContent = $self->_generateSoalSerialNumber($wrkDbFileContent);
			unless(defined $wrkDbFileContent) {
				error('Unable to update SOA Serial');
				return 1;
			}

			$wrkDbFileContent = replaceBloc(
				"; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
				"; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
				'',
				$wrkDbFileContent
			);

			$self->{'eventManager'}->trigger('afterNamedDelSub', \$wrkDbFileContent, $data);

			$wrkDbFile->set($wrkDbFileContent);
			$wrkDbFile->save();

			my $rs = execute(
				'named-compilezone -i none -s relative ' .
					"-o $self->{'config'}->{'BIND_DB_DIR'}/$data->{'PARENT_DOMAIN_NAME'}.db " .
					"$data->{'PARENT_DOMAIN_NAME'} $wrkDbFile->{'filename'}",
				\my $stdout,
				\my $stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			error("Unable to install $data->{'PARENT_DOMAIN_NAME'}.db") if $rs && ! $stderr;
			return $rs if $rs;

			my $prodFile = iMSCP::File->new(
				filename => "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'PARENT_DOMAIN_NAME'}.db"
			);
			$prodFile->mode(0640);
			$prodFile->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
		} else {
			error("File $wrkDbFile not found. Please run the i-MSCP setup script.");
			return 1;
		}
	}

	0;
}

=item postdeleteSub(\%data)

 Process postdeleteSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub postdeleteSub
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeNamedPostDelSub', $data);

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		my $rs = $self->addDmn({
			DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
			DOMAIN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
			MAIL_ENABLED => 1,
			CTM_ALS_ENTRY_DEL => { NAME => $data->{'USER_NAME'} }
		});
		return $rs if $rs;
	}

	$self->{'reload'} = 1;
	$self->{'eventManager'}->trigger('afterNamedPostDelSub', $data);
}

=item addCustomDNS(\%data)

 Process addCustomDNS tasks

 Param hash \%data Custom DNS data
 Return int 0 on success, other on failure

=cut

sub addCustomDNS
{
	my ($self, $data) = @_;

	if($self->{'config'}->{'BIND_MODE'} eq 'master') {
		my $wrkDbFile = "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db";

		if(-f $wrkDbFile) {
			$wrkDbFile = iMSCP::File->new( filename => $wrkDbFile );

			my $wrkDbFileContent = $wrkDbFile->get();

			$wrkDbFileContent = $self->_generateSoalSerialNumber($wrkDbFileContent);
			unless(defined $wrkDbFileContent) {
				error('Unable to update SOA Serial');
				return 1;
			}

			$self->{'eventManager'}->trigger('beforeNamedAddCustomDNS', \$wrkDbFileContent, $data);

			my $customDnsEntries = '';
			for my $record(@{$data->{'DNS_RECORDS'}}) {
				my ($name, $class, $type, $rdata) = @{$record};
				$customDnsEntries .= "$name\t$class\t$type\t$rdata\n";
			}

			$wrkDbFileContent = replaceBloc(
				"; custom DNS entries BEGIN\n",
				"; custom DNS entries ENDING\n",
				"; custom DNS entries BEGIN\n" . $customDnsEntries . "; custom DNS entries ENDING\n",
				$wrkDbFileContent
			);

			$self->{'eventManager'}->trigger('afterNamedAddCustomDNS', \$wrkDbFileContent, $data);

			$wrkDbFile->set($wrkDbFileContent);
			$wrkDbFile->save();

			my $rs = execute(
				'named-compilezone -i none -s relative ' .
					"-o $self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db " .
					"$data->{'DOMAIN_NAME'} $wrkDbFile->{'filename'}",
				\my $stdout,
				\my $stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			error("Unable to install $data->{'DOMAIN_NAME'}.db") if $rs && ! $stderr;
			return $rs if $rs;

			my $prodFile = iMSCP::File->new(
				filename => "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db"
			);
			$prodFile->mode(0640);
			$prodFile->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});

			$self->{'reload'} = 1;
		} else {
			error("File $wrkDbFile not found. Please run the i-MSCP setup script.");
			return 1;
		}
	}

	0;
}

=item restart()

 Restart Bind9

 Return int 0 on success, other on failure

=cut

sub restart
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeNamedRestart');
	iMSCP::Service->getInstance()->restart($self->{'config'}->{'NAMED_SNAME'});
	$self->{'eventManager'}->trigger('afterNamedRestart');
}

=item reload()

 Reload Bind9

 Return int 0 on success, other on failure

=cut

sub reload
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeNamedReload');
	iMSCP::Service->getInstance()->reload($self->{'config'}->{'NAMED_SNAME'});
	$self->{'eventManager'}->trigger('afterNamedReload');
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::named::bind

=cut

sub _init
{
	my $self = shift;

	defined $self->{'cfgDir'} or die(sprintf('cfgDir attribute is not defined in %s', ref $self));
	defined $self->{'eventManager'} or die(sprintf('eventManager attribute is not defined in %s', ref $self));

	$self->{'restart'} = 0;
	$self->{'reload'} = 0;
	$self->{'cfgDir'} .= '/bind';
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'tplDir'} = "$self->{'cfgDir'}/parts";
	$self->{'config'} = lazy { tie my %c, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/bind.data"; \%c; };
	$self;
}

=item _addDmnConfig(\%data)

 Add domain DNS configuration

 Param hash \%data Data as provided by the Domain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _addDmnConfig
{
	my ($self, $data) = @_;

	my ($cfgFileName, $cfgFileDir) = fileparse(
		$self->{'config'}->{'BIND_LOCAL_CONF_FILE'} || $self->{'config'}->{'BIND_CONF_FILE'}
	);

	if(-f "$self->{'wrkDir'}/$cfgFileName") {
		my $cfgFile = iMSCP::File->new( filename => "$self->{'wrkDir'}/$cfgFileName" );
		my $cfgWrkFileContent = $cfgFile->get();

		if(defined $self->{'config'}->{'BIND_MODE'}) {
			my $tplFileName = "cfg_$self->{'config'}->{'BIND_MODE'}.tpl";
			my $tplCfgEntryContent;
			$self->{'eventManager'}->trigger('onLoadTemplate', 'bind', $tplFileName, \$tplCfgEntryContent, $data);

			unless(defined $tplCfgEntryContent) {
				$tplCfgEntryContent = iMSCP::File->new( filename => "$self->{'tplDir'}/$tplFileName" )->get();
				unless(defined $tplCfgEntryContent) {
					error("Unable to read $self->{'tplDir'}/$tplFileName");
					return 1;
				}
			}

			$self->{'eventManager'}->trigger(
				'beforeNamedAddDmnConfig', \$cfgWrkFileContent, \$tplCfgEntryContent, $data
			);

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

			$cfgWrkFileContent = replaceBloc(
				"// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
				"// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n",
				'',
				$cfgWrkFileContent
			);

			$cfgWrkFileContent = replaceBloc(
				"// imscp [{ENTRY_ID}] entry BEGIN\n",
				"// imscp [{ENTRY_ID}] entry ENDING\n",
				$tplCfgEntryContent,
				$cfgWrkFileContent,
				'preserve'
			);

			$self->{'eventManager'}->trigger('afterNamedAddDmnConfig', \$cfgWrkFileContent, $data);

			$cfgFile->set($cfgWrkFileContent);
			$cfgFile->save();
			$cfgFile->mode(0644);
			$cfgFile->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
			$cfgFile->copyFile("$cfgFileDir$cfgFileName");
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

 Param hash \%data Data as provided by the Domain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _deleteDmnConfig
{
	my ($self, $data) = @_;

	my ($cfgFileName, $cfgFileDir) = fileparse(
		$self->{'config'}->{'BIND_LOCAL_CONF_FILE'} || $self->{'config'}->{'BIND_CONF_FILE'}
	);

	if(-f "$self->{'wrkDir'}/$cfgFileName") {
		my $cfgFile = iMSCP::File->new( filename => "$self->{'wrkDir'}/$cfgFileName" );
		my $cfgWrkFileContent = $cfgFile->get();

		$self->{'eventManager'}->trigger('beforeNamedDelDmnConfig', \$cfgWrkFileContent, $data);
		$cfgWrkFileContent = replaceBloc(
			"// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
			"// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n",
			'',
			$cfgWrkFileContent
		);
		$self->{'eventManager'}->trigger('afterNamedDelDmnConfig', \$cfgWrkFileContent, $data);

		$cfgFile->set($cfgWrkFileContent);
		$cfgFile->save();
		$cfgFile->mode(0644);
		$cfgFile->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
		$cfgFile->copyFile("$cfgFileDir$cfgFileName");
	} else {
		error("File $self->{'wrkDir'}/$cfgFileName not found. Please rerun the i-MSCP setup script.");
		return 1;
	}
}

=item _addDmnDb(\%data)

 Add domain DNS zone file

 Param hash \%data Data as provided by the Domain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _addDmnDb
{
	my ($self, $data) = @_;

	my $wrkDbFile = "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db";
	my $wrkDbFileContent;

	if(-f $wrkDbFile) {
		$wrkDbFile = iMSCP::File->new( filename => $wrkDbFile);
		$wrkDbFileContent = $wrkDbFile->get();
	} else {
		$wrkDbFile = iMSCP::File->new( filename => $wrkDbFile );
	}

	$self->{'eventManager'}->trigger('onLoadTemplate', 'bind', 'db.tpl', \my $tplDbFileContent, $data);

	unless(defined $tplDbFileContent) {
		$tplDbFileContent = iMSCP::File->new( filename => "$self->{'tplDir'}/db.tpl" )->get();
	}

	$self->{'eventManager'}->trigger('beforeNamedAddDmnDb', \$tplDbFileContent, $data);

	$tplDbFileContent = $self->_generateSoalSerialNumber(
		$tplDbFileContent, (defined $wrkDbFileContent) ? $wrkDbFileContent : undef
	);
	unless(defined $tplDbFileContent) {
		error('Unable to add/update SOA Serial');
		return 1;
	}

	my $dmnNsEntry = getBloc("; dmn NS entry BEGIN\n", "; dmn NS entry ENDING\n", $tplDbFileContent);
	my $dmnNsAEntry = getBloc("; dmn NS A entry BEGIN\n", "; dmn NS A entry ENDING\n", $tplDbFileContent);

	my $domainIp = (($main::imscpConfig{'BASE_SERVER_IP'} eq $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'}))
		? $data->{'DOMAIN_IP'} : $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'};

	my @nsIPs = (
		$domainIp, ($self->{'config'}->{'SECONDARY_DNS'} eq 'no') ? () : split ';', $self->{'config'}->{'SECONDARY_DNS'}
	);

	my $ipMngr = iMSCP::Net->getInstance();
	my ($dmnNsEntries, $dmnNsAentries, $nsNumber) = (undef, undef, 1);

	for my $ipAddr(@nsIPs) {
		$dmnNsEntries .= process({ NS_NUMBER => $nsNumber }, $dmnNsEntry);
		$dmnNsAentries .= process(
			{
				NS_NUMBER => $nsNumber,
				NS_IP_TYPE  => ($ipMngr->getAddrVersion($ipAddr) eq 'ipv4') ? 'A' : 'AAAA',
				NS_IP => $ipAddr
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

	my $dmnMailEntry = '';

	if($data->{'MAIL_ENABLED'}) {
		my $baseServerIp = $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'};

		$dmnMailEntry = process(
			{
				BASE_SERVER_IP_TYPE => ($ipMngr->getAddrVersion($baseServerIp) eq 'ipv4') ? 'A' : 'AAAA',
				BASE_SERVER_IP => $baseServerIp
			},
			getBloc("; dmn MAIL entry BEGIN\n", "; dmn MAIL entry ENDING\n", $tplDbFileContent)
		)
	}

	$tplDbFileContent = replaceBloc(
		"; dmn MAIL entry BEGIN\n", "; dmn MAIL entry ENDING\n", $dmnMailEntry, $tplDbFileContent
	);

	for my $record(@{$data->{'SPF_RECORDS'}}) {
		$tplDbFileContent .= $record . "\n";
	}

	if(defined $wrkDbFileContent) {
		if(exists $data->{'CTM_ALS_ENTRY_ADD'}) {
			$wrkDbFileContent =~ s/^$data->{'CTM_ALS_ENTRY_ADD'}->{'NAME'}\s+[^\n]*\n//m;

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
			$tplDbFileContent = replaceBloc(
				"; ctm als entries BEGIN\n",
				"; ctm als entries ENDING\n",
				getBloc("; ctm als entries BEGIN\n", "; ctm als entries ENDING\n", $wrkDbFileContent, 1),
				$tplDbFileContent
			);

			if(exists $data->{'CTM_ALS_ENTRY_DEL'}) {
				$tplDbFileContent =~ s/^$data->{'CTM_ALS_ENTRY_DEL'}->{'NAME'}\s+[^\n]*\n//m ;
			}
		}
	}

	$tplDbFileContent = process(
		{
			DOMAIN_NAME => $data->{'DOMAIN_NAME'},
			IP_TYPE => ($ipMngr->getAddrVersion($domainIp) eq 'ipv4') ? 'A' : 'AAAA',
			DOMAIN_IP => $domainIp
		},
		$tplDbFileContent
	);

	$self->{'eventManager'}->trigger('afterNamedAddDmnDb', \$tplDbFileContent, $data);

	$wrkDbFile->set($tplDbFileContent);
	$wrkDbFile->save();

	my $rs = execute(
		'named-compilezone -i none -s relative ' .
			"-o $self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db $data->{'DOMAIN_NAME'} " .
			$wrkDbFile->{'filename'},
		\my $stdout,
		\my $stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Unable to install $data->{'DOMAIN_NAME'}.db") if $rs && ! $stderr;
	return $rs if $rs;

	my $prodFile = iMSCP::File->new( filename => "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db" );
	$prodFile->mode(0640);
	$prodFile->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
}

=item _generateSoalSerialNumber($newDbFile [, $oldDbFile = undef ])

 Generate SOA Serial Number according RFC 1912

 Param string $newDbFile New DB file content
 Param string|undef $oldDbFile Old DB file content
 Return string|undef

=cut

sub _generateSoalSerialNumber
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

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
