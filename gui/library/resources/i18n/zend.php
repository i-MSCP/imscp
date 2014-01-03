<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage	I18n
 * @copyright   2010-2014 by i-MSCP team
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

die('The ./gui/library/resources/i18n/zend.php file must not be loaded.');

/**
 * Dummy function.
 *
 * @return void
 */
function tr(){}

// Note this file is not intended to be loaded. It will simply parsed by the gettext
// tools when updating translation files. All translation string were extracted from
// the set of Zend validation classes currently used by i-MSCP. When adding new Zend
// validator, the developer must think to update this file. Same thing for the
// iMSCP_Validate class. When a developer provides custom errors messages, this file
// must be updated.


// Zend validators - shared strings
tr("Invalid type given. String expected");

// msgid for Zend_Validate_Hostname
tr("'%value%' appears to be a DNS hostname but the given punycode notation cannot be decoded");

tr("'%value%' appears to be a DNS hostname but contains a dash in an invalid position");
tr("'%value%' does not match the expected structure for a DNS hostname");
tr("'%value%' appears to be a DNS hostname but cannot match against hostname schema for TLD '%tld%'");
tr("'%value%' does not appear to be a valid local network name");
tr("'%value%' does not appear to be a valid URI hostname");
tr("'%value%' appears to be an IP address, but IP addresses are not allowed");
tr("'%value%' appears to be a local network name but local network names are not allowed");
tr("'%value%' appears to be a DNS hostname but cannot extract TLD part");
tr("'%value%' appears to be a DNS hostname but cannot match TLD against known list");

// msgid for Zend_Validate_EmailAddress
tr("'%value%' is no valid email address in the basic format local-part@hostname");
tr("'%hostname%' is no valid hostname for email address '%value%'");
tr("'%hostname%' does not appear to have a valid MX record for the email address '%value%'");
tr("'%hostname%' is not in a routable network segment. The email address '%value%' should not be resolved from public network");
tr("'%localPart%' can not be matched against dot-atom format");
tr("'%localPart%' can not be matched against quoted-string format");
tr("'%localPart%' is no valid local part for email address '%value%'");
tr("'%value%' exceeds the allowed length");

// msgid for Zend_Validate_Ip
tr("'%value%' does not appear to be a valid IP address");

// msgid for iMSCP_Validate
tr("'%value%' appears to be a domain name but the given punycode notation cannot be decoded");
tr("'%value%' appears to be a domain name but contains a dash in an invalid position");
tr("'%value%' does not match the expected structure for a domain name");
tr("'%value%' appears to be a domain name but cannot match against domain name schema for TLD '%tld%'");
tr("'%value%' appears to be a domain name but cannot extract TLD part");
tr("'%value%' appears to be a domain name but cannot match TLD against known list");

tr("'%value%' appears to be a subdomain name but the given punycode notation cannot be decoded");
tr("'%value%' appears to be a subdomain name but contains a dash in an invalid position");
tr("'%value%' does not match the expected structure for a subdomain name");
tr("'%value%' appears to be a subdomain name but cannot match against subdomain schema for TLD '%tld%'");
tr("'%value%' appears to be a subdomain name but cannot extract TLD part");
tr("'%value%' appears to be a subdomain name but cannot match TLD against known list");
