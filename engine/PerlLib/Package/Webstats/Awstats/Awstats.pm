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
use Class::Autouse qw/ :nostat Package::Webstats::Awstats::Installer Package::Webstats::Awstats::Uninstaller /;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::Ext2Attributes qw(setImmutable clearImmutable);
use iMSCP::File;
use iMSCP::Rights;
use iMSCP::TemplateParser;
use Servers::cron;
use Servers::httpd;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 AWStats package for i-MSCP.

 Advanced Web Statistics (AWStats) is a powerful Web server logfile analyzer written in perl that shows you all your Web
 statistics including visits, unique visitors, pages, hits, rush hours, search engines, keywords used to find your site,
 robots, broken links and more.

 Project homepage: http://awstats.sourceforge.net/

=head1 PUBLIC METHODS

=over 4

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    Package::Webstats::Awstats::Installer->getInstance()->install();
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    Package::Webstats::Awstats::Uninstaller->getInstance()->uninstall();
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
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_USER'},
            mode  => '0700'
        }
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
        "$self->{'httpd'}->{'config'}->{'HTTPD_CONF_DIR'}/.imscp_awstats",
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $self->{'httpd'}->getRunningGroup(),
            mode  => '0640'
        }
    );
}

=item getDistroPackages()

 Get list of Debian packages

 Return list List of packages

=cut

sub getDistroPackages
{
    ('awstats');
}

=item addUser(\%data)

 Process addUser tasks

 Param hash \%data User data
 Return int 0 on success, other on failure

=cut

sub addUser
{
    my ($self, $data) = @_;

    my $filePath = "$self->{'httpd'}->{'config'}->{'HTTPD_CONF_DIR'}/.imscp_awstats";
    my $file = iMSCP::File->new( filename => $filePath );
    my $fileContent = $file->get();
    $fileContent = '' unless defined $fileContent;
    $fileContent =~ s/^$data->{'USERNAME'}:[^\n]*\n//gim;
    $fileContent .= "$data->{'USERNAME'}:$data->{'PASSWORD_HASH'}\n";

    my $rs ||= $file->set( $fileContent );
    $rs ||= $file->save();
}

=item addDmn(\%data)

 Process addDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ($self, $data) = @_;

    my $rs = $self->_addAwstatsConfig( $data );
    $rs ||= clearImmutable( $data->{'HOME_DIR'} );
    $rs ||= iMSCP::Dir->new( dirname => "$data->{'HOME_DIR'}/statistics" )->remove(); # Transitional
    $rs ||= setImmutable( $data->{'HOME_DIR'} ) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';
}

=item deleteDmn(\%data)

 Process deleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my (undef, $data) = @_;

    my $cfgFileName = "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$data->{'DOMAIN_NAME'}.conf";
    if (-f $cfgFileName) {
        my $rs = iMSCP::File->new( filename => $cfgFileName )->delFile();
        return $rs if $rs;
    }

    my $awstatsCacheDir = $main::imscpConfig{'AWSTATS_CACHE_DIR'};
    return 0 unless -d $awstatsCacheDir;

    my @awstatsCacheFiles = iMSCP::Dir->new(
        dirname  => $awstatsCacheDir,
        fileType => '^(?:awstats[0-9]+|dnscachelastupdate)'.quotemeta( ".$data->{'DOMAIN_NAME'}.txt" )
    )->getFiles();

    return 0 unless @awstatsCacheFiles;

    for(@awstatsCacheFiles) {
        my $file = iMSCP::File->new( filename => "$awstatsCacheDir/$_" );
        my $rs = $file->delFile();
        return $rs if $rs;
    }

    0;
}

=item addSub(\%data)

 Process addSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ($self, $data) = @_;

    $self->addDmn( $data );
}

=item deleteSub(\%data)

 Process deleteSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
    my ($self, $data) = @_;

    $self->deleteDmn( $data );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Package::Awstats

=cut

sub _init
{
    my $self = shift;

    $self->{'httpd'} = Servers::httpd->factory();
    iMSCP::EventManager->getInstance()->register( 'afterHttpdBuildConf', sub { $self->_addAwstatsSection( @_ ); } );
    $self;
}

=item _addAwstatsSection(\$cfgTpl, $filename, \%data)

 Add Apache configuration snippet for AWStats in the given domain vhost template file

 Listener responsible to build and insert Apache configuration snipped for AWStats in the given domain vhost file. The
 type of configuration snippet inserted depends on the AWStats mode (dynamic or static).

 Param string \$cfgTpl Template file content
 Param string $filename Template filename
 Param hash \%data Domain data
 Return int 0 on success, 1 on failure

=cut

sub _addAwstatsSection
{
    my ($self, $cfgTpl, $tplName, $data) = @_;

    return 0 unless $tplName =~ /^domain(?:_ssl)?\.tpl$/ && $data->{'FORWARD'} eq 'no';

    ${$cfgTpl} = replaceBloc(
        "# SECTION addons BEGIN.\n",
        "# SECTION addons END.\n",
        "    # SECTION addons BEGIN.\n".
            getBloc(
                "# SECTION addons BEGIN.\n",
                "# SECTION addons END.\n",
                ${$cfgTpl}
            ).
            process({ DOMAIN_NAME => $data->{'DOMAIN_NAME'} }, $self->_getApacheConfSnippet()).
            "    # SECTION addons END.\n",
        ${$cfgTpl}
    );
    0;
}

=item _getApacheConfSnippet()

 Get apache configuration snippet

 Return string

=cut

sub _getApacheConfSnippet
{
    <<"EOF";
    <Location /stats>
        ProxyPass http://127.0.0.1:8889/stats/{DOMAIN_NAME} retry=1 acquire=3000 timeout=600 Keepalive=On
        ProxyPassReverse http://127.0.0.1:8889/stats/{DOMAIN_NAME}
    </Location>
EOF
}

=item _addAwstatsConfig(\%data)

 Add awstats configuration file for the given domain

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub _addAwstatsConfig
{
    my ($self, $data) = @_;

    my $awstatsPackageRootDir = "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats/Awstats";
    my $tplFileContent = iMSCP::File->new( filename => "$awstatsPackageRootDir/Config/awstats.imscp_tpl.conf" )->get();
    unless (defined $tplFileContent) {
        error( sprintf( 'Could not read read %s file', $tplFileContent->{'filename'} ) );
        return 1;
    }

    my $qrs = iMSCP::Database->factory()->doQuery(
        'admin_id', 'SELECT admin_id, admin_name FROM admin WHERE admin_id = ?', $data->{'DOMAIN_ADMIN_ID'}
    );
    unless (ref $qrs eq 'HASH') {
        error($qrs);
        return 1;
    } elsif (!%{$qrs}) {
        erro(sprintf('Could not retrieve data from admin whith ID %d', $data->{'DOMAIN_ADMIN_ID'})),
    }

    my $tags = {
        ALIAS               => $data->{'ALIAS'},
        AUTH_USER           => "$qrs->{$data->{'DOMAIN_ADMIN_ID'}}->{'admin_name'}",
        AWSTATS_CACHE_DIR   => $main::imscpConfig{'AWSTATS_CACHE_DIR'},
        AWSTATS_ENGINE_DIR  => $main::imscpConfig{'AWSTATS_ENGINE_DIR'},
        AWSTATS_WEB_DIR     => $main::imscpConfig{'AWSTATS_WEB_DIR'},
        CMD_LOGRESOLVEMERGE => "perl $awstatsPackageRootDir/Scripts/logresolvemerge.pl",
        DOMAIN_NAME         => $data->{'DOMAIN_NAME'},
        LOG_DIR             => "$self->{'httpd'}->{'config'}->{'HTTPD_LOG_DIR'}/$data->{'DOMAIN_NAME'}"
    };

    $tplFileContent = process( $tags, $tplFileContent );
    unless (defined $tplFileContent) {
        error( 'Error while building Awstats configuration file' );
        return 1;
    }

    my $file = iMSCP::File->new(
        filename => "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$data->{'DOMAIN_NAME'}.conf"
    );
    my $rs = $file->set( $tplFileContent );
    $rs ||= $file->save();
    $rs ||= $file->mode( 0644 );
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
