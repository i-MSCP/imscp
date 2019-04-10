=head1 NAME

 Package::WebStatistics::AWStats::AWStats - AWStats

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

package Package::WebStatistics::AWStats::AWStats;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Package::WebStatistics::AWStats::Installer Package::WebStatistics::AWStats::Uninstaller /;
use iMSCP::Boolean;
use iMSCP::Database;
use iMSCP::Debug 'error';
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Ext2Attributes qw( setImmutable clearImmutable );
use iMSCP::File;
use iMSCP::Rights 'setRights';
use iMSCP::TemplateParser qw/ getBloc process replaceBloc /;
use Servers::cron;
use Servers::httpd;
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

 Process installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    Package::WebStatistics::AWStats::Installer->getInstance()->install();
}

=item postinstall( )

 Process post-installation tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    Package::WebStatistics::AWStats::Installer->getInstance()->postinstall();
}

=item uninstall( )

 Process uninstallation tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    Package::WebStatistics::AWStats::Uninstaller->getInstance()->uninstall();
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ( $self ) = @_;

    my $rs = setRights( "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/WebStatistics/AWStats/Scripts/awstats_updateall.pl", {
        user  => $::imscpConfig{'ROOT_USER'},
        group => $::imscpConfig{'ROOT_USER'},
        mode  => '0700'
    } );
    $rs ||= setRights( $::imscpConfig{'AWSTATS_CACHE_DIR'}, {
        user      => $::imscpConfig{'ROOT_USER'},
        group     => $self->{'httpd'}->getRunningGroup(),
        dirmode   => '02750',
        filemode  => '0640',
        recursive => TRUE
    } );
    $rs ||= setRights( "$self->{'httpd'}->{'config'}->{'HTTPD_CONF_DIR'}/.imscp_awstats", {
        user  => $::imscpConfig{'ROOT_USER'},
        group => $self->{'httpd'}->getRunningGroup(),
        mode  => '0640'
    } );
}

=item getDistributionPackages( )

 Get list of distribution packages

 Return list List of packages

=cut

sub getDistributionPackages
{
    'awstats';
}

=item addUser( \%data )

 Process addUser tasks

 Param hash \%data User data
 Return int 0 on success, other on failure

=cut

sub addUser
{
    my ( $self, $data ) = @_;

    my $filePath = "$self->{'httpd'}->{'config'}->{'HTTPD_CONF_DIR'}/.imscp_awstats";
    my $file = iMSCP::File->new( filename => $filePath );
    my $fileContentRef = $file->getAsRef();
    ${ $fileContentRef } = '' unless defined $fileContentRef;
    ${ $fileContentRef } =~ s/^$data->{'USERNAME'}:[^\n]*\n//gim;
    ${ $fileContentRef } .= "$data->{'USERNAME'}:$data->{'PASSWORD_HASH'}\n";

    my $rs = $file->save();
    $self->{'httpd'}->{'restart'} = TRUE unless $rs;
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

    return 0 if $self->{'eventManager'}->hasListener( 'afterHttpdBuildConf', \&_addAwstatsSection );

    $self->{'eventManager'}->register( 'afterHttpdBuildConf', \&_addAwstatsSection );
}

=item addDmn( \%data )

 Process addDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ( $self, $data ) = @_;

    my $rs = $self->_addAwstatsConfig( $data );
    $rs ||= clearImmutable( $data->{'HOME_DIR'} );
    return $rs if $rs;

    iMSCP::Dir->new( dirname => "$data->{'HOME_DIR'}/statistics" )->remove();
    setImmutable( $data->{'HOME_DIR'} ) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';
    0;
}

=item deleteDmn( \%data )

 Process deleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ( undef, $data ) = @_;

    my $cfgFileName = "$::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$data->{'DOMAIN_NAME'}.conf";
    if ( -f $cfgFileName ) {
        my $rs = iMSCP::File->new( filename => $cfgFileName )->delFile();
        return $rs if $rs;
    }

    my $awstatsCacheDir = $::imscpConfig{'AWSTATS_CACHE_DIR'};
    return 0 unless -d $awstatsCacheDir;

    my @awstatsCacheFiles = iMSCP::Dir->new(
        dirname  => $awstatsCacheDir,
        fileType => '^(?:awstats[0-9]+|dnscachelastupdate)' . quotemeta( ".$data->{'DOMAIN_NAME'}.txt" )
    )->getFiles();

    return 0 unless @awstatsCacheFiles;

    for my $cacheFile ( @awstatsCacheFiles ) {
        my $rs = iMSCP::File->new( filename => "$awstatsCacheDir/$cacheFile" )->delFile();
        return $rs if $rs;
    }

    0;
}

=item addSub( \%data )

 Process addSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ( $self, $data ) = @_;

    $self->addDmn( $data );
}

=item deleteSub( \%data )

 Process deleteSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
    my ( $self, $data ) = @_;

    $self->deleteDmn( $data );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Package::WebStatistics::AWStats::AWStats

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'httpd'} = Servers::httpd->factory();
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self;
}

=item _addAwstatsSection( \$cfgTpl, $filename, \%data )

 Listener responsible to build and insert Apache configuration snipped for
 AWStats in the given domain vhost file.

 Param string \$cfgTpl Template file content
 Param string $filename Template filename
 Param hash \%data Domain data
 Return int 0 on success, 1 on failure

=cut

sub _addAwstatsSection
{
    my ( $cfgTpl, $tplName, $data ) = @_;

    return 0 if $tplName ne 'domain.tpl' || $data->{'FORWARD'} ne 'no';

    ${ $cfgTpl } = replaceBloc(
        "# SECTION addons BEGIN.\n",
        "# SECTION addons END.\n",
        "    # SECTION addons BEGIN.\n"
            . getBloc( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", ${ $cfgTpl } )
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

=item _addAwstatsConfig( \%data )

 Add awstats configuration file for the given domain

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub _addAwstatsConfig
{
    my ( $self, $data ) = @_;

    my $awstatsPackageRootDir = "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/WebStatistics/AWStats";
    my $tplFileContent = iMSCP::File->new( filename => "$awstatsPackageRootDir/Config/awstats.imscp_tpl.conf" )->get();
    return 1 unless defined $tplFileContent;

    local $@;
    my $row = eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();
        local $dbh->{'RaiseError'} = TRUE;
        $dbh->selectrow_hashref( 'SELECT admin_name FROM admin WHERE admin_id = ?', undef, $data->{'DOMAIN_ADMIN_ID'} );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    } elsif ( !$row ) {
        error( sprintf( "Couldn't retrieve data from admin whith ID %d", $data->{'DOMAIN_ADMIN_ID'} ));
        return 1;
    }

    my $tags = {
        ALIAS               => $data->{'ALIAS'},
        AUTH_USER           => "$row->{'admin_name'}",
        AWSTATS_CACHE_DIR   => $::imscpConfig{'AWSTATS_CACHE_DIR'},
        AWSTATS_ENGINE_DIR  => $::imscpConfig{'AWSTATS_ENGINE_DIR'},
        AWSTATS_WEB_DIR     => $::imscpConfig{'AWSTATS_WEB_DIR'},
        CMD_LOGRESOLVEMERGE => "perl $awstatsPackageRootDir/Scripts/logresolvemerge.pl",
        DOMAIN_NAME         => $data->{'DOMAIN_NAME'},
        LOG_DIR             => "$self->{'httpd'}->{'config'}->{'HTTPD_LOG_DIR'}/$data->{'DOMAIN_NAME'}"
    };

    $tplFileContent = process( $tags, $tplFileContent );

    my $file = iMSCP::File->new( filename => "$::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$data->{'DOMAIN_NAME'}.conf" );
    $file->set( $tplFileContent );
    my $rs = $file->save();
    $rs ||= $file->owner( $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0644 );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
