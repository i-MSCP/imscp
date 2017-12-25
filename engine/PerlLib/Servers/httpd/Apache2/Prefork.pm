=head1 NAME

 Servers::httpd::Apache2::Event - i-MSCP Apache2 (MPM Prefork) server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Servers::httpd::Apache2::Prefork;

use strict;
use warnings;
use iMSCP::TemplateParser qw/ replaceBlocByRef /;
use parent 'Servers::httpd::Apache2::Abstract';

=head1 DESCRIPTION

 i-MSCP Apache2 (MPM Prefork) server implementation.

=head1 PRIVATE METHODS

=over 4

=item _init( )

 See Servers::httpd::Apache2::Abstract::_init()

=cut

sub _init
{
    my ($self) = @_;

    $self->SUPER::_init();
    $self->{'eventManager'}->register( 'afterApache2BuildConfFile', $self, -999 );
    $self;
}

=back

=head1 EVENT LISTENERS

=over 4

=item afterApache2BuildConfFile( $apache2Server, \$cfgTpl, $filename, \$trgFile, \%moduleData, \%apache2ServerData, \%apache2ServerConfig, \%parameters )

 Event listener that cleanup production files

 Param scalar $apache2Server Servers::httpd::Apache2::Prefork instance
 Param scalar \$scalar Reference to Apache2 conffile
 Param string $filename Apache2 template name
 Param scalar \$trgFile Target file path
 Param hashref \%moduleData Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Param hashref \%apache2ServerData Apache2 server data
 Param hashref \%apache2ServerConfig Apache2 server data
 Param hashref \%parameters OPTIONAL Parameters:
  - user  : File owner (default: root)
  - group : File group (default: root
  - mode  : File mode (default: 0644)
  - cached : Whether or not loaded file must be cached in memory
 Return int 0 on success, other on failure

=cut

sub afterApache2BuildConfFile
{
    my (undef, $cfgTpl, $filename, undef, $moduleData, $apache2ServerData) = @_;

    return $apache2ServerData->{'SKIP_TEMPLATE_CLEANER'} = 0 if $apache2ServerData->{'SKIP_TEMPLATE_CLEANER'};

    if ( $filename eq 'domain.tpl' ) {
        if ( index( $apache2ServerData->{'VHOST_TYPE'}, 'fwd' ) == -1 ) {
            replaceBlocByRef( "# SECTION itk BEGIN.\n", "# SECTION itk END.\n", '', $cfgTpl );

            if ( $moduleData->{'CGI_SUPPORT'} ne 'yes' ) {
                replaceBlocByRef( "# SECTION cgi BEGIN.\n", "# SECTION cgi END.\n", '', $cfgTpl );
            }
        } elsif ( $moduleData->{'FORWARD'} ne 'no' ) {
            if ( $moduleData->{'FORWARD_TYPE'} eq 'proxy' && ( !$moduleData->{'HSTS_SUPPORT'} || index( $apache2ServerData->{'VHOST_TYPE'},
                'ssl' ) != -1 ) ) {
                replaceBlocByRef( "# SECTION std_fwd BEGIN.\n", "# SECTION std_fwd END.\n", '', $cfgTpl );

                if ( index( $moduleData->{'FORWARD'}, 'https' ) != 0 ) {
                    replaceBlocByRef( "# SECTION ssl_proxy BEGIN.\n", "# SECTION ssl_proxy END.\n", '', $cfgTpl );
                }
            } else {
                replaceBlocByRef( "# SECTION proxy_fwd BEGIN.\n", "# SECTION proxy_fwd END.\n", '', $cfgTpl );
            }
        } else {
            replaceBlocByRef( "# SECTION proxy_fwd BEGIN.\n", "# SECTION proxy_fwd END.\n", '', $cfgTpl );
        }
    }

    ${$cfgTpl} =~ s/^\s*(?:[#;].*)?\n//gm;
    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
