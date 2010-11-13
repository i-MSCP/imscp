<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_MANAGE_RESELLER_OWNERS_PAGE_TITLE}</title>
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
                <li><a href="manage_reseller_owners.php">{TR_RESELLER_ASSIGNMENT}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">

            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <h2 class="users2"><span>{TR_RESELLER_ASSIGNMENT}</span></h2>
            <form action="manage_reseller_owners.php" method="post" name="admin_reseller_assignment" id="admin_reseller_assignment">
   
                <!-- BDP: reseller_list -->
                <table>
                    <tr>
                        <th>{TR_NUMBER}</th>
                        <th>{TR_MARK}</th>
                        <th>{TR_RESELLER_NAME}</th>
                        <th>{TR_OWNER}</th>
                    </tr>
                    <!-- BDP: reseller_item -->
                    <tr>
                        <td>{NUMBER}</td>
                        <td><input id="{CKB_NAME}" type="checkbox" name="{CKB_NAME}" /></td>
                        <td><label for="{CKB_NAME}">{RESELLER_NAME}</label></td>
                        <td>{OWNER}</td>
                    </tr>
                    <!-- EDP: reseller_item -->
                </table>
                <!-- EDP: reseller_list -->

                <!-- BDP: select_admin -->
                <div class="buttons">
                    {TR_TO_ADMIN}
                    <select name="dest_admin">
                    <!-- BDP: select_admin_option -->
                        <option {SELECTED} value="{VALUE}">{OPTION}</option>
                    <!-- EDP: select_admin_option -->
                    </select>
                    <input name="Submit" type="submit" value="{TR_MOVE}" />
                    <input type="hidden" name="uaction" value="reseller_owner" />
                </div>
                <!-- EDP: select_admin -->
            </form>
        </div>

        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>