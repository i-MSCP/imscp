=head1 NAME

 iMSCP::Bootstrapper - Bootstrap i-MSCP environment

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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

package iMSCP::Bootstrapper;

use strict;
use warnings;
use Carp;
use Fcntl ':flock';
use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::Requirements;
use iMSCP::Getopt;
use iMSCP::Database;
use IO::Handle;
use locale;
use POSIX qw(tzset locale_h);
use parent 'Common::SingletonClass';

umask 022;

autoflush STDOUT 1;
autoflush STDERR 1;

setlocale(LC_MESSAGES, 'C.UTF-8');

$ENV{'LANG'} = 'C.UTF-8';
$ENV{'PATH'} = '/usr/sbin:/usr/bin:/sbin:/bin:/usr/local/sbin:/usr/local/bin';

=head1 DESCRIPTION

 i-MSCP Bootstrapper. Bootstrap i-MSCP environment.

=head1 PUBLIC METHODS

=over 4

=item boot()

 i-MSCP Bootstrapper

 Return iMSCP::Bootstrapper, die on failure

=cut

sub boot
{
	my ($self, $options) = @_;

	my $mode = $options->{'mode'} || 'backend';
	debug("Booting $mode...");

	tie
		%main::imscpConfig,
		'iMSCP::Config',
		fileName => ($^O =~ /bsd$/ ? '/usr/local/etc/' : '/etc/') . 'imscp/imscp.conf',
		nocreate => 1,
		nofail => $options->{'nofail'},
		readonly => $options->{'config_readonly'};

	# Set timezone unless we are in setup mode (needed to show current local timezone in setup dialog)
	unless($mode eq 'setup') {
		$ENV{'TZ'} = $main::imscpConfig{'TIMEZONE'} || 'UTC';
		tzset;
	}

	setDebug(iMSCP::Getopt->debug || $main::imscpConfig{'DEBUG'} || 0);

	unless($options->{'norequirements'}) {
		my $test = ($mode eq 'setup') ? 'all' : 'user';
		iMSCP::Requirements->new()->$test();
	}

	$self->lock() unless $options->{'nolock'};
	$self->loadDbKeyAndIv() unless $options->{'nokeys'};

	unless ($options->{'nodatabase'}) {
		if(exists $main::imscpConfig{'DB_KEY'} && exists $main::imscpConfig{'DB_IV'}) {
			require iMSCP::Crypt;

			my $db = iMSCP::Database->factory();
			$db->set('DATABASE_HOST', $main::imscpConfig{'DATABASE_HOST'});
			$db->set('DATABASE_PORT', $main::imscpConfig{'DATABASE_PORT'});
			$db->set('DATABASE_NAME', $main::imscpConfig{'DATABASE_NAME'});
			$db->set('DATABASE_USER', $main::imscpConfig{'DATABASE_USER'});
			$db->set('DATABASE_PASSWORD', iMSCP::Crypt::decryptRijndaelCBC(
				$main::imscpConfig{'DB_KEY'}, $main::imscpConfig{'DB_IV'}, $main::imscpConfig{'DATABASE_PASSWORD'}
			));

			my $rs = $db->connect();
			(!$rs || ($options->{'nofail'})) or die(sprintf('Could not connect to the SQL server: %s', $rs));
		} else {
			croak('Could not decrypt database password without key or iv. Please, retry without the nokeys option');
		}
	}

	$self;
}

=item lock([ $file = '/tmp/imscp.lock', [ $nowait = 0 ]])

 Lock the given file or the engine lock file

 Param string $file OPTIONAL File to lock
 Param int $nowait No wait mode
 Return TRUE if the file has been locked, FALSE otherwise, die on failure

=cut

sub lock
{
	my $self = shift;
	my $file = shift || '/tmp/imscp.lock';
	my $nowait = shift || 0;

	unless(defined $self->{'locks'}->{$file}) {
		debug(sprintf('Acquire exclusive lock on %s', $file));
		open($self->{'locks'}->{$file}, '>', $file) or die(sprintf('Could not open %s for locking: %s', $file, $!));
		my $rs = flock($self->{'locks'}->{$file}, $nowait ? LOCK_EX|LOCK_NB : LOCK_EX);
		$rs || $nowait or die(sprintf( 'Unable to acquire lock on %s', $file));
		$rs;
	} else {
		1;
	}
}

=item unlock([ $file = '/tmp/imscp.lock' ])

 Unlock the given lock file or the engine lock file

 Param string $file OPTIONAL File to unlock
 Return TRUE on success, die on failure

=cut

sub unlock
{
	my ($self, $file) = (shift, shift || '/tmp/imscp.lock');

	if($self->{'locks'}->{$file}) {
		debug(sprintf('Release exclusive lock on %s', $file));
		flock($self->{'locks'}->{$file}, LOCK_UN) or die(sprintf('Could not release lock on %s', $file));
		close $self->{'locks'}->{$file} or die(sprintf('Could not close %s: %s', $self->{'locks'}->{$file}, $!));
		delete $self->{'locks'}->{$file};
	}

	1;
}

=back

=head1 PRIVATE METHODS

=over 4

=item loadDbKeyAndIv()

 Load encryption key and initiazation vector (create them if needed)

 Return undef

=cut

sub loadDbKeyAndIv
{
	tie my %imscpDbKeys, 'iMSCP::Config', fileName => "$main::imscpConfig{'CONF_DIR'}/imscp-db-keys", nowarn => 1;

	unless (
		defined $imscpDbKeys{'KEY'} && length $imscpDbKeys{'KEY'} == 32 &&
		defined $imscpDbKeys{'IV'} && length $imscpDbKeys{'IV'} == 16
	) {
		require iMSCP::Crypt;

		$main::imscpConfig{'DATABASE_PASSWORD'} = ''; # Force SQL password dialog
		%imscpDbKeys = (); # Clear any content (covers update from old imscp-db-keys file format)
		$imscpDbKeys{'KEY'} = iMSCP::Crypt::randomStr(32); # Generate new key
		$imscpDbKeys{'IV'} = iMSCP::Crypt::randomStr(16); # Generate new initialization vector
	}

	my $imscpConfigObj = tied %main::imscpConfig;
	$imscpConfigObj->{'temporary'} = 1;
	$main::imscpConfig{'DB_KEY'} = $imscpDbKeys{'KEY'};
	$main::imscpConfig{'DB_IV'} = $imscpDbKeys{'IV'};
	$imscpConfigObj->{'temporary'} = 0;
	undef;
}

=item END

 Unlock any locked file

=cut

END
{
	my $instance = __PACKAGE__->getInstance();

	for my $file ($instance->{locks}) {
		$instance->unlock($file);
	}
}

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
