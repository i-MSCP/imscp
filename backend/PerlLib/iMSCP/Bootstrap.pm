# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 by internet Multi Server Control Panel
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
# @copyright	2010 - 2011 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license      http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Bootstrap;

use strict;
use warnings;
use iMSCP::Config;
use iMSCP::Debug;
use iMSCP::Requirements;
use iMSCP::Exception;
use iMSCP::Database::Database;

use vars qw/@ISA/;

@ISA = ("Common::SingletonClass");
use Common::SingletonClass;

sub _init{
	my $self = shift;

	debug((caller(0))[3].': Starting...');

	if (!$self->{'loaded'}) {
		debug((caller(0))[3].': Booting...');

		$self->{args}->{mode} =~ /install|update/ ?
		iMSCP::Requirements->new()->test('_all') :
		iMSCP::Requirements->new()->test('_user');
		tie %main::configs, 'iMSCP::Config';
		$self->decodeDBData();
		$self->lock($main::configs{'imscp::paths::lock_file'});

		my %setting = (AutoCommit => 0, PrintError => 0);
		my $db = iMSCP::Database::Database->new(
			DATABASE_TYPE		=> $main::configs{'frontend::resources::db::params::type'},
			DATABASE_HOST		=> $main::configs{'frontend::resources::db::params::host'},
			DATABASE_NAME		=> $main::configs{'frontend::resources::db::params::dbname'},
			DATABASE_PASSWORD	=> $main::dbpass,
			DATABASE_USER		=> $main::configs{'frontend::resources::db::params::username'},
			DATABASE_SETTINGS	=> \%setting
		);
		$self->{'loaded'} = 1;
	}

	debug((caller(0))[3].': Ending...');

	0;
}

sub lock{
	my $self	= shift;
	my $lock	= shift;

	debug((caller(0))[3].': Starting...');

	iMSCP::Exception->new()->exception('Unable to open lock file!') if(!open(LOCK, '>', $lock));
	use Fcntl ":flock";
	iMSCP::Exception->new()->exception('Unable to acquire global lock!') if(!flock(LOCK, LOCK_EX));

	debug((caller(0))[3].': Ending...');

	0;
}

sub decodeDBData{
	debug((caller(0))[3].': Starting...');

	my $pass = $main::configs{'frontend::resources::db::params::password'};

	if(!defined($pass) || $pass eq ''){
		iMSCP::Exception->new()->exception('Undefined mysql password');
	}
	my $keyFile = $main::configs{'imscp::paths::config_dir'}.'/common/imscp-keys';
	iMSCP::Exception->new()->exception("File $keyFile do not exists") if( ! -f $keyFile);

	our ($key, $iv);
	do $keyFile;

	if (length($key) != 32 || length($iv) != 8) {
		iMSCP::Exception->new()->exception('KEY or IV has invalid length');
	}
	use Crypt::CBC;
	my $cipher = Crypt::CBC -> new(
		{
			'key'				=> $key,
			'keysize'			=> 32,
			'cipher'			=> 'Blowfish',
			'iv'				=> $iv,
			'regenerate_key'	=> 0,
			'padding'			=> 'space',
			'prepend_iv'		=> 0
		}
	);

	use MIME::Base64;
	my $decoded = decode_base64($pass."\n");
	$main::dbpass = $cipher -> decrypt($decoded);

	debug((caller(0))[3].': Ending...');
}

1;

__END__
