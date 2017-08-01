=head1 NAME

 Package::ServicesSSL - i-MSCP services SSL

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
use iMSCP::Debug qw/ getMessageByType /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::OpenSSL;
use Net::LibIDN qw/ idn_to_unicode /;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP services SSL.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%eventManager )

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self, $eventManager) = @_;

    $eventManager->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]}, sub { $self->servicesSslDialog( @_ ) };
            0;
        }
    );
}

=item serviceSslDialog( \%dialog )

 Ask for services SSL

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub servicesSslDialog
{
    my (undef, $dialog) = @_;

    my $hostname = main::setupGetQuestion( 'SERVER_HOSTNAME' );
    my $hostnameUnicode = idn_to_unicode( $hostname, 'utf-8' );
    my $sslEnabled = main::setupGetQuestion( 'SERVICES_SSL_ENABLED' );
    my $selfSignedCertificate = main::setupGetQuestion( 'SERVICES_SSL_SELFSIGNED_CERTIFICATE', 'no' );
    my $privateKeyPath = main::setupGetQuestion( 'SERVICES_SSL_PRIVATE_KEY_PATH', '/root' );
    my $passphrase = main::setupGetQuestion( 'SERVICES_SSL_PRIVATE_KEY_PASSPHRASE' );
    my $certificatePath = main::setupGetQuestion( 'SERVICES_SSL_CERTIFICATE_PATH', '/root' );
    my $caBundlePath = main::setupGetQuestion( 'SERVICES_SSL_CA_BUNDLE_PATH', '/root' );
    my $openSSL = iMSCP::OpenSSL->new();

    if ( $main::reconfigure =~ /^(?:services_ssl|ssl|all|forced)$/
        || $sslEnabled !~ /^(?:yes|no)$/
        || ( $sslEnabled eq 'yes' && $main::reconfigure =~ /^(?:system_hostname|hostnames)$/ )
    ) {
        my $rs = $dialog->yesno( <<'EOF', $sslEnabled eq 'no' ? 1 : 0 );

Do you want to enable SSL for FTP and MAIL services?
EOF
        if ( $rs == 0 ) {
            $sslEnabled = 'yes';
            $rs = $dialog->yesno( <<"EOF", $selfSignedCertificate eq 'no' ? 1 : 0 );

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
                        ( $rs, $privateKeyPath ) = $dialog->fselect( $privateKeyPath );
                    } while $rs < 30 && !( $privateKeyPath && -f $privateKeyPath );
                    return $rs if $rs >= 30;

                    ( $rs, $passphrase ) = $dialog->passwordbox( <<'EOF', $passphrase );

Please enter the passphrase for your private key if any:
EOF
                    return $rs if $rs >= 30;

                    $openSSL->{'private_key_container_path'} = $privateKeyPath;
                    $openSSL->{'private_key_passphrase'} = $passphrase;

                    $msg = '';
                    if ( $openSSL->validatePrivateKey() ) {
                        getMessageByType(
                            'error',
                            {
                                amount => 1,
                                remove => 1
                            }
                        );
                        $msg = "\n\\Z1Invalid private key or passphrase.\\Zn\n\nPlease try again.";
                    }
                } while $rs < 30 && $msg;
                return $rs if $rs >= 30;

                $rs = $dialog->yesno( <<'EOF' );

Do you have a SSL CA Bundle?
EOF
                if ( $rs == 0 ) {
                    do {
                        ( $rs, $caBundlePath ) = $dialog->fselect( $caBundlePath );
                    } while $rs < 30 && !( $caBundlePath && -f $caBundlePath );
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
                        ( $rs, $certificatePath ) = $dialog->fselect( $certificatePath );
                    } while $rs < 30 && !( $certificatePath && -f $certificatePath );
                    return $rs if $rs >= 30;

                    getMessageByType(
                        'error',
                        {
                            amount => 1,
                            remove => 1
                        }
                    );
                    $openSSL->{'certificate_container_path'} = $certificatePath;
                } while $rs < 30 && $openSSL->validateCertificate();
                return $rs if $rs >= 30;
            } else {
                $selfSignedCertificate = 'yes';
            }
        } else {
            $sslEnabled = 'no';
        }
    } elsif ( $sslEnabled eq 'yes' && !iMSCP::Getopt->preseed ) {
        $openSSL->{'private_key_container_path'} = "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem";
        $openSSL->{'ca_bundle_container_path'} = "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem";
        $openSSL->{'certificate_container_path'} = "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem";

        if ( $openSSL->validateCertificateChain() ) {
            getMessageByType(
                'error',
                {
                    amount => 1,
                    remove => 1
                }
            );
            $dialog->getInstance()->msgbox( <<'EOF' );

Your SSL certificate for the FTP and MAIL services is missing or invalid.
EOF
            main::setupSetQuestion( 'SERVICES_SSL_ENABLED', '' );
            goto &{servicesSslDialog};
        }

        # In case the certificate is valid, we skip SSL setup process
        main::setupSetQuestion( 'SERVICES_SSL_SETUP', 'no' );
    }

    main::setupSetQuestion( 'SERVICES_SSL_ENABLED', $sslEnabled );
    main::setupSetQuestion( 'SERVICES_SSL_SELFSIGNED_CERTIFICATE', $selfSignedCertificate );
    main::setupSetQuestion( 'SERVICES_SSL_PRIVATE_KEY_PATH', $privateKeyPath );
    main::setupSetQuestion( 'SERVICES_SSL_PRIVATE_KEY_PASSPHRASE', $passphrase );
    main::setupSetQuestion( 'SERVICES_SSL_CERTIFICATE_PATH', $certificatePath );
    main::setupSetQuestion( 'SERVICES_SSL_CA_BUNDLE_PATH', $caBundlePath );
    0;
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my $sslEnabled = main::setupGetQuestion( 'SERVICES_SSL_ENABLED' );

    if ( $sslEnabled eq 'no' || main::setupGetQuestion( 'SERVICES_SSL_SETUP', 'yes' ) eq 'no' ) {
        if ( $sslEnabled eq 'no' && -f "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem" ) {
            my $rs = iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem" )->delFile();
            return $rs if $rs;
        }

        return 0;
    }

    if ( main::setupGetQuestion( 'SERVICES_SSL_SELFSIGNED_CERTIFICATE' ) eq 'yes' ) {
        return iMSCP::OpenSSL->new(
            certificate_chains_storage_dir => $main::imscpConfig{'CONF_DIR'},
            certificate_chain_name         => 'imscp_services'
        )->createSelfSignedCertificate(
            {
                common_name => main::setupGetQuestion( 'SERVER_HOSTNAME' ),
                email       => main::setupGetQuestion( 'DEFAULT_ADMIN_ADDRESS' )
            }
        );
    }

    iMSCP::OpenSSL->new(
        certificate_chains_storage_dir => $main::imscpConfig{'CONF_DIR'},
        certificate_chain_name         => 'imscp_services',
        private_key_container_path     => main::setupGetQuestion( 'SERVICES_SSL_PRIVATE_KEY_PATH' ),
        private_key_passphrase         => main::setupGetQuestion( 'SERVICES_SSL_PRIVATE_KEY_PASSPHRASE' ),
        certificate_container_path     => main::setupGetQuestion( 'SERVICES_SSL_CERTIFICATE_PATH' ),
        ca_bundle_container_path       => main::setupGetQuestion( 'SERVICES_SSL_CA_BUNDLE_PATH' )
    )->createCertificateChain();
}

=item getPriority( )

 Get package priority

 Return int package priority

=cut

sub getPriority
{
    150;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
