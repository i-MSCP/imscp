#!/usr/bin/perl

=head1 NAME

Addons::awstats - i-MSCP Awstats addon

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2012 by internet Multi Server Control Panel
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
# @category		i-MSCP
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::awstats;

use strict;
use warnings;
use iMSCP::Debug;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Awstats addon for i-MSCP.

 Advanced Web Statistics (AWStats) is a powerful web server logfile analyzer written in perl that shows you all your
web statistics including visits, unique visitors, pages, hits, rush hours, search engines, keywords used to find your
site, robots, broken links and more.

 Project homepage: http://awstats.sourceforge.net/

=head1 CLASS METHODS

=over 4

=item factory()

 Implement singleton design pattern. Return instance of this class.

 Return Addons::awstats

=cut

sub factory
{
	Addons::awstats->new();
}

=back

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks($hooksManager)

 Register setup hook functions.

 Param iMSCP::HooksManager instance
 Return int - 0 on success, 1 on failure

=cut

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	use Addons::awstats::installer;
	Addons::awstats::installer->new()->registerSetupHooks($hooksManager);
}

=item install()

 Run the install method on the awstats addon installer.

 Return int - 0 on success, 1 on failure

=cut

sub install
{
	my $self = shift;

	use Addons::awstats::installer;
	Addons::awstats::installer->new()->install();
}

=item preaddDmn($\data)

 Register the awstatsSection or delAwstatsSection filter hook function according awstats addon status (On|Off).

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub preaddDmn
{
	my $self = shift;
	my $data = shift;

	use iMSCP::HooksManager;

	if($main::imscpConfig{'AWSTATS_ACTIVE'} && $main::imscpConfig{'AWSTATS_ACTIVE'} =~ /^yes$/i) {
		iMSCP::HooksManager->getInstance()->register(
			'beforeHttpdBuildConf', sub { return $self->awstatsSection(@_); }
		);
	} else {
		iMSCP::HooksManager->getInstance()->register(
    		'beforeHttpdBuildConf', sub { return $self->delAwstatsSection(@_); }
    	);
	}
}

=item addDmn(\$data)

 Add awstats configuration file and cron task.

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub addDmn{

	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$rs |= iMSCP::Dir->new(
		dirname => "/$data->{HOME_DIR}/statistics"
	)->make({
		mode => 0755,
		user => $data->{'USER'},
		group => $data->{'GROUP'}
	}) if $main::imscpConfig{'AWSTATS_MODE'};

	if($main::imscpConfig{'AWSTATS_ACTIVE'} && $main::imscpConfig{'AWSTATS_ACTIVE'} =~ /^yes$/i){
		$rs |= $self->_addAwstatsCfg($data);
		$rs |= $self->_addAwstatsCron($data) if $main::imscpConfig{'AWSTATS_MODE'};
	}

	$rs;
}

=item preaddSub(\$data)

 Register the delAwstatsSection filter hook function.

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub preaddSub
{
	my $self = shift;
	my $data = shift;

	use iMSCP::HooksManager;

	iMSCP::HooksManager->getInstance()->register(
		'beforeHttpdBuildConf', sub { return $self->delAwstatsSection(@_); }
	);
}

=item delDmn()

 Delete awstats configuration for the given domain.

 This is a method that is responsible to delete awstats configuration file and cron task for the given domain
(as specified by the domain data received).

 Param HASH reference - A reference to a hash containing domain data
Return int - 0 on success, 1 on failure

=cut

sub delDmn
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my $cfgFileName = "$main::imscpConfig{AWSTATS_CONFIG_DIR}/awstats.$data->{DMN_NAME}.conf";
	my $wrkFileName = "$self->{wrkDir}/awstats.$data->{DMN_NAME}.conf";

	$rs |= iMSCP::File->new(filename => $cfgFileName)->delFile() if -f $cfgFileName;
	$rs |= iMSCP::File->new(filename => $wrkFileName)->delFile() if -f $wrkFileName;
	$rs |= $self->_delAwstatsCron($data);

	$rs;
}

=back

=head1 HOOK FUNCTIONS

=over 4

=item awstatsSection(\$content, $filename)

 Add awstats section in the given domain template file.

 Filter hook function that is responsible to add awstats section in domain template file. The type of section added
depends on the awstats mode (dynamic or static). If the file received is not the one expected, this function will
auto-register itself to act on the next file.

 Param SCALAR reference - A scalar reference containing file content
 Param SCALAR Filename
 Return int - 0 on success, 1 on failure

=cut

sub awstatsSection
{
	my $self = shift;
	my $content = shift;
	my $filename = shift;

	if($filename =~ /domain.*tpl/) {

		use iMSCP::Templator;

		my ($bTag, $eTag);

		# Define tags for unused awstats section
		if($main::imscpConfig{AWSTATS_MODE} ne '1') {
			$bTag = "# SECTION awstats static BEGIN.\n";
			$eTag = "# SECTION awstats static END.\n";
		} else {
			$bTag = "# SECTION awstats dynamic BEGIN.\n";
			$eTag = "# SECTION awstats dynamic END.\n";
		}

		# Remove useless section
		$$content = replaceBloc($bTag, $eTag, '', $$content, undef);

		my $tags = {
			AWSTATS_CACHE_DIR => $main::imscpConfig{'AWSTATS_CACHE_DIR'},
			AWSTATS_CONFIG_DIR => $main::imscpConfig{'AWSTATS_CONFIG_DIR'},
			AWSTATS_ENGINE_DIR => $main::imscpConfig{'AWSTATS_ENGINE_DIR'},
			AWSTATS_WEB_DIR => $main::imscpConfig{'AWSTATS_WEB_DIR'},
			AWSTATS_ROOT_DIR => $main::imscpConfig{'AWSTATS_ROOT_DIR'},
			AWSTATS_GROUP_AUTH => $main::imscpConfig{'AWSTATS_GROUP_AUTH'}
		};

		# Process placeholders data for awstats section
		$$content = process($tags, $$content);
	} else {
		iMSCP::HooksManager->getInstance()->register(
			'beforeHttpdBuildConf', sub { return $self->awstatsSection(@_); }
		);
	}

	0;
}

=item delAwstatsSection(\$content, $filename)

 Delete awstats section in the given domain template file.

 Filter hook function that is responsible to delete awstats support section in the domain template file. If the file
received is not the one expected, this function will auto-register itself to act on the next file.

 Param SCALAR reference - A scalar reference containing file content
 Param SCALAR Filename
 Return int - 0 on success, 1 on failure

=cut

sub delAwstatsSection
{
	my $self = shift;
	my $content = shift;
	my $filename = shift;

	if($filename =~ /domain.*tpl/){

		use iMSCP::Templator;

		my $bTag = "# SECTION awstats support BEGIN.\n";
		my $eTag = "# SECTION awstats support END.\n";

		$$content = replaceBloc($bTag, $eTag, '', $$content, undef);
	}  else {
		iMSCP::HooksManager->getInstance()->register(
			'beforeHttpdBuildConf', sub { return $self->delAwstatsSection(@_); }
		) and return 1;
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by new() - Initialize instance.

 Return Addons::awstats

=cut

sub _init
{
	my $self = shift;

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/awstats";
	$self->{'bkpDir'} = "$self->{cfgDir}/backup";
	$self->{'wrkDir'} = "$self->{cfgDir}/working";
	$self->{'tplDir'} = "$self->{cfgDir}/parts";

	$self;
}

=item _addAwstatsCfg(\$data)

 Add awstats configuration file for the given domain.

 This is a method that is responsible to add awstats configuration file for the given domain (as specified by domain
data received).

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, other on failure

=cut

sub _addAwstatsCfg
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	use iMSCP::File;
	use iMSCP::Templator;
	use Servers::httpd;

	my $cfgFileName	= "awstats.$data->{DMN_NAME}.conf";
	my $cfgFile	= "$main::imscpConfig{AWSTATS_CONFIG_DIR}/$cfgFileName";
	my $tplFile	= "$self->{tplDir}/awstats.imscp_tpl.conf";
	my $wrkFile	= "$self->{wrkDir}/$cfgFileName";

	my $cfgFileContent = iMSCP::File->new(filename => $tplFile)->get();

	# Saving the current production file if it exists
	$rs |= iMSCP::File->new(filename => $cfgFile)->copyFile("$self->{bkpDir}/$cfgFileName." . time) if -f $cfgFile;

	# Load template file
	if(!$cfgFileContent){
		error("Can not load $tplFile");
		return 1;
	}

	my $tags = {
		DOMAIN_NAME => $data->{'DMN_NAME'},
		CMD_CAT => $main::imscpConfig{'CMD_CAT'},
		AWSTATS_CACHE_DIR => $main::imscpConfig{'AWSTATS_CACHE_DIR'},
		AWSTATS_ENGINE_DIR => $main::imscpConfig{'AWSTATS_ENGINE_DIR'},
		AWSTATS_WEB_DIR => $main::imscpConfig{'AWSTATS_WEB_DIR'}
	};

	$cfgFileContent = process($tags, $cfgFileContent);

	my $httpd = Servers::httpd->factory();
	$cfgFileContent = $httpd->buildConf($cfgFileContent);

	if(!$cfgFileContent){
		error("Error while building $cfgFile");
		return 1;
	}

	# Store the file in the working directory
	my $file = iMSCP::File->new(filename => $wrkFile);

	$rs |= $file->set($cfgFileContent);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	# Install the file in the production directory
	$rs |= $file->copyFile($main::imscpConfig{'AWSTATS_CONFIG_DIR'});

	$rs;
}

=item _addAwstatsCron(\$data)

 Add awstats cron task for the given domain.

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub _addAwstatsCron
{
	my $self = shift;
	my $data = shift;

	use iMSCP::File;
	use iMSCP::Templator;
	use Servers::cron;

	my $cron = Servers::cron->factory();

	$cron->addTask({
		MINUTE => int(rand(61)), # random number between 0..60
		HOUR => int(rand(6)), # random number between 0..5
		DAY => '*',
		MONTH => '*',
		DWEEK => '*',
		USER => $data->{'USER'},
		C0MMAND	=>	"perl $main::imscpConfig{AWSTATS_ROOT_DIR}/awstats_buildstaticpages.pl ".
					"-config=$data->{DMN_NAME} -update ".
					"-awstatsprog=$main::imscpConfig{AWSTATS_ENGINE_DIR}/awstats.pl ".
					"-dir=$data->{HOME_DIR}/statistics/",
		TASKID	=> "AWSTATS:$data->{DMN_NAME}"
	});
}

=item _addAwstatsCron(\$data)

 Remove awstats cron task for the given domain.

 Param HASH reference - A reference to a hash containing domain data
 Return int - 0 on success, 1 on failure

=cut

sub _delAwstatsCron
{
	my $self = shift;
	my $data = shift;

	use Servers::cron;

	my $cron = Servers::cron->factory();

	$cron->delTask({ TASKID	=> "AWSTATS:$data->{DMN_NAME}" });
}

=back

=head1 AUTHORS

 - Daniel Andreca <sci2tech@gmail.com>
 - Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
