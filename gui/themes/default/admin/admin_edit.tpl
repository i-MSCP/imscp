<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_EDIT_USER_PAGE_TITLE}</title>
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
                <h1 class="manage_users">{TR_MENU_MANAGE_USERS}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="manage_users.php">{TR_MENU_MANAGE_USERS}</a></li>
                <li><a>{TR_EDIT_ADMIN}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">

            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <h2 class="user"><span>{TR_EDIT_ADMIN}</span></h2>
            <form name="admin_edit_user" method="post" action="admin_edit.php">
                <fieldset>
                    <legend>{TR_CORE_DATA}</legend>
                    <table>
                        <tr>
                            <td><label for="username">{TR_USERNAME}</label></td>
                            <td class="content"> {USERNAME}</td>
                        </tr>
                        <tr>
                            <td><label for="password">{TR_PASSWORD}</label></td>
                            <td>
                                <input type="password" name="pass" id="pass" value="{VAL_PASSWORD}"/>
                                <input name="genpass" type="submit" class="button" value="{TR_PASSWORD_GENERATE}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="pass_rep">{TR_PASSWORD_REPEAT}</label></td>
                            <td><input type="password" name="pass" id="pass_rep" value="{VAL_PASSWORD}"/></td>
                        </tr>
                        <tr>
                            <td><label for="email">{TR_EMAIL}</label></td>
                            <td><input type="text" name="email" id="email" value="{EMAIL}"/></td>
                        </tr>
                    </table>
                </fieldset>

                <fieldset>
                    <legend>{TR_ADDITIONAL_DATA}</legend>
                    <table>
                        <tr>
                            <td><label for="first_name">{TR_FIRST_NAME}</label></td>
                            <td><input type="text" name="fname" id="fname" value="{FIRST_NAME}"/></td>
                        </tr>
                        <tr>
                            <td><label for="last_name">{TR_LAST_NAME}</label></td>
                            <td><input type="text" name="lname" id="lname" value="{LAST_NAME}"/></td>
                        </tr>
                        <tr>
                            <td><label for="gender">{TR_GENDER}</label></td>
                            <td><select id="gender" name="gender">
                                    <option value="M" {VL_MALE}>{TR_MALE}</option>
                                    <option value="F" {VL_FEMALE}>{TR_FEMALE}</option>
                                    <option value="U" {VL_UNKNOWN}>{TR_UNKNOWN}</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><label for="company">{TR_COMPANY}</label></td>
                            <td><input type="text" name="firm" id="firm" value="{FIRM}"/></td>
                        </tr>
                        <tr>
                            <td><label for="zip_postal_code">{TR_ZIP_POSTAL_CODE}</label></td>
                            <td><input type="text" name="zip" id="zip" value="{ZIP}"/></td>
                        </tr>
                        <tr>
                            <td><label for="city">{TR_CITY}</label></td>
                            <td><input type="text" name="city" id="city" value="{CITY}"/></td>
                        </tr>
                        <tr>
                            <td><label for="state">{TR_STATE_PROVINCE}</label></td>
                            <td><input type="text" name="state" id="state" value="{STATE_PROVINCE}"/></td>
                        </tr>
                        <tr>
                            <td><label for="country">{TR_COUNTRY}</label></td>
                            <td><input type="text" name="country" id="country" value="{COUNTRY}"/></td>
                        </tr>
                        <tr>
                            <td><label for="street_1">{TR_STREET_1}</label></td>
                            <td><input type="text" name="street1" id="street1" value="{STREET_1}"/></td>
                        </tr>

                        <tr>
                            <td><label for="street_2">{TR_STREET_2}</label></td>
                            <td><input type="text" name="street2" id="street2" value="{STREET_2}"/></td>
                        </tr>
                        <tr>
                            <td><label for="phone">{TR_PHONE}</label></td>
                            <td><input type="text" name="phone" id="phone" value="{PHONE}"/></td>
                        </tr>
                        <tr>
                            <td>{TR_FAX}</td>
                            <td><input type="text" name="fax" value="{FAX}" /></td>
                        </tr>
                    </table>
                </fieldset>

                <div class="buttons">
                    <input name="Submit" type="submit" class="button" value="{TR_UPDATE}" />
                    <input id="send_data" type="checkbox" name="send_data" checked="checked" />
                    <label for="send_data">{TR_SEND_DATA}</label>

                    <input type="hidden" name="uaction" value="edit_user" />
                    <input type="hidden" name="edit_id" value="{EDIT_ID}" />
                    <input type="hidden" name="edit_username" value="{USERNAME}" />
                </div>

            </form>
        </div>
        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>
