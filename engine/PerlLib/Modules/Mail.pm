#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
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
# @category     i-MSCP
# @copyright    2010-2014 by i-MSCP | http://i-mscp.net
# @author       Daniel Andreca <sci2tech@gmail.com>
# @link         http://i-mscp.net i-MSCP Home Site
# @license      http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::Mail;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use parent 'Modules::Abstract';

sub _init
{
	my $self = $_[0];

	$self->{'type'} = 'Mail';

	$self;
}

sub loadData
{
	my $self = $_[0];

	my $sql = '
		SELECT
			if(ISNULL(t2.mail_addr), "no", "yes") AS hasCatchAll,
			if(COUNT(t3.mail_addr) <> 0, "yes", "no") AS hasAutoResponder,
			t1.*
		FROM
			mail_users AS t1
		LEFT JOIN
			(SELECT mail_addr FROM mail_users WHERE mail_addr LIKE "@%") AS t2
		ON
			substr(t1.mail_addr, locate("@", t1.mail_addr)) = t2.mail_addr
		LEFT JOIN
			(SELECT mail_addr FROM mail_users WHERE mail_auto_respond = 1) AS t3
		ON
			t3.mail_addr LIKE concat("%", substr(t1.mail_addr, locate("@", t1.mail_addr)))
		WHERE
			t1.mail_id = ?
	';
	my $rdata = iMSCP::Database->factory()->doQuery('mail_id', $sql, $self->{'mailId'});
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$self->{'mailId'}}) {
		error("Mail record with ID $self->{'mailId'} has not been found in database");
		return 1;
	}

	%{$self} = (%{$self}, %{$rdata->{$self->{'mailId'}}});

	0;
}

sub process
{
	my $self = $_[0];

	$self->{'mailId'} = $_[1];

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;

	if($self->{'status'} ~~ ['toadd', 'tochange', 'toenable']) {
		$rs = $self->add();

		@sql = (
			'UPDATE mail_users SET status = ? WHERE mail_id = ?',
			($rs ? scalar getMessageByType('error') : 'ok'), $self->{'mail_id'}
		);
	} elsif($self->{'status'} eq 'todelete') {
		$rs = $self->delete();

		if($rs){
			@sql = (
				'UPDATE mail_users SET status = ? WHERE mail_id = ?',
				scalar getMessageByType('error'), $self->{'mail_id'}
			);
		} else {
			@sql = ('DELETE FROM mail_users WHERE mail_id = ?', $self->{'mail_id'});
		}
	} elsif($self->{'status'} eq 'todisable') {
		$rs = $self->disable();

		@sql = (
			'UPDATE mail_users SET status = ? WHERE mail_id = ?',
			($rs ? scalar getMessageByType('error') : 'disabled'), $self->{'mail_id'}
		);
	}

	my $rdata = iMSCP::Database->factory()->doQuery('dummy', @sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$rs;
}

sub _getMtaData
{
	my $self = $_[0];

	my $mail = $self->{'mail_addr'};
	$mail =~ s/^\s+//;

	$self->{'mta'} = {
		DOMAIN_NAME => (split('@', $mail))[1],
		MAIL_ACC => (split('@', $mail))[0],
		MAIL_ADDR => $mail,
		MAIL_CATCHALL => $self->{'mail_acc'},
		MAIL_PASS => $self->{'mail_pass'},
		MAIL_FORWARD => $self->{'mail_forward'},
		MAIL_TYPE => $self->{'mail_type'},
		MAIL_AUTO_RSPND => $self->{'mail_auto_respond'},
		MAIL_AUTO_RSPND_TXT => $self->{'mail_auto_respond_text'},
		MAIL_HAS_AUTO_RSPND => $self->{'hasAutoResponder'},
		MAIL_HAS_CATCH_ALL => $self->{'hasCatchAll'},
		MAIL_STATUS => $self->{'status'},
		MAIL_ON_CATCHALL => undef
	};

	if($self->{'hasCatchAll'} eq 'yes') {
		my $sql = "
			SELECT
				mail_addr
			FROM
				mail_users
			WHERE
				mail_addr
			LIKE
				'\%$self->{'mail_addr'}'
			AND
				mail_type LIKE '\%mail'
			AND
				mail_auto_respond = 0
		";
		my $rdata = iMSCP::Database->factory()->doQuery('mail_addr', $sql);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}

		@{$self->{'mta'}->{'MAIL_ON_CATCHALL'}} = keys %{$rdata};
	}

	0;
}

sub _getPoData
{
	my $self = $_[0];

	my $mail = $self->{mail_addr};
	$mail =~ s/^\s+//;

	$self->{'po'} = {
		DOMAIN_NAME => (split('@', $mail))[1],
		MAIL_ACC => (split('@', $mail))[0],
		MAIL_ADDR => $mail,
		MAIL_PASS => $self->{'mail_pass'},
		MAIL_TYPE => $self->{'mail_type'},
		MAIL_QUOTA => $self->{'quota'}
	};

	0;
}

sub _getPackagesData
{
	my $self = $_[0];

	my $mail = $self->{'mail_addr'};
	$mail =~ s/^\s+//;

	$self->{'packages'} = {
		DOMAIN_NAME => (split('@', $mail))[1],
		MAIL_ACC => (split('@', $mail))[0],
		MAIL_ADDR => $mail,
		MAIL_PASS => $self->{'mail_pass'},
		MAIL_TYPE => $self->{'mail_type'}
	};

	0;
}

1;
