<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_IMSCP_UPDATES_PAGE_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
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
                <img src="{THEME_COLOR_PATH}/images/imscp_logo.png" alt="i-MSCP logo" />
                <img src="{THEME_COLOR_PATH}/images/imscp_webhosting.png" alt="i-MSCP" />
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
                <li><a href="imscp_updates.php">{TR_UPDATES_TITLE}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">

            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <h2 class="update"><span>{TR_UPDATES_TITLE}</span></h2>
            <!-- BDP: props_list -->
            <table class="description">
                <!-- BDP: table_header -->
                <tr>
                    <th>{TR_AVAILABLE_UPDATES}</th>
                </tr>
                <!-- EDP: table_header -->
                <!-- BDP: update_message -->
                <tr>
                    <td>{TR_MESSAGE}</td>
                </tr>
                <!-- EDP: update_message -->
                <!-- BDP: update_infos -->
                <tr>
                    <td>{TR_UPDATE}</td>
                    <td>{UPDATE}</td>
                </tr>
                <tr>
                    <th>{TR_INFOS}</th>
                    <td>{INFOS}</td>
                </tr>
                <!-- EDP: update_infos -->
            </table>
            <!-- EDP: props_list -->
        </div>

        <div class="footer">
            i-MSCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>
