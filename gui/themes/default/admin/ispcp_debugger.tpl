<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_ISPCP_DEBUGGER_PAGE_TITLE}</title>
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
                <li><a href="ispcp_debugger.php">{TR_DEBUGGER_TITLE}</a></li>
            </ul>
        </div>
        <div class="left_menu">
            {MENU}
        </div>
        <div class="body">
            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <h2 class="debugger"><span>{TR_DEBUGGER_TITLE}</span></h2>
            <!-- BDP: props_list -->
            <table>
                <tr>
                    <td><b>{TR_DOMAIN_ERRORS}</b></td>
                </tr>
                <!-- BDP: domain_message -->
                <tr>
                    <td>{TR_DOMAIN_MESSAGE}</td>
                </tr>
                <!-- EDP: domain_message -->
                <!-- BDP: domain_list -->
                <tr>
                    <td>
                        <td>&nbsp;</td>
			  {TR_DOMAIN_NAME} - <a href="ispcp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}" class="link">{TR_CHANGE_STATUS}</a><br />
                        <span style="color:red;">{TR_DOMAIN_ERROR}</span>
                    </td>
                </tr>
                <!-- EDP: domain_list -->
            </table>
            <br />
            <table>
                <tr>
                    <td><b>{TR_ALIAS_ERRORS}</b></td>
                </tr>
                <!-- BDP: alias_message -->
                <tr>
                    <td>{TR_ALIAS_MESSAGE}</td>
                </tr>
                <!-- EDP: alias_message -->
                <!-- BDP: alias_list -->
                <tr>
                    <td>&nbsp;</td>
                    <td>
			  {TR_ALIAS_NAME} - <a href="ispcp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}" class="link">{TR_CHANGE_STATUS}</a><br />
                        <span style="color:red;">{TR_ALIAS_ERROR}</span>
                    </td>
                </tr>
                <!-- EDP: alias_list -->
            </table>
            <br />
            <table>
                <tr>                   
                    <td><b>{TR_SUBDOMAIN_ERRORS}</b></td>
                </tr>
                <!-- BDP: subdomain_message -->
                <tr>                    
                    <td>{TR_SUBDOMAIN_MESSAGE}</td>
                </tr>
                <!-- EDP: subdomain_message -->
                <!-- BDP: subdomain_list -->
                <tr>
                    <td>&nbsp;</td>
                    <td>
			  {TR_SUBDOMAIN_NAME} - <a href="ispcp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}" class="link">{TR_CHANGE_STATUS}</a><br />
                        <span style="color:red;">{TR_SUBDOMAIN_ERROR}</span>
                    </td>
                </tr>
                <!-- EDP: subdomain_list -->
            </table>
            <br />
            <table>
                <tr>                    
                    <td><b>{TR_SUBDOMAIN_ALIAS_ERRORS}</b></td>
                </tr>
                <!-- BDP: subdomain_alias_message -->
                <tr>                   
                    <td>{TR_SUBDOMAIN_ALIAS_MESSAGE}</td>
                </tr>
                <!-- EDP: subdomain_alias_message -->
                <!-- BDP: subdomain_alias_list -->
                <tr>
                    <td>&nbsp;</td>
                    <td>
			  {TR_SUBDOMAIN_ALIAS_NAME} - <a href="ispcp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}" class="link">{TR_CHANGE_STATUS}</a><br />
                        <span style="color:red;">{TR_SUBDOMAIN_ALIAS_ERROR}</span>
                    </td>
                </tr>
                <!-- EDP: subdomain_alias_list -->
            </table>
            <br />
            <table>
                <tr>                   
                    <td class="content3"><b>{TR_MAIL_ERRORS}</b></td>
                </tr>
                <!-- BDP: mail_message -->
                <tr>                    
                    <td>{TR_MAIL_MESSAGE}</td>
                </tr>
                <!-- EDP: mail_message -->
                <!-- BDP: mail_list -->
                <tr>
                    <td>&nbsp;</td>
                    <td>
			  {TR_MAIL_NAME} - <a href="ispcp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}" class="link">{TR_CHANGE_STATUS}</a><br />
                        <span style="color:red;">{TR_MAIL_ERROR}</span></td>
                </tr>
                <!-- EDP: mail_list -->
            </table>
            <br />
            <table>
                <tr>                  
                    <td><b>{TR_DAEMON_TOOLS}</b></td>
                </tr>
                <tr>
                    
                    <td><a href="ispcp_debugger.php?action=run_engine" class="link">{EXEC_COUNT} {TR_EXEC_REQUESTS}</a></td>
                </tr>
            </table>
            <!-- EDP: props_list -->
           </div>
        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>
