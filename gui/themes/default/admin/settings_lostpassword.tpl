<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_LOSTPW_EMAL_SETUP}</title>
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
                <h1 class="manage_users">{TR_LOSTPW_EMAIL}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">

            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->
            <h2 class="general"><span>{TR_LOSTPW_EMAIL}</span></h2>
            <form action="settings_lostpassword.php" method="post" name="frmlostpassword" id="frmlostpassword">
                <fieldset>
                    <legend>{TR_MESSAGE_TEMPLATE_INFO}</legend>
                </fieldset>
                <table>                 
                    <tr>                        
                        <td colspan="2"><b>{TR_ACTIVATION_EMAIL}</b></td>
                        <td><b>{TR_PASSWORD_EMAIL}</b></td>
                    </tr>
                    <tr>
                        <td><b>{TR_USER_LOGIN_NAME}</b></td>
                        <td>{USERNAME}</td>
                        <td><b>{TR_USER_LOGIN_NAME}</b></td>
                        <td>{USERNAME}</td>
                    </tr>
                    <tr>                        
                        <td><b>{TR_LOSTPW_LINK}</b></td>
                        <td>{LINK}</td>
                        <td><b>{TR_USER_PASSWORD}</b></td>
                        <td>{PASSWORD}</td>
                    </tr>
                    <tr>                        
                        <td><b>{TR_USER_REAL_NAME}</b></td>
                        <td>{NAME}</td>
                        <td><b>{TR_USER_REAL_NAME}</b></td>
                        <td>{NAME}</td>
                    </tr>
                    <tr>                       
                        <td><b>{TR_BASE_SERVER_VHOST}</b></td>
                        <td>{BASE_SERVER_VHOST}</td>
                        <td><b>{TR_BASE_SERVER_VHOST}</b></td>
                        <td>{BASE_SERVER_VHOST}</td>
                    </tr>
                    <tr>                        
                        <td><b>{TR_BASE_SERVER_VHOST_PREFIX}</b></td>
                        <td>{BASE_SERVER_VHOST_PREFIX}</td>
                        <td><b>{TR_BASE_SERVER_VHOST_PREFIX}</b></td>
                        <td>{BASE_SERVER_VHOST_PREFIX}</td>
                    </tr>
                </table>
                <br />
                <fieldset>
                    <legend>{TR_MESSAGE_TEMPLATE}</legend>
                </fieldset>
                <table>                                       
                    <td><b>{TR_SUBJECT}</b></td>
                    <td class="content" width="35%"><input name="subject1" type="text" class="textinput" id="subject1" style="width:90%" value="{SUBJECT_VALUE1}" /></td>
                    <td class="content" width="35%"><input type="text" name="subject2" value="{SUBJECT_VALUE2}" style="width:90%" class="textinput" /></td>
                    </tr>
                    <tr>                        
                        <td><b>{TR_MESSAGE}</b></td>
                        <td class="content" width="35%"><textarea name="message1" cols="80" rows="20" class="textinput2" id="message1" style="width:90%">{MESSAGE_VALUE1}</textarea></td>
                        <td class="content" width="35%"><textarea name="message2" cols="80" rows="20" class="textinput2" id="message2" style="width:90%">{MESSAGE_VALUE2}</textarea></td>
                    </tr>
                    <tr>                        
                        <td><b>{TR_SENDER_EMAIL}</b></td>
                        <td>{SENDER_EMAIL_VALUE}</td>
                        <input type="hidden" name="sender_email" value="{SENDER_EMAIL_VALUE}" />                        
                    </tr>
                    <tr>                        
                        <td><b>{TR_SENDER_NAME}</b></td>
                        <td>{SENDER_NAME_VALUE}</td>
                        <input type="hidden" name="sender_name" value="{SENDER_NAME_VALUE}" />                        
                    </tr>                    
                </table>
                <br />
                <td><input name="Submit" type="submit" class="button" value="{TR_APPLY_CHANGES}" /></td>
                <input type="hidden" name="uaction" value="apply" />
            </form>
        </div>
        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>