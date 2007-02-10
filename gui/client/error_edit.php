<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 by moleSoftware	|
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


function gen_error_page_data(&$tpl, &$sql, $user_id, $eid)
{
  $query = <<<SQL_QUERY
        select
            error_401,
            error_403,
            error_404,
            error_500
        from
            error_pages
        where
            user_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($user_id));
	
	$error_401 = stripslashes($rs -> fields['error_401']);
	$error_403 = stripslashes($rs -> fields['error_403']);
	$error_404 = stripslashes($rs -> fields['error_404']);
	$error_500 = stripslashes($rs -> fields['error_500']);


  $error_standard_content = <<<RIC
<html>
<head>
<title>VHCS ERROR {EID}</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="/vhcs2/themes/modern_blue/css/vhcs.css" rel="stylesheet" type="text/css">
</head>
<body text="#000000">
<table width="100%" height="99%"  border="00" cellpadding="0" cellspacing="0" bgcolor="#334163">
  <tr>
    <td height="551"><table width="100%"  border="00" cellpadding="0" cellspacing="0">
      <tr bgcolor="#334163">

        <td>&nbsp;</td>
        <td align="right">&nbsp;</td>
      </tr>
      <tr>
        <td width="1" background="/vhcs2/themes/modern_blue/images/login/content_background.gif"><img src="/vhcs2/themes/modern_blue/images/login/content_background.gif" width="1" height="348"></td>
        <td height="348" align="center" background="/vhcs2/themes/modern_blue/images/login/content_background.gif">
		  <table width="600" height="200" border="00" cellpadding="1" cellspacing="3" bgcolor="#FF0000">

            <tr>
              <td align="center" bgcolor="#FFFFFF"></strong>
			  <h2><font color="#FF0000">Error {EID}!</font></h2>
                <br/>
                <b>{EID_STATUS}</b><br>

			  </td>
            </tr>

          </table>
		</td>
      </tr>
      <tr>
        <td width="1" height="2" background="/vhcs2/themes/modern_blue/images/login/content_down.gif"><img src="/vhcs2/themes/modern_blue/images/login/content_down.gif" width="2" height="2"></td>
        <td height="2" background="/vhcs2/themes/modern_blue/images/login/content_down.gif"><img src="/vhcs2/themes/modern_blue/images/login/content_down.gif" width="2" height="2"></td>
      </tr>
      <tr>
        <td width="1" bgcolor="#334163">&nbsp;</td>

        <td bgcolor="#334163"><a href="http://www.vhcs.net" target="_blank"><img src="/vhcs2/themes/modern_blue/images/login/vhcs_logo.gif" alt="VHCS - Virtual Hosting Control System - Control Panel" width="68" height="60" border="0"></a></td>
        </tr>
    </table></td>
  </tr>
</table>
</body>
</html>

RIC;



	if ($eid==401 && $error_401 != ''){
	$tpl -> assign(
                    array(
								'ERROR' => $error_401,

                         )
                  );
	}
	elseif ($eid==403 && $error_403 != ''){
	$tpl -> assign(
                    array(
								'ERROR' => $error_403,

                         )
                  );
	}
	elseif ($eid==404 && $error_404 != ''){
	$tpl -> assign(
                    array(
								'ERROR' => $error_404,

                         )
                  );
	}
	elseif ($eid==500 && $error_500 != ''){
	$tpl -> assign(
                    array(
								'ERROR' => $error_500,

                         )
                  );
	}

	else {
		switch ($eid){
            case 401:
               $eid_status= "Unauthorized !";
               break;
            case 403:
               $eid_status = "Forbidden !";
               break;
            case 404:
               $eid_status = "File not found !";
            break;
              case 500:
            $eid_status = "Internal server error !";
              break;
	    }

	$tpl -> assign(
                    array(
								'ERROR' => $error_standard_content,
								'EID_STATUS' => $eid_status

                         )
                  );
	}


}




include '../include/vhcs-lib.php';

check_login();

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/error_edit.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('logged_from', 'page');

//
// page functions.
//

function update_error_page(&$sql, $user_id)
{

}

//
// common page data.
//

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_CLIENT_ERROR_PAGE_TITLE' => tr('VHCS - Client/Manage Error Custom Pages'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
						'TID' => $_SESSION['layout_id'],
                        'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
						'ISP_LOGO' => get_logo($_SESSION['user_id'])
                     )
              );

//
// dynamic page data.
//
if (!isset( $_GET['eid'])) {

        set_page_message(tr('Server error - please choose error page'));

		 header("Location: error_pages.php");

		 die();
}
else{
	$eid = $_GET['eid'];
}


if ($eid==401 or $eid==403 or $eid==404 or $eid==500){
	gen_error_page_data($tpl, $sql, $_SESSION['user_id'], $_GET['eid']);
}
else{
$tpl -> assign(
                array(
                        'ERROR' => tr('Server error - please choose error page'),
						'EID' => tr('0')

                     )
              );
}

//
// static page messages.
//

gen_client_mainmenu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/main_menu_webtools.tpl');
gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_webtools.tpl');

gen_logged_from($tpl);

check_permissions($tpl);


$tpl -> assign(
                array(
                        'TR_ERROR_EDIT_PAGE' => tr('Edit error page'),
                        'TR_SAVE' => tr('Save'),
						'TR_CANCEL' => tr('Cancel'),
						'EID' => $eid

                     )
              );

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();
?>
