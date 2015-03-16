=head1 NAME

 Servers::sqld::mysql::uninstaller - i-MSCP MySQL server uninstaller implementation

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

package Servers::sqld::mysql::uninstaller;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::File;
use File::HomeDir;
use Servers::sqld::mysql;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP MySQL server uninstaller implementation.

=head1 PUBLIC METHODS

=over 4

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	$_[0]->_removeOptionsFile();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::sqld::mysql:uninstaller

=cut

sub _init
{
	my $self = $_[0];

	$self->{'sqld'} = Servers::sqld::mysql->getInstance();

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/mysql";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self;
}

=item _removeOptionsFile()

 Remove options file (root user .my.cnf file)

 Return int 0 on success, other on failure

=cut

sub _removeOptionsFile
{
	my $self = $_[0];

	my $homeDir = File::HomeDir->users_home($main::imscpConfig{'ROOT_USER'});

	if(defined $homeDir) {
		if(-f "$homeDir/.my.cnf") {
			return iMSCP::File->new( filename => "$homeDir/.my.cnf" )->delFile();
		}
	} else {
		error('Unable to find root user homedir');
		return 1;
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
