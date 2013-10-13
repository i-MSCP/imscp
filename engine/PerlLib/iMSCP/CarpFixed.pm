#!/usr/bin/perl

package Carp;

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2013 by internet Multi Server Control Panel
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
#
# @category    i-MSCP
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

#
# This packages provides a fix for the following errors:
# - Bizarre copy of HASH in sassign at ...
# - panic: attempt to copy freed scalar...
#

use strict;
use warnings;

use parent 'Carp::Heavy', 'Exporter';

our ($CarpLevel, $MaxArgNums, $MaxEvalLen, $MaxArgLen, $Verbose);

BEGIN
{
	#if("$]" < 5.018001) {
	#	if("$]" >= 5.015002 || ("$]" >= 5.014002 && "$]" < 5.015) || ("$]" >= 5.012005 && "$]" < 5.013)) {
	#		*CALLER_OVERRIDE_CHECK_OK = sub () { 1 };
	#	} else {
	#		*CALLER_OVERRIDE_CHECK_OK = sub () { 0 };
	#	}
	#}
 }

delete $Carp::Heavy::{'_cgc'};
*_cgc = *_cgc_real;

sub _cgc_real
{
	no strict 'refs';
	return \&{"CORE::GLOBAL::caller"} if defined &{"CORE::GLOBAL::caller"};
	return;
}

delete $Carp::Heavy::{'caller_info'};
*caller_info = *caller_info_sassign_fixed;

sub caller_info_sassign_fixed
{
	my $i = shift(@_) + 1;
	my %call_info;
	my $cgc = _cgc();
	{
		# Some things override caller() but forget to implement the
		# @DB::args part of it, which we need.  We check for this by
		# pre-populating @DB::args with a sentinel which no-one else
		# has the address of, so that we can detect whether @DB::args
		# has been properly populated.  However, on earlier versions
		# of perl this check tickles a bug in CORE::caller() which
		# leaks memory.  So we only check on fixed perls.
		@DB::args = \$i if CALLER_OVERRIDE_CHECK_OK;
		package DB;
		@call_info{
			qw(pack file line sub has_args wantarray evaltext is_require) }
			= $cgc ? $cgc->($i) : caller($i);
	}

	return () unless (defined $call_info{'pack'});

	my $sub_name = Carp::get_subname(\%call_info);

	if ($call_info{'has_args'}) {
		my @args;

		if (CALLER_OVERRIDE_CHECK_OK && @DB::args == 1 && ref $DB::args[0] eq ref \$i && $DB::args[0] == \$i ) {
			@DB::args = (); # Don't let anyone see the address of $i
			local $@;

			my $where = eval {
				my $func = $cgc or return '';
				my $gv =
					*{
						( $::{"B::"} || return '')				# B stash
							->{'svref_2object'} || return ''	# entry in stash
					}{CODE}										# coderef in entry
						->($func)->GV;
				my $package = $gv->STASH->NAME;
				my $subname = $gv->NAME;
				return unless defined $package && defined $subname;

				# returning CORE::GLOBAL::caller isn't useful for tracing the cause:
				return if $package eq 'CORE::GLOBAL' && $subname eq 'caller';
				" in &${package}::$subname";
			} || '';
			@args = "** Incomplete caller override detected$where; \@DB::args were not set **";
		} else {
			@args = map {
    			local $@;
				my $tmp = eval { Carp::format_arg($_) };
				defined($tmp) ? $tmp : 'unknown';
			} @DB::args;
        }

		if ($MaxArgNums and @args > $MaxArgNums ) { # More than we want to show?
			$#args = $MaxArgNums;
			push @args, '...';
		}

		# Push the args onto the subroutine
		$sub_name .= '(' . join( ', ', @args ) . ')';
	}

	$call_info{'sub_name'} = $sub_name;

	wantarray() ? %call_info : \%call_info;
}

1;

__END__
