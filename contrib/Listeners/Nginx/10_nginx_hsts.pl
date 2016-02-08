# i-MSCP Listener::Nginx::HSTS listener file
# Copyright (C) 2015-2016 Rene Schuster <mail@reneschuster.de>
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
## Listener file for HTTP Strict Transport Security (HSTS) with Nginx
#

package Listener::Nginx::HSTS;

use strict;
use warnings;
use iMSCP::EventManager;

iMSCP::EventManager->getInstance()->register('afterFrontEndBuildHttpdVhosts', sub {
	my $cfgSnippet = <<EOF;
    # BEGIN Listener::Nginx::HSTS
    add_header Strict-Transport-Security max-age=31536000;
    # END Listener::Nginx::HSTS
EOF

	my $file = iMSCP::File->new('filename' => "/etc/nginx/sites-available/00_master_ssl.conf");
	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $file");
	return 1;
	}

	$fileContent =~ s/(ssl_prefer_server_ciphers.*\n)/$1\n$cfgSnippet/g;

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	0;
});

1;
__END__