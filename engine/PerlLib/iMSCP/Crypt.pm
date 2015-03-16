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

package iMSCP::Crypt;

use strict;
use warnings;

use iMSCP::Debug;
use Crypt::CBC;
use MIME::Base64;
use Crypt::PasswdMD5;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = $_[0];

	$self->{'cipher'}->{'key'} = '';
	$self->{'cipher'}->{'keysize'} = 32;
	$self->{'cipher'}->{'cipher'} = 'Blowfish';
	$self->{'cipher'}->{'iv'} = '';
	$self->{'cipher'}->{'regenerate_key'} = 0;
	$self->{'cipher'}->{'padding'} = 'space';
	$self->{'cipher'}->{'prepend_iv'} = 0;
}

sub set
{
	my ($self, $prop, $value) = @_;

	if(exists $self->{'cipher'}->{$prop}) {
		$self->{'cipher'}->{$prop} = $value;
	} else {
		fatal("Unknown property: $prop");
	}
}

sub randomString
{
	my $self = shift || iMSCP::Crypt->getInstance();
	my $length = shift;

	if(!ref $self || !$self->isa('iMSCP::Crypt')) {
		$length = $self;
		$self = iMSCP::Crypt->getInstance();
	}

	my $string = '';

	while(length $string < $length) {
		my $pool = Crypt::CBC->random_bytes(100);

		for(unpack 'C*', $pool) {
			next if $_ < 32 || $_ > 126;
			length $string < $length ? $string .= chr $_ : last;
		}
	}

	$string;
}

sub encrypt_db_password
{
	my ($self, $pass) = @_;

	error('Undefined input data...') if ! defined $pass || $pass eq '';
	error('KEY or IV has invalid length')
		if (length($self->{'cipher'}->{'key'}) != $self->{'cipher'}->{'keysize'} || length($self->{'cipher'}->{'iv'}) != 8);

	my $cipher = Crypt::CBC->new($self->{'cipher'});
	my $encoded	= encode_base64($cipher->encrypt($pass));
	chop($encoded);

	$encoded;
}

sub decrypt_db_password
{
	my ($self, $pass) = @_;

	if (! defined $pass || $pass eq ''){
		error('Undefined input data...') ;
		return undef;
	}

	if (length($self->{'cipher'}->{'key'}) != $self->{'cipher'}->{'keysize'} || length($self->{'cipher'}->{'iv'}) != 8) {
		error('KEY or IV has invalid length');
		return undef;
	}

	my $cipher = Crypt::CBC->new($self->{'cipher'});
	my $plaintext = $cipher->decrypt(decode_base64("$pass\n"));

	$plaintext;
}

sub crypt_md5_data
{
	my $self = shift || iMSCP::Crypt->getInstance();
	my $data = shift;

	if(! ref $self || !$self->isa('iMSCP::Crypt')) {
		$data = $self;
		$self = iMSCP::Crypt->getInstance();
	}

	unless ($data) {
		debug('Undefined input data');
		return undef;
	}

	debug("Crypting |$data|!");

	$data = unix_md5_crypt($data, $self->randomString(8));

	$data;
}

1;
__END__
