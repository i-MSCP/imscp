# i-MSCP Listener::Dovecot::Prefix
# Copyright (C) 2015 Christoph Keßler <info@it-kessler.de>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301 USA

#
## i-MSCP listener file to edit the PREFIX in dovecot.conf
#

package Listener::Dovecot::Prefix;

use iMSCP::File;
use iMSCP::EventManager;

sub removePrefix
{
	my $dovecotConfig = '/etc/dovecot/dovecot.conf';

	my $file = iMSCP::File->new( filename => $dovecotConfig );

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $dovecotConfig");
		return 1;
	}

	$fileContent =~ s/prefix = INBOX\./prefix =/;
	
	$rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();

}

iMSCP::EventManager->getInstance()->register('beforePoRestart', \&removePrefix);

1;
__END__