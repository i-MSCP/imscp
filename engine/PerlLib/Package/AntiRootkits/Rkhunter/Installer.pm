=head1 NAME

Package::AntiRootkits::Rkhunter::Installer - i-MSCP Rkhunter package installer

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

package Package::AntiRootkits::Rkhunter::Installer;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::TemplateParser;
use iMSCP::Execute;
use Servers::cron;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Rkhunter package installer.

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	$_[0]->_disableDebianConfig();
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->_addCronTask();
	return $rs if $rs;

	$self->_scheduleCheck();
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	require iMSCP::Rights;
	iMSCP::Rights->import();

	my $rs = setRights(
		"$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/AntiRootkits/Rkhunter/Cron.pl",
		{
			'user' => $main::imscpConfig{'ROOT_USER'},
			'group' => $main::imscpConfig{'ROOT_USER'},
			'mode' => '0700'
		}
	);

	if(-f $main::imscpConfig{'RKHUNTER_LOG'}) {
		$rs = setRights(
			$main::imscpConfig{'RKHUNTER_LOG'},
			{
				'user' => $main::imscpConfig{'ROOT_USER'},
				'group' => $main::imscpConfig{'IMSCP_GROUP'},
				'mode' => '0640'
			}
		);
	}

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _disableDebianConfig()

 Disable default configuration

 Return int 0 on success, other on failure

=cut

sub _disableDebianConfig
{
	if(-f '/etc/default/rkhunter') {
		my $file = iMSCP::File->new( filename => '/etc/default/rkhunter' );

		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error("Unable to read $file->{'filename'}");
			return 1;
		}

		$fileContent =~ s/CRON_DAILY_RUN=".*"/CRON_DAILY_RUN="false"/i;
		$fileContent =~ s/CRON_DB_UPDATE=".*"/CRON_DB_UPDATE="false"/i;

		my $rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	}

	if(-f '/etc/cron.daily/rkhunter') {
		my $rs = iMSCP::File->new(
			filename => '/etc/cron.daily/rkhunter'
		)->moveFile(
			'/etc/cron.daily/rkhunter.disabled'
		);
		return $rs if $rs;
	}

	if(-f '/etc/cron.weekly/rkhunter') {
		my $rs = iMSCP::File->new(
			filename => '/etc/cron.weekly/rkhunter'
		)->moveFile(
			'/etc/cron.weekly/rkhunter.disabled'
		);
		return $rs if $rs;
	}

	if(-f '/etc/logrotate.d/rkhunter') {
		my $rs = iMSCP::File->new(
			filename => '/etc/logrotate.d/rkhunter'
		)->moveFile(
			'/etc/logrotate.d/rkhunter.disabled'
		);
		return $rs if $rs;
	}

	0;
}

=item _addCronTask()

 Add cron task

 Return int 0 on success, other on failure

=cut

sub _addCronTask
{
	my $cronFile = iMSCP::File->new(
		filename => "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/AntiRootkits/Rkhunter/Cron.pl"
	);

	my $cronFileContent = $cronFile->get();
	unless(defined $cronFileContent ) {
		error("Unable to read $cronFile->{'filename'} file");
		return 1;
	}

	$cronFileContent = process(
		{ 'IMSCP_PERLLIB_PATH' => "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib" }, $cronFileContent
	);

	my $rs = $cronFile->set($cronFileContent);
	return $rs if $rs;

	$rs = $cronFile->save();
	return $rs if $rs;

	$rs = $cronFile->mode(0700);
	return $rs if $rs;

	$rs = $cronFile->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	Servers::cron->factory()->addTask(
		{
			TASKID => 'Package::AntiRootkits::Rkhunter',
			MINUTE => '@weekly',
			HOUR => '',
			DAY => '',
			MONTH => '',
			DWEEK => '',
			USER => $main::imscpConfig{'ROOT_USER'},
			COMMAND =>
				'nice -n 15 ionice -c2 -n5 perl ' .
				"$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/AntiRootkits/Rkhunter/Cron.pl " .
				"> /dev/null 2>&1"
		}
	);
}

=item _scheduleCheck()

 Schedule check if log file doesn't exist or is empty

 Return int 0 on success, other on failure

=cut

sub _scheduleCheck
{
	unless(-f -s $main::imscpConfig{'RKHUNTER_LOG'}) {
		# Create an empty file to avoid planning multiple check if installer is run many time
		my $file = iMSCP::File->new( filename => $main::imscpConfig{'RKHUNTER_LOG'} );

		my $rs = $file->set('Check scheduled...');
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		my ($stdout, $stderr);
		$rs = execute(
			"echo 'perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/AntiRootkits/Rkhunter/Cron.pl > /dev/null 2>&1' " .
			"| batch",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		error("Unable to schedule Rkhunter check") if $rs && ! $stderr;
		return $rs if $rs;
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
