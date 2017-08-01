=head1 NAME

 Package::Webmail::Roundcube::Uninstaller - i-MSCP Roundcube package uninstaller

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

package Package::Webmail::Roundcube::Uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Database;
use Package::FrontEnd;
use Package::Webmail::Roundcube::Roundcube;
use Servers::sqld;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Roundcube package uninstaller.

=head1 PUBLIC METHODS

=over 4

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    return 0 unless %{$self->{'config'}};

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

 Return Package::Webmail::Roundcube::Uninstaller

=cut

sub _init
{
    my ($self) = @_;

    $self->{'frontend'} = Package::FrontEnd->getInstance();
    $self->{'roundcube'} = Package::Webmail::Roundcube::Roundcube->getInstance();
    $self->{'db'} = iMSCP::Database->factory();
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/roundcube";
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
    my ($self) = @_;

    my $sqlServer = Servers::sqld->factory();
    return 0 unless $self->{'config'}->{'DATABASE_USER'};

    for(
        $main::imscpConfig{'DATABASE_USER_HOST'}, $main::imscpConfig{'BASE_SERVER_IP'}, 'localhost', '127.0.0.1', '%'
    ) {
        next unless $_;
        $sqlServer->dropUser( $self->{'config'}->{'DATABASE_USER'}, $_ );
    }

    0;
}

=item _removeSqlDatabase( )

 Remove database

 Return int 0

=cut

sub _removeSqlDatabase
{
    my ($self) = @_;

    local $@;
    eval {
        my $dbh = $self->{'db'}->getRawDb();
        $dbh->{'RaiseError'} = 1;
        $dbh->do(
            'DROP DATABASE IF EXISTS ' . $dbh->quote_identifier( $main::imscpConfig{'DATABASE_NAME'} . '_roundcube' )
        );
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
    my ($self) = @_;

    return 0 unless -f "$self->{'frontend'}->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master.conf";

    my $file = iMSCP::File->new(
        filename => "$self->{'frontend'}->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master.conf"
    );
    my $fileContentRef = $file->getAsRef();
    unless ( defined $fileContentRef ) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
        return 1;
    }

    $fileContentRef =~ s/[\t ]*include imscp_roundcube.conf;\n//;

    my $rs = $file->save();
    return $rs if $rs;

    $self->{'frontend'}->{'reload'} = 1;
    0;
}

=item _removeFiles( )

 Remove files

 Return int 0

=cut

sub _removeFiles
{
    my ($self) = @_;

    iMSCP::Dir->new( dirname => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail" )->remove();

    if ( -f "$self->{'frontend'}->{'config'}->{'HTTPD_CONF_DIR'}/imscp_roundcube.conf" ) {
        my $rs = iMSCP::File->new(
            filename => "$self->{'frontend'}->{'config'}->{'HTTPD_CONF_DIR'}/imscp_roundcube.conf"
        )->delFile();
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
