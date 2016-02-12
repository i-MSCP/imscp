=head1 NAME

 Servers::named::bind::installer - i-MSCP Bind9 Server implementation

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package Servers::named::bind::installer;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Config;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Net;
use iMSCP::ProgramFinder;
use iMSCP::TemplateParser;
use iMSCP::Service;
use Servers::named::bind;
use File::Basename;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Installer for the i-MSCP Bind9 Server implementation.

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

	$eventManager->register(
		'beforeSetupDialog',
		sub {
			push @{$_[0]}, sub { $self->askDnsServerMode(@_) }, sub { $self->askIPv6Support(@_) },
				sub { $self->askLocalDnsResolver(@_) };
			0;
		}
	);
}

=item askDnsServerMode(\%dialog)

 Ask user for DNS server mode

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub askDnsServerMode
{
	my ($self, $dialog) = @_;

	my $dnsServerMode = main::setupGetQuestion('BIND_MODE') || $self->{'config'}->{'BIND_MODE'};

	my $rs = 0;

	if($main::reconfigure ~~ [ 'named', 'servers', 'all', 'forced' ] || not $dnsServerMode ~~ [ 'master', 'slave' ]) {
		($rs, $dnsServerMode) = $dialog->radiolist(
			"\nSelect bind mode", [ 'master', 'slave' ], $dnsServerMode eq 'slave' ? 'slave' : 'master'
		);
	}

	if($rs != 30) {
		$self->{'config'}->{'BIND_MODE'} = $dnsServerMode;
		$rs = $self->askDnsServerIps($dialog);
	}

	$rs;
}

=item askDnsServerIps(\%dialog)

 Ask user for DNS server IPs

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub askDnsServerIps
{
	my ($self, $dialog) = @_;

	my $dnsServerMode = $self->{'config'}->{'BIND_MODE'};

	my $masterDnsIps = main::setupGetQuestion('PRIMARY_DNS') || $self->{'config'}->{'PRIMARY_DNS'};
	$masterDnsIps =~ s/;/ /g;
	my @masterDnsIps = split ' ', $masterDnsIps;

	my $slaveDnsIps = main::setupGetQuestion('SECONDARY_DNS') || $self->{'config'}->{'SECONDARY_DNS'};
	$slaveDnsIps =~ s/;/ /g;
	my @slaveDnsIps = split ' ', $slaveDnsIps;

	my ($rs, $answer, $msg) = (0, '', '');

	if($dnsServerMode eq 'master') {
		if(
			$main::reconfigure ~~ [ 'named', 'servers', 'all', 'forced' ] || ! $slaveDnsIps ||
			($slaveDnsIps ne 'no' && ! $self->_checkIps(\@slaveDnsIps))
		) {
			($rs, $answer) = $dialog->radiolist(
				"\nDo you want add slave DNS servers?", [ 'no', 'yes' ], ("@slaveDnsIps" ~~ [ '', 'no' ]) ? 'no' : 'yes'
			);

			if($rs != 30 && $answer eq 'yes') {
				@slaveDnsIps = () if $slaveDnsIps eq 'no';

				do {
					($rs, $answer) = $dialog->inputbox(
						"\nPlease enter slave DNS server IP addresses, each separated by space: $msg", "@slaveDnsIps"
					);

					$msg = '';

					if($rs != 30) {
						@slaveDnsIps = split ' ', $answer;

						if("@slaveDnsIps" eq '') {
							$msg = "\n\n\\Z1You must enter a least one IP address.\\Zn\n\nPlease, try again:";
						} elsif(! $self->_checkIps(\@slaveDnsIps)) {
							$msg = "\n\n\\Z1Wrong or disallowed IP address found.\\Zn\n\nPlease, try again:";
						}
					}
				} while($rs != 30 && $msg);
			} else {
				@slaveDnsIps = ('no');
			}
		}
	} elsif(
		$main::reconfigure ~~ [ 'named', 'servers', 'all', 'forced' ] || $masterDnsIps ~~ [ '', 'no' ] ||
		! $self->_checkIps(\@masterDnsIps)
	) {
		@masterDnsIps = () if $masterDnsIps eq 'no';

		do {
			($rs, $answer) = $dialog->inputbox(
				"\nPlease enter master DNS server IP addresses, each separated by space: $msg", "@masterDnsIps"
			);

			$msg = '';

			if($rs != 30) {
				@masterDnsIps = split ' ', $answer;

				if("@masterDnsIps" eq '') {
					$msg = "\n\n\\Z1You must enter a least one IP address.\\Zn\n\nPlease, try again:";
				} elsif(! $self->_checkIps(\@masterDnsIps)) {
					$msg = "\n\n\\Z1Wrong or disallowed IP address found.\\Zn\n\nPlease, try again:";
				}
			}
		} while($rs != 30 && $msg);
	}

	if($rs != 30) {
		if($dnsServerMode eq 'master') {
			$self->{'config'}->{'PRIMARY_DNS'} = 'no';
			$self->{'config'}->{'SECONDARY_DNS'} = ("@slaveDnsIps" ne 'no') ? join ';', @slaveDnsIps : 'no';
		} else {
			$self->{'config'}->{'PRIMARY_DNS'} = join ';', @masterDnsIps;
			$self->{'config'}->{'SECONDARY_DNS'} = 'no';
		}
	}

	$rs;
}

=item askIPv6Support(\%dialog)

 Ask user for DNS server IPv6 support

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub askIPv6Support
{
	my ($self, $dialog) = @_;

	my $ipv6 = main::setupGetQuestion('BIND_IPV6') || $self->{'config'}->{'BIND_IPV6'};
	my $rs = 0;

	if($main::reconfigure ~~ [ 'named', 'servers', 'all', 'forced' ] || $ipv6 !~ /^yes|no$/) {
		($rs, $ipv6) = $dialog->radiolist(
			"\nDo you want enable IPv6 support for your DNS server?", [ 'yes', 'no' ], $ipv6 eq 'yes' ? 'yes' : 'no'
		);
	}

	if($rs != 30) {
		$self->{'config'}->{'BIND_IPV6'} = $ipv6;
	}

	$rs;
}

=item askLocalDnsResolver(\%dialog)

 Ask user for local DNS resolver

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub askLocalDnsResolver
{
	my ($self, $dialog) = @_;

	my $localDnsResolver = main::setupGetQuestion('LOCAL_DNS_RESOLVER') || $self->{'config'}->{'LOCAL_DNS_RESOLVER'};
	my $rs = 0;

	if($main::reconfigure ~~ [ 'resolver', 'named', 'all', 'forced' ] || not $localDnsResolver ~~ [ 'yes', 'no' ]) {
		($rs, $localDnsResolver) = $dialog->radiolist(
			"\nDo you want use the local DNS resolver?", [ 'yes', 'no' ], $localDnsResolver ne 'no' ? 'yes' : 'no'
		);
	}

	$self->{'config'}->{'LOCAL_DNS_RESOLVER'} = $localDnsResolver if $rs != 30;

	$rs;
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	for my $conffile('BIND_CONF_DEFAULT_FILE', 'BIND_CONF_FILE', 'BIND_LOCAL_CONF_FILE', 'BIND_OPTIONS_CONF_FILE') {
		if ($self->{'config'}->{$conffile} ne '') {
			my $rs = $self->_bkpConfFile($self->{'config'}->{$conffile});
			return $rs if $rs;
		}
	}

	my $rs = $self->_switchTasks();
	return $rs if $rs;

	$rs = $self->_buildConf();
	return $rs if $rs;

	$rs = $self->_saveConf();
	return $rs if $rs;

	$self->_oldEngineCompatibility();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::named::bind::installer

=cut

sub _init
{
	my $self = shift;

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();
	$self->{'named'} = Servers::named::bind->getInstance();

	$self->{'eventManager'}->trigger(
		'beforeNamedInitInstaller', $self, 'bind'
	) and fatal('bind - beforeNamedInitInstaller has failed');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/bind";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'config'} = $self->{'named'}->{'config'};

	my $oldConf = "$self->{'cfgDir'}/bind.old.data";
	if(-f $oldConf) {
		tie my %oldConfig, 'iMSCP::Config', fileName => $oldConf;

		for my $param(keys %oldConfig) {
			if(exists $self->{'config'}->{$param}) {
				$self->{'config'}->{$param} = $oldConfig{$param};
			}
		}
	}

	$self->{'eventManager'}->trigger(
		'afterNamedInitInstaller', $self, 'bind'
	) and fatal('bind - afterNamedInitInstaller has failed');

	$self;
}

=item _bkpConfFile($cfgFile)

 Backup configuration file

 Param string $cfgFile Configuration file path
 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
	my ($self, $cfgFile) = @_;

	my $rs = $self->{'eventManager'}->trigger('beforeNamedBkpConfFile', $cfgFile);
	return $rs if $rs;

	if(-f $cfgFile) {
		my $file = iMSCP::File->new( filename => $cfgFile );
		my $filename = fileparse($cfgFile);

		unless(-f "$self->{'bkpDir'}/$filename.system") {
			$rs = $file->copyFile("$self->{'bkpDir'}/$filename.system");
			return $rs if $rs;
		} else {
			$rs = $file->copyFile("$self->{'bkpDir'}/$filename." . time);
			return $rs if $rs;
		}
	}

	$self->{'eventManager'}->trigger('afterNamedBkpConfFile', $cfgFile);
}

=item _switchTasks()

 Process switch tasks

 Return int 0 on success, other on failure

=cut

sub _switchTasks
{
	my $self = shift;

	my $slaveDbDir = iMSCP::Dir->new( dirname => "$self->{'config'}->{'BIND_DB_DIR'}/slave" );

	if($self->{'config'}->{'BIND_MODE'} eq 'slave') {
		my $rs = $slaveDbDir->make({
			user => $main::imscpConfig{'ROOT_USER'},
			group => $self->{'config'}->{'BIND_GROUP'},
			mode => '0775'
		});
		return $rs if $rs;

		my ($stdout, $stderr);
		$rs = execute("rm -f $self->{'wrkDir'}/*.db", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		$rs = execute("rm -f $self->{'config'}->{'BIND_DB_DIR'}/*.db", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs;
	} elsif(-d "$self->{'config'}->{'BIND_DB_DIR'}/slave") {
		my $rs = $slaveDbDir->remove();
		return $rs;
	}
}

=item _buildConf()

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
	my $self = shift;

	for my $conffile('BIND_CONF_FILE', 'BIND_LOCAL_CONF_FILE', 'BIND_OPTIONS_CONF_FILE') {
		next if $self->{'config'}->{$conffile} eq '';

		my $filename = fileparse($self->{'config'}->{$conffile});

		my $cfgTpl;
		my $rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'bind', $filename, \$cfgTpl, { });
		return $rs if $rs;

		unless(defined $cfgTpl) {
			$cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/$filename" )->get();
			unless(defined $cfgTpl) {
				error("Unable to read $self->{'cfgDir'}/$filename");
				return 1;
			}
		}

		$rs = $self->{'eventManager'}->trigger('beforeNamedBuildConf', \$cfgTpl, $filename);
		return $rs if $rs;

		if($conffile eq 'BIND_CONF_FILE' && ! -f "$self->{'config'}->{'BIND_CONF_DIR'}/bind.keys") {
			$cfgTpl =~ s%include "$self->{'config'}->{'BIND_CONF_DIR'}/bind.keys";\n%%;
		} elsif($conffile eq 'BIND_OPTIONS_CONF_FILE') {
			$cfgTpl =~ s/listen-on-v6\s+\{\s+any;\s+\};/listen-on-v6 { none; };/ if $self->{'config'}->{'BIND_IPV6'} eq 'no';

			my $namedVersion = $self->_getVersion();
			unless(defined $namedVersion) {
				error('Unable to retrieve named (Bind9) version');
				return 1;
			}

			if(version->parse($namedVersion) >= version->parse('9.9.3')) {
				$cfgTpl =~ s%//\s+(check-spf\s+ignore;)%$1%;
			}

			if($self->{'config'}->{'BIND_CONF_DEFAULT_FILE'} ne '' && -f $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'}) {
				my $filename = fileparse($self->{'config'}->{'BIND_CONF_DEFAULT_FILE'});

				my $fileContent;
				my $rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'bind', $filename, \$fileContent, { });
				return $rs if $rs;

				unless(defined $fileContent) {
					$fileContent = iMSCP::File->new( filename => $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'} )->get();
					unless(defined $fileContent) {
						error("Unable to read $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'}");
						return 1;
					}
				}

				$rs = $self->{'eventManager'}->trigger('beforeNamedBuildConf', \$fileContent, $filename);
				return $rs if $rs;

				# Enable/disable local DNS resolver

				$fileContent =~ s/RESOLVCONF=(?:no|yes)/RESOLVCONF=$self->{'config'}->{'LOCAL_DNS_RESOLVER'}/i;

				# Fix for #IP-1333
				my $serviceMngr = iMSCP::Service->getInstance();
				if($serviceMngr->isSystemd()) {
					if($self->{'config'}->{'LOCAL_DNS_RESOLVER'} eq 'yes') {
						$serviceMngr->enable('bind9-resolvconf');
					} else {
						$serviceMngr->stop('bind9-resolvconf');
						$serviceMngr->disable('bind9-resolvconf');
					}
				}


				# Enable/disable IPV6 support
				if($fileContent =~/OPTIONS="(.*)"/) {
					(my $options = $1) =~ s/\s*-[46]\s*//g;
					$options = '-4 ' . $options unless $self->{'config'}->{'BIND_IPV6'} eq 'yes';
					$fileContent =~ s/OPTIONS=".*"/OPTIONS="$options"/;
				}

				$rs = $self->{'eventManager'}->trigger('afterNamedBuildConf', \$fileContent, $filename);
				return $rs if $rs;

				my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$filename" );

				$rs = $file->set($fileContent);
				return $rs if $rs;

				$rs = $file->save();
				return $rs if $rs;

				$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
				return $rs if $rs;

				$rs = $file->mode(0644);
				return $rs if $rs;

				$rs = $file->copyFile($self->{'config'}->{'BIND_CONF_DEFAULT_FILE'});
				return $rs if $rs;
			}
		}

		$rs = $self->{'eventManager'}->trigger('afterNamedBuildConf', \$cfgTpl, $filename);
		return $rs if $rs;

		my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$filename" );

		$rs = $file->set($cfgTpl);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'});
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;

		$rs = $file->copyFile($self->{'config'}->{$conffile});
		return $rs if $rs;
	}

	0;
}

=item _saveConf()

 Save configuration file

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
	my $self = shift;

	iMSCP::File->new( filename => "$self->{'cfgDir'}/bind.data" )->copyFile("$self->{'cfgDir'}/bind.old.data");
}

=item _checkIps(\@ips)

 Check IP addresses

 Param array \@ips IP addresses to check
 Return bool TRUE if all IPs are valid, FALSE otherwise

=cut

sub _checkIps
{
	my ($self, $ips) = @_;

	my $net = iMSCP::Net->getInstance();

	for my $ipAddr(@{$ips}) {
		if($net->isValidAddr($ipAddr)) {
			my $normalizedIp = $net->normalizeAddr($ipAddr);
			return 0 if $normalizedIp ~~ [ '127.0.0.1', '::1' ];
		} else {
			return 0;
		}
	}

	1;
}

=item _getVersion()

 Get named version

 Return string on success, undef on failure

=cut

sub _getVersion
{
	my $self = shift;

	my ($stdout, $stderr);
	my $rs = execute("$self->{'config'}->{'NAMED_BNAME'} -v", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $rs && $stderr;

	unless($rs) {
		return $1 if $stdout =~ /^BIND\s+([0-9.]+)/;
	}

	undef;
}

=item _oldEngineCompatibility()

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeNamedOldEngineCompatibility');
	return $rs if $rs;

	if(iMSCP::ProgramFinder::find('resolvconf')) {
		my ($stdout, $stderr);
		my $rs = execute("resolvconf -d lo.imscp", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	$self->{'eventManager'}->trigger('afterNameddOldEngineCompatibility');
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
