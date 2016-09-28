=head1 NAME

Package::Webstats::Awstats::Installer - i-MSCP AWStats package installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Package::Webstats::Awstats::Installer;

use strict;
use warnings;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::TemplateParser;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Rights;
use Servers::cron;
use Servers::httpd;
use Servers::sqld;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 AWStats package installer.

 See Package::Webstats::Awstats::Awstats for more information.

=head1 PUBLIC METHODS

=over 4

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $self = shift;

    my $rs = $self->_disableDefaultConfig();
    $rs ||= $self->_createCacheDir();
    $rs ||= $self->_setupApache2();
    $rs ||= $self->_addAwstatsCronTask();
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my $self = shift;

    my $rs = setRights(
        "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats/Awstats/Scripts/awstats_updateall.pl",
        { user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'ROOT_USER'}, mode => '0700' }
    );
    $rs ||= setRights(
        $main::imscpConfig{'AWSTATS_CACHE_DIR'},
        {
            user      => $main::imscpConfig{'ROOT_USER'},
            group     => $self->{'httpd'}->getRunningGroup(),
            dirmode   => '02750',
            filemode  => '0640',
            recursive => 1
        }
    );
    $rs ||= setRights(
        "$self->{'httpd'}->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/01_awstats.conf",
        { user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'ROOT_GROUP'}, mode => '0640' }
    );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Package::Webstats::Awstats::Installer

=cut

sub _init
{
    my $self = shift;

    $self->{'httpd'} = Servers::httpd->factory();
    $self;
}

=item _createCacheDir()

 Create cache directory

 Return int 0 on success, other on failure

=cut

sub _createCacheDir
{
    my $self = shift;

    iMSCP::Dir->new( dirname => $main::imscpConfig{'AWSTATS_CACHE_DIR'} )->make(
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $self->{'httpd'}->getRunningGroup(),
            mode  => 02750
        }
    );
}

=item _setupApache2()

 Setup Apache2 for AWStats

 Return int 0 on success, other on failure

=cut

sub _setupApache2
{
    my $self = shift;

    my $isApache24 = version->parse( "$self->{'httpd'}->{'config'}->{'HTTPD_VERSION'}" ) >= version->parse( '2.4.0' );

    # Enable required Apache2 modules

    my $rs = $isApache24
        ? $self->{'httpd'}->enableModules(
            'rewrite', 'dbd', 'authn_core', 'authn_basic', 'authn_socache', 'authn_dbd', 'proxy', 'proxy_http'
        )
        : $self->{'httpd'}->enableModules(
            'rewrite', 'dbd', 'authn_core', 'authn_basic', 'authn_dbd', 'proxy', 'proxy_http'
        );
    return $rs if $rs;

    # Create required SQL user

    my $host = main::setupGetQuestion( 'DATABASE_HOST' );
    $host = $host eq 'localhost' ? '127.0.0.1' : $host;
    my $port = main::setupGetQuestion( 'DATABASE_PORT' );
    my $dbName = main::setupGetQuestion( 'DATABASE_NAME' );
    my $user = 'imscp_awstats';
    my $userHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    $userHost = '127.0.0.1' if $userHost eq 'localhost';
    my $oldUserHost = $main::imscpOldConfig{'DATABASE_USER_HOST'} || '';

    my @allowedChr = map { chr } (0x30 .. 0x39, 0x41 .. 0x5a, 0x61 .. 0x7a);
    my $pass = '';
    $pass .= $allowedChr[ rand @allowedChr ] for 1 .. 16;

    my $sqld = Servers::sqld->factory();
    for ($userHost, $oldUserHost, 'localhost') {
        next unless $_;
        $sqld->dropUser( $user, $_ );
    }

    local $@;
    eval {
        $sqld->createUser( $user, $userHost, $pass );

        # No need to escape wildcard characters. See https://bugs.mysql.com/bug.php?id=18660
        my $db = iMSCP::Database->factory();
        my $qDbName = $db->quoteIdentifier( $dbName );
        $rs = $db->doQuery( 'g', "GRANT SELECT ON $qDbName.admin TO ?\@?", $user, $userHost );
        unless (ref $rs eq 'HASH') {
            error( sprintf( 'Could not add SQL privileges: %s', $rs ) );
            return 1;
        }
    };
    if($@) {
        error($@);
        return 1;
    }
   
    # Create Apache2 vhost

    $self->{'httpd'}->setData(
        {
            AUTHZ_ALLOW_ALL    => $isApache24 ? 'Require all granted' : 'Allow from all',
            AWSTATS_ENGINE_DIR => $main::imscpConfig{'AWSTATS_ENGINE_DIR'},
            AWSTATS_WEB_DIR    => $main::imscpConfig{'AWSTATS_WEB_DIR'},
            DATABASE_HOST      => $host,
            DATABASE_PORT      => $port,
            DATABASE_USER      => $user,
            DATABASE_PASSWORD  => $pass,
            DATABASE_NAME      => $dbName,
            NAME_VIRTUALHOST    => $isApache24 ? '' : 'NameVirtualHost 127.0.0.1:8889'
        }
    );

    $rs = $self->{'httpd'}->buildConfFile(
        "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats/Awstats/Config/01_awstats.conf",
        { mode => 0640 }
    );
    $rs ||= $self->{'httpd'}->enableSites( '01_awstats.conf' );
}

=item _disableDefaultConfig()

 Disable default configuration

 Return int 0 on success, other on failure

=cut

sub _disableDefaultConfig
{
    my $rs = 0;

    if (-f "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf") {
        $rs = iMSCP::File->new( filename => "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf" )->moveFile(
            "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf.disabled"
        );
        return $rs if $rs;
    }

    my $cronDir = Servers::cron->factory()->{'config'}->{'CRON_D_DIR'};
    if (-f "$cronDir/awstats") {
        $rs = iMSCP::File->new( filename => "$cronDir/awstats" )->moveFile( "$cronDir/awstats.disable" );
    }

    $rs;
}

=item _addAwstatsCronTask()

 Add AWStats cron task for dynamic mode

 Return int 0 on success, other on failure

=cut

sub _addAwstatsCronTask
{
    Servers::cron->factory()->addTask(
        {
            TASKID  => 'Package::Webstats::Awstats',
            MINUTE  => '15',
            HOUR    => '3-21/6',
            DAY     => '*',
            MONTH   => '*',
            DWEEK   => '*',
            USER    => $main::imscpConfig{'ROOT_USER'},
            COMMAND => 'nice -n 15 ionice -c2 -n5 perl '.
                "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats/Awstats/Scripts/awstats_updateall.pl now ".
                "-awstatsprog=$main::imscpConfig{'AWSTATS_ENGINE_DIR'}/awstats.pl > /dev/null 2>&1"
        }
    );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
