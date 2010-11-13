<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_SERVER_DAY_STATS_PAGE_TITLE}</title>
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
                <h1 class="manage_users">{TR_SERVER_DAY_STATISTICS}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="server_statistic.php">{TR_SERVER_DAY_STATISTICS}</a></li>

            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">

            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <h2 class="general"><span>{TR_SERVER_DAY_STATISTICS}</span></h2>
            <p>{TR_YEAR} {YEAR}&nbsp;&nbsp;&nbsp;&nbsp; {TR_MONTH} {MONTH}&nbsp;&nbsp;&nbsp;{TR_DAY} {DAY}</p>
            <table>
            	<thead>
	                <tr>
	                    <th>{TR_HOUR}</th>
	                    <th>{TR_WEB_IN}</th>
	                    <th>{TR_WEB_OUT}</th>
	                    <th>{TR_SMTP_IN}</th>
	                    <th>{TR_SMTP_OUT}</th>
	                    <th>{TR_POP_IN}</th>
	                    <th>{TR_POP_OUT}</th>
	                    <th>{TR_OTHER_IN}</th>
	                    <th>{TR_OTHER_OUT}</th>
	                    <th>{TR_ALL_IN}</th>
	                    <th>{TR_ALL_OUT}</th>
	                    <th>{TR_ALL}</th>
	                </tr>
				</thead>
	            <tfoot>
	                <tr>
	                    <td>{TR_ALL}</td>
	                    <td>{WEB_IN_ALL}</td>
	                    <td>{WEB_OUT_ALL}</td>
	                    <td>{SMTP_IN_ALL}</td>
	                    <td>{SMTP_OUT_ALL}</td>
	                    <td>{POP_IN_ALL}</td>
	                    <td>{POP_OUT_ALL}</td>
	                    <td>{OTHER_IN_ALL}</td>
	                    <td>{OTHER_OUT_ALL}</td>
	                    <td>{ALL_IN_ALL}</td>
	                    <td>{ALL_OUT_ALL}</td>
	                    <td>{ALL_ALL}</td>
	                </tr>
	            </tfoot>   
                <tbody>
	                <!-- BDP: hour_list -->
	                <tr>
	                    <td>{HOUR}</td>
	                    <td>{WEB_IN}</td>
	                    <td>{WEB_OUT}</td>
	                    <td>{SMTP_IN}</td>
	                    <td>{SMTP_OUT}</td>
	                    <td>{POP_IN}</td>
	                    <td>{POP_OUT}</td>
	                    <td>{OTHER_IN}</td>
	                    <td>{OTHER_OUT}</td>
	                    <td>{ALL_IN}</td>
	                    <td>{ALL_OUT}</td>
	                    <td>{ALL}</td>
	                </tr>
	                <!-- EDP: hour_list -->
                </tbody>
            </table>
        </div>
        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>