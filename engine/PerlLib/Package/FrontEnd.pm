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
use iMSCP::HooksManager;
use iMSCP::TemplateParser;
use iMSCP::Service;
use File::Basename;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP FrontEnd package

=head1 PUBLIC METHODS

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeFrontEndPreInstall');
	return $rs if $rs;

	$rs = $self->stop();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterFrontEndPreInstall');
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeFrontEndInstall');
	return $rs if $rs;

	require Package::FrontEnd::Installer;
	$rs = Package::FrontEnd::Installer->getInstance()->install();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterFrontEndInstall');
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeFrontEndPostInstall');
	return $rs if $rs;

	$self->{'hooksManager'}->register(
		'beforeSetupRestartServices', sub { push @{$_[0]}, [ sub { $self->start(); }, 'FRONTEND' ]; 0; }
	);

	$self->{'hooksManager'}->trigger('afterFrontEndPostInstall');
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeFrontEndUninstall');
	return $rs if $rs;

	require Package::FrontEnd::Uninstaller;
	$rs = Package::FrontEnd::Uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterFrontEndUninstall');
}

=item setGuiPermissions()

 Set file permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeFrontEndSetPermissions');
	return $rs if $rs;

	require Package::FrontEnd::Installer;
	$rs = Package::FrontEnd::Installer->getInstance()->setGuiPermissions();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterFrontEndSetPermissions');
}

=item enableSites($sites)

 Enable the given sites

 Param string $sites Names of sites to enable, each separated by a space
 Return int 0 on sucess, other on failure

=cut

sub enableSites($$)
{
	my ($self, $sites) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeEnableFrontEndSites', \$sites);
	return $rs if $rs;

	my ($stdout, $stderr);

	for(split(' ', $sites)){
		if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_") {
			my $siteName = basename($_, '.conf');
			# TODO make relative symlink
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

	$self->{'hooksManager'}->trigger('afterEnableFrontEndSites', $sites);
}

=item disableSites($sites)

 Disable the given sites

 Param string $siteS Names of sites to disable, each separated by a space
 Return int 0 on success, other on failure

=cut

sub disableSites($$)
{
	my ($self, $sites) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeDisableFrontEndSites', \$sites);
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

	$self->{'hooksManager'}->trigger('afterDisableFrontEndSites', $sites);
}

=item start()

 Start frontEnd

 Return int 0 on success, other on failure

=cut

sub start
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeFrontEndStart');
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->start($self->{'config'}->{'HTTPD_SNAME'});
	error("Unable to start $self->{'config'}->{'HTTPD_SNAME'} service") if $rs;
	return $rs if $rs;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	$rs = iMSCP::Service->getInstance()->start($main::imscpConfig{'IMSCP_PANEL_SNAME'}, "-u $panelUName php5-cgi");
	error("Unable to start imscp_panel (FCGI manager) service") if $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterFrontEndStart');
}

=item stop()

 Stop frontEnd

 Return int 0 on success, other on failure

=cut

sub stop
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeFrontEndStop');
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->stop("$self->{'config'}->{'HTTPD_SNAME'}");
	error("Unable to stop $self->{'config'}->{'HTTPD_SNAME'} service") if $rs;
	return $rs if $rs;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	$rs = iMSCP::Service->getInstance()->stop($main::imscpConfig{'IMSCP_PANEL_SNAME'}, "-u $panelUName php5-cgi");
	error("Unable to stop imscp_panel (FCGI manager) service") if $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterFrontEndStop');
}

=item restart()

 Restart frontEnd

 Return int 0 on success, other on failure

=cut

sub restart
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeFrontEndRestart');
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->restart($self->{'config'}->{'HTTPD_SNAME'});
	error("Unable to restart $self->{'config'}->{'HTTPD_SNAME'} service") if $rs;
	return $rs if $rs;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	$rs = iMSCP::Service->getInstance()->restart($main::imscpConfig{'IMSCP_PANEL_SNAME'}, "-u $panelUName php5-cgi");
	error("Unable to restart imscp_panel (FCGI manager) service") if $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterFrontEndRestart');
}

=item buildConfFile($file, [\%data], [\%options])

 Build the given configuration file

 Param string $file Absolute path to config file or config filename relative to the $self->{'cfgDir'} directory
 Param hash $tplVars Reference to a hash containing template variables
 Param hash_ref $options Reference to a hash containing options such as destination, mode, user and group for final file
 Return int 0 on success, other on failure

=cut

sub buildConfFile($$;$$)
{
	my ($self, $file, $tplVars, $options) = @_;

	$tplVars ||= { };
	$options ||= { };

	my ($name, $path, $suffix) = fileparse($file);

	# Load template

	my $cfgTpl;
	my $rs = $self->{'hooksManager'}->trigger('onLoadTemplate', 'frontend', $name, \$cfgTpl, $tplVars);
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

	$rs = $self->{'hooksManager'}->trigger('beforeFrontEndBuildConfFile', \$cfgTpl, "$name$suffix", $tplVars, $options);
	return $rs if $rs;

	$cfgTpl = $self->_buildConf($cfgTpl, "$name$suffix", $tplVars);
	return 1 unless defined $cfgTpl;

	$cfgTpl =~ s/\n{2,}/\n\n/g; # Remove any duplicate blank lines

	$rs = $self->{'hooksManager'}->trigger('afterFrontEndBuildConfFile', \$cfgTpl, "$name$suffix", $tplVars, $options);
	return $rs if $rs;

	# Store file

	my $fileHandler = iMSCP::File->new(
		'filename' => ($options->{'destination'} ? $options->{'destination'} : "$self->{'wrkDir'}/$name$suffix")
	);

	$rs = $fileHandler->set($cfgTpl);
	return $rs if $rs;

	$rs = $fileHandler->save();
	return $rs if $rs;

	$rs = $fileHandler->mode($options->{'mode'} ? $options->{'mode'} : 0644);
	return $rs if $rs;

	$fileHandler->owner(
		$options->{'user'} ? $options->{'user'} : $main::imscpConfig{'ROOT_USER'},
		$options->{'group'} ? $options->{'group'} : $main::imscpConfig{'ROOT_GROUP'}
	);
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
	$self->{'restart'} = 0;

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/nginx";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	tie %{$self->{'config'}}, 'iMSCP::Config', 'fileName' => "$self->{'cfgDir'}/nginx.data";

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self;
}

=item _buildConf($cfgTpl, $filename, \%tplVars)

 Build the given configuration template

 Param string $cfgTpl String representing content of the configuration template
 Param string $filename Configuration template name
 Param hash $tplVars Reference to a hash containing template variables
 Return string String representing content of configuration template or undef

=cut

sub _buildConf($$$$)
{
	my ($self, $cfgTpl, $filename, $tplVars) = @_;

	$self->{'hooksManager'}->trigger('beforeFrontEndBuildConf', \$cfgTpl, $filename, $tplVars);

	$cfgTpl = process($tplVars, $cfgTpl);
	return undef if ! $cfgTpl;

	$self->{'hooksManager'}->trigger('afterFrontEndBuildConf', \$cfgTpl, $filename, $tplVars);

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
		}

		$? = $exitCode || $rs;
	}
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
