<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_CIRCULAR_PAGE_TITLE}</title>
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
                <li><a href="circular.php">{TR_CIRCULAR}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <h2 class="email"><span>{TR_CIRCULAR}</span></h2>
            <form name="admin_email_setup" method="post" action="circular.php">
                <fieldset>
                    <legend>{TR_CORE_DATA}</legend>

                    <table>
                        <tr>
                            <td><label for="rcpt_to">{TR_SEND_TO}</label></td>
                            <td><select id="rcpt_to" name="rcpt_to">
                                    <option value="usrs">{TR_ALL_USERS}</option>
                                    <option value="rsls">{TR_ALL_RESELLERS}</option>
                                    <option value="usrs_rslrs">{TR_ALL_USERS_AND_RESELLERS}</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><label for="msg_subject">{TR_MESSAGE_SUBJECT}</label></td>
                            <td><input type="text" name="msg_subject" id="msg_subject" value="{MESSAGE_SUBJECT}"/></td>
                        </tr>
                        <tr>
                            <td><label for="msg_text">{TR_MESSAGE_TEXT}</label></td>
                            <td><textarea name="msg_text" cols="80" rows="20">{MESSAGE_TEXT}</textarea></td>
                        </tr>
                    </table>
                </fieldset>

                <fieldset>
                    <legend>{TR_ADDITIONAL_DATA}</legend>
                
                    <table>
                        <tr>
                            <td><label for="sender_email">{TR_SENDER_EMAIL}</label></td>
                            <td><input type="text" name="sender_email" id="sender_email" value="{SENDER_EMAIL}"/></td>
                        </tr>
                        <tr>
                            <td><label for="sender_name">{TR_SENDER_NAME}</label></td>
                            <td><input type="text" name="sender_name" id="sender_name" value="{SENDER_NAME}"/></td>
                        </tr>
                    </table>
                </fieldset>

                <div class="buttons">
                    <input name="Submit" type="submit" class="button" value="{TR_SEND_MESSAGE}" />
                    <input type="hidden" name="uaction" value="send_circular" />
                </div>
            </form>
        </div>
        
        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>
