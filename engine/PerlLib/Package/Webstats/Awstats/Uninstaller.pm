=head1 NAME

Package::Webstats::Awstats::Uninstaller - i-MSCP AWStats package uninstaller

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

package Package::Webstats::Awstats::Uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::File;
use Servers::httpd;
use Servers::cron;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This is the uninstaller for the i-MSCP Awstats package.

=head1 PUBLIC METHODS

=over 4

=item uninstall( )

 Process AWStats package uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    my $rs = $self->_deleteFiles();
    $rs ||= $self->_removeVhost();
    $rs ||= $self->_restoreDebianConfig();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _deleteFiles( )

 Delete files

 Return int 0 on success other on failure

=cut

sub _deleteFiles
{
    my $httpd = Servers::httpd->factory();

    if ( -f "$httpd->{'config'}->{'HTTPD_CONF_DIR'}/.imscp_awstats" ) {
        my $rs = iMSCP::File->new( filename => "$httpd->{'config'}->{'HTTPD_CONF_DIR'}/.imscp_awstats" )->delFile();
        return $rs if $rs;
    }

    iMSCP::Dir->new( dirname => $main::imscpConfig{'AWSTATS_CACHE_DIR'} )->remove();

    return 0 unless -d $main::imscpConfig{'AWSTATS_CONFIG_DIR'};

    my $rs = execute( "rm -f $main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.*.conf", \ my $stdout, \ my $stderr );
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
    if ( -f "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf.disabled" ) {
        my $rs = iMSCP::File->new(
            filename => "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf.disabled"
        )->moveFile(
            "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf"
        );
        return $rs if $rs;
    }

    my $cronDir = Servers::cron->factory()->{'config'}->{'CRON_D_DIR'};
    return 0 unless -f "$cronDir/awstats.disable";
    iMSCP::File->new( filename => "$cronDir/awstats.disable" )->moveFile( "$cronDir/awstats" );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
