=head1 NAME

Package::FrontEnd::Installer - i-MSCP FrontEnd package installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Package::FrontEnd::Installer;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Rights;
use iMSCP::TemplateParser;
use iMSCP::SystemUser;
use iMSCP::OpenSSL;
use Package::FrontEnd;
use Servers::named;
use Data::Validate::Domain qw/is_domain/;
use File::Basename;
use Net::LibIDN qw/idn_to_ascii idn_to_unicode/;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP FrontEnd package installer.

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

	$eventManager->register('beforeSetupDialog', sub {
		push @{$_[0]}, sub { $self->askHostname(@_) }, sub { $self->askSsl(@_) }, sub { $self->askPorts(@_) }; 0;
	});
}

=item askDomain(\%dialog)

 Show hostname dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub askHostname
{
	my ($self, $dialog) = @_;

	my $vhost = main::setupGetQuestion('BASE_SERVER_VHOST');
	my %options =  (domain_private_tld => qr /.*/);

	my ($rs, @labels) = (0, $vhost ? split(/\./, $vhost) : ());

	if(grep($_ eq $main::reconfigure, ( 'panel', 'panel_hostname', 'hostnames', 'all', 'forced' ))
		|| @labels < 3 || !is_domain($vhost, \%options)
	) {
		$vhost = 'admin.' . main::setupGetQuestion('SERVER_HOSTNAME') unless $vhost;
		my $msg = '';

		do {
			($rs, $vhost) = $dialog->inputbox(
				"\nPlease enter the domain name from which the control panel must be reachable:$msg",
				idn_to_unicode($vhost, 'utf-8')
			);
			$msg = "\n\n\\Z1'$vhost' is not a fully-qualified domain name (FQDN).\\Zn\n\nPlease try again:";
			$vhost = idn_to_ascii($vhost, 'utf-8');
			@labels = split(/\./, $vhost);
		} while($rs < 30 && (@labels < 3 || !is_domain($vhost, \%options)));
	}

	main::setupSetQuestion('BASE_SERVER_VHOST', $vhost) if $rs < 30;
	$rs;
}

=item askSsl(\%dialog)

 Show SSL dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub askSsl
{
	my ($self, $dialog) = @_;

	my $domainName = main::setupGetQuestion('BASE_SERVER_VHOST');
	my $sslEnabled = main::setupGetQuestion('PANEL_SSL_ENABLED');
	my $selfSignedCertificate = main::setupGetQuestion('PANEL_SSL_SELFSIGNED_CERTIFICATE', 'no');
	my $privateKeyPath = main::setupGetQuestion('PANEL_SSL_PRIVATE_KEY_PATH', '/root/');
	my $passphrase = main::setupGetQuestion('PANEL_SSL_PRIVATE_KEY_PASSPHRASE');
	my $certificatPath = main::setupGetQuestion('PANEL_SSL_CERTIFICATE_PATH', "/root/");
	my $caBundlePath = main::setupGetQuestion('PANEL_SSL_CA_BUNDLE_PATH', '/root/');
	my $baseServerVhostPrefix = main::setupGetQuestion('BASE_SERVER_VHOST_PREFIX', 'http://');
	my $openSSL = iMSCP::OpenSSL->new();
	my $rs = 0;

	if(grep($_ eq $main::reconfigure, ( 'panel', 'panel_ssl', 'ssl', 'all', 'forced' ))
		|| !grep($_ eq $sslEnabled, ( 'yes', 'no' ))
		|| $sslEnabled eq 'yes'
		&& grep($_ eq $main::reconfigure, ( 'panel_hostname', 'hostnames' ))
	) {
		SSL_DIALOG:

		($rs, $sslEnabled) = $dialog->radiolist(
			"\nDo you want to activate SSL for the control panel?", [ 'no', 'yes' ], $sslEnabled eq 'yes' ? 'yes' : 'no'
		);

		if($sslEnabled eq 'yes' && $rs < 30) {
			($rs, $selfSignedCertificate) = $dialog->radiolist(
				"\nDo you have an SSL certificate for the $domainName domain?", [ 'yes', 'no' ],
				grep($_ eq $selfSignedCertificate, ( 'yes', 'no' )) ? $selfSignedCertificate eq 'yes' ? 'no' : 'yes' : 'no'
			);

			$selfSignedCertificate = $selfSignedCertificate eq 'no' ? 'yes' : 'no';

			if($selfSignedCertificate eq 'no' && $rs < 30) {
				my $msg = '';

				do {
					$dialog->msgbox("$msg\nPlease select your private key in next dialog.");

					do {
						($rs, $privateKeyPath) = $dialog->fselect($privateKeyPath);
					} while($rs < 30 && !($privateKeyPath && -f $privateKeyPath));

					if($rs < 30) {
						($rs, $passphrase) = $dialog->passwordbox(
							"\nPlease enter the passphrase for your private key if any:", $passphrase
						);
					}

					if($rs < 30) {
						$openSSL->{'private_key_container_path'} = $privateKeyPath;
						$openSSL->{'private_key_passphrase'} = $passphrase;

						if($openSSL->validatePrivateKey()) {
							$msg = "\n\\Z1Wrong private key or passphrase. Please try again.\\Zn\n\n";
						} else {
							$msg = '';
						}
					}
				} while($rs < 30 && $msg);

				if($rs < 30) {
					$rs = $dialog->yesno("\nDo you have any SSL intermediate certificate(s) (CA Bundle)?");

					unless($rs < 30) {
						do {
							($rs, $caBundlePath) = $dialog->fselect($caBundlePath);
						} while($rs < 30 && !($caBundlePath && -f $caBundlePath));

						$openSSL->{'ca_bundle_container_path'} = $caBundlePath if $rs < 30;
					} else {
						$openSSL->{'ca_bundle_container_path'} = '';
					}
				}

				if($rs < 30) {
					$dialog->msgbox("\nPlease select your SSL certificate in next dialog.");
					$rs = 1;

					do {
						$dialog->msgbox("\n\\Z1Wrong SSL certificate. Please try again.\\Zn\n\n") unless $rs;

						do {
							($rs, $certificatPath) = $dialog->fselect($certificatPath);
						} while($rs < 30 && !($certificatPath && -f $certificatPath));

						$openSSL->{'certificate_container_path'} = $certificatPath if $rs < 30;
					} while($rs < 30 && $openSSL->validateCertificate());
				}
			}

			if($rs < 30 && $sslEnabled eq 'yes') {
				($rs, $baseServerVhostPrefix) = $dialog->radiolist(
					"\nPlease, choose the default HTTP access mode for the control panel", [ 'https', 'http' ],
					$baseServerVhostPrefix eq 'https://' ? 'https' : 'http'
				);

				$baseServerVhostPrefix .= '://'
			}
		}
	} elsif($sslEnabled eq 'yes' && !iMSCP::Getopt->preseed) {
		$openSSL->{'private_key_container_path'} = "$main::imscpConfig{'CONF_DIR'}/$domainName.pem";
		$openSSL->{'ca_bundle_container_path'} = "$main::imscpConfig{'CONF_DIR'}/$domainName.pem";
		$openSSL->{'certificate_container_path'} = "$main::imscpConfig{'CONF_DIR'}/$domainName.pem";

		if($openSSL->validateCertificateChain()) {
			$dialog->msgbox("\nYour SSL certificate for the control panel is missing or invalid.");
			goto SSL_DIALOG;
		}

		# In case the certificate is valid, we do not generate it again
		main::setupSetQuestion('PANEL_SSL_SETUP', 'no');
	}

	if($rs < 30) {
		main::setupSetQuestion('PANEL_SSL_ENABLED', $sslEnabled);
		main::setupSetQuestion('PANEL_SSL_SELFSIGNED_CERTIFICATE', $selfSignedCertificate);
		main::setupSetQuestion('PANEL_SSL_PRIVATE_KEY_PATH', $privateKeyPath);
		main::setupSetQuestion('PANEL_SSL_PRIVATE_KEY_PASSPHRASE', $passphrase);
		main::setupSetQuestion('PANEL_SSL_CERTIFICATE_PATH', $certificatPath);
		main::setupSetQuestion('PANEL_SSL_CA_BUNDLE_PATH', $caBundlePath);
		main::setupSetQuestion('BASE_SERVER_VHOST_PREFIX', ($sslEnabled eq 'yes') ? $baseServerVhostPrefix : 'http://');
	}

	$rs;
}

=item askPorts(\%dialog)

 Show ports dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub askPorts
{
	my ($self, $dialog) = @_;

	my $httpPort = main::setupGetQuestion('BASE_SERVER_VHOST_HTTP_PORT');
	my $httpsPort = main::setupGetQuestion('BASE_SERVER_VHOST_HTTPS_PORT');
	my $ssl = main::setupGetQuestion('PANEL_SSL_ENABLED', 'no');
	my $rs = 0;

	if(grep($_ eq $main::reconfigure, ( 'panel', 'panel_ports', 'all', 'forced' ))
		|| $httpPort =~ /^\d+$/ || $httpPort < 1023 || $httpPort > 65535 || $httpsPort eq $httpPort
	) {
		my $msg = '';

		do {
			($rs, $httpPort) = $dialog->inputbox(
				"\nPlease enter the http port from which the control panel must be reachable:$msg",
				$httpPort ? $httpPort : 8080
			);
			$msg = "\n\n\\Z1The port '$httpPort' is reserved or not valid.\\Zn\n\nPlease try again:";
		} while($rs < 30 && ($httpPort !~ /^\d+$/ || $httpPort < 1023 || $httpPort > 65535 || $httpsPort == $httpPort));
	}

	if($rs < 30 && $ssl eq 'yes') {
		if(grep($_ eq $main::reconfigure, ( 'panel', 'panel_ports', 'all', 'forced' ))
			|| $httpsPort =~ /^\d+$/ || $httpsPort < 1023 || $httpsPort > 65535
			|| $httpsPort == $httpPort
		) {
			my $msg = '';

			do {
				($rs, $httpsPort) = $dialog->inputbox(
					"\nPlease enter the https port from which the control panel must be reachable:$msg",
					$httpsPort ? $httpsPort : 4443
				);
				$msg = "\n\n\\Z1The port '$httpsPort' is reserved or not valid.\\Zn\n\nPlease try again:";
			} while(
				$rs < 30
				&& ($httpsPort !~ /^\d+$/ || $httpsPort < 1023 || $httpsPort > 65535 || $httpsPort == $httpPort)
			);
		}
	} else {
		$httpsPort = 4443;
	}

	if($rs < 30) {
		main::setupSetQuestion('BASE_SERVER_VHOST_HTTP_PORT', $httpPort);
		main::setupSetQuestion('BASE_SERVER_VHOST_HTTPS_PORT', $httpsPort);
	}

	$rs;
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->_setupSsl();
	$rs ||= $self->_setHttpdVersion();
	$rs ||= $self->_addMasterWebUser();
	$rs ||= $self->_makeDirs();
	$rs ||= $self->_buildPhpConfig();
	$rs ||= $self->_buildHttpdConfig();
	$rs ||= $self->_buildInitDefaultFile();
	$rs ||= $self->_addDnsZone();
	$rs ||= $self->_saveConfig();
}

=item setGuiPermissions()

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = shift;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $guiRootDir = $main::imscpConfig{'GUI_ROOT_DIR'};

	my $rs = setRights($guiRootDir, {
		user => $panelUName, group => $panelGName, dirmode => '0550', filemode => '0440', recursive => 1
	});
	$rs ||= setRights("$guiRootDir/themes", {
		user => $panelUName, group => $panelGName, dirmode => '0550', filemode => '0440', recursive => 1
	});
	$rs ||= setRights("$guiRootDir/data", {
		user => $panelUName, group => $panelGName, dirmode => '0700', filemode => '0600', recursive => 1
	});
	$rs ||= setRights("$guiRootDir/data/persistent", {
		user => $panelUName, group => $panelGName, dirmode => '0750', filemode => '0640', recursive => 1
	});
	$rs ||= setRights("$guiRootDir/data", { user => $panelUName, group => $panelGName, mode => '0550' });
	$rs ||= setRights("$guiRootDir/i18n", {
		user => $panelUName, group => $panelGName, dirmode => '0700', filemode => '0600', recursive => 1
	});
	$rs ||= setRights("$guiRootDir/plugins", {
		user => $panelUName, 'group' => $panelGName, 'dirmode' => '0750', 'filemode' => '0640', recursive => 1
	});
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = shift;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $httpdUser = $self->{'config'}->{'HTTPD_USER'};
	my $httpdGroup = $self->{'config'}->{'HTTPD_GROUP'};

	my $rs = setRights($self->{'config'}->{'HTTPD_CONF_DIR'}, {
		user => $rootUName, group => $rootGName, dirmode => '0755', filemode => '0644', recursive => 1
	});
	$rs ||= setRights($self->{'config'}->{'HTTPD_LOG_DIR'}, {
		user => $rootUName, group => $rootGName, dirmode => '0755', filemode => '0640', recursive => 1
	});
	$rs ||= setRights("$self->{'config'}->{'PHP_STARTER_DIR'}/master", {
		'user' => $panelUName, group => $panelGName, dirmode => '0550', filemode => '0640', recursive => 1
	});
	$rs ||= setRights("$self->{'config'}->{'PHP_STARTER_DIR'}/master/php-fcgi-starter", {
		user => $panelUName, group => $panelGName, mode => '550'
	});
	$rs ||= setRights("$self->{'config'}->{'PHP_STARTER_DIR'}/master/php-fcgi-starter", {
		user => $panelUName, group => $panelGName, mode => '550'
	} );

	# Temporary directories as provided by nginx package (from Debian Team)
	if(-d "$self->{'config'}->{'HTTPD_TMP_ROOT_DIR_DEBIAN'}") {
		$rs = setRights($self->{'config'}->{'HTTPD_TMP_ROOT_DIR_DEBIAN'}, { user => $rootUName, group => $rootGName });

		for my $tmp('body', 'fastcgi', 'proxy', 'scgi', 'uwsgi') {
			next unless -d "$self->{'config'}->{'HTTPD_TMP_ROOT_DIR_DEBIAN'}/$tmp";

			$rs = setRights( "$self->{'config'}->{'HTTPD_TMP_ROOT_DIR_DEBIAN'}/$tmp", {
				user => $httpdUser, group => $httpdGroup, dirnmode => '0700', filemode => '0640', recursive => 1
			});
			$rs ||= setRights( "$self->{'config'}->{'HTTPD_TMP_ROOT_DIR_DEBIAN'}/$tmp", {
				user => $httpdUser, group => $rootGName, mode => '0700'
			});
			return $rs if $rs;
		}
	}

	# Temporary directories as provided by nginx package (from nginx Team)
	return 0 unless -d "$self->{'config'}->{'HTTPD_TMP_ROOT_DIR_NGINX'}";

	$rs = setRights($self->{'config'}->{'HTTPD_TMP_ROOT_DIR_NGINX'}, { user => $rootUName, group => $rootGName });

	for my $tmp('client_temp', 'fastcgi_temp', 'proxy_temp', 'scgi_temp', 'uwsgi_temp') {
		next unless -d "$self->{'config'}->{'HTTPD_TMP_ROOT_DIR_NGINX'}/$tmp";

		$rs = setRights("$self->{'config'}->{'HTTPD_TMP_ROOT_DIR_NGINX'}/$tmp", {
			user => $httpdUser, group => $httpdGroup, dirnmode => '0700', filemode => '0640', recursive => 1
		});
		$rs ||= setRights( "$self->{'config'}->{'HTTPD_TMP_ROOT_DIR_NGINX'}/$tmp", {
			user => $httpdUser, group => $rootGName, mode => '0700'
		});
		return $rs if $rs;
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Package::FrontEnd::Installer

=cut

sub _init
{
	my $self = shift;

	$self->{'frontend'} = Package::FrontEnd->getInstance();
	$self->{'eventManager'} = $self->{'frontend'}->{'eventManager'};
	$self->{'cfgDir'} = $self->{'frontend'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'config'} = $self->{'frontend'}->{'config'};

	my $oldConf = "$self->{'cfgDir'}/nginx.old.data";

	if(-f $oldConf) {
		tie %{$self->{'oldConfig'}}, 'iMSCP::Config', fileName => $oldConf, 'noerrors' => 1;

		for my $oldConf(keys %{$self->{'oldConfig'}}) {
			if(exists $self->{'config'}->{$oldConf}) {
				$self->{'config'}->{$oldConf} = $self->{'oldConfig'}->{$oldConf};
			}
		}
	}

	$self;
}

=item _setupSsl()

 Setup SSL

 Return int 0 on success, other on failure

=cut

sub _setupSsl
{
	my $domainName = main::setupGetQuestion('BASE_SERVER_VHOST');
	my $selfSignedCertificate = (main::setupGetQuestion('PANEL_SSL_SELFSIGNED_CERTIFICATE') eq 'yes') ? 1 : 0;
	my $privateKeyPath = main::setupGetQuestion('PANEL_SSL_PRIVATE_KEY_PATH');
	my $passphrase = main::setupGetQuestion('PANEL_SSL_PRIVATE_KEY_PASSPHRASE');
	my $certificatePath = main::setupGetQuestion('PANEL_SSL_CERTIFICATE_PATH');
	my $caBundlePath = main::setupGetQuestion('PANEL_SSL_CA_BUNDLE_PATH');
	my $baseServerVhostPrefix = main::setupGetQuestion('BASE_SERVER_VHOST_PREFIX');
	my $sslEnabled = main::setupGetQuestion('PANEL_SSL_ENABLED');

	return 0 unless $sslEnabled eq 'yes' && main::setupGetQuestion('PANEL_SSL_SETUP', 'yes') eq 'yes';

	if($selfSignedCertificate) {
		return iMSCP::OpenSSL->new(
			'certificate_chains_storage_dir' =>  $main::imscpConfig{'CONF_DIR'},
			'certificate_chain_name' => $domainName
		)->createSelfSignedCertificate($domainName);
	}

	iMSCP::OpenSSL->new(
		'certificate_chains_storage_dir' =>  $main::imscpConfig{'CONF_DIR'},
		'certificate_chain_name' => $domainName,
		'private_key_container_path' => $privateKeyPath,
		'private_key_passphrase' => $passphrase,
		'certificate_container_path' => $certificatePath,
		'ca_bundle_container_path' => $caBundlePath
	)->createCertificateChain();
}

=item _setHttpdVersion()

 Set httpd version

 Return int 0 on success, other on failure

=cut

sub _setHttpdVersion()
{
	my $self = shift;

	my $rs = execute('nginx -v', \ my $stdout, \ my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	if($stderr !~ m%nginx/([\d.]+)%) {
		error('Could not find nginx Nginx from `nginx -v` command output.');
		return 1;
	}

	$self->{'config'}->{'HTTPD_VERSION'} = $1;
	debug(sprintf('Nginx version set to: %s', $1));
	0;
}

=item _addMasterWebUser()

 Add master Web user

 Return int 0 on success, other on failure

=cut

sub _addMasterWebUser
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndAddUser');
	return $rs if $rs;

	my $userName = my $groupName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	my ($db, $errStr) = main::setupGetSqlConnect($main::imscpConfig{'DATABASE_NAME'});
	unless($db) {
		error(sprintf('Could not connect to SQL server: %s', $errStr));
		return 1;
	}

	my $rdata = $db->doQuery(
		'admin_sys_uid',
		'
			SELECT admin_sys_name, admin_sys_uid, admin_sys_gname FROM admin
			WHERE admin_type = ? AND created_by = ? LIMIT 1
		',
		'admin', '0'
	);

	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	if(!%{$rdata}) {
		error('Could not find admin user in database');
		return 1;
	}

	my $adminSysName = $rdata->{(%{$rdata})[0]}->{'admin_sys_name'};
	my $adminSysUid = $rdata->{(%{$rdata})[0]}->{'admin_sys_uid'};
	my $adminSysGname = $rdata->{(%{$rdata})[0]}->{'admin_sys_gname'};
	my ($oldUserName, undef, $userUid, $userGid) = getpwuid($adminSysUid);

	if(!$oldUserName || $userUid == 0) {
		# Creating i-MSCP Master Web user
		$rs = iMSCP::SystemUser->new(
			'username' => $userName,
			'comment' => 'i-MSCP Master Web User',
			'home' => $main::imscpConfig{'GUI_ROOT_DIR'},
			'skipCreateHome' => 1
		)->addSystemUser();
		return $rs if $rs;

		$userUid = getpwnam($userName);
		$userGid = getgrnam($groupName);
	} else {
		my @cmd = (
			'pkill -KILL -u', escapeShell($oldUserName), ';',
			'usermod',
			'-c', escapeShell('i-MSCP Master Web User'),
			'-d', escapeShell($main::imscpConfig{'GUI_ROOT_DIR'}),
			'-l', escapeShell($userName),
			'-m',
			escapeShell($adminSysName)
		);

		$rs = execute("@cmd", \ my $stdout, \ my $stderr);
		debug($stdout) if $stdout;
		debug($stderr) if $stderr && $rs;
		return $rs if $rs;

		@cmd = ('groupmod', '-n', escapeShell($groupName), escapeShell($adminSysGname));
		debug($stdout) if $stdout;
		debug($stderr) if $stderr && $rs;
		$rs = execute("@cmd", \$stdout, \$stderr);
		return $rs if $rs;
	}

	# Update the admin.admin_sys_name, admin.admin_sys_uid, admin.admin_sys_gname and admin.admin_sys_gid columns
	$rdata = $db->doQuery(
		'dummy',
		'
			UPDATE admin SET admin_sys_name = ?, admin_sys_uid = ?, admin_sys_gname = ?, admin_sys_gid = ?
			WHERE admin_type = ?
		',
		$userName, $userUid, $groupName, $userGid, 'admin'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$rs = iMSCP::SystemUser->new( username => $userName )->addToGroup($main::imscpConfig{'IMSCP_GROUP'});
	$rs ||= iMSCP::SystemUser->new( username => $self->{'config'}->{'HTTPD_USER'} )->addToGroup($groupName);
	$rs ||= $self->{'eventManager'}->trigger('afterHttpdAddUser');
}

=item _makeDirs()

 Create directories

 Return int 0 on success, other on failure

=cut

sub _makeDirs
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndMakeDirs');
	return $rs if $rs;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $phpStarterDir = $self->{'config'}->{'PHP_STARTER_DIR'};

	# Ensure that the FCGI starter directory exists
	$rs = iMSCP::Dir->new( dirname => $phpStarterDir )->make({
		user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'ROOT_GROUP'}, mode => 0555
	});
	# Remove previous FCGI tree if any ( needed to avoid any garbage from plugins )
	$rs ||= iMSCP::Dir->new( dirname => "$phpStarterDir/master" )->remove();
	return $rs if $rs;

	for ([ $self->{'config'}->{'HTTPD_CONF_DIR'}, $rootUName, $rootUName, 0755 ],
		[ $self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}, $rootUName, $rootUName, 0755 ],
		[ $self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'}, $rootUName, $rootUName, 0755 ],
		[ $self->{'config'}->{'HTTPD_LOG_DIR'}, $rootUName, $rootUName, 0755 ],
		[ $phpStarterDir, $rootUName, $rootGName, 0555 ],
		[ "$phpStarterDir/master", $panelUName, $panelGName, 0550 ],
		[ "$phpStarterDir/master/php5", $panelUName, $panelGName, 0550 ]
	) {
		$rs = iMSCP::Dir->new( dirname => $_->[0] )->make( { user => $_->[1], group => $_->[2], mode => $_->[3] } );
		return $rs if $rs;
	}

	$self->{'eventManager'}->trigger('afterFrontEndMakeDirs');
}

=item _buildPhpConfig()

 Build PHP configuration

 Return int 0 on success, other on failure

=cut

sub _buildPhpConfig
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEnddBuildPhpConfig');
	return $rs if $rs;

	my $cfgDir = $self->{'cfgDir'};
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";
	my $user = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $group = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $tplVars = {
		PHP_STARTER_DIR => $self->{'config'}->{'PHP_STARTER_DIR'},
		DOMAIN_NAME => 'master',
		PHP_FCGI_MAX_REQUESTS => $self->{'config'}->{'PHP_FCGI_MAX_REQUESTS'},
		PHP_FCGI_CHILDREN => $self->{'config'}->{'PHP_FCGI_CHILDREN'},
		WEB_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
		PANEL_USER => $user,
		PANEL_GROUP => $group,
		SPAWN_FCGI_BIN => $self->{'config'}->{'SPAWN_FCGI_BIN'},
		PHP_CGI_BIN => $self->{'config'}->{'PHP_CGI_BIN'}
	};

	$rs = $self->{'frontend'}->buildConfFile("$cfgDir/parts/master/php-fcgi-starter.tpl", $tplVars, {
		destination => "$wrkDir/master.php-fcgi-starter", mode => 0550, user => $user, group => $group
	});
	$rs ||= iMSCP::File->new( filename => "$wrkDir/master.php-fcgi-starter" )->copyFile(
		"$self->{'config'}->{'PHP_STARTER_DIR'}/master/php-fcgi-starter"
	);
	return $rs if $rs;

	$tplVars = {
		HOME_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
		WEB_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
		DOMAIN => $main::imscpConfig{'BASE_SERVER_VHOST'},
		CONF_DIR => $main::imscpConfig{'CONF_DIR'},
		PEAR_DIR => $main::imscpConfig{'PEAR_DIR'},
		RKHUNTER_LOG => $main::imscpConfig{'RKHUNTER_LOG'},
		CHKROOTKIT_LOG => $main::imscpConfig{'CHKROOTKIT_LOG'},
		OTHER_ROOTKIT_LOG => $main::imscpConfig{'OTHER_ROOTKIT_LOG'} ne ''
			? ":$main::imscpConfig{'OTHER_ROOTKIT_LOG'}" : '',
		TIMEZONE => $main::imscpConfig{'TIMEZONE'},
		DISTRO_OPENSSL_CNF => $main::imscpConfig{'DISTRO_OPENSSL_CNF'},
		DISTRO_CA_BUNDLE => $main::imscpConfig{'DISTRO_CA_BUNDLE'}
	};

	$rs = $self->{'frontend'}->buildConfFile("$cfgDir/parts/master/php5/php.ini", $tplVars, {
		destination => "$wrkDir/master.php.ini", mode => 0440, user => $user, group => $group
	});
	$rs ||= iMSCP::File->new(filename => "$wrkDir/master.php.ini")->copyFile(
		"$self->{'config'}->{'PHP_STARTER_DIR'}/master/php5/php.ini"
	);
	$rs ||= $self->{'eventManager'}->trigger('afterFrontEndBuildPhpConfig');
}

=item _buildHttpdConfig()

 Build httpd configuration

 Return int 0 on success, other on failure

=cut

sub _buildHttpdConfig
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndBuildHttpdConfig');
	return $rs if $rs;

	if(-f "$self->{'wrkDir'}/nginx.conf") {
		$rs = iMSCP::File->new( filename => "$self->{'wrkDir'}/nginx.conf" )->copyFile(
			"$self->{'bkpDir'}/nginx.conf." . time
		);
		return $rs if $rs;
	}

	my $nbCPUcores = $self->{'config'}->{'HTTPD_WORKER_PROCESSES'};

	if($nbCPUcores eq 'auto') {
		$rs = execute('grep processor /proc/cpuinfo | wc -l', \ my $stdout, \ my $stderr);
		debug($stdout) if $stdout;
		debug('Could not detect number of CPU cores. nginx worker_processes value set to 2') if $rs;

		unless($rs) {
			chomp($stdout);
			$nbCPUcores = $stdout;
			$nbCPUcores = 4 if $nbCPUcores > 4; # Limit number of workers
		} else {
			$nbCPUcores = 2;
		}
	}

	$rs = $self->{'frontend'}->buildConfFile("$self->{'cfgDir'}/nginx.conf", {
		'HTTPD_USER' => $self->{'config'}->{'HTTPD_USER'},
		'HTTPD_WORKER_PROCESSES' => $nbCPUcores,
		'HTTPD_WORKER_CONNECTIONS' => $self->{'config'}->{'HTTPD_WORKER_CONNECTIONS'},
		'HTTPD_RLIMIT_NOFILE' => $self->{'config'}->{'HTTPD_RLIMIT_NOFILE'},
		'HTTPD_LOG_DIR' => $self->{'config'}->{'HTTPD_LOG_DIR'},
		'HTTPD_PID_FILE' => $self->{'config'}->{'HTTPD_PID_FILE'},
		'HTTPD_CONF_DIR' => $self->{'config'}->{'HTTPD_CONF_DIR'},
		'HTTPD_LOG_DIR' => $self->{'config'}->{'HTTPD_LOG_DIR'},
		'HTTPD_SITES_ENABLED_DIR' => $self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'}
	});
	return $rs if $rs;

	$rs = iMSCP::File->new( filename => "$self->{'wrkDir'}/nginx.conf" )->copyFile(
		"$self->{'config'}->{'HTTPD_CONF_DIR'}"
	);
	return $rs if $rs;

	if(-f "$self->{'wrkDir'}/imscp_fastcgi.conf") {
		$rs = iMSCP::File->new( filename => "$self->{'wrkDir'}/imscp_fastcgi.conf" )->copyFile(
			"$self->{'bkpDir'}/imscp_fastcgi.conf." . time
		);
		return $rs if $rs;
	}

	$rs = $self->{'frontend'}->buildConfFile("$self->{'cfgDir'}/imscp_fastcgi.conf");
	$rs ||= iMSCP::File->new( filename => "$self->{'wrkDir'}/imscp_fastcgi.conf")->copyFile(
		"$self->{'config'}->{'HTTPD_CONF_DIR'}"
	);
	return $rs if $rs;

	if(-f "$self->{'wrkDir'}/imscp_php.conf") {
		$rs = iMSCP::File->new( filename => "$self->{'wrkDir'}/imscp_php.conf" )->copyFile(
			"$self->{'bkpDir'}/imscp_php.conf." . time
		);
		return $rs if $rs;
	}

	$rs = $self->{'frontend'}->buildConfFile("$self->{'cfgDir'}/imscp_php.conf");
	$rs ||= iMSCP::File->new( filename => "$self->{'wrkDir'}/imscp_php.conf" )->copyFile(
		"$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d"
	);
	$rs ||= $self->{'eventManager'}->trigger('afterFrontEndBuildHttpdConfig');
	$rs ||= $self->{'eventManager'}->trigger('beforeFrontEndBuildHttpdVhosts');
	return $rs if $rs;

	my $httpsPort = $main::imscpConfig{'BASE_SERVER_VHOST_HTTPS_PORT'};
	my $tplVars = {
		'BASE_SERVER_VHOST' => $main::imscpConfig{'BASE_SERVER_VHOST'},
		'BASE_SERVER_IP' => $main::imscpConfig{'BASE_SERVER_IP'},
		'BASE_SERVER_VHOST_HTTP_PORT' => $main::imscpConfig{'BASE_SERVER_VHOST_HTTP_PORT'},
		'BASE_SERVER_VHOST_HTTPS_PORT' => $httpsPort,
		'WEB_DIR' => $main::imscpConfig{'GUI_ROOT_DIR'},
		'CONF_DIR' => $main::imscpConfig{'CONF_DIR'}
	};

	if($main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} eq 'https://') {
		$rs = $self->{'eventManager'}->register( 'afterFrontEndBuildConf', sub {
			my ($cfgTpl, $tplName) = @_;

			if($tplName eq '00_master.conf') {
				$$cfgTpl = replaceBloc(
					"# SECTION custom BEGIN.\n",
					"# SECTION custom END.\n",
					"    # SECTION custom BEGIN.\n" .
					getBloc(
						"# SECTION custom BEGIN.\n",
						"# SECTION custom END.\n",
						$$cfgTpl
					) .
					"    rewrite .* https://\$host:$httpsPort\$request_uri redirect;\n" .
					"    # SECTION custom END.\n",
					$$cfgTpl
				);
			}

			0;
		});
		return $rs if $rs;
	}

	$rs = $self->{'frontend'}->disableSites('default', '00_master.conf', '00_master_ssl.conf');
	$rs ||= $self->{'frontend'}->buildConfFile('00_master.conf', $tplVars);
	$rs ||= iMSCP::File->new( filename => "$self->{'wrkDir'}/00_master.conf" )->copyFile(
		"$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master.conf"
	);
	$rs ||= $self->{'frontend'}->enableSites('00_master.conf');
	return $rs if $rs;

	if($main::imscpConfig{'PANEL_SSL_ENABLED'} eq 'yes') {
		$rs = $self->{'frontend'}->buildConfFile('00_master_ssl.conf', $tplVars);
		$rs ||= iMSCP::File->new( filename => "$self->{'wrkDir'}/00_master_ssl.conf" )->copyFile(
			"$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master_ssl.conf"
		);
		$rs ||= $self->{'frontend'}->enableSites('00_master_ssl.conf');
		return $rs if $rs;
	} else {
		for my $vhost("$self->{'wrkDir'}/00_master_ssl.conf",
			"$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master_ssl.conf"
		) {
			$rs = iMSCP::File->new( filename => $vhost )->delFile() if -f $vhost;
			return $rs if $rs;
		}
	}

	if(-f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf") { # Nginx package as provided by Nginx Team
		$rs = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf" )->moveFile(
			"$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf.disabled"
		);
		return $rs if $rs;
	}

	$self->{'eventManager'}->trigger('afterFrontEndBuildHttpdVhosts');
}

=item _buildInitDefaultFile()

 Build imscp_panel default init file

 Return int 0 on success, other on failure

=cut

sub _buildInitDefaultFile
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndBuildInitDefaultFile');
	return $rs if $rs;

	my $imscpInitdConfDir = "$main::imscpConfig{'CONF_DIR'}/init.d";

	if(-f "$imscpInitdConfDir/imscp_panel.default") {
		if(-f "$imscpInitdConfDir/working/imscp_panel") {
			$rs = iMSCP::File->new( filename => "$imscpInitdConfDir/working/imscp_panel" )->copyFile(
				"$imscpInitdConfDir/backup/imscp_panel." . time
			);
			return $rs if $rs;
		}

		$rs = $self->{'frontend'}->buildConfFile(
			"$imscpInitdConfDir/imscp_panel.default",
			{ MASTER_WEB_USER => $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'} },
			{ destination =>  "$imscpInitdConfDir/working/imscp_panel" }
		);
		$rs ||= iMSCP::File->new( filename => "$imscpInitdConfDir/working/imscp_panel" )->copyFile(
			'/etc/default/imscp_panel'
		);
		return $rs if $rs;
	}

	$self->{'eventManager'}->trigger('afterFrontEndBuildInitDefaultFile');
}

=item _addDnsZone()

 Add DNS zone

 Return int 0 on success, other on failure

=cut

sub _addDnsZone
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeNamedAddMasterZone');
	$rs ||= Servers::named->factory()->addDmn( {
		DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
		DOMAIN_IP => $main::imscpConfig{'BASE_SERVER_IP'},
		MAIL_ENABLED => 1
	});
	$rs ||= $self->{'eventManager'}->trigger('afterNamedAddMasterZone');
}

=item _saveConfig()

 Save configuration

 Return int 0 on success, other on failure

=cut

sub _saveConfig
{
	my $self = shift;

	my $rootUname = $main::imscpConfig{'ROOT_USER'};
	my $rootGname = $main::imscpConfig{'ROOT_GROUP'};
	my $file = iMSCP::File->new( filename => "$self->{'cfgDir'}/nginx.data" );
	my $rs = $file->owner($rootUname, $rootGname);
	$rs ||= $file->mode(0640);
	return $rs if $rs;

	my $cfg = $file->get();
	unless(defined $cfg) {
		error(sprintf('Could not read %s file', "$self->{'cfgDir'}/nginx.data"));
		return 1;
	}

	$file = iMSCP::File->new( filename => "$self->{'cfgDir'}/nginx.old.data" );
	$rs = $file->set($cfg);
	$rs ||= $file->save();
	$rs ||= $file->owner($rootUname, $rootGname);
	$rs ||= $file->mode(0640);
}

=back

=head1 AUTHOR

Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
