<?xml ve<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_MANAGE_EMAIL_SETUP_PAGE_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/ispcp.js"></script>
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
                <h1 class="general">{TR_MENU_SETTINGS}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="ip_manage.php">{TR_EMAIL_SETUP}</a></li>
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
            <h2 class="support"><span>{TR_EMAIL_SETUP}</span></h2>
            <fieldset>
                <legend>{TR_MESSAGE_TEMPLATE_INFO}</legend>
            </fieldset>
            <form name="admin_email_setup" method="post" action="settings_welcome_mail.php">
                <table>                    
                    <tr>                        
                        <td width="200"><b>{TR_USER_LOGIN_NAME}</b></td>
                        <td>{USERNAME}</td>
                    </tr>
                    <tr>                        
                        <td><b>{TR_USER_PASSWORD}</b></td>
                        <td>{PASSWORD}</td>
                    </tr>
                    <tr>                        
                        <td><b>{TR_USER_REAL_NAME}</b></td>
                        <td>{NAME}</td>
                    </tr>
                    <tr>                        
                        <td><b>{TR_USERTYPE}</b></td>
                        <td>{USERTYPE}</td>
                    </tr>
                    <tr>                        
                        <td><b>{TR_BASE_SERVER_VHOST}</b></td>
                        <td>{BASE_SERVER_VHOST}</td>
                    </tr>
                    <tr>                        
                        <td><b>{TR_BASE_SERVER_VHOST_PREFIX}</b></td>
                        <td>{BASE_SERVER_VHOST_PREFIX}</td>
                    </tr>
                </table>
                <br />
                <fieldset>
                    <legend>{TR_MESSAGE_TEMPLATE}</legend>
                </fieldset>
                <table>                    
                    <tr>
                        <td width="25"&nbsp;</td>
                        <td width="25"><label for="auto_subject"><b>{TR_SUBJECT}</b></label></td>
                        <td><input type="text" name="auto_subject" id="auto_subject" value="{SUBJECT_VALUE}" style="width:80%" class="textinput"/>
                        </td>                        
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td><label for="auto_message"<b>{TR_MESSAGE}</b></label></td>
                        <td><textarea name="auto_message" id="auto_message" style="width:80%" class="textinput2" cols="80" rows="30">{MESSAGE_VALUE}</textarea>
                        </td>
                    </tr>
                    <tr>                        
                        <td><b>{TR_SENDER_EMAIL}</b></td>
                        <td class="content">{SENDER_EMAIL_VALUE}</td>
                    </tr>
                    <tr>                        
                        <td><b>{TR_SENDER_NAME}</b></td>
                        <td>{SENDER_NAME_VALUE}</td>
                    </tr>
                </table>
                <br />
                <td><input name="Submit" type="submit" class="button" value="{TR_APPLY_CHANGES}" /></td>
                <input type="hidden" name="uaction" value="email_setup" />
            </form>
        </div>
        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>