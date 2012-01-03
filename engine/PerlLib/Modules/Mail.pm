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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category		i-MSCP
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::Mail;

use strict;
use warnings;
use iMSCP::Debug;
use Data::Dumper;

use vars qw/@ISA/;

@ISA = ('Common::SimpleClass', 'Modules::Abstract');
use Common::SimpleClass;
use Modules::Abstract;

sub _init{
	my $self		= shift;
	$self->{type}	= 'Mail';
}

sub loadData{

	my $self = shift;

	my $sql = '
		SELECT
			if(isnull(`t2`.`mail_addr`), "no", "yes") AS "haveCatchAll",
			`t1`.*
		FROM
			`mail_users`AS `t1`
		LEFT JOIN
			(SELECT `mail_addr` FROM `mail_users` WHERE `mail_addr` LIKE "@%") AS `t2`
		ON
			substr(`t1`.`mail_addr`, locate("@", `t1`.`mail_addr`)) = `t2`.`mail_addr`
		WHERE
			`t1`.`mail_id` = ?
	';
	my $rdata = iMSCP::Database->factory()->doQuery('mail_id', $sql, $self->{mailId});

	error("$rdata") and return 1 if(ref $rdata ne 'HASH');
	error("No mail has id = $self->{mailId}") and return 1 unless(exists $rdata->{$self->{mailId}});

	$self->{$_} = $rdata->{$self->{mailId}}->{$_} for keys %{$rdata->{$self->{mailId}}};

	0;
}

sub process{

	my $self		= shift;
	$self->{mailId}	= shift;

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;

	if($self->{status} =~ /^toadd|change|toenable$/){
		$rs = $self->add();
		@sql = (
			"UPDATE `mail_users` SET `status` = ? WHERE `mail_id` = ?",
			($rs ? scalar getMessageByType('ERROR') : 'ok'),
			$self->{mail_id}
		);
	}elsif($self->{status} =~ /^delete$/){
		$rs = $self->delete();
		if($rs){
			@sql = (
				"UPDATE `mail_users` SET `status` = ? WHERE `mail_id` = ?",
				scalar getMessageByType('ERROR'),
				$self->{mail_id}
			);
		}else {
			@sql = ("DELETE FROM `mail_users` WHERE `mail_id` = ?", $self->{mail_id});
		}
	}elsif($self->{status} =~ /^todisable$/){
		$rs = $self->disable();
		@sql = (
			"UPDATE `mail_users` SET `status` = ? WHERE `mail_id` = ?",
			($rs ? scalar getMessageByType('ERROR') : 'disabled'),
			$self->{mail_id}
		);
	}

	my $rdata = iMSCP::Database->factory()->doQuery('misc', @sql);
	error("$rdata") and return 1 if(ref $rdata ne 'HASH');

	$rs;
}

sub restore{
	0;
}

sub buildMTAData{

	my $self	= shift;
	my $mail	= $self->{mail_addr};
	$mail		=~ s/^\s+//;

	$self->{mta} = {
		DMN_NAME			=> (split('@', $mail))[1],
		MAIL_ACC			=> (split('@', $mail))[0],
		MAIL_ADDR			=> $mail,
		MAIL_CATCHALL		=> $self->{mail_acc},
		MAIL_PASS			=> $self->{mail_pass},
		MAIL_FORWARD		=> $self->{mail_forward},
		MAIL_TYPE			=> $self->{mail_type},
		MAIL_AUTO_RSPND		=> $self->{mail_auto_respond},
		MAIL_AUTO_RSPND_TXT	=> $self->{mail_auto_respond_text},
		MAIL_HAVE_CATCH_ALL	=> $self->{haveCatchAll},
		MAIL_STATUS			=> $self->{status},
		MAIL_ON_CATCHALL	=> undef
	};

	if($self->{mail_type} =~ m/_catchall/ && $self->{status} eq 'delete'){
		my $sql = "SELECT `mail_addr` FROM `mail_users` WHERE `mail_addr` LIKE '\%$self->{mail_addr}' AND `mail_type` LIKE '\%mail'";
		my $rdata = iMSCP::Database->factory()->doQuery('mail_addr', $sql);
		error("$rdata") and return 1 if(ref $rdata ne 'HASH');
		@{$self->{mta}->{MAIL_ON_CATCHALL}} = keys %{$rdata};
	}

	0;
}

sub buildPOData{

	my $self	= shift;
	my $mail	= $self->{mail_addr};
	$mail =~ s/^\s+//;

	$self->{po} = {
		DMN_NAME			=> (split('@', $mail))[1],
		MAIL_ACC			=> (split('@', $mail))[0],
		MAIL_ADDR			=> $mail,
		MAIL_PASS			=> $self->{mail_pass},
		MAIL_TYPE			=> $self->{mail_type},
	};

	0;
}

1;
