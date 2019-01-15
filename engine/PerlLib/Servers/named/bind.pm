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
use File::Basename;
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
use Try::Tiny;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Bind9 Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%em )

 Register setup event listeners

 Param iMSCP::EventManager \%em
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( undef, $em ) = @_;

    Servers::named::bind::installer->getInstance()->registerSetupListeners( $em );
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedPreInstall', 'bind' );
    $rs ||= $self->{'eventManager'}->trigger( 'afterNamedPreInstall', 'bind' );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedInstall', 'bind' );
    $rs ||= Servers::named::bind::installer->getInstance()->install();
    $rs ||= $self->{'eventManager'}->trigger( 'afterNamedInstall', 'bind' );
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeNamedPostInstall' );
        return $rs if $rs;

        iMSCP::Service->getInstance()->enable( $self->{'config'}->{'NAMED_SNAME'} );

        $rs = $self->{'eventManager'}->register(
            'beforeSetupRestartServices',
            sub {
                push @{ $_[0] }, [ sub { $self->restart(); }, 'Bind9' ];
                0;
            },
            100
        );
        $rs ||= $self->{'eventManager'}->trigger( 'afterNamedPostInstall' );
    } catch {
        error( $_ );
        1;
    };
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedUninstall', 'bind' );
    $rs ||= Servers::named::bind::uninstaller->getInstance()->uninstall();
    return $rs if $rs;

    if ( iMSCP::ProgramFinder::find( $self->{'config'}->{'NAMED_BNAME'} ) ) {
        $rs = $self->restart();
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterNamedUninstall', 'bind' );
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
        $rs = setRights( "$self->{'config'}->{'BIND_CONF_DIR'}/rndc.key", {
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

 Process addDmn tasks

 Param hashref \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ( $self, $data ) = @_;

    # Never process the same zone twice
    return 0 if $self->{'seen_zones'}->{$data->{'DOMAIN_NAME'}};

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedAddDmn', $data );
    $rs ||= $self->_addDmnConfig( $data );
    return $rs if $rs;

    if ( $self->{'config'}->{'BIND_MODE'} eq 'master' ) {
        $rs = $self->_addDmnDb( $data );
        return $rs if $rs;
    }

    $self->{'seen_zones'}->{$data->{'DOMAIN_NAME'}} = TRUE;
    $self->{'eventManager'}->trigger( 'afterNamedAddDmn', $data );
}

=item disableDmn( \%data )

 Process disableDmn tasks

 When a domain is being disabled, we must ensure that the DNS data are still
 present. This is only needed on setup or full reconfiguration, that is, when
 DNS zone files get fully re-generated.

 Param hashref \%data Domain data
 Return int 0 on success, other on failure

=cut

sub disableDmn
{
    my ( $self, $data ) = @_;

    return 0 unless $::execmode eq 'setup';

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedDisableDmn', $data );
    $rs ||= $self->addDmn( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterNamedDisableDmn', $data );
}

=item deleteDmn( \%data )

 Process deleteDmn tasks

 Param hashref \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ( $self, $data ) = @_;

    return 0 if $data->{'PARENT_DOMAIN_NAME'} eq $::imscpConfig{'BASE_SERVER_VHOST'} && !$data->{'FORCE_DELETION'};

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedDelDmn', $data );
    $rs ||= $self->_deleteDmnConfig( $data );
    return $rs if $rs;

    if ( $self->{'config'}->{'BIND_MODE'} eq 'master' ) {
        for my $file ( "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db", "$self->{'config'}->{'BIND_DB_MASTER_DIR'}/$data->{'DOMAIN_NAME'}.db" ) {
            next unless -f $file;
            $rs = iMSCP::File->new( filename => $file )->delFile();
            return $rs if $rs;
        }
    }

    $self->{'eventManager'}->trigger( 'afterNamedDelDmn', $data );
}

=item addSub( \%data )

 Process addSub tasks

 Param hashref \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ( $self, $data ) = @_;

    return 0 unless $self->{'config'}->{'BIND_MODE'} eq 'master';

    my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$data->{'PARENT_DOMAIN_NAME'}.db" );
    my $fileC = $file->getAsRef();
    return 1 unless defined $fileC;

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'bind', 'db_sub.tpl', \my $subEntry, $data );
    return $rs if $rs;

    unless ( defined $subEntry ) {
        $subEntry = iMSCP::File->new( filename => "$self->{'tplDir'}/db_sub.tpl" )->get();
        return 1 unless defined $subEntry;
    }

    $rs = $self->_updateSerial( $data->{'PARENT_DOMAIN_NAME'}, $fileC, $fileC ) unless $self->{'serials'}->{$data->{'PARENT_DOMAIN_NAME'}};
    $rs ||= $self->{'eventManager'}->trigger( 'beforeNamedAddSub', $fileC, \$subEntry, $data );
    return $rs if $rs;

    my $net = iMSCP::Net->getInstance();

    if ( $data->{'MAIL_ENABLED'} ) {
        $subEntry = replaceBloc(
            "; sub MAIL entry BEGIN\n",
            "; sub MAIL entry ENDING\n",
            process(
                {
                    BASE_SERVER_IP_TYPE => $net->getAddrVersion( $data->{'BASE_SERVER_PUBLIC_IP'} ) eq 'ipv4' ? 'A' : 'AAAA',
                    BASE_SERVER_IP      => $data->{'BASE_SERVER_PUBLIC_IP'},
                    DOMAIN_NAME         => $data->{'PARENT_DOMAIN_NAME'}
                },
                getBloc( "; sub MAIL entry BEGIN\n", "; sub MAIL entry ENDING\n", $subEntry )
            ),
            $subEntry
        );
    } else {
        $subEntry = replaceBloc( "; sub MAIL entry BEGIN\n", "; sub MAIL entry ENDING\n", '', $subEntry );
    }

    $subEntry = replaceBloc( "; sub OPTIONAL entries BEGIN\n", "; sub OPTIONAL entries ENDING\n", '', $subEntry ) unless $data->{'OPTIONAL_ENTRIES'};

    my $domainIP = $net->isRoutableAddr( $data->{'DOMAIN_IP'} ) ? $data->{'DOMAIN_IP'} : $data->{'BASE_SERVER_PUBLIC_IP'};
    $subEntry = process(
        {
            SUBDOMAIN_NAME => $data->{'DOMAIN_NAME'},
            IP_TYPE        => $net->getAddrVersion( $domainIP ) eq 'ipv4' ? 'A' : 'AAAA',
            DOMAIN_IP      => $domainIP
        },
        $subEntry
    );
    ${ $fileC } = replaceBloc( "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n", "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n", '', ${ $fileC } );
    ${ $fileC } = replaceBloc( "; sub [{SUBDOMAIN_NAME}] entry BEGIN\n", "; sub [{SUBDOMAIN_NAME}] entry ENDING\n", $subEntry, ${ $fileC }, TRUE );
    $rs = $self->{'eventManager'}->trigger( 'afterNamedAddSub', $fileC, $data );
    $rs ||= $file->save();
    $rs ||= $self->_compileZone( $data->{'PARENT_DOMAIN_NAME'}, $file->{'filename'} );
}

=item disableSub( \%data )

 Process disableSub tasks

 When a subdomain is being disabled, we must ensure that the DNS data are still
 present. This is only needed on setup or full reconfiguration, that is, when
 DNS zone files get fully re-generated.

 Param hashref \%data Domain data
 Return int 0 on success, other on failure

=cut

sub disableSub
{
    my ( $self, $data ) = @_;

    return 0 unless $::execmode eq 'setup';

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedDisableSub', $data );
    $rs ||= $self->addSub( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterNamedDisableSub', $data );
}

=item deleteSub( \%data )

 Process deleteSub tasks

 Param hashref \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
    my ( $self, $data ) = @_;

    return 0 unless $self->{'config'}->{'BIND_MODE'} eq 'master';

    my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$data->{'PARENT_DOMAIN_NAME'}.db" );
    my $fileC = $file->getAsRef();
    return 1 unless defined $fileC;

    unless ( $self->{'serials'}->{$data->{'PARENT_DOMAIN_NAME'}} ) {
        my $rs = $self->_updateSerial( $data->{'PARENT_DOMAIN_NAME'}, $fileC, $fileC );
        return $rs if $rs;
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedDelSub', $fileC, $data );
    return $rs if $rs;

    ${ $fileC } = replaceBloc( "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n", "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n", '', ${ $fileC } );
    $rs = $self->{'eventManager'}->trigger( 'afterNamedDelSub', \$fileC, $data );
    $rs ||= $file->save();
    $rs ||= $self->_compileZone( $data->{'PARENT_DOMAIN_NAME'}, $file->{'filename'} );
}

=item addCustomDNS( \%data )

 Process addCustomDNS tasks

 Param hashref \%data Custom DNS data
 Return int 0 on success, other on failure

=cut

sub addCustomDNS
{
    my ( $self, $data ) = @_;

    return 0 unless $self->{'config'}->{'BIND_MODE'} eq 'master';

    my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$data->{'ZONE_NAME'}.db" );
    my $fileC = $file->getAsRef();
    return 1 unless defined $fileC;

    unless ( $self->{'serials'}->{$data->{'ZONE_NAME'}} ) {
        my $rs = $self->_updateSerial( $data->{'ZONE_NAME'}, $fileC, $fileC );
        return $rs if $rs;
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedAddCustomDNS', $fileC, $data );
    return $rs if $rs;

    my $fh;
    unless ( open( $fh, '<', $fileC ) ) {
        error( sprintf( "Couldn't open in-memory file handle: %s", $! ));
        return 1;
    }

    my $zone = '';
    my $origin = $data->{'ZONE_NAME'};
    LINE:
    while ( my $line = <$fh> ) {
        if ( $line =~ /^\$ORIGIN\s+([^\s;]+)/ || index( $line, '$' ) == 0 || index( $line, ';' ) != -1 || index( $line, ')' ) == 0 ) {
            $origin = $1 if defined $1;
            $zone .= $line;
            next LINE;
        }

        my $defaultRR = $line;

        if ( @{ $data->{'DNS_RECORDS'} } ) {
            # Process $ORIGIN substitutions
            $defaultRR =~ s/\@/$origin/g;
            # Add $ORIGIN to unqualified names
            $defaultRR =~ s/^(\S+?[^\s.])\s+/$1.$origin\t/;
            # Skip default DNS RR if it is overridden by a custom DNS RR
            for my $rr ( @{ $data->{'DNS_RECORDS'} } ) {
                # Evaluates next custom DNS RR if no name/class/type matching
                next if $defaultRR !~ /^$rr->[0](?:\s+\d+[\S]*)?\s+$rr->[2]\s+$rr->[3]\s/;
                # Skips the default DNS RR if there is a name/class/type
                # matching and if the type is other than TXT
                next LINE if $rr->[3] ne 'TXT';
                # Skips default DNS RR if there is a name/class/type matching
                # and if the RDATA part of both DNS RR represent one of
                # following DNS RR: spf, dkim, adsp
                next LINE if ( index( $defaultRR, '"v=spf1' ) != -1 && index( $rr->[4], '"v=spf1' ) == 0 )
                    || ( index( $defaultRR, '"v=DKIM1;' ) != -1 && index( $rr->[4], '"v=DKIM1;' ) == 0 )
                    || ( index( $defaultRR, '"dkim=' ) != -1 && index( $rr->[4], '"dkim=' ) == 0 )
            }
        }

        $zone .= $line;
    }

    close( $fh );
    chomp( $zone );

    ${ $fileC } = <<"EOF";
$zone
@{ [ join( "\n", map { join "\t", @{ $_ } } @{ $data->{'DNS_RECORDS'} } ) ] }
EOF
    undef $zone;
    $rs = $self->{'eventManager'}->trigger( 'afterNamedAddCustomDNS', $fileC, $data );
    return $rs if $rs;

    $file = File::Temp->new();
    print $file ${ $fileC };
    $file->flush();

    $rs = $self->_compileZone( $data->{'ZONE_NAME'}, $file );
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

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeNamedRestart' );
        return $rs if $rs;

        iMSCP::Service->getInstance()->restart( $self->{'config'}->{'NAMED_SNAME'} );
        $self->{'eventManager'}->trigger( 'afterNamedRestart' );
    } catch {
        error( $_ );
        1;
    };
}

=item reload( )

 Reload Bind9

 Return int 0 on success, other on failure

=cut

sub reload
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeNamedReload' );
        return $rs if $rs;

        iMSCP::Service->getInstance()->reload( $self->{'config'}->{'NAMED_SNAME'} );
        $self->{'eventManager'}->trigger( 'afterNamedReload' );
    } catch {
        error( $_ );
        1;
    };
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

    @{ $self }{qw/ restart reload serials seen_zones eventManager /} = ( FALSE, FALSE, {}, {}, iMSCP::EventManager->getInstance() );
    $self->{'cfgDir'} = "$::imscpConfig{'CONF_DIR'}/bind";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'tplDir'} = "$self->{'cfgDir'}/parts";
    $self->_mergeConfig() if -f "$self->{'cfgDir'}/bind.data.dist";
    tie %{ $self->{'config'} },
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/bind.data",
        readonly    => $::execmode ne 'setup',
        nodeferring => $::execmode eq 'setup';
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
        tie my %newConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/bind.data.dist";
        tie my %oldConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/bind.data", readonly => TRUE;
        debug( 'Merging old configuration with new configuration...' );
        while ( my ( $key, $value ) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }
        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$self->{'cfgDir'}/bind.data.dist" )->moveFile( "$self->{'cfgDir'}/bind.data" ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error'
    );
}

=item _addDmnConfig( \%data )

 Add domain DNS configuration

 Param hashref \%data Data as provided by the Domain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _addDmnConfig
{
    my ( $self, $data ) = @_;

    unless ( defined $self->{'config'}->{'BIND_MODE'} ) {
        error( 'Bind mode is not defined.' );
        return 1;
    }

    my ( $fname, $fdir ) = fileparse( $self->{'config'}->{'BIND_LOCAL_CONF_FILE'} || $self->{'config'}->{'BIND_CONF_FILE'} );
    my $file = iMSCP::File->new( filename => File::Spec->catfile( $self->{'wrkDir'}, $fname ));
    my $fileC = $file->getAsRef();
    return 1 unless defined $fileC;

    my $tplFname = "cfg_$self->{'config'}->{'BIND_MODE'}.tpl";
    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'bind', $tplFname, \my $tplFileC, $data );
    return $rs if $rs;

    unless ( defined $tplFileC ) {
        $tplFileC = iMSCP::File->new( filename => File::Spec->catfile( $self->{'tplDir'}, $tplFname ))->get();
        return 1 unless defined $tplFileC;
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeNamedAddDmnConfig', $fileC, \$tplFileC, $data );
    return $rs if $rs;

    my $tags = {
        BIND_DB_FORMAT => $self->{'config'}->{'BIND_DB_FORMAT'} =~ s/=\d//r,
        DOMAIN_NAME    => $data->{'DOMAIN_NAME'}
    };

    if ( $self->{'config'}->{'BIND_MODE'} eq 'master' ) {
        if ( $self->{'config'}->{'SECONDARY_DNS'} ne 'no' ) {
            $tags->{'SECONDARY_DNS'} = join( '; ', split( /(?:[;,]| )/, $self->{'config'}->{'SECONDARY_DNS'} )) . '; localhost;';
        } else {
            $tags->{'SECONDARY_DNS'} = 'localhost;';
        }
    } else {
        $tags->{'PRIMARY_DNS'} = join( '; ', split( /(?:[;,]| )/, $self->{'config'}->{'PRIMARY_DNS'} )) . ';';
    }

    $tplFileC = "// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n" . process( $tags, $tplFileC ) . "// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n";
    ${ $fileC } = replaceBloc( "// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n", "// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n", '', ${ $fileC } );
    ${ $fileC } = replaceBloc( "// imscp [{ENTRY_ID}] entry BEGIN\n", "// imscp [{ENTRY_ID}] entry ENDING\n", $tplFileC, ${ $fileC }, TRUE );
    $rs = $self->{'eventManager'}->trigger( 'afterNamedAddDmnConfig', $fileC, $data );
    $rs ||= $file->save();
    $rs ||= $file->copyFile( File::Spec->catfile( $fdir, $fname ));
}

=item _deleteDmnConfig( \%data )

 Delete domain DNS configuration

 Param hashref \%data Data as provided by the Domain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _deleteDmnConfig
{
    my ( $self, $data ) = @_;

    my ( $fname, $fdir ) = fileparse( $self->{'config'}->{'BIND_LOCAL_CONF_FILE'} || $self->{'config'}->{'BIND_CONF_FILE'} );

    my $file = iMSCP::File->new( filename => File::Spec->catfile( $self->{'wrkDir'}, $fname ));
    my $fileC = $file->getAsRef();
    return 1 unless defined $fileC;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedDelDmnConfig', $fileC, $data );
    return $rs if $rs;

    ${ $fileC } = replaceBloc( "// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n", "// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n", '', ${ $fileC } );
    $rs = $self->{'eventManager'}->trigger( 'afterNamedDelDmnConfig', $fileC, $data );
    $rs ||= $file->save();
    $rs ||= $file->copyFile( File::Spec->catfile( $fdir, $fname ));
}

=item _addDmnDb( \%data )

 Add domain DNS zone file

 Param hashref \%data Data as provided by the Domain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _addDmnDb
{
    my ( $self, $data ) = @_;

    my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db" );
    my $fileC;

    if ( -f $file->{'filename'} && !defined( $fileC = $file->getAsRef()) ) {
        return 1;
    }

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'bind', 'db.tpl', \my $tplFileC, $data );
    return $rs if $rs;

    unless ( defined $tplFileC ) {
        $tplFileC = iMSCP::File->new( filename => "$self->{'tplDir'}/db.tpl" )->get();
        return 1 unless defined $tplFileC;
    }

    $rs = $self->_updateSerial( $data->{'DOMAIN_NAME'}, \$tplFileC, $fileC );
    $rs ||= $self->{'eventManager'}->trigger( 'beforeNamedAddDmnDb', \$tplFileC, $data );
    return $rs if $rs;

    my $nsRRb = getBloc( "; dmn NS RECORD entry BEGIN\n", "; dmn NS RECORD entry ENDING\n", $tplFileC );
    my $gRRb = getBloc( "; dmn NS GLUE RECORD entry BEGIN\n", "; dmn NS GLUE RECORD entry ENDING\n", $tplFileC );
    my $net = iMSCP::Net->getInstance();
    my $domainIP = $net->isRoutableAddr( $data->{'DOMAIN_IP'} ) ? $data->{'DOMAIN_IP'} : $data->{'BASE_SERVER_PUBLIC_IP'};

    unless ( $nsRRb eq '' && $gRRb eq '' ) {
        my @nsIPs = ( $domainIP, ( ( $self->{'config'}->{'SECONDARY_DNS'} eq 'no' ) ? () : split /(?:[;,]| )/, $self->{'config'}->{'SECONDARY_DNS'} ) );
        my ( $nsRR, $gRR ) = ( '', '' );

        for my $ipAddrType ( qw/ ipv4 ipv6 / ) {
            my $nsNumber = 1;
            for my $ipAddr ( @nsIPs ) {
                next unless $net->getAddrVersion( $ipAddr ) eq $ipAddrType;
                $nsRR .= process( { NS_NAME => 'ns' . $nsNumber }, $nsRRb ) if $nsRRb ne '';
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

        $tplFileC = replaceBloc( "; dmn NS RECORD entry BEGIN\n", "; dmn NS RECORD entry ENDING\n", $nsRR, $tplFileC ) if $nsRRb ne '';
        $tplFileC = replaceBloc( "; dmn NS GLUE RECORD entry BEGIN\n", "; dmn NS GLUE RECORD entry ENDING\n", $gRR, $tplFileC ) if $gRRb ne '';
    }

    my $mailEntry = '';
    if ( $data->{'MAIL_ENABLED'} ) {
        $mailEntry = process(
            {
                BASE_SERVER_IP_TYPE => $net->getAddrVersion( $data->{'BASE_SERVER_PUBLIC_IP'} ) eq 'ipv4' ? 'A' : 'AAAA',
                BASE_SERVER_IP      => $data->{'BASE_SERVER_PUBLIC_IP'}
            },
            getBloc( "; dmn MAIL entry BEGIN\n", "; dmn MAIL entry ENDING\n", $tplFileC )
        )
    }

    $tplFileC = replaceBloc( "; dmn MAIL entry BEGIN\n", "; dmn MAIL entry ENDING\n", $mailEntry, $tplFileC );
    $tplFileC = process(
        {
            DOMAIN_NAME => $data->{'DOMAIN_NAME'},
            IP_TYPE     => $net->getAddrVersion( $domainIP ) eq 'ipv4' ? 'A' : 'AAAA',
            DOMAIN_IP   => $domainIP
        },
        $tplFileC
    );

    unless ( !defined $fileC || $::execmode eq 'setup' ) {
        # Retrieve subdomain DNS records from current zone version and
        # re-inject them in new zone version
        # FIXME: By doing this way, we bypass the addSub() workflow leading to
        # events that are never triggered... This is a bad implementation.
        $tplFileC = replaceBloc(
            "; sub entries BEGIN\n",
            "; sub entries ENDING\n",
            getBloc( "; sub entries BEGIN\n", "; sub entries ENDING\n", ${ $fileC }, TRUE ),
            $tplFileC
        );
    }

    $rs = $self->{'eventManager'}->trigger( 'afterNamedAddDmnDb', \$tplFileC, $data );
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
        error( sprintf( "Couldn't update SOA serial number for the %s DNS zone", $zone ));
        return 1;
    }

    my %rc = %+;
    my ( $d, $m, $y ) = ( gmtime() )[3 .. 5];
    my $nowDate = sprintf( "%d%02d%02d", $y+1900, $m+1, $d );

    if ( exists $+{'placeholder'} ) {
        $self->{'serials'}->{$zone} = $nowDate . '00';
        ${ $nFileC } = process( { TIMESTAMP => $self->{'serials'}->{$zone} }, ${ $nFileC } );
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

=item _compileZone( $zone, $file )

 Compiles the given zone
 
 Param string $zone Zone name
 Param string $file Path to zone filename (zone in text format)
 Return int 0 on success, other on error
 
=cut

sub _compileZone
{
    my ( $self, $zone, $file ) = @_;

    local $UMASK = 027;
    my $rs = execute(
        'named-compilezone -i full -f text -F ' . escapeShell( $self->{'config'}->{'BIND_DB_FORMAT'} ) . ' -s relative -o - '
            . escapeShell( $zone ) . ' ' . escapeShell( $file ) . ' ' . '1>' . escapeShell( "$self->{'config'}->{'BIND_DB_MASTER_DIR'}/$zone.db" ),
        undef,
        \my $stderr
    );
    debug( $stderr ) if $stderr && !$rs;
    error( sprintf( "Couldn't compile the '%s' zone: %s", $zone, $stderr || 'Unknown error' )) if $rs;
    $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
