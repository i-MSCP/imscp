#!/usr/bin/perl

=head1 NAME

 Servers::sqld::mysql - i-MSCP MySQL server implementation

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

package Servers::sqld::mysql;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Execute;
use iMSCP::Service;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP MySQL server implementation.

=head1 PUBLIC METHODS

=over 4

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	require Servers::sqld::mysql::installer;

	Servers::sqld::mysql::installer->getInstance()->install();
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeSqldPostInstall', 'mysql');
	return $rs if $rs;

	$self->{'hooksManager'}->register(
		'beforeSetupRestartServices', sub { push @{$_[0]}, [ sub { $self->restart(); }, 'SQL' ]; 0; }
	) if $main::imscpConfig{'SQL_SERVER'} ne 'remote_server';

	$self->{'hooksManager'}->trigger('afterSqldPostInstall', 'mysql');
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeSqldUninstall', 'mysql');
	return $rs if $rs;

	require Servers::sqld::mysql::uninstaller;

	$rs = Servers::sqld::mysql::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$rs = $self->restart();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterSqldUninstall', 'mysql');
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	require Servers::sqld::mysql::installer;

	Servers::sqld::mysql::installer->getInstance()->setEnginePermissions();
}

=item restart()

 Restart server

 Return int 0 on success, other on failure

=cut

sub restart
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeSqldRestart');
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->restart('mysql', 'mysqld');
	error("Unable to restart mysql service") if $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterSqldRestart');
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance of this class.

 Return Servers::sqld::mysql

=cut

sub _init
{
	my $self = $_[0];

	$self->{'restart'} = 0;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforeSqldInit', $self, 'mysql'
	) and fatal('mysql - beforeSqldInit hook has failed');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/mysql";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'hooksManager'}->trigger(
		'afterSqldInit', $self, 'mysql'
	) and fatal('postfix - afterSqldInit hook has failed');

	$self;
}

=item END

 Process end tasks

=cut

END
{
	unless($main::execmode && $main::execmode eq 'setup' || $main::imscpConfig{'SQL_SERVER'} eq 'remote_server') {
		my $exitCode = $?;
		my $self = Servers::sqld::mysql->getInstance();
		my $rs = 0;

		if($self->{'restart'}) {
			$rs |= $self->restart();
		}

		$? = $exitCode || $rs;
	}
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
