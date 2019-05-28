=head1 NAME

 Package::AltUrlFeature - Alternative URLs for client websites

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

package Package::AltUrlsFeature;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::EventManager;
use iMSCP::Getopt;
use Servers::httpd;
use Servers::named;
use Scalar::Defer 'lazy';
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Provides alternative URLs for client websites.

=head1 PUBLIC METHODS

=over 4

=item getPriority( )

 Get package priority

 Return int package priority

=cut

sub getPriority
{
    -20;
}

=item registerSetupListeners( \%events )

 Register setup event listeners

 Param iMSCP::EventManager \%events
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( $self, $events ) = @_;

    $events->registerOne( 'beforeSetupDialog', sub {
        push @{ $_[0] },
            sub { $self->_dialogForAltUrlsFeature( @_ ); };
        0;
    } );
}

=item addDmn( \%data )

 Add control panel subdomain (A/AAAA) DNS record for client website alternative URL

 Param hashref \%data Data as provided by the Domain or alias modules
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ( $self, $data ) = @_;

    $self->_addDnsRecord( $data );
}

=item deleteDmn( \%data )

 Delete control panel subdomain (A/AAAA) DNS record for client website alternative URL

 Param hashref \%data Data as provided by the Domain or alias modules
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ( $self, $data ) = @_;

    $self->_deleteDnsRecord( $data );
}

=item disableDmn( \%data )

 See Package::AltUrlsFeature::addDmn()

 On a reconfiguration, we need make sure that DNS record are added, even for
 disabled domains.

=cut

sub disableDmn
{
    my ( $self, $data ) = @_;

    return 0 unless defined $::execmode && $::execmode eq 'setup';

    $self->addDmn( $data );
}

=item addSub( \%data )

 Add control panel subdomain  (A/AAAA) DNS record for client website alternative URL

 Param hashref \%data Data as provided by the Subdomain or SubAlias modules
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ( $self, $data ) = @_;

    $self->_addDnsRecord( $data );
}

=item deleteSub( \%data )

 Delete control panel subdomain (A/AAAA) DNS record for client website alternative URL 
 
 Param hashref \%data Data as provided by the Subdomain or SubAlias modules
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
    my ( $self, $data ) = @_;

    $self->_deleteDnsRecord( $data );
}

=item disableSub( \%data )

 See Package::AltUrlsFeature::addSub()
 
 On a reconfiguration, we need make sure that DNS record are added, even for
 disabled subdomains.

=cut

sub disableSub
{
    my ( $self, $data ) = @_;

    return 0 unless defined $::execmode && $::execmode eq 'setup';

    $self->addSub( $data );
}

=back

=head1 EVENT LISTENERS

=over 4

=item addServerAlias( \$cfgTpl, $tplName, \%data )

 Add server alias for client domain/subdomain alternative URL in httpd vhost file

 Param scalarref \$cfgTpl Httpd configuration file content
 Param string $tplName Httpd configuration template filename
 Param hashref \%data Domain data as provided by Domain, Alias, Subdomain or SubAlias modules
 Return int 0

=cut

sub _addServerAlias
{
    my ( $self, undef, $tplName, $data ) = @_;

    return 0 unless $tplName eq 'domain.tpl'
        && $::imscpConfig{'CLIENT_WEBSITES_ALT_URLS'} eq 'yes';

    my $serverData = $self->{'httpd'}->getData();
    my $alias = "$data->{'DOMAIN_TYPE'}$data->{'DOMAIN_ID'}.$::imscpConfig{'BASE_SERVER_VHOST'}";

    $self->{'httpd'}->setData( {
        SERVER_ALIASES => length $serverData->{'SERVER_ALIASES'}
            ? $serverData->{'SERVER_ALIASES'} . ' ' . $alias
            : $alias
    } );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance
 
 Return Package::AltUrlFeature

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'named'} = lazy { Servers::named->factory(); };
    $self->{'httpd'} = lazy { Servers::httpd->factory(); };
    iMSCP::EventManager->getInstance()->register(
        'beforeHttpdBuildConfFile', sub { $self->_addServerAlias( @_ ); }
    );
    $self;
}

=item _dialogForAltUrlsFeature( \%dialog )

 Setup dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForAltUrlsFeature
{
    my ( undef, $dialog ) = @_;

    my $value = ::setupGetQuestion(
        'CLIENT_WEBSITES_ALT_URLS', iMSCP::Getopt->preseed ? 'yes' : ''
    );

    if ( !grep ( $::reconfigure eq $_, qw/ alt_urls_feature all / )
        && grep ( $value eq $_, qw/ yes no / )
    ) {
        ::setupSetQuestion( 'CLIENT_WEBSITES_ALT_URLS', $value );
        return 20
    }

    my $ret = $dialog->boolean( <<"EOF", $value eq 'no' );
Do you want to enable the alternative URLs feature for the client websites?

Alternative URLs make the clients able to access their websites through control panel subdomains such as:

 \\Zbdmn1.@{ [ ::setupGetQuestion( 'BASE_SERVER_VHOST') ] }\\ZB

This feature is useful for clients who have not yet configured their DNS.
        
If you make use of an external DNS server for the control panel domain, don't forget to add a wildcard DNS such as:

 \\Zb*.@{ [ ::setupGetQuestion( 'BASE_SERVER_VHOST') ] }. IN A @{ [ ::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP') ] }\\ZB
EOF
    return 30 if $ret == 30;

    ::setupSetQuestion( 'CLIENT_WEBSITES_ALT_URLS', $ret ? 'no' : 'yes' );
    0;
}

=item _addDnsRecord( \%data )

 Add control panel subdomain (A/AAAA) DNS record for client website alternative URL

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub _addDnsRecord
{
    my ( $self, $data ) = @_;

    return 0 unless $::imscpConfig{'CLIENT_WEBSITES_ALT_URLS'} eq 'yes';

    $self->{'named'}->addSub( {
        PARENT_DOMAIN_NAME    => $::imscpConfig{'BASE_SERVER_VHOST'},
        DOMAIN_NAME           => "$data->{'DOMAIN_TYPE'}$data->{'DOMAIN_ID'}.$::imscpConfig{'BASE_SERVER_VHOST'}",
        DOMAIN_IP             => $data->{'DOMAIN_IP'},
        BASE_SERVER_PUBLIC_IP => $data->{'BASE_SERVER_PUBLIC_IP'},
        MAIL_ENABLED          => FALSE,
        OPTIONAL_RECORDS      => FALSE
    } );
}

=item _deleteDnsRecord( \%data )

 Delete control panel subdomain (A/AAAA) DNS record for client website alternative URL

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub _deleteDnsRecord
{
    my ( $self, $data ) = @_;

    return 0 unless $::imscpConfig{'CLIENT_WEBSITES_ALT_URLS'} eq 'yes';

    $self->{'named'}->deleteSub( {
        PARENT_DOMAIN_NAME => $::imscpConfig{'BASE_SERVER_VHOST'},
        DOMAIN_NAME        => "$data->{'DOMAIN_TYPE'}$data->{'DOMAIN_ID'}.$::imscpConfig{'BASE_SERVER_VHOST'}"
    } );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
