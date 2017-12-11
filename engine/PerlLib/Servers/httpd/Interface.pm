=head1 NAME

 Servers::httpd::Interface - Interface for i-MSCP httpd servers implementations

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

package Servers::httpd::Interface;

use strict;
use warnings;

=head1 DESCRIPTION

 Interface for i-MSCP httpd servers implementations.

=head1 PUBLIC METHODS

=over 4

=item addUser( \%moduleData )

 Process addUser tasks

 Param hashref \%moduleData User data as provided by User module
 Return int 0 on success, other on failure

=cut

sub addUser
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the addUser() method', ref $self ));
}

=item deleteUser( \%moduleData )

 Process deleteUser tasks

 Param hashref \%moduleData User data as provided by User module
 Return int 0 on success, other on failure

=cut

sub deleteUser
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the deleteUser() method', ref $self ));
}

=item addDmn( \%moduleData )

 Process addDmn tasks

 Param hashref \%moduleData Domain data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the addDmn() method', ref $self ));
}

=item restoreDmn( \%moduleData )

 Process restoreDmn tasks

 Param hashref \%moduleData Domain data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub restoreDmn
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the restoreDmn() method', ref $self ));
}

=item disableDmn( \%moduleData )

 Process disableDmn tasks

 Param hashref \%moduleData Domain data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub disableDmn
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the disableDmn() method', ref $self ));
}

=item deleteDmn( \%moduleData )

 Process deleteDmn tasks

 Param hashref \%moduleData Domain data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the deleteDmn() method', ref $self ));
}

=item addSub( \%moduleData )

 Process addSub tasks

 Param hashref \%moduleData Subdomain data as provided by Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the addSub() method', ref $self ));
}

=item restoreSub( \%moduleData )

 Process restoreSub tasks

 Param hashref \%moduleData Subdomain data as provided by Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub restoreSub
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the restoreSub() method', ref $self ));
}

=item disableSub( \%moduleData )

 Process disableSub tasks

 Param hashref \%moduleData Subdomain data as provided by Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub disableSub
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the disableSub() method', ref $self ));
}

=item deleteSub( \%moduleData )

 Process deleteSub tasks

 Param hashref \%moduleData Subdomain data as provided by Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the deleteSub() method', ref $self ));
}

=item addHtpasswd( \%moduleData )

 Process addHtpasswd tasks

 Param hashref \%moduleData Htpasswd entry data as provided by htpasswd module
 Return int 0 on success, other on failure

=cut

sub addHtpasswd
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the addHtpasswd() method', ref $self ));
}

=item deleteHtpasswd( \%moduleData )

 Process deleteHtpasswd tasks

 Param hashref \%moduleData Htpasswd entry data as provided by Htpasswd module
 Return int 0 on success, other on failure

=cut

sub deleteHtpasswd
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the deleteHtpasswd() method', ref $self ));
}

=item addHtgroup( \%moduleData )

 Process addHtgroup tasks

 Param hashref \%moduleData Htgroup data as provided by Htgroup module
 Return int 0 on success, other on failure

=cut

sub addHtgroup
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the addHtgroup() method', ref $self ));
}

=item deleteHtgroup( \%moduleData )

 Process deleteHtgroup tasks

 Param hashref \%moduleData Htgroup data as provided by Htgroup module
 Return int 0 on success, other on failure

=cut

sub deleteHtgroup
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the deleteHtgroup() method', ref $self ));
}

=item addHtaccess( \%moduleData )

 Process addHtaccess tasks

 Param hashref \%moduleData Htaccess data as provided by Htaccess module
 Return int 0 on success, other on failure

=cut

sub addHtaccess
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the addHtaccess() method', ref $self ));
}

=item deleteHtaccess( \%moduleData )

 Process deleteHtaccess tasks

 Param hashref \%moduleData Htaccess data as provided by Htaccess module
 Return int 0 on success, other on failure

=cut

sub deleteHtaccess
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the deleteHtaccess() method', ref $self ));
}

=item buildConfFile( $srcFile, $trgFile, [, \%moduleData = { } [, \%serverData [, \%parameters = { } ] ] ] )

 Build the given httpd configuration file

 Param string $srcFile Source file path relative to the i-MSCP httpd configuration directory
 Param string $trgFile Target file path
 Param hash \%data OPTIONAL Data as provided by Alias|Domain|SubAlias|Subdomain modules
 Param hash \%data OPTIONAL Server data (Runtime data have higher precedence than modules data)
 Param hash \%parameters OPTIONAL Parameters:
  - user  : File owner (default: root)
  - group : File group (default: root
  - mode  : File mode (default: 0644)
  - cache : Whether or not loaded file must be cached in memory
 Return int 0 on success, other on failure

=cut

sub buildConfFile
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the buildConfFile() method', ref $self ));
}

=item getTraffic( \%trafficDb )

 Get httpd traffic data

 Param hashref \%trafficDb Traffic database
 Die on failure

=cut

sub getTraffic
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the getTraffic() method', ref $self ));
}

=item getRunningUser( )

 Get user name under which the httpd server is running

 Return string User name under which the httpd server is running

=cut

sub getRunningUser
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the getRunningUser() method', ref $self ));
}

=item getRunningGroup( )

 Get group name under which the httpd server is running

 Return string Group name under which the httpd server is running

=cut

sub getRunningGroup
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the getRunningGroup() method', ref $self ));
}

=item enableSites( @sites )

 Enable the given httpd sites

 Param array @sites List of sites to enable
 Return int 0 on sucess, other on failure

=cut

sub enableSites
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the enableSites() method', ref $self ));
}

=item disableSites( @sites )

 Disable the given httpd sites

 Param array @sites List of sites to disable
 Return int 0 on sucess, other on failure

=cut

sub disableSites
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the disableSites() method', ref $self ));
}

=item enableModules( @modules )

 Enable the given httpd modules

 Param array $modules List of modules to enable
 Return int 0 on sucess, other on failure

=cut

sub enableModules
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the enableModules() method', ref $self ));
}

=item disableModules( @modules )

 Disable the given httpd modules

 Param array @modules List of modules to disable
 Return int 0 on sucess, other on failure

=cut

sub disableModules
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the disableModules() method', ref $self ));
}

=item enableConfs( @conffiles )

 Enable the given httpd configuration files

 Param array @conffiles List of configuration files to enable
 Return int 0 on sucess, other on failure

=cut

sub enableConfs
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the enableConfs() method', ref $self ));
}

=item disableConfs( @conffiles )

 Disable the given httpd configuration files

 Param array @conffiles Lilst of configuration files to disable
 Return int 0 on sucess, other on failure

=cut

sub disableConfs
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the disableConfs() method', ref $self ));
}

=item start( )

 Start httpd service

 Return int 0 on success, other on failure

=cut

sub start
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the start() method', ref $self ));
}

=item stop( )

 Stop httpd service

 Return int 0 on success, other on failure

=cut

sub stop
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the stop() method', ref $self ));
}

=item restart( )

 Restart httpd service

 Return int 0 on success, other on failure

=cut

sub restart
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the restart() method', ref $self ));
}

=item reload( )

 Reload httpd service

 Return int 0 on success, other on failure

=cut

sub reload
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the reload() method', ref $self ));
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
