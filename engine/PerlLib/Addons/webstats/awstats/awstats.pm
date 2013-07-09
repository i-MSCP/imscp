#!/usr/bin/perl

=head1 NAME

Addons::webstats::awstats::awstats - i-MSCP Awstats addon

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::webstats::awstats::awstats;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Execute;
use iMSCP::Templator;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Ext2Attributes qw(setImmutable clearImmutable);
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Awstats addon for i-MSCP.

 Advanced Web Statistics (AWStats) is a powerful Web server logfile analyzer written in perl that shows you all your Web
statistics including visits, unique visitors, pages, hits, rush hours, search engines, keywords used to find your site,
robots, broken links and more.

 Project homepage: http://awstats.sourceforge.net/

=head1 PUBLIC METHODS

=over 4

=item preaddDmn($\data)

 Schedule addition of Apache configuration snipped for Awstats.

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub preaddDmn
{
	my $self = shift;
	my $data = shift;

	iMSCP::HooksManager->getInstance()->register('beforeHttpdBuildConf', sub { $self->_addAwstatsSection(@_); });
}

=item addDmn(\$data)

 Add Awstats configuration file and cron task.

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub addDmn
{
	my $self = shift;
	my $data = shift;

	my $rs = $self->_addAwstatsConfig($data);
	return $rs if $rs;

	my $userStatisticsDir = "$data->{'HOME_DIR'}/statistics";

	# Unprotect home directory
	$rs = clearImmutable($data->{'HOME_DIR'});
	return $rs if $rs;

	if($main::imscpConfig{'AWSTATS_MODE'} eq '1') { # Static mode
		# Create statistics directory if doesn't not exist - Set its permissions, owner and group
		$rs = iMSCP::Dir->new(
			'dirname' => $userStatisticsDir
		)->make(
			{ 'mode' => 02750, 'user' => $main::imscpConfig{'ROOT_USER'}, 'group' => $data->{'GROUP'} }
		);
		return $rs if $rs;

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

		$rs = $self->_addAwstatsCronTask($data);
		return $rs if $rs;

		# Schedule static pages generation to avoid empty statistics directory
		my ($stdout, $stderr);
		$rs = execute(
			"umask 027; $main::imscpConfig{'CMD_ECHO'} " .
			"'perl $main::imscpConfig{'AWSTATS_ROOT_DIR'}/awstats_buildstaticpages.pl -config=$data->{'DOMAIN_NAME'} " .
			"-update -awstatsprog=$main::imscpConfig{'AWSTATS_ENGINE_DIR'}/awstats.pl -dir=$userStatisticsDir' " .
			 "| $main::imscpConfig{'CMD_BATCH'}",
			\$stdout, \$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		error("Unable to schedule generation of Awstats static pages") if $rs && ! $stderr;
		return $rs if $rs;
	} else {
		$rs = iMSCP::Dir->new('dirname' => $userStatisticsDir)->remove() if -d $userStatisticsDir;
	}

	# Protect home directory if needed
	$rs = setImmutable($data->{'HOME_DIR'}) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';
	return $rs if $rs;

	$rs;
}

=item deleteDmn(\$data)

 Delete Awstats configuration.

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub deleteDmn
{
	my $self = shift;
	my $data = shift;

	my $cfgFileName = "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.$data->{'DOMAIN_NAME'}.conf";
	my $wrkFileName = "$self->{'wrkDir'}/awstats.$data->{'DOMAIN_NAME'}.conf";

	my $rs = iMSCP::File->new('filename' => $cfgFileName)->delFile() if -f $cfgFileName;
	return $rs if $rs;

	$rs = iMSCP::File->new('filename' => $wrkFileName)->delFile() if -f $wrkFileName;
	return $rs if $rs;

	# Remove Awstats static HTML files if any
	if($main::imscpConfig{'AWSTATS_MODE'} eq '1') { # Static mode
		my $userStatisticsDir = "$data->{'HOME_DIR'}/statistics";

		if(-d $userStatisticsDir) {
			my @awstatsStaticFiles = iMSCP::Dir->new(
				'dirname' => $userStatisticsDir, 'fileType' => '.html'
			)->getFiles();

			for(@awstatsStaticFiles) {
				if(/^awstats\.$data->{'DOMAIN_NAME'}\./) {
					$rs = iMSCP::File->new('filename' => "$userStatisticsDir/$_")->delFile();
					return $rs if $rs;
				}
			}
		}
	}

	# Remove Awstats cache files if any
	my $awstatsCacheDir = $main::imscpConfig{'AWSTATS_CACHE_DIR'};

	if(-d $awstatsCacheDir) {
		my @awstatsCacheFiles = iMSCP::Dir->new('dirname' => $awstatsCacheDir, 'fileType' => '.txt')->getFiles();

		for(@awstatsCacheFiles) {
			$rs = iMSCP::File->new('filename' => "$awstatsCacheDir/$_")->delFile() if /$data->{'DOMAIN_NAME'}\.txt$/;
			return $rs if $rs;
		}
	}

	$self->_deleteAwstatsCronTask($data);
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance() - Initialize instance.

 Return Addons::awstats

=cut

sub _init
{
	my $self = shift;

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/awstats";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'tplDir'} = "$self->{'cfgDir'}/parts";

	$self;
}

=item _addAwstatsSection(\$content, $filename)

 Add Apache configuration snippet for Awstats in the given domain vhost template file.

 Filter hook function responsible to build and insert Apache configuration snipped for Awstats in the given domain vhost
 file. The type of configuration snippet inserted depends on the Awstats mode (dynamic or static). If the received file
 is not the one expected, this function will register itself on the hooks manager to act on the next file.

 Param SCALAR reference - A scalar reference containing file content
 Param SCALAR Filename
 Return int - 0 on success, 1 on failure

=cut

sub _addAwstatsSection
{
	my $self = shift;
	my $content = shift;
	my $filename = shift;
	my $rs = 0;

	if($filename =~ /domain.*tpl/) {
		my $beginTag = "# SECTION addons BEGIN.\n";
		my $endTag = "# SECTION addons END.\n";

		# Getting addons configuration section from Apache template file
		my $addonsConfSection = getBloc($beginTag, $endTag, $$content);

		# Build Apache configuration snippet for Awstats
		$addonsConfSection .= process(
			{
				AWSTATS_WEB_DIR => $main::imscpConfig{'AWSTATS_WEB_DIR'},
				WEBSTATS_GROUP_AUTH => $main::imscpConfig{'WEBSTATS_GROUP_AUTH'},
				WEBSTATS_RPATH => $main::imscpConfig{'WEBSTATS_RPATH'}
			},
			$self->_getApacheConfSnippet()
		);

		# Add Apache configuration snippet for Awstats into the addons configuration section
		$$content = replaceBloc($beginTag, $endTag, "    $beginTag$addonsConfSection    $endTag", $$content);
	} else {
		$rs = iMSCP::HooksManager->getInstance()->register(
			'beforeHttpdBuildConf', sub { $self->_addAwstatsSection(@_); }
		);
	}

	$rs;
}

=item _getApacheConfSnippet()

 Get apache configuration snippet.

 Return string

=cut

sub _getApacheConfSnippet
{
	my $self = shift;

	if($main::imscpConfig{'AWSTATS_MODE'}) { # static mode
		return <<EOF;
    Alias /awstatsicons "{AWSTATS_WEB_DIR}/icon/"
    Alias /{WEBSTATS_RPATH} "{HOME_DIR}/statistics/"
    <Directory "{HOME_DIR}/statistics">
        AllowOverride AuthConfig
        DirectoryIndex awstats.{DOMAIN_NAME}.html
        Order allow,deny
        Allow from all
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
		return <<EOF;
    ProxyRequests Off
    ProxyPass /{WEBSTATS_RPATH} http://localhost/{WEBSTATS_RPATH}/{DOMAIN_NAME}
    ProxyPassReverse /{WEBSTATS_RPATH} http://localhost/{WEBSTATS_RPATH}/{DOMAIN_NAME}
    <Location /{WEBSTATS_RPATH}>
        <IfModule mod_rewrite.c>
            RewriteEngine on
            RewriteRule ^(.+)\?config=([^\?\&]+)(.*) \$1\?config={DOMAIN_NAME}&\$3 [NC,L]
        </IfModule>
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

 Add awstats configuration file for the given domain.

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, other on failure

=cut

sub _addAwstatsConfig
{
	my $self = shift;
	my $data = shift;

	my $tplFile = "$self->{'tplDir'}/awstats.imscp_tpl.conf";
	my $cfgFileName = "awstats.$data->{'DOMAIN_NAME'}.conf";
	my $bkpFile = "$self->{'bkpDir'}/$cfgFileName";
	my $cfgFile = "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/$cfgFileName";
	my $wrkFile = "$self->{'wrkDir'}/$cfgFileName";

	# Save current working file if any
	my $rs = iMSCP::File->new('filename' => $wrkFile)->copyFile("$bkpFile." . time) if -f $wrkFile;
	return $rs if $rs;

	# Loading template file
	my $cfgFileContent = iMSCP::File->new('filename' => $tplFile)->get();
	unless(defined $cfgFileContent) {
		error("Unable to read $tplFile");
		return 1;
	}

	my $tags = {
		DOMAIN_NAME => $data->{'DOMAIN_NAME'},
		AWSTATS_CACHE_DIR => $main::imscpConfig{'AWSTATS_CACHE_DIR'},
		AWSTATS_ENGINE_DIR => $main::imscpConfig{'AWSTATS_ENGINE_DIR'},
		AWSTATS_WEB_DIR => $main::imscpConfig{'AWSTATS_WEB_DIR'}
	};

	$cfgFileContent = process($tags, $cfgFileContent);

	require Servers::httpd;
	$cfgFileContent = Servers::httpd->factory()->buildConf($cfgFileContent);

	unless(defined $cfgFileContent) {
		error("Error while building $cfgFile");
		return 1;
	}

	# Store the file in the working directory
	my $file = iMSCP::File->new('filename' => $wrkFile);

	$rs = $file->set($cfgFileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	# Install the file in the production directory
	$file->copyFile($cfgFile);
}

=item _addAwstatsCronTask(\$data)

 Add Awstats cron task.

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub _addAwstatsCronTask
{
	my $self = shift;
	my $data = shift;

	require Servers::cron;
	Servers::cron->factory()->addTask(
		{
			TASKID => "AWSTATS:$data->{'DOMAIN_NAME'}",
			MINUTE => int(rand(60)), # random number between 0..59
			HOUR => int(rand(6)), # random number between 0..5
			DAY => '*',
			MONTH => '*',
			DWEEK => '*',
			USER => $main::imscpConfig{'ROOT_USER'},
			COMMAND =>
				"umask 027; perl $main::imscpConfig{'AWSTATS_ROOT_DIR'}/awstats_buildstaticpages.pl " .
				"-config=$data->{'DOMAIN_NAME'} -update " .
				"-awstatsprog=$main::imscpConfig{'AWSTATS_ENGINE_DIR'}/awstats.pl " .
				"-dir=$data->{'HOME_DIR'}/statistics >/dev/null 2>&1"
		}
	);
}

=item _deleteAwstatsCronTask(\$data)

 Remove Awstats cron task.

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub _deleteAwstatsCronTask
{
	my $self = shift;
	my $data = shift;

	require Servers::cron;
	Servers::cron->factory()->deleteTask({ 'TASKID' => "AWSTATS:$data->{'DOMAIN_NAME'}" });
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
