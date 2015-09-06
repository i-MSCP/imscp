=head1 NAME

 Servers::named::bind::uninstaller - i-MSCP Bind9 Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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
	my $self = shift;

	my $rs = $self->_restoreConfFiles();
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

 Restore system configuration files

 Return int 0 on success, other on failure

=cut

sub _restoreConfFiles
{
	my $self = shift;

	if(-d $self->{'config'}->{'BIND_CONF_DIR'}) {
		for my $conffile('BIND_CONF_DEFAULT_FILE', 'BIND_CONF_FILE', 'BIND_LOCAL_CONF_FILE', 'BIND_OPTIONS_CONF_FILE') {
			if(defined $self->{'config'}->{$conffile}) {
				my $basename = basename($self->{'config'}->{$conffile});

				if(-f "$self->{'bkpDir'}/$basename.system") {
					iMSCP::File->new( filename => "$self->{'bkpDir'}/$basename.system" )->copyFile(
						$self->{'config'}->{$conffile}
					);

					iMSCP::File->new( filename => $self->{'config'}->{$conffile} )->mode(0644);
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
	my $self = shift;

	my $rs = execute("rm -f $self->{'config'}->{'BIND_DB_DIR'}/*.db", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = execute("rm -f $self->{'wrkDir'}/*", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	iMSCP::Dir->new( dirname => "$self->{'config'}->{'BIND_DB_DIR'}/slave" )->remove();
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
