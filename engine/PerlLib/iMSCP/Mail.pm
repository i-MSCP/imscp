=head1 NAME

 iMSCP::Mail - Send warning or error message to system administrator

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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
use iMSCP::Debug 'error';
use iMSCP::ProgramFinder;
use MIME::Entity;
use Text::Wrap;
use parent 'Exporter';

our @EXPORT_OK = qw/ sendInfoMessage sendErrorMessage sendWarningMessage /;

$Text::Wrap::huge = 'wrap';
$Text::Wrap::columns = 75;
$Text::Wrap::break = qr/[\s\n\|]/;

=head1 DESCRIPTION

 Send mail to system administrator

=head1 PUBLIC FUNCTIONS

=over 4

=item sendInfoMessage( $subject, $message )

 Send an informational message to system administrator

 Param string $subject Message subject
 Param string $body $message Body
 Return int 0 on success, other on failure
 
=cut

sub sendInfoMessage
{
    my ( $subject, $message ) = @_;

    _sendmail( $subject, $message, 'info' );
}

=item sendWarningMessage( $message )

 Send a warning message to system administrator

 Param string $body Message body
 Return int 0 on success, other on failure
 
=cut

sub sendWarningMessage
{
    my ( $body ) = @_;
    
    _sendmail( 'Warning raised', <<"EOF", 'warning' );
An unexpected warning has been raised in $0:

$body
EOF
}

=item sendErrorMessage( $body )

 Send an error message to system administrator

 Param string $body Message body
 Return int 0 on success, other on failure
 
=cut

sub sendErrorMessage
{
    my ( $body ) = @_;

    _sendmail( 'Error raised', <<"EOF", 'error' );
An unexpected error has been raised in $0:

$body
EOF
}

=back

=head1 PRIVATE FUNCTIONS

=over 4

=item _sendmail( $subject, $body, $severity )

 Send the given mail to system administrator

 Param string $subject Message subject
 Param string $message Message body to be sent
 Param string $severity Message severity
 Return int 0 on success, other on failure
 
=cut

sub _sendmail
{
    my ( $subject, $message, $severity ) = @_;

    length $subject or die( '$subject parameter is not defined or invalid' );
    length $message or die( '$message parameter is not defined or invalid' );
    length $severity or die( '$severity parameter is not defined or invalid' );
    
    return 0 unless my $bin = iMSCP::ProgramFinder::find( 'sendmail' );

    my $host = $::imscpConfig{'BASE_SERVER_VHOST'};
    my $out = MIME::Entity->new()->build(
        From       => "i-MSCP ($host) <noreply\@$host>",
        To         => $::imscpConfig{'DEFAULT_ADMIN_ADDRESS'},
        Subject    => "i-MSCP (backend) - $subject",
        Type       => 'text/plain; charset=utf-8',
        Encoding   => '8bit',
        Data       => encode( 'UTF-8', wrap( '', '', <<"EOF" )),
Dear administrator,

This is an automatic email sent by i-MSCP backend:
 
Server name: $::imscpConfig{'SERVER_HOSTNAME'}
Server IP: $::imscpConfig{'BASE_SERVER_PUBLIC_IP'}
Version: $::imscpConfig{'Version'}
Build: @{ [ $::imscpConfig{'Build'} || 'Unavailable' ] }
Message severity: $severity

==========================================================================

$message
==========================================================================

Please do not reply to this email.

___________________________
i-MSCP Backend Mailer
EOF
        'X-Mailer' => 'i-MSCP Backend Mailer'
    );

    my $fh;
    unless ( open $fh, '|-', $bin, '-t', '-oi', '-f', "noreply\@$host" ) {
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
