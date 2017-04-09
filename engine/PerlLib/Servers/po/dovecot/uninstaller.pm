# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Servers::po::dovecot::uninstaller;

use strict;
use warnings;
use iMSCP::Execute;
use iMSCP::File;
use Servers::mta;
use Servers::po::dovecot;
use Servers::sqld;
use parent 'Common::SingletonClass';

sub uninstall
{
    my $self = shift;

    my $rs = $self->_restoreConfFile( );
    $rs ||= $self->_dropSqlUser( );
}

sub _init
{
    my $self = shift;

    $self->{'po'} = Servers::po::dovecot->getInstance( );
    $self->{'mta'} = Servers::mta->factory( );
    $self->{'bkpDir'} = "$self->{'po'}->{'cfgDir'}/backup";
    $self->{'config'} = $self->{'po'}->{'config'};
    $self;
}

sub _restoreConfFile
{
    my $self = shift;

    return 0 unless -d $self->{'config'}->{'DOVECOT_CONF_DIR'};

    for ('dovecot.conf', 'dovecot-sql.conf') {
        next unless -f "$self->{'bkpDir'}/$_.system";

        my $rs = iMSCP::File->new( filename => "$self->{'bkpDir'}/$_.system" )->copyFile(
            "$self->{'config'}->{'DOVECOT_CONF_DIR'}/$_"
        );
        return $rs if $rs;
    }

    if (-f "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-sql.conf") {
        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-sql.conf" );
        my $rs ||= $file->owner(
            $main::imscpConfig{'ROOT_USER'}, $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'}
        );
        $rs ||= $file->mode( 0644 );
    }

    0;
}

sub _dropSqlUser
{
    my $self = shift;

    return 0 unless $self->{'config'}->{'DATABASE_USER'};

    my $sqlServer = Servers::sqld->factory( );

    for ('localhost', '%', $main::imscpConfig{'DATABASE_USER_HOST'}) {
        $sqlServer->dropUser( $self->{'config'}->{'DATABASE_USER'}, $_ );
    }

    0;
}

1;
__END__
