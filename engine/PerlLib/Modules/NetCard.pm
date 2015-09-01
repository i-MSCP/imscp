=head1 NAME

 Modules::NetCard - i-MSCP NetCard module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Modules::NetCard;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Provider::NetworkInterface;
use parent 'Common::Object';

=head1 DESCRIPTION

 i-MSCP NetCard module.

=head1 PUBLIC METHODS

=over 4

=item process()

 Process module

 Return int 0 on success, die on failure

=cut

sub process
{
	my $niProvider = iMSCP::Provider::NetworkInterface->getInstance();
	my $dbh = iMSCP::Database->factory()->getRawDb();

	my $sth = $dbh->prepare('SELECT * FROM server_ips WHERE ip_status <> ?');
	$sth or die(sprintf('Could not prepare SQL statement: %s' , $dbh->errstr));
	$sth->execute('ok') or die(sprintf('Could not execute prepared statement: %s', $dbh->errstr));

	while (my $row = $sth->fetchrow_hashref()) {
		my ($sth2, @params);
		local $@;
		eval {
			if($row->{'ip_status'} ~~ [ 'toadd', 'tochange' ]) {
				$niProvider->addIpAddr({
					id => $row->{'ip_id'}, ip_card => $row->{'ip_card'}, ip_address => $row->{'ip_number'}
				});
				$sth2 = $dbh->prepare('UPDATE server_ips SET ip_status = ? WHERE ip_id = ?');
				@params = ('ok', $row->{'ip_id'});
			} elsif($row->{'ip_status'} eq 'todelete') {
				$niProvider->removeIpAddr({
					id => $row->{'ip_id'}, ip_card => $row->{'ip_card'}, ip_address => $row->{'ip_number'}
				});
				$sth2 = $dbh->prepare('DELETE FROM server_ips WHERE ip_id = ?');
				@params = ($row->{'ip_id'});
			}

			$sth2 or die(sprintf('Could not prepare SQL statement: %s' , $dbh->errstr));
			$sth2->execute(@params) or die(sprintf('Could not execute prepared statement: %s', $dbh->errstr));
		};
		if($@) {
			my $error = $@;
			$sth2 = $dbh->prepare('UPDATE server_ips SET ip_status = ? WHERE ip_id = ?');
			$sth2->execute($error || 'Unknown error', $row->{'ip_id'}) or die(sprintf(
				'Could not execute prepared statement: %s', $dbh->errstr
			));
			die($@);
		}
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
