#!/usr/bin/perl

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

use strict;
use warnings;

use lib '{IMSCP_PERLLIB_PATH}';

use iMSCP::Debug;
use iMSCP::Bootstrapper;
use iMSCP::Execute;
use iMSCP::File;

# Turn off localisation features to force any command output to be in English
$ENV{'LC_MESSAGES'} = 'C';

# Do not clear screen at end of script
$ENV{'IMSCP_CLEAR_SCREEN'} = 0;

umask(027);

newDebug('imscp-rkhunter-package.log');

silent(1);

iMSCP::Bootstrapper->getInstance()->boot(
	{
		'nolock' => 'yes', 'norequirements' => 'yes', 'nokeys' => 'yes', 'nodatabase' => 'yes',
		'config_readonly' => 'yes'
	}
);

my $rs = 0;

if(-x $main::imscpConfig{'CMD_RKHUNTER'}) {
	my $rkhunterLogFile =  $main::imscpConfig{'RKHUNTER_LOG'} || '/var/log/rkhunter.log';

	# Error handling is specific with rkhunter. Therefore, we do not handle the exit code, but we write the output
	# into the imscp-rkhunter-package.log file. This is calqued on the cron task as provided by the Rkhunter Debian
	# package except that instead of sending an email on error or warning, we write in log file.
	my ($stdout, $stderr);
	execute("$main::imscpConfig{'CMD_RKHUNTER'} --cronjob --logfile $rkhunterLogFile", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr;

	if(-f $rkhunterLogFile) {
		my $file = iMSCP::File->new('filename', $rkhunterLogFile);
		$rs = $file->mode(0640);
		$rs ||= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'IMSCP_GROUP'});
	}
}

exit $rs;
