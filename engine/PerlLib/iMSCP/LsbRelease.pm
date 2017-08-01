=head1 NAME

 iMSCP::LsbRelease - Provides distribution-specific information

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package iMSCP::LsbRelease;

use strict;
use warnings;
use IPC::Open3;
use POSIX;
use Symbol;

my $IS_DEVUAN = -f '/etc/devuan_version';

# XXX: Update as needed
# This should really be included in apt-cache policy output... it is already
# in the Release file...
my %RELEASE_CODENAME_LOOKUP = ( !$IS_DEVUAN )
      ? (
        '1.1' => 'buzz',
        '1.2' => 'rex',
        '1.3' => 'bo',
        '2.0' => 'hamm',
        '2.1' => 'slink',
        '2.2' => 'potato',
        '3.0' => 'woody',
        '3.1' => 'sarge',
        '4.0' => 'etch',
        '5.0' => 'lenny',
        '6.0' => 'squeeze',
        '7'   => 'wheezy',
        '8'   => 'jessie',
        '9'   => 'stretch',
        '10'  => 'buster',
        '11'  => 'bullseye'
    ) : (
        '1' => 'jessie',
        '2' => 'ascii'
    );

my $TESTING_CODENAME = 'unknown.new.testing';

my @RELEASES_ORDER = (
    ( map { $RELEASE_CODENAME_LOOKUP{$_} } sort keys %RELEASE_CODENAME_LOOKUP ),
    'stable', 'testing', 'unstable', ( ( $IS_DEVUAN ) ? 'ceres' : 'sid' )
);

=head1 DESCRIPTION

 This class provides distribution-specific information as provided by the lsb_release command.

=head1 PUBLIC METHODS

=over 4

=item getInstance( )

 Create and return instance of this class

 Return iMSCP::LsbRelease

=cut

sub getInstance
{
    my ($self) = @_;
    return $self if ref $self;

    no strict 'refs';
    my $instance = \${"$self\::_instance"};
    unless ( defined ${$instance} ) {
        ${$instance} = bless {}, $self;
        %{${$instance}->{'lsbInfo'}} = ${$instance}->getDistroInformation();
    }

    ${$instance};
}

=item getId( [ $short = false ] )

 Get distributor ID

 Param bool $short OPTIONAL Weither or not short value must be returned (default FALSE)
 Return string

=cut

sub getId
{
    my ($self, $short) = @_;

    return $self->{'lsbInfo'}->{'ID'} || 'n/a' if $short;
    sprintf( "Distributor ID:\t%s", $self->{'lsbInfo'}->{'ID'} || 'n/a' );
}

=item getDescription( [ $short = false ] )

 Get description

 Param bool $short OPTIONAL Weither or not short value must be returned (default FALSE)
 Return string

=cut

sub getDescription
{
    my ($self, $short) = @_;

    return $self->{'lsbInfo'}->{'DESCRIPTION'} || 'n/a' if $short;
    sprintf( "Description:\t%s", $self->{'lsbInfo'}->{'DESCRIPTION'} || 'n/a' );
}

=item getRelease( [ $short = false [, forceNumeric = false ] ] )

 Get release

 Param bool $short OPTIONAL Weither or not short value must be returned (default FALSE)
 Param bool $forceNumeric Weither or not to force numeric value when possible (default FALSE)
 Return string

=cut

sub getRelease
{
    my ($self, $short, $forceNumeric) = @_;

    my $release = $self->{'lsbInfo'}->{'RELEASE'} || 'n/a';

    if ( $forceNumeric && $release =~ /[^\d.]/ ) {
        my $codename = $self->getCodename( 1 );
        my %lookup = reverse( %RELEASE_CODENAME_LOOKUP );
        $release = sprintf( '%.1f', $lookup{$codename} ) if exists $lookup{$codename};
    }

    return $release if $short;
    sprintf( "Release:\t%s", $self->{'lsbInfo'}->{'RELEASE'} || 'n/a' );
}

=item getCodename( [ $short = false ] )

 Get codename

 Param bool $short OPTIONAL Weither or not short value must be returned (default FALSE)
 Return string

=cut

sub getCodename
{
    my ($self, $short) = @_;

    return $self->{'lsbInfo'}->{'CODENAME'} || 'n/a' if $short;
    sprintf( "Codename:\t%s", $self->{'lsbInfo'}->{'CODENAME'} || 'n/a' );
}

=item getAll( [ $short = false ] )

 Get all distribution-specific information

 Param bool $short OPTIONAL Weither or not short value must be returned (default FALSE)
 Return string

=cut

sub getAll
{
    my ($self, $short) = @_;

    sprintf(
        "%s\n%s\n%s\n%s",
        $self->getId( $short ),
        $self->getDescription( $short ),
        $self->getRelease( $short ),
        $self->getCodename( $short )
    );
}

=item getDistroInformation( )

 Get distribution information

    Data are returned in hash such as:

    (
        'ID'          => 'Debian',
        'RELEASE'     => '8.7',
        'DESCRIPTION' => 'Debian GNU/Linux 8.7 (jessie)',
        'CODENAME'    => 'jessie'
    )

 Return hash Hash containing distribution information

=cut

sub getDistroInformation
{
    my ($self) = @_;

    # Try to retrieve information from /etc/lsb-release first
    my %lsbInfo = $self->_getLsbInformation();

    for ( 'ID', 'RELEASE', 'CODENAME', 'DESCRIPTION' ) {
        next if exists $lsbInfo{$_};
        my %distInfo = $self->_guessDebianRelease();
        %lsbInfo = ( %distInfo, %lsbInfo );
        last;
    }

    %lsbInfo;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _lookupCodename( $release [, $unknown = undef ] )

 Lookup distribution codename

 Return string Distribution codename if found or $unknown value

=cut

sub _lookupCodename
{
    my (undef, $release, $unknown) = @_;

    return $unknown unless $release =~ /(\d+)\.(\d+)(r(\d+))?/;

    my $shortRelease = ( !$IS_DEVUAN && int( $1 ) < 7 ) ? sprintf '%s.%s', $1, $2 : sprintf '%s', $1;
    $RELEASE_CODENAME_LOOKUP{$shortRelease} || $unknown;
}

=item _parsePolicyLine( $data )

 Parse a line from the apt-cache policy command output to retrieve distribution version, origin, suite, component and
 label field value

 Return hash

=cut

# map short field names to long field names
my %longnames = ( v => 'version', o => 'origin', a => 'suite', c => 'component', l => 'label' );

sub _parsePolicyLine
{
    my @bits = split ',', $_[1];

    my %retval = ();
    for( @bits ) {
        my @kv = split '=', $_, 2;
        $retval{$longnames{$kv[0]}} = $kv[1] if @kv > 1 && exists $longnames{$kv[0]};
    }

    %retval;
}

=item _releaseIndex( )

 Get release index if any

 Return string|int

=cut

sub _releaseIndex
{
    my $suite = $_[1]->{'suite'} || undef;

    if ( $suite ) {
        return grep($_ eq $suite, @RELEASES_ORDER)
            ? int( @RELEASES_ORDER-( grep { $RELEASES_ORDER[$_] eq $suite } 0 .. $#RELEASES_ORDER )[0] ) : $suite;
    }

    0;
}

=item _parseAptPolicy( )

 Parse output from apt-cache policy command

 Return array

=cut

sub _parseAptPolicy
{
    my $self = $_[0];

    my ($in, $out, $err) = ( undef, undef, gensym() );
    my $pid = open3( $in, $out, $err, 'LANG=C apt-cache policy' );
    close $in;

    my $stdout = do {
        local $/;
        <$out>
    };
    my $stderr = do {
        local $/;
        <$err>
    };

    close $out;
    close $err;
    waitpid( $pid, 0 ) or die "$!\n";
    die( sprintf( "Couldn't parse APT policy: %s", $stderr || 'Unknown error' )) if $?;

    my @data = ();
    my $priority;

    for( split /\n/, $stdout ) {
        s/^\s+|\s+$//g; # Remove leading and trailing whitespaces
        $priority = int( $1 ) if /^(\d+)/;
        if ( index( $_, 'release' ) == 0 ) {
            my @bits = split ' ', $_, 2;
            push @data, [ $priority, { $self->_parsePolicyLine( $bits[1] ) } ] if @bits > 1;
        }
    }

    @data;
}

=item _guessReleaseFromApt(
    [ $origin = 'Debian' [, $component = 'main' [, $ignoresuites = [ 'experimental' ] [, $label = 'Debian'
    [, $alternateOlabels = { 'Debian Ports' => 'ftp.debian-ports.org' } ] ] ] ] ]
)

 Retrieve distribution information by parsing output from the apt-cache policy command

 Return hash

=cut

sub _guessReleaseFromApt
{
    my ($self, $origin, $component, $ignoresuites, $label, $alternateOlabels) = @_;

    $origin ||= 'Debian';
    $component ||= 'main';
    $ignoresuites ||= [ 'experimental' ];
    $label ||= 'Debian';
    $alternateOlabels ||= { 'Debian Ports' => 'ftp.debian-ports.org' };

    my @releases = $self->_parseAptPolicy();

    return undef unless @releases;

    # We only care about the specified origin, component, and label
    @releases = grep {
        (
            ( $_->[1]->{'origin'} || '' ) eq $origin and
                ( $_->[1]->{'component'} || '' ) eq $component and
                ( $_->[1]->{'label'} || '' ) eq $label
        ) or (
            exists $alternateOlabels->{$_->[1]->{'origin'} || ''} and
                ( $_->[1]->{'label'} || '' ) eq $alternateOlabels->{( $_->[1]->{'origin'} || '' )}
        )
    } @releases;

    # Check again to make sure we didn't wipe out all of the releases
    return undef unless @releases;

    @releases = sort { $b->[0] <=> $a->[0] } @releases;

    # We've sorted the list by descending priority, so the first entry should
    # be the "main" release in use on the system

    my $maxPriority = $releases[0]->[0];
    @releases = grep { $_->[0] == $maxPriority } @releases;
    @releases = sort { $self->_releaseIndex( $a->[1] ) cmp $self->_releaseIndex( $b->[1] ) } @releases;
    %{$releases[0]->[1]};
}

=item _guessDebianRelease( )

 Return Debian distribution-specific information

 Return hash

=cut

sub _guessDebianRelease
{
    my $self = $_[0];

    my %distInfo = ( ID => ( $IS_DEVUAN ) ? 'Devuan' : 'Debian' );

    # Use /etc/dpkg/origins/default to fetch the distribution name
    my $etcDpkgOriginsDefauft = $ENV{'LSB_ETC_DPKG_ORIGINS_DEFAULT'} || '/etc/dpkg/origins/default';

    if ( -f $etcDpkgOriginsDefauft ) {
        if ( open my $fh, '<', $etcDpkgOriginsDefauft ) {
            while ( my $line = <$fh> ) {
                my ($header, $content) = split ':', $line, 2;

                $header = lc( $header );
                $content =~ s/^\s+|\s+$//g;

                if ( $header eq 'vendor' ) {
                    $distInfo{'ID'} = $content;
                }
            }

            close $fh;
        } else {
            warn( sprintf( "Couldn't open %s: %s", $etcDpkgOriginsDefauft, $! ));
        }
    }

    my ($kern) = uname();

    if ( $kern =~ /^(?:Linux|Hurd|NetBSD)$/ ) {
        $distInfo{'OS'} = "GNU/$kern";
    } elsif ( $kern eq 'FreeBSD' ) {
        $distInfo{'OS'} = "GNU/k$kern";
    } elsif ( $kern =~ /^GNU\/(?:Linux|kFreeBSD)$/ ) {
        $distInfo{'OS'} = $kern;
    } else {
        $distInfo{'OS'} = 'GNU';
    }

    $distInfo{'DESCRIPTION'} = sprintf( '%s %s', $distInfo{'ID'}, $distInfo{'OS'} );

    my $etcDebianVersion = $ENV{'LSB_ETC_DEBIAN_VERSION'}
        || ( ( $IS_DEVUAN ) ? '/etc/devuan_version' : '/etc/debian_version' );

    if ( -f $etcDebianVersion ) {
        my $release = 'unknown';

        if ( open my $fh, '<', $etcDebianVersion ) {
            $release = do {
                local $/;
                <$fh>
            };
            $release =~ s/^\s+|\s+$//g; # Remove leading and trailing whitespaces

            close $fh;
        } else {
            warn( sprintf( "Couldn't open %s file: %s", $etcDebianVersion, $! ));
        }

        if ( $release !~ /^[a-z]/ ) {
            # /etc/debian_version or /etc/devuan_version should be numeric
            $distInfo{'CODENAME'} = $self->_lookupCodename( $release, 'n/a' );
            $distInfo{'RELEASE'} = $release;
        } elsif ( $release =~ m%(.*)/(?:sid|ceres)$% ) {
            $TESTING_CODENAME = $1 if lc( $1 ) ne 'testing';
            $distInfo{'RELEASE'} = 'testing/unstable';
        } else {
            $distInfo{'RELEASE'} = $release;
        }
    }

    # Only use apt information if we did not get the proper information
    # from /etc/debian_version or if we don't have a codename
    # (which will happen if /etc/debian_version does not contain a
    # number but some text like 'testing/unstable' or 'lenny/sid')
    #
    # This is slightly faster and less error prone in case the user
    # has an entry in his /etc/apt/sources.list but has not actually
    # upgraded the system.
    unless ( exists $distInfo{'CODENAME'} ) {
        my %rInfo = ( $distInfo{'ID'} eq 'Devuan' )
            ? $self->_guessReleaseFromApt(
                'Devuan', 'main', 'experimental', 'Devuan', { 'Devuan Ports' => 'packages.devuan.org' }
            )
            : $self->_guessReleaseFromApt();

        if ( %rInfo ) {
            my $release = $rInfo{'version'} || '';

            # Special case Debian-Ports as their Release file has 'version': '1.0'
            if ( !$IS_DEVUAN &&
                $release eq '1.0'
                && $rInfo{'origin'} eq 'Debian Ports'
                && $rInfo{'label'} == 'ftp.debian-ports.org'
            ) {
                $release = undef;
                $rInfo{'suite'} = 'unstable';
            }

            if ( $release ) {
                $distInfo{'CODENAME'} = $self->_lookupCodename( $release, 'n/a' );
            } else {
                $release = $rInfo{'suite'} || 'unstable';

                if ( $release eq 'testing' ) {
                    # Would be nice if I didn't have to hardcode this.
                    $distInfo{'CODENAME'} = $TESTING_CODENAME;
                } else {
                    $distInfo{'CODENAME'} = ( $IS_DEVUAN ) ? 'ceres' : 'sid';
                }
            }

            $distInfo{'RELEASE'} = $release;
        }
    }

    if ( exists $distInfo{'RELEASE'} ) {
        $distInfo{'DESCRIPTION'} .= sprintf( ' %s', $distInfo{'RELEASE'} );
    }

    if ( exists $distInfo{'CODENAME'} ) {
        $distInfo{'DESCRIPTION'} .= sprintf( ' (%s)', $distInfo{'CODENAME'} );
    }

    %distInfo;
}

=item _getLsbInformation( )

 Get lsb information from lsb-release file

 Return hash Hash containing lsb information

=cut

sub _getLsbInformation
{
    my %distInfo = ();
    my $etcLsbFile = $ENV{'LSB_ETC_LSB_RELEASE'} || '/etc/lsb-release';

    if ( -f $etcLsbFile ) {
        if ( open my $fh, '<', $etcLsbFile ) {
            while ( my $line = <$fh> ) {
                $line =~ s/^\s+|\s+$//g; # Remove trailing and leading whitespaces

                next unless $line && index( $line, '=' ) != -1; # Skip invalid lines

                my ($var, $arg) = split '=', $line, 2;
                if ( index( $var, 'DISTRIB_' ) == 0 ) {
                    $var = substr( $var, 8 );
                    $arg = substr( $arg, 1, -1 ) if $arg =~ /^".*?"$/;
                    $distInfo{$var} = $arg if $arg; # Ignore empty arguments
                }
            }

            close $fh;
        } else {
            warn( sprintf( "Couldn't open %s file: %s", $etcLsbFile, $! ));
        }
    }

    %distInfo;
}

=back

=head1 NOTE

 This is a rewrite for i-MSCP of the lsb_release command as provided by the lsb-release Debian package.

 Detection of systems using a mix of packages from various distributions or releases is something of a black art; the
 current heuristic tends to assume that the installation is of the earliest distribution which is still being used by apt
 but that heuristic is subject to error.

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
