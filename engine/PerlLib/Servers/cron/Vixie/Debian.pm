=head1 NAME

 Servers::cron::Vixie::Debian - i-MSCP (Debian) Vixie cron server abstract implementation

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA. 

package Servers::cron::Vixie::Debian;

use strict;
use warnings;
use autouse 'iMSCP::Rights' => qw/ setRights /;
use Class::Autouse qw/ :nostat iMSCP::Service /;
use iMSCP::Debug qw/ error /;
use iMSCP::File;
use parent 'Servers::cron::Abstract';

=head1 DESCRIPTION

 i-MSCP (Debian) Vixie cron server abstract implementation.
 
 See CRON(8) manpage.
 
=head1 PUBLIC METHODS

=over 4

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    eval { iMSCP::Service->getInstance()->stop( 'cron' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'cron', 'imscp', \ my $cfgTpl, {} );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => '/etc/imscp/cron/imscp' )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read %s", '/etc/imscp/cron/imscp' ));
            return 1;
        }
    }

    processByRef(
        {
            QUOTA_ROOT_DIR  => $main::imscpConfig{'QUOTA_ROOT_DIR'},
            LOG_DIR         => $main::imscpConfig{'LOG_DIR'},
            TRAFF_ROOT_DIR  => $main::imscpConfig{'TRAFF_ROOT_DIR'},
            TOOLS_ROOT_DIR  => $main::imscpConfig{'TOOLS_ROOT_DIR'},
            BACKUP_MINUTE   => $main::imscpConfig{'BACKUP_MINUTE'},
            BACKUP_HOUR     => $main::imscpConfig{'BACKUP_HOUR'},
            BACKUP_ROOT_DIR => $main::imscpConfig{'BACKUP_ROOT_DIR'},
            CONF_DIR        => $main::imscpConfig{'CONF_DIR'},
            BACKUP_FILE_DIR => $main::imscpConfig{'BACKUP_FILE_DIR'}
        },
        \$cfgTpl
    );

    my $file = iMSCP::File->new( filename => "/etc/cron.d/imscp" );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0640 );

    $rs || $self->_cleanup();
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    eval { iMSCP::Service->getInstance()->enable( 'cron' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]},
                [
                    sub {
                        iMSCP::Service->getInstance()->enable( 'cron' )->start( 'cron' );
                        0;
                    },
                    'Cron'
                ];
            0;
        },
        -99
    );
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    return 0 unless -f "/etc/cron.d/imscp";

    setRights( "/etc/cron.d//imscp",
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => '0640'
        }
    );
}

=item addTask( \%data [, $filepath = '/etc/cron.d/imscp' ] )

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
    my ($self, $data, $filepath) = @_;
    $data = {} unless ref $data eq 'HASH';

    unless ( exists $data->{'COMMAND'} && exists $data->{'TASKID'} ) {
        error( 'Missing command or task ID' );
        return 1;
    }

    $filepath ||= '/etc/cron.d/imscp';

    $data->{'MINUTE'} //= '@daily';
    $data->{'HOUR'} //= '*';
    $data->{'DAY'} //= '*';
    $data->{'MONTH'} //= '*';
    $data->{'DWEEK'} //= '*';
    $data->{'USER'} //= $main::imscpConfig{'ROOT_USER'};

    eval { $self->_validateCronTask( $data ); };
    if ( $@ ) {
        error( sprintf( 'Invalid cron tasks: %s', $@ ));
        return 1;
    }

    my $file = iMSCP::File->new( filename => $filepath );
    my $fileContentRef = \ '';

    if ( -f $filepath ) {
        $fileContentRef = $file->getAsRef();
        unless ( defined $fileContentRef ) {
            error( sprintf( "Couldn't read the %s file", $file->{'filename'} ));
            return 1;
        }

        # Remove entry with same ID if any
        replaceBlocByRef(
            qr/^\s*\Q# imscp [$data->{'TASKID'}] entry BEGIN\E\n/m, qr/\Q# imscp [$data->{'TASKID'}] entry ENDING\E\n/, '', $fileContentRef
        );
    } else {
        ${$fileContentRef} = <<'EOF';
# CRON(8) configuration file - auto-generated by i-MSCP
#     DO NOT EDIT THIS FILE BY HAND -- YOUR CHANGES WILL BE OVERWRITTEN

PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

EOF
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeCronAddTask', $fileContentRef, $data );
    return $rs if $rs;

    ( ${$fileContentRef} .= <<"EOF" ) =~ s/^(\@[^\s]+)\s+/$1 /gm;

# imscp [$data->{'TASKID'}] entry BEGIN
$data->{'MINUTE'} $data->{'HOUR'} $data->{'DAY'} $data->{'MONTH'} $data->{'DWEEK'} $data->{'USER'} $data->{'COMMAND'}
# imscp [$data->{'TASKID'}] entry ENDING
EOF

    $rs = $self->{'eventManager'}->trigger( 'afterCronAddTask', $fileContentRef, $data );
    $rs ||= $file->save();
}

=item deleteTask( \%data [, $filepath = '/etc/cron.d/imscp' ] )

 Delete a cron task

 Param hash \%data Cron task data:
  - TASKID Cron task unique identifier
 Param string $filepath OPTIONAL Cron file path (default: imscp cron file)
 Return int 0 on success, other on failure

=cut

sub deleteTask
{
    my ($self, $data, $filepath) = @_;
    $data = {} unless ref $data eq 'HASH';

    unless ( exists $data->{'TASKID'} ) {
        error( 'Missing task ID' );
        return 1;
    }

    $filepath ||= '/etc/cron.d/imscp';
    return 0 unless -f $filepath;

    my $file = iMSCP::File->new( filename => $filepath );
    my $fileContentRef = $file->getAsRef();
    unless ( defined $fileContentRef ) {
        error( sprintf( "Couldn't read the %s file", $file->{'filename'} ));
        return 1;
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeCronDeleteTask', $fileContentRef, $data );
    return $rs if $rs;

    replaceBlocByRef(
        qr/^\s*\Q# imscp [$data->{'TASKID'}] entry BEGIN\E\n/m, qr/\Q# imscp [$data->{'TASKID'}] entry ENDING\E\n/, '', $fileContentRef
    );

    $rs = $self->{'eventManager'}->trigger( 'afterCronDeleteTask', $fileContentRef, $data );
    $rs ||= $file->save();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _cleanup

 Process cleanup tasks

 Return int 0 on success, other on failure

=cut

sub _cleanup
{
    return 0 unless '/etc/imscp/cron/cron.data';

    iMSCP::File->new( filename => '/etc/imscp/cron/cron.data' )->delFile();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
