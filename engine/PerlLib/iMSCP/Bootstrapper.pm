=head1 NAME

 iMSCP::Bootstrapper - i-MSCP Bootstrapper

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package iMSCP::Bootstrapper;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::Requirements;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Database;
use Fcntl ':flock';
use POSIX qw(tzset);
use parent 'Common::SingletonClass';

$ENV{'PATH'} = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';

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

    my $mode = $options->{'mode'} || 'backend';
    debug( sprintf( 'Booting %s....', $mode ) );

    tie
        %main::imscpConfig,
        'iMSCP::Config',
        fileName => ($^O =~ /bsd$/ ? '/usr/local/etc/' : '/etc/').'imscp/imscp.conf',
        nocreate => 1, # Do not create file if it doesn't exist (raise error instead)
        nofail   => $options->{'nofail'} && $options->{'nofail'} eq 'yes' ? 1 : 0,
        readonly => $options->{'config_readonly'} && $options->{'config_readonly'} eq 'yes' ? 1 : 0;

    # Set timezone unless we are in setup mode (needed to show current local timezone in setup dialog)
    unless ($mode eq 'setup') {
        $ENV{'TZ'} = $main::imscpConfig{'TIMEZONE'} || 'UTC';
        tzset;
    }

    # Set debug mode
    setDebug( iMSCP::Getopt->debug || $main::imscpConfig{'DEBUG'} || 0 );

    unless ($options->{'norequirements'} && $options->{'norequirements'} eq 'yes') {
        my $test = ($mode eq 'setup') ? 'all' : 'user';
        iMSCP::Requirements->new()->$test();
    }

    $self->lock() unless $options->{'nolock'} && $options->{'nolock'} eq 'yes';
    $self->_genKeys() unless $options->{'nokeys'} && $options->{'nokeys'} eq 'yes';

    return $self if $options->{'nodatabase'} && $options->{'nodatabase'} eq 'yes';

    my $database = iMSCP::Database->factory();
    require iMSCP::Crypt;

    $database->set( 'DATABASE_HOST', $main::imscpConfig{'DATABASE_HOST'} );
    $database->set( 'DATABASE_PORT', $main::imscpConfig{'DATABASE_PORT'} );
    $database->set( 'DATABASE_NAME', $main::imscpConfig{'DATABASE_NAME'} );
    $database->set( 'DATABASE_USER', $main::imscpConfig{'DATABASE_USER'} );
    $database->set( 'DATABASE_PASSWORD', iMSCP::Crypt::decryptBlowfishCBC(
            $main::imscpDBKey, $main::imscpDBiv, $main::imscpConfig{'DATABASE_PASSWORD'}
        ) );
    my $rs = $database->connect();
    !$rs || ($options->{'nofail'} && $options->{'nofail'} eq 'yes') or die( sprintf(
            'Could not connect to the SQL server: %s', $rs
        ) );
    $self;
}

=item lock([$lockFile [, $nowait ]])

 Acquire an exclusive lock on the given file (default to /tmp/imscp.lock)

 Param bool $nowait Whether or not wait for lock
 Return int 1 on success, other on failure

=cut

sub lock
{
    my $self = shift;
    my $lockFile = shift || '/tmp/imscp.lock';
    my $nowait = shift || 0;

    return 1 if defined $self->{'locks'}->{$lockFile};

    debug( sprintf( 'Acquire exclusive lock on %s', $lockFile ) );
    open $self->{'locks'}->{$lockFile}, '>', $lockFile or die( sprintf( 'Could not open %s file file', $lockFile ) );
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

    return $self unless defined $self->{'locks'}->{$lockFile};

    debug( sprintf( 'Releasing exclusive lock on %s', $lockFile ) );
    flock( $self->{'locks'}->{$lockFile}, LOCK_UN ) or die( sprintf(
            'Could not release exclusive lock on %s', $lockFile
        ) );
    close $self->{'locks'}->{$lockFile};
    delete $self->{'locks'}->{$lockFile};
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
    my $self = shift;

    my $keyFile = "$main::imscpConfig{'CONF_DIR'}/imscp-db-keys";
    our $db_pass_key = '{KEY}';
    our $db_pass_iv = '{IV}';

    require "$keyFile" if -f $keyFile;

    if ($db_pass_key eq '{KEY}' || $db_pass_iv eq '{IV}') {
        debug( 'Generating database keys...' );

        if (-d $main::imscpConfig{'CONF_DIR'}) {
            require iMSCP::Crypt;
            require Data::Dumper;
            Data::Dumper->import();

            open( my $fh, '>:utf8', "$main::imscpConfig{'CONF_DIR'}/imscp-db-keys" )
                or die( "Error: Unable to open file '$main::imscpConfig{'CONF_DIR'}/imscp-db-keys' for writing: $!" );

            print {$fh} Data::Dumper->Dump(
                    [ iMSCP::Crypt::randomStr( 32 ), iMSCP::Crypt::randomStr( 8 ) ], [ qw(db_pass_key db_pass_iv) ]
                );

            close $fh;
        } else {
            die( "Destination path $main::imscpConfig{'CONF_DIR'} doesn't exist or is not a directory" );
        }

        require "$keyFile";
    }

    $main::imscpDBKey = $db_pass_key;
    $main::imscpDBiv = $db_pass_iv;
    undef;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
