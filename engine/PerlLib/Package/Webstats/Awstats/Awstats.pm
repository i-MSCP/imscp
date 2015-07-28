=head1 NAME

Package::Webstats::Awstats::Awstats - i-MSCP AWStats package

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

package Package::Webstats::Awstats::Awstats;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::TemplateParser;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Ext2Attributes qw(setImmutable clearImmutable);
use Servers::cron;
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

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub showDialog
{
	my ($self, $dialog) = @_;

	require Package::Webstats::Awstats::Installer;

	Package::Webstats::Awstats::Installer->getInstance()->showDialog($dialog);
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	require Package::Webstats::Awstats::Installer;

	Package::Webstats::Awstats::Installer->getInstance()->install();
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	require Package::Webstats::Awstats::Uninstaller;

	Package::Webstats::Awstats::Uninstaller->getInstance()->uninstall();
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	require Package::Webstats::Awstats::Installer;

	Package::Webstats::Awstats::Installer->getInstance()->setEnginePermissions();
}

=item getDistroPackages()

 Get list of Debian packages

 Return array List of packages

=cut

sub getDistroPackages
{
	['awstats'];
}

=item addDmn(\%data)

 Process addDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
	my ($self, $data) = @_;

	my $rs = $self->_addAwstatsConfig($data);
	return $rs if $rs;

	my $userStatisticsDir = "$data->{'HOME_DIR'}/statistics";

	$rs = clearImmutable($data->{'HOME_DIR'});
	return $rs if $rs;

	if($main::imscpConfig{'AWSTATS_MODE'} eq '1') { # Static mode
		unless(-d $userStatisticsDir) {
			$rs = iMSCP::Dir->new(
				dirname => $userStatisticsDir
			)->make({
				user => $main::imscpConfig{'ROOT_USER'}, group => $data->{'GROUP'}, mode => 02750
			});
			return $rs if $rs;
		} else {
			require iMSCP::Rights;
			iMSCP::Rights->import();

			$rs = setRights($userStatisticsDir, {
				filemode => '0640', user => $main::imscpConfig{'ROOT_USER'}, group => $data->{'GROUP'}, recursive => 1
			});
			return $rs if $rs;
		}

		$rs = $self->_addAwstatsCronTask($data);
		return $rs if $rs;

		unless(-f "$userStatisticsDir/awstats.$data->{'DOMAIN_NAME'}.html") {
			my ($stdout, $stderr);
			$rs = execute(
				"echo " .
				"'perl " .
				"$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats/Awstats/Scripts/awstats_buildstaticpages.pl " .
				"-config=$data->{'DOMAIN_NAME'} " .
				"-update -awstatsprog=$main::imscpConfig{'AWSTATS_ENGINE_DIR'}/awstats.pl -dir=$userStatisticsDir' " .
				"| batch",
				\$stdout,
				\$stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			error("Unable to schedule generation of AWStats static pages") if $rs && ! $stderr;
			return $rs if $rs;
		}
	} else {
		$rs = iMSCP::Dir->new( dirname => $userStatisticsDir )->remove();
	}

	$rs = setImmutable($data->{'HOME_DIR'}) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';
	return $rs if $rs;

	$rs;
}

=item deleteDmn(\%data)

 Process deleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
	my ($self, $data) = @_;

	my $cfgFileName = "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$data->{'DOMAIN_NAME'}.conf";
	my $wrkFileName = "$self->{'wrkDir'}/awstats.$data->{'DOMAIN_NAME'}.conf";

	my $rs = 0;

	$rs = iMSCP::File->new( filename => $cfgFileName )->delFile() if -f $cfgFileName;
	return $rs if $rs;

	$rs = iMSCP::File->new( filename => $wrkFileName )->delFile() if -f $wrkFileName;
	return $rs if $rs;

	if($main::imscpConfig{'AWSTATS_MODE'} eq '1') { # Static mode
		my $userStatisticsDir = "$data->{'HOME_DIR'}/statistics";

		if(-d $userStatisticsDir) {
			my @awstatsStaticFiles = iMSCP::Dir->new(
				dirname => $userStatisticsDir,
				fileType => '^' . quotemeta("awstats.$data->{'DOMAIN_NAME'}") . '.*?\\.html'
			)->getFiles();

			if(@awstatsStaticFiles) {
				my $file = iMSCP::File->new();

				for(@awstatsStaticFiles) {
					$file->{'filename'} = "$userStatisticsDir/$_";
					$rs = $file->delFile();
					return $rs if $rs;
				}
			}
		}
	}

	my $awstatsCacheDir = $main::imscpConfig{'AWSTATS_CACHE_DIR'};

	if(-d $awstatsCacheDir) {
		my @awstatsCacheFiles = iMSCP::Dir->new(
			dirname => $awstatsCacheDir,
			fileType => '^(?:awstats[0-9]+|dnscachelastupdate)' . quotemeta(".$data->{'DOMAIN_NAME'}.txt")
		)->getFiles();

		if(@awstatsCacheFiles) {
			my $file = iMSCP::File->new();

			for(@awstatsCacheFiles) {
				$file->{'filename'} = "$awstatsCacheDir/$_";
				$rs = $file->delFile();
				return $rs if $rs;
			}
		}
	}

	$self->_deleteAwstatsCronTask($data);
}

=item addSub(\%data)

 Process addSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
	my ($self, $data) = @_;

	$self->addDmn($data);
}

=item deleteSub(\%data)

 Process deleteSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
	my ($self, $data) = @_;

	$self->deleteDmn($data);
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
	my $self = $_[0];

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/awstats";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'tplDir'} = "$self->{'cfgDir'}/parts";

	iMSCP::EventManager->getInstance()->register('afterHttpdBuildConf', sub { $self->_addAwstatsSection(@_); });

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

	if($tplName =~ /^domain(?:_ssl)?\.tpl$/ && $data->{'FORWARD'} eq 'no') {
		require Servers::httpd;
		my $httpd = Servers::httpd->factory();

		my $version = $httpd->{'config'}->{'HTTPD_VERSION'};

		$$cfgTpl = replaceBloc(
			"# SECTION addons BEGIN.\n",
			"# SECTION addons END.\n",

			"    # SECTION addons BEGIN.\n" .
			getBloc(
				"# SECTION addons BEGIN.\n",
				"# SECTION addons END.\n",
				$$cfgTpl
			) .
			process(
				{
					AUTHZ_ALLOW_ALL => (version->parse($version) >= version->parse('2.4.0'))
						? 'Require all granted' : 'Allow from all',
					AWSTATS_WEB_DIR => $main::imscpConfig{'AWSTATS_WEB_DIR'},
					DOMAIN_NAME => $data->{'DOMAIN_NAME'},
					HOME_DIR => $data->{'HOME_DIR'},
					HTACCESS_USERS_FILENAME => $httpd->{'config'}->{'HTACCESS_USERS_FILENAME'},
					HTACCESS_GROUPS_FILENAME => $httpd->{'config'}->{'HTACCESS_GROUPS_FILENAME'}
				},
				$self->_getApacheConfSnippet()
			) .
			"    # SECTION addons END.\n",

			$$cfgTpl
		);
	}

	0;
}

=item _getApacheConfSnippet()

 Get apache configuration snippet

 Return string

=cut

sub _getApacheConfSnippet
{
	if($main::imscpConfig{'AWSTATS_MODE'}) { # static mode
		<<EOF;
    Alias /awstatsicons "{AWSTATS_WEB_DIR}/icon/"
    Alias /stats "{HOME_DIR}/statistics/"

    <Directory "{HOME_DIR}/statistics">
        AllowOverride AuthConfig
        DirectoryIndex awstats.{DOMAIN_NAME}.html
        {AUTHZ_ALLOW_ALL}
    </Directory>

    <Location /stats>
        AuthType Basic
        AuthName "Statistics for domain {DOMAIN_NAME}"
        AuthUserFile {HOME_DIR}/{HTACCESS_USERS_FILENAME}
        AuthGroupFile {HOME_DIR}/{HTACCESS_GROUPS_FILENAME}
        Require group statistics
    </Location>
EOF
	} else { # Dynamic mode
		<<EOF;
    ProxyRequests Off
    ProxyPass /stats http://localhost/stats/{DOMAIN_NAME}
    ProxyPassReverse /stats http://localhost/stats/{DOMAIN_NAME}

    <Location /stats>
        RewriteEngine on
        RewriteRule ^(.+)\?config=([^\?\&]+)(.*) \$1\?config={DOMAIN_NAME}&\$3 [NC,L]
        AuthType Basic
        AuthName "Statistics for domain {DOMAIN_NAME}"
        AuthUserFile {HOME_DIR}/{HTACCESS_USERS_FILENAME}
        AuthGroupFile {HOME_DIR}/{HTACCESS_GROUPS_FILENAME}
        Require group statistics
    </Location>
EOF
	}
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
	unless(defined $tplFileContent) {
		error("Unable to read $tplFileContent->{'filename'}");
		return 1;
	}

	require Servers::httpd;
	my $httpd = Servers::httpd->factory();

	my $tags = {
		ALIAS => $data->{'ALIAS'},
		AWSTATS_CACHE_DIR => $main::imscpConfig{'AWSTATS_CACHE_DIR'},
		AWSTATS_ENGINE_DIR => $main::imscpConfig{'AWSTATS_ENGINE_DIR'},
		AWSTATS_WEB_DIR => $main::imscpConfig{'AWSTATS_WEB_DIR'},
		CMD_LOGRESOLVEMERGE => "perl $awstatsPackageRootDir/Scripts/logresolvemerge.pl",
		DOMAIN_NAME => $data->{'DOMAIN_NAME'},
		LOG_DIR => "$httpd->{'config'}->{'HTTPD_LOG_DIR'}/$data->{'DOMAIN_NAME'}",
	};

	$tplFileContent = process($tags, $tplFileContent);

	unless(defined $tplFileContent) {
		error("Error while building Awstats configuration file");
		return 1;
	}

	my $file = iMSCP::File->new(
		filename => "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$data->{'DOMAIN_NAME'}.conf"
	);

	my $rs = $file->set($tplFileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
}

=item _addAwstatsCronTask(\%data)

 Add Awstats cron task

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub _addAwstatsCronTask
{
	my ($self, $data) = @_;

	Servers::cron->factory()->addTask(
		{
			TASKID => "Package::Webstats::Awstats ($data->{'DOMAIN_NAME'})",
			MINUTE => int(rand(60)), # random number between 0..59
			HOUR => int(rand(6)), # random number between 0..5
			DAY => '*',
			MONTH => '*',
			DWEEK => '*',
			USER => $main::imscpConfig{'ROOT_USER'},
			COMMAND =>
				'nice -n 15 ionice -c2 -n5 perl ' .
				"$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats/Awstats/Scripts/awstats_buildstaticpages.pl " .
				"-config=$data->{'DOMAIN_NAME'} -update " .
				"-awstatsprog=$main::imscpConfig{'AWSTATS_ENGINE_DIR'}/awstats.pl " .
				"-dir=$data->{'HOME_DIR'}/statistics > /dev/null 2>&1"
		}
	);
}

=item _deleteAwstatsCronTask(\%data)

 Remove AWStats cron task

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub _deleteAwstatsCronTask
{
	my ($self, $data) = @_;

	Servers::cron->factory()->deleteTask({ 'TASKID' => "Addons::Webstats::Awstats ($data->{'DOMAIN_NAME'})" });
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
