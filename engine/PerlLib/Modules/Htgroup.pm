=head1 NAME

 Modules::Htgroup - i-MSCP Htgroup module

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

package Modules::Htgroup;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::Database;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP Htgroup module.

=head1 PUBLIC METHODS

=over 4

=item getType()

 Get module type

 Return string Module type

=cut

sub getType
{
	'Htgroup';
}

=item process($htgroupId)

 Process module

 Param int $htgroupId Htgroup unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
	my ($self, $htgroupId) = @_;

	my $rs = $self->_loadData($htgroupId);
	return $rs if $rs;

	my @sql;

	if($self->{'status'} ~~ ['toadd', 'tochange']) {
		$rs = $self->add();

		@sql = (
			"UPDATE htaccess_groups SET status = ? WHERE id = ?",
			($rs ? scalar getMessageByType('error') || 'Unknown error' : 'ok'),
			$htgroupId
		);
	} elsif($self->{'status'} eq 'todelete') {
		$rs = $self->delete();

		if($rs) {
			@sql = (
				'UPDATE htaccess_groups SET status = ? WHERE id = ?', scalar getMessageByType('error'), $htgroupId
			);
		} else {
			@sql = ('DELETE FROM htaccess_groups WHERE id = ?', $htgroupId);
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

=item _loadData($htgroupId)

 Load data

 Param int $htgroupId $Htgroup unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
	my ($self, $htgroupId) = @_;

	my $db = iMSCP::Database->factory();

	$db->doQuery('dummy', 'SET SESSION group_concat_max_len = 8192');

	my $rdata = $db->doQuery(
		'id',
		"
			SELECT
				t2.id, t2.ugroup, t2.status, t2.users, t3.domain_name, t3.domain_admin_id, t3.web_folder_protection
			FROM
				(
					SELECT * from htaccess_groups,
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
										'^(', (SELECT REPLACE((SELECT members FROM htaccess_groups WHERE id = ?), ',', '|')), ')\$'
									)
								)
							GROUP BY
								dmn_id
						), '') AS users
					) AS t1
				) AS t2
			INNER JOIN
				domain AS t3 ON (t2.dmn_id = t3.domain_id)
			WHERE
				id = ?
		",
		$htgroupId,
		$htgroupId
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$htgroupId}) {
		error("Htgroup record with ID $htgroupId has not been found in database");
		return 1;
	}

	unless(exists $rdata->{$htgroupId}->{'domain_name'}) {
		require Data::Dumper;
		Data::Dumper->import();
		local $Data::Dumper::Terse = 1;
		error('Orphan entry: ' . Dumper($rdata->{$htgroupId}));

		my @sql = (
			'UPDATE htaccess_groups SET status = ? WHERE id = ?',
			'Orphan entry: ' . Dumper($rdata->{$htgroupId}),
			$htgroupId
		);

		$db->doQuery('dummy', @sql);
		return 1;
	}

	%{$self} = (%{$self}, %{$rdata->{$htgroupId}});

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

		$self->{'httpd'} = {
			DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
			USER => $userName,
			GROUP => $groupName,
			WEB_DIR => "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}",
			HTGROUP_NAME => $self->{'ugroup'},
			HTGROUP_USERS => $self->{'users'},
			HTGROUP_DMN => $self->{'domain_name'},
			WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'}
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
__END__
