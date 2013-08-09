#!/usr/bin/perl

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @category     i-MSCP
# @copyright    2010-2013 by i-MSCP | http://i-mscp.net
# @author       Daniel Andreca <sci2tech@gmail.com>
# @link         http://i-mscp.net i-MSCP Home Site
# @license      http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::po::courier::uninstaller;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Execute;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/courier";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	my $conf = "$self->{'cfgDir'}/courier.data";

	tie %self::config, 'iMSCP::Config','fileName' => $conf;

	0;
}

sub uninstall
{
	my $self = shift;

	my $rs = $self->restoreConfFile();
	return $rs if $rs;

	$rs = $self->authDaemon();
	return $rs if $rs;

	$self->userDB();
}

sub restoreConfFile
{
	my $self = shift;
	my $rs = 0;
	my $file;

	for ('authdaemonrc', 'userdb', $self::config{'COURIER_IMAP_SSL'}, $self::config{'COURIER_POP_SSL'}) {
		$rs	= iMSCP::File->new(
			'filename' => "$self->{'bkpDir'}/$_.system"
		)->copyFile(
			"$self::config{'AUTHLIB_CONF_DIR'}/$_"
		) if -f "$self->{'bkpDir'}/$_.system";
		return $rs if $rs;
	}

	0;
}

sub authDaemon
{
	my $self= shift;

	my $file = iMSCP::File->new('filename' => "$self::config{'AUTHLIB_CONF_DIR'}/authdaemonrc");

	my $rs = $file->mode(0660);
	return $rs if $rs;

	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
}

sub userDB
{
	my $self = shift;

	my $file = iMSCP::File->new('filename' => "$self::config{'AUTHLIB_CONF_DIR'}/userdb");

	my $rs = $file->mode(0600);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	my ($stdout, $stderr);
	$rs = execute($self::config{'CMD_MAKEUSERDB'}, \$stdout, \$stderr);
	debug($stdout) if ($stdout);
	error($stderr) if $stderr && $rs;
	error("Error while executing $self::config{CMD_MAKEUSERDB} returned status $rs") if $rs && ! $stderr;

	$rs;
}

1;
