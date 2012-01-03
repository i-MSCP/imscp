<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
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
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     Core
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Autoloading classes
 *
 * @param string $className Class name to be loaded
 * @return void
 */
function autoload_class($className)
{
    $path = str_replace('_', '/', $className);

    if (file_exists(LIBRARY_PATH . '/' . $path . '.php')) {
        require_once LIBRARY_PATH . '/' . $path . '.php';
    } elseif(file_exists(LIBRARY_PATH . '/vendor/' . $path . '.php')) {
		require_once LIBRARY_PATH . '/vendor/' . $path . '.php';
	}
}
