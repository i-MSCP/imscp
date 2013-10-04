#!/usr/bin/perl

=head1 NAME

 Servers::named::bind::uninstaller - i-MSCP Bind9 Server implementation

=cut

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
use iMSCP::Execute;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Uninstaller for the i-MSCP Bind9 Server implementation.

=head1 PUBLIC METHODS

=over 4

=item uninstall()

 Process uninstall tasks.

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $rs = $self->_restoreConfFiles();
	return $rs if $rs;

	$self->_deleteDbFiles();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance.

 Return Servers::named::bind::uninstaller

=cut

sub _init
{
	my $self = shift;

	$self->{'named'} = Servers::named::bind->getInstance();

	$self->{'cfgDir'} = $self->{'named'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'vrlDir'} = "$self->{'cfgDir'}/imscp";

	$self->{'config'} = $self->{'named'}->{'config'};

	$self;
}

=item _restoreConfFiles()

 Restore system configuration files.

 Return int 0 on success, other on failure

=cut

sub _restoreConfFiles
{
	my $self = shift;

	for (
		$self->{'config'}->{'BIND_CONF_DEFAULT_FILE'}, $self->{'config'}->{'BIND_CONF_FILE'},
		$self->{'config'}->{'BIND_LOCAL_CONF_FILE'}, $self->{'config'}->{'BIND_OPTIONS_CONF_FILE'}
	) {
		next if ! defined $_;
		my $filename = fileparse($_);

		if(-f "$self->{'bkpDir'}/$filename.system"){
			my $rs = iMSCP::File->new(
				'filename' => "$self->{'bkpDir'}/$filename.system"
			)->copyFile($_);

			# Config file mode is incorrect after copy from backup, therefore set it right
			$rs = iMSCP::File->new('filename' => $_)->mode(0644);
			return $rs if $rs;
		}
	}

	0;
}

=item _deleteDbFiles()

 Delete i-MSCP db files.

 Return int 0 on success, other on failure

=cut

sub _deleteDbFiles
{
	my $self = shift;
	my $stdout;
	my $stderr;

	my $rs = execute("$main::imscpConfig{'CMD_RM'} -f $self->{'config'}->{'BIND_DB_DIR'}/*.db", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs;

	$rs = iMSCP::Dir->new('dirname' => "$self->{'config'}->{'BIND_DB_DIR'}/slave")->remove()
		if -d "$self->{'config'}->{'BIND_DB_DIR'}/slave";
	return $rs if $rs;

	0;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
