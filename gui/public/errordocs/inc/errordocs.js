/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010-2013 by i-MSCP | http://i-mscp.net
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
 */

function getUri() {
    uri = 'unknown';

	if (document.location.pathname !== undefined) {
		uri = document.location.pathname.replace( /[<]/g, "&lt;").replace(/[>]/g, "&gt;");;

        if(document.location.search !== undefined) {
           uri = uri + document.location.search;
        }
	}

	return uri;
}
