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
use iMSCP::Boolean;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute 'execute';
use iMSCP::Ext2Attributes qw( setImmutable clearImmutable );
use iMSCP::File;
use iMSCP::Rights 'setRights';
use iMSCP::TemplateParser qw/ getBloc process replaceBloc /;
use Servers::cron;
use Scalar::Defer 'lazy';
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
    my ( $self ) = @_;

    my $rs = $self->_disableDefaultConfig();
    $rs ||= $self->_createCacheDir();
    $rs ||= $self->_setupApache2();
}

=item postinstall( )

 Process post-installation tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ( $self ) = @_;

    $self->_addAWStatsCronTask();
}

=item uninstall( )

 Process uninstallation tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ( $self ) = @_;

    my $rs = $self->_deleteFiles();
    $rs ||= $self->_removeVhost();
    $rs ||= $self->_restoreDebianConfig();
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ( $self ) = @_;

    my $rs = setRights(
        "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/WebStatistics/AWStats/Scripts/awstats_updateall.pl",
        {
            user  => $::imscpConfig{'ROOT_USER'},
            group => $::imscpConfig{'ROOT_USER'},
            mode  => '0700'
        }
    );
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

 Get list of distribution packages to install or uninstall, depending on context

 Return List of distribution packages

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

    my $file = iMSCP::File->new(
        filename => "$self->{'httpd'}->{'config'}->{'HTTPD_CONF_DIR'}/.imscp_awstats"
    );
    return 1 unless defined( my $fileC = $file->getAsRef());

    ${ $fileC } =~ s/^$data->{'USERNAME'}:[^\n]*\n//gim;
    ${ $fileC } .= "$data->{'USERNAME'}:$data->{'PASSWORD_HASH'}\n";

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

    return 0 if $self->{'events'}->hasListener(
        'afterHttpdBuildConf', \&_addAwstatsSection
    );

    $self->{'events'}->register(
        'afterHttpdBuildConf', \&_addAwstatsSection
    );
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

    eval { iMSCP::Dir->new( dirname => "$data->{'HOME_DIR'}/statistics" )->remove(); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

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

    if ( -f "$::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$data->{'DOMAIN_NAME'}.conf" ) {
        my $rs = iMSCP::File->new(
            filename => "$::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$data->{'DOMAIN_NAME'}.conf"
        )->delFile();
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

    $self->{'httpd'} = lazy { Servers::httpd->factory() };
    $self->{'events'} = iMSCP::EventManager->getInstance();
    $self;
}

=item _createCacheDir( )

 Create cache directory

 Return int 0 on success, other on failure

=cut

sub _createCacheDir
{
    my ( $self ) = @_;

    iMSCP::Dir->new( dirname => $::imscpConfig{'AWSTATS_CACHE_DIR'} )->make( {
        user  => $::imscpConfig{'ROOT_USER'},
        group => $self->{'httpd'}->getRunningGroup(),
        mode  => 02750
    } );
}

=item _setupApache2( )

 Setup Apache2 for AWStats

 Return int 0 on success, other on failure

=cut

sub _setupApache2
{
    my ( $self ) = @_;

    # Create Basic authentication file

    my $file = iMSCP::File->new(
        filename => "$self->{'httpd'}->{'config'}->{'HTTPD_CONF_DIR'}/.imscp_awstats"
    );
    # Make sure to start with an empty file on update/reconfiguration
    $file->set( '' );
    my $rs = $file->save();
    $rs ||= $file->owner(
        $::imscpConfig{'ROOT_USER'},
        $self->{'httpd'}->getRunningGroup()
    );
    $rs ||= $file->mode( 0640 );
    return $rs if $rs;

    # Enable required Apache2 modules

    $rs = $self->{'httpd'}->enableModules(
        'rewrite', 'authn_core', 'authn_basic', 'authn_socache', 'proxy',
        'proxy_http'
    );
    return $rs if $rs;

    # Create Apache2 vhost

    $self->{'httpd'}->setData( {
        AWSTATS_AUTH_USER_FILE_PATH => "$self->{'httpd'}->{'config'}->{'HTTPD_CONF_DIR'}/.imscp_awstats",
        AWSTATS_ENGINE_DIR          => $::imscpConfig{'AWSTATS_ENGINE_DIR'},
        AWSTATS_WEB_DIR             => $::imscpConfig{'AWSTATS_WEB_DIR'}
    } );

    $rs = $self->{'httpd'}->buildConfFile(
        "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/WebStatistics/AWStats/Config/01_awstats.conf"
    );
    $rs ||= $self->{'httpd'}->enableSites( '01_awstats.conf' );
}

=item _disableDefaultConfig( )

 Disable default configuration

 Return int 0 on success, other on failure

=cut

sub _disableDefaultConfig
{
    my $rs = 0;

    if ( -f "$::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf" ) {
        $rs = iMSCP::File->new(
            filename => "$::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf"
        )->moveFile(
            "$::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf.disabled"
        );
        return $rs if $rs;
    }

    my $cronDir = Servers::cron->factory()->{'config'}->{'CRON_D_DIR'};
    $rs = iMSCP::File->new( filename => "$cronDir/awstats" )->moveFile( "$cronDir/awstats.disable" ) if -f "$cronDir/awstats";
    $rs;
}

=item _addAWStatsCronTask( )

 Add AWStats cron task for dynamic mode

 Return int 0 on success, other on failure

=cut

sub _addAWStatsCronTask
{
    Servers::cron->factory()->addTask( {
        TASKID  => 'Package::WebStatistics::AWStats',
        MINUTE  => '15',
        HOUR    => '3-21/6',
        DAY     => '*',
        MONTH   => '*',
        DWEEK   => '*',
        USER    => $::imscpConfig{'ROOT_USER'},
        COMMAND => 'nice -n 10 ionice -c2 -n5 ' .
            "perl $::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/WebStatistics/AWStats/Scripts/awstats_updateall.pl now " .
            "-awstatsprog=$::imscpConfig{'AWSTATS_ENGINE_DIR'}/awstats.pl > /dev/null 2>&1"
    } );
}

=item _deleteFiles( )

 Delete files

 Return int 0 on success other on failure

=cut

sub _deleteFiles
{
    my $httpd = Servers::httpd->factory();

    if ( -f "$httpd->{'config'}->{'HTTPD_CONF_DIR'}/.imscp_awstats" ) {
        my $rs = iMSCP::File->new(
            filename => "$httpd->{'config'}->{'HTTPD_CONF_DIR'}/.imscp_awstats"
        )->delFile();
        return $rs if $rs;
    }

    local $@;
    eval {
        iMSCP::Dir->new(
            dirname => $::imscpConfig{'AWSTATS_CACHE_DIR'}
        )->remove();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    return 0 unless -d $::imscpConfig{'AWSTATS_CONFIG_DIR'};

    my $rs = execute(
        "rm -f $::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.*.conf",
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item _removeVhost( )

 Remove global vhost file

 Return int 0 on success, other on failure

=cut

sub _removeVhost
{
    my $httpd = Servers::httpd->factory();

    return 0 unless -f "$httpd->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/01_awstats.conf";

    my $rs = $httpd->disableSites( '01_awstats.conf' );
    $rs ||= iMSCP::File->new(
        filename => "$httpd->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/01_awstats.conf"
    )->delFile();
}

=item _restoreDebianConfig( )

 Restore default configuration

 Return int 0 on success, other on failure

=cut

sub _restoreDebianConfig
{
    if ( -f "$::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf.disabled" ) {
        my $rs = iMSCP::File->new(
            filename => "$::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf.disabled"
        )->moveFile(
            "$::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf"
        );
        return $rs if $rs;
    }

    my $cronDir = Servers::cron->factory()->{'config'}->{'CRON_D_DIR'};
    return 0 unless -f "$cronDir/awstats.disable";
    iMSCP::File->new( filename => "$cronDir/awstats.disable" )->moveFile( "$cronDir/awstats" );
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

    if ( $data->{'VHOST_TYPE'} eq 'domain' && $data->{'SSL_SUPPORT'} ) {
        ${ $cfgTpl } = replaceBloc(
            "# SECTION addons BEGIN.\n",
            "# SECTION addons END.\n",
            "    # SECTION addons BEGIN.\n"
                    . getBloc(
                    "# SECTION addons BEGIN.\n",
                    "# SECTION addons END.\n",
                    ${ $cfgTpl }
                )
                . "    RedirectMatch 301 ^/stats\/?\$ https://$data->{'DOMAIN_NAME'}/stats/\n"
                . "    # SECTION addons END.\n",
            ${ $cfgTpl }
        );
        return 0;
    }

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
    my $tplFileContent = iMSCP::File->new(
        filename => "$awstatsPackageRootDir/Config/awstats.imscp_tpl.conf"
    )->get();
    return 1 unless defined $tplFileContent;

    local $@;
    my $row = eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();
        $dbh->selectrow_hashref( 'SELECT admin_name FROM admin WHERE admin_id = ?', undef, $data->{'DOMAIN_ADMIN_ID'} );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    } elsif ( !$row ) {
        error( sprintf( "Couldn't retrieve data for admin with ID %d", $data->{'DOMAIN_ADMIN_ID'} ));
        return 1;
    }

    my $tags = {
        #ALIAS               => "$data->{'DOMAIN_TYPE'}$data->{'DOMAIN_ID'}.$::imscpConfig{'BASE_SERVER_VHOST'}",
        AUTH_USER           => "$row->{'admin_name'}",
        AWSTATS_CACHE_DIR   => $::imscpConfig{'AWSTATS_CACHE_DIR'},
        AWSTATS_ENGINE_DIR  => $::imscpConfig{'AWSTATS_ENGINE_DIR'},
        AWSTATS_WEB_DIR     => $::imscpConfig{'AWSTATS_WEB_DIR'},
        CMD_LOGRESOLVEMERGE => "perl $awstatsPackageRootDir/Scripts/logresolvemerge.pl",
        DOMAIN_NAME         => $data->{'DOMAIN_NAME'},
        LOG_DIR             => "$self->{'httpd'}->{'config'}->{'HTTPD_LOG_DIR'}/$data->{'DOMAIN_NAME'}"
    };

    $tplFileContent = process( $tags, $tplFileContent );

    my $file = iMSCP::File->new(
        filename => "$::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$data->{'DOMAIN_NAME'}.conf"
    );
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
