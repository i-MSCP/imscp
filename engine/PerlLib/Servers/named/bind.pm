=head1 NAME

 Servers::named::bind - i-MSCP Bind9 Server implementation

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
use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::ProgramFinder;
use iMSCP::TemplateParser;
use iMSCP::Net;
use iMSCP::Rights;
use iMSCP::Service;
use iMSCP::Umask;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Bind9 Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%eventManager )

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my (undef, $eventManager) = @_;

    Servers::named::bind::installer->getInstance()->registerSetupListeners( $eventManager );
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedPreInstall', 'bind' );
    $rs ||= $self->{'eventManager'}->trigger( 'afterNamedPreInstall', 'bind' );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

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
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedPostInstall' );
    return $rs if $rs;

    local $@;
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
    $rs ||= $self->{'eventManager'}->trigger( 'afterNamedPostInstall' );
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

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
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedSetEnginePermissions' );
    $rs ||= setRights(
        $self->{'config'}->{'BIND_CONF_DIR'},
        {
            user      => $main::imscpConfig{'ROOT_USER'},
            group     => $self->{'config'}->{'BIND_GROUP'},
            dirmode   => '2750',
            filemode  => '0640',
            recursive => 1
        }
    );
    $rs ||= setRights(
        $self->{'config'}->{'BIND_DB_ROOT_DIR'},
        {
            user      => $self->{'config'}->{'BIND_USER'},
            group     => $self->{'config'}->{'BIND_GROUP'},
            dirmode   => '2750',
            filemode  => '0640',
            recursive => 1
        }
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterNamedSetEnginePermissions' );
}

=item addDmn( \%data )

 Process addDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ($self, $data) = @_;

    # Never process the same zone twice
    # Occurs only in few contexts (eg. when using BASE_SERVER_VHOST as customer domain)
    return 0 if $self->{'seen_zones'}->{$data->{'DOMAIN_NAME'}};

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedAddDmn', $data );
    $rs ||= $self->_addDmnConfig( $data );
    return $rs if $rs;

    if ( $self->{'config'}->{'BIND_MODE'} eq 'master' ) {
        $rs = $self->_addDmnDb( $data );
        return $rs if $rs;
    }

    $self->{'seen_zones'}->{$data->{'DOMAIN_NAME'}} = 1;
    $self->{'eventManager'}->trigger( 'afterNamedAddDmn', $data );
}

=item postaddDmn( \%data )

 Process postaddDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub postaddDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedPostAddDmn', $data );
    return $rs if $rs;

    if ( $main::imscpConfig{'CLIENT_WEBSITES_ALT_URLS'} eq 'yes'
        && $self->{'config'}->{'BIND_MODE'} eq 'master'
        && defined $data->{'ALIAS'}
    ) {
        $rs = $self->addSub(
            {
                PARENT_DOMAIN_NAME    => $main::imscpConfig{'BASE_SERVER_VHOST'},
                DOMAIN_NAME           => $data->{'ALIAS'} . '.' . $main::imscpConfig{'BASE_SERVER_VHOST'},
                MAIL_ENABLED          => 0,
                DOMAIN_IP             => $data->{'BASE_SERVER_PUBLIC_IP'},
                BASE_SERVER_PUBLIC_IP => $data->{'BASE_SERVER_PUBLIC_IP'},
                OPTIONAL_ENTRIES      => 0
            }
        );
        return $rs if $rs;
    }

    $self->{'reload'} = 1;
    $self->{'eventManager'}->trigger( 'afterNamedPostAddDmn', $data );
}

=item disableDmn( \%data )

 Process disableDmn tasks

 When a domain is being disabled, we must ensure that the DNS data are still
 present for it (eg: when doing a full upgrade or reconfiguration). This
 explain here why we are executing the addDmn( ) action.

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub disableDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedDisableDmn', $data );
    $rs ||= $self->addDmn( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterNamedDisableDmn', $data );
}

=item postdisableDmn( \%data )

 Process postdisableDmn tasks

 See the disableDmn( ) method for explaination.

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub postdisableDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedPostDisableDmn', $data );
    $rs ||= $self->postaddDmn( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterNamedPostDisableDmn', $data );
}

=item deleteDmn( \%data )

 Process deleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ($self, $data) = @_;

    return 0 if $data->{'PARENT_DOMAIN_NAME'} eq $main::imscpConfig{'BASE_SERVER_VHOST'}
        && !$data->{'FORCE_DELETION'};

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedDelDmn', $data );
    $rs ||= $self->_deleteDmnConfig( $data );
    return $rs if $rs;

    if ( $self->{'config'}->{'BIND_MODE'} eq 'master' ) {
        for( "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db",
            "$self->{'config'}->{'BIND_DB_MASTER_DIR'}/$data->{'DOMAIN_NAME'}.db"
        ) {
            next unless -f;
            $rs = iMSCP::File->new( filename => $_ )->delFile();
            return $rs if $rs;
        }
    }

    $self->{'eventManager'}->trigger( 'afterNamedDelDmn', $data );
}

=item postdeleteDmn( \%data )

 Process postdeleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub postdeleteDmn
{
    my ($self, $data) = @_;

    return 0 if $data->{'PARENT_DOMAIN_NAME'} eq $main::imscpConfig{'BASE_SERVER_VHOST'}
        && !$data->{'FORCE_DELETION'};

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedPostDelDmn', $data );
    return $rs if $rs;

    if ( $main::imscpConfig{'CLIENT_WEBSITES_ALT_URLS'} eq 'yes'
        && $self->{'config'}->{'BIND_MODE'} eq 'master'
        && defined $data->{'ALIAS'}
    ) {
        $rs = $self->deleteSub(
            {
                PARENT_DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
                DOMAIN_NAME        => $data->{'ALIAS'} . '.' . $main::imscpConfig{'BASE_SERVER_VHOST'}
            }
        );
        return $rs if $rs;
    }

    $self->{'reload'} = 1;
    $self->{'eventManager'}->trigger( 'afterNamedPostDelDmn', $data );
}

=item addSub( \%data )

 Process addSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
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
        error( sprintf( "Couldn't read %s file", $wrkDbFile->{'filename'} ));
        return 1;
    }

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'bind', 'db_sub.tpl', \ my $subEntry, $data );
    return $rs if $rs;

    unless ( defined $subEntry ) {
        $subEntry = iMSCP::File->new( filename => "$self->{'tplDir'}/db_sub.tpl" )->get();
        unless ( defined $subEntry ) {
            error( sprintf( "Couldn't read %s file", "$self->{'tplDir'}/db_sub.tpl file" ));
            return 1;
        }
    }

    unless ( $self->{'serials'}->{$data->{'PARENT_DOMAIN_NAME'}} ) {
        $rs = $self->_updateSOAserialNumber( $data->{'PARENT_DOMAIN_NAME'}, \$wrkDbFileContent, \$wrkDbFileContent );
    }

    $rs ||= $self->{'eventManager'}->trigger( 'beforeNamedAddSub', \$wrkDbFileContent, \$subEntry, $data );
    return $rs if $rs;

    my $net = iMSCP::Net->getInstance();

    if ( $data->{'MAIL_ENABLED'} ) {
        $subEntry = replaceBloc(
            "; sub MAIL entry BEGIN\n",
            "; sub MAIL entry ENDING\n",
            process(
                {
                    BASE_SERVER_IP_TYPE => ( $net->getAddrVersion( $data->{'BASE_SERVER_PUBLIC_IP'} ) eq 'ipv4' )
                        ? 'A'
                        : 'AAAA',
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

    if ( defined $data->{'OPTIONAL_ENTRIES'} && !$data->{'OPTIONAL_ENTRIES'} ) {
        $subEntry = replaceBloc( "; sub OPTIONAL entries BEGIN\n", "; sub OPTIONAL entries ENDING\n", '', $subEntry );
    }

    my $domainIP = $net->isRoutableAddr( $data->{'DOMAIN_IP'} )
        ? $data->{'DOMAIN_IP'} : $data->{'BASE_SERVER_PUBLIC_IP'};

    $subEntry = process(
        {
            SUBDOMAIN_NAME => $data->{'DOMAIN_NAME'},
            IP_TYPE        => ( $net->getAddrVersion( $domainIP ) eq 'ipv4' ) ? 'A' : 'AAAA',
            DOMAIN_IP      => $domainIP
        },
        $subEntry
    );

    # Remove previous entry if any
    $wrkDbFileContent = replaceBloc(
        "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
        "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
        '',
        $wrkDbFileContent
    );

    # Add new entry
    $wrkDbFileContent = replaceBloc(
        "; sub [{SUBDOMAIN_NAME}] entry BEGIN\n",
        "; sub [{SUBDOMAIN_NAME}] entry ENDING\n",
        $subEntry,
        $wrkDbFileContent,
        'preserve'
    );

    $rs = $self->{'eventManager'}->trigger( 'afterNamedAddSub', \$wrkDbFileContent, $data );
    $rs ||= $wrkDbFile->set( $wrkDbFileContent );
    $rs ||= $wrkDbFile->save();
    $rs ||= $self->_compileZone( $data->{'PARENT_DOMAIN_NAME'}, $wrkDbFile->{'filename'} );
}

=item postaddSub( \%data )

 Process postaddSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub postaddSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedPostAddSub', $data );
    return $rs if $rs;

    if ( $main::imscpConfig{'CLIENT_WEBSITES_ALT_URLS'} eq 'yes'
        && $self->{'config'}->{'BIND_MODE'} eq 'master'
        && defined $data->{'ALIAS'}
    ) {
        $rs = $self->addSub(
            {
                PARENT_DOMAIN_NAME    => $main::imscpConfig{'BASE_SERVER_VHOST'},
                DOMAIN_NAME           => $data->{'ALIAS'} . '.' . $main::imscpConfig{'BASE_SERVER_VHOST'},
                MAIL_ENABLED          => 0,
                DOMAIN_IP             => $data->{'BASE_SERVER_PUBLIC_IP'},
                BASE_SERVER_PUBLIC_IP => $data->{'BASE_SERVER_PUBLIC_IP'},
                OPTIONAL_ENTRIES      => 0
            }
        );
        return $rs if $rs;
    }

    $self->{'reload'} = 1;
    $self->{'eventManager'}->trigger( 'afterNamedPostAddSub', $data );
}

=item disableSub( \%data )

 Process disableSub tasks

 When a subdomain is being disabled, we must ensure that the DNS data are still present for it (eg: when doing a full
 upgrade or reconfiguration). This explain here why we are executing the addSub( ) action.

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub disableSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedDisableSub', $data );
    $rs ||= $self->addSub( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterNamedDisableSub', $data );
}

=item postdisableSub( \%data )

 Process postdisableSub tasks

 See the disableSub( ) method for explaination.

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub postdisableSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedPostDisableSub', $data );
    $rs ||= $self->postaddSub( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterNamedPostDisableSub', $data );
}

=item deleteSub( \%data )

 Process deleteSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
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
        error( sprintf( "Couldn't read %s file", $wrkDbFile->{'filename'} ));
        return 1;
    }

    unless ( $self->{'serials'}->{$data->{'PARENT_DOMAIN_NAME'}} ) {
        my $rs = $self->_updateSOAserialNumber( $data->{'PARENT_DOMAIN_NAME'}, \$wrkDbFileContent, \$wrkDbFileContent );
        return $rs if $rs;
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedDelSub', \$wrkDbFileContent, $data );
    return $rs if $rs;

    $wrkDbFileContent = replaceBloc(
        "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
        "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
        '',
        $wrkDbFileContent
    );

    $rs = $self->{'eventManager'}->trigger( 'afterNamedDelSub', \$wrkDbFileContent, $data );
    $rs ||= $wrkDbFile->set( $wrkDbFileContent );
    $rs ||= $wrkDbFile->save();
    $rs ||= $self->_compileZone( $data->{'PARENT_DOMAIN_NAME'}, $wrkDbFile->{'filename'} );
}

=item postdeleteSub( \%data )

 Process postdeleteSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub postdeleteSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedPostDelSub', $data );
    return $rs if $rs;

    if ( $main::imscpConfig{'CLIENT_WEBSITES_ALT_URLS'} eq 'yes'
        && $self->{'config'}->{'BIND_MODE'} eq 'master'
        && defined $data->{'ALIAS'}
    ) {
        $rs = $self->deleteSub(
            {
                PARENT_DOMAIN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'},
                DOMAIN_NAME        => $data->{'ALIAS'} . '.' . $main::imscpConfig{'BASE_SERVER_VHOST'}
            }
        );
        return $rs if $rs;
    }

    $self->{'reload'} = 1;
    $self->{'eventManager'}->trigger( 'afterNamedPostDelSub', $data );
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
        error( sprintf( "Couldn't read %s file", $wrkDbFile->{'filename'} ));
        return 1;
    }

    unless ( $self->{'serials'}->{$data->{'DOMAIN_NAME'}} ) {
        my $rs = $self->_updateSOAserialNumber( $data->{'DOMAIN_NAME'}, \$wrkDbFileContent, \$wrkDbFileContent );
        return $rs if $rs;
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedAddCustomDNS', \$wrkDbFileContent, $data );
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

    $newWrkDbFileContent = replaceBloc(
        "; custom DNS entries BEGIN\n",
        "; custom DNS entries ENDING\n",
        "; custom DNS entries BEGIN\n" . ( join "\n", @customDNS, '' ) . "; custom DNS entries ENDING\n",
        $newWrkDbFileContent
    );

    $rs = $self->{'eventManager'}->trigger( 'afterNamedAddCustomDNS', \$newWrkDbFileContent, $data );
    $rs ||= $wrkDbFile->set( $newWrkDbFileContent );
    $rs ||= $wrkDbFile->save();
    $rs ||= $self->_compileZone( $data->{'DOMAIN_NAME'}, $wrkDbFile->{'filename'} );
    $self->{'reload'} = 1 unless $rs;
    $rs;
}

=item restart( )

 Restart Bind9

 Return int 0 on success, other on failure

=cut

sub restart
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedRestart' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart( $self->{'config'}->{'NAMED_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterNamedRestart' );
}

=item reload( )

 Reload Bind9

 Return int 0 on success, other on failure

=cut

sub reload
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedReload' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->reload( $self->{'config'}->{'NAMED_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterNamedReload' );
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
    my ($self) = @_;

    $self->{'restart'} = 0;
    $self->{'reload'} = 0;
    $self->{'serials'} = {};
    $self->{'seen_zones'} = {};
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/bind";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'tplDir'} = "$self->{'cfgDir'}/parts";
    $self->_mergeConfig() if -f "$self->{'cfgDir'}/bind.data.dist";
    tie %{$self->{'config'}},
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/bind.data",
        readonly    => !( defined $main::execmode && $main::execmode eq 'setup' ),
        nodeferring => ( defined $main::execmode && $main::execmode eq 'setup' );
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

        debug( 'Merging old configuration with new configuration...' );

        while ( my ($key, $value) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }

        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$self->{'cfgDir'}/bind.data.dist" )->moveFile(
        "$self->{'cfgDir'}/bind.data"
    ) == 0 or die(
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
        error( sprintf( "Couldn't read %s file", "$self->{'wrkDir'}/$cfgFileName" ));
        return 1;
    }

    my $tplFileName = "cfg_$self->{'config'}->{'BIND_MODE'}.tpl";
    my $rs = $self->{'eventManager'}->trigger(
        'onLoadTemplate', 'bind', $tplFileName, \ my $tplCfgEntryContent, $data
    );
    return $rs if $rs;

    unless ( defined $tplCfgEntryContent ) {
        $tplCfgEntryContent = iMSCP::File->new( filename => "$self->{'tplDir'}/$tplFileName" )->get();
        unless ( defined $tplCfgEntryContent ) {
            error( sprintf( "Couldn't read %s file", "$self->{'tplDir'}/$tplFileName" ));
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger(
        'beforeNamedAddDmnConfig', \$cfgWrkFileContent, \$tplCfgEntryContent, $data
    );
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

    $tplCfgEntryContent = "// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n"
        . process( $tags, $tplCfgEntryContent )
        . "// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n";

    $cfgWrkFileContent = replaceBloc(
        "// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
        "// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n",
        '',
        $cfgWrkFileContent
    );
    $cfgWrkFileContent = replaceBloc(
        "// imscp [{ENTRY_ID}] entry BEGIN\n",
        "// imscp [{ENTRY_ID}] entry ENDING\n",
        $tplCfgEntryContent,
        $cfgWrkFileContent,
        'preserve'
    );

    $rs = $self->{'eventManager'}->trigger( 'afterNamedAddDmnConfig', \$cfgWrkFileContent, $data );
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
        error( sprintf( "Couldn't read %s file", "$self->{'wrkDir'}/$cfgFileName" ));
        return 1;
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedDelDmnConfig', \$cfgWrkFileContent, $data );
    return $rs if $rs;

    $cfgWrkFileContent = replaceBloc(
        "// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
        "// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n",
        '',
        $cfgWrkFileContent
    );

    $rs = $self->{'eventManager'}->trigger( 'afterNamedDelDmnConfig', \$cfgWrkFileContent, $data );
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
        error( sprintf( "Couldn't read %s file", $wrkDbFile->{'filename'} ));
        return 1;
    }

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'bind', 'db.tpl', \ my $tplDbFileC, $data );
    return $rs if $rs;

    unless ( defined $tplDbFileC ) {
        $tplDbFileC = iMSCP::File->new( filename => "$self->{'tplDir'}/db.tpl" )->get();
        unless ( defined $tplDbFileC ) {
            error( sprintf( "Couldn't read %s file", "$self->{'tplDir'}/db.tpl" ));
            return 1;
        }
    }

    $rs = $self->_updateSOAserialNumber( $data->{'DOMAIN_NAME'}, \$tplDbFileC, \$wrkDbFileContent );
    $rs ||= $self->{'eventManager'}->trigger( 'beforeNamedAddDmnDb', \$tplDbFileC, $data );
    return $rs if $rs;

    my $nsRecordB = getBloc( "; dmn NS RECORD entry BEGIN\n", "; dmn NS RECORD entry ENDING\n", $tplDbFileC );
    my $glueRecordB = getBloc(
        "; dmn NS GLUE RECORD entry BEGIN\n", "; dmn NS GLUE RECORD entry ENDING\n", $tplDbFileC
    );

    my $net = iMSCP::Net->getInstance();
    my $domainIP = $net->isRoutableAddr( $data->{'DOMAIN_IP'} )
        ? $data->{'DOMAIN_IP'} : $data->{'BASE_SERVER_PUBLIC_IP'};

    unless ( $nsRecordB eq '' && $glueRecordB eq '' ) {
        my @nsIPs = (
            $domainIP,
            ( ( $self->{'config'}->{'SECONDARY_DNS'} eq 'no' ) ? () : split /(?:[;,]| )/, $self->{'config'}->{'SECONDARY_DNS'} )
        );

        my ($nsRecords, $glueRecords) = ( '', '' );

        for my $ipAddrType( qw/ ipv4 ipv6 / ) {
            my $nsNumber = 1;

            for my $ipAddr( @nsIPs ) {
                next unless $net->getAddrVersion( $ipAddr ) eq $ipAddrType;
                $nsRecords .= process(
                    { NS_NAME => 'ns' . $nsNumber },
                    $nsRecordB
                ) if $nsRecordB ne '';

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

        $tplDbFileC = replaceBloc(
            "; dmn NS RECORD entry BEGIN\n", "; dmn NS RECORD entry ENDING\n", $nsRecords, $tplDbFileC
        ) if $nsRecordB ne '';

        $tplDbFileC = replaceBloc(
            "; dmn NS GLUE RECORD entry BEGIN\n", "; dmn NS GLUE RECORD entry ENDING\n", $glueRecords, $tplDbFileC
        ) if $glueRecordB ne '';
    }

    my $dmnMailEntry = '';
    if ( $data->{'MAIL_ENABLED'} ) {
        $dmnMailEntry = process(
            {
                BASE_SERVER_IP_TYPE => ( $net->getAddrVersion( $data->{'BASE_SERVER_PUBLIC_IP'} ) eq 'ipv4' )
                    ? 'A' : 'AAAA',
                BASE_SERVER_IP      => $data->{'BASE_SERVER_PUBLIC_IP'}
            },
            getBloc( "; dmn MAIL entry BEGIN\n", "; dmn MAIL entry ENDING\n", $tplDbFileC )
        )
    }

    $tplDbFileC = replaceBloc( "; dmn MAIL entry BEGIN\n", "; dmn MAIL entry ENDING\n", $dmnMailEntry, $tplDbFileC );
    $tplDbFileC = process(
        {
            DOMAIN_NAME => $data->{'DOMAIN_NAME'},
            IP_TYPE     => ( $net->getAddrVersion( $domainIP ) eq 'ipv4' ) ? 'A' : 'AAAA',
            DOMAIN_IP   => $domainIP
        },
        $tplDbFileC
    );

    unless ( !defined $wrkDbFileContent || defined $main::execmode && $main::execmode eq 'setup' ) {
        # Re-add subdomain entries
        $tplDbFileC = replaceBloc(
            "; sub entries BEGIN\n",
            "; sub entries ENDING\n",
            getBloc( "; sub entries BEGIN\n", "; sub entries ENDING\n", $wrkDbFileContent, 'with_tags' ),
            $tplDbFileC
        );

        # Re-add custom DNS entries
        $tplDbFileC = replaceBloc(
            "; custom DNS entries BEGIN\n",
            "; custom DNS entries ENDING\n",
            getBloc( "; custom DNS entries BEGIN\n", "; custom DNS entries ENDING\n", $wrkDbFileContent, 'with_tags' ),
            $tplDbFileC
        );
    }

    $rs = $self->{'eventManager'}->trigger( 'afterNamedAddDmnDb', \$tplDbFileC, $data );
    $rs ||= $wrkDbFile->set( $tplDbFileC );
    $rs ||= $wrkDbFile->save();
    $rs ||= $self->_compileZone( $data->{'DOMAIN_NAME'}, $wrkDbFile->{'filename'} );
}

=item _updateSOAserialNumber( $zone, \$zoneFileContent, \$oldZoneFileContent )

 Update SOA serial number for the given zone
 
 Note: Format follows RFC 1912 section 2.2 recommendations.

 Param string zone Zone name
 Param scalarref \$zoneFileContent Zone file content
 Param scalarref \$oldZoneFileContent Old zone file content
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
        ${$zoneFileContent} = process( { TIMESTAMP => $self->{'serials'}->{$zone} }, ${$zoneFileContent} );
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

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
