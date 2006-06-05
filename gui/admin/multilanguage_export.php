<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 by moleSoftware		            		|
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



include '../include/vhcs-lib.php';

check_login();


if (isset($_GET['export_lang']) && $_GET['export_lang'] !== ''){

  $language_table = $_GET['export_lang'];
  $query = <<<SQL_QUERY
            select
                msgid,
                msgstr
            from
                $language_table
SQL_QUERY;

    $rs = exec_query($sql, $query, array());

		if($rs->RecordCount() == 0)
        {
			set_page_message( tr("Incorrect data input!"));
			header( "Location: multilanguage.php" );
		    die();

		}

		while (!$rs -> EOF) {

					$msgid = $rs->fields['msgid'];
					$msgstr = $rs->fields['msgstr'];
					if ($msgid !== '' && $msgstr !== '') {
						echo $msgid." = ".$msgstr."\n";
					}

			$rs -> MoveNext();
		}


} else {

	set_page_message( tr("Incorrect data input!"));
	header( "Location: multilanguage.php" );
    die();
}

?>