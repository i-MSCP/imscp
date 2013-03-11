#!/usr/bin/perl

=head1 NAME

 iMSCP::Boot - Boot i-MSCP

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
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Boot;

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

 Boot i-MSCP.

 Return iMSCP::Boot

=cut

sub boot
{
	my $self = shift;
	my $options = shift;

	debug('Booting...');

	tie
		%main::imscpConfig,
		'iMSCP::Config',
		'fileName' => (($^O =~ /bsd$/ ? '/usr/local/etc/' : '/etc/') . 'imscp/imscp.conf'),
		'nocreate' => 1, # Do not create file if it doesn't exists (raise error instead)
		'noerrors' => 1; # Do not raise error when attempting to access to an inexistent configuration parameter

	# Set verbose mode
	verbose(iMSCP::Getopt->debug || $main::imscpConfig{'DEBUG'} || 0);

	iMSCP::Requirements->new()->test(
		$options->{'mode'} && $options->{'mode'} eq 'setup' ? 'all' : 'user'
	) unless($options->{'norequirements'} && $options->{'norequirements'} eq 'yes');

	$self->lock() unless($options->{'nolock'} && $options->{'nolock'} eq 'yes');

	$self->_genKey();

	unless ($options->{'nodatabase'} && $options->{'nodatabase'} eq 'yes') {
		require iMSCP::Crypt;
		my $crypt = iMSCP::Crypt->getInstance();
		my $database = iMSCP::Database->new('db' => $main::imscpConfig{'DATABASE_TYPE'})->factory();

		$database->set('DATABASE_HOST', $main::imscpConfig{'DATABASE_HOST'});
		$database->set('DATABASE_PORT', $main::imscpConfig{'DATABASE_PORT'});
		$database->set('DATABASE_NAME', $main::imscpConfig{'DATABASE_NAME'});
		$database->set('DATABASE_USER', $main::imscpConfig{'DATABASE_USER'});
		$database->set('DATABASE_PASSWORD', $crypt->decrypt_db_password($main::imscpConfig{'DATABASE_PASSWORD'}));
		my $rs = $database->connect();

		fatal("Unable to connect to the SQL server: $rs") if $rs;
	}

	$self;
}

=item lock([$lockFile])

 Lock the given file or the engine lock file.

 Return iMSCP::Boot

=cut

sub lock
{
	my $self = shift;
	my $lockFile = shift || $main::imscpConfig{'MR_LOCK_FILE'};

	unless(defined $self->{'locks'}->{$lockFile}) {
		fatal('Unable to open lock file') if ! open($self->{'locks'}->{$lockFile}, '>', $lockFile);
		fatal('Unable to acquire global lock') if ! flock($self->{'locks'}->{$lockFile}, LOCK_EX);
	}

	$self;
}

=item unlock([$lockFile])

 Unlock the given lock file or the engine lock file.

 Return iMSCP::Boot

=cut

sub unlock
{
	my $self = shift;
	my $lockFile = shift || $main::imscpConfig{'MR_LOCK_FILE'};

	if(defined $self->{'locks'}->{$lockFile}) {
		fatal('Unable to release global lock') if ! flock($self->{'locks'}->{$lockFile}, LOCK_UN);
	}

	$self;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _genKey()

 Generates encryption key and vector.

 Return undef

=cut

sub _genKey
{
	my $self = shift;

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
			fatal("Destination path $main::imscpConfig{'CONF_DIR'} doesn't exists or is not a directory");
		}

		require "$keyFile";
	}

	$main::imscpDBKey = $db_pass_key;
	$main::imscpDBiv = $db_pass_iv;

	iMSCP::Crypt->getInstance()->set('key', $main::imscpDBKey);
	iMSCP::Crypt->getInstance()->set('iv', $main::imscpDBiv);

	debug("Key: |$main::imscpDBKey|, iv:|$main::imscpDBiv|");

	undef;
}

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
