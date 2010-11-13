<-- Kilburn el tamaño del cuadro de las ips cuando lo meto en la tabla se pone gigante el puñetero no he conseguido ponerlo a su tamaño para las ips -->
<?xml ve<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_IP_MANAGE_PAGE_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/ispcp.js"></script>
        <!--[if IE 6]>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <![endif]-->
        <script type="text/javascript">
            /* <![CDATA[ */
            function action_delete(url, subject) {
                return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
            }
            /* ]]> */
        </script>
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
                <h1 class="general">{TR_MENU_SETTINGS}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="ip_manage.php">{MANAGE_IPS}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>


        <div class="body">
            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <!-- BDP: tickets_list -->
            <h2 class="support"><span>{MANAGE_IPS}</span></h2>
            <fieldset>
                <legend>{TR_AVAILABLE_IPS}</legend>
            </fieldset>
            <table>                    
                <tr align="center">                    
                    <td align="left"><strong>{TR_IP}</strong></td>
                    <td><strong>{TR_DOMAIN}</strong></td>
                    <td><strong>{TR_ALIAS}</strong></td>
                    <td><strong>{TR_NETWORK_CARD}</strong></td>
                    <td width="103"><strong>{TR_ACTION}</strong></td>
                </tr>
                <!-- BDP: ip_row -->
                <tr>                    
                    <td align="left"">{IP}</td>
                    <td align="center">{DOMAIN}</td>
                    <td align="center">{ALIAS}</td>
                    <td align="center">{NETWORK_CARD}</td>
                    <td align="center">
                        <!-- BDP: ip_delete_show -->
												{IP_ACTION}
                        <!-- EDP: ip_delete_show -->
                        <!-- BDP: ip_delete_link -->
                        <img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /> <a href="#" onclick="action_delete('{IP_ACTION_SCRIPT}', '{IP}')"  title="{IP_ACTION}" class="link">{IP_ACTION}</a>
                        <!-- EDP: ip_delete_link -->
                    </td>
                </tr>
                <!-- EDP: ip_row -->
            </table>           
            <form name="add_new_ip_frm" method="post" action="ip_manage.php">
                <br />
                <fieldset>
                    <legend>{TR_ADD_NEW_IP}</legend>
                </fieldset>
                <table>
                    <tr>                        
                        <td width="25"><label for="ip">{TR_IP}</label></td>
                        <td><input name="ip_number_1" type="text" class="textinput" style="width:31px" value="{VALUE_IP1}" maxlength="3" />	                       											.</td>
                        <input name="ip_number_2" type="text" class="textinput" style="width:31px" value="{VALUE_IP2}" maxlength="3" />													.
                        <td><input name="ip_number_3" type="text" class="textinput" style="width:31px" value="{VALUE_IP3}" maxlength="3" />													.</td>
                        <td><input name="ip_number_4" type="text" class="textinput" style="width:31px" value="{VALUE_IP4}" maxlength="3" /></td>
                    </tr>
                    <tr>
                    <td><label for="domain">{TR_DOMAIN}</label></td>
                    <td><input type="text" name="domain" id="domain" value="{VALUE_DOMAIN}" /></td>
                    </tr>
                    <tr>
                        <td><label for="alias">{TR_ALIAS}</label></td>
                        <td><input type="text" name="alias" id="alias" value="{VALUE_ALIAS}" />
                        </td>
                    </tr>
                    <tr>                        
                        <td>{TR_NETWORK_CARD}</td>
                        <td>
                            <select name="ip_card">
                                <!-- BDP: card_list -->
                                <option>{NETWORK_CARDS}</option>
                                <!-- EDP: card_list -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input name="Submit" type="submit" class="button" value="  {TR_ADD}  " />
                        </td>
                    </tr>
                </table>
                <input type="hidden" name="uaction" value="add_ip" />
            </form>
        </div>
        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>