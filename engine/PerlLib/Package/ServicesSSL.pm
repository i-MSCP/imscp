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
        push @{ $_[0] }, sub { $self->servicesSslDialog( @_ ) };
        0;
    } );
}

=item serviceSslDialog( \%dialog )

 Ask for services SSL

 Param iMSCP::Dialog \%dialog
 Return int 0 NEXT, 30 BACKUP, 50 ESC

=cut

sub servicesSslDialog
{
    my ( undef, $dialog ) = @_;

    my $hostname = ::setupGetQuestion( 'SERVER_HOSTNAME' );
    my $hostnameUnicode = idn_to_unicode( $hostname, 'utf-8' );
    my $sslEnabled = ::setupGetQuestion( 'SERVICES_SSL_ENABLED' );
    my $selfSignedCertificate = ::setupGetQuestion(
        'SERVICES_SSL_SELFSIGNED_CERTIFICATE', 'no'
    );
    my $privateKeyPath = ::setupGetQuestion(
        'SERVICES_SSL_PRIVATE_KEY_PATH', '/root'
    );
    my $passphrase = ::setupGetQuestion(
        'SERVICES_SSL_PRIVATE_KEY_PASSPHRASE'
    );
    my $certificatePath = ::setupGetQuestion(
        'SERVICES_SSL_CERTIFICATE_PATH', '/root'
    );
    my $caBundlePath = ::setupGetQuestion(
        'SERVICES_SSL_CA_BUNDLE_PATH', '/root'
    );
    my $openSSL = iMSCP::OpenSSL->new();

    if ( $::reconfigure =~ /^(?:services_ssl|ssl|all|forced)$/
        || $sslEnabled !~ /^(?:yes|no)$/
        || ( $sslEnabled eq 'yes' && $::reconfigure =~ /^(?:system_hostname|hostnames)$/ )
    ) {
        my $rs = $dialog->yesno(
            <<'EOF', $sslEnabled eq 'no' ? TRUE : FALSE );

Do you want to enable SSL for FTP and MAIL services?
EOF
        if ( $rs == 0 ) {
            $sslEnabled = 'yes';
            $rs = $dialog->yesno(
                <<"EOF", $selfSignedCertificate eq 'no' ? TRUE : FALSE );

Do you have a SSL certificate for the $hostnameUnicode domain?
EOF
            if ( $rs == 0 ) {
                my $msg = '';

                do {
                    $dialog->msgbox( <<'EOF' );
$msg
Please select your private key in next dialog.
EOF
                    do {
                        ( $rs, $privateKeyPath ) = $dialog->fselect(
                            $privateKeyPath
                        );
                    } while $rs < 30 && !(
                        length $privateKeyPath && -f $privateKeyPath
                    );
                    return $rs if $rs >= 30;

                    ( $rs, $passphrase ) = $dialog->passwordbox(
                        <<'EOF', $passphrase );

Please enter the passphrase for your private key if any:
EOF
                    return $rs if $rs >= 30;

                    $openSSL->{'private_key_container_path'} = $privateKeyPath;
                    $openSSL->{'private_key_passphrase'} = $passphrase;

                    $msg = '';
                    if ( $openSSL->validatePrivateKey() ) {
                        getMessageByType( 'error', {
                            amount => 1,
                            remove => TRUE
                        } );
                        $msg = "\n\\Z1Invalid private key or passphrase.\\Zn\n\nPlease try again.";
                    }
                } while $rs < 30 && $msg;
                return $rs if $rs >= 30;

                $rs = $dialog->yesno( <<'EOF' );

Do you have a SSL CA Bundle?
EOF
                if ( $rs == 0 ) {
                    do {
                        ( $rs, $caBundlePath ) = $dialog->fselect(
                            $caBundlePath
                        );
                    } while $rs < 30 && !(
                        length $caBundlePath && -f $caBundlePath
                    );
                    return $rs if $rs >= 30;

                    $openSSL->{'ca_bundle_container_path'} = $caBundlePath;
                } else {
                    $openSSL->{'ca_bundle_container_path'} = '';
                }

                $dialog->msgbox( <<'EOF' );

Please select your SSL certificate in next dialog.
EOF
                $rs = 1;
                do {
                    $dialog->msgbox( <<"EOF" ) unless $rs;

\\Z1Invalid SSL certificate. Please try again.\\Zn
EOF
                    do {
                        ( $rs, $certificatePath ) = $dialog->fselect(
                            $certificatePath
                        );
                    } while $rs < 30 && !(
                        length $certificatePath && -f $certificatePath
                    );
                    return $rs if $rs >= 30;

                    getMessageByType( 'error', {
                        amount => 1,
                        remove => TRUE
                    } );
                    $openSSL->{'certificate_container_path'}
                        = $certificatePath;
                } while $rs < 30 && $openSSL->validateCertificate();
                return $rs if $rs >= 30;
            } else {
                $selfSignedCertificate = 'yes';
            }
        } else {
            $sslEnabled = 'no';
        }
    } elsif ( $sslEnabled eq 'yes' && !iMSCP::Getopt->preseed ) {
        $openSSL->{'private_key_container_path'} =
            "$::imscpConfig{'CONF_DIR'}/imscp_services.pem";
        $openSSL->{'ca_bundle_container_path'} =
            "$::imscpConfig{'CONF_DIR'}/imscp_services.pem";
        $openSSL->{'certificate_container_path'} =
            "$::imscpConfig{'CONF_DIR'}/imscp_services.pem";

        if ( $openSSL->validateCertificateChain() ) {
            getMessageByType( 'error', {
                amount => 1,
                remove => TRUE
            } );
            $dialog->getInstance()->msgbox( <<'EOF' );

Your SSL certificate for the FTP and MAIL services is missing or invalid.
EOF
            ::setupSetQuestion( 'SERVICES_SSL_ENABLED', '' );
            goto &{servicesSslDialog};
        }

        # In case the certificate is valid, we skip SSL setup process
        ::setupSetQuestion( 'SERVICES_SSL_SETUP', 'no' );
    }

    ::setupSetQuestion( 'SERVICES_SSL_ENABLED', $sslEnabled );
    ::setupSetQuestion(
        'SERVICES_SSL_SELFSIGNED_CERTIFICATE', $selfSignedCertificate
    );
    ::setupSetQuestion( 'SERVICES_SSL_PRIVATE_KEY_PATH', $privateKeyPath );
    ::setupSetQuestion( 'SERVICES_SSL_PRIVATE_KEY_PASSPHRASE', $passphrase );
    ::setupSetQuestion( 'SERVICES_SSL_CERTIFICATE_PATH', $certificatePath );
    ::setupSetQuestion( 'SERVICES_SSL_CA_BUNDLE_PATH', $caBundlePath );
    0;
}

=item preinstall( )

 Process pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my $sslEnabled = ::setupGetQuestion( 'SERVICES_SSL_ENABLED' );

    if ( $sslEnabled eq 'no'
        || ::setupGetQuestion( 'SERVICES_SSL_SETUP', 'yes' ) eq 'no'
    ) {
        if ( $sslEnabled eq 'no'
            && -f "$::imscpConfig{'CONF_DIR'}/imscp_services.pem"
        ) {
            my $rs = iMSCP::File->new(
                filename => "$::imscpConfig{'CONF_DIR'}/imscp_services.pem"
            )->delFile();
            return $rs if $rs;
        }

        return 0;
    }

    if ( ::setupGetQuestion( 'SERVICES_SSL_SELFSIGNED_CERTIFICATE' ) eq 'yes' ) {
        return iMSCP::OpenSSL->new(
            certificate_chains_storage_dir => $::imscpConfig{'CONF_DIR'},
            certificate_chain_name         => 'imscp_services'
        )->createSelfSignedCertificate( {
            common_name => ::setupGetQuestion( 'SERVER_HOSTNAME' ),
            email       => ::setupGetQuestion( 'DEFAULT_ADMIN_ADDRESS' )
        } );
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
    )->createCertificateChain();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
