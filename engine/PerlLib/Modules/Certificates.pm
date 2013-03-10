#!/usr/bin/perl

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
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::Certificates;

use strict;
use warnings;

use iMSCP::Debug;
use Data::Dumper;
use File::Temp;
use iMSCP::File;
use iMSCP::Dir;
use Modules::openssl;
use parent 'Modules::Abstract';

sub _init
{
	my $self = shift;

	$self->{'type'} = 'Certificates';

	$self;
}

sub loadData
{
	my $self = shift;

	my $sql = "SELECT * FROM `ssl_certs` WHERE `cert_id` = ?";

	my $certData = iMSCP::Database->factory()->doQuery('cert_id', $sql, $self->{'cert_id'});
	if(ref $certData ne 'HASH') {
		error($certData);
		return 1;
	}

	unless(exists $certData->{$self->{'cert_id'}}) {
		error("No record in table cert_ssl has id = $self->{'cert_id'}");
		return 1;
	}

	$self->{$_} = $certData->{$self->{'cert_id'}}->{$_} for keys %{$certData->{$self->{'cert_id'}}};

	if($self->{'type'} eq 'dmn') {
		$sql = 'SELECT `domain_name` AS `name`, `domain_id` AS `id` FROM `domain` WHERE `domain_id` = ?';
	} elsif($self->{'type'} eq 'als') {
		$sql = 'SELECT `alias_name` AS `name`, `alias_id` AS `id` FROM `domain_aliasses` WHERE `alias_id` = ?';
	} elsif($self->{'type'} eq 'sub') {
		$sql = 'SELECT CONCAT(`subdomain_name`, \'.\', `domain_name`) AS `name`, `subdomain_id` AS `id` FROM `subdomain` LEFT JOIN `domain` USING(`domain_id`) WHERE `subdomain_id` = ?';
	} else {
		$sql = 'SELECT CONCAT(`subdomain_alias_name`, \'.\', `alias_name`) AS `name`, `subdomain_alias_id` AS `id` FROM `subdomain_alias` LEFT JOIN `domain_aliasses` USING(`alias_id`) WHERE `subdomain_alias_id` = ?';
	}

	my $rdata = iMSCP::Database->factory()->doQuery('id', $sql, $self->{'id'});
	if(ref $rdata ne 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$self->{'id'}}) {
		error("No record in table $self->{type} has id = $self->{id}");
		return 1;
	}

	unless($rdata->{$self->{'id'}}->{'name'}) {
		local $Data::Dumper::Terse = 1;
		error('Orphan entry: ' . Dumper($certData->{$self->{'cert_id'}}));

		my @sql = (
			"UPDATE `ssl_certs` SET `status` = ? WHERE `cert_id` = ?",
			"Orphan entry: " . Dumper($rdata->{$self->{'cert_id'}}),
			$self->{'cert_id'}
		);

		my $rdata = iMSCP::Database->factory()->doQuery('update', @sql);
		return 1;
	}

	$self->{'name'} = $rdata->{$self->{'id'}}->{'name'};

	0;
}

sub process
{
	my $self = shift;
	$self->{'cert_id'} = shift;

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;

	if($self->{'status'} =~ /^toadd|change$/){
		$rs = $self->add();
		@sql = (
			"UPDATE `ssl_certs` SET `status` = ? WHERE `cert_id` = ?",
			($rs ? scalar getMessageByType('error') : 'ok'),
			$self->{'cert_id'}
		);
	}elsif($self->{'status'} =~ /^delete$/){
		$rs = $self->delete();
		if($rs){
			@sql = (
				"UPDATE `ssl_certs` SET `status` = ? WHERE `cert_id` = ?",
				scalar getMessageByType('error'),
				$self->{'cert_id'}
			);
		}else {
			@sql = ("DELETE FROM `ssl_certs` WHERE `cert_id` = ?", $self->{'cert_id'});
		}
	}

	my $rdata = iMSCP::Database->factory()->doQuery('dummy', @sql);
	if(ref $rdata ne 'HASH') {
		error($rdata);
		return 1;
	}

	$rs;
}

sub add
{
	my $self = shift;
	my $rs = 0;
	my $rootUser = $main::imscpConfig{'ROOT_USER'};
	my $rootGroup = $main::imscpConfig{'ROOT_GROUP'};
	my $certPath = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs";

	$rs = iMSCP::Dir->new('dirname' => $certPath)->make(
		{ 'mode' => 0750, 'owner' => $rootUser, 'group' => $rootGroup }
	);
	return $rs if $rs;

	my $certFH = File::Temp->new('DIR' => '/tmp', 'UNLINK' => 1, 'OPEN' => 0);

	my $file = iMSCP::File->new('filename' => $certFH->filename);
	$file->set($self->{'cert'});
	$rs = $file->save();
	return $rs if $rs;

	Modules::openssl->getInstance()->{'cert_path'} = $certFH->filename;

	my $keyFH = File::Temp->new('DIR' => '/tmp', 'UNLINK' => 1, 'OPEN' => 0);
	$file = iMSCP::File->new('filename' => $keyFH->filename);
	$file->set($self->{key});
	$rs = $file->save();
	return $rs if $rs;

	Modules::openssl->getInstance()->{'key_path'} = $keyFH->filename;

	my $caFH;

	if($self->{'ca_cert'}) {
		$caFH = File::Temp->new('DIR' => '/tmp', 'UNLINK' => 1, 'OPEN' => 0);

		$file = iMSCP::File->new('filename' => $caFH->filename);
		$file->set($self->{'ca_cert'});
		$rs = $file->save();
		return $rs if $rs;

		Modules::openssl->getInstance()->{'intermediate_cert_path'} = $caFH->filename;
	} else {
		Modules::openssl->getInstance()->{'intermediate_cert_path'} = '';
	}

	Modules::openssl->getInstance()->{'openssl_path'} = $main::imscpConfig{'CMD_OPENSSL'};

	Modules::openssl->getInstance()->{'key_pass'} = $self->{'password'};
	$rs = Modules::openssl->getInstance()->ssl_check_all();
	return $rs if $rs;

	Modules::openssl->getInstance()->{'new_cert_path'} = $certPath,
	Modules::openssl->getInstance()->{'new_cert_name'} = $self->{'name'};

	Modules::openssl->getInstance()->{'cert_selfsigned'} = 0;
	Modules::openssl->getInstance()->ssl_export_all();

	0;
}

sub delete
{
	my $self = shift;
	my $rs = 0;
	my $rootUser = $main::imscpConfig{'ROOT_USER'};
	my $rootGroup = $main::imscpConfig{'ROOT_GROUP'};
	my $certPath = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs";
	my $cert = "$certPath/$self->{'name'}.pem";

	$rs = iMSCP::Dir->new('dirname' => $certPath)->make(
		{ 'mode' => 0750, 'owner' => $rootUser, 'group' => $rootGroup }
	);
	return $rs if $rs;

	$rs = iMSCP::File->new('filename' => $cert)->delFile($cert) if -f $cert;
	return $rs if $rs;

	0;
}

1;
