# i-MSCP Listener::Sender::Login::Maps listener file
# Copyright (C) 2015-2016 Sven Jantzen <info@svenjantzen.de>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301 USA

#
## Add "smtpd_sender_login_maps" to postfix.
## For all mail accounts and forward when forward domain is hostet by the same
## customer on this server.
## Note - All addresses are entered for forwarding can send for the forwarded mail address.
#

package Listener::Sender::Login::Maps;

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Database;
use Servers::mta;


########### EDIT THIS VALUE ###########
# "all" = mail accounts and forward mail
# "account" = only for mail accounts
my $login_maps_for = "all";
#######################################


#
## Please, don't edit anything below this line
#

sub create_sender_login_maps
{
	# Select mailadresses
	my $db = iMSCP::Database->factory();
	my $mailAdressQuery = $db->doQuery(
		'mail_addr',
		"SELECT
		mail_addr, mail_forward, domain_id, sub_id, SUBSTRING_INDEX(mail_type,',',1) AS mailTypeOne, If(SUBSTRING_INDEX(mail_type,',',1) = SUBSTRING_INDEX(mail_type,',',-1), NULL, SUBSTRING_INDEX(mail_type,',',-1)) AS mailTypeSecond
		FROM
		mail_users
		"
	);

	unless(ref $mailAdressQuery eq 'HASH') {
        error($mailAdressQuery);
        return 1;
    }

	my $fileValueAdress;
	my $mailTypeSelect = "mailTypeOne";
	
	if ( %{$mailAdressQuery} ) {
        for(keys %{$mailAdressQuery}) {
			
			my $mailType = $mailAdressQuery->{$_}->{$mailTypeSelect};

			# for mail accounts
			# type normal_mail and alias_mail
			if ( $mailType eq 'normal_mail' or $mailType eq 'alias_mail' or $mailType eq 'subdom_mail' ) {
				$fileValueAdress .= $mailAdressQuery->{$_}->{'mail_addr'};
				$fileValueAdress .= "\t";
				$fileValueAdress .= $mailAdressQuery->{$_}->{'mail_addr'};
				$fileValueAdress .= "\n";
			}

			# for mail forward
			if ( $login_maps_for eq "all" ) {
				# normal_forward or alias_forward or subdom_forward type
				if ( $mailType eq 'normal_forward' or $mailType eq 'alias_forward' or $mailType eq 'subdom_forward' ) {

					my @domainValideForward = split /,/, $mailAdressQuery->{$_}->{'mail_forward'};
					my $forwardMailAdressAccept;
					my $forwardMailAdress;

					foreach $forwardMailAdress (@domainValideForward) {
						my @domainValide = split /@/, $forwardMailAdress;

						my $selectCol;
						my $domainNameQuery;
						my $domainName;

						# normal_forward - alias_forward
						if ( $mailType eq 'normal_forward' or $mailType eq 'alias_forward' ) {
							$domainNameQuery = $db->doQuery(
									'domain_name',
								"
									SELECT
										domain_name
									FROM
										domain
									WHERE
										domain_status = 'ok' AND domain_id = '$mailAdressQuery->{$_}->{'domain_id'}'
								"
							);
							$selectCol = "domain_name";
						}

						# subdom_forward
						if ( $mailType eq 'subdom_forward' ) {
							$domainNameQuery = $db->doQuery(
								'name_subdom_forward',
								"
									SELECT
										CONCAT(T1.subdomain_name,'.',T2.domain_name) AS name_subdom_forward
									FROM
										subdomain T1 INNER JOIN domain T2
									ON
										T1.subdomain_id = '$mailAdressQuery->{$_}->{'sub_id'}' AND T2.domain_id = '$mailAdressQuery->{$_}->{'domain_id'}'
								"
							);
							$selectCol = "name_subdom_forward";
						}

						unless(ref $domainNameQuery eq 'HASH') {
							error($domainNameQuery);
							return 1;
						}
				
						if ( %{$domainNameQuery} ) {
							for(keys %{$domainNameQuery}) {
								$domainName = $domainNameQuery->{$_}->{$selectCol};
							}
						}

						# Check if $domainValide[1] is owner by the same customer as $domainName
						if ( $domainValide[1] eq $domainName ) {
							if (not defined $forwardMailAdressAccept) {
								$forwardMailAdressAccept = $forwardMailAdress;
							}
							else {
								$forwardMailAdressAccept .= "," . $forwardMailAdress;
							}
						}

						undef $domainNameQuery;
						undef $selectCol;
					}

					if ( defined $forwardMailAdressAccept && $forwardMailAdressAccept ne '' ) {
						$fileValueAdress .= $mailAdressQuery->{$_}->{'mail_addr'};
						$fileValueAdress .= "\t";
						$fileValueAdress .= $forwardMailAdressAccept;
						$fileValueAdress .= "\n";
					}
				
					undef $forwardMailAdressAccept;
					undef $mailType;
				}
			}

			if ( defined $mailAdressQuery->{$_}->{'mailTypeSecond'} && $mailTypeSelect eq 'mailTypeOne' ) {
				$mailTypeSelect = "mailTypeSecond";
				redo
			}
			else {
				$mailTypeSelect = "mailTypeOne";
			}
		}
	}


	# Save the result in sender_login_maps file
    my $fileSenderLoginMaps = "/etc/postfix/imscp/sender_login_maps";
    my $file = iMSCP::File->new( filename => $fileSenderLoginMaps );
	
    my $rs = $file->set($fileValueAdress);
    return $rs if $rs;

    $rs = $file->save();
    return $rs if $rs;

    # Create the hash file for postfix (sender_login_maps.db)
	Servers::mta->factory()->{'postmap'}->{$fileSenderLoginMaps} = 1;
	
    #$rs = execute("postmap $fileSenderLoginMaps", \my $stdout, \my $stderr);
	#debug($stdout) if $stdout;
    #error($stderr) if $stderr && $rs;

    0;
}

# is called every time when a customer add or delete a mailadress
sub main_cf_sender_login_maps
{
	my ($cfgTpl) = shift;

    my $replace = "smtpd_sender_login_maps = hash:/etc/postfix/imscp/sender_login_maps\n\n";
    $replace .= "smtpd_sender_restrictions = ";
    $replace .= "reject_authenticated_sender_login_mismatch,\n";
    $replace .= "\t\t\t\t";

	$$cfgTpl =~ s/(smtpd_sender_restrictions = )/$replace/m;
	
	0;
}

# Register event listeners on the event manager
my $eventManager = iMSCP::EventManager->getInstance();
$eventManager->register('beforeMtaPostmap', \&create_sender_login_maps);
$eventManager->register('afterMtaBuildMainCfFile', \&main_cf_sender_login_maps);

1;
__END__