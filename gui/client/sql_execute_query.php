<?php
/**
* ispCP ω (OMEGA) a Virtual Hosting Control System
*
* @copyright 2001-2006 by moleSoftware GmbH
* @copyright 2006-2007 by ispCP | http://isp-control.net
* @link http://isp-control.net
* @author ispCP Team (2007)
*
* @license
* 	This program is free software; you can redistribute it and/or modify it under
*   the terms of the MPL General Public License as published by the Free Software
*   Foundation; either version 1.1 of the License, or (at your option) any later
*   version.
*   You should have received a copy of the MPL Mozilla Public License along with
*   this program; if not, write to the Open Source Initiative (OSI)
*   http://opensource.org | osi@opensource.org
*/

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'] . '/sql_execute_query.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('sql_result', 'page');

if (isset($_GET['id'])) {
    $db_user_id = $_GET['id'];
} else if (isset($_POST['id'])) {
    $db_user_id = $_POST['id'];
} else {
    user_goto('manage_sql.php');
}

// page functions.

function sql_rs2html(&$rs, $tbl_props = false, $tpl_col_names = false, $htmlspecialchars = true) {
    $s = '';
    $rows = 0;
    $docnt = false;
    $res = '';
    $gSQLMaxRows = 100;
    $gSQLBlockRows = 20;

    if (!$rs) return false;

    if (!$tbl_props) $tbl_props = 'width="100%" border="0" cellspacing="1" cellpadding="0"';

    $typearr = array();
    $ncols = $rs->FieldCount();
    $hdr = "<table cols=\"$ncols\" $tbl_props>\n\t<tr>\n";
    // we'll generate column names here
    for ($i = 0; $i < $ncols; $i++) {
        $field = $rs->FetchField($i);
        if ($tpl_col_names) {
            $fname = $tpl_col_names[$i];
        } else {
            $fname = htmlspecialchars($field->name);
        }
        $typearr[$i] = $rs->MetaType($field->type, $field->max_length);
        // print " $field->name $field->type $typearr[$i] ";
        if (strlen($fname) == 0)
			$fname = '&nbsp;';
        $hdr .= "\t\t<td class=\"content4\"><b>$fname</b></td>\n";
    }
    // print $hdr."\t</tr>\n";
    $res .= $hdr . "\t</tr>\n";
    // smart algorithm - handles ADODB_FETCH_MODE's correctly!
    $numoffset = isset($rs->fields[0]);

    while (!$rs->EOF) {
        $s .= "\t<tr valign=\"top\">\n";
        for ($i = 0, $v = ($numoffset) ? $rs->fields[0] : reset($rs->fields);
            $i < $ncols;
            $i++, $v = ($numoffset) ? @$rs->fields[$i] : next($rs->fields)) {
            $type = $typearr[$i];
            switch ($type) {
                case 'T':
                    $s .= "\t\t<td class=\"content4\">\n" . $rs->UserTimeStamp($v, "D d, M Y, h:i:s") . "&nbsp;</td>\n";
                    break;
                case 'D':
                    $s .= "\t\t<td class=\"content4\">\n" . $rs->UserDate($v, "D d, M Y") . "&nbsp;</td>\n";
                    break;
                case 'I':
                case 'R':
                case 'N':
                    $s .= "\t\t<td class=\"content4\">\n" . stripslashes((trim($v))) . "&nbsp;</td>\n";
                    break;
                default:
                    if ($htmlspecialchars)
						$v = htmlspecialchars($v);
                    $s .= "\t\t<td class=\"content4\">\n" . str_replace("\n", '<br />', stripslashes((trim($v)))) . "&nbsp;</td>\n";
            }
        }

        $s .= "\t</tr>\n";
        $rows += 1;

        if ($rows >= $gSQLMaxRows) {
            $rows = "<p>Truncated at $gSQLMaxRows</p>";
            break;
        }

        $rs->MoveNext();
        // additional EOF check to prevent a widow header
        if (!$rs->EOF && $rows % $gSQLBlockRows == 0) {
            // if (connection_aborted()) break;// not needed as PHP aborts script, unlike ASP
            // print $s . "</table><br>\n\n";
            $res .= $s . "</table><br />\n\n";
            $s = $hdr;
        }
    } // while
    // print $s."</table>\n\n";
    $res .= $s . "</table>\n\n";

    if ($docnt) print "<h2>" . $rows . " Rows</h2>";
    return $res;
}

function execute_sql_query(&$tpl, &$sql, $user_id, $db_user_id) {
    global $cfg;

    if (!isset($_POST['uaction'])) return;

    // let's check user input;

    if ($_POST['sql_query'] === '') {
        set_page_message(tr('Please enter SQL query!'));
        $tpl->assign('SQL_RESULT', '');
        return;
    }

    $dmn_id = get_user_domain_id($sql, $user_id);
    $query = <<<SQL_QUERY
       SELECT
           t1.*,
           t2.sqld_name
       FROM
           sql_user AS t1,
           sql_database AS t2
       WHERE
           t1.sqld_id = t2.sqld_id
         AND
           t1.sqlu_id = ?
         AND
           t2.domain_id = ?
	   ORDER BY
		   t2.sqld_name ASC,
		   t1.sqlu_name ASC
SQL_QUERY;

    $rs = exec_query($sql, $query, array($db_user_id, $dmn_id));

    $db_user_name = $rs->fields['sqlu_name'];
    $db_user_pass = $rs->fields['sqlu_pass'];
    $db_name = $rs->fields['sqld_name'];
    $sql_user = &ADONewConnection('mysql');

    if (!@$sql_user->Connect($cfg['DB_HOST'], $db_user_name, $db_user_pass, $db_name)) {
        set_page_message(tr('Cannot connect as MySQL administrator!'));
        $tpl->assign('SQL_RESULT', '');
        return;
    }

    $query = $_POST['sql_query'];
    $query = stripslashes($query);
    $rs = $sql_user->Execute($query);

    if (!$rs) {
        $tpl->assign(array('QUERY_STATUS' => tr('SQL query has error'),
                'QUERY_RESULT' => $sql_user->ErrorMsg()));
    } else {
        write_log($_SESSION['user_logged'] . ": execute SQL query!");
        $tpl->assign(array('QUERY_STATUS' => tr('SQL query is ok'),
                'QUERY_RESULT' => sql_rs2html($rs)));
    }

    @$sql_user->Close();
}

function gen_page_post_data(&$tpl, &$sql, $db_user_id) {
    if (isset($_POST['uaction']) && $_POST['uaction'] === 'execute_query') {
        $tpl->assign(
					array(
						'USER_NAME' => clean_input($_POST['user_name']),
                		'SQL_QUERY' => stripslashes($_POST['sql_query'])
						)
					);
    } else {
        $query = <<<SQL_QUERY
            select
				sqlu_name as db_user_name
			from
				sql_user
			where
				sqlu_id = ?
SQL_QUERY;

        $rs = exec_query($sql, $query, array($db_user_id));
        $tpl->assign(
					array(
						'USER_NAME' => $rs->fields['db_user_name'],
                		'SQL_QUERY' => '',
                		'SQL_RESULT' => ''
						)
					);
    }

    $tpl->assign('ID', $db_user_id);
}

// common page data.

if (isset($_SESSION['sql_support']) && $_SESSION['sql_support'] == "no") {
    header("Location: index.php");
    die();
}

global $cfg;

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl->assign(
			array(
				'TR_CLIENT_SQL_EXECUTE_QUERY_PAGE_TITLE' => tr('ISPCP - Client/Execute SQL Query'),
        		'THEME_COLOR_PATH' => "../themes/$theme_color",
        		'THEME_CHARSET' => tr('encoding'),
        		'TID' => $_SESSION['layout_id'],
        		'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE'],
        		'ISP_LOGO' => get_logo($_SESSION['user_id'])
				)
			);

// dynamic page data.

check_usr_sql_perms($sql, $db_user_id);
gen_page_post_data($tpl, $sql, $db_user_id);
execute_sql_query($tpl, $sql, $_SESSION['user_id'], $db_user_id);

// static page messages.

gen_client_mainmenu($tpl, $cfg['CLIENT_TEMPLATE_PATH'] . '/main_menu_manage_sql.tpl');
gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'] . '/menu_manage_sql.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
			array(
				'TR_EXECUTE_SQL_QUERY' => tr('Execute SQL query'),
		        'TR_USER_NAME' => tr('User name'),
		        'TR_SQL_QUERY' => tr('SQL query'),
		        'TR_QUERY_STATUS' => tr('Query status'),
		        'TR_QUERY_RESULT' => tr('Query result'),
		        'TR_EXECUTE' => tr('Execute')
				)
			);

gen_page_message($tpl);
$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();

?>