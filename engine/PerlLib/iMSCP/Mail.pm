=head1 NAME

 iMSCP::Mail - Send warning or error message to system administrator

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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
use Encode;
use iMSCP::Debug;
use iMSCP::ProgramFinder;
use MIME::Entity;
use Text::Wrap;
use parent 'Common::Object';

$Text::Wrap::huge = 'wrap';
$Text::Wrap::columns = 75;
$Text::Wrap::break = qr/[\s\n\|]/;

=head1 DESCRIPTION

 Send warning or error message to system administrator

=head1 PUBLIC METHODS

=over 4

=item errmsg( $message )

 Send an error message to system administrator

 Param string Error message to be sent
 Return int 0 on success, other on failure
 
=cut

sub errmsg
{
    my ($self, $message) = @_;

    defined $message or die( "$message parameter is not defined" );

    my $functionName = ( caller( 1 ) )[3] || 'main';
    $self->_sendMail( 'i-MSCP - An error has been raised', <<"EOF", 'error' );
An error has been raised while executing function $functionName in $0:

$message
EOF
    0;
}

=item warnMsg( $message )

 Send a warning message to system administrator

 Param string $message Warning message to be sent
 Return int 0 on success, other on failure
 
=cut

sub warnMsg
{
    my ($self, $message) = @_;

    defined $message or die( "$message parameter is not defined" );

    my $functionName = ( caller( 1 ) )[3] || 'main';
    $self->_sendMail( 'i-MSCP - A warning has been raised', <<"EOF", 'warning' );
A warning has been raised while executing function $functionName in $0:

$message
EOF
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _sendMail($subject, $message, $severity)

 Send a message to system administrator

 Param string $subject Message subject
 Param string $message Message to be sent
 Param string $severity Message severity
 Return int 0 on success, other on failure
 
=cut

sub _sendMail
{
    my (undef, $subject, $message, $severity) = @_;

    my $sendmail = iMSCP::ProgramFinder::find( 'sendmail' ) or die( "Couldn't find sendmail executable" );
    my $host = $main::imscpConfig{'BASE_SERVER_VHOST'};
    my $out = MIME::Entity->new()->build(
        From       => "i-MSCP ($host) <noreply\@$host>",
        To         => $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'},
        Subject    => $subject,
        Type       => 'text/plain; charset=utf-8',
        Encoding   => '8bit',
        Data       => encode( 'UTF-8', wrap( '', '', <<"EOF" )),
Dear administrator,

This is an automatic email sent by i-MSCP:
 
Server name: $main::imscpConfig{'SERVER_HOSTNAME'}
Server IP: $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'}
Version: $main::imscpConfig{'Version'}
Build: $main::imscpConfig{'BuildDate'}
Message severity: $severity

==========================================================================
$message
==========================================================================

Please do not reply to this email.

___________________________
i-MSCP Mailer
EOF
        'X-Mailer' => 'i-MSCP Mailer (backend)'
    );

    my $fh;
    unless ( open $fh, '|-', $sendmail, '-t', '-oi', '-f', "noreply\@$host" ) {
        error( sprintf( "Couldn't send mail: %s", $! ));
        return 1;
    }
    $out->print( $fh );
    close $fh;
    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
