=head1 NAME

 iMSCP::Getopt - Provides command line options for both imscp-autoinstall and imscp-setup scripts

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug qw/ debugRegisterCallBack /;
use Text::Wrap;
use fields qw /reconfigure noprompt preseed listener cleanPackageCache skipPackageUpdate debug verbose/;

$Text::Wrap::columns = 80;
$Text::Wrap::break = qr/[\s\n\|]/;

my $options = fields::new('iMSCP::Getopt');
my $optionHelp = '';
my $showUsage;

=head1 DESCRIPTION

 This class provide command line options parser for i-MSCP.

=head1 CLASS METHODS

=over 4

=item parse($usage)

 Parses command line options in @ARGV with GetOptions from Getopt::Long

 The first parameter should be basic usage text for the program. Usage text for the globally supported options will be
 prepended to this if usage help must be printed.

 If any additonal parameters are passed to this function, they are also passed to GetOptions. This can be used to handle
 additional options.

 Return undef

=cut

sub parse
{
	my ($class, $usage, @options) = @_;

	$showUsage = sub {
		my $exitCode = shift || 0;
		print STDERR wrap('', '', <<EOF);
$usage
 -r,    --reconfigure [item]    Type --reconfigure help.
 -n,    --noprompt              Switch to non-interactive mode.
 -p,    --preseed <file>        Path to preseed file.
 -l,    --listener <file>       Path to listener file.
 -c     --clean-package-cache   Cleanup i-MSCP composer package cache.
 -a     --skip-package-update   Skip i-MSCP composer packages update.
 -d,    --debug                 Force debug mode.
 -v     --verbose               Enable verbose mode
 -?, -h  --help                  Show this help.

$optionHelp
EOF
		debugRegisterCallBack(sub { exit $exitCode; });
		exit $exitCode;
	};

	# Do not load Getopt::Long if not needed
	return unless grep { $_ =~ /^-/ } @ARGV;

	local $SIG{__WARN__} = sub {
		my $error = shift;
		$error =~ s/(.*?) at.*/$1/;
		print STDERR wrap('', '', $error) if $error ne "Died\n";
	};

	require Getopt::Long;
	Getopt::Long::Configure('bundling');
	eval {
		Getopt::Long::GetOptions(
			'reconfigure|r:s', sub { $class->reconfigure($_[1]) },
			'noprompt|n', sub { $options->{'noprompt'} = 1 },
			'preseed|p=s', sub { $class->preseed($_[1]) },
			'listener|l=s', sub { $class->listenerFile($_[1]) },
			'clean-package-cache|c', sub { $options->{'cleanPackageCache'} = 1 },
			'skip-package-update|a', sub { $options->{'skipPackageUpdate'} = 1 },
			'debug|d', sub { $options->{'debug'} = 1 },
			'verbose|v', sub { $options->{'verbose'} = 1 },
			'help|?|h', sub { $showUsage->() },
			@options,
		) || $showUsage->(1);
	};

	undef;
}

=item parseNoDefault($usage)

 Parses command line options in @ARGV with GetOptions from Getopt::Long. Default options are excluded

 The first parameter should be basic usage text for the program. Any following parameters are passed to to GetOptions.

 Return undef

=cut

sub parseNoDefault
{
	my ($class, $usage, @options) = @_;

	$showUsage = sub {
		my $exitCode = shift || 0;
		print STDERR wrap('', '', <<EOF);
$usage
 -?, -h  --help          Show this help.

EOF
		debugRegisterCallBack(sub { exit $exitCode; });
		exit $exitCode;
	};

	# Do not load Getopt::Long if not needed
	return unless grep { $_ =~ /^-/ } @ARGV;

	local $SIG{__WARN__} = sub {
		my $error = shift;
		$error =~ s/(.*?) at.*/$1/;
		print STDERR wrap('', '', $error) if $error ne "Died\n";
	};

	require Getopt::Long;
	Getopt::Long::Configure('bundling');
	eval {
		Getopt::Long::GetOptions(
			'help|?|h', sub { $showUsage->() },
			@options
		) || $showUsage->(1);
	};
	undef;
}

=item showUsage($exitCode)

 Show usage

 Param int $exitCode OPTIONAL Exit code
 Return undef

=cut

sub showUsage
{
	my ($class, $exitCode) = @_;

	$exitCode //= 1;
	ref $showUsage eq 'CODE' or die('ShowUsage() is not defined.');
	$showUsage->($exitCode);
}

our @reconfigurationItems = sort(
	'all', 'servers', 'httpd', 'mta', 'mailfilters', 'po', 'ftpd', 'named', 'sql', 'hostnames', 'system_hostname',
	'panel_hostname', 'panel_ports', 'ips', 'admin', 'php', 'timezone', 'panel_ssl', 'services_ssl', 'ssl', 'backup',
	'webstats', 'sqlmanager', 'webmails', 'filemanager', 'antirootkits'
);

=item reconfigure([ $item = 'none' ])

 Reconfiguration item

 Param string $item OPTIONAL Reconfiguration item
 Return string Name of item to reconfigure or none

=cut

sub reconfigure
{
	my ($class, $item) = @_;

	return $options->{'reconfigure'} ||= 'none' unless defined $item;

	if($item eq 'help') {
		$optionHelp = <<EOF;
Reconfigure option usage:

Without any argument, this option allows to reconfigure all items. You can reconfigure a specific item by passing it name as argument.

Available items are:

EOF
			$optionHelp .=  ' ' . (join '|', @reconfigurationItems);
			die();
	} elsif($item eq '') {
		$item = 'all';
	}

	$item eq 'none' || $item ~~ @reconfigurationItems or die(sprintf(
		"Error: '%s' is not a valid argument for the --reconfigure option.", $item
	));

	$options->{'reconfigure'} = $item;
}

=item preseed([ $file = undef ])

 Accessor/Mutator for the preseed command line option

 Param string $file OPTIONAL Preseed file path
 Return string Path to preseed file or empty string

=cut

sub preseed
{
	my ($class, $file) = @_;

	return $options->{'preseed'} unless defined $file;

	-f $file or die(sprintf('Preseed file not found: %s', $file));
	$options->{'preseed'} = $file;
}

=item listener([$file = undef])

 Accessor/Mutator for the listener command line option

 Param string $file OPTIONAL Listener file path
 Return string Path to listener file or undef

=cut

sub listener
{
	my ($class, $file) = @_;

	return $options->{'listener'} unless defined $file;

	-f $file or die(sprintf('Listener file not found: %s', $file));
	$options->{'listener'} = $file;
}

=back

=head1 OPTIONS

 Default accessor/mutator for command line options

 Return mixed Option value if defined or undef;

=cut

sub AUTOLOAD
{
	(my $option = our $AUTOLOAD) =~ s/.*://;
	my($class, $value) = @_;

	$options->{$option} = $value if $value;
	$options->{$option};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
