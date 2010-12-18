<?php
/**
 * iMSCP complete domain backup/restore tool
 * Restore application
 *
 * @copyright 	2010 Thomas Wacker
 * @author 		Thomas Wacker <zuhause@thomaswacker.de>
 * @version 	SVN: $Id$
 *
 * @license
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

interface iBackupPackage
{
	public function __construct($domain_name, $password, $log_level);
	public function getDomainConfig();
	public function getEMailConfig();
	public function getFTPConfig();
	public function getDomainAliasConfig();
	public function getSubDomainConfig();
	public function getWebUserConfig();
	public function getWebGroupConfig();
	public function getWebAccessConfig();
	public function getDNSConfig();
	public function getDBConfig();
	public function getDBUserConfig();
	public function runPackager();
}
