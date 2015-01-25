#!/usr/bin/perl

=head1 NAME

 iMSCP::Bootstrapper - Boot i-MSCP

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
#
# @category    i-MSCP
# @copyright   2010-2015 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Bootstrapper;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::Requirements;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Database;
use Fcntl ":flock";
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Bootstrap class for i-MSCP.

=head1 PUBLIC METHODS

=over 4

=item boot()

 Boot i-MSCP

 Return iMSCP::Bootstrapper

=cut

sub boot
{
	my ($self, $options) = @_;

	my $mode = $options->{'mode'} || 'backend';
	debug("Booting $mode...");

	tie
		%main::imscpConfig,
		'iMSCP::Config',
		'fileName' => ($^O =~ /bsd$/ ? '/usr/local/etc/' : '/etc/') . 'imscp/imscp.conf',
		'nocreate' => 1, # Do not create file if it doesn't exist (raise error instead)
		'nofail' => $options->{'nofail'} && $options->{'nofail'} eq 'yes' ? 1 : 0,
		'readonly' => $options->{'config_readonly'} && $options->{'config_readonly'} eq 'yes' ? 1 : 0;

	# Set verbose mode
	verbose(iMSCP::Getopt->debug || $main::imscpConfig{'DEBUG'} || 0);

	iMSCP::Requirements->new()->test(
		$mode eq 'setup' ? 'all' : 'user'
	) unless($options->{'norequirements'} && $options->{'norequirements'} eq 'yes');

	$self->lock() unless($options->{'nolock'} && $options->{'nolock'} eq 'yes');

	$self->_genKeys() unless($options->{'nokeys'} && $options->{'nokeys'} eq 'yes');

	unless ($options->{'nodatabase'} && $options->{'nodatabase'} eq 'yes') {
		require iMSCP::Crypt;
		my $crypt = iMSCP::Crypt->getInstance();
		my $database = iMSCP::Database->factory();

		$database->set('DATABASE_HOST', $main::imscpConfig{'DATABASE_HOST'});
		$database->set('DATABASE_PORT', $main::imscpConfig{'DATABASE_PORT'});
		$database->set('DATABASE_NAME', $main::imscpConfig{'DATABASE_NAME'});
		$database->set('DATABASE_USER', $main::imscpConfig{'DATABASE_USER'});
		$database->set('DATABASE_PASSWORD', $crypt->decrypt_db_password($main::imscpConfig{'DATABASE_PASSWORD'}));
		my $rs = $database->connect();

		fatal("Unable to connect to the SQL server: $rs")
			if ($rs && ! ($options->{'nofail'} && $options->{'nofail'} eq 'yes'));
	}

	$self;
}

=item lock([$lockFile, [$nowait]])

 Lock the given file or the engine lock file

 Return int 1 on success, other on failure

=cut

sub lock
{
	my $self = shift;
	my $lockFile = shift || '/tmp/imscp.lock';
	my $nowait = shift || 0;

	my $rs = 1;

	unless(defined $self->{'locks'}->{$lockFile}) {
		debug("Acquire exclusive lock on $lockFile");

		fatal('Unable to open lock file') unless open($self->{'locks'}->{$lockFile}, '>', $lockFile);
		$rs = flock($self->{'locks'}->{$lockFile}, $nowait ? LOCK_EX | LOCK_NB : LOCK_EX);
		fatal('Unable to acquire lock') unless $rs || $nowait;
	}

	$rs;
}

=item unlock([$lockFile])

 Unlock the given lock file or the engine lock file

 Return iMSCP::Bootstrapper

=cut

sub unlock
{
	my $self = shift;
	my $lockFile = shift || '/tmp/imscp.lock';

	if(defined $self->{'locks'}->{$lockFile}) {
		debug("Release exclusive lock on $lockFile");

		fatal('Unable to release lock') if ! flock($self->{'locks'}->{$lockFile}, LOCK_UN);
		close $self->{'locks'}->{$lockFile};
		delete $self->{'locks'}->{$lockFile};
	}

	$self;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _genKeys()

 Generates encryption key and vector

 Return undef

=cut

sub _genKeys
{
	my $self = $_[0];

	my $keyFile = "$main::imscpConfig{'CONF_DIR'}/imscp-db-keys";
	our $db_pass_key = '{KEY}';
	our $db_pass_iv = '{IV}';

	require  iMSCP::Crypt;
	require "$keyFile" if -f $keyFile;

	if ($db_pass_key eq '{KEY}' || $db_pass_iv eq '{IV}') {
		debug('Generating database keys...');

		if(-d $main::imscpConfig{'CONF_DIR'}) {
			require Data::Dumper;
			Data::Dumper->import();

			open(KEYFILE, '>:utf8', "$main::imscpConfig{'CONF_DIR'}/imscp-db-keys")
				or fatal("Error: Unable to open file '$main::imscpConfig{'CONF_DIR'}/imscp-db-keys' for writing: $!");

			print KEYFILE Data::Dumper->Dump(
				[iMSCP::Crypt::randomString(32), iMSCP::Crypt::randomString(8)], [qw(db_pass_key db_pass_iv)]
			);

			close KEYFILE;
		} else {
			fatal("Destination path $main::imscpConfig{'CONF_DIR'} doesn't exist or is not a directory");
		}

		require "$keyFile";
	}

	$main::imscpDBKey = $db_pass_key;
	$main::imscpDBiv = $db_pass_iv;

	iMSCP::Crypt->getInstance()->set('key', $main::imscpDBKey);
	iMSCP::Crypt->getInstance()->set('iv', $main::imscpDBiv);

	undef;
}

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
