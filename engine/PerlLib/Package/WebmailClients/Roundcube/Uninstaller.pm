=head1 NAME

 Package::WebmailClients::Roundcube::Uninstaller - i-MSCP Roundcube package uninstaller

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Package::WebmailClients::Roundcube::Uninstaller;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Composer;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Database;
use Package::FrontEnd;
use Package::WebmailClients::Roundcube::Roundcube;
use Servers::sqld;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Roundcube package uninstaller.

=head1 PUBLIC METHODS

=over 4

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ( $self ) = @_;

    return 0 unless %{ $self->{'config'} };

    eval {
        iMSCP::Composer->new(
            user          => $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'},
            composer_home => "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/.composer",
            composer_json => 'composer.json'
        )
            ->remove( 'imscp/roundcube' )
            ->dumpComposerJson();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    my $rs = $self->_removeSqlUser();
    $rs ||= $self->_removeSqlDatabase();
    $rs ||= $self->_unregisterConfig();
    $rs ||= $self->_removeFiles();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Package::WebmailClients::Roundcube::Uninstaller

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'frontend'} = Package::FrontEnd->getInstance();
    $self->{'roundcube'} = Package::WebmailClients::Roundcube::Roundcube->getInstance();
    $self->{'db'} = iMSCP::Database->factory();
    $self->{'cfgDir'} = "$::imscpConfig{'CONF_DIR'}/roundcube";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'config'} = $self->{'roundcube'}->{'config'};
    $self;
}

=item _removeSqlUser( )

 Remove SQL user

 Return int 0 on success, other on failure

=cut

sub _removeSqlUser
{
    my ( $self ) = @_;

    my $sqlServer = Servers::sqld->factory();
    return 0 unless $self->{'config'}->{'DATABASE_USER'};

    for my $host ( $::imscpConfig{'DATABASE_USER_HOST'}, $::imscpConfig{'BASE_SERVER_IP'}, 'localhost', '127.0.0.1', '%' ) {
        next unless length $host;
        $sqlServer->dropUser( $self->{'config'}->{'DATABASE_USER'}, $host );
    }

    0;
}

=item _removeSqlDatabase( )

 Remove database

 Return int 0

=cut

sub _removeSqlDatabase
{
    my ( $self ) = @_;

    local $@;
    eval {
        my $dbh = $self->{'db'}->getRawDb();
        local $dbh->{'RaiseError'} = TRUE;
        $dbh->do( 'DROP DATABASE IF EXISTS ' . $dbh->quote_identifier( $::imscpConfig{'DATABASE_NAME'} . '_roundcube' ));
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }
    0;
}

=item _unregisterConfig( )

 Remove include directive from frontEnd vhost files

 Return int 0 on success, other on failure

=cut

sub _unregisterConfig
{
    my ( $self ) = @_;

    return 0 unless -f "$self->{'frontend'}->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master.conf";

    my $file = iMSCP::File->new( filename => "$self->{'frontend'}->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master.conf" );
    my $fileContentRef = $file->getAsRef();
    return 1 unless defined $fileContentRef;

    $fileContentRef =~ s/[\t ]*include imscp_roundcube.conf;\n//;

    my $rs = $file->save();
    return $rs if $rs;

    $self->{'frontend'}->{'reload'} = TRUE;
    0;
}

=item _removeFiles( )

 Remove files

 Return int 0

=cut

sub _removeFiles
{
    my ( $self ) = @_;

    iMSCP::Dir->new( dirname => "$::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail" )->remove();

    if ( -f "$self->{'frontend'}->{'config'}->{'HTTPD_CONF_DIR'}/imscp_roundcube.conf" ) {
        my $rs = iMSCP::File->new( filename => "$self->{'frontend'}->{'config'}->{'HTTPD_CONF_DIR'}/imscp_roundcube.conf" )->delFile();
        return $rs if $rs;
    };

    iMSCP::Dir->new( dirname => $self->{'cfgDir'} )->remove();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
