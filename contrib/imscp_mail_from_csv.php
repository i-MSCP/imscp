<?php
/**
 * Copyright (C) 2015 by Laurent Declercq
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * Description:
 *
 * Script which allow to import mail accounts into i-MSCP using a CSV file as source.
 * CSV file entries must be as follow:
 *
 * user@domain.tld,password
 * user2@domain.tld,password
 * ...
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get mail data
 *
 * @throws iMSCP_Exception in case data are not found
 * @param string $domainName Domain name
 * @return array Array which contains mail data
 */
function cli_getMailData($domainName)
{
	static $data = array();

	if (!array_key_exists($domainName, $data)) {
		$stmt = exec_query('SELECT domain_id FROM domain WHERE domain_name = ?', $domainName);

		if ($stmt->rowCount()) {
			$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
			$data[$domainName] = array(
				'domain_id' => $row['domain_id'],
				'sub_id' => '0',
				'mail_type' => MT_NORMAL_MAIL
			);
		} else {
			$stmt = exec_query(
				"
					SELECT
						domain_id, subdomain_id
					FROM
						subdomain
					INNER JOIN
						domain USING(domain_id)
					WHERE
						CONCAT(subdomain_name, '.', domain_name) = ?
				",
				$domainName
			);

			if ($stmt->rowCount()) {
				$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
				$data[$domainName] = array(
					'domain_id' => $row['domain_id'],
					'sub_id' => $row['subdomain_id'],
					'mail_type' => MT_SUBDOM_MAIL
				);
			} else {
				$stmt = exec_query('SELECT domain_id FROM domain_aliasses WHERE alias_name = ?', $domainName);

				if ($stmt->rowCount()) {
					$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
					$data[$domainName] = array(
						'domain_id' => $row['domain_id'],
						'sub_id' => '0',
						'mail_type' => MT_ALIAS_MAIL
					);
				} else {
					$stmt = exec_query(
						"
							SELECT
								domain_id, subdomain_alias_id
							FROM
								subdomain_alias
							INNER JOIN
								domain_aliasses USING(alias_id)
							INNER JOIN
								domain USING(domain_id)
							WHERE
								CONCAT(subdomain_alias_name, '.', alias_name) = ?
						",
						$domainName
					);

					if ($stmt->rowCount()) {
						$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
						$data[$domainName] = array(
							'domain_id' => $row['domain_id'],
							'sub_id' => $row['subdomain_alias_id'],
							'mail_type' => MT_ALSSUB_MAIL
						);
					} else {
						$data[$domainName] = null;
					}
				}
			}
		}
	}

	if ($data[$domainName] !== null) {
		return $data[$domainName];
	}

	throw new iMSCP_Exception('This script can only add mail accounts for domains which are already managed by i-MSCP.');
}

/***********************************************************************************************************************
 * Main
 */

// Include i-MSCP core library
include '/var/www/imscp/gui/library/imscp-lib.php';

error_reporting(0);
ini_set('display_errors', 0);
ini_set('max_execution_time', 0);

// Full path to CSV file
if (isset($argv[1])) {
	$csvFilePath = $argv[1];
} else {
	printf("USAGE: php %s <FULL_PATH_TO_CSV_FILE>\n", $argv[0]);
	exit(1);
}

// csv column delimiter
$csvDelimiter = ',';

if (($handle = fopen($csvFilePath, 'r')) !== false) {
	$db = iMSCP_Database::getRawInstance();
	$stmt = $db->prepare(
		'
			INSERT INTO mail_users (
				mail_acc, mail_pass, mail_forward, domain_id, mail_type, sub_id, status, mail_auto_respond,
				mail_auto_respond_text, quota, mail_addr
			) VALUES (
				:mail_acc, :mail_pass, :mail_forward, :domain_id, :mail_type, :sub_id, :status, :mail_auto_respond,
				:mail_auto_respond_text, :quota, :mail_addr
			)
		'
	);

	// Create i-MSCP mail accounts using entries from CSV file
	while (($csvEntry = fgetcsv($handle, 1024, $csvDelimiter)) !== false) {
		$mailAddr = trim($csvEntry[0]);
		$asciiMailAddr = encode_idna($mailAddr);
		$mailPassword = trim($csvEntry[1]);

		try {
			if (!chk_email($asciiMailAddr)) {
				throw new iMSCP_Exception(sprintf('%s is not a valid email address.', $mailAddr));
			}

			if (checkPasswordSyntax($mailPassword)) {
				list($mailUser, $mailDomain) = explode('@', $asciiMailAddr);

				$mailAccount = array_merge(cli_getMailData($mailDomain), array(
					'mail_acc' => $mailUser,
					'mail_pass' => $mailPassword,
					'mail_forward' => '_no_',
					'status' => 'toadd',
					'mail_auto_respond' => '0',
					'mail_auto_respond_text' => null,
					'quota' => '0',
					'mail_addr' => $asciiMailAddr
				));

				try {
					$stmt->execute($mailAccount);
					printf("The %s mail account has been successfully inserted into the i-MSCP database.\n", $mailAddr);
				} catch (PDOException $e) {
					if ($e->getCode() == 23000) {
						printf("WARN:  The %s mail account already exists in the i-MSCP database. Skipping.\n", $mailAddr);
					} else {
						fwrite(STDERR, sprintf(
							"ERROR: Unable to insert the %s mail account in the i-MSCP database: %s\n",
							$mailAddr,
							$e->getMessage()
						));
					}
				}
			} else {
				throw new iMSCP_Exception(sprintf('Wrong password syntax or length for the %s mail account.', $mailAddr));
			}
		} catch (iMSCP_Exception $e) {
			fwrite(STDERR, sprintf("ERROR: %s mail account has been skipped: %s\n", $mailAddr, $e->getMessage()));
		}
	}

	if (send_request()) {
		print "Request has been successfully sent to i-MSCP daemon.\n";
	} else {
		fwrite(STDERR, "ERROR: Unable to send request to i-MSCP daemon.\n");
		exit(1);
	}
} else {
	fwrite(STDERR, sprintf("ERROR: Unable to open %s file.\n", $csvFilePath));
	exit(1);
}

exit(0);
