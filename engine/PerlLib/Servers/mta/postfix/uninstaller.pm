# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by internet Multi Server Control Panel
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

package Servers::mta::postfix::uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::File;
use File::Basename;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::SystemUser;
use Servers::mta::postfix;
use parent 'Common::SingletonClass';

sub _init
{
    my $self = shift;

    $self->{'mta'} = Servers::mta::postfix->getInstance();
    $self->{'cfgDir'} = $self->{'mta'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'vrlDir'} = "$self->{'cfgDir'}/imscp";
    $self->{'config'} = $self->{'mta'}->{'config'};
    $self;
}

sub uninstall
{
    my $self = shift;

    my $rs = $self->_restoreConfFile();
    $rs ||= $self->_buildAliasses();
    $rs ||= $self->_removeUsers();
    $rs ||= $self->_removeDirs();
}

sub _removeDirs
{
    my $self = shift;

    for my $file($self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}, $self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}) {
        my $rs = iMSCP::Dir->new( dirname => $file )->remove();
        return $rs if $rs;
    }

    0;
}

sub _removeUsers
{
    my $self = shift;

    iMSCP::SystemUser->new( force => 'yes' )->delSystemUser( $self->{'config'}->{'MTA_MAILBOX_UID_NAME'} );
}

sub _buildAliasses
{
    my $self = shift;

    my $rs = execute( "newaliases", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    error( "Error while executing newaliases command" ) if !$stderr && $rs;
    $rs;
}

sub _restoreConfFile
{
    my $self = shift;

    for my $file($self->{'config'}->{'POSTFIX_CONF_FILE'}, $self->{'config'}->{'POSTFIX_MASTER_CONF_FILE'}) {
        my $filename = fileparse( $file );

        if (-f "$self->{'bkpDir'}/$filename.system") {
            my $rs = iMSCP::File->new( filename => "$self->{'bkpDir'}/$filename.system" )->copyFile( $file );
            return $rs if $rs;
        }
    }

    0;
}

1;
__END__
