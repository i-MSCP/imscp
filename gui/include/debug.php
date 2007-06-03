<?php
/**
 *  ispCP (OMEGA) a Virtual Hosting Control Panel
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 **/

function dump_gui_debug() {

    $source = array ('_SESSION', '_POST', '_GET', '_COOKIE',
                     '_FILES', 'GLOBALS','_SERVER');

    foreach ($source as $vname)
    {
        echo "<font color='#0000FF'><u>Content of <b>\$$vname</b>:</u><br /></font>";
        echo '<pre>';
        echo htmlentities(print_r($$vname, true));
        echo '</pre>';
    }

}

?>