=head1 NAME

 Servers::named::bind - i-MSCP Bind9 Server implementation

=cut

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package Servers::named::bind;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::ProgramFinder;
use iMSCP::TemplateParser;
use iMSCP::Net;
use iMSCP::Service;
use File::Basename;
use Scalar::Defer;
use Class::Autouse qw/Servers::named::bind::installer Servers::named::bind::uninstaller/;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Bind9 Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners(\%eventManager)

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self, $eventManager) = @_;

    Servers::named::bind::installer->getInstance()->registerSetupListeners( $eventManager );
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedPreInstall', 'bind' );
    $rs ||= $self->{'eventManager'}->trigger( 'afterNamedPreInstall', 'bind' );
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedInstall', 'bind' );
    $rs ||= Servers::named::bind::installer->getInstance()->install();
    $rs ||= $self->{'eventManager'}->trigger( 'afterNamedInstall', 'bind' );
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedPostInstall' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->enable( $self->{'config'}->{'NAMED_SNAME'} ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $rs ||= $self->{'eventManager'}->register(
        'beforeSetupRestartServices', sub {
            push @{$_[0]}, [ sub { $self->restart(); }, 'Bind9' ];
            0;
        }
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterNamedPostInstall' );
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedUninstall', 'bind' );
    $rs ||= Servers::named::bind::uninstaller->getInstance()->uninstall();
    return $rs if $rs;

    if (iMSCP::ProgramFinder::find( $self->{'config'}->{'NAMED_BNAME'} )) {
        $rs = $self->restart();
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterNamedUninstall', 'bind' );
}

=item addDmn(\%data)

 Process addDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedAddDmn', $data );
    $rs ||= $self->_addDmnConfig( $data );
    return $rs if $rs;

    if ($self->{'config'}->{'BIND_MODE'} eq 'master') {
        $rs = $self->_addDmnDb( $data );
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterNamedAddDmn', $data );
}

=item postaddDmn(\%data)

 Process postaddDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub postaddDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedPostAddDmn', $data );
    return $rs if $rs;

    if ($self->{'config'}->{'BIND_MODE'} eq 'master') {
        my $domainIp = ($main::imscpConfig{'BASE_SERVER_IP'} eq $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'})
            ? $data->{'DOMAIN_IP'} : $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'};

        $rs = $self->addDmn( {
                DOMAIN_NAME       => $main::imscpConfig{'BASE_SERVER_VHOST'},
                DOMAIN_IP         => $main::imscpConfig{'BASE_SERVER_IP'},
                MAIL_ENABLED      => 1,
                CTM_ALS_ENTRY_ADD => {
                    NAME  => $data->{'USER_NAME'},
                    CLASS => 'IN',
                    TYPE  => iMSCP::Net->getInstance()->getAddrVersion( $domainIp ) eq 'ipv4' ? 'A' : 'AAAA',
                    DATA  => $domainIp
                }
            } );
        return $rs if $rs;
    }

    $self->{'reload'} = 1;
    $self->{'eventManager'}->trigger( 'afterNamedPostAddDmn', $data );
}

=item disableDmn(\%data)

 Process disableDmn tasks

 When a domain is being disabled, we must ensure that the DNS data are still present for it (eg: when doing a full
upgrade or reconfiguration). This explain here why we are calling the addDmn() method.

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

=item postdisableDmn(\%data)

 Process postdisableDmn tasks

 See the disableDmn() method for explaination.

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

=item deleteDmn(\%data)

 Process deleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedDelDmn', $data );
    $rs ||= $self->_deleteDmnConfig( $data );
    return $rs if $rs;

    if ($self->{'config'}->{'BIND_MODE'} eq 'master') {
        for my $file(
            "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db",
            "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db"
        ) {
            if (-f $file) {
                $rs = iMSCP::File->new( filename => $file )->delFile();
                return $rs if $rs;
            }
        }
    }

    $self->{'eventManager'}->trigger( 'afterNamedDelDmn', $data );
}

=item postdeleteDmn(\%data)

 Process postdeleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub postdeleteDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedPostDelDmn', $data );
    return $rs if $rs;

    if ($self->{'config'}->{'BIND_MODE'} eq 'master') {
        $rs = $self->addDmn( {
                DOMAIN_NAME       => $main::imscpConfig{'BASE_SERVER_VHOST'},
                DOMAIN_IP         => $main::imscpConfig{'BASE_SERVER_IP'},
                MAIL_ENABLED      => 1,
                CTM_ALS_ENTRY_DEL => { NAME => $data->{'USER_NAME'} }
            } );
        return $rs if $rs;
    }

    $self->{'reload'} = 1;
    $self->{'eventManager'}->trigger( 'afterNamedPostDelDmn', $data );
}

=item addSub(\%data)

 Process addSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ($self, $data) = @_;

    return 0 unless $self->{'config'}->{'BIND_MODE'} eq 'master';

    my $wrkDbFile = "$self->{'wrkDir'}/$data->{'PARENT_DOMAIN_NAME'}.db";
    unless (-f $wrkDbFile) {
        error( sprintf( 'File %s not found. Please run the i-MSCP setup script.', $wrkDbFile ) );
        return 1;
    }

    $wrkDbFile = iMSCP::File->new( filename => $wrkDbFile );
    my $wrkDbFileContent = $wrkDbFile->get();
    unless (defined $wrkDbFileContent) {
        error( sprintf( 'Could not read %s file', $wrkDbFile->{'filename'} ) );
        return 1;
    }

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'bind', 'db_sub.tpl', \my $subEntry, $data );
    return $rs if $rs;

    unless (defined $subEntry) {
        $subEntry = iMSCP::File->new( filename => "$self->{'tplDir'}/db_sub.tpl" )->get();
        unless (defined $subEntry) {
            error( sprintf( 'Could not read %s file', "$self->{'tplDir'}/db_sub.tpl file" ) );
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeNamedAddSub', \$wrkDbFileContent, \$subEntry, $data );
    return $rs if $rs;

    $wrkDbFileContent = $self->_generateSoalSerialNumber( $wrkDbFileContent );
    unless (defined $wrkDbFileContent) {
        error( 'Could not update SOA Serial' );
        return 1;
    }

    if ($data->{'MAIL_ENABLED'}) {
        my $subMailEntry = getBloc( "; sub MX entry BEGIN\n", "; sub MX entry ENDING\n", $subEntry );
        my $subMailEntryContent = '';

        for my $entry(keys %{$data->{'MAIL_DATA'}}) {
            $subMailEntryContent .= process( { MX_DATA => $data->{'MAIL_DATA'}->{$entry} }, $subMailEntry );
        }

        $subEntry = replaceBloc(
            "; sub MX entry BEGIN\n", "; sub MX entry ENDING\n", $subMailEntryContent, $subEntry
        );

        $subEntry = replaceBloc(
            "; sub SPF entry BEGIN\n",
            "; sub SPF entry ENDING\n",
            process(
                { DOMAIN_NAME => $data->{'PARENT_DOMAIN_NAME'} },
                getBloc( "; sub SPF entry BEGIN\n", "; sub SPF entry ENDING\n", $subEntry )
            ),
            $subEntry
        );
    } else {
        $subEntry = replaceBloc( "; sub MX entry BEGIN\n", "; sub MX entry ENDING\n", '', $subEntry );
        $subEntry = replaceBloc( "; sub SPF entry BEGIN\n", "; sub SPF entry ENDING\n", '', $subEntry );
    }

    my $domainIp = ($main::imscpConfig{'BASE_SERVER_IP'} eq $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'})
        ? $data->{'DOMAIN_IP'} : $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'};

    my $net = iMSCP::Net->getInstance();

    $subEntry = process(
        {
            SUBDOMAIN_NAME => $data->{'DOMAIN_NAME'},
            IP_TYPE        => $net->getAddrVersion( $domainIp ) eq 'ipv4' ? 'A' : 'AAAA',
            DOMAIN_IP      => $domainIp
        },
        $subEntry
    );

    $wrkDbFileContent = replaceBloc(
        "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
        "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
        '',
        $wrkDbFileContent
    );

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
    return $rs if $rs;

    $rs = execute(
        'named-compilezone -i none -s relative'.
            " -o - $data->{'PARENT_DOMAIN_NAME'} $wrkDbFile->{'filename'}".
            " > $self->{'config'}->{'BIND_DB_DIR'}/$data->{'PARENT_DOMAIN_NAME'}.db",
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    error( sprintf( 'Could not dump %s zone file', $wrkDbFile->{'filename'} ) ) if $rs && !$stderr;
    return $rs if $rs;

    my $prodFile = iMSCP::File->new( filename =>
        "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'PARENT_DOMAIN_NAME'}.db" );
    $rs ||= $prodFile->mode( 0640 );
    $rs = $prodFile->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'} );
}

=item postaddSub(\%data)

 Process postaddSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub postaddSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedPostAddSub', $data );
    return $rs if $rs;

    if ($self->{'config'}->{'BIND_MODE'} eq 'master') {
        my $domainIp = (($main::imscpConfig{'BASE_SERVER_IP'} eq $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'}))
            ? $data->{'DOMAIN_IP'} : $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'};

        $rs = $self->addDmn( {
                DOMAIN_NAME       => $main::imscpConfig{'BASE_SERVER_VHOST'},
                DOMAIN_IP         => $main::imscpConfig{'BASE_SERVER_IP'},
                MAIL_ENABLED      => 1,
                CTM_ALS_ENTRY_ADD => {
                    NAME  => $data->{'USER_NAME'},
                    CLASS => 'IN',
                    TYPE  => iMSCP::Net->getInstance()->getAddrVersion( $domainIp ) eq 'ipv4' ? 'A' : 'AAAA',
                    DATA  => $domainIp
                }
            } );
        return $rs if $rs;
    }

    $self->{'reload'} = 1;
    $self->{'eventManager'}->trigger( 'afterNamedPostAddSub', $data );
}

=item disableSub(\%data)

 Process disableSub tasks

 When a subdomain is being disabled, we must ensure that the DNS data are still present for it (eg: when doing a full
upgrade or reconfiguration). This explain here why we are calling the addSub() method.

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

=item postdisableSub(\%data)

 Process postdisableSub tasks

 See the disableSub() method for explaination.

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

=item deleteSub(\%data)

 Process deleteSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
    my ($self, $data) = @_;

    return 0 unless $self->{'config'}->{'BIND_MODE'} eq 'master';

    my $wrkDbFile = "$self->{'wrkDir'}/$data->{'PARENT_DOMAIN_NAME'}.db";
    unless (-f $wrkDbFile) {
        error( sprintf( 'File %s not found. Please run the i-MSCP setup script.', $wrkDbFile ) );
        return 1;
    }

    $wrkDbFile = iMSCP::File->new( filename => $wrkDbFile );
    my $wrkDbFileContent = $wrkDbFile->get();
    unless (defined $wrkDbFileContent) {
        error( sprintf( 'Could not read %s file', $wrkDbFile->{'filename'} ) );
        return 1;
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedDelSub', \$wrkDbFileContent, $data );
    return $rs if $rs;

    $wrkDbFileContent = $self->_generateSoalSerialNumber( $wrkDbFileContent );
    unless (defined $wrkDbFileContent) {
        error( 'Could not load update SOA Serial' );
        return 1;
    }

    $wrkDbFileContent = replaceBloc(
        "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
        "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
        '',
        $wrkDbFileContent
    );

    $rs = $self->{'eventManager'}->trigger( 'afterNamedDelSub', \$wrkDbFileContent, $data );
    $rs ||= $wrkDbFile->set( $wrkDbFileContent );
    $rs ||= $wrkDbFile->save();
    return $rs if $rs;

    $rs = execute(
        'named-compilezone -i none -s relative'.
            " -o - $data->{'PARENT_DOMAIN_NAME'} $wrkDbFile->{'filename'}".
            " > $self->{'config'}->{'BIND_DB_DIR'}/$data->{'PARENT_DOMAIN_NAME'}.db",
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    error( sprintf( 'Could not dump %s zone file', $wrkDbFile->{'filename'} ) ) if $rs && !$stderr;
    return $rs if $rs;

    my $prodFile = iMSCP::File->new(
        filename => "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'PARENT_DOMAIN_NAME'}.db"
    );
    $rs = $prodFile->mode( 0640 );
    $rs ||= $prodFile->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'} );
}

=item postdeleteSub(\%data)

 Process postdeleteSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub postdeleteSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedPostDelSub', $data );
    return $rs if $rs;

    if ($self->{'config'}->{'BIND_MODE'} eq 'master') {
        $rs = $self->addDmn( {
                DOMAIN_NAME       => $main::imscpConfig{'BASE_SERVER_VHOST'},
                DOMAIN_IP         => $main::imscpConfig{'BASE_SERVER_IP'},
                MAIL_ENABLED      => 1,
                CTM_ALS_ENTRY_DEL => { NAME => $data->{'USER_NAME'} }
            } );
        return $rs if $rs;
    }

    $self->{'reload'} = 1;
    $self->{'eventManager'}->trigger( 'afterNamedPostDelSub', $data );
}

=item addCustomDNS(\%data)

 Process addCustomDNS tasks

 Param hash \%data Custom DNS data
 Return int 0 on success, other on failure

=cut

sub addCustomDNS
{
    my ($self, $data) = @_;

    return 0 unless $self->{'config'}->{'BIND_MODE'} eq 'master';

    my $wrkDbFile = "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db";
    unless (-f $wrkDbFile) {
        error( sprintf( 'File %s not found. Please run the i-MSCP setup script.', $wrkDbFile ) );
        return 1;
    }

    $wrkDbFile = iMSCP::File->new( filename => $wrkDbFile );
    my $wrkDbFileContent = $wrkDbFile->get();
    unless (defined $wrkDbFileContent) {
        error( sprintf( 'Could not read %s file', $wrkDbFile->{'filename'} ) );
        return 1;
    }

    $wrkDbFileContent = $self->_generateSoalSerialNumber( $wrkDbFileContent );
    unless (defined $wrkDbFileContent) {
        error( 'Could not to update SOA Serial' );
        return 1;
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedAddCustomDNS', \$wrkDbFileContent, $data );
    return $rs if $rs;

    my @customDnsEntries = ();
    for my $record(@{$data->{'DNS_RECORDS'}}) {
        push @customDnsEntries, join "\t", @{$record};
    }

    # Remove default SPF records if needed
    if (grep($_ =~ /^[^\s]+\s+IN\s+(?:SPF|TXT)\s+.*?v=spf1\s.*/gm, @customDnsEntries)) {
        $wrkDbFileContent =~ s/^[^\s]+\s+IN\s+TXT\s+.*?v=spf1\s.*\n//gm;
    }

    $wrkDbFileContent = replaceBloc(
        "; custom DNS entries BEGIN\n",
        "; custom DNS entries ENDING\n",
        "; custom DNS entries BEGIN\n".( join "\n", @customDnsEntries, '' )."; custom DNS entries ENDING\n",
        $wrkDbFileContent
    );

    $rs = $self->{'eventManager'}->trigger( 'afterNamedAddCustomDNS', \$wrkDbFileContent, $data );
    $rs ||= $wrkDbFile->set( $wrkDbFileContent );
    $rs ||= $wrkDbFile->save();
    return $rs if $rs;

    $rs = execute(
        'named-compilezone -i full -s relative'.
            " -o - $data->{'DOMAIN_NAME'} $wrkDbFile->{'filename'}".
            " > $self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db",
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $rs;
    return $rs if $rs;

    my $prodFile = iMSCP::File->new( filename => "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db" );
    $rs = $prodFile->mode( 0640 );
    $rs ||= $prodFile->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'} );
    return $rs if $rs;
    $self->{'reload'} = 1;
    0;
}

=item restart()

 Restart Bind9

 Return int 0 on success, other on failure

=cut

sub restart
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedRestart' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart( $self->{'config'}->{'NAMED_SNAME'} ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterNamedRestart' );
}

=item reload()

 Reload Bind9

 Return int 0 on success, other on failure

=cut

sub reload
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedReload' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->reload( $self->{'config'}->{'NAMED_SNAME'} ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterNamedReload' );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::named::bind

=cut

sub _init
{
    my $self = shift;

    $self->{'restart'} = 0;
    $self->{'reload'} = 0;
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'eventManager'}->trigger( 'beforeNamedInit', $self,
        'bind' ) and fatal( 'bind - beforeNamedInit has failed' );
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/bind";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'tplDir'} = "$self->{'cfgDir'}/parts";
    $self->{'config'} = lazy {
            tie my %c, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/bind.data";
            \%c;
        };
    $self->{'eventManager'}->trigger( 'afterNamedInit', $self, 'bind' ) and fatal( 'bind - afterNamedInit has failed' );
    $self;
}

=item _addDmnConfig(\%data)

 Add domain DNS configuration

 Param hash \%data Data as provided by the Domain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _addDmnConfig
{
    my ($self, $data) = @_;

    unless (defined $self->{'config'}->{'BIND_MODE'}) {
        error( 'Bind mode is not defined. Please rerun the i-MSCP setup script.' );
        return 1;
    }

    my ($cfgFileName, $cfgFileDir) = fileparse(
        $self->{'config'}->{'BIND_LOCAL_CONF_FILE'} || $self->{'config'}->{'BIND_CONF_FILE'}
    );

    unless (-f "$self->{'wrkDir'}/$cfgFileName") {
        error( sprintf( 'File %s not found. Please rerun the i-MSCP setup script.',
                "$self->{'wrkDir'}/$cfgFileName" ) );
        return 1;
    }

    my $cfgFile = iMSCP::File->new( filename => "$self->{'wrkDir'}/$cfgFileName" );
    my $cfgWrkFileContent = $cfgFile->get();
    unless (defined $cfgWrkFileContent) {
        error( sprintf( 'Could not read %s file', "$self->{'wrkDir'}/$cfgFileName" ) );
        return 1;
    }

    my $tplFileName = "cfg_$self->{'config'}->{'BIND_MODE'}.tpl";
    my $rs = $self->{'eventManager'}->trigger(
        'onLoadTemplate', 'bind', $tplFileName, \my $tplCfgEntryContent, $data
    );
    return $rs if $rs;

    unless (defined $tplCfgEntryContent) {
        $tplCfgEntryContent = iMSCP::File->new( filename => "$self->{'tplDir'}/$tplFileName" )->get();
        unless (defined $tplCfgEntryContent) {
            error( sprintf( 'Could not read %s file', "$self->{'tplDir'}/$tplFileName" ) );
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger(
        'beforeNamedAddDmnConfig', \$cfgWrkFileContent, \$tplCfgEntryContent, $data
    );
    return $rs if $rs;

    my $tags = {
        DB_DIR      => $self->{'config'}->{'BIND_DB_DIR'},
        DOMAIN_NAME => $data->{'DOMAIN_NAME'}
    };

    if ($self->{'config'}->{'BIND_MODE'} eq 'master') {
        if ($self->{'config'}->{'SECONDARY_DNS'} ne 'no') {
            $tags->{'SECONDARY_DNS'} = join( '; ', split( ';', $self->{'config'}->{'SECONDARY_DNS'} ) ).'; localhost;';
        } else {
            $tags->{'SECONDARY_DNS'} = 'localhost;';
        }
    } else {
        $tags->{'PRIMARY_DNS'} = join( '; ', split( ';', $self->{'config'}->{'PRIMARY_DNS'} ) ).';';
    }

    $tplCfgEntryContent =
        "// imscp [$data->{'DOMAIN_NAME'}] entry BEGIN\n".
            process( $tags, $tplCfgEntryContent ).
            "// imscp [$data->{'DOMAIN_NAME'}] entry ENDING\n";

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
    $rs ||= $cfgFile->mode( 0644 );
    $rs ||= $cfgFile->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'} );
    $rs ||= $cfgFile->copyFile( "$cfgFileDir$cfgFileName" );
}

=item _deleteDmnConfig(\%data)

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

    unless (-f "$self->{'wrkDir'}/$cfgFileName") {
        error( sprintf( 'File %s not found. Please rerun the i-MSCP setup script.',
                "$self->{'wrkDir'}/$cfgFileName" ) );
        return 1;
    }

    my $cfgFile = iMSCP::File->new( filename => "$self->{'wrkDir'}/$cfgFileName" );
    my $cfgWrkFileContent = $cfgFile->get();
    unless (defined $cfgWrkFileContent) {
        error( sprintf( 'Could not read %s file', "$self->{'wrkDir'}/$cfgFileName" ) );
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
    $rs ||= $cfgFile->mode( 0644 );
    $rs ||= $cfgFile->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'} );
    $rs ||= $cfgFile->copyFile( "$cfgFileDir$cfgFileName" );
}

=item _addDmnDb(\%data)

 Add domain DNS zone file

 Param hash \%data Data as provided by the Domain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _addDmnDb
{
    my ($self, $data) = @_;

    my $wrkDbFile = "$self->{'wrkDir'}/$data->{'DOMAIN_NAME'}.db";
    my $wrkDbFileContent;

    if (-f $wrkDbFile) {
        $wrkDbFile = iMSCP::File->new( filename => $wrkDbFile );
        $wrkDbFileContent = $wrkDbFile->get();
        unless (defined $wrkDbFileContent) {
            error( sprintf( 'Could not read %s file', $wrkDbFile->{'filename'} ) );
            return 1;
        }
    } else {
        $wrkDbFile = iMSCP::File->new( filename => $wrkDbFile );
    }

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'bind', 'db.tpl', \my $tplDbFileContent, $data );
    return $rs if $rs;

    unless (defined $tplDbFileContent) {
        $tplDbFileContent = iMSCP::File->new( filename => "$self->{'tplDir'}/db.tpl" )->get();
        unless (defined $tplDbFileContent) {
            error( sprintf( 'Could not read %s file', "$self->{'tplDir'}/db.tpl" ) );
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeNamedAddDmnDb', \$tplDbFileContent, $data );
    return $rs if $rs;

    $tplDbFileContent = $self->_generateSoalSerialNumber(
        $tplDbFileContent, defined $wrkDbFileContent ? $wrkDbFileContent : undef
    );
    unless (defined $tplDbFileContent) {
        error( 'Could not add/update SOA Serial' );
        return 1;
    }

    my $dmnNsEntry = getBloc( "; dmn NS entry BEGIN\n", "; dmn NS entry ENDING\n", $tplDbFileContent );
    my $dmnNsAEntry = getBloc( "; dmn NS A entry BEGIN\n", "; dmn NS A entry ENDING\n", $tplDbFileContent );
    my $domainIp = (($main::imscpConfig{'BASE_SERVER_IP'} eq $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'}))
        ? $data->{'DOMAIN_IP'} : $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'};

    my @nsIPs = (
        $domainIp, $self->{'config'}->{'SECONDARY_DNS'} eq 'no' ? () : split ';', $self->{'config'}->{'SECONDARY_DNS'}
    );

    my $net = iMSCP::Net->getInstance();
    my ($dmnNsEntries, $dmnNsAentries, $nsNumber) = (undef, undef, 1);

    for my $ipAddr(@nsIPs) {
        $dmnNsEntries .= process( { NS_NUMBER => $nsNumber }, $dmnNsEntry );
        $dmnNsAentries .= process(
            {
                NS_NUMBER  => $nsNumber,
                NS_IP_TYPE => $net->getAddrVersion( $ipAddr ) eq 'ipv4' ? 'A' : 'AAAA',
                NS_IP      => $ipAddr
            },
            $dmnNsAEntry
        );

        $nsNumber++;
    }

    $tplDbFileContent = replaceBloc(
        "; dmn NS entry BEGIN\n", "; dmn NS entry ENDING\n", $dmnNsEntries, $tplDbFileContent
    );
    $tplDbFileContent = replaceBloc(
        "; dmn NS A entry BEGIN\n", "; dmn NS A entry ENDING\n", $dmnNsAentries, $tplDbFileContent
    );

    my $dmnMailEntry = '';
    if ($data->{'MAIL_ENABLED'}) {
        my $baseServerIp = $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'};

        $dmnMailEntry = process(
            {
                BASE_SERVER_IP_TYPE => $net->getAddrVersion( $baseServerIp ) eq 'ipv4' ? 'A' : 'AAAA',
                BASE_SERVER_IP      => $baseServerIp
            },
            getBloc( "; dmn MAIL entry BEGIN\n", "; dmn MAIL entry ENDING\n", $tplDbFileContent )
        )
    }

    $tplDbFileContent = replaceBloc(
        "; dmn MAIL entry BEGIN\n", "; dmn MAIL entry ENDING\n", $dmnMailEntry, $tplDbFileContent
    );

    for my $record(@{$data->{'SPF_RECORDS'}}) {
        $tplDbFileContent .= $record."\n";
    }

    if (defined $wrkDbFileContent) {
        if (exists $data->{'CTM_ALS_ENTRY_ADD'}) {
            $wrkDbFileContent =~ s/^$data->{'CTM_ALS_ENTRY_ADD'}->{'NAME'}\s+[^\n]*\n//m;

            $tplDbFileContent = replaceBloc(
                "; ctm als entries BEGIN\n",
                "; ctm als entries ENDING\n",
                "; ctm als entries BEGIN\n".
                    getBloc( "; ctm als entries BEGIN\n", "; ctm als entries ENDING\n", $wrkDbFileContent ).
                    process(
                        {
                            NAME  => $data->{'CTM_ALS_ENTRY_ADD'}->{'NAME'},
                            CLASS => $data->{'CTM_ALS_ENTRY_ADD'}->{'CLASS'},
                            TYPE  => $data->{'CTM_ALS_ENTRY_ADD'}->{'TYPE'},
                            DATA  => $data->{'CTM_ALS_ENTRY_ADD'}->{'DATA'}
                        },
                        "{NAME}\t{CLASS}\t{TYPE}\t{DATA}\n"
                    ).
                    "; ctm als entries ENDING\n",
                $tplDbFileContent
            );
        } else {
            $tplDbFileContent = replaceBloc(
                "; ctm als entries BEGIN\n",
                "; ctm als entries ENDING\n",
                getBloc( "; ctm als entries BEGIN\n", "; ctm als entries ENDING\n", $wrkDbFileContent, 1 ),
                $tplDbFileContent
            );

            if (exists $data->{'CTM_ALS_ENTRY_DEL'}) {
                $tplDbFileContent =~ s/^$data->{'CTM_ALS_ENTRY_DEL'}->{'NAME'}\s+[^\n]*\n//m;
            }
        }
    }

    $tplDbFileContent = process(
        {
            DOMAIN_NAME => $data->{'DOMAIN_NAME'},
            IP_TYPE     => $net->getAddrVersion( $domainIp ) eq 'ipv4' ? 'A' : 'AAAA',
            DOMAIN_IP   => $domainIp
        },
        $tplDbFileContent
    );

    $rs = $self->{'eventManager'}->trigger( 'afterNamedAddDmnDb', \$tplDbFileContent, $data );
    $rs ||= $wrkDbFile->set( $tplDbFileContent );
    $rs ||= $wrkDbFile->save();
    return $rs if $rs;

    $rs = execute(
        'named-compilezone -i none -s relative'.
            " -o - $data->{'DOMAIN_NAME'} $wrkDbFile->{'filename'}".
            " > $self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db",
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    error( sprintf( 'Could not dump %s zone file', $wrkDbFile->{'filename'} ) ) if $rs && !$stderr;
    return $rs if $rs;

    my $prodFile = iMSCP::File->new( filename => "$self->{'config'}->{'BIND_DB_DIR'}/$data->{'DOMAIN_NAME'}.db" );
    $rs = $prodFile->mode( 0640 );
    $rs ||= $prodFile->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'BIND_GROUP'} );
}

=item _generateSoalSerialNumber($newDbFile [, $oldDbFile = undef ])

 Generate SOA Serial Number according RFC 1912

 Param string $newDbFile New DB file content
 Param string|undef $oldDbFile Old DB file content
 Return string|undef

=cut

sub _generateSoalSerialNumber
{
    my ($self, $newDbFile, $oldDbFile) = @_;

    $oldDbFile ||= $newDbFile;

    if (
        (my $tyear, my $tmon, my $tday, my $nn, my $placeholder) = (
            $oldDbFile =~ /^[\s]+(?:(\d{4})(\d{2})(\d{2})(\d{2})|(\{TIMESTAMP\}))/m
        )
    ) {
        my (undef, undef, undef, $day, $mon, $year) = localtime;
        my ($newSerial, $oldSerial);

        if ($placeholder) {
            $newSerial = sprintf( '%04d%02d%02d00', $year + 1900, $mon + 1, $day );
        } else {
            $oldSerial = "$tyear$tmon$tday$nn";
            $nn++;

            if ($nn >= 99) {
                $nn = 0;
                $tday++;
            }

            $newSerial = ((($year + 1900) * 10000 + ($mon + 1) * 100 + $day) > ($tyear * 10000 + $tmon * 100 + $tday))
                ? (sprintf '%04d%02d%02d00', $year + 1900, $mon + 1, $day)
                : (sprintf '%04d%02d%02d%02d', $tyear, $tmon, $tday, $nn);
        }

        $newDbFile =~ s/$oldSerial/$newSerial/ if defined $oldSerial;
        $newDbFile = process( { TIMESTAMP => $newSerial }, $newDbFile );
    } else {
        $newDbFile = undef;
    }

    $newDbFile;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
