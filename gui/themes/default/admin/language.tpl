<!-- INCLUDE "../shared/layout/header.tpl" -->
        <div class="header">
            {MAIN_MENU}

            <div class="logo">
                <img src="{ISP_LOGO}" alt="i-MSCP logo" />
            </div>
        </div>
        <div class="location">
            <div class="location-area">
                <h1 class="general">{TR_MENU_GENERAL_INFORMATION}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="index.php">{TR_MENU_GENERAL_INFORMATION}</a></li>
                <li><a href="#" onclick="return false;">{TR_LANGUAGE}</a></li>
            </ul>
        </div>
        <div class="left_menu">
            {MENU}
        </div>
        <div class="body">
            <h2 class="multilanguage"><span>{TR_LANGUAGE}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <form name="adminChangeLanguage" method="post" action="language.php">
                <table>
					<tr>
						<th colspan="2">{TR_LANGUAGE}</th>
					</tr>
                    <tr>
                        <td style="width:300px;">
                            <label for="def_language">{TR_CHOOSE_LANGUAGE}</label>
                        </td>
                        <td>
                            <select name="def_language" id="def_language">
                                <!-- BDP: def_language -->
                                <option value="{LANG_VALUE}" {LANG_SELECTED}>{LANG_NAME}</option>
                                <!-- EDP: def_language -->
                            </select>
                        </td>
                    </tr>
                </table>
                <div class="buttons">
                    <input name="submit" type="submit" class="button" value="{TR_UPDATE}" />
                </div>
            </form>
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
