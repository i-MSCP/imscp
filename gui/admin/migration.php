<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
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
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

require '../include/imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);
redirect_to_level_page();

$query = "
	UPDATE
		`domain`
	SET
		`domain_status` = 'toadd'
";

$rs = execute_query($query);
print "Domains updated";

$query = "
	UPDATE
		`domain_aliasses`
	SET
		`alias_status` = 'toadd'
";

$rs = execute_query($query);
print "Domain aliases updated";

$query = "
	UPDATE
		`subdomain`
	SET
		`subdomain_status` = 'toadd'
";

$rs = execute_query($query);
print "Subdomains updated";

$query = "
	UPDATE
		`subdomain_alias`
	SET
		`subdomain_alias_status` = 'toadd'
";

$rs = execute_query($query);
print "Subdomains alias updated";

$query = "
	UPDATE
		`mail_users`
	SET
		`status` = 'toadd'
";

$rs = execute_query($query);
print "Emails updated";
