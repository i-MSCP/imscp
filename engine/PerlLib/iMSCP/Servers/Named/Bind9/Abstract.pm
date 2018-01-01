=head1 NAME

 iMSCP::Servers::Named::Bind9::Abstract - i-MSCP Bind9 Server abstract implementation

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package iMSCP::Servers::Named::Bind9::Abstract;

use strict;
use warnings;
use autouse 'iMSCP::Dialog::InputValidation' => qw/ isOneOfStringsInList isStringInList /;
use autouse 'iMSCP::Rights' => qw/ setRights /;
use Class::Autouse  qw/ :nostat iMSCP::Getopt /;
use File::Basename;
use iMSCP::Config;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dir;
use iMSCP::Execute qw/ execute /;
use iMSCP::File;
use iMSCP::Net;
use iMSCP::ProgramFinder;
use iMSCP::TemplateParser qw/ getBlocByRef process processByRef replaceBlocByRef /;
use iMSCP::Service;
use iMSCP::Umask;
use version;
use parent 'iMSCP::Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Bind9 Server abstract implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( )

 Register setup event listeners

 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self) = @_;

    $self->{'eventManager'}->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]},
                sub { $self->askDnsServerMode( @_ ) },
                sub { $self->askIPv6Support( @_ ) },
                sub { $self->askLocalDnsResolver( @_ ) };
            0;
        }
    );
}

=item askDnsServerMode( \%dialog )

 Ask user for DNS server type to configure

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub askDnsServerMode
{
    my ($self, $dialog) = @_;

    my $value = main::setupGetQuestion( 'BIND_MODE', $self->{'config'}->{'BIND_MODE'} || ( iMSCP::Getopt->preseed ? 'master' : '' ));
    my %choices = ( 'master', 'Master DNS server', 'slave', 'Slave DNS server' );

    if ( isOneOfStringsInList( iMSCP::Getopt->reconfigure, [ 'named', 'servers', 'all', 'forced' ] ) || !isStringInList( $value, keys %choices ) ) {
        ( my $rs, $value ) = $dialog->radiolist( <<"EOF", \%choices, ( grep( $value eq $_, keys %choices ) )[0] || 'master' );
Please choose the type of DNS server to configure:
\\Z \\Zn
EOF
        return $rs unless $rs < 30;
    }

    $self->{'config'}->{'BIND_MODE'} = $value;
    $self->askDnsServerIps( $dialog );
}

=item askDnsServerIps( \%dialog )

 Ask user for DNS server adresses IP

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub askDnsServerIps
{
    my ($self, $dialog) = @_;

    my $dnsServerMode = $self->{'config'}->{'BIND_MODE'};
    my @masterDnsIps = split /[; \t]+/, main::setupGetQuestion(
            'PRIMARY_DNS', $self->{'config'}->{'PRIMARY_DNS'} || ( iMSCP::Getopt->preseed ? 'no' : '' )
        );
    my @slaveDnsIps = split /[; \t]+/, main::setupGetQuestion(
            'SECONDARY_DNS', $self->{'config'}->{'SECONDARY_DNS'} || ( iMSCP::Getopt->preseed ? 'no' : '' )
        );
    my ($rs, $answer, $msg) = ( 0, '', '' );

    if ( $dnsServerMode eq 'master' ) {
        if ( isOneOfStringsInList( iMSCP::Getopt->reconfigure, [ 'named', 'servers', 'all', 'forced' ] )
            || !@slaveDnsIps
            || ( $slaveDnsIps[0] ne 'no' && !$self->_checkIps( @slaveDnsIps ) )
        ) {
            my %choices = ( 'yes', 'Yes', 'no', 'No' );
            ( $rs, $answer ) = $dialog->radiolist( <<"EOF", \%choices, !@slaveDnsIps || $slaveDnsIps[0] eq 'no' ? 'no' : 'yes' );
Do you want to add slave DNS servers?
\\Z \\Zn
EOF
            if ( $rs < 30 && $answer eq 'yes' ) {
                @slaveDnsIps = () if @slaveDnsIps && $slaveDnsIps[0] eq 'no';

                do {
                    ( $rs, $answer ) = $dialog->inputbox( <<"EOF", join ' ', @slaveDnsIps );
$msg
Please enter the IP addresses for the slave DNS servers, each separated by a space or semicolon:
EOF
                    $msg = '';
                    if ( $rs < 30 ) {
                        @slaveDnsIps = split /[; ]+/, $answer;

                        if ( !@slaveDnsIps ) {
                            $msg = <<"EOF";
\\Z1You must enter at least one IP address.\\Zn
EOF

                        } elsif ( !$self->_checkIps( @slaveDnsIps ) ) {
                            $msg = <<"EOF"
\\Z1Wrong or disallowed IP address found.\\Zn
EOF
                        }
                    }
                } while $rs < 30 && $msg;
            } else {
                @slaveDnsIps = ( 'no' );
            }
        }
    } elsif ( isOneOfStringsInList( iMSCP::Getopt->reconfigure, [ 'named', 'servers', 'all', 'forced' ] )
        || !@slaveDnsIps
        || $slaveDnsIps[0] eq 'no'
        || !$self->_checkIps( @masterDnsIps )
    ) {
        @masterDnsIps = () if @masterDnsIps && $masterDnsIps[0] eq 'no';

        do {
            ( $rs, $answer ) = $dialog->inputbox( <<"EOF", join ' ', @masterDnsIps );
$msg
Please enter the IP addresses for the master DNS server, each separated by space or semicolon:
EOF
            $msg = '';
            if ( $rs < 30 ) {
                @masterDnsIps = split /[; ]+/, $answer;

                if ( !@masterDnsIps ) {
                    $msg = <<"EOF";
\\Z1You must enter a least one IP address.\\Zn
EOF
                } elsif ( !$self->_checkIps( @masterDnsIps ) ) {
                    $msg = <<"EOF";
\\Z1Wrong or disallowed IP address found.\\Zn
EOF
                }
            }
        } while $rs < 30 && $msg;
    }

    return $rs unless $rs < 30;

    if ( $dnsServerMode eq 'master' ) {
        $self->{'config'}->{'PRIMARY_DNS'} = 'no';
        $self->{'config'}->{'SECONDARY_DNS'} = join ';', @slaveDnsIps;
        return $rs;
    }

    $self->{'config'}->{'PRIMARY_DNS'} = join ';', @masterDnsIps;
    $self->{'config'}->{'SECONDARY_DNS'} = 'no';
    $rs;
}

=item askIPv6Support( \%dialog )

 Ask user for DNS server IPv6 support

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub askIPv6Support
{
    my ($self, $dialog) = @_;

    unless ( main::setupGetQuestion( 'IPV6_SUPPORT' ) ) {
        $self->{'config'}->{'BIND_IPV6'} = 'no';
        return 0;
    }

    my $value = main::setupGetQuestion( 'BIND_IPV6', $self->{'config'}->{'BIND_IPV6'} || ( iMSCP::Getopt->preseed ? 'no' : '' ));
    my %choices = ( 'yes', 'Yes', 'no', 'No' );

    if ( isOneOfStringsInList( iMSCP::Getopt->reconfigure, [ 'named', 'servers', 'all', 'forced' ] ) || !isStringInList( $value, keys %choices ) ) {
        ( my $rs, $value ) = $dialog->radiolist( <<"EOF", \%choices, ( grep( $value eq $_, keys %choices ) )[0] || 'no' );
Do you want to enable IPv6 support for the DNS server?
\\Z \\Zn
EOF
        return $rs unless $rs < 30;
    }

    $self->{'config'}->{'BIND_IPV6'} = $value;
    0;
}

=item askLocalDnsResolver( \%dialog )

 Ask user for local DNS resolver

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub askLocalDnsResolver
{
    my ($self, $dialog) = @_;

    my $value = main::setupGetQuestion(
        'LOCAL_DNS_RESOLVER', $self->{'config'}->{'LOCAL_DNS_RESOLVER'} || ( iMSCP::Getopt->preseed ? 'yes' : '' )
    );
    my %choices = ( 'yes', 'Yes', 'no', 'No' );

    if ( isOneOfStringsInList( iMSCP::Getopt->reconfigure, [ 'resolver', 'named', 'servers', 'all', 'forced' ] )
        || !isStringInList( $value, keys %choices )
    ) {
        ( my $rs, $value ) = $dialog->radiolist( <<"EOF", \%choices, ( grep( $value eq $_, keys %choices ) )[0] || 'yes' );
Do you want to use the local DNS resolver?
\\Z \\Zn
EOF
        return $rs unless $rs < 30;
    }

    $self->{'config'}->{'LOCAL_DNS_RESOLVER'} = $value;
    0;
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9PreInstall' );
    $rs ||= $self->{'eventManager'}->trigger( 'afterBind9PreInstall' );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    for my $conffile( 'BIND_CONF_DEFAULT_FILE', 'BIND_CONF_FILE', 'BIND_LOCAL_CONF_FILE', 'BIND_OPTIONS_CONF_FILE' ) {
        if ( $self->{'config'}->{$conffile} ne '' ) {
            my $rs = $self->_bkpConfFile( $self->{'config'}->{$conffile} );
            return $rs if $rs;
        }
    }

    my $rs = $self->_makeDirs();
    $rs ||= $self->_buildConf();
    $rs ||= $self->_cleanup();
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9PostInstall' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->enable( $self->{'config'}->{'NAMED_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs ||= $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]}, [ sub { $self->restart(); }, 'Bind9' ];
            0;
        },
        100
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterBind9PostInstall' );
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    my $rs = $self->_removeConfig();
    return $rs if $rs;

    if ( iMSCP::ProgramFinder::find( $self->{'config'}->{'NAMED_BNAME'} ) ) {
        $rs = $self->restart();
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterBind9Uninstall' );
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ($self) = @_;

    my $rs = setRights( $self->{'config'}->{'BIND_CONF_DIR'},
        {
            user      => $main::imscpConfig{'ROOT_USER'},
            group     => $self->{'config'}->{'BIND_GROUP'},
            dirmode   => '2750',
            filemode  => '0640',
            recursive => 1
        }
    );
    $rs ||= setRights( $self->{'config'}->{'BIND_DB_ROOT_DIR'},
        {
            user      => $self->{'config'}->{'BIND_USER'},
            group     => $self->{'config'}->{'BIND_GROUP'},
            dirmode   => '2750',
            filemode  => '0640',
            recursive => 1
        }
    );
}

=item addDomain( \%data )

 Process addDomain tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDomain
{
    my ($self, $data) = @_;

    # Never process the same zone twice
    # Occurs only in few contexts (eg. when using BASE_SERVER_VHOST as customer domain)
    return 0 if $self->{'seen_zones'}->{$data->{'DOMAIN_NAME'}};

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9AddDomain', $data );
    $rs ||= $self->_addDmnConfig( $data );
    return $rs if $rs;

    if ( $self->{'config'}->{'BIND_MODE'} eq 'master' ) {
        $rs = $self->_addDmnDb( $data );
        return $rs if $rs;
    }

    $self->{'seen_zones'}->{$data->{'DOMAIN_NAME'}} ||= 1;
    $self->{'eventManager'}->trigger( 'afterBind9AddDomain', $data );
}

=item postaddDomain( \%data )

 Process postaddDomain tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub postaddDomain
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9PostAddDomain', $data );
    return $rs if $rs;

    if ( $main::imscpConfig{'CLIENT_DOMAIN_ALT_URLS'} eq 'yes' && $self->{'config'}->{'BIND_MODE'} eq 'master' && defined $data->{'ALIAS'} ) {
        $rs = $self->addSubdomain( {
            # Listeners want probably know real parent domain name for the
            # DNS name being added even if that entry is added in another
            # zone. For instance, see the 20_named_dualstack.pl listener
            # file. (since 1.6.0)
            REAL_PARENT_DOMAIN_NAME => $data->{'PARENT_DOMAIN_NAME'},
            PARENT_DOMAIN_NAME      => $main::imscpConfig{'BASE_SERVER_VHOST'},
            DOMAIN_NAME             => $data->{'ALIAS'} . '.' . $main::imscpConfig{'BASE_SERVER_VHOST'},
            MAIL_ENABLED            => 0,
            DOMAIN_IP               => $data->{'BASE_SERVER_PUBLIC_IP'},
            # Listeners want probably know type of the entry being added (since 1.6.0)
            DOMAIN_TYPE             => 'sub',
            BASE_SERVER_PUBLIC_IP   => $data->{'BASE_SERVER_PUBLIC_IP'},
            OPTIONAL_ENTRIES        => 0,
            STATUS                  => $data->{'STATUS'} # (since 1.6.0)
        } );
        return $rs if $rs;
    }

    $self->{'reload'} ||= 1;
    $self->{'eventManager'}->trigger( 'afterBind9PostAddDomain', $data );
}

=item disableDomain( \%data )

 Process disableDomain tasks

 When a domain is being disabled, we must ensure that the DNS data are still
 present for it (eg: when doing a full upgrade or reconfiguration). This
 explain here why we are executing the addDomain() method.

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub disableDomain
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9DisableDomain', $data );
    $rs ||= $self->addDomain( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterBind9DisableDomain', $data );
}

=item postdisableDomain( \%data )

 Process postdisableDomain tasks

 See the disableDomain() method for explaination.

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub postdisableDomain
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9PostDisableDomain', $data );
    $rs ||= $self->postaddDomain( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterBind9PostDisableDomain', $data );
}

=item deleteDomain( \%data )

 Process deleteDomain tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDomain
{
    my ($self, $data) = @_;

    return 0 if $data->{'PARENT_DOMAIN_NAME'} eq $main::imscpConfig{'BASE_SERVER_VHOST'} && !$data->{'FORCE_DELETION'};

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9DeleteDomain', $data );
    $rs ||= $self->_deleteDmnConfig( $data );
    return $rs if $rs;

    if ( $self->{'config'}->{'BIND_MODE'} eq 'master' ) {
        for ( "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db", "$self->{'config'}->{'BIND_DB_MASTER_DIR'}/$data->{'DOMAIN_NAME'}.db" ) {
            next unless -f;
            $rs = iMSCP::File->new( filename => $_ )->delFile();
            return $rs if $rs;
        }
    }

    $self->{'eventManager'}->trigger( 'afterBind9DeleteDomain', $data );
}

=item postdeleteDomain( \%data )

 Process postdeleteDomain tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub postdeleteDomain
{
    my ($self, $data) = @_;

    return 0 if $data->{'PARENT_DOMAIN_NAME'} eq $main::imscpConfig{'BASE_SERVER_VHOST'} && !$data->{'FORCE_DELETION'};

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9PostDeleteDomain', $data );
    return $rs if $rs;

    if ( $main::imscpConfig{'CLIENT_DOMAIN_ALT_URLS'} eq 'yes' && $self->{'config'}->{'BIND_MODE'} eq 'master' && defined $data->{'ALIAS'} ) {
        $rs = $self->deleteSubdomain( {
            PARENT_DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
            DOMAIN_NAME        => $data->{'ALIAS'} . '.' . $main::imscpConfig{'BASE_SERVER_VHOST'}
        } );
        return $rs if $rs;
    }

    $self->{'reload'} ||= 1;
    $self->{'eventManager'}->trigger( 'afterBind9PostDeleteDomain', $data );
}

=item addSubdomain( \%data )

 Process addSubdomain tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSubdomain
{
    my ($self, $data) = @_;

    return 0 unless $self->{'config'}->{'BIND_MODE'} eq 'master';

    my $wrkDbFile = "$self->{'wrkDir'}/$data->{'PARENT_DOMAIN_NAME'}.db";
    unless ( -f $wrkDbFile ) {
        error( sprintf( 'File %s not found. Run imscp-reconfigure script.', $wrkDbFile ));
        return 1;
    }

    $wrkDbFile = iMSCP::File->new( filename => $wrkDbFile );
    my $wrkDbFileContent = $wrkDbFile->get();
    unless ( defined $wrkDbFileContent ) {
        error( sprintf( "Couldn't read the %s file", $wrkDbFile->{'filename'} ));
        return 1;
    }

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'bind9', 'db_sub.tpl', \ my $subEntry, $data );
    return $rs if $rs;

    unless ( defined $subEntry ) {
        $subEntry = iMSCP::File->new( filename => "$self->{'tplDir'}/db_sub.tpl" )->get();
        unless ( defined $subEntry ) {
            error( sprintf( "Couldn't read the %s file", "$self->{'tplDir'}/db_sub.tpl file" ));
            return 1;
        }
    }

    unless ( $self->{'serials'}->{$data->{'PARENT_DOMAIN_NAME'}} ) {
        $rs = $self->_updateSOAserialNumber( $data->{'PARENT_DOMAIN_NAME'}, \$wrkDbFileContent, \$wrkDbFileContent );
    }

    $rs ||= $self->{'eventManager'}->trigger( 'beforeBind9AddSubdomain', \$wrkDbFileContent, \$subEntry, $data );
    return $rs if $rs;

    my $net = iMSCP::Net->getInstance();

    replaceBlocByRef(
        "; sub MAIL entry BEGIN\n",
        "; sub MAIL entry ENDING\n",
        ( $data->{'MAIL_ENABLED'}
            ? process(
                {
                    BASE_SERVER_IP_TYPE => ( $net->getAddrVersion( $data->{'BASE_SERVER_PUBLIC_IP'} ) eq 'ipv4' ) ? 'A' : 'AAAA',
                    BASE_SERVER_IP      => $data->{'BASE_SERVER_PUBLIC_IP'},
                    DOMAIN_NAME         => $data->{'PARENT_DOMAIN_NAME'}
                },
                getBlocByRef( "; sub MAIL entry BEGIN\n", "; sub MAIL entry ENDING\n", \$subEntry )
            )
            : ''
        ),
        \$subEntry
    );

    if ( defined $data->{'OPTIONAL_ENTRIES'} && !$data->{'OPTIONAL_ENTRIES'} ) {
        replaceBlocByRef( "; sub OPTIONAL entries BEGIN\n", "; sub OPTIONAL entries ENDING\n", '', \$subEntry );
    }

    my $domainIP = $net->isRoutableAddr( $data->{'DOMAIN_IP'} ) ? $data->{'DOMAIN_IP'} : $data->{'BASE_SERVER_PUBLIC_IP'};

    processByRef(
        {
            SUBDOMAIN_NAME => $data->{'DOMAIN_NAME'},
            IP_TYPE        => $net->getAddrVersion( $domainIP ) eq 'ipv4' ? 'A' : 'AAAA',
            DOMAIN_IP      => $domainIP
        },
        \$subEntry
    );

    replaceBlocByRef( "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n", "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n", '', \$wrkDbFileContent );
    replaceBlocByRef(
        "; sub [{SUBDOMAIN_NAME}] entry BEGIN\n", "; sub [{SUBDOMAIN_NAME}] entry ENDING\n", $subEntry, \$wrkDbFileContent, 'preserve'
    );

    $rs = $self->{'eventManager'}->trigger( 'afterBind9AddSubdomain', \$wrkDbFileContent, $data );
    $rs ||= $wrkDbFile->set( $wrkDbFileContent );
    $rs ||= $wrkDbFile->save();
    $rs ||= $self->_compileZone( $data->{'PARENT_DOMAIN_NAME'}, $wrkDbFile->{'filename'} );
}

=item postaddSubdomain( \%data )

 Process postaddSubdomain tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub postaddSubdomain
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9PostAddSubdomain', $data );
    return $rs if $rs;

    if ( $main::imscpConfig{'CLIENT_DOMAIN_ALT_URLS'} eq 'yes' && $self->{'config'}->{'BIND_MODE'} eq 'master' && defined $data->{'ALIAS'} ) {
        $rs = $self->addSubdomain( {
            # Listeners want probably know real parent domain name for the
            # DNS name being added even if that entry is added in another
            # zone. For instance, see the 20_named_dualstack.pl listener
            # file. (since 1.6.0)
            REAL_PARENT_DOMAIN_NAME => $data->{'PARENT_DOMAIN_NAME'},
            PARENT_DOMAIN_NAME      => $main::imscpConfig{'BASE_SERVER_VHOST'},
            DOMAIN_NAME             => $data->{'ALIAS'} . '.' . $main::imscpConfig{'BASE_SERVER_VHOST'},
            MAIL_ENABLED            => 0,
            DOMAIN_IP               => $data->{'BASE_SERVER_PUBLIC_IP'},
            # Listeners want probably know type of the entry being added (since 1.6.0)
            DOMAIN_TYPE             => 'sub',
            BASE_SERVER_PUBLIC_IP   => $data->{'BASE_SERVER_PUBLIC_IP'},
            OPTIONAL_ENTRIES        => 0,
            STATUS                  => $data->{'STATUS'} # (since 1.6.0)
        } );
        return $rs if $rs;
    }

    $self->{'reload'} ||= 1;
    $self->{'eventManager'}->trigger( 'afterBind9PostAddSubdomain', $data );
}

=item disableSubdomain( \%data )

 Process disableSubdomain tasks

 When a subdomain is being disabled, we must ensure that the DNS data are still present for it (eg: when doing a full
 upgrade or reconfiguration). This explain here why we are executing the addSubdomain() action.

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub disableSubdomain
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9DisableSubdomain', $data );
    $rs ||= $self->addSubdomain( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterBind9DisableSubdomain', $data );
}

=item postdisableSubdomain( \%data )

 Process postdisableSubdomain tasks

 See the disableSubdomain( ) method for explaination.

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub postdisableSubdomain
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9PostDisableSubdomain', $data );
    $rs ||= $self->postaddSubdomain( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterBind9PostDisableSubdomain', $data );
}

=item deleteSubdomain( \%data )

 Process deleteSubdomain tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSubdomain
{
    my ($self, $data) = @_;

    return 0 unless $self->{'config'}->{'BIND_MODE'} eq 'master';

    my $wrkDbFile = "$self->{'wrkDir'}/$data->{'PARENT_DOMAIN_NAME'}.db";
    unless ( -f $wrkDbFile ) {
        error( sprintf( 'File %s not found. Run imscp-reconfigure script.', $wrkDbFile ));
        return 1;
    }

    $wrkDbFile = iMSCP::File->new( filename => $wrkDbFile );
    my $wrkDbFileContent = $wrkDbFile->get();
    unless ( defined $wrkDbFileContent ) {
        error( sprintf( "Couldn't read the %s file", $wrkDbFile->{'filename'} ));
        return 1;
    }

    unless ( $self->{'serials'}->{$data->{'PARENT_DOMAIN_NAME'}} ) {
        my $rs = $self->_updateSOAserialNumber( $data->{'PARENT_DOMAIN_NAME'}, \$wrkDbFileContent, \$wrkDbFileContent );
        return $rs if $rs;
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9DeleteSubdomain', \$wrkDbFileContent, $data );
    return $rs if $rs;

    replaceBlocByRef( "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n", "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n", '', \$wrkDbFileContent );

    $rs = $self->{'eventManager'}->trigger( 'afterBind9DeleteSubdomain', \$wrkDbFileContent, $data );
    $rs ||= $wrkDbFile->set( $wrkDbFileContent );
    $rs ||= $wrkDbFile->save();
    $rs ||= $self->_compileZone( $data->{'PARENT_DOMAIN_NAME'}, $wrkDbFile->{'filename'} );
}

=item postdeleteSubdomain( \%data )

 Process postdeleteSubdomain tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub postdeleteSubdomain
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9PostDeleteSubdomain', $data );
    return $rs if $rs;

    if ( $main::imscpConfig{'CLIENT_DOMAIN_ALT_URLS'} eq 'yes' && $self->{'config'}->{'BIND_MODE'} eq 'master' && defined $data->{'ALIAS'} ) {
        $rs = $self->deleteSubdomain( {
            PARENT_DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
            DOMAIN_NAME        => $data->{'ALIAS'} . '.' . $main::imscpConfig{'BASE_SERVER_VHOST'}
        } );
        return $rs if $rs;
    }

    $self->{'reload'} ||= 1;
    $self->{'eventManager'}->trigger( 'afterBind9PostDeleteSubdomain', $data );
}

=item addCustomDNS( \%data )

 Process addCustomDNS tasks

 Param hash \%data Custom DNS data
 Return int 0 on success, other on failure

=cut

sub addCustomDNS
{
    my ($self, $data) = @_;

    return 0 unless $self->{'config'}->{'BIND_MODE'} eq 'master';

    my $wrkDbFile = "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db";
    unless ( -f $wrkDbFile ) {
        error( sprintf( 'File %s not found. Run imscp-reconfigure script.', $wrkDbFile ));
        return 1;
    }

    $wrkDbFile = iMSCP::File->new( filename => $wrkDbFile );
    my $wrkDbFileContent = $wrkDbFile->get();
    unless ( defined $wrkDbFileContent ) {
        error( sprintf( "Couldn't read the %s file", $wrkDbFile->{'filename'} ));
        return 1;
    }

    unless ( $self->{'serials'}->{$data->{'DOMAIN_NAME'}} ) {
        my $rs = $self->_updateSOAserialNumber( $data->{'DOMAIN_NAME'}, \$wrkDbFileContent, \$wrkDbFileContent );
        return $rs if $rs;
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9AddCustomDNS', \$wrkDbFileContent, $data );
    return $rs if $rs;

    my @customDNS = ();
    push @customDNS, join "\t", @{$_} for @{$data->{'DNS_RECORDS'}};

    my $fh;
    unless ( open( $fh, '<', \$wrkDbFileContent ) ) {
        error( sprintf( "Couldn't open in-memory file handle: %s", $! ));
        return 1;
    }

    my ($newWrkDbFileContent, $origin) = ( '', '' );
    while ( my $line = <$fh> ) {
        my $isOrigin = $line =~ /^\$ORIGIN\s+([^\s;]+).*\n$/;
        $origin = $1 if $isOrigin; # Update $ORIGIN if needed

        unless ( $isOrigin || index( $line, '$' ) == 0 || index( $line, ';' ) == 0 ) {
            # Process $ORIGIN substitutions
            $line =~ s/\@/$origin/g;
            $line =~ s/^(\S+?[^\s.])\s+/$1.$origin\t/;
            # Skip default SPF record line if SPF record for the same DNS name exists in @customDNS
            next if $line =~ /^(\S+)\s+.*?\s+"v=\bspf1\b.*?"/ && grep /^\Q$1\E\s+.*?\s+"v=\bspf1\b.*?"/, @customDNS;
        }

        $newWrkDbFileContent .= $line;
    }
    close( $fh );
    undef $wrkDbFileContent;

    replaceBlocByRef(
        "; custom DNS entries BEGIN\n",
        "; custom DNS entries ENDING\n",
        "; custom DNS entries BEGIN\n" . ( join "\n", @customDNS, '' ) . "; custom DNS entries ENDING\n",
        \$newWrkDbFileContent
    );

    $rs = $self->{'eventManager'}->trigger( 'afterBind9AddCustomDNS', \$newWrkDbFileContent, $data );
    $rs ||= $wrkDbFile->set( $newWrkDbFileContent );
    $rs ||= $wrkDbFile->save();
    $rs ||= $self->_compileZone( $data->{'DOMAIN_NAME'}, $wrkDbFile->{'filename'} );
    $self->{'reload'} ||= 1 unless $rs;
    $rs;
}

=item restart( )

 Restart Bind9

 Return int 0 on success, other on failure

=cut

sub restart
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9Restart' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->restart( $self->{'config'}->{'NAMED_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterBind9Restart' );
}

=item reload( )

 Reload Bind9

 Return int 0 on success, other on failure

=cut

sub reload
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9Reload' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->reload( $self->{'config'}->{'NAMED_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterBind9Reload' );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Servers::Named::Bind9::Abstract

=cut

sub _init
{
    my ($self) = @_;

    @{$self}{qw/ restart reload serials seen_zones /} = ( 0, 0, {}, {} );
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/bind";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'tplDir'} = "$self->{'cfgDir'}/parts";
    $self->_mergeConfig() if defined $main::execmode && $main::execmode eq 'setup' && -f "$self->{'cfgDir'}/bind.data.dist";
    tie %{$self->{'config'}},
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/bind.data",
        readonly    => !( defined $main::execmode && $main::execmode eq 'setup' ),
        nodeferring => defined $main::execmode && $main::execmode eq 'setup';
    $self;
}

=item _mergeConfig( )

 Merge distribution configuration with production configuration

 Die on failure

=cut

sub _mergeConfig
{
    my ($self) = @_;

    if ( -f "$self->{'cfgDir'}/bind.data" ) {
        tie my %newConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/bind.data.dist";
        tie my %oldConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/bind.data", readonly => 1;

        debug( 'Merging old configuration with new configuration ...' );

        while ( my ($key, $value) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }

        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$self->{'cfgDir'}/bind.data.dist" )->moveFile( "$self->{'cfgDir'}/bind.data" ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
}

=item _addDmnConfig( \%data )

 Add domain DNS configuration

 Param hash \%data Data as provided by the Domain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _addDmnConfig
{
    my ($self, $data) = @_;

    unless ( defined $self->{'config'}->{'BIND_MODE'} ) {
        error( 'Bind mode is not defined. Run imscp-reconfigure script.' );
        return 1;
    }

    my ($cfgFileName, $cfgFileDir) = fileparse(
        $self->{'config'}->{'BIND_LOCAL_CONF_FILE'} || $self->{'config'}->{'BIND_CONF_FILE'}
    );

    unless ( -f "$self->{'wrkDir'}/$cfgFileName" ) {
        error( sprintf( 'File %s not found. Run imscp-reconfigure script.', "$self->{'wrkDir'}/$cfgFileName" ));
        return 1;
    }

    my $cfgFile = iMSCP::File->new( filename => "$self->{'wrkDir'}/$cfgFileName" );
    my $cfgWrkFileContent = $cfgFile->get();
    unless ( defined $cfgWrkFileContent ) {
        error( sprintf( "Couldn't read the %s file", "$self->{'wrkDir'}/$cfgFileName" ));
        return 1;
    }

    my $tplFileName = "cfg_$self->{'config'}->{'BIND_MODE'}.tpl";
    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'bind9', $tplFileName, \ my $tplCfgEntryContent, $data );
    return $rs if $rs;

    unless ( defined $tplCfgEntryContent ) {
        $tplCfgEntryContent = iMSCP::File->new( filename => "$self->{'tplDir'}/$tplFileName" )->get();
        unless ( defined $tplCfgEntryContent ) {
            error( sprintf( "Couldn't read the %s file", "$self->{'tplDir'}/$tplFileName" ));
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeBind9AddDmnConfig', \$cfgWrkFileContent, \$tplCfgEntryContent, $data );
    return $rs if $rs;

    my $tags = {
        BIND_DB_FORMAT => $self->{'config'}->{'BIND_DB_FORMAT'} =~ s/=\d//r,
        DOMAIN_NAME    => $data->{'DOMAIN_NAME'}
    };

    if ( $self->{'config'}->{'BIND_MODE'} eq 'master' ) {
        if ( $self->{'config'}->{'SECONDARY_DNS'} ne 'no' ) {
            $tags->{'SECONDARY_DNS'} = join( '; ', split( ';', $self->{'config'}->{'SECONDARY_DNS'} )) . '; localhost;';
        } else {
            $tags->{'SECONDARY_DNS'} = 'localhost;';
        }
    } else {
        $tags->{'PRIMARY_DNS'} = join( '; ', split( ';', $self->{'config'}->{'PRIMARY_DNS'} )) . ';';
    }

    replaceBlocByRef(
        "// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n", "// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n", '', \$cfgWrkFileContent
    );
    replaceBlocByRef( "// imscp [{ENTRY_ID}] entry BEGIN\n", "// imscp [{ENTRY_ID}] entry ENDING\n", <<"EOF", \$cfgWrkFileContent, 'preserve' );
// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN
@{ [ process( $tags, $tplCfgEntryContent ) ] }
// imscp [$data->{'DOMAIN_NAME'}] entry ENDING
EOF

    $rs = $self->{'eventManager'}->trigger( 'afterBind9AddDmnConfig', \$cfgWrkFileContent, $data );
    $rs ||= $cfgFile->set( $cfgWrkFileContent );
    $rs ||= $cfgFile->save();
    $rs ||= $cfgFile->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'} );
    $rs ||= $cfgFile->mode( 0640 );
    $rs ||= $cfgFile->copyFile( "$cfgFileDir$cfgFileName" );
}

=item _deleteDmnConfig( \%data )

 Delete domain DNS configuration

 Param hash \%data Data as provided by the Domain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _deleteDmnConfig
{
    my ($self, $data) = @_;

    my ($cfgFileName, $cfgFileDir) = fileparse(
        $self->{'config'}->{'BIND_LOCAL_CONF_FILE'} || $self->{'config'}->{'BIND_CONF_FILE'}
    );

    unless ( -f "$self->{'wrkDir'}/$cfgFileName" ) {
        error( sprintf( 'File %s not found. Run imscp-reconfigure script.', "$self->{'wrkDir'}/$cfgFileName" ));
        return 1;
    }

    my $cfgFile = iMSCP::File->new( filename => "$self->{'wrkDir'}/$cfgFileName" );
    my $cfgWrkFileContent = $cfgFile->get();
    unless ( defined $cfgWrkFileContent ) {
        error( sprintf( "Couldn't read the %s file", "$self->{'wrkDir'}/$cfgFileName" ));
        return 1;
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9DelDmnConfig', \$cfgWrkFileContent, $data );
    return $rs if $rs;

    replaceBlocByRef(
        "// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n", "// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n", '', \$cfgWrkFileContent
    );

    $rs = $self->{'eventManager'}->trigger( 'afterBind9DelDmnConfig', \$cfgWrkFileContent, $data );
    $rs ||= $cfgFile->set( $cfgWrkFileContent );
    $rs ||= $cfgFile->save();
    $rs ||= $cfgFile->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'} );
    $rs ||= $cfgFile->mode( 0640 );
    $rs ||= $cfgFile->copyFile( "$cfgFileDir$cfgFileName" );
}

=item _addDmnDb( \%data )

 Add domain DNS zone file

 Param hash \%data Data as provided by the Domain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _addDmnDb
{
    my ($self, $data) = @_;

    my $wrkDbFile = iMSCP::File->new( filename => "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db" );
    my $wrkDbFileContent;

    if ( -f $wrkDbFile->{'filename'} && !defined ( $wrkDbFileContent = $wrkDbFile->get()) ) {
        error( sprintf( "Couldn't read the %s file", $wrkDbFile->{'filename'} ));
        return 1;
    }

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'bind9', 'db.tpl', \ my $tplDbFileC, $data );
    return $rs if $rs;

    unless ( defined $tplDbFileC ) {
        $tplDbFileC = iMSCP::File->new( filename => "$self->{'tplDir'}/db.tpl" )->get();
        unless ( defined $tplDbFileC ) {
            error( sprintf( "Couldn't read the %s file", "$self->{'tplDir'}/db.tpl" ));
            return 1;
        }
    }

    $rs = $self->_updateSOAserialNumber( $data->{'DOMAIN_NAME'}, \$tplDbFileC, \$wrkDbFileContent );
    $rs ||= $self->{'eventManager'}->trigger( 'beforeBind9AddDomainDb', \$tplDbFileC, $data );
    return $rs if $rs;

    my $nsRecordB = getBlocByRef( "; dmn NS RECORD entry BEGIN\n", "; dmn NS RECORD entry ENDING\n", \$tplDbFileC );
    my $glueRecordB = getBlocByRef( "; dmn NS GLUE RECORD entry BEGIN\n", "; dmn NS GLUE RECORD entry ENDING\n", \$tplDbFileC );

    my $net = iMSCP::Net->getInstance();
    my $domainIP = $net->isRoutableAddr( $data->{'DOMAIN_IP'} ) ? $data->{'DOMAIN_IP'} : $data->{'BASE_SERVER_PUBLIC_IP'};

    unless ( $nsRecordB eq '' && $glueRecordB eq '' ) {
        my @nsIPs = ( $domainIP, ( ( $self->{'config'}->{'SECONDARY_DNS'} eq 'no' ) ? () : split ';', $self->{'config'}->{'SECONDARY_DNS'} ) );
        my ($nsRecords, $glueRecords) = ( '', '' );

        for my $ipAddrType( qw/ ipv4 ipv6 / ) {
            my $nsNumber = 1;

            for my $ipAddr( @nsIPs ) {
                next unless $net->getAddrVersion( $ipAddr ) eq $ipAddrType;
                $nsRecords .= process( { NS_NAME => 'ns' . $nsNumber }, $nsRecordB ) if $nsRecordB ne '';
                $glueRecords .= process(
                    {
                        NS_NAME    => 'ns' . $nsNumber,
                        NS_IP_TYPE => ( $ipAddrType eq 'ipv4' ) ? 'A' : 'AAAA',
                        NS_IP      => $ipAddr
                    },
                    $glueRecordB
                ) if $glueRecordB ne '';

                $nsNumber++;
            }
        }

        replaceBlocByRef( "; dmn NS RECORD entry BEGIN\n", "; dmn NS RECORD entry ENDING\n", $nsRecords, \$tplDbFileC ) if $nsRecordB ne '';

        if ( $glueRecordB ne '' ) {
            replaceBlocByRef( "; dmn NS GLUE RECORD entry BEGIN\n", "; dmn NS GLUE RECORD entry ENDING\n", $glueRecords, \$tplDbFileC );
        }
    }

    my $dmnMailEntry = '';
    if ( $data->{'MAIL_ENABLED'} ) {
        $dmnMailEntry = process(
            {
                BASE_SERVER_IP_TYPE => ( $net->getAddrVersion( $data->{'BASE_SERVER_PUBLIC_IP'} ) eq 'ipv4' ) ? 'A' : 'AAAA',
                BASE_SERVER_IP      => $data->{'BASE_SERVER_PUBLIC_IP'}
            },
            getBlocByRef( "; dmn MAIL entry BEGIN\n", "; dmn MAIL entry ENDING\n", \$tplDbFileC )
        )
    }

    replaceBlocByRef( "; dmn MAIL entry BEGIN\n", "; dmn MAIL entry ENDING\n", $dmnMailEntry, \$tplDbFileC );

    processByRef(
        {
            DOMAIN_NAME => $data->{'DOMAIN_NAME'},
            IP_TYPE     => ( $net->getAddrVersion( $domainIP ) eq 'ipv4' ) ? 'A' : 'AAAA',
            DOMAIN_IP   => $domainIP
        },
        \$tplDbFileC
    );

    unless ( !defined $wrkDbFileContent || defined $main::execmode && $main::execmode eq 'setup' ) {
        # Re-add subdomain entries
        replaceBlocByRef(
            "; sub entries BEGIN\n",
            "; sub entries ENDING\n",
            getBlocByRef( "; sub entries BEGIN\n", "; sub entries ENDING\n", \$wrkDbFileContent, 'with_tags' ),
            \$tplDbFileC
        );

        # Re-add custom DNS entries
        replaceBlocByRef(
            "; custom DNS entries BEGIN\n",
            "; custom DNS entries ENDING\n",
            getBlocByRef( "; custom DNS entries BEGIN\n", "; custom DNS entries ENDING\n", \$wrkDbFileContent, 'with_tags' ),
            \$tplDbFileC
        );
    }

    $rs = $self->{'eventManager'}->trigger( 'afterBind9AddDomainDb', \$tplDbFileC, $data );
    $rs ||= $wrkDbFile->set( $tplDbFileC );
    $rs ||= $wrkDbFile->save();
    $rs ||= $self->_compileZone( $data->{'DOMAIN_NAME'}, $wrkDbFile->{'filename'} );
}

=item _updateSOAserialNumber( $zone, \$zoneFileContent, \$oldZoneFileContent )

 Update SOA serial number for the given zone
 
 Note: Format follows RFC 1912 section 2.2 recommendations.

 Param string zone Zone name
 Param scalarref \$zoneFileContent Reference to zone file content
 Param scalarref \$oldZoneFileContent Reference to old zone file content
 Return int 0 on success, other on failure

=cut

sub _updateSOAserialNumber
{
    my ($self, $zone, $zoneFileContent, $oldZoneFileContent) = @_;

    $oldZoneFileContent = $zoneFileContent unless defined ${$oldZoneFileContent};

    if ( ${$oldZoneFileContent} !~ /^\s+(?:(?<date>\d{8})(?<nn>\d{2})|(?<placeholder>\{TIMESTAMP\}))\s*;[^\n]*\n/m ) {
        error( sprintf( "Couldn't update SOA serial number for the %s DNS zone", $zone ));
        return 1;
    }

    my %rc = %+;
    my ($d, $m, $y) = ( gmtime() )[3 .. 5];
    my $nowDate = sprintf( "%d%02d%02d", $y+1900, $m+1, $d );

    if ( exists $+{'placeholder'} ) {
        $self->{'serials'}->{$zone} = $nowDate . '00';
        processByRef( { TIMESTAMP => $self->{'serials'}->{$zone} }, $zoneFileContent );
        return 0;
    }

    if ( $rc{'date'} >= $nowDate ) {
        $rc{'nn'}++;

        if ( $rc{'nn'} >= 99 ) {
            $rc{'date'}++;
            $rc{'nn'} = '00';
        }
    } else {
        $rc{'date'} = $nowDate;
        $rc{'nn'} = '00';
    }

    $self->{'serials'}->{$zone} = $rc{'date'} . $rc{'nn'};
    ${$zoneFileContent} =~ s/^(\s+)(?:\d{10}|\{TIMESTAMP\})(\s*;[^\n]*\n)/$1$self->{'serials'}->{$zone}$2/m;
    0;
}

=item _compileZone( $zonename, $filename )

 Compiles the given zone
 
 Param string $zonename Zone name
 Param string $filename Path to zone filename (zone in text format)
 Return int 0 on success, other on error
 
=cut

sub _compileZone
{
    my ($self, $zonename, $filename) = @_;

    local $UMASK = 027;
    my $rs = execute(
        [
            'named-compilezone',
            '-i', 'full',
            '-f', 'text',
            '-F', $self->{'config'}->{'BIND_DB_FORMAT'},
            '-s', 'relative',
            '-o', "$self->{'config'}->{'BIND_DB_MASTER_DIR'}/$zonename.db",
            $zonename,
            $filename
        ],
        \ my $stdout,
        \ my $stderr
    );
    debug( $stdout ) if $stdout;
    error( sprintf( "Couldn't compile the %s zone: %s", $zonename, $stderr || 'Unknown error' )) if $rs;
    $rs;
}

=item _bkpConfFile($cfgFile)

 Backup configuration file

 Param string $cfgFile Configuration file path
 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
    my ($self, $cfgFile) = @_;

    return 0 unless -f $cfgFile;

    my $file = iMSCP::File->new( filename => $cfgFile );
    my $filename = basename( $cfgFile );

    unless ( -f "$self->{'bkpDir'}/$filename.system" ) {
        my $rs = $file->copyFile( "$self->{'bkpDir'}/$filename.system", { preserve => 'no' } );
        return $rs if $rs;
    } else {
        my $rs = $file->copyFile( "$self->{'bkpDir'}/$filename." . time, { preserve => 'no' } );
        return $rs if $rs;
    }

    0;
}

=item _makeDirs( )

 Create directories

 Return int 0 on success, other on failure

=cut

sub _makeDirs
{
    my ($self) = @_;

    my @directories = (
        [
            $self->{'config'}->{'BIND_DB_MASTER_DIR'},
            $self->{'config'}->{'BIND_USER'},
            $self->{'config'}->{'BIND_GROUP'},
            02750
        ],
        [
            $self->{'config'}->{'BIND_DB_SLAVE_DIR'},
            $self->{'config'}->{'BIND_USER'},
            $self->{'config'}->{'BIND_GROUP'},
            02750
        ]
    );

    my $rs = $self->{'eventManager'}->trigger( 'beforeBind9dMakeDirs', \@directories );
    return $rs if $rs;

    eval {
        for my $directory( @directories ) {
            iMSCP::Dir->new( dirname => $directory->[0] )->make( {
                user  => $directory->[1],
                group => $directory->[2],
                mode  => $directory->[3]
            } );
        }

        iMSCP::Dir->new( dirname => $self->{'config'}->{'BIND_DB_MASTER_DIR'} )->clear();

        if ( $self->{'config'}->{'BIND_MODE'} ne 'slave' ) {
            iMSCP::Dir->new( dirname => $self->{'config'}->{'BIND_DB_SLAVE_DIR'} )->clear();
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterBind9MakeDirs', \@directories );
}

=item _buildConf( )

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
    my ($self) = @_;

    # default conffile (Debian/Ubuntu specific)
    if ( $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'} && -f $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'} ) {
        my $tplName = basename( $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'} );
        my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'bind9', $tplName, \ my $tplContent, {} );
        return $rs if $rs;

        unless ( defined $tplContent ) {
            $tplContent = iMSCP::File->new( filename => $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'} )->get();
            unless ( defined $tplContent ) {
                error( sprintf( "Couldn't read the %s file", $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'} ));
                return 1;
            }
        }

        # Enable/disable local DNS resolver
        $tplContent =~ s/RESOLVCONF=(?:no|yes)/RESOLVCONF=$self->{'config'}->{'LOCAL_DNS_RESOLVER'}/i;

        # Fix for #IP-1333
        # See also: https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=744304
        my $serviceMngr = iMSCP::Service->getInstance();
        if ( $serviceMngr->isSystemd() ) {
            if ( $self->{'config'}->{'LOCAL_DNS_RESOLVER'} eq 'yes' ) {
                # Service will be started automatically when Bind9 will be restarted
                $serviceMngr->enable( 'bind9-resolvconf' );
            } else {
                $serviceMngr->stop( 'bind9-resolvconf' );
                $serviceMngr->disable( 'bind9-resolvconf' );
            }
        }

        # Enable/disable IPV6 support
        if ( $tplContent =~ /OPTIONS="(.*)"/ ) {
            ( my $options = $1 ) =~ s/\s*-[46]\s*//g;
            $options = '-4 ' . $options unless $self->{'config'}->{'BIND_IPV6'} eq 'yes';
            $tplContent =~ s/OPTIONS=".*"/OPTIONS="$options"/;
        }

        $rs = $self->{'eventManager'}->trigger( 'afterBind9BuildConf', \$tplContent, $tplName );
        return $rs if $rs;

        my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$tplName" );
        $file->set( $tplContent );
        $rs = $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
        $rs ||= $file->mode( 0644 );
        $rs ||= $file->copyFile( $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'} );
        return $rs if $rs;
    }

    # option conffile
    if ( $self->{'config'}->{'BIND_OPTIONS_CONF_FILE'} ) {
        my $tplName = basename( $self->{'config'}->{'BIND_OPTIONS_CONF_FILE'} );
        my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'bind9', $tplName, \ my $tplContent, {} );
        return $rs if $rs;

        unless ( defined $tplContent ) {
            $tplContent = iMSCP::File->new( filename => "$self->{'cfgDir'}/$tplName" )->get();
            unless ( defined $tplContent ) {
                error( sprintf( "Couldn't read the %s file", "$self->{'cfgDir'}/$tplName" ));
                return 1;
            }
        }

        if ( $self->{'config'}->{'BIND_IPV6'} eq 'no' ) {
            $tplContent =~ s/listen-on-v6\s+\{\s+any;\s+\};/listen-on-v6 { none; };/;
        }

        my $namedVersion = $self->_getVersion();
        unless ( defined $namedVersion ) {
            error( "Couldn't retrieve named (Bind9) version" );
            return 1;
        }

        if ( version->parse( $namedVersion ) >= version->parse( '9.9.3' ) ) {
            $tplContent =~ s%//\s+(check-spf\s+ignore;)%$1%;
        }

        $rs = $self->{'eventManager'}->trigger( 'afterBind9BuildConf', \$tplContent, $tplName );
        return $rs if $rs;

        local $UMASK = 027;
        my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$tplName" );
        $file->set( $tplContent );
        $rs = $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'} );
        $rs ||= $file->mode( 0640 );
        $rs ||= $file->copyFile( $self->{'config'}->{'BIND_OPTIONS_CONF_FILE'} );
        return $rs if $rs;
    }

    # master conffile
    if ( $self->{'config'}->{'BIND_CONF_FILE'} ) {
        my $tplName = basename( $self->{'config'}->{'BIND_CONF_FILE'} );
        my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'bind9', $tplName, \ my $tplContent, {} );
        return $rs if $rs;

        unless ( defined $tplContent ) {
            $tplContent = iMSCP::File->new( filename => "$self->{'cfgDir'}/$tplName" )->get();
            unless ( defined $tplContent ) {
                error( sprintf( "Couldn't read the %s file", "$self->{'cfgDir'}/$tplName" ));
                return 1;
            }
        }

        unless ( -f "$self->{'config'}->{'BIND_CONF_DIR'}/bind.keys" ) {
            $tplContent =~ s%include\s+\Q"$self->{'config'}->{'BIND_CONF_DIR'}\E/bind.keys";\n%%;
        }

        $rs = $self->{'eventManager'}->trigger( 'afterBind9BuildConf', \$tplContent, $tplName );
        return $rs if $rs;

        local $UMASK = 027;
        my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$tplName" );
        $file->set( $tplContent );
        $rs = $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'} );
        $rs ||= $file->mode( 0640 );
        $rs ||= $file->copyFile( $self->{'config'}->{'BIND_CONF_FILE'} );
        return $rs if $rs;
    }

    # local conffile
    if ( $self->{'config'}->{'BIND_LOCAL_CONF_FILE'} ) {
        my $tplName = basename( $self->{'config'}->{'BIND_LOCAL_CONF_FILE'} );
        my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'bind9', $tplName, \ my $tplContent, {} );
        return $rs if $rs;

        unless ( defined $tplContent ) {
            $tplContent = iMSCP::File->new( filename => "$self->{'cfgDir'}/$tplName" )->get();
            unless ( defined $tplContent ) {
                error( sprintf( "Couldn't read the %s file", "$self->{'cfgDir'}/$tplName" ));
                return 1;
            }
        }

        $rs = $self->{'eventManager'}->trigger( 'afterBind9BuildConf', \$tplContent, $tplName );
        return $rs if $rs;

        local $UMASK = 027;
        my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$tplName" );
        $file->set( $tplContent );
        $rs = $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'} );
        $rs ||= $file->mode( 0640 );
        $rs ||= $file->copyFile( $self->{'config'}->{'BIND_LOCAL_CONF_FILE'} );
        return $rs if $rs;
    }

    0;
}

=item _checkIps(@ips)

 Check IP addresses

 Param list @ips List of IP addresses to check
 Return bool TRUE if all IPs are valid, FALSE otherwise

=cut

sub _checkIps
{
    my (undef, @ips) = @_;

    my $net = iMSCP::Net->getInstance();

    for my $ipAddr( @ips ) {
        return 0 unless $net->isValidAddr( $ipAddr ) && $net->getAddrType( $ipAddr ) =~ /^(?:PRIVATE|UNIQUE-LOCAL-UNICAST|PUBLIC|GLOBAL-UNICAST)$/;
    }

    1;
}

=item _getVersion( )

 Get named version

 Return string on success, undef on failure

=cut

sub _getVersion
{
    my ($self) = @_;

    my $rs = execute( "$self->{'config'}->{'NAMED_BNAME'} -v", \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;

    unless ( $rs ) {
        return $1 if $stdout =~ /^BIND\s+([0-9.]+)/;
    }

    undef;
}

=item _cleanup( )

 Process cleanup tasks

 Return int 0 on success, other on failure

=cut

sub _cleanup
{
    my ($self) = @_;

    if ( -f "$self->{'cfgDir'}/bind.old.data" ) {
        my $rs = iMSCP::File->new( filename => "$self->{'cfgDir'}/bind.old.data" )->delFile();
        return $rs if $rs;
    }

    if ( iMSCP::ProgramFinder::find( 'resolvconf' ) ) {
        my $rs = execute( "resolvconf -d lo.imscp", \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
    }

    eval { iMSCP::Dir->new( dirname => $self->{'config'}->{'BIND_DB_ROOT_DIR'} )->clear( undef, qr/\.db$/ ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _removeConfig( )

 Remove configuration

 Return int 0 on success, other on failure

=cut

sub _removeConfig
{
    my ($self) = @_;

    if ( exists $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'} ) {
        my $dirname = dirname( $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'} );

        if ( -d $dirname ) {
            my $filename = basename( $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'} );

            if ( -f "$self->{'bkpDir'}/$filename.system" ) {
                my $rs = iMSCP::File->new( filename => "$self->{'bkpDir'}/$filename.system" )->copyFile(
                    $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'}, { preserve => 'no' }
                );
                return $rs if $rs;

                my $file = iMSCP::File->new( filename => $self->{'config'}->{'BIND_CONF_DEFAULT_FILE'} );
                $rs = $file->mode( 0640 );
                $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'} );
                return $rs if $rs;
            }
        }
    }

    for ( 'BIND_CONF_FILE', 'BIND_LOCAL_CONF_FILE', 'BIND_OPTIONS_CONF_FILE' ) {
        next unless exists $self->{'config'}->{$_};

        my $dirname = dirname( $self->{'config'}->{$_} );
        next unless -d $dirname;

        my $filename = basename( $self->{'config'}->{$_} );
        next unless -f "$self->{'bkpDir'}/$filename.system";

        my $rs = iMSCP::File->new( filename => "$self->{'bkpDir'}/$filename.system" )->copyFile(
            $self->{'config'}->{$_}, { preserve => 'no' }
        );
        return $rs if $rs;

        my $file = iMSCP::File->new( filename => $self->{'config'}->{$_} );
        $rs = $file->mode( 0640 );
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'} );
        return $rs if $rs;
    }

    eval {
        iMSCP::Dir->new( dirname => $self->{'config'}->{'BIND_DB_MASTER_DIR'} )->remove();
        iMSCP::Dir->new( dirname => $self->{'config'}->{'BIND_DB_SLAVE_DIR'} )->remove();
        iMSCP::Dir->new( dirname => $self->{'wrkDir'} )->clear();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
