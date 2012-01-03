#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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
# @category		i-MSCP
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license      http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Mail;

use strict;
use warnings;
use iMSCP::Debug;

use vars qw/@ISA/;
@ISA = ('Common::SimpleClass');
use Common::SimpleClass;

sub _init{}

sub errmsg{

	my ($self, $errmsg) = @_;

	use POSIX;
	use Net::LibIDN qw/idn_to_ascii/;
	use MIME::Entity;

	my @parts = split('@', $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'});
	my $dmn = pop(@parts);
	$dmn = idn_to_ascii($dmn, 'utf-8');
	push(@parts, $dmn);

	my $admin_email	= join('@', @parts);
	my $date		=  strftime "%d.%m.%Y %H:%M:%S", localtime;
	my $server_name	= $main::imscpConfig{'SERVER_HOSTNAME'};
	my $server_ip	= $main::imscpConfig{'BASE_SERVER_IP'};
	my $fname		= (caller(1))[3];
	$fname			= 'main' unless $fname;

	my $msg_data =
		"Dear admin,\n\n".
		"I'm an automatic email sent by your $server_name ($server_ip) server.\n\n".
		"A critical error just was encountered while executing function $fname in $0.\n\n".
		"Error encountered was:\n\n".
		"=====================================================================\n".
		"$errmsg\n".
		"====================================================================="
	;

	$msg_data =~ s/(.{1,79}\S|\S+)\s+/$1\n/mg;

	my $out = new MIME::Entity;

	$out->build(
		From		=> "$server_name ($server_ip) <$admin_email>",
		To			=> $admin_email,
		Subject		=> "[$date] i-MSCP Error report",
		Data		=> $msg_data,
		'X-Mailer'	=> "i-MSCP $main::imscpConfig{'Version'} Automatic Error Messenger"
	);

	debug("Send message to $admin_email: $msg_data");

	unless(open MAIL, "| /usr/sbin/sendmail -t -oi"){
		error('Can not send mail...');
	} else {
		$out -> print(\*MAIL);
		close MAIL;
	}

	0;
}


sub warnMsg{

	my ($self, $msg) = @_;

	use POSIX;
	use Net::LibIDN qw/idn_to_ascii/;
	use MIME::Entity;

	my @parts = split('@', $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'});
	my $dmn = pop(@parts);
	$dmn = idn_to_ascii($dmn, 'utf-8');
	push(@parts, $dmn);

	my $admin_email	= join('@', @parts);
	my $date		=  strftime "%d.%m.%Y %H:%M:%S", localtime;
	my $server_name	= $main::imscpConfig{'SERVER_HOSTNAME'};
	my $server_ip	= $main::imscpConfig{'BASE_SERVER_IP'};
	my $fname = (caller(1))[3];

	my $msg_data =
		"Dear admin,\n\n".
		"I'm an automatic email sent by your $server_name ($server_ip) server.\n\n".
		"Folowing warning was raised while executing $fname in $0.\n\n".
		"Warning text:\n\n".
		"=====================================================================\n".
		"$msg\n".
		"====================================================================="
	;

	$msg_data =~ s/(.{1,79}\S|\S+)\s+/$1\n/mg;

	my $out = new MIME::Entity;

	$out -> build(
		From		=> "$server_name ($server_ip) <$admin_email>",
		To			=> $admin_email,
		Subject		=> "[$date] i-MSCP Error report",
		Data		=> $msg_data,
		'X-Mailer'	=> "i-MSCP $main::imscpConfig{'Version'} Automatic Error Messenger"
	);

	debug("Send message to $admin_email: $msg_data");

	unless(open MAIL, "| /usr/sbin/sendmail -t -oi"){
		error('Can not send mail...');
	} else {
		$out -> print(\*MAIL);
		close MAIL;
	}

	0;
}

1;
