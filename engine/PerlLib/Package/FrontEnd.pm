#!/usr/bin/perl

=head1 NAME

Package::FrontEnd - i-MSCP FrontEnd package

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Package::FrontEnd;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::Execute;
use iMSCP::EventManager;
use iMSCP::TemplateParser;
use iMSCP::Service;
use File::Basename;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP FrontEnd package.

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

	require Package::FrontEnd::Installer;
	Package::FrontEnd::Installer->getInstance()->registerSetupListeners($eventManager);
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndPreInstall');
	return $rs if $rs;

	$rs = $self->stop();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterFrontEndPreInstall');
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndInstall');
	return $rs if $rs;

	require Package::FrontEnd::Installer;
	$rs = Package::FrontEnd::Installer->getInstance()->install();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterFrontEndInstall');
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndPostInstall');
	return $rs if $rs;

	$self->{'eventManager'}->register(
		'beforeSetupRestartServices', sub { push @{$_[0]}, [ sub { $self->start(); }, 'Frontend (Nginx)' ]; 0; }
	);

	$self->{'eventManager'}->trigger('afterFrontEndPostInstall');
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndUninstall');
	return $rs if $rs;

	require Package::FrontEnd::Uninstaller;
	$rs = Package::FrontEnd::Uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterFrontEndUninstall');
}

=item setGuiPermissions()

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFrontendSetGuiPermissions');
	return $rs if $rs;

	require Package::FrontEnd::Installer;
	$rs = Package::FrontEnd::Installer->getInstance()->setGuiPermissions();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterFrontendSetGuiPermissions');
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndSetEnginePermissions');
	return $rs if $rs;

	require Package::FrontEnd::Installer;
	$rs = Package::FrontEnd::Installer->getInstance()->setEnginePermissions();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterFrontEndSetEnginePermissions');
}

=item enableSites($sites)

 Enable the given sites

 Param string $sites Names of sites to enable, each space separated
 Return int 0 on sucess, other on failure

=cut

sub enableSites
{
	my ($self, $sites) = @_;

	my $rs = $self->{'eventManager'}->trigger('beforeEnableFrontEndSites', \$sites);
	return $rs if $rs;

	my ($stdout, $stderr);

	for(split(' ', $sites)){
		if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_") {
			my $siteName = basename($_, '.conf');
			$rs = execute(
				"$main::imscpConfig{'CMD_LN'} -fs $self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_ " .
					"$self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'}/$siteName",
				\$stdout,
				\$stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;

			$self->{'restart'} = 1;
		} else {
			error("Site $_ doesn't exist");
			return 1;
		}
	}

	$self->{'eventManager'}->trigger('afterEnableFrontEndSites', $sites);
}

=item disableSites($sites)

 Disable the given sites

 Param string $siteS Names of sites to disable, each space separated
 Return int 0 on success, other on failure

=cut

sub disableSites
{
	my ($self, $sites) = @_;

	my $rs = $self->{'eventManager'}->trigger('beforeDisableFrontEndSites', \$sites);
	return $rs if $rs;

	my ($stdout, $stderr);

	for(split(' ', $sites)) {
		my $siteName = basename($_, '.conf');

		if(-s "$self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'}/$siteName") {
			$rs = execute(
				"$main::imscpConfig{'CMD_RM'} -f $self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'}/$siteName",
				\$stdout,
				\$stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;

			$self->{'restart'} = 1;
		}
	}

	$self->{'eventManager'}->trigger('afterDisableFrontEndSites', $sites);
}

=item start()

 Start frontEnd

 Return int 0 on success, other on failure

=cut

sub start
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndStart');
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->start($self->{'config'}->{'HTTPD_SNAME'});
	error("Unable to start $self->{'config'}->{'HTTPD_SNAME'} service") if $rs;
	return $rs if $rs;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	$rs = iMSCP::Service->getInstance()->start($main::imscpConfig{'IMSCP_PANEL_SNAME'}, "-u $panelUName php5-cgi");
	error("Unable to start imscp_panel (FCGI manager) service") if $rs;
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterFrontEndStart');
}

=item stop()

 Stop frontEnd

 Return int 0 on success, other on failure

=cut

sub stop
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndStop');
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->stop("$self->{'config'}->{'HTTPD_SNAME'}");
	error("Unable to stop $self->{'config'}->{'HTTPD_SNAME'} service") if $rs;
	return $rs if $rs;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	$rs = iMSCP::Service->getInstance()->stop($main::imscpConfig{'IMSCP_PANEL_SNAME'}, "-u $panelUName php5-cgi");
	error("Unable to stop imscp_panel (FCGI manager) service") if $rs;
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterFrontEndStop');
}

=item reload()

 Reload frontEnd

 Return int 0 on success, other on failure

=cut

sub reload
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndReload');
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->reload($self->{'config'}->{'HTTPD_SNAME'});
	error("Unable to reload $self->{'config'}->{'HTTPD_SNAME'} service") if $rs;
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterFrontEndReload');
}

=item restart()

 Restart frontEnd

 Return int 0 on success, other on failure

=cut

sub restart
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndRestart');
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->restart($self->{'config'}->{'HTTPD_SNAME'});
	error("Unable to restart $self->{'config'}->{'HTTPD_SNAME'} service") if $rs;
	return $rs if $rs;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	$rs = iMSCP::Service->getInstance()->restart($main::imscpConfig{'IMSCP_PANEL_SNAME'}, "-u $panelUName php5-cgi");
	error("Unable to restart imscp_panel (FCGI manager) service") if $rs;
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterFrontEndRestart');
}

=item buildConfFile($file, [\%tplVars = { }, [\%options = { }]])

 Build the given configuration file

 Param string $file Absolute config file path or config filename relative to the nginx configuration directory
 Param hash \%tplVars OPTIONAL Template variables
 Param hash \%options OPTIONAL Options such as destination, mode, user and group for final file
 Return int 0 on success, other on failure

=cut

sub buildConfFile
{
	my ($self, $file, $tplVars, $options) = @_;

	$tplVars ||= { };
	$options ||= { };

	my ($filename, $path) = fileparse($file);

	# Load template

	my $cfgTpl;
	my $rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'frontend', $filename, \$cfgTpl, $tplVars);
	return $rs if $rs;

	unless(defined $cfgTpl) {
		$file = "$self->{'cfgDir'}/$file" unless -d $path && $path ne './';

		$cfgTpl = iMSCP::File->new('filename' => $file)->get();
		unless(defined $cfgTpl) {
			error("Unable to read $file");
			return 1;
		}
	}

	# Build file

	$rs = $self->{'eventManager'}->trigger('beforeFrontEndBuildConfFile', \$cfgTpl, $filename, $tplVars, $options);
	return $rs if $rs;

	$cfgTpl = $self->_buildConf($cfgTpl, $filename, $tplVars);

	$cfgTpl =~ s/\n{2,}/\n\n/g; # Remove any duplicate blank lines

	$rs = $self->{'eventManager'}->trigger('afterFrontEndBuildConfFile', \$cfgTpl, $filename, $tplVars, $options);
	return $rs if $rs;

	# Store file

	my $fileHandler = iMSCP::File->new(
		'filename' => ($options->{'destination'}) ? $options->{'destination'} : "$self->{'wrkDir'}/$filename"
	);

	$rs = $fileHandler->set($cfgTpl);
	return $rs if $rs;

	$rs = $fileHandler->save();
	return $rs if $rs;

	$rs = $fileHandler->mode($options->{'mode'} ? $options->{'mode'} : 0644);
	return $rs if $rs;

	$fileHandler->owner(
		($options->{'user'}) ? $options->{'user'} : $main::imscpConfig{'ROOT_USER'},
		($options->{'group'}) ? $options->{'group'} : $main::imscpConfig{'ROOT_GROUP'}
	);
}

=item deleteTmp()

 Delete temporary files

 Return int 0 on success, other on failure

=cut

sub deleteTmp
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndDelTmp');
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterFrontEndDelTmp');
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Package::FrontEnd

=cut

sub _init
{
	my $self = $_[0];

	$self->{'start'} = 0;
	$self->{'reload'} = 0;
	$self->{'restart'} = 0;

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/nginx";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	tie %{$self->{'config'}}, 'iMSCP::Config', 'fileName' => "$self->{'cfgDir'}/nginx.data";

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();

	$self;
}

=item _buildConf($cfgTpl, $filename, [\%tplVars])

 Build the given configuration template

 Param string $cfgTpl Temmplate content
 Param string $filename Template filename
 Param hash OPTIONAL \%tplVars Template variables
 Return string Template content

=cut

sub _buildConf
{
	my ($self, $cfgTpl, $filename, $tplVars) = @_;

	$tplVars ||= { };

	$self->{'eventManager'}->trigger('beforeFrontEndBuildConf', \$cfgTpl, $filename, $tplVars);

	$cfgTpl = process($tplVars, $cfgTpl);

	$self->{'eventManager'}->trigger('afterFrontEndBuildConf', \$cfgTpl, $filename, $tplVars);

	$cfgTpl;
}

=item END

 Code triggered at the very end of script execution

 - Start or restart httpd and php-fcgi if needed

 Return int Exit code

=cut

END
{
	unless($main::execmode && $main::execmode eq 'setup') {
		my $exitCode = $?;
		my $self = Package::FrontEnd->getInstance();
		my $rs = 0;

		if($self->{'start'}) {
			$rs = $self->start();
		} elsif($self->{'restart'}) {
			$rs = $self->restart();
		} elsif($self->{'reload'}) {
			$rs = $self->reload();
		}

		$? = $exitCode || $rs;
	}
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
