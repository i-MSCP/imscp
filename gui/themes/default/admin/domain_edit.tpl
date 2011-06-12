<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_EDIT_DOMAIN_PAGE_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
        <link href="{THEME_COLOR_PATH}/css/jquery.ui.datepicker.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/imscp.js"></script>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.js"></script>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.imscpTooltips.js"></script>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.ui.core.js"></script>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.ui.datepicker.js"></script>
        <!--[if IE 6]>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <![endif]-->
        <script language="JavaScript" type="text/JavaScript">
            /*<![CDATA[*/
            $(document).ready(function() {
                $('#dmn_exp_help').iMSCPtooltips({msg:"{TR_DMN_EXP_HELP}"});
                $('#datepicker').datepicker();
                $('#datepicker').change(function() {
                    if($(this).val() != '') {
                        $('#neverexpire').attr('disabled', 'disabled')
                    } else {
                        $('#neverexpire').removeAttr('disabled');
                    }
                });
                $('#neverexpire').change(function() {
                    if($(this).is(':checked')) {
                        $('#datepicker').attr('disabled', 'disabled')
                    } else {
                        $('#datepicker').removeAttr('disabled');
                    }
                });
            });
            /*]]>*/
        </script>
    </head>
    <body>
        <div class="header">
            {MAIN_MENU}
            <div class="logo">
                <img src="{THEME_COLOR_PATH}/images/imscp_logo.png" alt="i-MSCP logo" />
            </div>
        </div>
        <div class="location">
            <div class="location-area icons-left">
                <h1 class="manage_users">{TR_MENU_MANAGE_USERS}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a>
                </li>
            </ul>
            <ul class="path">
                <li><a href="manage_users.php">{TR_MENU_MANAGE_USERS}</a></li>
                <li>
                    <a href="domain_edit.php?edit_id={DOMAIN_ID}">{TR_EDIT_DOMAIN}</a>
                </li>
            </ul>
        </div>
        <div class="left_menu">
            {MENU}
        </div>
        <div class="body">
            <h2 class="domains"><span>{TR_EDIT_DOMAIN}</span></h2>
            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->
            <form name="admin_edit_domain_frm" method="post" action="domain_edit.php">
                <table>
                    <tr>
                        <td>{TR_DOMAIN_NAME}</td>
                        <td>{VL_DOMAIN_NAME}</td>
                    </tr>
                    <tr>
                        <td>{TR_DOMAIN_EXPIRE}</td>
                        <td>{VL_DOMAIN_EXPIRE}</td>
                    </tr>
                    <tr>
                        <td>
                            <label for="datepicker">{TR_DOMAIN_NEW_EXPIRE}</label>
                            <span style="vertical-align:middle;" class="icon i_help" id="dmn_exp_help">Help</span>
                        </td>
                        <td>
                            <div class="content">
                                <input type="text" id="datepicker" name="dmn_expire_date" value="{VL_DOMAIN_EXPIRE_DATE}" {VL_DISABLED} />
                                <label for="neverexpire">(MM/DD/YYYY) {TR_EXPIRE_CHECKBOX}</label>
                                <input type="checkbox" name="neverexpire" id="neverexpire" {VL_NEVEREXPIRE} {VL_DISABLED_NE}/>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>{TR_DOMAIN_IP}</td>
                        <td>{VL_DOMAIN_IP}</td>
                    </tr>
                    <tr>
                        <td><label for="domain_php">{TR_PHP_SUPP}</label></td>
                        <td><select id="domain_php" name="domain_php">
                            <option value="yes" {PHP_YES}>{TR_YES}</option>
                            <option value="no" {PHP_NO}>{TR_NO}</option>
                        </select>
                        </td>
                    </tr>
                    <!-- BDP: t_software_support -->
                    <tr>
                        <td>
                            <label for="domain_software_allowed">{TR_SOFTWARE_SUPP}</label>
                        </td>
                        <td>
                            <select name="domain_software_allowed" id="domain_software_allowed">
                                <option value="yes" {SOFTWARE_YES}>{TR_YES}</option>
                                <option value="no" {SOFTWARE_NO}>{TR_NO}</option>
                            </select>
                        </td>
                    </tr>
                    <!-- EDP: t_software_support -->
                    <tr>
                        <td><label for="domain_cgi">{TR_CGI_SUPP}</label></td>
                        <td><select id="domain_cgi" name="domain_cgi">
                            <option value="yes" {CGI_YES}>{TR_YES}</option>
                            <option value="no" {CGI_NO}>{TR_NO}</option>
                        </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="domain_dns">{TR_DNS_SUPP}</label></td>
                        <td><select id="domain_dns" name="domain_dns">
                            <option value="yes" {DNS_YES}>{TR_YES}</option>
                            <option value="no" {DNS_NO}>{TR_NO}</option>
                        </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="backup">{TR_BACKUP}</label></td>
                        <td><select id="backup" name="backup">
                            <option value="dmn" {BACKUP_DOMAIN}>{TR_BACKUP_DOMAIN}</option>
                            <option value="sql" {BACKUP_SQL}>{TR_BACKUP_SQL}</option>
                            <option value="full" {BACKUP_FULL}>{TR_BACKUP_FULL}</option>
                            <option value="no" {BACKUP_NO}>{TR_BACKUP_NO}</option>
                        </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="dom_sub">{TR_SUBDOMAINS}</label></td>
                        <td>
                            <input type="text" name="dom_sub" id="dom_sub" value="{VL_DOM_SUB}" />
                        </td>
                    </tr>
                    <tr>
                        <td><label for="dom_alias">{TR_ALIAS}</label></td>
                        <td>
                            <input type="text" name="dom_alias" id="dom_alias" value="{VL_DOM_ALIAS}" />
                        </td>
                    </tr>
                    <tr>
                        <td><label for="dom_mail_acCount">{TR_MAIL_ACCOUNT}</label>
                        </td>
                        <td>
                            <input type="text" name="dom_mail_acCount" id="dom_mail_acCount" value="{VL_DOM_MAIL_ACCOUNT}" />
                        </td>
                    </tr>
                    <tr>
                        <td><label for="dom_ftp_acCounts">{TR_FTP_ACCOUNTS}</label>
                        </td>
                        <td>
                            <input type="text" name="dom_ftp_acCounts" id="dom_ftp_acCounts" value="{VL_FTP_ACCOUNTS}" />
                        </td>
                    </tr>
                    <tr>
                        <td><label for="dom_sqldb">{TR_SQL_DB}</label></td>
                        <td>
                            <input type="text" name="dom_sqldb" id="dom_sqldb" value="{VL_SQL_DB}" />
                        </td>
                    </tr>
                    <tr>
                        <td><label for="dom_sql_users">{TR_SQL_USERS}</label></td>
                        <td>
                            <input type="text" name="dom_sql_users" id="dom_sql_users" value="{VL_SQL_USERS}" />
                        </td>
                    </tr>
                    <tr>
                        <td><label for="dom_traffic">{TR_TRAFFIC}</label></td>
                        <td>
                            <input type="text" name="dom_traffic" id="dom_traffic" value="{VL_TRAFFIC}" />
                        </td>
                    </tr>
                    <tr>
                        <td><label for="dom_disk">{TR_DISK}</label></td>
                        <td>
                            <input type="text" name="dom_disk" id="dom_disk" value="{VL_DOM_DISK}" />
                        </td>
                    </tr>
                </table>
                <div class="buttons">
                    <input name="Submit" type="submit" value="{TR_UPDATE_DATA}" />
                    <input name="Submit" type="submit" onclick="MM_goToURL('parent','users.php');return document.MM_returnValue" value="{TR_CANCEL}" />
                    <input type="hidden" name="uaction" value="update" />
                    <input type="hidden" name="domain_id" value="{DOMAIN_ID}" />
                </div>
            </form>
            <br />
        </div>
        <div class="footer">
            i-MSCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>
