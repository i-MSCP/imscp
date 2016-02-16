# i-MSCP Listener::Apache2::Tools::Proxy listener file
# Copyright (C) 2016-2016 Rene Schuster <mail@reneschuster.de>
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
## Allows to redirect/proxy i-MSCP tools (pma,webmail...) in customers Apache2 vhost files.
#

package Listener::Apache2::Tools::Proxy;

use strict;
use warnings;
use iMSCP::EventManager;

iMSCP::EventManager->getInstance()->register('beforeHttpdBuildConf', sub {
	my ($cfgTpl, $tplName, $data) = @_;

	return 0 unless =~ /^domain(?:_ssl)?\.tpl$/;

	if($tplName eq 'domain.tpl') {
		my $redirect = "    RedirectMatch permanent ^(/(?:ftp|pma|webmail)[\/]?)\$ ";

		if($data->{'SSL_SUPPORT'}) {
			$redirect .= "https://$data->{'DOMAIN_NAME'}\$1";
		} else {
			$redirect .= "https://$main::imscpConfig{'BASE_SERVER_VHOST'}:$main::imscpConfig{'BASE_SERVER_VHOST_HTTPS_PORT'}\$1";
		}

		$$cfgTpl =~ s/(^\s+Include.*<\/VirtualHost>)/\n    # BEGIN Listener::Apache2::Tools::Proxy\n$redirect\n    # END Listener::Apache2::Tools::Proxy\n$1/sm;
		return 0;
	}

	my $cfgProxy = <<EOF;

    # BEGIN Listener::Apache2::Tools::Proxy
    SSLProxyEngine On
    ProxyPass /ftp/ {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}:{BASE_SERVER_VHOST_HTTPS_PORT}/ftp/ retry=0 timeout=30
    ProxyPassReverse /ftp/ {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}:{BASE_SERVER_VHOST_HTTPS_PORT}/ftp/
    ProxyPass /pma/ {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}:{BASE_SERVER_VHOST_HTTPS_PORT}/pma/ retry=0 timeout=30
    ProxyPassReverse /pma/ {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}:{BASE_SERVER_VHOST_HTTPS_PORT}/pma/
    ProxyPass /webmail/ {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}:{BASE_SERVER_VHOST_HTTPS_PORT}/webmail/ retry=0 timeout=30
    ProxyPassReverse /webmail/ {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}:{BASE_SERVER_VHOST_HTTPS_PORT}/webmail/
    # END Listener::Apache2::Tools::Proxy
EOF

	$cfgProxy = iMSCP::TemplateParser::process(
		{ 
			BASE_SERVER_VHOST_HTTPS_PORT => $main::imscpConfig{'BASE_SERVER_VHOST_HTTPS_PORT'},
		},
		$cfgProxy
	);

	$$cfgTpl =~ s/(^\s+Include.*<\/VirtualHost>)/$cfgProxy$1/sm;
	0;
});

1;
__END__
