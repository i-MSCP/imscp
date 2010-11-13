<?xml ve<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_SETTINGS_PAGE_TITLE}</title>
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
            <!--
            function action_delete(url, service) {
                if (!confirm(sprintf("{TR_MESSAGE_DELETE}", service)))
                    return false;
                location = url;
            }

            function enable_for_post() {
                for (var i = 0; i < document.frmsettings.length; i++) {
                    for (var j = 0; j < document.frmsettings.elements[i].length; j++) {
                        if (document.frmsettings.elements[i].name == "port_type[]") {
                            document.frmsettings.elements[i].disabled = false;
                        }
                    }
                }
                return true;
            }
            //-->
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
                <h1 class="settings">{TR_MENU_SETTINGS}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="settings_ports.php">{TR_SERVERPORTS}</a></li>
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
            <h2 class="settings"><span>{TR_SERVERPORTS}</span></h2>
            <form name="frmsettings" method="post" action="settings_ports.php" onsubmit="return enable_for_post();">             
                <table>
                    <tr>
                        <td<strong>{TR_SERVICE}</strong></td>
                        <td><strong>{TR_IP}</strong></td>
                        <td><strong>{TR_PORT}</strong></td>
                        <td><strong>{TR_PROTOCOL}</strong></td>
                        <td"><strong>{TR_SHOW}</strong></td>
                        <td><strong>{TR_ACTION}</strong></td>
                    </tr>
                    <!-- BDP: service_ports -->                  
                    <td><b>{SERVICE}</b></td>
                    <input name="var_name[]" type="hidden" id="var_name{NUM}" value="{VAR_NAME}" />
                    <input name="custom[]" type="hidden" id="custom{NUM}" value="{CUSTOM}" />
                    <td class="{CLASS}">
                        <input name="ip[]" type="text" class="textinput" id="ip{NUM}" value="{IP}" maxlength="15" {PORT_READONLY} />
                </td>
                <td class="{CLASS}">
                    <input name="port[]" type="text" class="textinput" id="port{NUM}" style="width:50px" value="{PORT}" maxlength="5" {PORT_READONLY} />
            </td>
            <td class="{CLASS}">
                <select name="port_type[]" id="port_type{NUM}" {PROTOCOL_READONLY}>
                        <option value="udp" {SELECTED_UDP}>{TR_UDP}</option>
                    <option value="tcp" {SELECTED_TCP}>{TR_TCP}</option>
                </select>
            </td>
            <td class="{CLASS}">
                <select name="show_val[]" id="show_val{NUM}">
                    <option value="1" {SELECTED_ON}>{TR_ENABLED}</option>
                    <option value="0" {SELECTED_OFF}>{TR_DISABLED}</option>
                </select>
            </td>
            <td class="{CLASS}" width="100" nowrap="nowrap">
                <!-- BDP: port_delete_show -->
													{TR_DELETE}
                <!-- EDP: port_delete_show -->
                <!-- BDP: port_delete_link -->
                <a class="icon i_delete" href="#" onclick="action_delete('{URL_DELETE_USR}', '{NAME}')">{TR_DELETE}</a>                
                <!-- EDP: port_delete_link -->
            </td>
            </tr>
            <!-- EDP: service_ports -->
        </table>
        <br />
        <td><b>{TR_ADD}</b></td>
        <table>
            <tr>
                <td><input name="name_new" type="text" class="textinput" id="service" value="" maxlength="25"/></td>
                <td><input name="ip_new" type="text" class="textinput" id="ip" style="" value="" maxlength="15" /></td>
                <td><input name="port_new" type="text" class="textinput" id="port" style="width:50px" value="" maxlength="6" /></td>
                <td>
                    <select name="port_type_new" id="port_type">
                        <option value="udp">{TR_UDP}</option>
                        <option value="tcp" selected="selected">{TR_TCP}</option>
                    </select>
                </td>
                <td>
                    <select name="show_val_new" id="show_val">
                        <option value="1" selected="selected">{TR_ENABLED}</option>
                        <option value="0">{TR_DISABLED}</option>
                    </select>
                </td>
                <td class="{CLASS}" width="100" nowrap="nowrap">&nbsp;</td>
            </tr>
            <tr>
        </table>
        <br />
        <td>&nbsp;</td>
        <td colspan="6">
            <input type="hidden" name="uaction" value="apply" />
            <input name="Submit" type="submit" class="button" value="{TR_APPLY_CHANGES}" />
        </td>
        </tr>
    </form>
</div>
<div class="footer">
    ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
</div>
</body>
</html>