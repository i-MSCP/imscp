<?php

function gen_domain_details($tpl, $sql, $domain_id)
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
        $alias_rs = exec_query($sql, $alias_query, $domain_id);

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
