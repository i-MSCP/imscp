<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
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
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);
redirect_to_level_page();

$query = "
	UPDATE
		`domain`
	SET
		`domain_status` = 'toadd'
";

$rs = execute_query($sql, $query);
print "Domains updated";

$query = "
	UPDATE
		`domain_aliasses`
	SET
		`alias_status` = 'toadd'
";

$rs = execute_query($sql, $query);
print "Domain aliases updated";

$query = "
	UPDATE
		`subdomain`
	SET
		`subdomain_status` = 'toadd'
";

$rs = execute_query($sql, $query);
print "Subdomains updated";

$query = "
	UPDATE
		`subdomain_alias`
	SET
		`subdomain_alias_status` = 'toadd'
";

$rs = execute_query($sql, $query);
print "Subdomains alias updated";

$query = "
	UPDATE
		`mail_users`
	SET
		`status` = 'toadd'
";

$rs = execute_query($sql, $query);
print "Emails updated";
