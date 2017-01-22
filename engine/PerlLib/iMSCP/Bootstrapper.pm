=head1 NAME

 iMSCP::Bootstrapper - i-MSCP Bootstrapper

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by internet Multi Server Control Panel
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
use Fcntl qw/ :flock /;
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Getopt;
use iMSCP::Umask;
use IO::Handle;
use locale;
use POSIX qw / tzset locale_h /;
use parent 'Common::SingletonClass';

$SIG{INT} = 'IGNORE';

umask 022;

setlocale(LC_ALL, 'C.UTF-8');
$ENV{'LC_ALL'} = 'C.UTF-8';
$ENV{'LANG'} = 'C.UTF-8';

$ENV{'PATH'} = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';
$ENV{'HOME'} = (getpwuid $>)[7] or die( 'Could not find running user homedir' );

=head1 DESCRIPTION

 Bootstrap class for i-MSCP

=head1 PUBLIC METHODS

=over 4

=item boot()

 Boot i-MSCP

 Return iMSCP::Bootstrapper

=cut

sub boot
{
    my ($self, $options) = @_;

    setDebug( 1 ); # Set debug mode for booting time

    my $mode = $options->{'mode'} || 'backend';
    debug( sprintf( 'Booting %s....', $mode ) );

    $self->lock() unless $options->{'nolock'};
    $self->loadMainConfig( $options );

    # Set timezone unless we are in setup mode (needed to show current local timezone in setup dialog)
    unless ($mode eq 'setup') {
        $ENV{'TZ'} = $main::imscpConfig{'TIMEZONE'} || 'UTC';
        tzset;
    }

    setDebug( iMSCP::Getopt->debug || $main::imscpConfig{'DEBUG'} || 0 ); # Set debug mode

    unless ($options->{'norequirements'}) {
        require iMSCP::Requirements;
        my $test = ($mode eq 'setup') ? 'all' : 'user';
        iMSCP::Requirements->new()->$test();
    }

    $self->_genKeys() unless $options->{'nokeys'};
    $self->_setDbSettings() unless $options->{'nodatabase'};

    iMSCP::EventManager->getInstance()->trigger( 'onBoot', $mode ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
    $self;
}

=item loadMainConfig(\%options)

 Load main configuration file using given options

 Param hashref \%options Options for iMSCP::Config object
 Return int 0 on success, die on failure

=cut

sub loadMainConfig
{
    my (undef, $options) = @_;

    require iMSCP::Config;
    untie %main::imscpConfig;
    tie
        %main::imscpConfig,
        'iMSCP::Config',
        fileName  => ($^O =~ /bsd$/ ? '/usr/local/etc/' : '/etc/').'imscp/imscp.conf',
        nodie    => $options->{'nodie'} // 0,
        nocreate  => $options->{'nocreate'} // 1,
        readonly  => $options->{'config_readonly'} // 0,
        temporary => $options->{'config_temporary'} // 0;
}

=item lock([ $lockFile [, $nowait ] ])

 Acquire an exclusive lock on the given file (default to /tmp/imscp.lock)

 Param bool $nowait Whether or not wait for lock
 Return int 1 if lock has been acquired, 0 if lock file has not been acquired (nowait case), die on failure

=cut

sub lock
{
    my $self = shift;
    my $lockFile = shift || '/tmp/imscp.lock';
    my $nowait = shift || 0;

    return 1 if exists $self->{'locks'}->{$lockFile};

    debug( sprintf( 'Acquire exclusive lock on %s', $lockFile ) );
    open $self->{'locks'}->{$lockFile}, '>', $lockFile or die( sprintf( 'Could not open %s file', $lockFile ) );
    my $rs = flock( $self->{'locks'}->{$lockFile}, $nowait ? LOCK_EX | LOCK_NB : LOCK_EX );
    $rs || $nowait or die( sprintf( 'Could not acquire exclusive lock on %s', $lockFile ) );
    $rs;
}

=item unlock([$lockFile])

 Unlock the given file (default to /tmp/imscp.lock)

 Return iMSCP::Bootstrapper

=cut

sub unlock
{
    my ($self, $lockFile) = (shift, shift || '/tmp/imscp.lock');

    return $self unless exists $self->{'locks'}->{$lockFile};

    debug( sprintf( 'Releasing exclusive lock on %s', $lockFile ) );
    flock( $self->{'locks'}->{$lockFile}, LOCK_UN ) or die(
        sprintf( 'Could not release exclusive lock on %s', $lockFile )
    );
    close $self->{'locks'}->{$lockFile};
    delete $self->{'locks'}->{$lockFile};
    unlink $lockFile;
    $self;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _genKeys()

 Generates encryption key and vector

 Return undef

=cut

sub _genKeys
{
    my $keyFile = "$main::imscpConfig{'CONF_DIR'}/imscp-db-keys";
    our $db_pass_key = '{KEY}';
    our $db_pass_iv = '{IV}';

    require "$keyFile" if -f $keyFile;

    if ($db_pass_key eq '{KEY}' || length($db_pass_key) != 32 || $db_pass_iv eq '{IV}' || length($db_pass_iv) != 16) {
        require iMSCP::Crypt;
        require Data::Dumper;

        debug( 'Generating database keys...' );

        -d $main::imscpConfig{'CONF_DIR'} or die(
            sprintf("%s doesn't exist or is not a directory", $main::imscpConfig{'CONF_DIR'} )
        );

        local $UMASK = 027; # imscp-db-keys file must not be created world-readable

        open my $fh, '>', "$main::imscpConfig{'CONF_DIR'}/imscp-db-keys" or die(
            sprintf('Could not open %s file for writing: %s', "$main::imscpConfig{'CONF_DIR'}/imscp-db-keys", $!)
        );

        print {$fh} Data::Dumper->Dump(
            [ iMSCP::Crypt::randomStr( 32 ), iMSCP::Crypt::randomStr( 16 ) ], [ qw/ db_pass_key db_pass_iv / ]
        );

        close $fh;
        delete $INC{$keyFile}; # Force reload of keyfile
        require "$keyFile";
    }

    $main::imscpDBKey = $db_pass_key;
    undef $db_pass_key;
    $main::imscpDBiv = $db_pass_iv;
    undef $db_pass_iv;
    undef;
}

=item _setDbSettings()

 Set database connection settings

 Return int 0 on success, die on failure

=cut

sub _setDbSettings
{
    require iMSCP::Database;
    require iMSCP::Crypt;

    my $database = iMSCP::Database->factory();
    $database->set( 'DATABASE_HOST', $main::imscpConfig{'DATABASE_HOST'} );
    $database->set( 'DATABASE_PORT', $main::imscpConfig{'DATABASE_PORT'} );
    $database->set( 'DATABASE_NAME', $main::imscpConfig{'DATABASE_NAME'} );
    $database->set( 'DATABASE_USER', $main::imscpConfig{'DATABASE_USER'} );
    $database->set(
        'DATABASE_PASSWORD',
        iMSCP::Crypt::decryptRijndaelCBC( $main::imscpDBKey, $main::imscpDBiv, $main::imscpConfig{'DATABASE_PASSWORD'} )
    );
    0;
}

=item END

 Process ending tasks (Release of lock files)

=cut

END {
    my $self = __PACKAGE__->getInstance();
    $self->unlock( $_ ) for keys %{$self->{'locks'}};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
