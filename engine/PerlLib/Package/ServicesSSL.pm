=head1 NAME

 Package::ServicesSSL - SSL for various services (FTP, SMTP, IMAP/POP)

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

package Package::ServicesSSL;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Debug 'getMessageByType';
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::OpenSSL;
use Net::LibIDN 'idn_to_unicode';
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Provides SSL for various services (FTP, SMTP, IMAP/POP).

=head1 PUBLIC METHODS

=over 4

=item getPriority( )

 Get package priority

 Return int package priority

=cut

sub getPriority
{
    -10;
}

=item registerSetupListeners( \%events )

 Register setup event listeners

 Param iMSCP::EventManager \%events
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( $self, $events ) = @_;

    $events->registerOne( 'beforeSetupDialog', sub {
        push @{ $_[0] },
            sub { $self->_dialogForServicesSSL( @_ ); };
        0;
    } );
}

=item preinstall( )

 Process pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my $sslEnabled = ::setupGetQuestion( 'SERVICE_SSL_ENABLED' );

    # SSL is disabled. We need remove the SSL certificate if any and
    # return early
    if ( $sslEnabled eq 'no' ) {
        if ( -f "$::imscpConfig{'CONF_DIR'}/imscp_services.pem" ) {
            return iMSCP::File->new(
                filename => "$::imscpConfig{'CONF_DIR'}/imscp_services.pem"
            )->delFile();
        }

        return 0;
    }

    # If the current SSL certificate chain is valid, we return early
    if ( ::setupGetQuestion( 'SERVICES_SSL_HAS_VALID_CHAIN', 'no' ) eq 'yes' ) {
        return 0;
    }

    # If no SSL certificate has been provided, we need generate a self-signed
    # SSL certificate
    if ( ::setupGetQuestion( 'SERVICES_SSL_SELFSIGNED_CERTIFICATE' ) eq 'yes' ) {
        return iMSCP::OpenSSL->new(
            certificate_chains_storage_dir => $::imscpConfig{'CONF_DIR'},
            certificate_chain_name         => 'imscp_services'
        )->createSelfSignedCertificate( {
            common_name => ::setupGetQuestion( 'SERVER_HOSTNAME' ),
            email       => ::setupGetQuestion( 'DEFAULT_ADMIN_ADDRESS' )
        } ) ? 0 : 1;
    }

    iMSCP::OpenSSL->new(
        certificate_chains_storage_dir => $::imscpConfig{'CONF_DIR'},
        certificate_chain_name         => 'imscp_services',
        private_key_container_path     =>
            ::setupGetQuestion( 'SERVICES_SSL_PRIVATE_KEY_PATH' ),
        private_key_passphrase         =>
            ::setupGetQuestion( 'SERVICES_SSL_PRIVATE_KEY_PASSPHRASE' ),
        certificate_container_path     =>
            ::setupGetQuestion( 'SERVICES_SSL_CERTIFICATE_PATH' ),
        ca_bundle_container_path       =>
            ::setupGetQuestion( 'SERVICES_SSL_CA_BUNDLE_PATH' )
    )->createCertificateChain() ? 0 : 1;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _dialogForServicesSSL( \%dialog )

 Dialog for services (FTP, IMAP/POP, SMTP) SSL

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (back)

=cut

sub _dialogForServicesSSL
{
    my ( undef, $dialog ) = @_;

    my $idn = idn_to_unicode(
        ::setupGetQuestion( 'SERVER_HOSTNAME' ), 'utf-8'
    );
    my $ssl = ::setupGetQuestion( 'SERVICES_SSL_ENABLED' );
    my $selfSignedCrt = ::setupGetQuestion(
        'SERVICES_SSL_SELFSIGNED_CERTIFICATE'
    );
    my $pkPath = ::setupGetQuestion(
        'SERVICES_SSL_PRIVATE_KEY_PATH',
        "$::imscpConfig{'CONF_DIR'}/imscp_services.pem"
    );
    my $passphrase = ::setupGetQuestion(
        'SERVICES_SSL_PRIVATE_KEY_PASSPHRASE'
    );
    my $crtPath = ::setupGetQuestion(
        'SERVICES_SSL_CERTIFICATE_PATH',
        "$::imscpConfig{'CONF_DIR'}/imscp_services.pem"
    );
    my $caPath = ::setupGetQuestion(
        'SERVICES_SSL_CA_BUNDLE_PATH',
        "$::imscpConfig{'CONF_DIR'}/imscp_services.pem"
    );
    my $openSSL = iMSCP::OpenSSL->new();

    if ( !grep ( $::reconfigure eq $_, qw/ services_ssl ssl all / ) ) {
        goto CHECK_SSL_CHAIN if $ssl eq 'yes';
        return 20 if $ssl eq 'no';
    }

    SSL_DIALOG:
    my $ret = $dialog->boolean( <<'EOF', $ssl eq 'no' );
Do you want to enable secure connections (SSL) for the FTP, IMAP/POP, and SMTP services?
EOF
    return 30 if $ret == 30;

    if ( $ret == 1 ) {
        ::setupSetQuestion( 'SERVICES_SSL_ENABLED', 'no' );
        return 0;
    }

    ::setupSetQuestion( 'SERVICES_SSL_ENABLED', 'yes' );

    SSL_SELF_SIGNED_DIALOG:
    $ret = $dialog->boolean( <<"EOF", $selfSignedCrt eq 'no' );
Do you have an SSL certificate for the $idn domain?
    
If you say 'no', a self-signed SSL certificate will be generated.
EOF
    goto SSL_DIALOG if $ret == 30;

    if ( $ret == 1 ) {
        ::setupSetQuestion( 'SERVICES_SSL_SELFSIGNED_CERTIFICATE', 'yes' );
        ::setupSetQuestion( 'SERVICES_SSL_HAS_VALID_CHAIN', 'no' );
        return 0;
    }

    ::setupSetQuestion( 'SERVICES_SSL_SELFSIGNED_CERTIFICATE', 'no' );

    my $msg = '';
    SSL_PK_DIALOG:
    do {
        ( $ret, $pkPath ) = $dialog->string( <<"EOF", $pkPath );
${msg}Please enter a path for the SSL certificate private key. Leave this field blank if you don't have one:
EOF
        if ( $ret != 30 ) {
            $pkPath =~ s/^\s+|\s+$//g;
            $msg = !length $pkPath || !-f $pkPath
                ? "\\Z1Invalid SSL certificate private key path.\\Zn\n\n" : '';
        }
    } while $ret != 30 && length $msg;
    goto SSL_SELF_SIGNED_DIALOG if $ret == 30;

    ::setupSetQuestion( 'SERVICES_SSL_PRIVATE_KEY_PATH', $pkPath );

    SSL_PK_PASSPHRASE_DIALOG:
    do {
        ( $ret, $passphrase ) = $dialog->password( <<"EOF", $passphrase );
${msg}Please enter the passphrase for the SSL certificate private key. Leave this field blank if you don't have one:
EOF
        if ( $ret != 30 ) {
            $passphrase =~ s/^\s+|\s+$//g;
            @{ $openSSL }{qw/
                private_key_container_path private_key_passphrase
            /} = (
                $pkPath, $passphrase
            );

            unless ( $openSSL->validatePrivateKey() ) {
                $msg = "\\Z1" . getMessageByType( 'error', {
                    amount => 1,
                    remove => TRUE
                } ) . "\\Zn\n\n";
            } else {
                $msg = '';
            }
        }
    } while $ret != 30 && length $msg;
    goto SSL_PK_DIALOG if $ret == 30;

    ::setupSetQuestion( 'SERVICES_SSL_PRIVATE_KEY_PASSPHRASE', $passphrase );

    SSL_CA_BUNDLE_DIALOG:
    do {
        ( $ret, $caPath ) = $dialog->string( <<"EOF", $caPath );
${msg}Please enter a path for the SSL certificate CA bundle. Leave this field blank if you don't have one:
EOF
        if ( $ret != 30 ) {
            $caPath =~ s/^\s+|\s+$//g;
            $msg = length $caPath && !-f $caPath
                ? "\\Z1Invalid SSL certificate CA bundle path.\\Zn\n\n" : '';
        }
    } while $ret != 30 && length $msg;
    goto SSL_PK_PASSPHRASE_DIALOG if $ret == 30;

    ::setupSetQuestion( 'SERVICES_SSL_CA_BUNDLE_PATH', $caPath );

    do {
        ( $ret, $crtPath ) = $dialog->string( <<"EOF", $crtPath );
${msg}Please enter a path for the SSL certificate:
EOF
        if ( $ret != 30 ) {
            $crtPath =~ s/^\s+|\s+$//g;
            @{ $openSSL }{qw/
                ca_bundle_container_path certificate_container_path
            /} = (
                $caPath, $crtPath
            );

            unless ( $openSSL->validateCertificate()
                && $openSSL->validateCertKeyMatching()
            ) {
                $msg = "\\Z1" . getMessageByType( 'error', {
                    amount => 1,
                    remove => TRUE
                } ) . "\\Zn\n\n";
            } else {
                $msg = '';
            }
        }
    } while $ret != 30 && length $msg;
    goto SSL_CA_BUNDLE_DIALOG if $ret == 30;

    ::setupSetQuestion( 'SERVICES_SSL_CERTIFICATE_PATH', $crtPath );
    ::setupSetQuestion( 'PANEL_SSL_HAS_VALID_CHAIN', 'no' );
    goto END_SSL_DIALOG;

    CHECK_SSL_CHAIN:

    @{ $openSSL }{qw/
        private_key_container_path ca_bundle_container_path
        certificate_container_path
    /} = (
        $pkPath, $caPath, $crtPath
    );

    unless ( $openSSL->validateCertificateChain() ) {
        if ( iMSCP::Getopt->preseed && $selfSignedCrt ) {
            getMessageByType( 'error', {
                amount => 1,
                remove => TRUE
            } );
            return 20;
        }

        local $dialog->{'_opts'}->{
            $dialog->{'program'} eq 'dialog' ? 'ok-label' : 'ok-button'
        } = 'Reconfigure';
        $msg = getMessageByType( 'error', {
            amount => 1,
            remove => TRUE
        } );
        $dialog->error( <<"EOF" );
Your SSL certificate chain for the FTP, IMAP/POP, and SMTP services is missing or invalid.

Error was: \\Z1$msg\\Zn
EOF
        ::setupSetQuestion( 'SERVICES_SSL_ENABLED', '' );
        goto &{_dialogForCpSSL};
    }

    ::setupSetQuestion( 'SERVICES_SSL_HAS_VALID_CHAIN', 'yes' );

    END_SSL_DIALOG:
    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
