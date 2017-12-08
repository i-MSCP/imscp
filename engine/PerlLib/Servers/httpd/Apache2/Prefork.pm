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
use iMSCP::TemplateParser qw/ replaceBloc /;
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
    $self->{'eventManager'}->register( 'afterHttpdBuildConfFile', sub { $self->_cleanTemplate( @_ ) }, -999 );
    $self;
}

=item _setupModules( )

 See Servers::httpd::Apache2::Abstract::_setupModules()

=cut

sub _setupModules
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSetupApache2Modules', __PACKAGE__ );
    $rs ||= $self->disableModules( qw/ mpm_event mpm_itk mpm_worker cgid / );
    $rs ||= $self->enableModules(
        qw/mpm_prefork access_compat alias auth_basic auth_digest authn_core authn_file authz_core authz_groupfile authz_host authz_user autoindex
        cgi deflate dir env expires headers mime mime_magic negotiation proxy proxy_http rewrite ssl suexec version/
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterSetupApache2Modules', __PACKAGE__ );
}

=back

=head1 EVENT LISTENERS

=over 4

=item _cleanTemplate( \$tpl, $filename, \%moduleData )

 Event listener which is responsible to cleanup production configuration files

 Param string \$tpl Template content
 Param string $name Template name
 Param hash \%moduleData Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0

=cut

sub _cleanTemplate
{
    my (undef, $tpl, $name, $moduleData) = @_;

    if ( $moduleData->{'SKIP_TEMPLATE_CLEANER'} ) {
        delete $moduleData->{'SKIP_TEMPLATE_CLEANER'};
        return 0;
    }

    if ( $name eq 'domain.tpl' ) {
        if ( $moduleData->{'VHOST_TYPE'} !~ /fwd/ ) {
            unless ( $moduleData->{'CGI_SUPPORT'} eq 'yes' ) {
                ${$tpl} = replaceBloc( "# SECTION suexec BEGIN.\n", "# SECTION suexec END.\n", '', ${$tpl} );
                ${$tpl} = replaceBloc( "# SECTION cgi BEGIN.\n", "# SECTION cgi END.\n", '', ${$tpl} );
            }

            ${$tpl} = replaceBloc( "# SECTION itk BEGIN.\n", "# SECTION itk END.\n", '', ${$tpl} );
        } elsif ( $moduleData->{'FORWARD'} ne 'no' ) {
            if ( $moduleData->{'FORWARD_TYPE'} eq 'proxy' && ( !$moduleData->{'HSTS_SUPPORT'} || $moduleData->{'VHOST_TYPE'} =~ /ssl/ ) ) {
                ${$tpl} = replaceBloc( "# SECTION std_fwd BEGIN.\n", "# SECTION std_fwd END.\n", '', ${$tpl} );
                if ( index( $moduleData->{'FORWARD'}, 'https' ) != 0 ) {
                    ${$tpl} = replaceBloc( "# SECTION ssl_proxy BEGIN.\n", "# SECTION ssl_proxy END.\n", '', ${$tpl} );
                }
            } else {
                ${$tpl} = replaceBloc( "# SECTION proxy_fwd BEGIN.\n", "# SECTION proxy_fwd END.\n", '', ${$tpl} );
            }
        } else {
            ${$tpl} = replaceBloc( "# SECTION proxy_fwd BEGIN.\n", "# SECTION proxy_fwd END.\n", '', ${$tpl} );
        }
    }

    ${$tpl} =~ s/^\s*(?:[#;].*)?\n//gm;
    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
