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

package Modules::Htusers;

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
	$self->{type}	= 'Htuser';
}

sub loadData{

	my $self = shift;

	my $sql = "
		SELECT
			`t1`.`uname`, `t1`.`upass`, `t1`.`status`, `t1`.`id`, `t2`.`domain_name`
		FROM
			`htaccess_users` as `t1`
		LEFT JOIN
			`domain` as `t2`
		ON
			`t1`.`dmn_id` = `t2`.`domain_id`
		WHERE
			`t1`.`id` = ?
	";

	my $rdata = iMSCP::Database->factory()->doQuery('id', $sql, $self->{htuserId});

	error("$rdata") and return 1 if(ref $rdata ne 'HASH');
	error("No user in table htaccess_users has id = $self->{htuserId}") and return 1 unless(exists $rdata->{$self->{htuserId}});

	unless($rdata->{$self->{htuserId}}->{domain_name}){
		local $Data::Dumper::Terse = 1;
		error("Orphan entry: ".Dumper($rdata->{$self->{htuserId}}));
		my @sql = (
			"UPDATE `htaccess_users` SET `status` = ? WHERE `id` = ?",
			"Orphan entry: ".Dumper($rdata->{$self->{htuserId}}),
			$self->{htuserId}
		);
		my $rdata = iMSCP::Database->factory()->doQuery('update', @sql);
		return 1;
	}

	$self->{$_} = $rdata->{$self->{htuserId}}->{$_} for keys %{$rdata->{$self->{htuserId}}};

	0;
}

sub process{

	my $self		= shift;
	$self->{htuserId}	= shift;

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;

	if($self->{status} =~ /^toadd|change$/){
		$rs = $self->add();
		@sql = (
			"UPDATE `htaccess_users` SET `status` = ? WHERE `id` = ?",
			($rs ? scalar getMessageByType('ERROR') : 'ok'),
			$self->{id}
		);
	}elsif($self->{status} =~ /^delete$/){
		$rs = $self->delete();
		if($rs){
			@sql = (
				"UPDATE `htaccess_users` SET `status` = ? WHERE `id` = ?",
				scalar getMessageByType('ERROR'),
				$self->{id}
			);
		}else {
			@sql = ("DELETE FROM `htaccess_users` WHERE `id` = ?", $self->{id});
		}
	}

	my $rdata = iMSCP::Database->factory()->doQuery('delete', @sql);
	error("$rdata") and return 1 if(ref $rdata ne 'HASH');

	$rs;
}

sub buildHTTPDData{

	my $self	= shift;

	$self->{httpd} = {
		HTUSER_NAME	=> $self->{uname},
		HTUSER_PASS	=> $self->{upass},
		HTUSER_DMN	=> $self->{domain_name},
	};

	0;
}

1;
