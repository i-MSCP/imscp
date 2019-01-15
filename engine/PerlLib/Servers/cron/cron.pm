=head1 NAME

 Servers::cron::cron - i-MSCP Cron server implementation

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

package Servers::cron::cron;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Config;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::Rights 'setRights';
use iMSCP::TemplateParser qw/ process replaceBloc /;
use iMSCP::Service;
use Try::Tiny;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Cron server implementation.

=head1 PUBLIC METHODS

=over 4

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeCronPreinstall', 'cron' );
        return $rs if $rs;

        #iMSCP::Service->getInstance()->stop( 'cron' );
        $self->{'eventManager'}->trigger( 'afterCronPreinstall', 'cron' );
    } catch {
        error( $_ );
        1;
    };
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeCronInstall', 'cron' );
        $rs ||= $self->{'eventManager'}->trigger( 'onLoadTemplate', 'cron', 'imscp', \my $cfgTpl, {} );
        return $rs if $rs;

        my $file = iMSCP::File->new( filename => "$self->{'cfgDir'}/imscp" );

        unless ( defined $cfgTpl ) {
            $cfgTpl = $file->getAsRef();
            return 1 unless defined $cfgTpl;
        } else {
            $file->set( $cfgTpl );
            $cfgTpl = $file->getAsRef();
        }

        ${ $cfgTpl } = process(
            {
                BACKUP_CLIENTS_HOUR   => $::imscpConfig{'BACKUP_CLIENTS_HOUR'},
                BACKUP_CLIENTS_MINUTE => $::imscpConfig{'BACKUP_CLIENTS_MINUTE'},
                BACKUP_CP_HOUR        => $::imscpConfig{'BACKUP_CP_HOUR'},
                BACKUP_CP_MINUTE      => $::imscpConfig{'BACKUP_CP_MINUTE'},
                CONF_DIR              => $::imscpConfig{'CONF_DIR'},
                ROOT_DIR              => $::imscpConfig{'ROOT_DIR'},
                ENGINE_ROOT_DIR       => $::imscpConfig{'ENGINE_ROOT_DIR'},
                LOG_DIR               => $::imscpConfig{'LOG_DIR'}
            },
            ${ $cfgTpl }
        );

        $file->{'filename'} = "$self->{'config'}->{'CRON_D_DIR'}/imscp";
        $rs = $file->save();
        $rs ||= $file->owner( $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'} );
        $rs ||= $file->mode( 0640 );
        $rs ||= $self->{'eventManager'}->trigger( 'afterCronInstall', 'cron' );
    } catch {
        error( $_ );
        1;
    };
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeCronPostInstall', 'cron' );
        return $rs if $rs;

        my $srvMngr = iMSCP::Service->getInstance();
        $srvMngr->enable( $self->{'config'}->{'CRON_SNAME'} );
        $rs = $self->{'eventManager'}->register(
            'beforeSetupRestartServices',
            sub {
                push @{ $_[0] },
                    [
                        sub {
                            $srvMngr->start( $self->{'config'}->{'CRON_SNAME'} );
                            0;
                        },
                        'Cron'
                    ];
                0;
            },
            -99
        );
        $rs ||= $self->{'eventManager'}->trigger( 'afterCronPostInstall', 'cron' );
    } catch {
        error( $_ );
        1;
    };
}

=item addTask( \%data [, $filepath = $self->{'config'}->{'CRON_D_DIR'}/imscp ] )

 Add a new cron task

 Param hash \%data Cron task data:
  - TASKID :Cron task unique identifier
  - MINUTE  : OPTIONAL Minute or shortcut such as @daily, @monthly... (Default: @daily)
  - HOUR    : OPTIONAL Hour - ignored if the MINUTE field defines a shortcut (Default: *)
  - DAY     : OPTIONAL Day of month - ignored if the MINUTE field defines a shortcut (Default: *)
  - MONTH   : OPTIONAL Month - ignored if the MINUTE field defines a shortcut - Default (Default: *)
  - DWEEK   : OPTIONAL Day of week - ignored if the MINUTE field defines a shortcut - (Default: *)
  - USER    : OPTIONAL Use under which the command must be run (default: root)
  - COMMAND : Command to run
  Param string $filepath OPTIONAL Cron file path (default: imscp cron file)
  Return int 0 on success, other on failure

=cut

sub addTask
{
    my ( $self, $data, $filepath ) = @_;

    try {
        $data = {} unless ref $data eq 'HASH';
        unless ( exists $data->{'COMMAND'} && exists $data->{'TASKID'} ) {
            error( 'Missing command or task ID' );
            return 1;
        }

        $filepath ||= "$self->{'config'}->{'CRON_D_DIR'}/imscp";
        $data->{'MINUTE'} //= '@daily';
        $data->{'HOUR'} //= '*';
        $data->{'DAY'} //= '*';
        $data->{'MONTH'} //= '*';
        $data->{'DWEEK'} //= '*';
        $data->{'USER'} //= $::imscpConfig{'ROOT_USER'};

        $self->_validateCronTask( $data );

        my $file = iMSCP::File->new( filename => $filepath );
        my $fileC;

        if ( -f $filepath ) {
            $fileC = $file->getAsRef();
            return 1 unless defined $fileC;

            # Remove entry with same ID if any
            ${ $fileC } = replaceBloc(
                qr/(:?^\n)?\Q# imscp [$data->{'TASKID'}] entry BEGIN\E\n/m, qr/\Q# imscp [$data->{'TASKID'}] entry ENDING\E\n/, '', ${ $fileC }
            );
        } else {
            $file->set( <<'EOF' );
# CRON(8) configuration file - auto-generated by i-MSCP
#     DO NOT EDIT THIS FILE BY HAND -- YOUR CHANGES WILL BE OVERWRITTEN

PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

EOF
            $fileC = $file->getAsRef();
        }

        my $rs = $self->{'eventManager'}->trigger( 'beforeCronAddTask', $fileC, $data );
        return $rs if $rs;

        ( ${ $fileC } .= <<"EOF" ) =~ s/^(\@[^\s]+)\s+/$1 /gm;

# imscp [$data->{'TASKID'}] entry BEGIN
$data->{'MINUTE'} $data->{'HOUR'} $data->{'DAY'} $data->{'MONTH'} $data->{'DWEEK'} $data->{'USER'} $data->{'COMMAND'}
# imscp [$data->{'TASKID'}] entry ENDING
EOF
        $rs = $self->{'eventManager'}->trigger( 'afterCronAddTask', $fileC, $data );
        return $rs if $rs;

        $file->save();
    } catch {
        error( $_ );
        1;
    };
}

=item deleteTask( \%data [, $filepath = $self->{'config'}->{'CRON_D_DIR'}/imscp ] )

 Delete a cron task

 Param hash \%data Cron task data:
  - TASKID Cron task unique identifier
 Param string $filepath OPTIONAL Cron file path (default: imscp cron file)
 Return int 0 on success, other on failure

=cut

sub deleteTask
{
    my ( $self, $data, $filepath ) = @_;

    try {
        $data = {} unless ref $data eq 'HASH';
        unless ( exists $data->{'TASKID'} ) {
            error( 'Missing task ID' );
            return 1;
        }

        $filepath ||= "$self->{'config'}->{'CRON_D_DIR'}/imscp";
        return 0 unless -f $filepath;

        my $file = iMSCP::File->new( filename => $filepath );
        my $fileC = $file->getAsRef();
        return 1 unless defined $fileC;

        my $rs = $self->{'eventManager'}->trigger( 'beforeCronDelTask', $fileC, $data );
        return $rs if $rs;

        ${ $fileC } = replaceBloc(
            qr/(:?^\n)?\Q# imscp [$data->{'TASKID'}] entry BEGIN\E\n/m, qr/\Q# imscp [$data->{'TASKID'}] entry ENDING\E\n/, '', ${ $fileC }
        );
        $rs = $self->{'eventManager'}->trigger( 'afterCronDelTask', $fileC, $data );
        $rs ||= $file->save();
    } catch {
        error( $_ );
        1;
    };
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ( $self ) = @_;

    return 0 unless -f "$self->{'config'}->{'CRON_D_DIR'}/imscp";

    setRights( "$self->{'config'}->{'CRON_D_DIR'}/imscp", {
        user  => $::imscpConfig{'ROOT_USER'},
        group => $::imscpConfig{'ROOT_GROUP'},
        mode  => '0640'
    } );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::cron::cron

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = "$::imscpConfig{'CONF_DIR'}/cron";
    $self->_mergeConfig() if -f "$self->{'cfgDir'}/cron.data.dist";
    tie %{ $self->{'config'} },
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/cron.data",
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

    if ( -f "$self->{'cfgDir'}/cron.data" ) {
        tie my %newConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/cron.data.dist";
        tie my %oldConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/cron.data", readonly => TRUE;
        debug( 'Merging old configuration with new configuration...' );
        while ( my ( $key, $value ) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }
        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$self->{'cfgDir'}/cron.data.dist" )->moveFile( "$self->{'cfgDir'}/cron.data" ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error'
    );
}

=item _validateCronTask( )

 Validate cron task attributes

 die if an attribute is not valid

=cut

sub _validateCronTask
{
    my ( $self, $data ) = @_;

    if ( $data->{'MINUTE'} =~ /^\@(?:reboot|yearly|annually|monthly|weekly|daily|midnight|hourly)$/ ) {
        $data->{'HOUR'} = $data->{'DAY'} = $data->{'MONTH'} = $data->{'DWEEK'} = '';
        return;
    }

    for my $attribute ( qw/ minute hour day month dweek / ) {
        $self->_validateField( $attribute, $data->{ uc( $attribute ) } );
    }
}

=item _validateField( )

 Validate the given cron task field value

 Param string $name Attribute name
 Param string $value Attribute value
 Return void, die if an attribute is not valid

=cut

sub _validateField
{
    my ( undef, $name, $value ) = @_;

    length $name or die( 'Missing or invalid $name parameter' );
    defined $value or die( 'Missing or invalid $value parameter' );

    return if $value eq '*';

    $value ne '' or die( sprintf( "Value for the '%s' cron task field cannot be empty", $name ));

    my $step = '[1-9]?[0-9]';
    my $months = 'jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec';
    my $days = 'mon|tue|wed|thu|fri|sat|sun';
    my @namesArr = ();
    my $pattern;

    if ( $name eq 'minute' ) {
        $pattern = '[ ]*(\b[0-5]?[0-9]\b)[ ]*';
    } elsif ( $name eq 'hour' ) {
        $pattern = '[ ]*(\b[01]?[0-9]\b|\b2[0-3]\b)[ ]*';
    } elsif ( $name eq 'day' ) {
        $pattern = '[ ]*(\b[01]?[1-9]\b|\b2[0-9]\b|\b3[01]\b)[ ]*';
    } elsif ( $name eq 'month' ) {
        @namesArr = split '|', $months;
        $pattern = "([ ]*(\b[0-1]?[0-9]\b)[ ]*)|([ ]*($months)[ ]*)";
    } elsif ( $name eq 'dweek' ) {
        @namesArr = split '|', $days;
        $pattern = "([ ]*(\b[0]?[0-7]\b)[ ]*)|([ ]*($days)[ ]*)";
    }

    defined $pattern or die( sprintf( "Unknown '%s' cron task field", $name ));

    my $range = "((($pattern)|(\\*\\/$step)?)|((($pattern)-($pattern))(\\/$step)?))";
    my $longPattern = "$range(,$range)*";
    $value =~ /^$longPattern$/i or die( sprintf( "Invalid value '%s' given for the '%s' cron task field", $value, $name ));

    for my $testField ( split ',', $value ) {
        next if $testField !~ /^((($pattern)-($pattern))(\/$step)?)+$/;
        my @compare = split '-', $testField;
        my @compareSlash = split '/', $compare['1'];
        $compare[1] = $compareSlash[0] if scalar @compareSlash == 2;
        my ( $left ) = grep { $namesArr[$_] eq lc( $compare[0] ) } 0 .. $#namesArr;
        my ( $right ) = grep { $namesArr[$_] eq lc( $compare[1] ) } 0 .. $#namesArr;
        $left = $compare[0] unless $left;
        $right = $compare[1] unless $right;
        die( sprintf( "Invalid value '%s' given for the '%s' cron task field", $value, $name )) if int( $left ) > int( $right );
    }

    undef;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
