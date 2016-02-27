=head1 NAME

 Servers::ftpd::vsftpd::uninstaller - i-MSCP VsFTPd Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2016 by Laurent Declercq <l.declercq@nuxwin.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package Servers::ftpd::vsftpd::uninstaller;

use strict;
use warnings;
use File::Basename;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use Servers::ftpd::vsftpd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Uninstaller for the i-MSCP VsFTPd Server implementation.

=head1 PUBLIC METHODS

=over 4

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, die on failure

=cut

sub uninstall
{
	(shift)->_restoreDefaultConf();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::ftpd::vsftpd::uninstaller

=cut

sub _init
{
	my $self = shift;

	$self->{'ftpd'} = Servers::ftpd::vsftpd->getInstance();
	$self->{'cfgDir'} = $self->{'ftpd'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'config'} = $self->{'ftpd'}->{'config'};
	$self;
}

=item _restoreDefaultConf()

 Restore default configuration

 Return int 0 on success, other on failure

=cut

sub _restoreDefaultConf
{
	my $self = shift;

	for my $conffile($self->{'config'}->{'FTPD_CONF_FILE'}, $self->{'config'}->{'FTPD_PAM_CONF_FILE'}) {
		my $basename = basename($conffile);

		if(-f "$self->{'bkpDir'}/$basename.system") {
			my $rs = iMSCP::File->new( filename => "$self->{'bkpDir'}/$basename.system" )->copyFile($conffile);
			return $rs if $rs;
		}
	}

	iMSCP::Dir->new( dirname => $self->{'config'}->{'FTPD_USER_CONF_DIR'} );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
