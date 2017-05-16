=head1 NAME

 Servers::named::bind::uninstaller - i-MSCP Bind9 Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurente Declercq <l.declercq@nuxwin.com>
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

package Servers::named::bind::uninstaller;

use strict;
use warnings;
use File::Basename;
use iMSCP::Config;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::File;
use Servers::named::bind;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Uninstaller for the i-MSCP Bind9 Server implementation.

=head1 PUBLIC METHODS

=over 4

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    $self->_removeConfig( );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::named::bind::uninstaller

=cut

sub _init
{
    my ($self) = @_;

    $self->{'named'} = Servers::named::bind->getInstance( );
    $self->{'cfgDir'} = $self->{'named'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'vrlDir'} = "$self->{'cfgDir'}/imscp";
    $self->{'config'} = $self->{'named'}->{'config'};

    (tied %{$self->{'config'}})->{'temporary'} = 1;

    my $oldConf = "$self->{'cfgDir'}/bind.old.data";
    if (-f $oldConf) {
        tie my %oldConfig, 'iMSCP::Config', fileName => $oldConf, readonly => 1;
        while(my ($key, $value) = each(%oldConfig)) {
            next unless exists $self->{'config'}->{$key};
            $self->{'config'}->{$key} = $value;
        }
    }

    (tied %{$self->{'config'}})->{'temporary'} = 0;

    $self;
}

=item _removeConfig( )

 Remove configuration

 Return int 0 on success, other on failure

=cut

sub _removeConfig
{
    my ($self) = @_;

    for ('BIND_CONF_DEFAULT_FILE', 'BIND_CONF_FILE', 'BIND_LOCAL_CONF_FILE', 'BIND_OPTIONS_CONF_FILE') {
        next unless exists $self->{'config'}->{$_};

        my $dirname = dirname( $self->{'config'}->{$_} );
        next unless -d $dirname;

        my $filename = basename( $self->{'config'}->{$_} );
        next unless -f "$self->{'bkpDir'}/$filename.system";

        my $rs = iMSCP::File->new( filename => "$self->{'bkpDir'}/$filename.system" )->copyFile(
            $self->{'config'}->{$_}
        );
        $rs ||= iMSCP::File->new( filename => $self->{'config'}->{$_} )->mode( 0644 );
        return $rs if $rs;
    }

    if (-d $self->{'config'}->{'BIND_DB_DIR'}) {
        my $rs = execute( "rm -f $self->{'config'}->{'BIND_DB_DIR'}/*.db", \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;

        eval { iMSCP::Dir->new( dirname => "$self->{'config'}->{'BIND_DB_DIR'}/slave" )->remove( ); };
        if ($@) {
            error($@);
            return 1;
        }
    }

    if (-d $self->{'wrkDir'}) {
        my $rs = execute( "rm -f $self->{'wrkDir'}/*", \$stdout, \$stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
    }

    if (-f "$self->{'cfgDir'}/bind.old.data") {
        my $rs = iMSCP::File->new( filename => "$self->{'cfgDir'}/bind.old.data" )->delFile( );
        return $rs if $rs;
    }

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
