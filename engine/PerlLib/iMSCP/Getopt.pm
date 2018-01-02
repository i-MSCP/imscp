=head1 NAME

 iMSCP::Getopt - Provides command line options parser for i-MSCP scripts

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

package iMSCP::Getopt;

use strict;
use warnings;
use File::Basename;
use Text::Wrap qw/ wrap /;
use fields qw / clearPackageCache debug fixPermissions listener noprompt
    preseed reconfigure skipPackageUpdate verbose /;

$Text::Wrap::columns = 80;
$Text::Wrap::break = qr/[\s\n\|]/;

my $options = fields::new( 'iMSCP::Getopt' );
my $OPTION_HELP = '';
my $SHOW_USAGE;

=head1 DESCRIPTION

 This class provide command line options parser for i-MSCP.

=head1 CLASS METHODS

=over 4

=item parse( $usage, @options )

 Parses command line options in @ARGV with GetOptions from Getopt::Long

 The first parameter should be basic usage text for the program. Usage text for
 the globally supported options will be prepended to this if usage help must be
 printed.

 If any additonal parameters are passed to this function, they are also passed
 to GetOptions. This can be used to handle additional options.

 Param string $usage Usage text
 Param list @options OPTIONAL Additional options
 Return void

=cut

sub parse
{
    my ($class, $usage, @options) = @_;

    $SHOW_USAGE = sub {
        if ( $OPTION_HELP ne '' ) {
            print STDERR wrap( '', '', <<"EOF" );
$OPTION_HELP
EOF

        } else {
            print STDERR wrap( '', '', <<"EOF" );

$usage
 -a,    --skip-package-update     Skip i-MSCP composer packages update.
 -c,    --clean-package-cache     Clear i-MSCP composer package cache.
 -d,    --debug                   Enable debug mode.
 -h,-?  --help                    Show this help.
 -l,    --listener <file>         Path to listener file.
 -n,    --noprompt                Switch to non-interactive mode.
 -p,    --preseed <file>          Path to preseed file.
 -r,    --reconfigure [item,...]  Type `help` for list of allowed items.
 -v,    --verbose                 Enable verbose mode.
 -x,    --fix-permissions         Fix permissions.
EOF
        }
    };

    # Do not load Getopt::Long if not needed
    return unless grep { $_ =~ /^-/ } @ARGV;

    local $SIG{'__WARN__'} = sub {
        my $error = shift;
        $error =~ s/(.*?) at.*/$1/;
        print STDERR wrap( '', '', $error ) if $error ne "Died\n";
    };

    require Getopt::Long;
    Getopt::Long::Configure( 'bundling' );
    Getopt::Long::GetOptions(
        'clean-package-cache|c', sub { $options->{'clearPackageCache'} = 1 },
        'debug|d', sub { $options->{'debug'} = 1 },
        'help|?|h', sub { $class->showUsage() },
        'fix-permissions|x', sub { $options->{'fixPermissions'} = 1 },
        'listener|l=s', sub { $class->listener( $_[1] ) },
        'noprompt|n', sub { $options->{'noprompt'} = 1 },
        'preseed|p=s', sub { $class->preseed( $_[1] ) },
        'reconfigure|r:s', sub { $class->reconfigure( $_[1] ) },
        'skip-package-update|a', sub { $options->{'skipPackageUpdate'} = 1 },
        'verbose|v', sub { $options->{'verbose'} = 1 },
        @options,
    ) or $class->showUsage();
}

=item parseNoDefault( $usage, @options )

 Parses command line options in @ARGV with GetOptions from Getopt::Long.
 Default options are excluded

 The first parameter should be basic usage text for the program. Any following
 parameters are passed to to GetOptions.

 Param string $usage Usage text
 Param list @options Options
 Return void

=cut

sub parseNoDefault
{
    my ($class, $usage, @options) = @_;

    $SHOW_USAGE = sub {
        print STDERR wrap( '', '', <<"EOF" );

$usage
 -?,-h  --help          Show this help.

EOF
    };

    # Do not load Getopt::Long if not needed
    return unless grep { $_ =~ /^-/ } @ARGV;

    local $SIG{'__WARN__'} = sub {
        my $error = shift;
        $error =~ s/(.*?) at.*/$1/;
        print STDERR wrap( '', '', $error ) if $error ne "Died\n";
    };

    require Getopt::Long;
    Getopt::Long::Configure( 'bundling' );
    Getopt::Long::GetOptions( 'help|?|h', sub { $class->showUsage() }, @options ) or $class->showUsage();
}

=item showUsage( $exitCode )

 Show usage

 Param int $exitCode OPTIONAL Exit code
 Return void

=cut

sub showUsage
{
    ref $SHOW_USAGE eq 'CODE' or die( 'showUsage( ) is not defined.' );
    $SHOW_USAGE->();
    exit 1;
}

my %RECONFIGURATION_ITEMS = (
    admin             => 'Reconfigure the master administrator',
    admin_credentials => 'Reconfigure credential for the master administrator',
    admin_email       => 'Reconfigure the email for the master administrator',
    alt_urls          => 'Reconfigure the alternative URL feature',
    antirootkits      => 'Reconfigure the anti-rootkits',
    backup            => 'Reconfigure backup options',
    filemanagers      => 'Reconfigure the file managers',
    ftpd              => 'Reconfigure the FTP server',
    hostnames         => 'Reconfigure server and control panel hostnames',
    httpd             => 'Reconfigure the httpd server',
    mta               => 'Reconfigure the SMTP server',
    named             => 'Reconfigure the DNS server',
    panel             => 'Reconfigure the control panel',
    panel_hostname    => 'Reconfigure the hostname for the control panel',
    panel_ports       => 'Reconfigure the http(s) ports for the control panel',
    panel_ssl         => 'Reconfigure SSL for the control panel',
    php               => 'Reconfigure PHP',
    po                => 'Reconfigure the IMAP/POP servers',
    primary_ip        => 'Reconfigure the server primary IP address',
    servers           => 'Reconfigure all servers',
    servers_ssl       => 'Reconfigure SSL for the IMAP/POP, SMTP and FTP servers',
    sqld              => 'Reconfigure the SQL server',
    sqlmanager        => 'Reconfigure the SQL manager',
    ssl               => 'Reconfigure SSL for the servers and control panel',
    system_hostname   => 'Reconfigure the system hostname',
    system_server     => 'Reconfigure the system server',
    timezone          => 'Reconfigure the system timezone',
    webmails          => 'Reconfigure the Webmails',
    webstats          => 'Reconfigure Webstats packages'
);

=item reconfigure( [ $items = 'none' ] )

 Reconfiguration items

 Param string $items OPTIONAL List of comma separated items to reconfigure
 Return string Name of item to reconfigure or none

=cut

sub reconfigure
{
    my (undef, $items) = @_;

    return $options->{'reconfigure'} ||= [ 'none' ] unless defined $items;

    my @items = split /,/, $items;

    if ( grep( 'help' eq $_, @items ) ) {
        $OPTION_HELP = <<"EOF";
Reconfiguration option usage:

Without any argument, this option make it possible to reconfigure all items. You can reconfigure many items at once by providing a list of comma separated items as follows

 perl @{[ basename( $0 ) ]} --reconfigure http,php,po

Bear in mind that even when only one item is reconfigured, all i-MSCP configuration files are regenerated, even those that don't belong to the item being reconfigured.

Each item belong to one i-MSCP package/server.

The following items are available:

EOF

        $OPTION_HELP .= " - $_" . ( ' ' x ( 17-length( $_ ) ) ) . " : $RECONFIGURATION_ITEMS{$_}\n" for sort keys %RECONFIGURATION_ITEMS;
        die();
    } elsif ( !@items ) {
        push @items, 'all';
    } else {
        for my $item( @items ) {
            grep($_ eq $item, keys %RECONFIGURATION_ITEMS, 'none', 'forced') or die(
                sprintf( "Error: '%s' is not a valid item for the the --reconfigure option.", $item )
            );
        }
    }

    $options->{'reconfigure'} = [ @items ];
}

=item preseed( [ $file = undef ] )

 Accessor/Mutator for the preseed command line option

 Param string $file OPTIONAL Preseed file path
 Return string Path to preseed file or empty string

=cut

sub preseed
{
    my (undef, $file) = @_;

    return $options->{'preseed'} unless defined $file;

    -f $file or die( sprintf( 'Preseed file not found: %s', $file ));
    $options->{'preseed'} = $file;
}

=item listener( [ $file = undef ] )

 Accessor/Mutator for the listener command line option

 Param string $file OPTIONAL Listener file path
 Return string Path to listener file or undef

=cut

sub listener
{
    my (undef, $file) = @_;

    return $options->{'listener'} unless defined $file;

    -f $file or die( sprintf( 'Listener file not found: %s', $file ));
    $options->{'listener'} = $file;
}

=back

=head1 AUTOLOAD

 Handles all option fields, by creating accessor methods for them the
 first time they are accessed.

=cut

sub AUTOLOAD
{
    ( my $field = our $AUTOLOAD ) =~ s/.*://;

    no strict 'refs';
    *{$AUTOLOAD} = sub {
        shift;
        return $options->{$field} // 0 unless @_;
        $options->{$field} = shift;
    };
    goto &{$AUTOLOAD};
}

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
