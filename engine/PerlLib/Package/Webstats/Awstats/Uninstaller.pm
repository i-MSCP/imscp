=head1 NAME

Package::Webstats::Awstats::Uninstaller - i-MSCP AWStats package uninstaller

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use Servers::httpd;
use Servers::cron;
use iMSCP::Ext2Attributes qw(setImmutable clearImmutable isImmutable);
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This is the uninstaller for the i-MSCP Awstats package.

=head1 PUBLIC METHODS

=over 4

=item uninstall()

 Process AWStats package uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $rs = $self->_deleteFiles();
	return $rs if $rs;

	$rs = $self->_removeVhost();
	return $rs if $rs;

	$self->_restoreDebianConfig();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _deleteFiles()

 Delete files

 Return int 0 on success other on failure

=cut

sub _deleteFiles
{
	my $rs = 0;

	if(-d $main::imscpConfig{'USER_WEB_DIR'}) {
		my @homeDirs = iMSCP::Dir->new( dirname => $main::imscpConfig{'USER_WEB_DIR'} )->getDirs();

		if(@homeDirs) {
			for(@homeDirs) {
				my $isImmutableHomeDir = isImmutable("$main::imscpConfig{'USER_WEB_DIR'}/$_");

				$rs = clearImmutable("$main::imscpConfig{'USER_WEB_DIR'}/$_") if $isImmutableHomeDir;
				return $rs if $rs;

				iMSCP::Dir->new( dirname => "$main::imscpConfig{'USER_WEB_DIR'}/$_/statistics" )->remove();

				$rs = setImmutable("$main::imscpConfig{'USER_WEB_DIR'}/$_") if $isImmutableHomeDir;
				return $rs if $rs;
			}
		}
	}

	if(-d $main::imscpConfig{'AWSTATS_CACHE_DIR'}) {
		$rs = execute("rm -fR $main::imscpConfig{'AWSTATS_CACHE_DIR'}/*",  \my $stdout, \my $stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	if(-d $main::imscpConfig{'AWSTATS_CONFIG_DIR'}) {
		$rs = execute("rm -f $main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.*.conf", \my $stdout, \my $stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
	}

	$rs;
}

=item _removeVhost()

 Remove global vhost file

 Return int 0 on success, other on failure

=cut

sub _removeVhost
{
	my $httpd = Servers::httpd->factory();

	if(-f "$httpd->{'apacheWrkDir'}/01_awstats.conf") {
		iMSCP::File->new( filename => "$httpd->{'apacheWrkDir'}/01_awstats.conf" )->delFile();
	}

	if (-f "$httpd->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/01_awstats.conf") {
		$rs = $httpd->disableSites('01_awstats.conf');
		return $rs if $rs;

		iMSCP::File->new(filename => "$httpd->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/01_awstats.conf")->delFile();
	}

	0;
}

=item _restoreDebianConfig()

 Restore default configuration

 Return int 0 on success, other on failure

=cut

sub _restoreDebianConfig
{
	if(-f "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf.disabled") {
		iMSCP::File->new(
			filename => "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf.disabled"
		)->moveFile(
			"$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf"
		);
	}

	my $cronDir = Servers::cron->factory()->{'config'}->{'CRON_D_DIR'};
	if(-f "$cronDir/awstats.disable") {
		iMSCP::File->new( filename => "$cronDir/awstats.disable" )->moveFile("$cronDir/awstats");
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
