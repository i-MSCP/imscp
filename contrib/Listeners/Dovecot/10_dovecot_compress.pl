# i-MSCP Listener::Dovecot::Compress listener file
# Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
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

our $VERSION = '1.0.1';

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::File;
use Servers::po;
use version;

#
## Configuration parameters
#

# Compression level
my $compressionLevel = 6;

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->registerOne(
    'afterPoBuildConf',
    sub {
        version->parse( "$main::imscpConfig{'PluginApi'}" ) >= version->parse( '1.5.1' ) or die(
            sprintf( "The 10_dovecot_compress.pl listener file version %s requires i-MSCP >= 1.6.0", $VERSION )
        );

        my $dovecotConfdir = Servers::po->factory()->{'config'}->{'DOVECOT_CONF_DIR'};
        my $file = iMSCP::File->new( filename => "$dovecotConfdir/imscp.d/10_dovecot_compress_listener.conf" );
        $file->set( <<"EOT" );
mail_plugins = \$mail_plugins zlib

plugin {
    zlib_save = gz
    zlib_save_level = $compressionLevel
}

protocol imap {
    mail_plugins = \$mail_plugins imap_zlib
}
EOT
        $file->save();
    }
);

1;
__END__
