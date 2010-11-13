<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_SETTINGS_PAGE_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
        <!--[if IE 6]>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <![endif]-->
    </head>

    <body>
        <div class="header">
            {MAIN_MENU}

            <div class="logo">
                <img src="{THEME_COLOR_PATH}/images/ispcp_logo.png" alt="IspCP logo" />
                <img src="{THEME_COLOR_PATH}/images/ispcp_webhosting.png" alt="IspCP omega" />
            </div>
        </div>

        <div class="location">
            <div class="location-area icons-left">
                <h1 class="manage_users">{TR_MENU_SETTINGS}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="settings.php">{TR_SETTINGS}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <h2 class="general"><span>{TR_SETTINGS}</span></h2>

            <form action="settings.php" method="post" name="frmsettings" id="frmsettings">
                <table>
                    <fieldset>
                        <legend>{TR_CHECK_FOR_UPDATES}</legend>
                    </fieldset>
                    <tr>
                        <td width="200"><label for="checkforupdate">{TR_CHECK_FOR_UPDATES}</label></td>
                        <td><select id="checkforupdate" name="checkforupdate">
                                <option value="0" {CHECK_FOR_UPDATES_SELECTED_OFF}>{TR_DISABLED}</option>
                                <option value="1" {CHECK_FOR_UPDATES_SELECTED_ON}>{TR_ENABLED}</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <table>    
                    <br />
                    <fieldset>
                        <legend>{TR_LOSTPASSWORD}</legend>
                    </fieldset>
                    <tr>
                        <td width="200"><label for="lostpassword">{TR_LOSTPASSWORD}</label></td>
                        <td><select id="lostpassword" name="lostpassword">
                                <option value="0" {LOSTPASSWORD_SELECTED_OFF}>{TR_DISABLED}</option>
                                <option value="1" {LOSTPASSWORD_SELECTED_ON}>{TR_ENABLED}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="lostpassword_timeout">{TR_LOSTPASSWORD_TIMEOUT}</label></td>
                        <td><input type="text" name="lostpassword_timeout" id="lostpassword_timeout" value="{LOSTPASSWORD_TIMEOUT_VALUE}"/></td>

                    </tr>
                </table>
                <table>
                    <br/>
                    <fieldset>
                        <legend>{TR_PASSWORD_SETTINGS}</legend>
                    </fieldset>
                    <tr>
                        <td width="200"><label for="passwd_strong">{TR_PASSWD_STRONG}</label></td>
                        <td><select id="passwd_strong" name="passwd_strong">
                                <option value="0" {PASSWD_STRONG_OFF}>{TR_DISABLED}</option>
                                <option value="1" {PASSWD_STRONG_ON}>{TR_ENABLED}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="passwd_chars">{TR_PASSWD_CHARS}</label></td>
                        <td><input type="text" name="passwd_chars" id="passwd_chars" value="{PASSWD_CHARS}" maxlength="2" /></td>
                    </tr>
                </table>                
                <table>
                    <br />
                    <fieldset>
                        <legend>{TR_BRUTEFORCE}</legend>
                    </fieldset>
                    <tr>
                        <td width="200"><label for="bruteforce">{TR_BRUTEFORCE}</label></td>
                        <td><select id="bruteforce" name="bruteforce">
                                <option value="0" {BRUTEFORCE_SELECTED_OFF}>{TR_DISABLED}</option>
                                <option value="1" {BRUTEFORCE_SELECTED_ON}>{TR_ENABLED}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="200"><label for="bruteforce_between">{TR_BRUTEFORCE_BETWEEN}</label></td>
                        <td><select id="bruteforce_between" name="bruteforce_between">
                                <option value="0" {BRUTEFORCE_BETWEEN_SELECTED_OFF}>{TR_DISABLED}</option>
                                <option value="1" {BRUTEFORCE_BETWEEN_SELECTED_ON}>{TR_ENABLED}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="bruteforce_max_login">{TR_BRUTEFORCE_MAX_LOGIN}</label></td>
                        <td><input type="text" name="bruteforce_max_login" id="bruteforce_max_login" value="{BRUTEFORCE_MAX_LOGIN_VALUE}" maxlength="3" /></td>
                    </tr>
                    <tr>
                        <td><label for="bruteforce_block_time">{TR_BRUTEFORCE_BLOCK_TIME}</label></td>
                        <td><input name="bruteforce_block_time" type="text" id="bruteforce_block_time" value="{BRUTEFORCE_BLOCK_TIME_VALUE}" maxlength="3" /></td>
                    </tr>
                    <tr>
                        <td><label for="bruteforce_between_time">{TR_BRUTEFORCE_BETWEEN_TIME}</label></td>
                        <td><input name="bruteforce_between_time" type="text" id="bruteforce_between_time" value="{BRUTEFORCE_BETWEEN_TIME_VALUE}" maxlength="3" /></td>
                    </tr>
                    <tr>
                        <td><label for="bruteforce_max_capcha">{TR_BRUTEFORCE_MAX_CAPTCHA}</label></td>
                        <td><input name="bruteforce_max_capcha" type="text" id="bruteforce_max_capcha" value="{BRUTEFORCE_MAX_CAPTCHA}" maxlength="3" /></td>
                    </tr>
                </table>
                <table>
                    <br />
                    <fieldset>
                        <legend>{TR_MAIL_SETTINGS}</legend>
                    </fieldset>
                    <tr>
                        <td width="200"><label for="create_default_email_addresses">{TR_CREATE_DEFAULT_EMAIL_ADDRESSES}</label></td>
                        <td><select id="create_default_email_addresses" name="create_default_email_addresses">
                                <option value="0" {CREATE_DEFAULT_EMAIL_ADDRESSES_OFF}>{TR_DISABLED}</option>
                                <option value="1" {CREATE_DEFAULT_EMAIL_ADDRESSES_ON}>{TR_ENABLED}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="200"><label for="count_default_email_addresses">{TR_COUNT_DEFAULT_EMAIL_ADDRESSES}</label></td>
                        <td><select id="count_default_email_addresses" name="count_default_email_addresses">
                                <option value="0" {COUNT_DEFAULT_EMAIL_ADDRESSES_OFF}>{TR_DISABLED}</option>
                                <option value="1" {COUNT_DEFAULT_EMAIL_ADDRESSES_ON}>{TR_ENABLED}</option>
                            </select></td>
                    </tr>
                    <tr>
                        <td width="200"><label for="hard_mail_suspension">{TR_HARD_MAIL_SUSPENSION}</label></td>
                        <td><select id="hard_mail_suspension" name="hard_mail_suspension">
                                <option value="0" {HARD_MAIL_SUSPENSION_OFF}>{TR_DISABLED}</option>
                                <option value="1" {HARD_MAIL_SUSPENSION_ON}>{TR_ENABLED}</option>
                            </select></td>
                    </tr>
                </table>
                <table>
                    <br />
                    <fieldset>
                        <legend>{TR_OTHER_SETTINGS}</legend>
                    </fieldset>
                    <tr>
                        <td width="200"><label for="def_language">{TR_USER_INITIAL_LANG}</label></td>
                        <td><select name="def_language" id="def_language">
                                <!-- BDP: def_language -->
                                <option value="{LANG_VALUE}" {LANG_SELECTED}>{LANG_NAME}</option>
                                <!-- EDP: def_language -->
                            </select></td>
                    </tr>
                    <tr>
                        <td width="200"><label for="support_system">{TR_SUPPORT_SYSTEM}</label></td>
                        <td><select name="support_system" id="support_system">
                                <option value="0" {SUPPORT_SYSTEM_SELECTED_OFF}>{TR_DISABLED}</option>
                                <option value="1" {SUPPORT_SYSTEM_SELECTED_ON}>{TR_ENABLED}</option>
                            </select></td>
                    </tr>
                    <tr>
                        <td width="200"><label for="show_serverload">{TR_SHOW_SERVERLOAD}</label></td>
                        <td><select name="show_serverload" id="show_serverload">
                                <option value="0" {SHOW_SERVERLOAD_SELECTED_OFF}>{TR_DISABLED}</option>
                                <option value="1" {SHOW_SERVERLOAD_SELECTED_ON}>{TR_ENABLED}</option>
                            </select></td>
                    </tr>
                    <tr>
                        <td width="200"><label for="hosting_plan_level">{TR_HOSTING_PLANS_LEVEL}</label></td>
                        <td class="content"><select name="hosting_plan_level" id="hosting_plan_level">
                                <option value="admin" {HOSTING_PLANS_LEVEL_ADMIN}>{TR_ADMIN}</option>
                                <option value="reseller" {HOSTING_PLANS_LEVEL_RESELLER}>{TR_RESELLER}</option>
                            </select></td>
                    </tr>
                    <tr>
                        <td width="200"><label for="domain_rows_per_page">{TR_DOMAIN_ROWS_PER_PAGE}</label></td>
                        <td><input name="domain_rows_per_page" type="text" id="domain_rows_per_page" value="{DOMAIN_ROWS_PER_PAGE}" maxlength="3" /></td>
                    </tr>
                    <tr>
                        <td width="200"><label for="log_level">{TR_LOG_LEVEL}</label></td>
                        <td><select name="log_level" id="log_level">
                                <option value="E_USER_OFF" {LOG_LEVEL_SELECTED_OFF}>{TR_E_USER_OFF}</option>
                                <option value="E_USER_ERROR" {LOG_LEVEL_SELECTED_ERROR}>{TR_E_USER_ERROR}</option>
                                <option value="E_USER_WARNING" {LOG_LEVEL_SELECTED_WARNING}>{TR_E_USER_WARNING}</option>
                                <option value="E_USER_NOTICE" {LOG_LEVEL_SELECTED_NOTICE}>{TR_E_USER_NOTICE}</option>
                            </select></td>
                    </tr>
                    <tr>
                        <td width="200"><label for="prevent_external_login_admin">{TR_PREVENT_EXTERNAL_LOGIN_ADMIN}</label></td>
                        <td><select name="prevent_external_login_admin" id="prevent_external_login_admin">
                                <option value="0" {PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_OFF}>{TR_DISABLED}</option>
                                <option value="1" {PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_ON}>{TR_ENABLED}</option>
                            </select></td>
                    </tr>
                    <tr>
                        <td width="200"><label for="prevent_external_login_reseller">{TR_PREVENT_EXTERNAL_LOGIN_RESELLER}</label></td>
                        <td><select name="prevent_external_login_reseller" id="prevent_external_login_reseller">
                                <option value="0" {PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_OFF}>{TR_DISABLED}</option>
                                <option value="1" {PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_ON}>{TR_ENABLED}</option>
                            </select></td>
                    </tr>
                    <tr>
                        <td width="200"><label for="prevent_external_login_client">{TR_PREVENT_EXTERNAL_LOGIN_CLIENT}</label></td>
                        <td><select name="prevent_external_login_client" id="prevent_external_login_client">
                                <option value="0" {PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_OFF}>{TR_DISABLED}</option>
                                <option value="1" {PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_ON}>{TR_ENABLED}</option>
                            </select></td>
                    </tr>
                    <tr>
                        <td width="200"><label for="coid">{TR_CUSTOM_ORDERPANEL_ID}</label></td>
                        <td><input type="text" name="coid" id="coid" value="{CUSTOM_ORDERPANEL_ID}" /></td>
                    </tr>
                </table>
                <div class="buttons">
                    <input name="Submit" type="submit" class="button" value="{TR_APPLY_CHANGES}" />
                    <input type="hidden" name="uaction" value="apply" />
                </div>
            </form>
        </div>
        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>