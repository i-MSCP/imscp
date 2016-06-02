=head1 NAME

Package::Webmail::RainLoop::Uninstaller - i-MSCP RainLoop package uninstaller

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Package::Webmail::RainLoop::Uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Database;
use Package::FrontEnd;
use Package::Webmail::RainLoop::RainLoop;
use Servers::sqld;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP RainLoop package uninstaller.

=head1 PUBLIC METHODS

=over 4

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my $self = shift;

    my $rs = $self->_removeSqlUser();
    $rs ||= $self->_removeSqlDatabase();
    $rs ||= $self->_unregisterConfig();
    $rs ||= $self->_removeFiles();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Package::Webmail::RainLoop::Uninstaller

=cut

sub _init
{
    my $self = shift;

    $self->{'rainloop'} = Package::Webmail::RainLoop::RainLoop->getInstance();
    $self->{'frontend'} = Package::FrontEnd->getInstance();
    $self->{'db'} = iMSCP::Database->factory();
    $self;
}

=item _removeSqlUser()

 Remove SQL user

 Return int 0 on success, other on failure

=cut

sub _removeSqlUser
{
    my $self = shift;

    my $sqlServer = Servers::sqld->factory();

    return 0 unless $self->{'rainloop'}->{'config'}->{'DATABASE_USER'};

    for my $host(
        $main::imscpConfig{'DATABASE_USER_HOST'}, $main::imscpConfig{'BASE_SERVER_IP'}, 'localhost', '127.0.0.1', '%'
    ) {
        next unless $host;
        $sqlServer->dropUser( $self->{'rainloop'}->{'config'}->{'DATABASE_USER'}, $host );
    }

    0;
}

=item _removeSqlDatabase()

 Remove database

 Return int 0

=cut

sub _removeSqlDatabase
{
    my $self = shift;

    my $dbName = $self->{'db'}->quoteIdentifier( $main::imscpConfig{'DATABASE_NAME'}.'_rainloop' );
    $self->{'db'}->doQuery( 'dummy', "DROP DATABASE IF EXISTS $dbName" );
    0;
}

=item _unregisterConfig

 Remove include directive from frontEnd vhost files

 Return int 0 on success, other on failure

=cut

sub _unregisterConfig
{
    my $self = shift;

    for my $vhostFile('00_master.conf', '00_master_ssl.conf') {
        next unless -f "$self->{'frontend'}->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$vhostFile";

        my $file = iMSCP::File->new(
            filename => "$self->{'frontend'}->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$vhostFile"
        );
        my $fileContent = $file->get();
        unless (defined $fileContent) {
            error( sprintf( 'Could not read %s file', $file->{'filename'} ) );
            return 1;
        }

        $fileContent =~ s/[\t ]*include imscp_rainloop.conf;\n//;

        my $rs = $file->set( $fileContent );
        $rs ||= $file->save();
        return $rs if $rs;
    }

    $self->{'frontend'}->{'reload'} = 1;
    0;
}

=item _removeFiles()

 Remove files

 Return int 0

=cut

sub _removeFiles
{
    my $self = shift;

    my $rs = iMSCP::Dir->new( dirname => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/rainloop" )->remove();
    $rs ||= iMSCP::Dir->new( dirname => $self->{'rainloop'}->{'cfgDir'} )->remove();
    return $rs if $rs;

    if (-f "$self->{'frontend'}->{'config'}->{'HTTPD_CONF_DIR'}/imscp_rainloop.conf") {
        $rs = iMSCP::File->new(
            filename => "$self->{'frontend'}->{'config'}->{'HTTPD_CONF_DIR'}/imscp_rainloop.conf"
        )->delFile();
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
