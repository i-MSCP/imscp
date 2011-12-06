<!-- INCLUDE "../shared/layout/header.tpl" -->
        <script language="JavaScript" type="text/JavaScript">
            /*<![CDATA[*/
                $(document).ready(function(){
                    $('#dmn_help').iMSCPtooltips({msg:"{TR_DMN_HELP}"});

                    if($('#datepicker').val() == '') {
                        $('#datepicker').attr('disabled', 'disabled');
                        $('#never_expire').removeAttr('disabled');
                    }

                    $('#datepicker').datepicker();
                    $('#datepicker').change(function() {
                        if($(this).val() != '') {
                            $('#never_expire').attr('disabled', 'disabled')
                        } else {
                            $('#never_expire').removeAttr('disabled');
                        }
                    });

                    $('#never_expire').change(function() {
                        if($(this).is(':checked')) {
                            $('#datepicker').attr('disabled', 'disabled')
                        } else {
                            $('#datepicker').removeAttr('disabled');
                        }
                    });
                });
            /*]]>*/
        </script>
        <div class="header">
            {MAIN_MENU}
            <div class="logo">
                <img src="{ISP_LOGO}" alt="i-MSCP logo" />
            </div>
        </div>
        <div class="location">
            <div class="location-area">
                <h1 class="manage_users">{TR_MENU_MANAGE_USERS}</h1>
            </div>
            <ul class="location-menu">
                <!-- BDP: logged_from -->
                <li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
                <!-- EDP: logged_from -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="users.php">{TR_MENU_MANAGE_USERS}</a></li>
                <li><a href="user_add1.php">{TR_ADD_USER}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <h2 class="general"><span>{TR_ADD_USER}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <!-- BDP: add_form -->
            <form name="reseller_add_users_first_frm" method="post" action="user_add1.php">
                    <table>
						<tr>
							<th colspan="2">{TR_CORE_DATA}</th>
						</tr>
                        <tr>
                            <td style="width:300px;">
                            <label for="dmn_name" style="vertical-align: middle;">{TR_DOMAIN_NAME}</label>
                                <span style="vertical-align:middle" class="icon i_help" id="dmn_help">Help</span>
                            </td>
                            <td>
                                <input type="text" name="dmn_name" id="dmn_name" value="{DMN_NAME_VALUE}" />
                            </td>
                        </tr>
                        <!-- BDP: expire -->
                        <tr>
                            <td><label for="datepicker">{TR_DOMAIN_EXPIRE}</label></td>
                            <td>
                                <div>
                                    <input type="text" name="datepicker" id="datepicker" value="{DATEPICKER_VALUE}">
                                    <label for="never_expire">(MM/DD/YYYY) {TR_EXPIRE_CHECKBOX}</label>
                                    <input type="checkbox" name="never_expire" id="never_expire" value="0" checked />
                                </div>
                            </td>
                        </tr>
                        <!-- BDP: add_user -->
                        <tr>
                            <td>{TR_CHOOSE_HOSTING_PLAN}</td>
                            <td>
                                <select id="dmn_tpl" name="dmn_tpl">
                                    <!-- BDP: hp_entry -->
                                    <option value="{CHN}"{CH{CHN}}>{HP_NAME}</option>
                                    <!-- EDP: hp_entry -->
                                </select>
                            </td>
                        </tr>
                        <!-- BDP: personalize -->
                        <tr>
                            <td>{TR_PERSONALIZE_TEMPLATE}</td>
                            <td>
                                <input type="radio" id="chtpl_yes" name="chtpl" value="_yes_" {CHTPL1_VAL} /><label for="chtpl_yes">{TR_YES}</label>
                                <input type="radio" id="chtpl_no" name="chtpl" value="_no_" {CHTPL2_VAL} /><label for="chtpl_no">{TR_NO}</label>
                            </td>
                        </tr>
                        <!-- EDP: personalize -->
                        <!-- EDP: add_user -->
                    </table>
                <div class="buttons">
                    <input name="Submit" type="submit" class="button" value="{TR_NEXT_STEP}" />
                </div>
                <input type="hidden" name="uaction" value="user_add_nxt" />
            </form>
            <!-- EDP: add_form -->
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
