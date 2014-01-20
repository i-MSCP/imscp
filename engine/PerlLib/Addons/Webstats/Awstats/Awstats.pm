#!/usr/bin/perl

=head1 NAME

Addons::Webstats::Awstats::Awstats - i-MSCP AWStats addon

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
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
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::Webstats::Awstats::Awstats;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Execute;
use iMSCP::TemplateParser;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Ext2Attributes qw(setImmutable clearImmutable);
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 AWStats addon for i-MSCP

 Advanced Web Statistics (AWStats) is a powerful Web server logfile analyzer written in perl that shows you all your Web
statistics including visits, unique visitors, pages, hits, rush hours, search engines, keywords used to find your site,
robots, broken links and more.

 Project homepage: http://awstats.sourceforge.net/

=head1 PUBLIC METHODS

=over 4

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog::Dialog|iMSCP::Dialog::Whiptail $dialog
 Return int 0 or 30

=cut

sub showDialog($$)
{
	my ($self, $dialog) = @_;

	require Addons::Webstats::Awstats::Installer;
	Addons::Webstats::Awstats::Installer->getInstance()->showDialog($dialog);
}

#=item preinstall()
#
# Process preinstall tasks
#
# Return int 0 on success, other on failure
#
#=cut
#
#sub preinstall
#{
#	require Addons::Webstats::Awstats::Installer;
#	Addons::Webstats::Awstats::Installer->getInstance()->preinstall();
#}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	require Addons::Webstats::Awstats::Installer;
	Addons::Webstats::Awstats::Installer->getInstance()->install();
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	require Addons::Webstats::Awstats::Uninstaller;
	Addons::Webstats::Awstats::Uninstaller->getInstance()->uninstall();
}

=item setEnginePermissions()

 Set files permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	require Addons::Webstats::Awstats::Installer;
	Addons::Webstats::Awstats::Installer->getInstance()->setEnginePermissions();
}

=item getPackages()

 Get list of Debian packages to which this addon depends

 Return array_ref An array containing list of packages

=cut

sub getPackages
{
	['awstats'];
}

=item addDmn(\%data)

 Add AWStats configuration file and cron task

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub addDmn($$)
{
	my ($self, $data) = @_;

	my $rs = $self->_addAwstatsConfig($data);
	return $rs if $rs;

	my $userStatisticsDir = "$data->{'HOME_DIR'}/statistics";

	# Unprotect home directory
	$rs = clearImmutable($data->{'HOME_DIR'});
	return $rs if $rs;

	if($main::imscpConfig{'AWSTATS_MODE'} eq '1') { # Static mode
		# Create statistics directory if it doesn't already exist - Set its permissions, owner and group in any case
		if(! -d $userStatisticsDir) {
			$rs = iMSCP::Dir->new(
				'dirname' => $userStatisticsDir
			)->make(
				{ 'mode' => 02750, 'user' => $main::imscpConfig{'ROOT_USER'}, 'group' => $data->{'GROUP'} }
			);
			return $rs if $rs;
		} else {
			require iMSCP::Rights;
			iMSCP::Rights->import();

			# Set user statistics pages permissions, owner and group
			$rs = setRights(
				$userStatisticsDir,
				{
					'filemode' => '0640',
					'user' => $main::imscpConfig{'ROOT_USER'},
					'group' => $data->{'GROUP'},
					'recursive' => 1
				}
			);
			return $rs if $rs;
		}

		$rs = $self->_addAwstatsCronTask($data);
		return $rs if $rs;

		# Schedule static pages generation for the domain if needed
		if(! -f "$userStatisticsDir/awstats.$data->{'DOMAIN_NAME'}.html") {
			my ($stdout, $stderr);
			$rs = execute(
				"umask 027; $main::imscpConfig{'CMD_ECHO'} " .
				"'$main::imscpConfig{'CMD_PERL'} " .
				"$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Addons/Webstats/Awstats/Scripts/awstats_buildstaticpages.pl " .
				"-config=$data->{'DOMAIN_NAME'} " .
				"-update -awstatsprog=$main::imscpConfig{'AWSTATS_ENGINE_DIR'}/awstats.pl -dir=$userStatisticsDir' " .
				"| $main::imscpConfig{'CMD_BATCH'}",
				\$stdout,
				\$stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			error("Unable to schedule generation of AWStats static pages") if $rs && ! $stderr;
			return $rs if $rs;
		}
	} else {
		$rs = iMSCP::Dir->new('dirname' => $userStatisticsDir)->remove();
	}

	# Protect home directory if needed
	$rs = setImmutable($data->{'HOME_DIR'}) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';
	return $rs if $rs;

	$rs;
}

=item deleteDmn(\%data)

 Delete AWStats configuration

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub deleteDmn($$)
{
	my ($self, $data) = @_;

	my $cfgFileName = "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$data->{'DOMAIN_NAME'}.conf";
	my $wrkFileName = "$self->{'wrkDir'}/awstats.$data->{'DOMAIN_NAME'}.conf";

	my $rs = 0;

	$rs = iMSCP::File->new('filename' => $cfgFileName)->delFile() if -f $cfgFileName;
	return $rs if $rs;

	$rs = iMSCP::File->new('filename' => $wrkFileName)->delFile() if -f $wrkFileName;
	return $rs if $rs;

	# Remove AWStats static HTML files if any
	if($main::imscpConfig{'AWSTATS_MODE'} eq '1') { # Static mode
		my $userStatisticsDir = "$data->{'HOME_DIR'}/statistics";

		if(-d $userStatisticsDir) {
			my @awstatsStaticFiles = iMSCP::Dir->new(
				'dirname' => $userStatisticsDir,
				'fileType' => '^' . quotemeta("awstats.$data->{'DOMAIN_NAME'}") . '.*?\\.html'
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

	# Remove AWStats cache files if any
	my $awstatsCacheDir = $main::imscpConfig{'AWSTATS_CACHE_DIR'};

	if(-d $awstatsCacheDir) {
		my @awstatsCacheFiles = iMSCP::Dir->new(
			'dirname' => $awstatsCacheDir,
			'fileType' => '^(?:awstats[0-9]+|dnscachelastupdate)' . quotemeta(".$data->{'DOMAIN_NAME'}.txt")
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

=item preaddSub(\%data)

 Schedule addition of Apache configuration snipped for AWStats

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub preaddSub
{
	my ($self, $data) = @_;

	$self->preaddDmn($data);
}

=item addSub(\%data)

 Add AWStats configuration file and cron task

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub addSub
{
	my ($self, $data) = @_;

	$self->addDmn($data);
}

=item deleteSub(\%data)

 Delete AWStats configuration

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub deleteSub($$)
{
	my ($self, $data) = @_;

	$self->deleteDmn($data);
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance() - Initialize instance

 Return Addons::Awstats

=cut

sub _init
{
	my $self = shift;

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/awstats";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'tplDir'} = "$self->{'cfgDir'}/parts";

	# Register event listener which is responsible to add Awstats configuration snippet in Apache vhost file
	iMSCP::HooksManager->getInstance()->register('afterHttpdBuildConf', sub { $self->_addAwstatsSection(@_); });

	$self;
}

=item _addAwstatsSection(\$cfgTpl, $filename)

 Add Apache configuration snippet for AWStats in the given domain vhost template file

 Listener responsible to build and insert Apache configuration snipped for AWStats in the given domain vhost file. The
type of configuration snippet inserted depends on the AWStats mode (dynamic or static).

 Param string $cfgTpl Reference to template file content
 Param string Template filename
 Param hash Domain data
 Return int - 0 on success, 1 on failure

=cut

sub _addAwstatsSection($$$$)
{
	my ($self, $cfgTpl, $tplName, $data) = @_;

	if($tplName =~ /^domain(?:_ssl)?\.tpl$/) {
		require Servers::httpd;
		my $httpd = Servers::httpd->factory();

		# Build and add Apache configuration snippet for AWStats

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
					AUTHZ_ALLOW_ALL => (version->new("v$httpd->{'config'}->{'APACHE_VERSION'}") >= version->new('v2.4.0'))
						? 'Require all granted' : 'Allow from all',
					AWSTATS_WEB_DIR => $main::imscpConfig{'AWSTATS_WEB_DIR'},
					DOMAIN_NAME => $data->{'DOMAIN_NAME'},
					HOME_DIR => $data->{'HOME_DIR'},
					HTACCESS_USERS_FILE_NAME => $httpd->{'config'}->{'HTACCESS_USERS_FILE_NAME'},
					HTACCESS_GROUPS_FILE_NAME => $httpd->{'config'}->{'HTACCESS_GROUPS_FILE_NAME'},
					WEBSTATS_GROUP_AUTH => $main::imscpConfig{'WEBSTATS_GROUP_AUTH'},
					WEBSTATS_RPATH => $main::imscpConfig{'WEBSTATS_RPATH'}
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
    Alias /{WEBSTATS_RPATH} "{HOME_DIR}/statistics/"

    <Directory "{HOME_DIR}/statistics">
        AllowOverride AuthConfig
        DirectoryIndex awstats.{DOMAIN_NAME}.html
        {AUTHZ_ALLOW_ALL}
    </Directory>

    <Location /{WEBSTATS_RPATH}>
        AuthType Basic
        AuthName "Statistics for domain {DOMAIN_NAME}"
        AuthUserFile {HOME_DIR}/{HTACCESS_USERS_FILE_NAME}
        AuthGroupFile {HOME_DIR}/{HTACCESS_GROUPS_FILE_NAME}
        Require group {WEBSTATS_GROUP_AUTH}
    </Location>
EOF
	} else { # Dynamic mode
		<<EOF;
    ProxyRequests Off
    ProxyPass /{WEBSTATS_RPATH} http://localhost/{WEBSTATS_RPATH}/{DOMAIN_NAME}
    ProxyPassReverse /{WEBSTATS_RPATH} http://localhost/{WEBSTATS_RPATH}/{DOMAIN_NAME}

    <Location /{WEBSTATS_RPATH}>
        RewriteEngine on
        RewriteRule ^(.+)\?config=([^\?\&]+)(.*) \$1\?config={DOMAIN_NAME}&\$3 [NC,L]
        AuthType Basic
        AuthName "Statistics for domain {DOMAIN_NAME}"
        AuthUserFile {HOME_DIR}/{HTACCESS_USERS_FILE_NAME}
        AuthGroupFile {HOME_DIR}/{HTACCESS_GROUPS_FILE_NAME}
        Require group {WEBSTATS_GROUP_AUTH}
    </Location>
EOF
	}
}

=item _addAwstatsConfig(\$data)

 Add awstats configuration file for the given domain

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, other on failure

=cut

sub _addAwstatsConfig
{
	my ($self, $data) = @_;

	my $awstatsAddonRootDir = "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Addons/Webstats/Awstats";

	# Loading template file
	my $tplFileContent = iMSCP::File->new('filename' => "$awstatsAddonRootDir/Config/awstats.imscp_tpl.conf")->get();
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
		CMD_LOGRESOLVEMERGE => "$main::imscpConfig{'CMD_PERL'} $awstatsAddonRootDir/Scripts/logresolvemerge.pl",
		DOMAIN_NAME => $data->{'DOMAIN_NAME'},
		LOG_DIR => "$httpd->{'config'}->{'APACHE_LOG_DIR'}/$data->{'DOMAIN_NAME'}",
	};

	$tplFileContent = process($tags, $tplFileContent);

	unless(defined $tplFileContent) {
		error("Error while building Awstats configuration file");
		return 1;
	}

	# Install file
	my $file = iMSCP::File->new(
		'filename' => "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$data->{'DOMAIN_NAME'}.conf"
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

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub _addAwstatsCronTask($$)
{
	my ($self, $data) = @_;

	require Servers::cron;
	Servers::cron->factory()->addTask(
		{
			TASKID => "Addons::Webstats::Awstats ($data->{'DOMAIN_NAME'})",
			MINUTE => int(rand(60)), # random number between 0..59
			HOUR => int(rand(6)), # random number between 0..5
			DAY => '*',
			MONTH => '*',
			DWEEK => '*',
			USER => $main::imscpConfig{'ROOT_USER'},
			COMMAND =>
				"umask 027; $main::imscpConfig{'CMD_PERL'} " .
				"$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Addons/Webstats/Awstats/Scripts/awstats_buildstaticpages.pl " .
				"-config=$data->{'DOMAIN_NAME'} -update " .
				"-awstatsprog=$main::imscpConfig{'AWSTATS_ENGINE_DIR'}/awstats.pl " .
				"-dir=$data->{'HOME_DIR'}/statistics >/dev/null 2>&1"
		}
	);
}

=item _deleteAwstatsCronTask(\%data)

 Remove AWStats cron task

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub _deleteAwstatsCronTask($$)
{
	my ($self, $data) = @_;

	require Servers::cron;
	Servers::cron->factory()->deleteTask({ 'TASKID' => "Addons::Webstats::Awstats ($data->{'DOMAIN_NAME'})" });
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
