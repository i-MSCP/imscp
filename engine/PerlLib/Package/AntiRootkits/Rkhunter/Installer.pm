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
	my $self = shift;

	my $rs = $self->_addCronTask();
	$rs ||= $self->_scheduleCheck();
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	require iMSCP::Rights;
	iMSCP::Rights->import();

	my $rs = setRights("$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/AntiRootkits/Rkhunter/Cron.pl", {
		user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'ROOT_USER'}, mode => '0700'
	});
	return $rs if $rs;

	return 0 unless -f $main::imscpConfig{'RKHUNTER_LOG'};

	setRights($main::imscpConfig{'RKHUNTER_LOG'}, {
		user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'IMSCP_GROUP'}, mode => '0640'
	});
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
			error(sprintf('Could not read %s file', $file->{'filename'}));
			return 1;
		}

		$fileContent =~ s/CRON_DAILY_RUN=".*"/CRON_DAILY_RUN="false"/i;
		$fileContent =~ s/CRON_DB_UPDATE=".*"/CRON_DB_UPDATE="false"/i;

		my $rs = $file->set($fileContent);
		$rs ||= $file->save();
		return $rs if $rs;
	}

	if(-f '/etc/cron.daily/rkhunter') {
		my $rs = iMSCP::File->new( filename => '/etc/cron.daily/rkhunter' )->moveFile(
			'/etc/cron.daily/rkhunter.disabled'
		);
		return $rs if $rs;
	}

	if(-f '/etc/cron.weekly/rkhunter') {
		my $rs = iMSCP::File->new( filename => '/etc/cron.weekly/rkhunter' )->moveFile(
			'/etc/cron.weekly/rkhunter.disabled'
		);
		return $rs if $rs;
	}

	if(-f '/etc/logrotate.d/rkhunter') {
		my $rs = iMSCP::File->new( filename => '/etc/logrotate.d/rkhunter' )->moveFile(
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
	my $file = iMSCP::File->new(
		filename => "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/AntiRootkits/Rkhunter/Cron.pl"
	);

	my $fileContent = $file->get();
	unless(defined $fileContent ) {
		error(sprintf('Could not read %s file', $$file->{'filename'}));
		return 1;
	}

	$fileContent = process({ IMSCP_PERLLIB_PATH => "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib" }, $fileContent);

	my $rs = $file->set($fileContent);
	$rs ||= $file->save();
	$rs ||= $file->mode(0700);
	$rs ||= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs ||= Servers::cron->factory()->addTask( {
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
	});
}

=item _scheduleCheck()

 Schedule check if log file doesn't exist or is empty

 Return int 0 on success, other on failure

=cut

sub _scheduleCheck
{
	return 0 if -f -s $main::imscpConfig{'RKHUNTER_LOG'};

	# Create an empty file to avoid planning multiple check if installer is run many time
	my $file = iMSCP::File->new( filename => $main::imscpConfig{'RKHUNTER_LOG'} );
	my $rs = $file->set('Check scheduled...');
	$rs ||= $file->save();
	return $rs if $rs;

	$rs = execute(
		"echo 'perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/AntiRootkits/Rkhunter/Cron.pl > /dev/null 2>&1' " .
		"| batch",
		\ my $stdout,
		\ my $stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Could not schedule Rkhunter check") if $rs && ! $stderr;
	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
