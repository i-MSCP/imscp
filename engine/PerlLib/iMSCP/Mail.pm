# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by internet Multi Server Control Panel
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

package iMSCP::Mail;

use strict;
use warnings;
use iMSCP::Debug;
use POSIX;
use Net::LibIDN qw/idn_to_ascii/;
use MIME::Entity;
use Text::Wrap;
use parent 'Exporter';

$Text::Wrap::columns = 75;
$Text::Wrap::break = qr/[\s\n\|]/;

use parent 'Common::Object';

sub errmsg
{
    my ($self, $message) = @_;

    my @parts = split '@', $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'} || '';
    return 0 if @parts < 2;

    my $domain = pop( @parts );
    $domain = idn_to_ascii( $domain, 'utf-8' );
    push @parts, $domain;

    my $adminEmail = join '@', @parts;
    my $date = strftime "%d.%m.%Y %H:%M:%S", localtime;
    my $serverName = $main::imscpConfig{'SERVER_HOSTNAME'};
    my $serverIP = $main::imscpConfig{'BASE_SERVER_IP'};
    my $functionName = (caller( 1 ))[3];
    $functionName = 'main' unless $functionName;

    my $body = <<EOF;
Dear admin,

This is an automatic email sent by your $serverName ($serverIP) server.

A critical error has been encountered while executing function $functionName in $0.

Error was:

==========================================================================
$message
==========================================================================
EOF

    my $out = new MIME::Entity;

    $out->build(
        From       => "$serverName ($serverIP) <$adminEmail>",
        To         => $adminEmail,
        Subject    => "[$date] i-MSCP Error Report",
        Data       => wrap( '', '', $body ),
        'X-Mailer' => "i-MSCP $main::imscpConfig{'Version'} Automatic Messenger"
    );

    unless (open MAIL, '| /usr/sbin/sendmail -t -oi') {
        error( "Unable to send mail: $!" );
        return 1;
    }

    $out->print( \*MAIL );
    close MAIL;

    0;
}

sub warnMsg
{
    my ($self, $message) = @_;

    my @parts = split '@', $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'} || '';
    return 0 if @parts < 2;

    my $dmn = pop( @parts );
    $dmn = idn_to_ascii( $dmn, 'utf-8' );
    push( @parts, $dmn );

    my $adminEmail = join '@', @parts;
    my $date = strftime "%d.%m.%Y %H:%M:%S", localtime;
    my $serverName = $main::imscpConfig{'SERVER_HOSTNAME'};
    my $serverIP = $main::imscpConfig{'BASE_SERVER_IP'};
    my $functionName = (caller( 1 ))[3];
    my $body = <<EOF;
Dear admin,

This is an automatic email sent by your $serverName ($serverIP) server.

The following warning has been raised while executing function $functionName in $0.

Warning was:

==========================================================================
$message
==========================================================================
EOF

    my $out = new MIME::Entity;

    $out->build(
        From       => "$serverName ($serverIP) <$adminEmail>",
        To         => $adminEmail,
        Subject    => "[$date] i-MSCP Warning Report",
        Data       => wrap( '', '', $body ),
        'X-Mailer' => "i-MSCP $main::imscpConfig{'Version'} Automatic Messenger"
    );

    unless (open MAIL, '| /usr/sbin/sendmail -t -oi') {
        error( "Unable to send mail: $!" );
        return 1;
    }

    $out->print( \*MAIL );
    close MAIL;
    0;
}

1;
__END__
