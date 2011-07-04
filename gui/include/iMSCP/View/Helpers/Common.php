<?php

function gen_domain_details($tpl, $domain_id)
{
    $tpl->assign('USER_DETAILS', '');

    if (isset($_SESSION['details']) && $_SESSION['details'] == 'hide') {
        $tpl->assign(
            array(
                 'TR_VIEW_DETAILS' => tr('view aliases'),
                 'SHOW_DETAILS' => "show",
            )
        );

        return;
    } else if (isset($_SESSION['details']) && $_SESSION['details'] === "show") {
        $tpl->assign(
            array(
                 'TR_VIEW_DETAILS' => tr('hide aliases'),
                 'SHOW_DETAILS' => "hide",
            )
        );

        $alias_query = "
			SELECT
				`alias_id`, `alias_name`
			FROM
				`domain_aliasses`
			WHERE
				`domain_id` = ?
			ORDER BY
				`alias_id` DESC
		";
        $alias_rs = exec_query($alias_query, $domain_id);

        if ($alias_rs->recordCount() == 0) {
            $tpl->assign('USER_DETAILS', '');
        } else {
            while (!$alias_rs->EOF) {
                $alias_name = $alias_rs->fields['alias_name'];

                $tpl->assign('ALIAS_DOMAIN', tohtml(decode_idna($alias_name)));
                $tpl->parse('USER_DETAILS', '.user_details');

                $alias_rs->moveNext();
            }
        }
    } else {
        $tpl->assign(
            array(
                 'TR_VIEW_DETAILS' => tr('view aliases'),
                 'SHOW_DETAILS' => "show",
            )
        );

        return;
    }
}

/**
 * Helper function to generate logged from template part.
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function gen_logged_from($tpl)
{
    if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id'])) {
        $tpl->assign(array(
                          'YOU_ARE_LOGGED_AS' => tr(
                              '%1$s you are now logged as %2$s',
                              $_SESSION['logged_from'],
                              decode_idna($_SESSION['user_logged'])
                          ),
                          'TR_GO_BACK' => tr('Go back')));

        $tpl->parse('LOGGED_FROM', '.logged_from');
    } else {
        $tpl->assign('LOGGED_FROM', '');
    }
}

/**
 * Helper function to generates a html list of available languages.
 *
 * This method generate a HTML list of available languages. The language used by the
 * user is pre-selected. If no language is found, a specific message is shown.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  $user_def_language
 * @return void
 */
function gen_def_language($tpl, $user_def_language)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$htmlSelected = $cfg->HTML_SELECTED;
	$availableLanguages = i18n_getAvailableLanguages();

	if (!empty($availableLanguages)) {
		foreach ($availableLanguages as $language) {
			$tpl->assign(array(
							  'LANG_VALUE' => $language['locale'],
							  'LANG_SELECTED' => ($language['locale'] == $user_def_language)
								  ? $htmlSelected : '',
							  'LANG_NAME' => tohtml($language['language'])));

			$tpl->parse('DEF_LANGUAGE', '.def_language');
		}
	} else {
		$tpl->assign('LANGUAGES_AVAILABLE', '');
		set_page_message(tr('No language found.'), 'warning');
	}
}

/**
 * Helper function to generate HTML list of months and years
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @param  $user_month
 * @param  $user_year
 * @return void
 */
function gen_select_lists($tpl, $user_month, $user_year)
{
    global $crnt_month, $crnt_year;

     /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    if (!$user_month == '' || !$user_year == '') {
        $crnt_month = $user_month;
        $crnt_year = $user_year;
    } else {
        $crnt_month = date('m');
        $crnt_year = date('Y');
    }

    for ($i = 1; $i <= 12; $i++) {
        $selected = ($i == $crnt_month) ? $cfg->HTML_SELECTED : '';
        $tpl->assign(array('OPTION_SELECTED' => $selected, 'MONTH_VALUE' => $i));
        $tpl->parse('MONTH_LIST', '.month_list');
    }

    for ($i = $crnt_year - 1; $i <= $crnt_year + 1; $i++) {
        $selected = ($i == $crnt_year) ? $cfg->HTML_SELECTED : '';
        $tpl->assign(array('OPTION_SELECTED' => $selected, 'YEAR_VALUE' => $i));
        $tpl->parse('YEAR_LIST', '.year_list');
    }
}

/**
 * Must be documented
 *
 * @param iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @param $user_id
 * @param bool $encode
 * @return void
 */
function gen_purchase_haf($tpl, $user_id, $encode = false)
{
     /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $query = "SELECT `header`, `footer` FROM `orders_settings` WHERE `user_id` = ?;";

    if (isset($_SESSION['user_theme'])) {
        $theme = $_SESSION['user_theme'];
    } else {
        $theme = $cfg->USER_INITIAL_THEME;
    }

    $rs = exec_query($query, $user_id);

    if ($rs->recordCount() == 0) {
        $title = tr("i-MSCP - Order Panel");

        $header = <<<RIC
<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>{$title}</title>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<link href="../themes/{$theme}/css/imscp_orderpanel.css" rel="stylesheet" type="text/css" />
	</head>
	<body>
		<div align="center">
			<table width="100%" style="height:95%">
				<tr>
					<td align="center">
RIC;

        $footer = <<<RIC
					</td>
				</tr>
			</table>
		</div>
	</body>
</html>
RIC;
    } else {
        $header = $rs->fields['header'];
        $footer = $rs->fields['footer'];
        $header = str_replace('\\', '', $header);
        $footer = str_replace('\\', '', $footer);
    }

    if ($encode) {
        $header = htmlentities($header, ENT_COMPAT, 'UTF-8');
        $footer = htmlentities($footer, ENT_COMPAT, 'UTF-8');
    }

    $tpl->assign(array(
                      'PURCHASE_HEADER' => $header,
                      'PURCHASE_FOOTER' => $footer));
}
