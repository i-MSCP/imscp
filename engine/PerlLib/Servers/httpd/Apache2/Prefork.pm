=head1 NAME

 Servers::httpd::Apache2::Event - i-MSCP Apache2 (MPM Prefork) server implementation

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
    $self->{'eventManager'}->register( 'afterApache2BuildConfFile', \&_cleanTemplate, -999 );
    $self;
}

=item _setupModules( )

 See Servers::httpd::Apache2::Abstract::_setupModules()

=cut

sub _setupModules
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2SetupModules', __PACKAGE__ );
    $rs ||= $self->disableModules( qw/ mpm_event mpm_itk mpm_worker cgid / );
    $rs ||= $self->enableModules(
        qw/mpm_prefork access_compat alias auth_basic auth_digest authn_core authn_file authz_core authz_groupfile authz_host authz_user autoindex
        cgi deflate dir env expires headers mime mime_magic negotiation proxy proxy_http rewrite ssl suexec version/
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2SetupModules', __PACKAGE__ );
}

=back

=head1 EVENT LISTENERS

=over 4

=item _cleanTemplate( \$cfgTpl, $filename, \%moduleData, \%serverData )

 Event listener which is responsible to cleanup production configuration files

 Param string \$cfgTpl Template content
 Param string $name Template name
 Param hashref \%moduleData Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Param hashref \%serverData Server data
 Return int 0

=cut

sub _cleanTemplate
{
    my ($cfgTpl, $name, $moduleData, $serverData) = @_;

    return $serverData->{'SKIP_TEMPLATE_CLEANER'} = 0 if $serverData->{'SKIP_TEMPLATE_CLEANER'};

    if ( $name eq 'domain.tpl' ) {
        if ( index( $serverData->{'VHOST_TYPE'}, 'fwd' ) == -1 ) {
            replaceBlocByRef( "# SECTION itk BEGIN.\n", "# SECTION itk END.\n", '', $cfgTpl );

            if ( $moduleData->{'CGI_SUPPORT'} ne 'yes' ) {
                replaceBlocByRef( "# SECTION cgi BEGIN.\n", "# SECTION cgi END.\n", '', $cfgTpl );
            }
        } elsif ( $moduleData->{'FORWARD'} ne 'no' ) {
            if ( $moduleData->{'FORWARD_TYPE'} eq 'proxy' && ( !$moduleData->{'HSTS_SUPPORT'} || index($serverData->{'VHOST_TYPE'}, 'ssl') != -1) ) {
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
