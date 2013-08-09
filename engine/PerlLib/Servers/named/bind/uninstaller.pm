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
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::named::bind::uninstaller;

use strict;
use warnings;

use iMSCP::Debug;
use File::Basename;
use iMSCP::File;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/bind";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	tie %{$self->{'config'}}, 'iMSCP::Config', 'fileName' => "$self->{'cfgDir'}/bind.data", 'noerrors' => 1;

	0;
}

sub uninstall
{
	my $self = shift;

	$self->_restoreConfFiles();
}

sub _restoreConfFiles
{
	my $self = shift;
	my $rs = 0;

	for (
		$self->{'config'}->{'BIND_CONF_DEFAULT_FILE'},
		$self->{'config'}->{'BIND_CONF_FILE'},
		$self->{'config'}->{'BIND_LOCAL_CONF_FILE'},
		$self->{'config'}->{'BIND_OPTIONS_CONF_FILE'}
	) {
		next if !defined $_;
		my $filename = fileparse($_);

		if(-f "$self->{'bkpDir'}/$filename.system"){
			$rs	= iMSCP::File->new(
				'filename' => "$self->{'bkpDir'}/$filename.system"
			)->copyFile($_);

			# Config file mode is incorrect after copy from backup, therefore set it right
			$rs |= iMSCP::File->new('filename' => $_)->mode(0644);
			return $rs if $rs;
		}
	}

	0;
}

1;
