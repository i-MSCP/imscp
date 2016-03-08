=head1 NAME

 Modules::Htusers - i-MSCP Htusers module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by internet Multi Server Control Panel
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

package Modules::Htusers;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Database;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP Htuser module.

=head1 PUBLIC METHODS

=over 4

=item getType()

 Get module type

 Return string Module type

=cut

sub getType
{
	'Htuser';
}

=item process($htuserId)

 Process module

 Param int $htuserId Htuser unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
	my ($self, $htuserId) = @_;

	my $rs = $self->_loadData($htuserId);
	return $rs if $rs;

	my @sql;
	if(grep($_ eq $self->{'status'}, ( 'toadd', 'tochange' ))) {
		$rs = $self->add();
		@sql = (
			'UPDATE htaccess_users SET status = ? WHERE id = ?',
			($rs ? scalar getMessageByType('error') || 'Unknown error' : 'ok'), $htuserId
		);
	} elsif($self->{'status'} eq 'todelete') {
		$rs = $self->delete();
		if($rs) {
			@sql = ('UPDATE htaccess_users SET status = ? WHERE id = ?', scalar getMessageByType('error'), $htuserId);
		} else {
			@sql = ('DELETE FROM htaccess_users WHERE id = ?', $htuserId);
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

=item _loadData($htuserId)

 Load data

 Param int $htuserId Htuser unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
	my ($self, $htuserId) = @_;

	my $rdata = iMSCP::Database->factory()->doQuery(
		'id',
		'
			SELECT t1.uname, t1.upass, t1.status, t1.id, t2.domain_name, t2.domain_admin_id, t2.web_folder_protection
			FROM htaccess_users AS t1 INNER JOIN domain AS t2 ON (t1.dmn_id = t2.domain_id)
			WHERE t1.id = ?
		',
		$htuserId
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$htuserId}) {
		error(sprintf('Htuser record with ID %s has not been found in database', $htuserId));
		return 1;
	}

	unless(exists $rdata->{$htuserId}->{'domain_name'}) {
		require Data::Dumper;
		Data::Dumper->import();

		local $Data::Dumper::Terse = 1;
		error('Orphan entry: ' . Dumper($rdata->{$htuserId}));

		my @sql = (
			'UPDATE htaccess_users SET status = ? WHERE id = ?', 'Orphan entry: ' . Dumper($rdata->{$htuserId}),
			$htuserId
		);
		my $rdata = iMSCP::Database->factory()->doQuery('update', @sql);
		return 1;
	}

	%{$self} = (%{$self}, %{$rdata->{$htuserId}});
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

	return %{$self->{'httpd'}} if $self->{'httpd'};

	my $groupName = my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

	$self->{'httpd'} = {
		DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
		USER => $userName,
		GROUP => $groupName,
		WEB_DIR => "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}",
		HTUSER_NAME => $self->{'uname'},
		HTUSER_PASS => $self->{'upass'},
		HTUSER_DMN => $self->{'domain_name'},
		WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'}
	};
	%{$self->{'httpd'}};
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
