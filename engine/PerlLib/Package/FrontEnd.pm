=head1 NAME

Package::FrontEnd - i-MSCP FrontEnd package

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

package Package::FrontEnd;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::EventManager;
use iMSCP::TemplateParser;
use iMSCP::Service;
use File::Basename;
use Scalar::Defer;
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
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndPreInstall');
	$rs ||= $self->stop();
	$rs ||= $self->{'eventManager'}->trigger('afterFrontEndPreInstall');
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndInstall');
	require Package::FrontEnd::Installer;
	$rs ||= Package::FrontEnd::Installer->getInstance()->install();
	$rs ||= $self->{'eventManager'}->trigger('afterFrontEndInstall');
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndPostInstall');
	return $rs if $rs;

	local $@;
	eval {
		my $serviceMngr = iMSCP::Service->getInstance();
		$serviceMngr->enable($self->{'config'}->{'HTTPD_SNAME'});
		$serviceMngr->enable('imscp_panel');
	};
	if($@) {
		error($@);
		return 1;
	}

	$rs = $self->{'eventManager'}->register(
		'beforeSetupRestartServices', sub { push @{$_[0]}, [ sub { $self->start(); }, 'Frontend (Nginx/PHP)' ]; 0; }
	);
	$rs ||= $self->{'eventManager'}->trigger('afterFrontEndPostInstall');
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndUninstall');
	require Package::FrontEnd::Uninstaller;
	$rs ||= Package::FrontEnd::Uninstaller->getInstance()->uninstall();
	$rs ||=$self->{'eventManager'}->trigger('afterFrontEndUninstall');
}

=item setGuiPermissions()

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFrontendSetGuiPermissions');
	require Package::FrontEnd::Installer;
	$rs ||= Package::FrontEnd::Installer->getInstance()->setGuiPermissions();
	$rs ||= $self->{'eventManager'}->trigger('afterFrontendSetGuiPermissions');
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndSetEnginePermissions');
	require Package::FrontEnd::Installer;
	$rs ||= Package::FrontEnd::Installer->getInstance()->setEnginePermissions();
	$rs ||= $self->{'eventManager'}->trigger('afterFrontEndSetEnginePermissions');
}

=item enableSites(@sites)

 Enable the given site(s)

 Param array @sites List of sites to enable
 Return int 0 on sucess, other on failure

=cut

sub enableSites
{
	my ($self, @sites) = @_;

	my $rs = $self->{'eventManager'}->trigger('beforeEnableFrontEndSites', \@sites);
	return $rs if $rs;

	for my $site(@sites) {
		unless(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$site") {
			error(sprintf("Site %s doesn't exist", $site));
			return 1;
		}

		unless(symlink(
			"$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$site",
			"$self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'}/" . basename($site, '.conf')
		)) {
			error(sprintf('Could not enable the %s site: %s', $site, $!));
			return 1;
		}
	}

	$self->{'eventManager'}->trigger('afterEnableFrontEndSites', @sites);
}

=item disableSites(@sites)

 Disable the given site(s)

 Param array @sites List of sites to disable
 Return int 0 on success, other on failure

=cut

sub disableSites
{
	my ($self, @sites) = @_;

	my $rs = $self->{'eventManager'}->trigger('beforeDisableFrontEndSites', \@sites);
	return $rs if $rs;

	for my $site(@sites) {
		my $siteName = basename($site, '.conf');

		next unless -l "$self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'}/$siteName";

		$rs = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'}/$siteName" )->delFile();
		return $rs if $rs;

		$self->{'restart'} = 1;
	}

	$self->{'eventManager'}->trigger('afterDisableFrontEndSites', @sites);
}

=item start()

 Start frontEnd

 Return int 0 on success, other on failure

=cut

sub start
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndStart');
	return $rs if $rs;

	local $@;
	eval {
		my $serviceMngr = iMSCP::Service->getInstance();
		$serviceMngr->start($self->{'config'}->{'HTTPD_SNAME'});
		$serviceMngr->start('imscp_panel');
	};
	if($@) {
		error($@);
		return 1;
	}

	$self->{'eventManager'}->trigger('afterFrontEndStart');
}

=item stop()

 Stop frontEnd

 Return int 0 on success, other on failure

=cut

sub stop
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndStop');
	return $rs if $rs;

	local $@;
	eval {
		my $serviceMngr = iMSCP::Service->getInstance();
		$serviceMngr->stop("$self->{'config'}->{'HTTPD_SNAME'}");
		$serviceMngr->stop('imscp_panel');
	};
	if($@) {
		error($@);
		return 1;
	}

	$self->{'eventManager'}->trigger('afterFrontEndStop');
}

=item reload()

 Reload frontEnd

 Return int 0 on success, other on failure

=cut

sub reload
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndReload');
	return $rs if $rs;

	local $@;
	eval { iMSCP::Service->getInstance()->reload($self->{'config'}->{'HTTPD_SNAME'}); };
	if($@) {
		error($@);
		return 1;
	}

	$self->{'eventManager'}->trigger('afterFrontEndReload');
}

=item restart()

 Restart frontEnd

 Return int 0 on success, other on failure

=cut

sub restart
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFrontEndRestart');
	return $rs if $rs;

	local $@;
	eval {
		my $serviceMngr = iMSCP::Service->getInstance();
		$serviceMngr->restart($self->{'config'}->{'HTTPD_SNAME'});
		$serviceMngr->restart('imscp_panel');
	};
	if($@) {
		error($@);
		return 1;
	}

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
	my $rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'frontend', $filename, \ my $cfgTpl, $tplVars);
	return $rs if $rs;

	unless(defined $cfgTpl) {
		$file = "$self->{'cfgDir'}/$file" unless -d $path && $path ne './';
		$cfgTpl = iMSCP::File->new( filename => $file )->get();
		unless(defined $cfgTpl) {
			error(sprintf('Could not read %s file', $file));
			return 1;
		}
	}

	$rs = $self->{'eventManager'}->trigger('beforeFrontEndBuildConfFile', \$cfgTpl, $filename, $tplVars, $options);
	return $rs if $rs;

	$cfgTpl = $self->_buildConf($cfgTpl, $filename, $tplVars);
	$cfgTpl =~ s/\n{2,}/\n\n/g; # Remove any duplicate blank lines

	$rs = $self->{'eventManager'}->trigger('afterFrontEndBuildConfFile', \$cfgTpl, $filename, $tplVars, $options);
	return $rs if $rs;

	my $fileHandler = iMSCP::File->new(
		filename => $options->{'destination'} ? $options->{'destination'} : "$self->{'wrkDir'}/$filename"
	);
	$rs = $fileHandler->set($cfgTpl);
	$rs ||= $fileHandler->save();
	$rs ||= $fileHandler->mode($options->{'mode'} ? $options->{'mode'} : 0644);
	$rs ||= $fileHandler->owner(
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
	my $self = shift;

	$self->{'start'} = 0;
	$self->{'reload'} = 0;
	$self->{'restart'} = 0;
	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/nginx";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'config'} = lazy { tie my %c, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/nginx.data"; \%c; };
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
	unless($? || defined $main::execmode && $main::execmode eq 'setup') {
		my $self = Package::FrontEnd->getInstance();

		if($self->{'start'}) {
			$? = $self->start();
		} elsif($self->{'restart'}) {
			$? = $self->restart();
		} elsif($self->{'reload'}) {
			$? = $self->reload();
		}
	}
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
