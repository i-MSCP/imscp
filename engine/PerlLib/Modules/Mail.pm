=head1 NAME

 Modules::Mail - i-MSCP Mail module

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

package Modules::Mail;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::Database;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP Mail module.

=head1 PUBLIC METHODS

=over 4

=item getType()

 Get module type

 Return string Module type

=cut

sub getType
{
	'Mail';
}

=item process($mailId)

 Process module

 Param int $mailId Mail unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
	my ($self, $mailId) = @_;

	my $rs = $self->_loadData($mailId);
	return $rs if $rs;

	my @sql;

	if($self->{'status'} ~~ [ 'toadd', 'tochange', 'toenable' ]) {
		$rs = $self->add();

		@sql = (
			'UPDATE mail_users SET status = ? WHERE mail_id = ?',
			($rs ? scalar getMessageByType('error') || 'Unknown error' : 'ok'), $mailId
		);
	} elsif($self->{'status'} eq 'todelete') {
		$rs = $self->delete();

		if($rs){
			@sql = (
				'UPDATE mail_users SET status = ? WHERE mail_id = ?',
				scalar getMessageByType('error') || 'Unknown error',
				$mailId
			);
		} else {
			@sql = ('DELETE FROM mail_users WHERE mail_id = ?', $self->{'mail_id'});
		}
	} elsif($self->{'status'} eq 'todisable') {
		$rs = $self->disable();

		@sql = (
			'UPDATE mail_users SET status = ? WHERE mail_id = ?',
			($rs ? scalar getMessageByType('error') || 'Unknown error' : 'disabled'), $mailId
		);
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

=item _loadData($mailId)

 Load data

 Param int $mailId Mail unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
	my ($self, $mailId) = @_;

	my $row = iMSCP::Database->factory()->doQuery(
		'mail_id',
		'
			SELECT
				t1.mail_id, t1.mail_acc, t1.mail_pass, t1.mail_forward, t1.mail_type, t1.status, t1.mail_auto_respond,
				t1.quota, t1.mail_addr,
				COUNT(t2.mail_addr) AS mail_has_catch_all,
				COUNT(t3.mail_addr) AS mail_dmn_has_auto_responder
			FROM
				mail_users AS t1
			LEFT JOIN (
				SELECT mail_addr FROM mail_users WHERE status NOT IN("todisable", "todelete") AND mail_addr LIKE "@%"
			) AS t2 ON (substr(t1.mail_addr, locate("@", t1.mail_addr)) = t2.mail_addr)
			LEFT JOIN (
				SELECT mail_addr FROM mail_users WHERE status NOT IN("todisable", "todelete") AND mail_auto_respond = 1
			) AS t3 ON(t3.mail_addr LIKE concat("%", substr(t1.mail_addr, locate("@", t1.mail_addr))))
			WHERE
				t1.mail_id = ?
		',
		$mailId
	);
	unless(ref $row eq 'HASH') {
		error($row);
		return 1;
	}

	unless(exists $row->{$mailId}) {
		error("Mail record with ID $mailId has not been found in database");
		return 1;
	}

	%{$self} = (%{$self}, %{$row->{$mailId}});

	0;
}

=item _getMtaData($action)

 Data provider method for MTA servers

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getMtaData
{
	my $self = shift;

	unless($self->{'mta'}) {
		(my $mailAddr = $self->{'mail_addr'}) =~ s/^\s+//;
		my ($localPart, $domainPart) = split '@', $mailAddr;

		$self->{'mta'} = {
			DOMAIN_NAME => $domainPart,
			MAIL_ACC => $localPart,
			MAIL_ADDR => $mailAddr,
			MAIL_CATCHALL => $self->{'mail_acc'},
			MAIL_PASS => $self->{'mail_pass'},
			MAIL_FORWARD => $self->{'mail_forward'},
			MAIL_TYPE => $self->{'mail_type'},
			MAIL_HAS_AUTO_RESPONDER => $self->{'mail_auto_respond'},
			MAIL_DOMAIN_HAS_AUTO_RESPONDER => $self->{'mail_dmn_has_auto_responder'},
			MAIL_STATUS => $self->{'status'},
			MAIL_HAS_CATCH_ALL => $self->{'mail_has_catch_all'},
		};

		if($self->{'mail_has_catch_all'}) {
			my $rows = iMSCP::Database->factory()->doQuery(
				'mail_addr',
				'SELECT mail_addr FROM mail_users WHERE mail_addr LIKE ? AND mail_type LIKE ? AND mail_auto_respond = 0',
				'%' . $self->{'mail_addr'},
				'%' . 'mail'
			);
			unless(ref $rows eq 'HASH') {
				fatal($rows);
			}

			@{$self->{'mta'}->{'MAIL_ON_CATCHALL'}} = keys %{$rows};
		}
	}

	%{$self->{'mta'}};
}

=item _getPoData($action)

 Data provider method for IMAP/POP3 servers

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getPoData
{
	my $self = shift;

	unless($self->{'po'}) {
		(my $mailAddr = $self->{'mail_addr'}) =~ s/^\s+//;
		my ($localPart, $domainPart) = split '@', $mailAddr;

		$self->{'po'} = {
			DOMAIN_NAME => $domainPart,
			MAIL_ACC => $localPart,
			MAIL_PASS => $self->{'mail_pass'},
			MAIL_TYPE => $self->{'mail_type'},
			MAIL_QUOTA => $self->{'quota'},
			MAIL_ADDR => $mailAddr
		};
	}

	%{$self->{'po'}};
}

=item _getPackagesData($action)

 Data provider method for i-MSCP packages

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getPackagesData
{
	my ($self, $action) = @_;

	unless($self->{'packages'}) {
		(my $mailAddr = $self->{'mail_addr'}) =~ s/^\s+//;
		my ($localPart, $domainPart) = split '@', $mailAddr;

		$self->{'packages'} = {
			DOMAIN_NAME => $domainPart,
			MAIL_ACC => $localPart,
			MAIL_PASS => $self->{'mail_pass'},
			MAIL_TYPE => $self->{'mail_type'},
			MAIL_ADDR => $mailAddr
		};
	}

	%{$self->{'packages'}};
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
