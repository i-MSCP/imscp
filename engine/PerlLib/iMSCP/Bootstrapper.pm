=head1 NAME

 iMSCP::Bootstrapper - i-MSCP Bootstrapper

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

package iMSCP::Bootstrapper;

use strict;
use warnings;
use File::Basename 'fileparse';
use File::Spec;
use iMSCP::Boolean;
use iMSCP::Debug qw/ debug getMessageByType setDebug /;
use iMSCP::EventManager;
use iMSCP::Getopt;
use iMSCP::LockFile;
use iMSCP::Umask '$UMASK';
use POSIX 'tzset';
use parent 'Common::SingletonClass';

$SIG{'INT'} = 'IGNORE';
$ENV{'HOME'} = ( getpwuid $> )[7] or die( "Couldn't find running user homedir" );

umask 022;

=head1 DESCRIPTION

 Bootstrap class for i-MSCP

=head1 PUBLIC METHODS

=over 4

=item boot( \%options )

 Boot i-MSCP

 Param hashref \%options Bootstrap options
 Return iMSCP::Bootstrapper

=cut

sub boot
{
    my ( $self, $options ) = @_;

    # Sets execution mode if needed
    $::execmode = 'backend' unless defined $::execmode;

    setDebug( TRUE ); # Set debug mode for booting time
    debug( sprintf( 'Booting backend....' ));

    $self->lock() unless $options->{'nolock'};
    $self->loadMainConfig( $options );

    # Set timezone unless we are in setup or uninstall modes (needed to show current local timezone in setup dialog)
    unless ( grep ( $::execmode eq $_, ( 'setup', 'uninstaller' ) ) ) {
        $ENV{'TZ'} = $::imscpConfig{'TIMEZONE'} || 'UTC';
        tzset;
    }

    setDebug( iMSCP::Getopt->debug || $::imscpConfig{'DEBUG'} || 0 ); # Set debug mode

    unless ( $options->{'norequirements'} ) {
        require iMSCP::Requirements;
        my $test = $::execmode eq 'setup' ? 'all' : 'user';
        iMSCP::Requirements->new()->$test();
    }

    iMSCP::EventManager->getInstance()->trigger( 'onBoot' ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error'
    );
    $self;
}

=item loadMainConfig( \%options )

 Load main configuration file using given options

 Param hashref \%options Options for iMSCP::Config object
 Return int 0 on success, die on failure

=cut

sub loadMainConfig
{
    my ( undef, $options ) = @_;

    require iMSCP::Config;
    untie %::imscpConfig;
    tie
        %::imscpConfig,
        'iMSCP::Config',
        fileName    => ( $^O =~ /bsd$/ ? '/usr/local/etc/' : '/etc/' ) . 'imscp/imscp.conf',
        nocreate    => $options->{'nocreate'} // TRUE,
        nodeferring => $options->{'nodeferring'} // FALSE,
        nodie       => $options->{'nodie'} // FALSE,
        readonly    => $options->{'config_readonly'} // FALSE,
        temporary   => $options->{'config_temporary'} // FALSE;
}

=item lock( [ $file = '/var/lock/imscp.lock [, $nowait = FALSE ] ] )

 Lock a file

 Param string $file Lock file Either a lock file name or full lock file path
 Param bool $nowait OPTIONAL Whether or not to wait for lock (Default: FALSE)
 Return int 1 if lock file has been acquired, 0 if lock file has not been acquired (nowait case), die on failure

=cut

sub lock
{
    my ( $self, $file, $nowait ) = @_;
    $file //= 'imscp.lock';
    $nowait //= FALSE;

    ( $file, my $path ) = fileparse( $file );

    if ( $path eq './' ) {
        $path = '/var/lock';
    } elsif ( index( $path, '/' ) != 0 ) {
        $path = File::Spec->catdir( '/var/lock', $path );
    }

    $file = File::Spec->catfile( $path, $file );

    return 1 if exists $self->{'locks'}->{$file};

    my $lock = iMSCP::LockFile->new( path => $file, non_blocking => $nowait );
    my $ret = $lock->acquire();
    $self->{'locks'}->{$file} = $lock if $ret;
    $ret;
}

=item unlock( [ $lockFile = /var/lock/imscp.lock ] )

 Unlock file

 Param string $lockFile OPTIONAL Lock file path
 Return iMSCP::Bootstrapper

=cut

sub unlock
{
    my ( $self, $lockFile ) = @_;
    $lockFile = File::Spec->canonpath( $lockFile ||= '/var/lock/imscp.lock' );

    return $self unless exists $self->{'locks'}->{$lockFile};

    $self->{'locks'}->{$lockFile}->release();
    delete $self->{'locks'}->{$lockFile};
    $self;
}

=back

=head1 PRIVATE METHODS

=over 4

=item END

 Process ending tasks (Release lock on files)

=cut

END {
    my $self = __PACKAGE__->getInstance();
    $self->{'locks'}->{$_}->release() for keys %{ $self->{'locks'} };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
