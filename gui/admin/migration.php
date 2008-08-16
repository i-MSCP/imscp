<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);
redirect_to_level_page();

$query = <<<SQL_QUERY

update

domain

set

domain_status = 'toadd'


SQL_QUERY;

$rs = execute_query($sql, $query);
print "Domains updated";

$query = <<<SQL_QUERY

        update

            domain_aliasses

        set

            alias_status = 'toadd'


SQL_QUERY;

$rs = execute_query($sql, $query);
print "Domain aliases updated";

$query = <<<SQL_QUERY

        update

            subdomain

        set

            subdomain_status = 'toadd'


SQL_QUERY;

$rs = execute_query($sql, $query);
print "Subdomains updated";

$query = <<<SQL_QUERY

        update

            mail_users

        set

             status = 'toadd'


SQL_QUERY;

$rs = execute_query($sql, $query);
print "Emails updated";

?>