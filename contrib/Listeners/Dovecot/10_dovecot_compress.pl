# i-MSCP Listener::Dovecot::Compress listener file
# Copyright (C) 2015-2017 Rene Schuster <mail@reneschuster.de>
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
## Activates the Dovecot compress plugin to reduce the bandwidth usage of IMAP, and also compresses the stored mails.
##
## For more information please consult:
##   http://wiki2.dovecot.org/Plugins/Compress
##   http://wiki2.dovecot.org/Plugins/Zlib
#

package Listener::Dovecot::Compress;

use strict;
use warnings;
use iMSCP::EventManager;

#
## Configuration parameters
#

# Compression level
my $compressionLevel = 6;

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'beforePoBuildConf',
    sub {
        my ($cfgTpl, $tplName) = @_;

        return 0 unless $tplName eq 'dovecot.conf';

        my $cfgSnippet = <<EOF;

	# BEGIN Listener::Dovecot::Compress
	zlib_save = gz
	zlib_save_level = $compressionLevel
	# END Listener::Dovecot::Compress
EOF

        # Enable zlib plugin globally for reading/writing
        $$cfgTpl =~ s/^(mail_plugins\s+=.*)/$1 zlib/m;
        $$cfgTpl =~ s/^(protocol\simap\s+\{.*?mail_plugins.*?$)/$1 imap_zlib/sm;

        # Enable these only if you want compression while saving
        $$cfgTpl =~ s/^(plugin\s+\{.*?)(\})/$1$cfgSnippet$2/sm;
        0;
    }
);

1;
__END__
