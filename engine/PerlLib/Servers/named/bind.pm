=head1 NAME

 Servers::named::bind - i-MSCP Bind9 Server implementation

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package Servers::named::bind;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Servers::named::bind::installer Servers::named::bind::uninstaller /;
use File::Basename 'fileparse';
use File::Spec;
use File::Temp;
use iMSCP::Boolean;
use iMSCP::Debug qw/ getMessageByType debug error /;
use iMSCP::Config;
use iMSCP::EventManager;
use iMSCP::Execute qw/ escapeShell execute /;
use iMSCP::File;
use iMSCP::ProgramFinder;
use iMSCP::TemplateParser qw/ process replaceBloc getBloc /;
use iMSCP::Net;
use iMSCP::Rights 'setRights';
use iMSCP::Service;
use iMSCP::Umask '$UMASK';
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Bind9 Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%events )

 Register setup event listeners

 Param iMSCP::EventManager \%events
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( undef, $events ) = @_;

    Servers::named::bind::installer->getInstance()->registerSetupListeners(
        $events
    );
}

=item preinstall( )

 Pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeNamedPreInstall', 'bind' );
    $rs ||= Servers::named::bind::installer->getInstance()->preinstall();
    $rs ||= $self->{'events'}->trigger( 'afterNamedPreInstall', 'bind' );
}

=item install( )

 Installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeNamedInstall', 'bind' );
    $rs ||= Servers::named::bind::installer->getInstance()->install();
    $rs ||= $self->{'events'}->trigger( 'afterNamedInstall', 'bind' );
}

=item postinstall( )

 Post-installation tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeNamedPostInstall' );
    $rs ||= Servers::named::bind::installer->getInstance()->postinstall();
    $rs ||= $self->{'events'}->trigger( 'afterNamedPostInstall' );
}

=item uninstall( )

 Uninstallation tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeNamedUninstall', 'bind' );
    $rs ||= Servers::named::bind::uninstaller->getInstance()->uninstall();
    return $rs if $rs;

    if ( -x $self->{'config'}->{'NAMED'} ) {
        $rs = $self->restart();
        return $rs if $rs;
    }

    $self->{'events'}->trigger( 'afterNamedUninstall', 'bind' );
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ( $self ) = @_;

    my $rs = setRights( $self->{'config'}->{'BIND_CONF_DIR'}, {
        user      => $::imscpConfig{'ROOT_USER'},
        group     => $self->{'config'}->{'BIND_GROUP'},
        dirmode   => '2750',
        filemode  => '0640',
        recursive => TRUE
    } );
    return $rs if $rs;

    if ( -f "$self->{'config'}->{'BIND_CONF_DIR'}/rndc.key" ) {
        $rs ||= setRights( "$self->{'config'}->{'BIND_CONF_DIR'}/rndc.key", {
            user  => $self->{'config'}->{'BIND_USER'},
            group => $self->{'config'}->{'BIND_GROUP'},
        } );
        return $rs if $rs;
    }

    $rs = setRights( $self->{'config'}->{'BIND_DB_ROOT_DIR'}, {
        user      => $::imscpConfig{'ROOT_USER'},
        group     => $self->{'config'}->{'BIND_GROUP'},
        dirmode   => '2770',
        filemode  => '0640',
        recursive => TRUE
    } );
    $rs ||= setRights( $self->{'wrkDir'}, {
        user      => $::imscpConfig{'ROOT_USER'},
        group     => $self->{'config'}->{'BIND_GROUP'},
        dirmode   => '2770',
        filemode  => '0640',
        recursive => TRUE
    } );
}

=item addDmn( \%data )

 Domain addition tasks

 Param hashref \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ( $self, $data ) = @_;

    # Never process the same zone twice
    return 0 if exists $self->{'seen_zones'}->{$data->{'DOMAIN_NAME'}};

    my $rs = $self->{'events'}->trigger( 'beforeNamedAddDmn', $data );
    $rs ||= $self->_addDmnConfig( $data );
    return $rs if $rs;

    if ( $self->{'config'}->{'BIND_MODE'} eq 'master' ) {
        $rs = $self->_addDmnDb( $data );
        return $rs if $rs;
    }

    $self->{'seen_zones'}->{$data->{'DOMAIN_NAME'}} = TRUE;
    $self->{'reload'} = TRUE;
    $self->{'events'}->trigger( 'afterNamedAddDmn', $data );
}

=item disableDmn( \%data )

 Domain deactivation tasks

 On a reconfiguration, we need make sure that DNS record are added, even for
 disabled domains.

 Param hashref \%data Domain data
 Return int 0 on success, other on failure

=cut

sub disableDmn
{
    my ( $self, $data ) = @_;

    return 0 unless defined $::execmode && $::execmode eq 'setup';

    my $rs = $self->{'events'}->trigger( 'beforeNamedDisableDmn', $data );
    $rs ||= $self->addDmn( $data );
    $rs ||= $self->{'events'}->trigger( 'afterNamedDisableDmn', $data );
}

=item deleteDmn( \%data )

 Domain deletion tasks

 Param hashref \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ( $self, $data ) = @_;

    return 0 unless $data->{'FORCE_DELETION'}
        || $data->{'PARENT_DOMAIN_NAME'} ne $::imscpConfig{'BASE_SERVER_VHOST'};

    my $rs = $self->{'events'}->trigger( 'beforeNamedDelDmn', $data );
    $rs ||= $self->_deleteDmnConfig( $data );
    return $rs if $rs;

    if ( $self->{'config'}->{'BIND_MODE'} eq 'master' ) {
        for my $file (
            "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db",
            "$self->{'config'}->{'BIND_DB_MASTER_DIR'}/$data->{'DOMAIN_NAME'}.db"
        ) {
            next unless -f $file;
            $rs = iMSCP::File->new( filename => $file )->delFile();
            return $rs if $rs;
        }
    }

    $self->{'reload'} = TRUE;
    $self->{'events'}->trigger( 'afterNamedDelDmn', $data );
}

=item addSub( \%data )

 Subdomain addition tasks

 Param hashref \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ( $self, $data ) = @_;

    return 0 unless $self->{'config'}->{'BIND_MODE'} eq 'master';

    my $file = iMSCP::File->new(
        filename => "$self->{'wrkDir'}/$data->{'PARENT_DOMAIN_NAME'}.db"
    );
    return 1 unless defined( my $fileC = $file->getAsRef());

    my $rs = $self->{'events'}->trigger(
        'onLoadTemplate', 'bind', 'db_sub.tpl', \my $subEntry, $data
    );
    return $rs if $rs;

    unless ( defined $subEntry ) {
        return 1 unless defined(
            $subEntry = iMSCP::File->new(
                filename => "$self->{'tplDir'}/db_sub.tpl"
            )->get()
        );
    }

    $rs = $self->_updateSerial(
        $data->{'PARENT_DOMAIN_NAME'}, $fileC, $fileC
    ) unless exists $self->{'serials'}->{$data->{'PARENT_DOMAIN_NAME'}};
    $rs ||= $self->{'events'}->trigger( 'beforeNamedAddSub', $fileC, \$subEntry, $data );
    return $rs if $rs;

    my $net = iMSCP::Net->getInstance();

    if ( $data->{'MAIL_ENABLED'} ) {
        $subEntry = replaceBloc(
            "; subdomain MAIL records BEGIN\n",
            "; subdomain MAIL records ENDING\n",
            process(
                {
                    BASE_SERVER_IP_TYPE => $net->getAddrVersion(
                        $data->{'BASE_SERVER_PUBLIC_IP'}
                    ) eq 'ipv4' ? 'A' : 'AAAA',
                    BASE_SERVER_IP      => $data->{'BASE_SERVER_PUBLIC_IP'},
                    DOMAIN_NAME         => $data->{'PARENT_DOMAIN_NAME'}
                },
                getBloc(
                    "; subdomain MAIL records BEGIN\n",
                    "; subdomain MAIL records ENDING\n",
                    $subEntry
                )
            ),
            $subEntry
        );
    } else {
        $subEntry = replaceBloc(
            "; subdomain MAIL records BEGIN\n",
            "; subdomain MAIL records ENDING\n",
            '',
            $subEntry
        );
    }

    if ( defined $data->{'OPTIONAL_RECORDS'}
        && !$data->{'OPTIONAL_RECORDS'}
    ) {
        $subEntry = replaceBloc(
            "; subdomain OPTIONAL records BEGIN\n",
            "; subdomain OPTIONAL records ENDING\n",
            '',
            $subEntry
        );
    }

    my $domainIP = $net->isRoutableAddr( $data->{'DOMAIN_IP'} )
        ? $data->{'DOMAIN_IP'}
        : $data->{'BASE_SERVER_PUBLIC_IP'};

    $subEntry = process(
        {
            SUBDOMAIN_NAME => $data->{'DOMAIN_NAME'},
            IP_TYPE        => $net->getAddrVersion( $domainIP ) eq 'ipv4'
                ? 'A' : 'AAAA',
            DOMAIN_IP      => $domainIP
        },
        $subEntry
    );
    ${ $fileC } = replaceBloc(
        "; subdomain [$data->{'DOMAIN_NAME'}] records BEGIN\n",
        "; subdomain [$data->{'DOMAIN_NAME'}] records ENDING\n",
        '',
        ${ $fileC }
    );
    ${ $fileC } = replaceBloc(
        "; subdomain [{SUBDOMAIN_NAME}] records BEGIN\n",
        "; subdomain [{SUBDOMAIN_NAME}] records ENDING\n",
        $subEntry,
        ${ $fileC },
        TRUE
    );
    $rs = $self->{'events'}->trigger( 'afterNamedAddSub', $fileC, $data );
    $rs ||= $file->save();
    $rs ||= $self->_compileZone(
        $data->{'PARENT_DOMAIN_NAME'}, $file->{'filename'}
    );
    $self->{'reload'} = TRUE unless $rs;
    $rs;
}

=item disableSub( \%data )

 Subdomain deactivation tasks

 On a reconfiguration, we need make sure that DNS record are added, even for
 disabled subdomains.

 Param hashref \%data Domain data
 Return int 0 on success, other on failure

=cut

sub disableSub
{
    my ( $self, $data ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeNamedDisableSub', $data );
    $rs ||= $self->addSub( $data );
    $rs ||= $self->{'events'}->trigger( 'afterNamedDisableSub', $data );
}

=item deleteSub( \%data )

 Subdomain deletion tasks

 Param hashref \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
    my ( $self, $data ) = @_;

    return 0 unless $self->{'config'}->{'BIND_MODE'} eq 'master';

    my $file = iMSCP::File->new(
        filename => "$self->{'wrkDir'}/$data->{'PARENT_DOMAIN_NAME'}.db"
    );
    return 1 unless defined( my $fileC = $file->getAsRef());

    unless ( exists $self->{'serials'}->{$data->{'PARENT_DOMAIN_NAME'}} ) {
        my $rs = $self->_updateSerial(
            $data->{'PARENT_DOMAIN_NAME'}, $fileC, $fileC
        );
        return $rs if $rs;
    }

    my $rs = $self->{'events'}->trigger( 'beforeNamedDelSub', $fileC, $data );
    return $rs if $rs;

    ${ $fileC } = replaceBloc(
        "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
        "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
        '',
        ${ $fileC }
    );
    $rs = $self->{'events'}->trigger( 'afterNamedDelSub', \$fileC, $data );
    $rs ||= $file->save();
    $rs ||= $self->_compileZone( $data->{'PARENT_DOMAIN_NAME'}, $file->{'filename'} );
    $self->{'reload'} = TRUE unless $rs;
    $rs;
}

=item addCustomDNS( \%data )

 Custom DNS addition tasks

 Param hashref \%data Custom DNS data
 Return int 0 on success, other on failure

=cut

sub addCustomDNS
{
    my ( $self, $data ) = @_;

    return 0 unless $self->{'config'}->{'BIND_MODE'} eq 'master';

    my $file = iMSCP::File->new(
        filename => "$self->{'wrkDir'}/$data->{'DNS_ZONE'}.db"
    );
    return 1 unless defined( my $fileC = $file->getAsRef());

    unless ( exists $self->{'serials'}->{$data->{'DNS_ZONE'}} ) {
        my $rs = $self->_updateSerial( $data->{'DNS_ZONE'}, $fileC, $fileC );
        # Even though, we do not want operate directly on the intermediate zone
        # file (see below), wee still need save changes regarding SOA rr.
        $rs ||= $file->save();
        return $rs if $rs;
    }

    # We don't want operate directly on the intermediate zone file as this
    # would remove default RRs which are overridden by custom DNS RRs, and
    # this would make difficult to restore them later on without triggering
    # a full reconfiguration. Thus, we copy the content of te intermediate zone
    # file in a temporary file and we work on that file instead. Doing this
    # means that custom DNS records won't never be added in intermediate zone
    # files.
    my $fileTMP = File::Temp->new( UNLINK => false );
    print $fileTMP ${ $fileC };
    $fileTMP->close();
    $file = iMSCP::File->new( filename => $fileTMP->filename());
    return 1 unless defined( $fileC = $file->getAsRef());

    my $rs = $self->{'events'}->trigger(
        'beforeNamedAddCustomDNS', $fileC, $data
    );
    return $rs if $rs;

    my $fh;
    unless ( open( $fh, '<', $fileC ) ) {
        error( sprintf( "Couldn't open in-memory file handle: %s", $! ));
        return 1;
    }

    my $defaultRRs = '';
    my $origin = $data->{'DNS_ZONE'} . '.';

    ENTRY: while ( my $entry = <$fh> ) {
        if ( $entry =~ /^\$ORIGIN\s+([^\s]+)/
            || index( $entry, '$' ) == 0
            || index( $entry, ';' ) != -1
            || index( $entry, ')' ) == 0
        ) {
            $origin = $1 if defined $1;
            $defaultRRs .= $entry;
            next ENTRY;
        }

        # Skip default DNS RR which are overridden by a custom DNS RR
        if ( @{ $data->{'DNS_RR'} } ) {
            # Process $ORIGIN substitutions
            $entry =~ s/\@/$origin/g;
            # Add $ORIGIN to unqualified names
            $entry =~ s/^(\S+?[^\s.])\s+/$1.$origin\t/;

            for my $rr ( @{ $data->{'DNS_RR'} } ) {
                # Custom DNS record is one of A, AAAA or CNAME and
                # the default DNS RR name/type is equal to the custom DNS RR name
                next ENTRY if grep ( $_ eq $rr->{'type'}, qw/ A CNAME / )
                    && $entry =~ /^\Q$rr->{'name'}\E(?:\s+\d+)?\s+$rr->{'class'}\s+(A|CNAME)\s/;
                next ENTRY if grep ( $_ eq $rr->{'type'}, qw/ AAAA CNAME / )
                    && $entry =~ /^\Q$rr->{'name'}\E(?:\s+\d+)?\s+$rr->{'class'}\s+(AAAA|CNAME)\s/;

                # Evaluates next custom DNS RR if there is no name/class/type
                # matching
                next if $entry !~ /^\Q$rr->{'name'}\E(?:\s+\d+)?\s+$rr->{'class'}\s+$rr->{'type'}\s/;

                # Skips the default DNS RR if there is a name/class/type
                # matching, and if the type is other than TXT.
                next ENTRY if $rr->{'type'} ne 'TXT';

                # Skips default DNS RR if there is a name/class/type matching
                # and if the RDATA part of both DNS RR represent one of
                # following DNS RRs: spf, dkim, adsp
                next ENTRY if (
                    index( $entry, '"v=spf1' ) != -1
                        && index( $rr->{'rdata'}, '"v=spf1' ) != -1
                ) || (
                    index( $entry, '"v=DKIM1;' ) != -1
                        && index( $rr->{'rdata'}, '"v=DKIM1;' ) != -1
                ) || (
                    index( $entry, '"dkim=' ) != -1
                        && index( $rr->{'rdata'}, '"dkim=' ) != -1
                )
            }
        }

        $defaultRRs .= $entry;
    }

    close( $fh );
    chomp( $defaultRRs );

    ${ $fileC } = <<"EOF";
$defaultRRs
; custom DNS records BEGIN
@{ [
    join "\n", map (
        "$_->{'name'}\t$_->{'ttl'}\t$_->{'class'}\t$_->{'type'}\t$_->{'rdata'}", 
        @{ $data->{'DNS_RR'} }
    )
] }
; custom DNS records ENDING
EOF
    undef $defaultRRs;
    $rs = $self->{'events'}->trigger(
        'afterNamedAddCustomDNS', $fileC, $data
    );
    $rs ||= $file->save();
    $rs ||= $self->_compileZone( $data->{'DNS_ZONE'}, $fileTMP->filename());
    $self->{'reload'} = TRUE unless $rs;
    $rs;
}

=item restart( )

 Restart Bind9

 Return int 0 on success, other on failure

=cut

sub restart
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeNamedRestart' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart(
        $self->{'config'}->{'NAMED_SERVICE'}
    ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->trigger( 'afterNamedRestart' );
}

=item reload( )

 Reload Bind9

 Return int 0 on success, other on failure

=cut

sub reload
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeNamedReload' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->reload(
        $self->{'config'}->{'NAMED_SERVICE'}
    ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->trigger( 'afterNamedReload' );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::named::bind

=cut

sub _init
{
    my ( $self ) = @_;

    @{ $self }{qw/ restart reload serials seen_zones events /} = (
        FALSE, FALSE, {}, {}, iMSCP::EventManager->getInstance()
    );
    $self->{'cfgDir'} = "$::imscpConfig{'CONF_DIR'}/bind";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'tplDir'} = "$self->{'cfgDir'}/parts";
    $self->_mergeConfig() if -f "$self->{'cfgDir'}/bind.data.dist";
    tie %{ $self->{'config'} },
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/bind.data",
        readonly    => !( defined $::execmode && $::execmode eq 'setup' ),
        nodeferring => ( defined $::execmode && $::execmode eq 'setup' );
    $self;
}

=item _mergeConfig( )

 Merge distribution configuration with production configuration

 Die on failure

=cut

sub _mergeConfig
{
    my ( $self ) = @_;

    if ( -f "$self->{'cfgDir'}/bind.data" ) {
        tie my %newConfig, 'iMSCP::Config',
            fileName => "$self->{'cfgDir'}/bind.data.dist";
        tie my %oldConfig, 'iMSCP::Config',
            fileName => "$self->{'cfgDir'}/bind.data", readonly => TRUE;

        debug( 'Merging old configuration with new configuration...' );

        while ( my ( $key, $value ) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }

        %{ $self->{'oldConfig'} } = ( %oldConfig );

        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new(
        filename => "$self->{'cfgDir'}/bind.data.dist"
    )->moveFile( "$self->{'cfgDir'}/bind.data" ) == 0 or die( getMessageByType(
        'error', { amount => 1, remove => TRUE }
    ) || 'Unknown error' );
}

=item _addDmnConfig( \%data )

 Add domain DNS configuration

 Param hashref \%data Data as provided by the Domain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _addDmnConfig
{
    my ( $self, $data ) = @_;

    my ( $name, $dir ) = fileparse(
        $self->{'config'}->{'BIND_LOCAL_CONF_FILE'}
            || $self->{'config'}->{'BIND_CONF_FILE'}
    );
    my $file = iMSCP::File->new(
        filename => File::Spec->catfile( $self->{'wrkDir'}, $name )
    );
    return 1 unless defined( my $fileC = $file->getAsRef());

    my $tpl = "cfg_$self->{'config'}->{'BIND_MODE'}.tpl";
    my $rs = $self->{'events'}->trigger(
        'onLoadTemplate', 'bind', $tpl, \my $tplFileC, $data
    );
    return $rs if $rs;

    unless ( defined $tplFileC ) {
        return 1 unless defined(
            $tplFileC = iMSCP::File->new(
                filename => File::Spec->catfile( $self->{'tplDir'}, $tpl )
            )->get()
        );
    }

    $rs = $self->{'events'}->trigger(
        'beforeNamedAddDmnConfig', $fileC, \$tplFileC, $data
    );
    return $rs if $rs;

    my $tags = {
        BIND_DB_FORMAT => $self->{'config'}->{'BIND_DB_FORMAT'} =~ s/=\d//r,
        DOMAIN_NAME    => $data->{'DOMAIN_NAME'}
    };

    if ( $self->{'config'}->{'BIND_MODE'} eq 'master' ) {
        if ( $self->{'config'}->{'SECONDARY_DNS'} ne 'no' ) {
            $tags->{'SECONDARY_DNS'} = join(
                '; ', split( /(?:[;,]| )/, $self->{'config'}->{'SECONDARY_DNS'} )
            ) . '; localhost;';
        } else {
            $tags->{'SECONDARY_DNS'} = 'localhost;';
        }
    } else {
        $tags->{'PRIMARY_DNS'} = join(
            '; ', split( /(?:[;,]| )/, $self->{'config'}->{'PRIMARY_DNS'} )
        ) . ';';
    }

    $tplFileC = "// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n"
        . process( $tags, $tplFileC )
        . "// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n";
    ${ $fileC } = replaceBloc(
        "// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
        "// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n",
        '',
        ${ $fileC }
    );
    ${ $fileC } = replaceBloc(
        "// imscp [{ENTRY_ID}] entry BEGIN\n",
        "// imscp [{ENTRY_ID}] entry ENDING\n",
        $tplFileC,
        ${ $fileC },
        TRUE
    );
    $rs = $self->{'events'}->trigger(
        'afterNamedAddDmnConfig', $fileC, $data
    );
    $rs ||= $file->save();
    $rs ||= $file->copyFile( File::Spec->catfile( $dir, $name ));
}

=item _deleteDmnConfig( \%data )

 Delete domain DNS configuration

 Param hashref \%data Data as provided by the Domain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _deleteDmnConfig
{
    my ( $self, $data ) = @_;

    my ( $name, $dir ) = fileparse(
        $self->{'config'}->{'BIND_LOCAL_CONF_FILE'}
            || $self->{'config'}->{'BIND_CONF_FILE'}
    );

    my $file = iMSCP::File->new(
        filename => File::Spec->catfile( $self->{'wrkDir'}, $name )
    );
    return 1 unless defined( my $fileC = $file->getAsRef());

    my $rs = $self->{'events'}->trigger(
        'beforeNamedDelDmnConfig', $fileC, $data
    );
    return $rs if $rs;

    ${ $fileC } = replaceBloc(
        "// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
        "// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n",
        '',
        ${ $fileC }
    );
    $rs = $self->{'events'}->trigger(
        'afterNamedDelDmnConfig', $fileC, $data
    );
    $rs ||= $file->save();
    $rs ||= $file->copyFile( File::Spec->catfile( $dir, $name ));
}

=item _addDmnDb( \%data )

 Add domain DNS zone file

 Param hashref \%data Data as provided by the Domain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _addDmnDb
{
    my ( $self, $data ) = @_;

    my $file = iMSCP::File->new(
        filename => "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db"
    );
    my $fileC;

    if ( -f $file->{'filename'} && !defined( $fileC = $file->getAsRef()) ) {
        return 1;
    }

    my $rs = $self->{'events'}->trigger(
        'onLoadTemplate', 'bind', 'db.tpl', \my $tplFileC, $data
    );
    return $rs if $rs;

    unless ( defined $tplFileC ) {
        return 1 unless defined(
            $tplFileC = iMSCP::File->new(
                filename => "$self->{'tplDir'}/db.tpl"
            )->get()
        );
    }

    $rs = $self->_updateSerial( $data->{'DOMAIN_NAME'}, \$tplFileC, $fileC );
    $rs ||= $self->{'events'}->trigger( 'beforeNamedAddDmnDb', \$tplFileC, $data );
    return $rs if $rs;

    my $nsRRb = getBloc(
        "; domain NS records BEGIN\n",
        "; domain NS records ENDING\n",
        $tplFileC
    );
    my $gRRb = getBloc(
        "; domain NS GLUE records BEGIN\n",
        "; domain NS GLUE records ENDING\n",
        $tplFileC
    );
    my $net = iMSCP::Net->getInstance();
    my $domainIP = $net->isRoutableAddr( $data->{'DOMAIN_IP'} )
        ? $data->{'DOMAIN_IP'}
        : $data->{'BASE_SERVER_PUBLIC_IP'};

    unless ( $nsRRb eq '' && $gRRb eq '' ) {
        my @nsIPs = (
            $domainIP,
            ( $self->{'config'}->{'SECONDARY_DNS'} eq 'no'
                ? () : split /(?:[;,]| )/, $self->{'config'}->{'SECONDARY_DNS'}
            )
        );
        my ( $nsRR, $gRR ) = ( '', '' );

        for my $ipAddrType ( qw/ ipv4 ipv6 / ) {
            my $nsNumber = 1;
            for my $ipAddr ( @nsIPs ) {
                next unless $net->getAddrVersion( $ipAddr ) eq $ipAddrType;
                $nsRR .= process(
                    { NS_NAME => 'ns' . $nsNumber }, $nsRRb
                ) if $nsRRb ne '';
                $gRR .= process(
                    {
                        NS_NAME    => 'ns' . $nsNumber,
                        NS_IP_TYPE => $ipAddrType eq 'ipv4' ? 'A' : 'AAAA',
                        NS_IP      => $ipAddr
                    },
                    $gRRb
                ) if $gRRb ne '';
                $nsNumber++;
            }
        }

        $tplFileC = replaceBloc(
            "; domain NS records BEGIN\n",
            "; domain NS records ENDING\n",
            $nsRR,
            $tplFileC
        ) if $nsRRb ne '';

        $tplFileC = replaceBloc(
            "; domain NS GLUE records BEGIN\n",
            "; domain NS GLUE records ENDING\n",
            $gRR,
            $tplFileC
        ) if $gRRb ne '';
    }

    my $mailEntry = '';
    if ( $data->{'MAIL_ENABLED'} ) {
        $mailEntry = process(
            {
                BASE_SERVER_IP_TYPE => $net->getAddrVersion(
                    $data->{'BASE_SERVER_PUBLIC_IP'}
                ) eq 'ipv4' ? 'A' : 'AAAA',
                BASE_SERVER_IP      => $data->{'BASE_SERVER_PUBLIC_IP'}
            },
            getBloc(
                "; domain MAIL records BEGIN\n",
                "; domain MAIL records ENDING\n",
                $tplFileC
            )
        )
    }

    $tplFileC = replaceBloc(
        "; domain MAIL records BEGIN\n",
        "; domain MAIL records ENDING\n",
        $mailEntry,
        $tplFileC
    );

    $tplFileC = process(
        {
            DOMAIN_NAME => $data->{'DOMAIN_NAME'},
            IP_TYPE     => $net->getAddrVersion( $domainIP ) eq 'ipv4'
                ? 'A' : 'AAAA',
            DOMAIN_IP   => $domainIP
        },
        $tplFileC
    );

    $rs = $self->{'events'}->trigger(
        'afterNamedAddDmnDb', \$tplFileC, $data
    );
    return $rs if $rs;

    local $UMASK = 027;
    $rs = $file->set( $tplFileC );
    $rs ||= $file->save();
    $rs ||= $self->_compileZone( $data->{'DOMAIN_NAME'}, $file->{'filename'} );
}

=item _updateSerial( $zone, \$nFileC, \$oFileC )

 Update SOA serial number for the given zone
 
 Note: Format follows RFC 1912 section 2.2 recommendations.

 Param string zone Zone name
 Param scalarref \$nFileC Zone file content
 Param scalarref \$oFileC Old zone file content
 Return int 0 on success, other on failure

=cut

sub _updateSerial
{
    my ( $self, $zone, $nFileC, $oFileC ) = @_;

    $oFileC = $nFileC unless defined ${ $oFileC };

    if ( ${ $oFileC } !~ /^\s+(?:(?<date>\d{8})(?<nn>\d{2})|(?<placeholder>\{TIMESTAMP\}))\s*;[^\n]*\n/m ) {
        error( sprintf(
            "Couldn't update SOA serial number for the %s DNS zone", $zone
        ));
        return 1;
    }

    my %rc = %+;
    my ( $d, $m, $y ) = ( gmtime() )[3 .. 5];
    my $nowDate = sprintf( "%d%02d%02d", $y+1900, $m+1, $d );

    if ( exists $+{'placeholder'} ) {
        $self->{'serials'}->{$zone} = $nowDate . '00';
        ${ $nFileC } = process(
            { TIMESTAMP => $self->{'serials'}->{$zone} }, ${ $nFileC }
        );
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
    ${ $nFileC } =~ s/^(\s+)(?:\d{10}|\{TIMESTAMP\})(\s*;[^\n]*\n)/$1$self->{'serials'}->{$zone}$2/m;
    0;
}

=item _compileZone( $zone, $filename )

 Compiles the given zone
 
 Param string $zone Zone name
 Param string $filename Path to zone filename (zone in text format)
 Return int 0 on success, other on error
 
=cut

sub _compileZone
{
    my ( $self, $zone, $filename ) = @_;

    local $UMASK = 027;
    my $rs = execute(
        [
            $self->{'config'}->{'NAMED_COMPILEZONE'},
            '-f', 'text',
            '-F', $self->{'config'}->{'BIND_DB_FORMAT'},
            '-s', 'relative',
            '-o', "$self->{'config'}->{'BIND_DB_MASTER_DIR'}/$zone.db",
            $zone,
            $filename
        ],
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if !$rs && length $stdout;
    error( sprintf(
        "Couldn't compile the '%s' DNS zone: %s%s", $zone, $stdout, $stderr
    )) if $rs;
    $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
