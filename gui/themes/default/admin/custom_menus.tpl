<?xml ve<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_CUSTOM_MENUS_PAGE_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/ispcp.js"></script>
        <!--[if IE 6]>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <![endif]-->
        <script type="text/javascript">
            /* <![CDATA[ */
            function action_delete(url, subject) {
                return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
            }
            /* ]]> */
        </script>
        <style type="text/css">
            <!--
            .style1 {font-weight: bold}
            -->
        </style>
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
                <h1 class="general">{TR_MENU_QUESTIONS_AND_COMMENTS}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="custom_menus.php">{TR_TITLE_CUSTOM_MENUS}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>


        <div class="body">
            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <!-- BDP: tickets_list -->
            <h2 class="support"><span>{TR_TITLE_CUSTOM_MENUS}</span></h2>
            <table>
                <tr>
                    <td width="25">&nbsp;</td>
                    <td><b>{TR_MENU_NAME}</b></td>
                    <td align="center"><b>{TR_LEVEL}</b></td>
                    <td colspan="2" align="center"><b>{TR_ACTON}</b></td>
                </tr>
                <!-- BDP: button_list -->
                <tr>
                    <td>&nbsp;</td>
                    <td>{CONTENT}"><a href="{LINK}" class="link" target="_blank"><strong>{MENU_NAME}</strong></a><br />
                        {LINK}</td>
                    <td align="center">{LEVEL}</td>
                    <td width="100" class="{CONTENT}" align="center"><img src="{THEME_COLOR_PATH}/images/icons/edit.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /> <a href="custom_menus.php?edit_id={BUTONN_ID}" class="link">{TR_EDIT}</a></td>
                    <td width="100" class="{CONTENT}" align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /> <a href="custom_menus.php?delete_id={BUTONN_ID}" onclick="return action_delete('custom_menus.php?delete_id={BUTONN_ID}', '{MENU_NAME2}')" class="link">{TR_DELETE}</a></td>
                </tr>
                <!-- EDP: button_list -->
            </table>
            <br />
            <form name="add_new_button_frm" method="post" action="custom_menus.php">
                <!-- BDP: add_button -->
                <fieldset>
                    <legend>{TR_ADD_NEW_BUTTON}</legend>
                </fieldset>                
                <table>
                    <tr>                        
                        <td><label for="bname">{TR_BUTTON_NAME}</label></td>
                        <td><input type="text" name="bname" id="bname" /></td>
                    </tr>
                    <tr>                        
                        <td><label for="blink">{TR_BUTTON_LINK}</label></td>
                        <td><input type="text" name="blink" id="blink" /></td>
                    </tr>
                    <tr>                        
                        <td><label for="btarget">{TR_BUTTON_TARGET}</label></td>
                        <td><input type="text" name="btarget" id="btarget" /></td>
                    </tr>
                    <tr>                       
                        <td width="200"><label for="bview">{TR_VIEW_FROM}</label></td>
                        <td><select name="bview" id="bview">
                                <option value="admin">{ADMIN}</option>
                                <option value="reseller">{RESELLER}</option>
                                <option value="user">{USER}</option>
                                <option value="all">{RESSELER_AND_USER}</option>
                            </select></td>
                    </tr>
                    <tr>                        
                        <td><input name="Button" type="button" class="button" value="  {TR_SAVE}  " onclick="return sbmt(document.forms[0],'new_button');" /></td>
                    </tr>
                </table>
                <!-- EDP: add_button -->
                <!-- BDP: edit_button -->                
                <fieldset>
                    <legend>{TR_EDIT_BUTTON}</legend>
                </fieldset>                
                <table>
                    <tr>
                        <td><label for="bname">{TR_BUTTON_NAME}</label></td>
                        <td><input type="text" name="bname" id="bname" value="{BUTON_NAME}" /></td>
                    </tr>
                    <tr>
                        <td><label for="blink">{TR_BUTTON_LINK}</label></td>
                        <td><input type="text" name="blink" id="blink" value="{BUTON_LINK}" /></td>
                    </tr>
                    <tr>
                        <td><label for="btarget">{TR_BUTTON_TARGET}</label></td>
                        <td><input type="text" name="btarget" id="btarget" value="{BUTON_TARGET}" /></td>
                    </tr>
                    <tr>
                        <td width="200"><label for="bview">{TR_VIEW_FROM}</label></td>
                        <td><select name="bview" id="bview">
                                <option value="admin">{ADMIN}</option>
                                <option value="reseller">{RESELLER}</option>
                                <option value="user">{USER}</option>
                                <option value="all">{RESSELER_AND_USER}</option>
                            </select></td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td colspan="2"><input name="Button" type="button" class="button" value="  {TR_SAVE}  " onclick="return sbmt(document.forms[0],'edit_button');" /></td>
                    </tr>
                </table>
                <input type="hidden" name="eid" value="{EID}" />
                <!-- EDP: edit_button -->
                <input type="hidden" name="uaction" value="" />
            </form>
        </div>
        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>