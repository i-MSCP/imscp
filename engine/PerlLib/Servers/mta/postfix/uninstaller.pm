=head1 NAME

 Servers::mta::postfix::uninstaller - i-MSCP Postfix MTA server uninstaller implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by internet Multi Server Control Panel
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
use File::Basename;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dir;
use iMSCP::Execute qw/ execute /;
use iMSCP::File;
use iMSCP::SystemUser;
use Servers::mta::postfix;
use Try::Tiny;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Postfix MTA server uninstaller implementation.

=head1 PUBLIC METHODS

=over 4

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ( $self ) = @_;

    my $rs = $self->_restoreConffiles();
    $rs ||= $self->_buildAliasesFile();
    $rs ||= $self->_removeUser();
    $rs ||= $self->_removeFiles();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::mta::postfix::uninstaller

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'mta'} = Servers::mta::postfix->getInstance();
    $self->{'config'} = $self->{'mta'}->{'config'};
    $self;
}

=item _restoreConffiles( )

 Restore configuration files

 Return int 0 on success, other on failure

=cut

sub _restoreConffiles
{
    return 0 unless -d "/etc/postfix";

    for my $file ( '/usr/share/postfix/main.cf.debian', '/usr/share/postfix/master.cf.dist' ) {
        next unless -f $file;
        my $rs = iMSCP::File->new( filename => $file )->copyFile( '/etc/postfix/' . basename( $file ), { preserve => 'no' } );
        return $rs if $rs;
    }

    0;
}

=item _buildAliasesFile( )

 Build /etc/aliases file
 
 Return int 0 on success, other on failure

=cut

sub _buildAliasesFile
{
    my $rs = execute( 'newaliases', \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item _removeUser( )

 Remove user

 Return int 0 on success, other on failure

=cut

sub _removeUser
{
    iMSCP::SystemUser->new( force => 'yes' )->delSystemUser( $_[0]->{'config'}->{'MTA_MAILBOX_UID_NAME'} );
}

=item _removeFiles( )

 Remove files

 Return int 0 on success, other or die on failure

=cut

sub _removeFiles
{
    my ( $self ) = @_;

    try {
        for my $dir ( $self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}, $self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'} ) {
            iMSCP::Dir->new( dirname => $dir )->remove();
        }
        0;
    } catch {
        error( $_ );
        1;
    };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
