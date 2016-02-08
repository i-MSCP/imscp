# i-MSCP Listener::Apache2::HSTS listener file
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
## Listener file for HTTP Strict Transport Security (HSTS) with Apache2
#

package Listener::Apache2::HSTS;

use strict;
use warnings;
use iMSCP::EventManager;

iMSCP::EventManager->getInstance()->register('beforeHttpdBuildConf', sub {
	my ($cfgTpl, $tplName, $data) = @_;

	my $cfgSnippet = <<EOF;
    # BEGIN Listener::Apache2::HSTS
    Header always set Strict-Transport-Security "max-age=31536000"
    # END Listener::Apache2::HSTS
EOF

	if($tplName =~ /^domain_ssl\.tpl$/) {
		$$cfgTpl =~ s/(^\s+Include.*<\/VirtualHost>)/\n$cfgSnippet$1/sm;
	}

	0;
});

1;
__END__
