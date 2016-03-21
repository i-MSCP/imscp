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

package Servers::po::courier::uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::TemplateParser;
use Servers::po::courier;
use Servers::sqld;
use parent 'Common::SingletonClass';

sub _init
{
    my $self = shift;

    $self->{'po'} = Servers::po::courier->getInstance();
    $self->{'cfgDir'} = $self->{'po'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'config'} = $self->{'po'}->{'config'};
    $self;
}

sub uninstall
{
    my $self = shift;

    my $rs = $self->_removeSqlUser();
    $rs ||= $self->_restoreConfFile();
    $rs ||= $self->_authDaemon();
    $rs ||= $self->_deleteQuotaWarning();
}

=item _removeSqlUser()

 Remove any authdaemon SQL user

 Return int 0

=cut

sub _removeSqlUser
{
    my $self = shift;

    my $sqlServer = Servers::sqld->factory();

    for my $host(
        $main::imscpConfig{'DATABASE_USER_HOST'}, $main::imscpConfig{'BASE_SERVER_IP'}, 'localhost', '127.0.0.1', '%'
    ) {
        next unless $host;

        if ($self->{'config'}->{'AUTHDAEMON_DATABASE_USER'}) {
            $sqlServer->dropUser( $self->{'config'}->{'AUTHDAEMON_DATABASE_USER'}, $host );
        }

        if ($self->{'config'}->{'SALS_DATABASE_USER'}) {
            $sqlServer->dropUser( $self->{'config'}->{'SALS_DATABASE_USER'}, $host );
        }
    }

    0;
}

sub _restoreConfFile
{
    my $self = shift;

    if (-f "$self->{'bkpDir'}/$self->{'config'}->{'AUTHDAEMON_SNAME'}.system") {
        my $file = iMSCP::File->new( filename => "$self->{'bkpDir'}/$self->{'config'}->{'AUTHDAEMON_SNAME'}.system" );
        my $rs = $file->copyFile( "/etc/init.d/$self->{'config'}->{'AUTHDAEMON_SNAME'}" );
        return $rs if $rs;

        $file->{'filename'} = "/etc/init.d/$self->{'config'}->{'AUTHDAEMON_SNAME'}";
        $rs = $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
        $rs ||= $file->mode( 0755 );
        return $rs if $rs;
    }

    for my $filename(
        'authdaemonrc', 'authmysqlrc', $self->{'config'}->{'COURIER_IMAP_SSL'}, $self->{'config'}->{'COURIER_POP_SSL'}
    ) {
        if (-f "$self->{'bkpDir'}/$filename.system") {
            my $rs = iMSCP::File->new( filename => "$self->{'bkpDir'}/$filename.system" )->copyFile(
                "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/$filename"
            );
            return $rs if $rs;
        }
    }

    if (-f "$self->{'config'}->{'COURIER_CONF_DIR'}/imapd") {
        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'COURIER_CONF_DIR'}/imapd" );
        my $fileContent = $file->get();
        unless (defined $fileContent) {
            error( "Could not read $self->{'filename'}" );
            return 1;
        }

        $fileContent = replaceBloc(
            "\n# Servers::po::courier::installer - BEGIN\n",
            "# Servers::po::courier::installer - ENDING\n",
            '',
            $fileContent
        );

        my $rs = $file->set( $fileContent );
        $rs ||= $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    if (-f "$self->{'config'}->{'SASL_CONF_DIR'}/smtpd.conf") {
        my $rs = iMSCP::File->new( filename => "$self->{'config'}->{'SASL_CONF_DIR'}/smtpd.conf" )->delFile();
        return $rs if $rs;
    }

    0;
}

sub _authDaemon
{
    my $self = shift;

    my $file = iMSCP::File->new( filename => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/authdaemonrc" );
    my $rs = $file->owner( $self->{'config'}->{'AUTHDAEMON_USER'}, $self->{'config'}->{'AUTHDAEMON_GROUP'} );
    $rs ||= $file->mode( 0660 );
}

sub _deleteQuotaWarning
{
    my $self = shift;

    if (-f $self->{'config'}->{'QUOTA_WARN_MSG_PATH'}) {
        return iMSCP::File->new( filename => $self->{'config'}->{'QUOTA_WARN_MSG_PATH'} )->delFile();
    }

    0;
}

1;
__END__
