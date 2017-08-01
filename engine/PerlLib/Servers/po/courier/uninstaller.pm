=head1 NAME

 Servers::po::courier::uninstaller - i-MSCP Courier server uninstaller

=cut

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
use iMSCP::Config;
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::Mount qw/ removeMountEntry umount /;
use iMSCP::SystemUser;
use iMSCP::TemplateParser;
use Servers::mta;
use Servers::po::courier;
use Servers::sqld;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Courier server uninstaller.

=head1 PUBLIC METHODS

=over 4

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, die on failure

=cut

sub uninstall
{
    my ($self) = @_;

    # In setup context, processing must be delayed, else we won't be able to connect to SQL server
    if ( $main::execmode eq 'setup' ) {
        return iMSCP::EventManager->getInstance()->register(
            'afterSqldPreinstall',
            sub {
                my $rs ||= $self->_dropSqlUser();
                $rs ||= $self->_removeConfig();
            }
        );
    }

    my $rs = $self->_dropSqlUser();
    $rs ||= $self->_removeConfig();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::po::courier::uninstaller

=cut

sub _init
{
    my ($self) = @_;

    $self->{'po'} = Servers::po::courier->getInstance();
    $self->{'mta'} = Servers::mta->factory();
    $self->{'cfgDir'} = $self->{'po'}->{'cfgDir'};
    $self->{'config'} = $self->{'po'}->{'config'};
    $self;
}

=item _dropSqlUser( )

 Drop SQL user

 Return int 0 on success, other on failure

=cut

sub _dropSqlUser
{
    my ($self) = @_;

    # In setup context, take value from old conffile, else take value from current conffile
    my $dbUserHost = ( $main::execmode eq 'setup' )
        ? $main::imscpOldConfig{'DATABASE_USER_HOST'} : $main::imscpConfig{'DATABASE_USER_HOST'};

    return 0 unless $self->{'config'}->{'AUTHDAEMON_DATABASE_USER'} && $dbUserHost;

    local $@;
    eval { Servers::sqld->factory()->dropUser( $self->{'config'}->{'AUTHDAEMON_DATABASE_USER'}, $dbUserHost ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _removeConfig( )

 Remove configuration

 Return int 0 on success, other on failure

=cut

sub _removeConfig
{
    my ($self) = @_;

    # Umount the courier-authdaemond rundir from the Postfix chroot
    my $fsFile = File::Spec->canonpath(
        "$self->{'mta'}->{'config'}->{'POSTFIX_QUEUE_DIR'}/$self->{'config'}->{'AUTHLIB_SOCKET_DIR'}"
    );
    my $rs = removeMountEntry( qr%.*?[ \t]+\Q$fsFile\E(?:/|[ \t]+)[^\n]+% );
    $rs ||= umount( $fsFile );
    return $rs if $rs;

    iMSCP::Dir->new( dirname => $fsFile )->remove();

    # Remove the `postfix' user from the `mail' group
    $rs = iMSCP::SystemUser->new()->removeFromGroup(
        $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'}, $self->{'mta'}->{'config'}->{'POSTFIX_USER'}
    );
    return $rs if $rs;

    # Remove i-MSCP configuration stanza from the courier-imap daemon configuration file
    if ( -f "$self->{'config'}->{'COURIER_CONF_DIR'}/imapd" ) {
        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'COURIER_CONF_DIR'}/imapd" );
        my $fileContent = $file->get();
        unless ( defined $fileContent ) {
            error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
            return 1;
        }

        $fileContent = replaceBloc(
            qr/(:?^\n)?# Servers::po::courier::installer - BEGIN\n/m,
            qr/# Servers::po::courier::installer - ENDING\n/,
            '',
            $fileContent
        );

        $file->set( $fileContent );

        $rs = $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    # Remove the configuration file for SASL
    if ( -f "$self->{'config'}->{'SASL_CONF_DIR'}/smtpd.conf" ) {
        $rs = iMSCP::File->new( filename => "$self->{'config'}->{'SASL_CONF_DIR'}/smtpd.conf" )->delFile();
        return $rs if $rs;
    }

    # Remove the systemd-tmpfiles file
    if ( -f '/etc/tmpfiles.d/courier-authdaemon.conf' ) {
        $rs = iMSCP::File->new( filename => '/etc/tmpfiles.d/courier-authdaemon.conf' )->delFile();
        return $rs if $rs;
    }

    # Remove the quota warning script
    if ( -f $self->{'config'}->{'QUOTA_WARN_MSG_PATH'} ) {
        $rs = iMSCP::File->new( filename => $self->{'config'}->{'QUOTA_WARN_MSG_PATH'} )->delFile();
        return $rs if $rs;
    }

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
