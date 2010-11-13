<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_MAIN_INDEX_PAGE_TITLE}</title>
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
                <h1 class="general">{TR_GENERAL_INFORMATION}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="index.php">{TR_GENERAL_INFORMATION}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
    
            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->
            <!-- BDP: msg_entry -->
            <div class="warning">{TR_NEW_MSGS}</div>
            <!-- EDP: msg_entry -->
            <!-- BDP: update_message -->
            <div class="info">{UPDATE}</div>
            <!-- EDP: update_message -->
            <!-- BDP: database_update_message -->
            <div class="warning">{DATABASE_UPDATE}</div>
            <!-- EDP: database_update_message -->
            <!-- BDP: critical_update_message -->
            <div class="warning">{CRITICAL_MESSAGE}</div>
            <!-- EDP: critical_update_message -->

            <h2 class="general"><span>{TR_GENERAL_INFORMATION}</span></h2>


            <!-- BDP: props_list -->
            <table>
                <tr>
                    <td>{TR_ACCOUNT_NAME}</td>
                    <td>{ACCOUNT_NAME}</td>
                </tr>
                <tr>
                    <td>{TR_ADMIN_USERS}</td>
                    <td>{ADMIN_USERS}</td>
                </tr>
                <tr>
                    <td>{TR_RESELLER_USERS}</td>
                    <td>{RESELLER_USERS}</td>
                </tr>
                <tr>
                    <td>{TR_NORMAL_USERS}</td>
                    <td>{NORMAL_USERS}</td>
                </tr>
                <tr>
                    <td>{TR_DOMAINS}</td>
                    <td>{DOMAINS}</td>
                </tr>
                <tr>
                    <td>{TR_SUBDOMAINS}</td>
                    <td>{SUBDOMAINS}</td>
                </tr>
                <tr>
                    <td>{TR_DOMAINS_ALIASES}</td>
                    <td>{DOMAINS_ALIASES}</td>
                </tr>
                <tr>
                    <td>{TR_MAIL_ACCOUNTS}</td>
                    <td>{MAIL_ACCOUNTS}</td>
                </tr>
                <tr>
                    <td>{TR_FTP_ACCOUNTS}</td>
                    <td>{FTP_ACCOUNTS}</td>
                </tr>
                <tr>
                    <td>{TR_SQL_DATABASES}</td>
                    <td>{SQL_DATABASES}</td>
                </tr>
                <tr>
                    <td>{TR_SQL_USERS}</td>
                    <td>{SQL_USERS}</td>
                </tr>
            </table>
            
            <h2 class="traffic"><span>{TR_SERVER_TRAFFIC}</span></h2>
            <!-- BDP: traff_warn -->
            <div class="warning">{TR_TRAFFIC_WARNING}</div>
            <!-- EDP: traff_warn -->
            {TRAFFIC_WARNING}
            <div class="graph"><span style="width:{TRAFFIC_PERCENT}%">&nbsp;</span></div>
        
        </div>

        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>

    </body>
</html>