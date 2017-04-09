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

package Servers::po::courier::uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Mount qw/ removeMountEntry umount /;
use iMSCP::TemplateParser;
use Servers::mta;
use Servers::po::courier;
use Servers::sqld;
use parent 'Common::SingletonClass';

sub _init
{
    my $self = shift;

    $self->{'config'} = Servers::po::courier->getInstance( )->{'config'};
    $self->{'mta'} = Servers::mta->factory( );
    $self;
}

sub uninstall
{
    my $self = shift;

    my $rs = $self->_umountAuthdaemonSocketDir( );
    $rs ||= $self->_removeSqlUser( );
    $rs ||= $self->_removeConfig( );
}

sub _umountAuthdaemonSocketDir
{
    my $self = shift;

    my $fsFile = File::Spec->canonpath( "$self->{'mta'}->{'config'}->{'POSTFIX_QUEUE_DIR'}/private/authdaemon" );
    my $rs = removeMountEntry( qr%.*?[ \t]+\Q$fsFile\E(?:/|[ \t]+)[^\n]+% );
    $rs ||= umount( $fsFile );
    $rs ||= iMSCP::Dir->new( dirname => $fsFile )->remove( );
}

sub _removeSqlUser
{
    my $self = shift;

    my $sqlServer = Servers::sqld->factory( );

    for ($main::imscpConfig{'DATABASE_USER_HOST'}, $main::imscpConfig{'BASE_SERVER_IP'}, 'localhost', '127.0.0.1',
        '%') {
        next unless $_;

        if ($self->{'config'}->{'AUTHDAEMON_DATABASE_USER'}) {
            $sqlServer->dropUser( $self->{'config'}->{'AUTHDAEMON_DATABASE_USER'}, $_ );
        }

        if (exists $self->{'config'}->{'SALS_DATABASE_USER'}) {
            $sqlServer->dropUser( $self->{'config'}->{'SALS_DATABASE_USER'}, $_ );
        }
    }

    0;
}

sub _removeConfig
{
    my $self = shift;

    if (-f "$self->{'config'}->{'COURIER_CONF_DIR'}/imapd") {
        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'COURIER_CONF_DIR'}/imapd" );
        my $fileContent = $file->get( );
        unless (defined $fileContent) {
            error( sprintf( "Couldn't read %s file", $file->{'filename'} ) );
            return 1;
        }

        $fileContent = replaceBloc(
            "\n# Servers::po::courier::installer - BEGIN\n",
            "# Servers::po::courier::installer - ENDING\n",
            '',
            $fileContent
        );

        my $rs = $file->set( $fileContent );
        $rs ||= $file->save( );
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    if (-f "$self->{'config'}->{'SASL_CONF_DIR'}/smtpd.conf") {
        my $rs = iMSCP::File->new( filename => "$self->{'config'}->{'SASL_CONF_DIR'}/smtpd.conf" )->delFile( );
        return $rs if $rs;
    }

    if (-f '/etc/tmpfiles.d/courier-authdaemon.conf') {
        my $rs = iMSCP::File->new( filename => '/etc/tmpfiles.d/courier-authdaemon.conf' )->delFile( );
        return $rs if $rs;
    }

    if (-f $self->{'config'}->{'QUOTA_WARN_MSG_PATH'}) {
        my $rs = iMSCP::File->new( filename => $self->{'config'}->{'QUOTA_WARN_MSG_PATH'} )->delFile( );
        return $rs if $rs;
    }

    0;
}

1;
__END__
