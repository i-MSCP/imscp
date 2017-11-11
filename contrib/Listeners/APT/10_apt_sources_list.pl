# i-MSCP Listener::APT::Source::List listener file
# Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
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
## Replaces i-MSCP provided SOURCES.LIST(5) file.
#
# Warning: Bear in mind that if you replace the i-MSCP provided sources.list,
# you must make sure that all required repositories for i-MSCP are available.

package Listener::APT::Source::List;

our $VERSION = '1.0.0';

use strict;
use warnings;
use iMSCP::Debug qw/ getMessageByType /;
use iMSCP::File;
use iMSCP::EventManager;

# Path to your own SOURCES.LIST(5) file
my $APT_SOURCES_LIST_FILE_PATH = '/usr/local/src/sources.list';

# Please don't edit anything below this line

iMSCP::EventManager->getInstance()->register(
    'onLoadTemplate',
    sub {
        my ($target, $tplFilename, $tplFileContent) = @_;

        return 0 unless $target eq 'apt' && $tplFilename eq 'sources.list';

        ${$tplFileContent} = iMSCP::File->new( filename => $APT_SOURCES_LIST_FILE_PATH )->get() or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } )
        );

        0;
    }
);

1;
__END__
