#!/usr/bin/perl
#
# ispcp-eol - Convert all EOL of ispCP files to Unix format and adding missing EOL
# Copyright (C) 2010  Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA

# This script will convert all EOL to Unix format in all ispCP package files.
# An EOL will be also added at end of files if missing
# This script should live and be called from the ./contrib directory

use Getopt::Long;
use File::Find;
use strict;
use warnings;

#
# Variables - Begin
#

# Directories and files that should be ignored
my $discard_files = '^.*\.(gif|ico|jpg|mo|png|po|project|sfd|svn|swf|ttf|wav|z)|filemanager|pma|webmail$';

# Counters
our ($files, $crlf, $eol) = (0, 0, 0);
our @list_files = ();

#
# Variables - Begin
#

#
## Options management - Begin
#

our ($simulate,$verbose, $flist, $backup, $help) = ('', '', '', '', '');

GetOptions (
	'backup!' => \$backup,
	'help!' => \$help,
	'lfiles!' =>\$flist,
	'simulate!' => \$simulate,
	'verbose!' => \$verbose
);

if($help) {
	my $usage = << "USAGE";
Usage: perl $0 [OPTION]...

Converts all EOL of ispCP files in the Unix form and adds an EOL at end of file if
missing.

Options:
  -b, --backup    Perfoms a backup of each file (*.bak)
  -h, --help      Show this help
  -l, --lfiles    List and shows all modified files
  -s, --simulate  Does not act. Performs only a simulation
  -v, --verbose   Displays a summary of actions that have been made

By default (without any options) the program doing all the actions silently.
USAGE

	print $usage;
	exit 0;
}

# Backup option
if($backup && !$simulate) {
	$^I ='.bak';
} elsif(!$simulate) {
	$^I ='';
}

#
## Options management - End
#

#
## Subroutines - Begin
#

# Should be documented
sub FixFiles {
	my ($changed, $bak_file, $wlcrlf, $wleol) = ('','','','');

	if(!-d && !/.*\.bak$/) {
		@ARGV = $_ ;
		$bak_file = $_ . '.bak';

		while(<>) {
			# Convert all CRLF|CR to LF in the file
			if(/\x0D\x0A|\x0D$/) {
				s/\x0D\x0A|\x0D$/\x0A/ if(!$simulate);
				push @list_files, "Fixed CRLF|CR to LF in $File::Find::name \n" if($flist && !$wlcrlf);
				$changed = 1;
				$crlf++;
			}

			# Add EOL if doesn't exists at end of file
			if(eof && !/\x0A$/) {
				s/$/\x0A/ if(!$simulate);
				push @list_files, "Fixed missing EOL in $File::Find::name \n" if($flist && !$wleol);
				$changed = 1;
				$eol++;
			}

			print if(!$simulate);
		}

		unlink "./$bak_file" if(!$changed && -e $bak_file);

		($changed, $wlcrlf, $wleol) = ('','','');
		$files++;
	}
}

# Should be documented
sub ToUniq {
	my ($tref) = @_;
	my %uniq;
	@uniq{@{$tref}} = ();
	keys %uniq;
}

#
## Subroutines - End
#

#
## Main program
#

# Go to the ispCP package root directory
chdir '../';

find{wanted => \&FixFiles, preprocess => sub {grep !/$discard_files/, @_;}}, '.';

if($flist) {
	@list_files = ToUniq \@list_files;
	print sort @list_files;
}

if ($verbose){
	my $resume = << "RESUME";

	TOTAL of processed files: $files
	TOTAL of files that had no end of line EOL: $eol
	TOTAL of EOL converted to the Unix form (CRLF|CR to LF): $crlf

	Note: The directories/files corresponding to the following pattern:

		/^.*\.(gif|ico|jpg|mo|png|po|project|svn|swf|ttf|wav|z)|filemanager|pma|webmail\$/

	were not processed by this program.
RESUME

	print $resume;
}

exit 0;
