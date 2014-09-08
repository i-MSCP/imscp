#!/usr/bin/perl

=head1 NAME

 Servers::named::bind::uninstaller - i-MSCP Bind9 Server implementation

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
use Servers::named::bind;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Uninstaller for the i-MSCP Bind9 Server implementation.

=head1 PUBLIC METHODS

=over 4

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->_disableLocalResolver();
	return $rs if $rs;

	$rs = $self->_restoreConfFiles();
	return $rs if $rs;

	$self->_deleteDbFiles();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::named::bind::uninstaller

=cut

sub _init
{
	my $self = $_[0];

	$self->{'named'} = Servers::named::bind->getInstance();

	$self->{'cfgDir'} = $self->{'named'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'vrlDir'} = "$self->{'cfgDir'}/imscp";

	$self->{'config'} = $self->{'named'}->{'config'};

	$self;
}

=item _disableLocalResolver()

 Disable local resolver

 Return int 0 on success, other on failure

=cut

sub _disableLocalResolver
{
	my ($stdout, $stderr);
	my $rs = execute(
		"$main::imscpConfig{'CMD_PERL'} $main::imscpConfig{'TOOLS_ROOT_DIR'}/imscp-local-dns-resolver stop",
		\$stdout,
		\$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _restoreConfFiles()

 Restore system configuration files

 Return int 0 on success, other on failure

=cut

sub _restoreConfFiles
{
	my $self = $_[0];

	if(-d $self->{'config'}->{'BIND_CONF_DIR'}) {
		for ('BIND_CONF_DEFAULT_FILE', 'BIND_CONF_FILE', 'BIND_LOCAL_CONF_FILE', 'BIND_OPTIONS_CONF_FILE') {
			if(defined $self->{'config'}->{$_}) {
				my $filename = fileparse($self->{'config'}->{$_});

				if(-f "$self->{'bkpDir'}/$filename.system") {
					my $rs = iMSCP::File->new(
						'filename' => "$self->{'bkpDir'}/$filename.system"
					)->copyFile(
						$self->{'config'}->{$_}
					);

					# Config file mode is incorrect after copy from backup, therefore set it right
					$rs = iMSCP::File->new('filename' => $self->{'config'}->{$_})->mode(0644);
					return $rs if $rs;
				}
			}
		}
	}

	0;
}

=item _deleteDbFiles()

 Delete i-MSCP db files

 Return int 0 on success, other on failure

=cut

sub _deleteDbFiles
{
	my $self = $_[0];

	my ($stdout, $stderr);
	my $rs = execute("$main::imscpConfig{'CMD_RM'} -f $self->{'config'}->{'BIND_DB_DIR'}/*.db", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = execute("$main::imscpConfig{'CMD_RM'} -f $self->{'wrkDir'}/*", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = iMSCP::Dir->new('dirname' => "$self->{'config'}->{'BIND_DB_DIR'}/slave")->remove();
	return $rs if $rs;

	0;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
