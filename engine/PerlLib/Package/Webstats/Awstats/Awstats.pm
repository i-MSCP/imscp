=head1 NAME

 Package::Webstats::Awstats::Awstats - i-MSCP AWStats package

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

package Package::Webstats::Awstats::Awstats;

use strict;
use warnings;
use autouse 'iMSCP::Rights' => qw/ setRights /;
use Class::Autouse qw/ :nostat Package::Webstats::Awstats::Installer Package::Webstats::Awstats::Uninstaller Servers::httpd /;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Ext2Attributes qw( setImmutable clearImmutable );
use iMSCP::File;
use iMSCP::TemplateParser qw/ getBlocByRef processByRef replaceBlocByRef /;
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
    my $httpd = Servers::httpd->factory();

    my $rs = setRights( "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats/Awstats/Scripts/awstats_updateall.pl",
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_USER'},
            mode  => '0700'
        }
    );
    $rs ||= setRights( $main::imscpConfig{'AWSTATS_CACHE_DIR'},
        {
            user      => $main::imscpConfig{'ROOT_USER'},
            group     => $httpd->getRunningGroup(),
            dirmode   => '02750',
            filemode  => '0640',
            recursive => 1
        }
    );
    $rs ||= setRights( "$httpd->{'config'}->{'HTTPD_CONF_DIR'}/.imscp_awstats",
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $httpd->getRunningGroup(),
            mode  => '0640'
        }
    );
}

=item getDistroPackages( )

 Get list of Debian packages

 Return list List of packages

=cut

sub getDistroPackages
{
    ( 'awstats', 'libnet-dns-perl' );
}

=item addUser( \%moduleData )

 Process addUser tasks

 Param hashref \%moduleData Data as provided by User module
 Return int 0 on success, other on failure

=cut

sub addUser
{
    my (undef, $moduleData) = @_;

    my $httpd = Servers::httpd->factory();
    my $file = iMSCP::File->new( filename => "$httpd->{'config'}->{'HTTPD_CONF_DIR'}/.imscp_awstats" );
    my $fileContentRef = $file->getAsRef();
    ${$fileContentRef} = '' unless defined $fileContentRef;
    ${$fileContentRef} =~ s/^$moduleData->{'USERNAME'}:[^\n]*\n//gim;
    ${$fileContentRef} .= "$moduleData->{'USERNAME'}:$moduleData->{'PASSWORD_HASH'}\n";

    my $rs = $file->save();
    return $rs if $rs;

    $httpd->{'reload'} ||= 1;
    0;
}

=item preaddDomain( )

 Process preaddDomain tasks

 Return int 0 on success, other on failure

=cut

sub preaddDomain
{
    my ($self) = @_;

    return 0 if $self->{'_is_registered_event_listener'};

    $self->{'_is_registered_event_listener'} = 1;
    $self->{'eventManager'}->register( 'beforeApache2BuildConfFile', $self );
}

=item addDomain( \%moduleData )

 Process addDomain tasks

 Param hashref \%moduleData Data as provided by Alias|Domain|SubAlias|Subdomain modules
 Return int 0 on success, other on failure

=cut

sub addDomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->_addAwstatsConfig( $moduleData );
    return $rs if $rs;

    clearImmutable( $moduleData->{'HOME_DIR'} );

    eval { iMSCP::Dir->new( dirname => "$moduleData->{'HOME_DIR'}/statistics" )->remove(); };
    if ( $@ ) {
        error( $@ );

        # Set immutable bit if needed (even on error)
        setImmutable( $moduleData->{'HOME_DIR'} ) if $moduleData->{'WEB_FOLDER_PROTECTION'} eq 'yes';
        return 1;
    }

    setImmutable( $moduleData->{'HOME_DIR'} ) if $moduleData->{'WEB_FOLDER_PROTECTION'} eq 'yes';
    0;
}

=item deleteDomain( \%moduleData )

 Process deleteDomain tasks

 Param hashref \%moduleData Data as provided by Alias|Domain|SubAlias|Subdomain modules
 Return int 0 on success, other on failure

=cut

sub deleteDomain
{
    my (undef, $moduleData) = @_;

    if ( -f "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$moduleData->{'DOMAIN_NAME'}.conf" ) {
        my $rs = iMSCP::File->new( filename => "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$moduleData->{'DOMAIN_NAME'}.conf" )->delFile();
        return $rs if $rs;
    }

    return 0 unless -d $main::imscpConfig{'AWSTATS_CACHE_DIR'};

    my @awstatsCacheFiles = eval {
        iMSCP::Dir->new(
            dirname  => $main::imscpConfig{'AWSTATS_CACHE_DIR'},
            fileType => '^(?:awstats[0-9]+|dnscachelastupdate)' . quotemeta( ".$moduleData->{'DOMAIN_NAME'}.txt" )
        )->getFiles();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    return 0 unless @awstatsCacheFiles;

    for ( @awstatsCacheFiles ) {
        my $rs = iMSCP::File->new( filename => "$main::imscpConfig{'AWSTATS_CACHE_DIR'}/$_" )->delFile();
        return $rs if $rs;
    }

    0;
}

=item addSubdomain( \%moduleData )

 Process addSubdomain tasks

 Param hashref \%moduleData Data as provided by Alias|Domain|SubAlias|Subdomain modules
 Return int 0 on success, other on failure

=cut

sub addSubdomain
{
    my ($self, $moduleData) = @_;

    $self->addDomain( $moduleData );
}

=item deleteSubdomain( \%moduleData )

 Process deleteSubdomain tasks

 Param hashref \%moduleData Data as provided by Alias|Domain|SubAlias|Subdomain modules
 Return int 0 on success, other on failure

=cut

sub deleteSubdomain
{
    my ($self, $moduleData) = @_;

    $self->deleteDomain( $moduleData );
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
    my ($self) = @_;

    $self->{'_is_registered_event_listener'} = 0;
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self;
}

=item _addAwstatsConfig( \%moduleData )

 Add awstats configuration file for the given domain

 Param hashref \%moduleData Data as provided by Alias|Domain|SubAlias|Subdomain modules
 Return int 0 on success, other on failure

=cut

sub _addAwstatsConfig
{
    my (undef, $moduleData) = @_;

    my $row = eval {
        my $dbh = iMSCP::Database->getInstance()->getRawDb();
        local $dbh->{'RaiseError'} = 1;
        $dbh->selectrow_hashref( 'SELECT admin_name FROM admin WHERE admin_id = ?', undef, $moduleData->{'DOMAIN_ADMIN_ID'} );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    } elsif ( !$row ) {
        error( sprintf( "Couldn't retrieve data for admin with ID %d", $moduleData->{'DOMAIN_ADMIN_ID'} ));
        return 1;
    }

    my $file = iMSCP::File->new( filename => "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats/Awstats/Config/awstats.imscp_tpl.conf" );
    my $fileContentRef = $file->getAsRef();
    unless ( defined $fileContentRef ) {
        error( sprintf( "Couldn't read the %s file", $file->{'filename'} ));
        return 1;
    }

    my $httpd = Servers::httpd->factory();

    processByRef(
        {
            ALIAS               => $moduleData->{'ALIAS'},
            AUTH_USER           => "$row->{'admin_name'}",
            AWSTATS_CACHE_DIR   => $main::imscpConfig{'AWSTATS_CACHE_DIR'},
            AWSTATS_ENGINE_DIR  => $main::imscpConfig{'AWSTATS_ENGINE_DIR'},
            AWSTATS_WEB_DIR     => $main::imscpConfig{'AWSTATS_WEB_DIR'},
            CMD_LOGRESOLVEMERGE => "perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats/Awstats/Scripts/logresolvemerge.pl",
            DOMAIN_NAME         => $moduleData->{'DOMAIN_NAME'},
            LOG_DIR             => "$httpd->{'config'}->{'HTTPD_LOG_DIR'}/$moduleData->{'DOMAIN_NAME'}"
        },
        $fileContentRef
    );

    $file->{'filename'} = "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$moduleData->{'DOMAIN_NAME'}.conf";
    my $rs = $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0644 );
}

=back

=head1 EVENT LISTENERS

=over 4

=item afterApache2BuildConfFile( $awstats, \$cfgTpl, $filename, \$trgFile, \%moduleData, \%apache2ServerData, \%apache2ServerConfig, \%parameters )

 Event listener that inject AWstats configuration in Apache2 vhosts

 Param scalar $awstats Package::Webstats::Awstats::Awstats instance
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

sub beforeApache2BuildConfFile
{
    my (undef, $cfgTpl, $filename, undef, $moduleData) = @_;

    return 0 if $filename ne 'domain.tpl' || $moduleData->{'FORWARD'} ne 'no';

    debug( sprintf( 'Injecting AWStats configuration in Apache2 vhost for the %s domain', $moduleData->{'DOMAIN_NAME'} ));

    replaceBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", <<"EOF", $cfgTpl );
    # SECTION addons BEGIN.
@{[ getBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", $cfgTpl ) ] }
    <Location /stats>
        ProxyErrorOverride On
        ProxyPreserveHost Off
        ProxyPass http://127.0.0.1:8889/stats/{DOMAIN_NAME} retry=0 acquire=3000 timeout=30 Keepalive=On
        ProxyPassReverse http://127.0.0.1:8889/stats/{DOMAIN_NAME}
    </Location>
    # SECTION addons END.
EOF

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
