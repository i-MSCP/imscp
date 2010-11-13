<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_MAINTENANCEMODE_PAGE_TITLE}</title>
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
                <h1 class="webtools">{TR_MENU_SYSTEM_TOOLS}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="system_info.php">{TR_MENU_SYSTEM_TOOLS}</a></li>
                <li><a href="settings_maintenance_mode.php">{TR_MAINTENANCEMODE}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">

            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <h2 class="maintenancemode"><span>{TR_MAINTENANCEMODE}</span></h2>
            <form action="settings_maintenance_mode.php" method="post" name="maintenancemode_frm" id="maintenancemode_frm">
                <table>
                    <tr>
                        <td><label for="maintenancemode_message">{TR_MESSAGE}</label></td>
                        <td><textarea name="maintenancemode_message" id="maintenancemode_message" cols="80" rows="30">{MESSAGE_VALUE}</textarea></td>
                    </tr>
                    <tr>
                        <td><label for="maintenancemode">{TR_MAINTENANCEMODE}</label></td>
                        <td><select name="maintenancemode" id="maintenancemode">
                                <option value="0" {SELECTED_OFF}>{TR_DISABLED}</option>
                                <option value="1" {SELECTED_ON}>{TR_ENABLED}</option>
                        </select></td>
                    </tr>
                </table>
                <div class="buttons">
                    <input name="Submit" type="submit" value="{TR_APPLY_CHANGES}" />
                    <input type="hidden" name="uaction" value="apply" />
                </div>
            </form>
            <div class="info">{TR_MESSAGE_TEMPLATE_INFO}</div>
        </div>

        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>