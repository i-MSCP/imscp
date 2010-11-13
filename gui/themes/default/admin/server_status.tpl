<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_SERVER_STATUS_PAGE_TITLE}</title>
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
                <h1 class="general">{TR_MENU_GENERAL_INFORMATION}</h1>
            </div>
            <ul class="location-menu">
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="index.php">{TR_MENU_GENERAL_INFORMATION}</a></li>
                <li><a href="server_status.php">{TR_SERVER_STATUS}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>

        <div class="body">
            <h2 class="doc"><span>{TR_SERVER_STATUS}</span></h2>
            <!-- BDP: props_list -->
            <table>
                <tr>
                    <th>{TR_HOST}</th>
                    <th>{TR_SERVICE}</th>
                    <th>{TR_STATUS}</th>
                </tr>
                <!-- BDP: service_status -->
                <tr>
                    <td class="{CLASS}">{HOST} (Port {PORT})</td>
                    <td class="{CLASS}">{SERVICE}</td>
                    <td class="{CLASS}">{STATUS}</td>
                </tr>
                <!-- EDP: service_status -->
            </table>
            <!-- EDP: props_list -->
        </div>

    </body>
</html>