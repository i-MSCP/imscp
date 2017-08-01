=head1 NAME

 Package::FileManager::Net2ftp::Uninstaller - i-MSCP Net2ftp package uninstaller

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

package Package::FileManager::Net2ftp::Uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use Package::FrontEnd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Net2ftp package uninstaller.

=head1 PUBLIC METHODS

=over 4

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    my $rs = $self->_unregisterConfig();
    $rs ||= $self->_removeFiles();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Package::FileManager::Net2ftp::Uninstaller

=cut

sub _init
{
    my ($self) = @_;

    $self->{'frontend'} = Package::FrontEnd->getInstance();
    $self;
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

    ${$fileContentRef} =~ s/[\t ]*include imscp_net2ftp.conf;\n//;

    my $rs = $file->save();
    return $rs if $rs;

    $self->{'frontend'}->{'reload'} = 1;
    0;
}

=item _removeFiles( )

 Remove files

 Return int 0 on success, other on failure

=cut

sub _removeFiles
{
    my ($self) = @_;

    iMSCP::Dir->new( dirname => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/ftp" )->remove();

    return 0 unless -f "$self->{'frontend'}->{'config'}->{'HTTPD_CONF_DIR'}/imscp_net2ftp.conf";

    iMSCP::File->new(
        filename => "$self->{'frontend'}->{'config'}->{'HTTPD_CONF_DIR'}/imscp_net2ftp.conf"
    )->delFile();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
