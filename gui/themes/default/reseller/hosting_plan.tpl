
        <script type="text/javascript">
            /* <![CDATA[ */
            function action_delete(url, subject) {
                if (!confirm(sprintf("{TR_MESSAGE_DELETE}", subject)))
                    return false;
                location = url;
            }
            /* ]]> */
        </script>
            <!-- BDP: hp_table -->
            <table>
                <thead>
                    <tr>
                        <th>{TR_NOM}</th>
                        <th>{TR_PLAN_NAME}</th>
                        <th>{TR_PURCHASING}</th>
                        <th>{TR_ACTION}</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- BDP: hp_entry -->
                    <tr>
                        <td>{PLAN_NOM}</td>
                        <td>
                            <a href="../orderpanel/package_info.php?coid={CUSTOM_ORDERPANEL_ID}&amp;user_id={RESELLER_ID}&amp;id={HP_ID}" target="_blank" title="{PLAN_SHOW}">{PLAN_NAME}</a>
                        </td>
                        <td>{PURCHASING}</td>
                        <td>
                            <a href="hosting_plan_edit.php?hpid={HP_ID}" class="icon i_edit">{TR_EDIT}</a>
                            <!-- BDP: hp_delete -->
                            <a href="#" onclick="return action_delete('hosting_plan_delete.php?hpid={HP_ID}', '{PLAN_NAME2}')" class="icon i_delete">{PLAN_ACTION}</a>
                            <!-- EDP: hp_delete -->
                        </td>
                    </tr>
                    <!-- EDP: hp_entry -->
                </tbody>
            </table>
            <!-- EDP: hp_table -->
