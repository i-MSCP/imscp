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
 
 The following events *MUST* be triggered:
  - before<SNAME>AddUser( \%moduleData )
  - after<SNAME>AddUser( \%moduleData )

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

 The following events *MUST* be triggered:
  - before<SNAME>DeleteUser( \%moduleData )
  - after<SNAME>DeleteUser( \%moduleData )

 Param hashref \%moduleData User data as provided by User module
 Return int 0 on success, other on failure

=cut

sub deleteUser
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the deleteUser() method', ref $self ));
}

=item addDomain( \%moduleData )

 Process addDomain tasks

 The following events *MUST* be triggered:
  - before<SNAME>AddDomain( \%moduleData )
  - after<SNAME>AddDomain( \%moduleData )

 Param hashref \%moduleData Domain data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub addDomain
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the addDomain() method', ref $self ));
}

=item restoreDmn( \%moduleData )

 Process restoreDmn tasks

 The following events *MUST* be triggered:
  - before<SNAME>RestoreDomain( \%moduleData )
  - after<SNAME>RestoreDomain( \%moduleData )

 Param hashref \%moduleData Domain data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub restoreDmn
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the restoreDmn() method', ref $self ));
}

=item disableDomain( \%moduleData )

 Process disableDomain tasks

 The following events *MUST* be triggered:
  - before<SNAME>DisableDomain( \%moduleData )
  - after<SNAME>DisableDomain( \%moduleData )

 Param hashref \%moduleData Domain data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub disableDomain
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the disableDomain() method', ref $self ));
}

=item deleteDomain( \%moduleData )

 Process deleteDomain tasks

 The following events *MUST* be triggered:
  - before<SNAME>DeleteDomain( \%moduleData )
  - after<SNAME>DeleteDomain( \%moduleData )

 Param hashref \%moduleData Domain data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub deleteDomain
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the deleteDomain() method', ref $self ));
}

=item addSubbdomain( \%moduleData )

 Process addSubbdomain tasks

 The following events *MUST* be triggered:
  - before<SNAME>DisableSubdomain( \%moduleData )
  - after<SNAME>DisableSubdomain( \%moduleData )

 Param hashref \%moduleData Subdomain data as provided by Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub addSubbdomain
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the addSubbdomain() method', ref $self ));
}

=item restoreSubdomain( \%moduleData )

 Process restoreSubdomain tasks

 The following events *MUST* be triggered:
  - before<SNAME>RestoreSubdomain( \%moduleData )
  - after<SNAME>RestoreSubdomain( \%moduleData )

 Param hashref \%moduleData Subdomain data as provided by Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub restoreSubdomain
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the restoreSubdomain() method', ref $self ));
}

=item disableSubdomain( \%moduleData )

 Process disableSubdomain tasks

 The following events *MUST* be triggered:
  - before<SNAME>DisableSubdomain( \%moduleData )
  - after<SNAME>DisableSubdomain( \%moduleData )

 Param hashref \%moduleData Subdomain data as provided by Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub disableSubdomain
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the disableSubdomain() method', ref $self ));
}

=item deleteSubdomain( \%moduleData )

 Process deleteSubdomain tasks

 The following events *MUST* be triggered:
  - before<SNAME>DeleteSubdomain( \%moduleData )
  - after<SNAME>DeleteSubdomain( \%moduleData )

 Param hashref \%moduleData Subdomain data as provided by Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub deleteSubdomain
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the deleteSubdomain() method', ref $self ));
}

=item addHtpasswd( \%moduleData )

 Process addHtpasswd tasks

 The following events *MUST* be triggered:
  - before<SNAME>AddHtpasswd( \%moduleData )
  - after<SNAME>AddHtpasswd( \%moduleData )

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

 The following events *MUST* be triggered:
  - before<SNAME>DeketeHtpasswd( \%moduleData )
  - after<SNAME>DeleteHtpasswd( \%moduleData )

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

 The following events *MUST* be triggered:
  - before<SNAME>AddHtgroup( \%moduleData )
  - after<SNAME>AddHtgroup( \%moduleData )

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

 The following events *MUST* be triggered:
  - before<SNAME>deleteHtgroup( \%moduleData )
  - after<SNAME>deleteHtgroup( \%moduleData )

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

 The following events *MUST* be triggered:
  - before<SNAME>AddHtaccess( \%moduleData )
  - after<SNAME>AddHtaccess( \%moduleData )

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

 The following events *MUST* be triggered:
  - before<SNAME>DeleteHtaccess( \%moduleData )
  - after<SNAME>DeleteHtaccess( \%moduleData )

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

 The following events *MUST* be triggered:
  -
  - onLoadTemplate( '<SNAME>', basename($srcFile), \$cfgTpl, \%moduleData, \%serverData, $self->{'config'} )
  - before<SNAME>BuildConfFile( \$cfgTpl, basename($srcFile), \$trgFile, \%moduleData, \%serverData, $self->{'config'}, $parameters )
  - after<SNAME>BuildConfFile( \$cfgTpl, basename($srcFile), \$trgFile, \%moduleData, \%serverData, $self->{'config'}, $parameters )

 Param string $srcFile Source file path relative to the i-MSCP httpd configuration directory
 Param string $trgFile Target file path
 Param hashref \%data OPTIONAL Data as provided by Alias|Domain|SubAlias|Subdomain modules
 Param hashref \%data OPTIONAL Server data (Runtime data have higher precedence than modules data)
 Param hashref \%parameters OPTIONAL Parameters:
  - user  : File owner (default: root)
  - group : File group (default: root
  - mode  : File mode (default: 0644)
  - cached : Whether or not loaded file must be cached in memory
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

 The following events *MUST* be triggered:
  - before<SNAME>Start()
  - after<SNAME>Start()

 Return int 0 on success, other on failure

=cut

sub start
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the start() method', ref $self ));
}

=item stop( )

 Stop httpd service

 The following events *MUST* be triggered:
  - before<SNAME>Stop()
  - after<SNAME>Stop()

 Return int 0 on success, other on failure

=cut

sub stop
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the stop() method', ref $self ));
}

=item restart( )

 Restart httpd service

 The following events *MUST* be triggered:
  - before<SNAME>Restart()
  - after<SNAME>Restart()

 Return int 0 on success, other on failure

=cut

sub restart
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the restart() method', ref $self ));
}

=item reload( )

 Reload httpd service

 The following events *MUST* be triggered:
  - before<SNAME>Reload()
  - after<SNAME>Reload()

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
