=head1 NAME

 Package::WebsitesAltURLs - i-MSCP Alternative URLs fo client Websites

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

package Package::WebsitesAltURLs;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Servers::httpd Servers::named /;
use iMSCP::Boolean;
use iMSCP::EventManager;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Alternative URLs for client websites.
 
 Alternative URLs make the clients able to access their Websites (domains)
 through a control panel subdomains such as dmn1.panel.domain.tld.

 This feature is useful for clients who have not yet updated their DNS so that
 their domain name points to the IP address of the server that has been
 assigned to them.

=head1 PUBLIC METHODS

=over 4

=item registerInstallerDialogs( \@dialog )

 Register setup dialog

 Param arrayref \@dialog

=cut

sub registerInstallerDialogs
{
    my ( undef, $dialogs ) = @_;

    push @{ $dialogs }, \&_setupDialog;
    0;
}

=item getPriority( )

 Get package priority

 Return int package priority

=cut

sub getPriority
{
    0;
}

=item postaddDmn( \%data )

 Process postaddDmn tasks
 
 Param hashref \%data Domain data

=cut

sub postaddDmn
{
    my ( $self, $data ) = @_;

    $self->_addDnsRR( $data );
}

=item postdeleteDmn( \%data )

 Process postdeleteDmn tasks
 
 Param hashref \%data Domain data

=cut

sub postdeleteDmn
{
    my ( $self, $data ) = @_;

    $self->_deleteDnsRR( $data );
}

=item postaddSub( \%data )

 Process postaddSub tasks
 
 Param hashref \%data Subdomain data

=cut

sub postaddSub
{
    my ( $self, $data ) = @_;

    $self->_addDnsRR( $data );
}

=item postDeleteSub( \%data )

 Process postDeleteSub tasks
 
 Param hashref \%data Subdomain data

=cut

sub postDeleteSub
{
    my ( $self, $data ) = @_;

    $self->_deleteDnsRR( $data );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return void, die on failure

=cut

sub _init
{
    my ( $self ) = @_;

    iMSCP::EventManager->getInstance()->register( 'beforeHttpdBuildConfFile', \&_addServerAlias );
    $self;
}

=item _setupDialog( $dialog )

 Setup dialog - Ask for client Websites alternative URLs

 Param iMSCP::Dialog $dialog
 Return int 0 (NEXT), 30 (BACK), 50 (ESC)

=cut

sub _setupDialog
{
    my ( $dialog ) = @_;

    my $value = ::setupGetQuestion( 'CLIENT_WEBSITES_ALT_URLS' );

    if ( $main::reconfigure =~ /^(?:alt_urls_feature||all|forced)$/ || !grep ( $value eq $_, 'yes', 'no') ) {
        my $rs = $dialog->yesno( <<'EOF', $value eq 'no', TRUE );

Do you want to enable the alternative URLs for the client websites?

Alternative URLs make the clients able to access their websites (domains) through control panel subdomains such as dmn1.panel.domain.tld.

This feature is useful for clients who have not yet updated their DNS so that their domain name points to the IP address of the server that has been assigned to them. 
EOF
        return $rs unless $rs < 30;
        $value = $rs ? 'no' : 'yes'
    }

    ::setupSetQuestion( 'CLIENT_WEBSITES_ALT_URLS', $value );
    0;
}

=item _addServerAlias( \$cfgTpl, $filename, \%data, $options )

 Add server alias for client domain/subdomain alternative URL in httpd vhost file

 Param scalarref Httpd configuration file content
 Param string Httpd configuration filename
 Param hashref \%data Domain data as provided by domain modules
 Param hashref \%option Options
 Return int 0

=cut

sub _addServerAlias
{
    my ( undef, $filename, $data ) = @_;

    return 0 if $filename ne 'domain.tpl' || $::imscpConfig{'CLIENT_WEBSITES_ALT_URLS'} ne 'yes' || $data->{'ACTION'} !~ /^add(?:Dmn|Sub)$/;

    my $httpd = Servers::httpd->factory();
    my $httpdData = $httpd->getData();
    my $alias = "$httpdData->{'DOMAIN_TYPE'}$httpdData->{'DOMAIN_ID'}.$::imscpConfig{'BASE_SERVER_VHOST'}";
    $httpd->setData( { SERVER_ALIASES => length $httpdData->{'SERVER_ALIASES'} ? $httpdData->{'SERVER_ALIASES'} . ' ' . $alias : $alias } );
}

=item _addDnsRR( \%data )

 Add DNS (A/AAAA) resource record for client website alternative URL

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub _addDnsRR
{
    my ( undef, $data ) = @_;

    return 0 unless $::imscpConfig{'CLIENT_WEBSITES_ALT_URLS'} eq 'yes';

    Servers::named->factory()->addSub( {
        BASE_SERVER_PUBLIC_IP => $data->{'BASE_SERVER_PUBLIC_IP'},
        PARENT_DOMAIN_NAME    => $::imscpConfig{'BASE_SERVER_VHOST'},
        DOMAIN_NAME           => "$data->{'DOMAIN_TYPE'}$data->{'DOMAIN_ID'}.$::imscpConfig{'BASE_SERVER_VHOST'}",
        DOMAIN_IP             => $data->{'DOMAIN_IP'},
        MAIL_ENABLED          => FALSE,
        OPTIONAL_ENTRIES      => FALSE
    } );
}

=item _deleteDnsRR( \%data )

 Delete DNS (A/AAAA) resource record for client website alternative URL

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub _deleteDnsRR
{
    my ( undef, $data ) = @_;

    return 0 unless $::imscpConfig{'CLIENT_WEBSITES_ALT_URLS'} eq 'yes';

    Servers::named->factory()->deleteSub( {
        PARENT_DOMAIN_NAME => $::imscpConfig{'SYSTEM_DOMAIN'},
        DOMAIN_NAME        => "$data->{'DOMAIN_TYPE'}$data->{'DOMAIN_ID'}.$::imscpConfig{'BASE_SERVER_VHOST'}"
    } );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
