<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_MANAGE_RESELLER_USERS_PAGE_TITLE}</title>
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
                <h1 class="manage_users">{TR_MENU_MANAGE_USERS}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="manage_users.php">{TR_MENU_MANAGE_USERS}</a></li>
                <li><a>{TR_USER_ASSIGNMENT}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">

            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <h2 class="users2"><span>{TR_USER_ASSIGNMENT}</span></h2>
            <form action="manage_reseller_users.php" method="post" name="admin_user_assignment" id="admin_user_assignment">
                <!-- BDP: src_reseller -->
                <div class="buttons">
                    {TR_FROM_RESELLER}
                    <select name="src_reseller" onchange="return sbmt(document.forms[0],'change_src');">
                        <!-- BDP: src_reseller_option -->
                        <option {SRC_RSL_SELECTED} value="{SRC_RSL_VALUE}">{SRC_RSL_OPTION}</option>
                        <!-- EDP: src_reseller_option -->
                    </select>
                </div>
                <!-- EDP: src_reseller -->

                <!-- BDP: reseller_list -->
                <table>
                    <tr>
                        <th>{TR_NUMBER}</th>
                        <th>{TR_MARK}</th>
                        <th>{TR_USER_NAME}</th>
                    </tr>
                    <!-- BDP: reseller_item -->
                    <tr>
                        <td>{NUMBER}</td>
                        <td><input id="{CKB_NAME}" type="checkbox" name="{CKB_NAME}" /></td>
                        <td><label for="{CKB_NAME}">{USER_NAME}</label></td>
                    </tr>
                    <!-- EDP: reseller_item -->
                </table>
                <!-- EDP: reseller_list -->

                <!-- BDP: dst_reseller -->
                <div class="buttons">
                    {TR_TO_RESELLER}
                    <select name="dst_reseller">
                        <!-- BDP: dst_reseller_option -->
                        <option {DST_RSL_SELECTED} value="{DST_RSL_VALUE}">{DST_RSL_OPTION}</option>
                        <!-- EDP: dst_reseller_option -->
                    </select>
                    <input name="Submit" type="submit" class="button" value="{TR_MOVE}" />
                    <input type="hidden" name="uaction" value="move_user" />
                </div>
                <!-- EDP: dst_reseller -->

            </form>
        </div>
    </body>
</html>
