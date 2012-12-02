#/usr/bin/perl

=head1 NAME

 iMSCP::Getopt - Provides command line optionsfor both imscp-autoinstall and imscp-setup scripts

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2012 by internet Multi Server Control Panel
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
# @category i-MSCP
# @copyright 2010 - 2012 by i-MSCP | http://i-mscp.net
# @author Laurent Declercq <l.declercq@nuxwin.com>
# @link http://i-mscp.net i-MSCP Home Site
# @license http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Getopt;

use strict;
use warnings;
use iMSCP::HooksManager;
use iMSCP::Debug qw / error /;
use fields qw / reconfigure noprompt preseed debug /;
our $options = fields::new('iMSCP::Getopt');

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

sub parse
{
	my $class = shift;
	my $usage = shift;

	my $showusage = sub {
		print STDERR $usage."\n";
		print STDERR <<EOF;
  -r,  --reconfigure    Re-show all questions already seen.
  -n,  --noprompt       Switch to non-interactive mode (Expert option).
  -p,  --preseed        Path to preseed file (Expert option).
  -d,  --debug          Force debug mode.
  -h,  --help           Show this help.
EOF
		iMSCP::HooksManager->getInstance()->register('beforeExit', sub { exit 1; });

		exit 1;
	};

	# Do not load Getopt::Long if not needed
	return unless grep { $_ =~ /^-/ } @ARGV;

	my $previousHandler = $SIG{__WARN__};
    $SIG{__WARN__} = sub{ print STDERR "@_" };

	require Getopt::Long;

	Getopt::Long::Configure('bundling');

	eval {
		Getopt::Long::GetOptions(
			'reconfigure|r', sub { $options->{'reconfigure'} = 'true' },
			'noprompt|n', sub { $options->{'noprompt'} = 'true' },
			'preseed|p=s', sub { shift; $class->preseed(shift) },
			'debug|d', sub { $options->{'debug'} = 'true' },
			'help|h|?', $showusage,
			@_,
		) || $showusage->();
	};

	# Restore previous handler
	$SIG{__WARN__} = $previousHandler;

	undef;
}

=item reconfigure()

 Whether user asked for reconfiguration

 Return int 0 or 1

=cut

sub reconfigure
{
	my ($class, $value) = @_;

	$options->{'reconfigure'} = $value if defined $value;
	$options->{'reconfigure'} ? 1 : 0;
}

=item noprompt()

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

sub preseed
{
	my ($class, $value) = @_;

	if(defined $value) {
		if( -f $value) {
			$options->{'preseed'} = $value;
		} else {
			die("Preseed file not found: $value")
		}
	}

	$options->{'preseed'};
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
