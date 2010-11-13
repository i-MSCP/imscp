<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_SYSTEM_INFO_PAGE_TITLE}</title>
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
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <h2 class="tools"><span>{TR_SYSTEM_INFO}</span></h2>
            <!-- BDP: props_list -->
            <table class="description">
                <tr>
                    <th>{TR_KERNEL}</th>
                    <td>{KERNEL}</td>
                </tr>
                <tr>
                    <th>{TR_UPTIME}</th>
                    <td>{UPTIME}</td>
                </tr>
                <tr>
                    <th>{TR_LOAD}</th>
                    <td>{LOAD}</td>
                </tr>
            </table>

            <!-- EDP: props_list -->
            <h2 class="tools"><span>{TR_CPU_SYSTEM_INFO}</span></h2>
            <table class="description">
                <tr>
                    <th>{TR_CPU_MODEL}</th>
                    <td>{CPU_MODEL}</td>
                </tr>
                <tr>
                    <th>{TR_CPU_COUNT}</th>
                    <td>{CPU_COUNT}</td>
                </tr>
                <tr>
                    <th>{TR_CPU_MHZ}</th>
                    <td>{CPU_MHZ}</td>
                </tr>
                <tr>
                    <th>{TR_CPU_CACHE}</th>
                    <td>{CPU_CACHE}</td>
                </tr>
                <tr>
                    <th>{TR_CPU_BOGOMIPS}</th>
                    <td>{CPU_BOGOMIPS}</td>
                </tr>
            </table>

            <h2 class="tools"><span>{TR_MEMRY_SYSTEM_INFO}</span></h2>
            <table>
                <tr>
                    <th>{TR_RAM}</th>
                    <th>{TR_TOTAL}</th>
                    <th>{TR_USED}</th>
                    <th>{TR_FREE}</th>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>{RAM_TOTAL}</td>
                    <td>{RAM_USED}</td>
                    <td>{RAM_FREE}</td>
                </tr>
                <tr>
                    <th>{TR_SWAP}</th>
                    <th>{TR_TOTAL}</th>
                    <th>{TR_USED}</th>
                    <th>{TR_FREE}</th>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>{SWAP_TOTAL}</td>
                    <td>{SWAP_USED}</td>
                    <td>{SWAP_FREE}</td>
                </tr>
            </table>

            <h2 class="tools"><span>{TR_FILE_SYSTEM_INFO}</span></h2>
            <!-- BDP: disk_list -->
            <table>
                <tr>
                    <th>{TR_MOUNT}</th>
                    <th>{TR_TYPE}</th>
                    <th>{TR_PARTITION}</th>
                    <th>{TR_PERCENT}</th>
                    <th>{TR_FREE}</th>
                    <th>{TR_USED}</th>
                    <th>{TR_SIZE}</th>
                </tr>
                <!-- BDP: disk_list_item -->
                <tr>
                    <td>{MOUNT}</td>
                    <td>{TYPE}</td>
                    <td>{PARTITION}</td>
                    <td>{PERCENT} %</td>
                    <td>{FREE}</td>
                    <td>{USED}</td>
                    <td>{SIZE}</td>
                </tr>
                <!-- EDP: disk_list_item -->
            </table>
            <!-- EDP: disk_list -->
        </div>

        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>