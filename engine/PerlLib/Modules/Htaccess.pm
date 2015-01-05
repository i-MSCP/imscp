#!/usr/bin/perl

=head1 NAME

 Modules::Htaccess - i-MSCP Htaccess module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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
# @copyright   2010-2015 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::Htaccess;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Database;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP Htaccess module.

=head1 PUBLIC METHODS

=over 4

=item getType()

 Get module type

 Return string Module type

=cut

sub getType
{
	'Htaccess';
}

=item process($htaccessId)

 Process module

 Param int $htaccessId Htaccess unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
	my ($self, $htaccessId) = @_;

	my $rs = $self->_loadData($htaccessId);
	return $rs if $rs;

	my @sql;

	if($self->{'status'} ~~ ['toadd', 'tochange']) {
		$rs = $self->add();

		@sql = (
			'UPDATE htaccess SET status = ? WHERE id = ?', ($rs ? scalar getMessageByType('error') : 'ok'), $htaccessId
		);
	} elsif($self->{'status'} eq 'todelete') {
		$rs = $self->delete();

		if($rs) {
			@sql = (
				'UPDATE htaccess SET status = ? WHERE id = ?', scalar getMessageByType('error'), $htaccessId
			);
		} else {
			@sql = ('DELETE FROM htaccess WHERE id = ?', $htaccessId);
		}
	}

	my $rdata = iMSCP::Database->factory()->doQuery('dummy', @sql);

	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _loadData($htaccessId)

 Load data

 Param int $htaccessId Htaccess unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
	my ($self, $htaccessId) = @_;

	my $db = iMSCP::Database->factory();

	$db->doQuery('dummy', 'SET SESSION group_concat_max_len = 8192');

	my $rdata = $db->doQuery(
		'id',
		"
			SELECT
				t3.id, t3.auth_type, t3.auth_name, t3.path, t3.status, t3.users, t3.groups, t4.domain_name,
				t4.domain_admin_id
			FROM
				(
					SELECT * FROM htaccess,
					(
						SELECT IFNULL(
						(
							SELECT
								group_concat(uname SEPARATOR ' ')
							FROM
								htaccess_users
							WHERE
								id regexp (
									CONCAT(
										'^(', (SELECT REPLACE((SELECT user_id FROM htaccess WHERE id = ?), ',', '|')), ')\$'
									)
								)
							GROUP BY
								dmn_id
						), '') AS users
					) AS t1,
					(
						SELECT IFNULL(
						(
							SELECT
								group_concat(ugroup SEPARATOR ' ')
							FROM
								htaccess_groups
							WHERE
								id regexp (
									CONCAT(
										'^(', (SELECT REPLACE((SELECT group_id FROM htaccess WHERE id = ?), ',', '|')), ')\$'
									)
								)
							GROUP BY
								dmn_id
						), '') AS groups
					) AS t2
				) AS t3
			INNER JOIN
				domain AS t4 ON (t3.dmn_id = t4.domain_id)
			WHERE
				t3.id = ?
		",
		$htaccessId,
		$htaccessId,
		$htaccessId
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$htaccessId}) {
		error("Htaccess record with ID $htaccessId has not been found in database");
		return 1;
	}

	unless(exists $rdata->{$htaccessId}->{'domain_name'}) {
		require Data::Dumper;
		Data::Dumper->import();
		local $Data::Dumper::Terse = 1;
		error("Orphan entry: " . Dumper($rdata->{$htaccessId}));

		my @sql = (
			'UPDATE htaccess SET status = ? WHERE id = ?', 'Orphan entry: ' . Dumper($rdata->{$htaccessId}), $htaccessId
		);
		$db->doQuery('dummy', @sql);
		return 1;
	}

	%{$self} = (%{$self}, %{$rdata->{$htaccessId}});

	0;
}

=item _getHttpdData($action)

 Data provider method for Httpd servers

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getHttpdData
{
	my ($self, $action) = @_;

	unless($self->{'httpd'}) {
		my $groupName = my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
			($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

		my $hDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}";
		my $pathDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}/$self->{'path'}";
		$pathDir =~ s~/+~/~g;
		$hDir =~ s~/+~/~g;

		$self->{'httpd'} = {
			DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
			USER => $userName,
 			GROUP => $groupName,
			AUTH_TYPE => $self->{'auth_type'},
			AUTH_NAME => $self->{'auth_name'},
			AUTH_PATH => $pathDir,
			HOME_PATH => $hDir,
			DOMAIN_NAME => $self->{'domain_name'},
			HTUSERS => $self->{'users'},
			HTGROUPS => $self->{'groups'}
		};
	}

	%{$self->{'httpd'}};
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
