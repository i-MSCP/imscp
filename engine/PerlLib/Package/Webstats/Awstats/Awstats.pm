=head1 NAME

 Package::Webstats::Awstats::Awstats - i-MSCP AWStats package

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

package Package::Webstats::Awstats::Awstats;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Package::Webstats::Awstats::Installer Package::Webstats::Awstats::Uninstaller /;
use iMSCP::Boolean;
use iMSCP::Database;
use iMSCP::Debug 'error';
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::Ext2Attributes qw/ setImmutable clearImmutable /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Rights 'setRights';
use iMSCP::TemplateParser qw/ process replaceBloc getBloc /;
use iMSCP::Umask '$UMASK';
use Servers::cron;
use Servers::httpd;
use Try::Tiny;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 AWStats package for i-MSCP.

 Advanced Web Statistics (AWStats) is a powerful Web server logfile analyzer
 written in perl that shows you all your Web statistics including visits,
 unique visitors, pages, hits, rush hours, search engines, keywords used to
 find your site, robots, broken links and more.

 Project homepage: http://awstats.sourceforge.net/

=head1 PUBLIC METHODS

=over 4

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    Package::Webstats::Awstats::Installer->getInstance()->install();
}

=item postinstall( )

 Process post install tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    Package::Webstats::Awstats::Installer->getInstance()->postinstall();
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    Package::Webstats::Awstats::Uninstaller->getInstance()->uninstall();
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ( $self ) = @_;

    my $rs = setRights( "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats/Awstats/bin", {
        user      => $::imscpConfig{'ROOT_USER'},
        group     => $::imscpConfig{'ROOT_USER'},
        mode      => '0750',
        recursive => TRUE
    } );
    $rs ||= setRights( $::imscpConfig{'AWSTATS_CONFIG_DIR'}, {
        user      => $::imscpConfig{'ROOT_USER'},
        group     => $self->{'httpd'}->getRunningGroup(),
        dirmode   => '2750',
        filemode  => '0640',
        recursive => iMSCP::Getopt->fixPermissions
    } );
    $rs ||= setRights( $::imscpConfig{'AWSTATS_CACHE_DIR'}, {
        user      => $::imscpConfig{'ROOT_USER'},
        group     => $self->{'httpd'}->getRunningGroup(),
        dirmode   => '2750',
        filemode  => '0640',
        recursive => iMSCP::Getopt->fixPermissions
    } );
    $rs ||= setRights( "$self->{'httpd'}->{'config'}->{'HTTPD_CONF_DIR'}/.imscp_awstats", {
        user  => $::imscpConfig{'ROOT_USER'},
        group => $self->{'httpd'}->getRunningGroup(),
        mode  => '0640'
    } );
}

=item getDistroPackages( )

 Get list of Debian packages

 Return list List of packages

=cut

sub getDistroPackages
{
    ( 'awstats' );
}

=item addUser( \%data )

 Process addUser tasks

 Param hash \%data User data
 Return int 0 on success, other on failure

=cut

sub addUser
{
    my ( $self, $data ) = @_;

    my $filepath = "$self->{'httpd'}->{'config'}->{'HTTPD_CONF_DIR'}/.imscp_awstats";
    my $file = iMSCP::File->new( filename => $filepath );
    my $fileC = $file->getAsRef();
    return 1 unless defined $fileC;

    ${ $fileC } =~ s/^$data->{'USERNAME'}:[^\n]*\n//gm;
    ${ $fileC } .= "$data->{'USERNAME'}:$data->{'PASSWORD_HASH'}\n";

    my $rs = $file->save();
    $rs || ( $self->{'httpd'}->{'restart'} = TRUE ); # FIXME Is that really needed?
    $rs;
}

=item preaddDmn( \%data )

 Process preaddDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub preaddDmn
{
    my ( $self ) = @_;

    $self->_registerEventListener();
}

=item addDmn( \%data )

 Process addDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ( $self, $data ) = @_;

    $self->_buildAWStatsConfig( $data );
}

=item deleteDmn( \%data )

 Process deleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ( $self, $data ) = @_;

    $self->_deleteAWStatsFiles( $data->{'DOMAIN_NAME'} );
}

=item preaddSub( \%data )

 Process preaddSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub preaddSub
{
    my ( $self ) = @_;

    $self->_registerEventListener();
}

=item addSub( \%data )

 Process addSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ( $self, $data ) = @_;

    $self->_buildAWStatsConfig( $data );
}

=item deleteSub( \%data )

 Process deleteSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
    my ( $self, $data ) = @_;

    $self->_deleteAWStatsFiles( $data->{'DOMAIN_NAME'} );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Package::Awstats

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'httpd'} = Servers::httpd->factory();
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self;
}

=item _registerEventListener()

 Register event listener for AWStats section injection in Apache2 vhost files

 Return 0 on success, other on failure

=cut

sub _registerEventListener
{
    my ( $self ) = @_;

    return 0 if $self->{'_listener'};

    $self->{'_listener'} = TRUE;
    $self->{'eventManager'}->register( 'afterHttpdBuildConf', \&_injectAWStatsSectionInApache2Vhost );
}

=item _injectAWStatsSectionInApache2Vhost( \$cfgTpl, $tplName, \%data )

 Listener that injects AWStats section in Apache2 vhost file for the given domain (or subdomain)

 Param string \$cfgTpl Template file content
 Param string $tplName Template filename
 Param hash \%data Domain or subdomain data
 Return int 0 on success, 1 on failure

=cut

sub _injectAWStatsSectionInApache2Vhost
{
    my ( $cfgTpl, $tplName, $data ) = @_;

    return 0 unless $tplName eq 'domain.tpl' && $data->{'FORWARD'} eq 'no';

    ${ $cfgTpl } = replaceBloc(
        "# SECTION addons BEGIN.\n",
        "# SECTION addons END.\n",
        "    # SECTION addons BEGIN.\n" .
            getBloc( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", ${ $cfgTpl } )
            . process( { DOMAIN_NAME => $data->{'DOMAIN_NAME'} }, <<'EOF' )
    <Location /stats>
        ProxyErrorOverride On
        ProxyPreserveHost Off
        ProxyPass http://127.0.0.1:8889/stats/{DOMAIN_NAME} retry=1 acquire=3000 timeout=600 Keepalive=On
        ProxyPassReverse http://127.0.0.1:8889/stats/{DOMAIN_NAME}
    </Location>
EOF
            . "    # SECTION addons END.\n",
        ${ $cfgTpl }
    );

    0;
}

=item _buildAWStatsConfig( \%data )

 Build awstats configuration file for the given domain (or subdomain)

 Param hash \%data Domain or subdomain data
 Return int 0 on success, other on failure

=cut

sub _buildAWStatsConfig
{
    my ( $self, $data ) = @_;

    try {
        my ( $authUser ) = iMSCP::Database->factory()->getConnector()->run( fixup => sub {
            @{ $_->selectcol_arrayref( 'SELECT admin_name FROM admin WHERE admin_id = ?', undef, $data->{'DOMAIN_ADMIN_ID'} ) };
        } );
        defined $authUser or die( sprintf( "Couldn't retrieve data for admin with ID %d", $data->{'DOMAIN_ADMIN_ID'} ));

        my $packageDir = "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats/Awstats";
        my $file = iMSCP::File->new( filename => "$packageDir/config/awstats_config.tpl" );
        return 1 unless defined( my $fileC = $file->getAsRef());

        ${ $fileC } = process(
            {
                AUTH_USER           => $authUser,
                AWSTATS_CACHE_DIR   => $::imscpConfig{'AWSTATS_CACHE_DIR'},
                AWSTATS_ENGINE_DIR  => $::imscpConfig{'AWSTATS_ENGINE_DIR'},
                AWSTATS_WEB_DIR     => $::imscpConfig{'AWSTATS_WEB_DIR'},
                CMD_LOGRESOLVEMERGE => "$packageDir/bin/logresolvemerge.pl",
                HOST_ALIASES        => $self->{'httpd'}->getData()->{'SERVER_ALIASES'},
                LOG_DIR             => "$self->{'httpd'}->{'config'}->{'HTTPD_LOG_DIR'}/$data->{'DOMAIN_NAME'}",
                SITE_DOMAIN         => $data->{'DOMAIN_NAME'}
            },
            ${ $fileC }
        );

        local $UMASK = 027;
        $file->{'filename'} = "$::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$data->{'DOMAIN_NAME'}.conf";
        $file->save();
    } catch {
        error( $_ );
        1;
    };
}

=item _deleteAWStatsFiles( $domain )

 Delete any AWStats file for the given domain (or subdomain)

 - AWStats configuration file
 - AWStats cache files

 Param Regexp $domain Domain name
 Return 0 on success, other on failure

=cut

sub _deleteAWStatsFiles( )
{
    my ( undef, $domain ) = @_;

    try {
        if ( -f "$::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$domain.conf" ) {
            my $rs = iMSCP::File->new( filename => "$::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$domain.conf" )->delFile();
            return $rs if $rs;
        }

        return 0 unless -d $::imscpConfig{'AWSTATS_CACHE_DIR'};

        for my $file ( iMSCP::Dir->new(
            dirname  => $::imscpConfig{'AWSTATS_CACHE_DIR'},
            fileType => qr/^(?:awstats[0-9]+|dnscachelastupdate)\Q.$domain.txt\E/
        )->getFiles() ) {
            my $rs = iMSCP::File->new( filename => "$::imscpConfig{'AWSTATS_CACHE_DIR'}/$file" )->delFile();
            return $rs if $rs;
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
