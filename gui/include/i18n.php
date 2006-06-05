<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 by moleSoftware		            	|
//  |			http://vhcs.net | http://www.molesoftware.com		           		|
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the MPL General Public License                   |
//  | as published by the Free Software Foundation; either version 1.1              |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//  | You should have received a copy of the MPL Mozilla Public License             |
//  | along with this program; if not, write to the Open Source Initiative (OSI)    |
//  | http://opensource.org | osi@opensource.org								    |
//  |                                                                               |
//   -------------------------------------------------------------------------------



function tr($msgid) 
{
    
    global $sql, $default_lang;
    
    $default_lang = $_SESSION['user_def_lang'];
    
    if (!$sql) {
        return $msgid;
    }

    $table = $default_lang;
    
    $res   = $sql->Execute("select msgstr from $table where msgid='$msgid'");

    if(!$res) {
        return $msgid;
    }

    $num = $res->RowCount();
    if ($num == 0) {
        return $msgid;
    }
    else {
        $data = $res->FetchRow();
        if ($data['msgstr'] == '') {
            return $msgid;
        }
        else {
            return $data['msgstr'];
        }
    }
}

?>
