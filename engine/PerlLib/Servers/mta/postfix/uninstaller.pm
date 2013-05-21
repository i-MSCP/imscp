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
# @category    i-MSCP
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2


package Servers::mta::postfix::uninstaller;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Templator;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/postfix";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'vrlDir'} = "$self->{'cfgDir'}/imscp";

	my $conf = "$self->{'cfgDir'}/postfix.data";

	tie %self::postfixConfig, 'iMSCP::Config','fileName' => $conf;

	0;
}

sub uninstall
{
	my $self = shift;

	my $rs = $self->restoreConfFile();
	return $rs if $rs;

	$rs = $self->buildAliasses();
	return $rs if $rs;

	$rs = $self->removeUsers();
	return $rs if $rs;

	$self->removeDirs();
}

sub removeDirs
{
	my $self = shift;
	my $rs = 0;

	use iMSCP::Dir;

	debug('Creating postfix folders');

	for ($self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'}, $self::postfixConfig{'MTA_VIRTUAL_MAIL_DIR'}) {
		$rs = iMSCP::Dir->new('dirname' => $_)->remove();
		return $rs if $rs;
	}

	0;
}

sub removeUsers
{
	my $rs = 0;

	use iMSCP::SystemUser;
	my $user = iMSCP::SystemUser->new();

	$user->{'force'} = 'yes';

	$user->delSystemUser($self::postfixConfig{'MTA_MAILBOX_UID_NAME'});
}

sub buildAliasses
{
	$self = shift;

	my ($$stdout, $stderr);

	# Rebuilding the database for the mail aliases file - Begin
	my $rs = execute("$self::postfixConfig{'CMD_NEWALIASES'}", \$stdout, \$stderr);
	debug($stdout);
	error($stderr) if $stderr && $rs;
	error("Error while executing $self::postfixConfig{'CMD_NEWALIASES'}") if ! $stderr && $rs;

	$rs;
}

sub restoreConfFile
{
	my $self = shift;
	my $rs = 0;

	use File::Basename;
	use iMSCP::File;

	for ($self::postfixConfig{'POSTFIX_CONF_FILE'}, $self::postfixConfig{'POSTFIX_MASTER_CONF_FILE'}) {
		my ($filename, $directories, $suffix) = fileparse($_);

		if(-f "$self->{bkpDir}/$filename$suffix.system"){
			$rs	= iMSCP::File->new(
				'filename' => "$self->{bkpDir}/$filename$suffix.system"
			)->copyFile(
				$_
			);
			return $rs if $rs;
		}
	}

	0;
}

1;
