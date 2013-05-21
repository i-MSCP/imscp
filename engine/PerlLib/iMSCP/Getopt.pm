#/usr/bin/perl

=head1 NAME

 iMSCP::Getopt - Provides command line options for both imscp-autoinstall and imscp-setup scripts

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
#
# @category    i-MSCP
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Getopt;

use strict;
use warnings;

use iMSCP::HooksManager;
use iMSCP::Debug qw /error debugRegisterCallBack output /;
use fields qw / reconfigure noprompt preseed hookFile cleanAddons debug backtrace /;
our $options = fields::new('iMSCP::Getopt');

our $optionHelp = '';

=head1 DESCRIPTION

This class provide command line options for both imscp-autoinstall and imscp-setup scripts.

=head1 CLASS METHODS

=over 4

=item getopt($usage)

 This class method parses command line options in @ARGV with GetOptions from Getopt::Long.

 The first parameter should be basic usage text for the program in question. Usage text for the globally supported
options will be prepended to this if usage help must be printed.

 If any additonal parameters are passed to this function, they are also passed to GetOptions. This can be used to handle
additional options.

 Param STRING $usage Usage text
 Return undef

=cut

sub parse($$)
{
	my $class = shift;
	my $usage = shift;

	my $showUsage = sub {
		my $exitCode = shift || 0;
		print STDERR output(<<EOF);
$usage
 -r,  --reconfigure  <item>  Type --reconfigure help for more information.
 -n,  --noprompt             Switch to non-interactive mode.
 -p,  --preseed      <file>  Path to preseed file.
 -h,  --hook-file    <file>  Path to hook file.
 -c   --clean-addons         Cleanup local addon packages repository.
 -d,  --debug                Force debug mode.
 -t,  --backtrace            Enable backtrace (implies debug option).
 -?,  --help                 Show this help.

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
    	print STDERR output($error) if $error ne "Died\n";
    };

	require Getopt::Long;

	Getopt::Long::Configure('bundling');

	eval {
		Getopt::Long::GetOptions(
			'reconfigure|r:s', sub { shift, $class->reconfigure(shift) },
			'noprompt|n', sub { $options->{'noprompt'} = 1 },
			'preseed|p=s', sub { shift; $class->preseed(shift) },
			'hook-file|h=s', sub { shift; $class->hookFile(shift) },
			'clean-addons|c', sub { $options->{'cleanAddons'} = 1 },
			'debug|d', sub { $options->{'debug'} = 1 },
			'backtrace|t', sub { shift; $class->backtrace(shift) },
			'help|?', sub { $showUsage->() },
			@_,
		) || $showUsage->(1);
	};

	undef;
}

our $reconfigureItems = [
	'all', 'servers', 'httpd', 'mta', 'mailfilters', 'po', 'ftpd', 'named', 'sql', 'hostname', 'resolver', 'ips',
	'admin', 'php', 'ssl', 'backup', 'webstats', 'sqlmanager', 'webmail', 'filemanager'
];

=item reconfigure()

 Whether user asked for reconfiguration

 Return int|string 0 or name of item to reconfigure

=cut

sub reconfigure($;$)
{
	my ($class, $value) = @_;

	if(defined $value) {
		if($value eq 'help') {
			$optionHelp .= "The --reconfigure option allows to reconfigure a specific item.\n";
			$optionHelp .= " Available items:\n\n ";
			$optionHelp .=  (join '|', @{$reconfigureItems});
			$optionHelp .= "\n\n Without any argument, all is reconfigured.";
			$optionHelp .= "\n\n";
			die();
		} elsif($value eq '') {
			$value = 'all';
		}

		$value ~~ $reconfigureItems or die("Error: '$value' is not a valid argument for the --reconfigure option.");

		$options->{'reconfigure'} = $value;
	}

	$options->{'reconfigure'} ? $options->{'reconfigure'} : 'none';
}

=item noprompt($;$)

 Whether user asked for non-interactive mode

 Return int 0 or 1

=cut

sub noprompt
{
	my ($class, $value) = @_;

	$options->{'noprompt'} = $value if defined $value;
	$options->{'noprompt'} ? 1 : 0;
}

=item

 Preseed file path

 Return SCALAR|undef Path to preseed file or undef

=cut

sub preseed($;$)
{
	my ($class, $value) = @_;

	if(defined $value) {
		if( -f $value) {
			$options->{'preseed'} = $value;
		} else {
			die("Preseed file not found: $value");
		}
	}

	$options->{'preseed'};
}

=item

 Hook file path

 Return SCALAR|undef Path to hook file or undef

=cut

sub hookFile($;$)
{
	my ($class, $value) = @_;

	if(defined $value) {
		if( -f $value) {
			$options->{'hookFile'} = $value;
		} else {
			die("Hook file not found: $value")
		}
	}

	$options->{'hookFile'};
}

=item

 Whether user asked for backtrace

 Return int 0 or 1

=cut

sub backtrace($;$)
{
	my ($class, $value) = @_;

	if(defined $value) {
		$options->{'debug'} = 1;
		$options->{'backtrace'} = 1;
	}

	$options->{'backtrace'} ? 1 : 0;
}

=back

=head1 FIELDS

Other fields can be accessed and set by calling class methods.

=cut

sub AUTOLOAD
{
	(my $field = our $AUTOLOAD) =~ s/.*://;
	my $class = shift;

	return $options->{$field} = shift if @_;
	return $options->{$field} if defined $options->{$field};
	return '';
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
