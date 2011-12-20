
        <script type="text/javascript">
            /* <![CDATA[ */
            function delete_order(url, subject) {
                if (!confirm(sprintf("{TR_MESSAGE_DELETE_ACCOUNT}", subject))) {
                    return false;
                }
                location = url;
            }
            /* ]]> */
        </script>
            <!-- BDP: props_list -->
            <!-- BDP: orders_table -->
            <table>
                <thead>
                    <tr>
                        <th>{TR_ID}</th>
                        <th>{TR_DOMAIN}</th>
                        <th>{TR_HP}</th>
                        <th>{TR_USER}</th>
                        <th>{TR_STATUS}</th>
                        <th>{TR_ACTION}</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- BDP: order -->
                    <tr>
                        <td>{ID}</td>
                        <td>{DOMAIN}</td>
                        <td>{HP}</td>
                        <td>{USER}v</td>
                        <td>{STATUS}</td>
                        <td>
                            <a href="{LINK}" class="icon i_add_user">{TR_ADD}</a>
                            <a href="#" onclick="delete_order('orders_delete.php?order_id={ID}', '{DOMAIN}')" class="icon i_delete">{TR_DELETE}</a>
                        </td>
                    </tr>
                    <!-- EDP: order -->
                </tbody>
            </table>
            <!-- EDP: orders_table -->
            <div class="paginator">
                <!-- BDP: scroll_next_gray -->
                <a class="icon i_next_gray" href="#">&nbsp;</a>
                <!-- EDP: scroll_next_gray -->

                <!-- BDP: scroll_next -->
                <a class="icon i_next" href="orders.php?psi={NEXT_PSI}" title="{TR_NEXT}">{TR_NEXT}</a>
                <!-- EDP: scroll_next -->

                <!-- BDP: scroll_prev -->
                <a class="icon i_prev" href="orders.php?psi={PREV_PSI}" title="{TR_PREVIOUS}">{TR_PREVIOUS}</a>
                <!-- EDP: scroll_prev -->

                <!-- BDP: scroll_prev_gray -->
                <a class="icon i_prev_gray" href="#">&nbsp;</a>
                <!-- EDP: scroll_prev_gray -->
            </div>
            <!-- EDP: props_list -->
