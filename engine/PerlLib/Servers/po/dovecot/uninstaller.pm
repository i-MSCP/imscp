# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by internet Multi Server Control Panel
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
use Servers::mta::postfix;
use Servers::po::dovecot;
use Servers::sqld;
use parent 'Common::SingletonClass';

sub uninstall
{
    my $self = shift;

    my $rs = $self->_restoreConfFile();
    $rs ||= $self->_dropSqlUser();
}

sub _init
{
    my $self = shift;

    $self->{'po'} = Servers::po::dovecot->getInstance();
    $self->{'mta'} = Servers::mta::postfix->getInstance();
    $self->{'cfgDir'} = $self->{'po'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'config'} = $self->{'po'}->{'config'};
    $self;
}

sub _restoreConfFile
{
    my $self = shift;

    for ('dovecot.conf', 'dovecot-sql.conf') {
        next unless -f "$self->{bkpDir}/$_.system";
        my $rs = iMSCP::File->new( filename => "$self->{bkpDir}/$_.system" )->copyFile(
            "$self->{'config'}->{'DOVECOT_CONF_DIR'}/$_"
        );
        return $rs if $rs;
    }

    my $file = iMSCP::File->new( filename => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-sql.conf" );
    my $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'mta'}->{'MTA_MAILBOX_GID_NAME'} );
    $rs ||= $file->mode( 0644 );
}

sub _dropSqlUser
{
    my $self = shift;

    my $sqlServer = Servers::sqld->factory();
    return 0 unless $self->{'config'}->{'DATABASE_USER'};

    for ('localhost', '%', $main::imscpConfig{'DATABASE_USER_HOST'}) {
        $sqlServer->dropUser( $self->{'config'}->{'DATABASE_USER'}, $_ );
    }

    0;
}

1;
__END__
