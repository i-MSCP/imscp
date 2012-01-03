#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Boot;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Crypt;
use iMSCP::Config;
use iMSCP::Requirements;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub init{
	my $self = shift;
	my $option = shift;

	$option = {} if ref $option ne 'HASH';

	unless($self->{'loaded'}) {
		debug('Booting...');

		tie %main::imscpConfig, 'iMSCP::Config','fileName' => (($^O =~ /bsd$/ ? '/usr/local/etc/' : '/etc/').'imscp/imscp.conf'), noerrors => 1;

		verbose($main::imscpConfig{'DEBUG'}) unless($self->{args}->{mode} && $self->{args}->{mode} eq 'setup'); #on setup DEBUG is allways 0.

		iMSCP::Requirements->new()->test($self->{args}->{mode} && $self->{args}->{mode} eq 'setup' ? 'all' : 'user') unless($option->{norequirements} && $option->{norequirements} eq 'yes');

		$self->lock($main::imscpConfig{MR_LOCK_FILE}) unless($option->{nolock} && $option->{nolock} eq 'yes');

		$self->genKey();

		unless ($option->{nodatabase} && $option->{nodatabase} eq 'yes'){
				use iMSCP::Database;
				use iMSCP::Crypt;

				my $crypt = iMSCP::Crypt->new();
				my $database = iMSCP::Database->new(db => $main::imscpConfig{'DATABASE_TYPE'})->factory();

				$database->set('DATABASE_HOST', $main::imscpConfig{'DATABASE_HOST'});
				$database->set('DATABASE_PORT', $main::imscpConfig{'DATABASE_PORT'});
				$database->set('DATABASE_NAME', $main::imscpConfig{'DATABASE_NAME'});
				$database->set('DATABASE_USER', $main::imscpConfig{'DATABASE_USER'});
				$database->set('DATABASE_PASSWORD', $crypt->decrypt_db_password($main::imscpConfig{'DATABASE_PASSWORD'}));
				my $rs = $database->connect();
				fatal("$rs") if $rs;
		}

		$self->{'loaded'} = 1;
	}

	0;
}

sub lock{
	my $self	= shift;
	my $lock	= shift || $main::imscpConfig{MR_LOCK_FILE};

	fatal('Unable to open lock file!') if(!open($self->{lock}, '>', $lock));

	use Fcntl ":flock";
	fatal('Unable to acquire global lock!') if(!flock($self->{lock}, LOCK_EX));

	0;
}

sub unlock{
	my $self	= shift;
	my $lock	= shift;

	use Fcntl ":flock";
	fatal('Unable to release global lock!') if(!flock($self->{lock}, LOCK_UN));

	0;
}

sub genKey{

	use iMSCP::File;

	my $key_file		= "$main::imscpConfig{'CONF_DIR'}/imscp-db-keys";
	our $db_pass_key	= '{KEY}';
	our $db_pass_iv		= '{IV}';

	require "$key_file" if( -f $key_file);

	if ($db_pass_key eq '{KEY}' || $db_pass_iv eq '{IV}') {

		print STDOUT "\tGenerating database keys, it may take some time, please  wait...\n";
		print STDOUT "\tIf it takes to long, please check:  http://i-mscp.net/dokuwiki/doku.php?id=keyrpl\n";

		if(-d $main::imscpConfig{'CONF_DIR'}) {
			open(F, '>:utf8', "$main::imscpConfig{'CONF_DIR'}/imscp-db-keys") or fatal("Error: Can't open file '$main::imscpConfig{'CONF_DIR'}/imscp-db-keys' for writing: $!");
			print F Data::Dumper->Dump([iMSCP::Crypt::randomString(32), iMSCP::Crypt::randomString(8)], [qw(db_pass_key db_pass_iv)]);
			close F;
		} else {
			fatal("Error: Destination path $main::imscpConfig{'CONF_DIR'} don't exists or is not a directory!");
		}
		require "$key_file";
	}

	$main::imscpDBKey	= $db_pass_key;
	$main::imscpDBiv	= $db_pass_iv;

	iMSCP::Crypt->new()->set('key', $main::imscpDBKey);
	iMSCP::Crypt->new()->set('iv', $main::imscpDBiv);

	debug("Key: |$main::imscpDBKey|, iv:|$main::imscpDBiv|");

}

1;

__END__
