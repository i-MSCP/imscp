=head1 NAME

 Servers::sqld::mysql - i-MSCP MySQL server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Servers::sqld::mysql;

use strict;
use warnings;
use iMSCP::Config;
use iMSCP::Debug;
use iMSCP::Service;
use Scalar::Defer;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP MySQL server implementation.

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeSqldPreinstall', 'mysql');

	require Servers::sqld::mysql::installer;
	my $rs = Servers::sqld::mysql::installer->getInstance()->preinstall();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterSqldPreinstall', 'mysql');
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeSqldInstall', 'mysql');

	require Servers::sqld::mysql::installer;
	my $rs = Servers::sqld::mysql::installer->getInstance()->install();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterSqldInstall', 'mysql');
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeSqldPostInstall', 'mysql');
	$self->{'eventManager'}->register(
		'beforeSetupRestartServices', sub { push @{$_[0]}, [ sub { $self->restart(); }, 'MySQL SQL server' ]; 0 }
	);
	$self->{'eventManager'}->trigger('afterSqldPostInstall', 'mysql');
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeSqldUninstall', 'mysql');

	require Servers::sqld::mysql::uninstaller;
	my $rs = Servers::sqld::mysql::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$self->restart();
	$self->{'eventManager'}->trigger('afterSqldUninstall', 'mysql');
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeSqldSetEnginePermissions');

	require Servers::sqld::mysql::installer;
	my $rs = Servers::sqld::mysql::installer->getInstance()->setEnginePermissions();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterSqldSetEnginePermissions');
}

=item restart()

 Restart server

 Return int 0 on success, other on failure

=cut

sub restart
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeSqldRestart');
	iMSCP::Service->getInstance()->restart('mysql');
	$self->{'eventManager'}->trigger('afterSqldRestart');
}

=item getVersion()

 Get SQL server version

 Return string MySQL server version

=cut

sub getVersion
{
	my $self = shift;

	$self->{'config'}->{'SQLD_VERSION'};
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::sqld::mysql

=cut

sub _init
{
	my $self = shift;

	defined $self->{'cfgDir'} or die(sprintf('cfgDir attribute is not defined in %s', ref $self));
	defined $self->{'eventManager'} or die(sprintf('eventManager attribute is not defined in %s', ref $self));

	$self->{'cfgDir'} .= '/mysql';
	$self->{'config'} = lazy { tie my %c, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/mysql.data"; \%c; };
	$self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
