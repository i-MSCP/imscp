<?xml ve<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_CHANGE_SERVER_TRAFFIC_SETTINGS_TITLE}</title>
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
                <li><a href="ip_manage.php">{TR_SERVER_TRAFFIC_SETTINGS}</a></li>
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
            <h2 class="support"><span>{TR_SERVER_TRAFFIC_SETTINGS}</span></h2>
            <fieldset>
                <legend>{TR_SET_SERVER_TRAFFIC_SETTINGS}</legend>
            </fieldset>
            <form action="settings_server_traffic.php" method="post" name="admin_modify_server_traffic_settings" id="admin_modify_server_traffic_settings">
                <table>              
                    <tr>                       
                        <td width="25"><label for="max_traffic">{TR_MAX_TRAFFIC}</label></td>
                        <td><input name="max_traffic" type="text" id="max_traffic" value="{MAX_TRAFFIC}" />
                        </td>
                    </tr>
                    <tr>                        
                        <td><label for="traffic_warning">{TR_WARNING}</label></td>
                        <td><input name="traffic_warning" type="text" id="traffic_warning" value="{TRAFFIC_WARNING}" />
                        </td>
                    </tr>
                </table>
                    <tr>
                        <br />
                        <td><input name="Submit" type="submit" class="button" value="{TR_MODIFY}" /></td>
                    </tr>                
                <input type="hidden" name="uaction" value="modify" />
            </form>
            </div>
        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>