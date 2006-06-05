<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_SERVER_DAY_STATS_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
</head>

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%">
  <tr>
    <td height="80" align="left" valign="top">
    <table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="17"><img src="{THEME_COLOR_PATH}/images/top/left.jpg" width="17" height="80"></td>
          <td width="198" align="center" background="{THEME_COLOR_PATH}/images/top/logo_background.jpg"><img src="{ISP_LOGO}"></td>
          <td background="{THEME_COLOR_PATH}/images/top/left_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/left_fill.jpg" width="2" height="80"></td>
          <td width="766"><img src="{THEME_COLOR_PATH}/images/top/middle_background.jpg" width="766" height="80"></td>
          <td background="{THEME_COLOR_PATH}/images/top/right_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/right_fill.jpg" width="3" height="80"></td>
          <td width="9"><img src="{THEME_COLOR_PATH}/images/top/right.jpg" width="9" height="80"></td>
        </tr>
    </table></td>
  </tr>
  <tr>
    <td valign="top"><table height="100%" width="100%"  border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="215" valign="top" bgcolor="#F5F5F5"><!-- Menu begin -->
  {MENU}
    <!-- Menu end -->
        </td>
        <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_stats.jpg" width="85" height="62" align="absmiddle">{TR_SERVER_DAY_STATISTICS}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td valign="top">
			<table>
              <tr>
                <td width="20" nowrap >&nbsp;</td>
                <td height="25" colspan="13" nowrap class="content">{TR_YEAR} {YEAR}&nbsp;&nbsp;&nbsp;&nbsp; {TR_MONTH}{MONTH}&nbsp;&nbsp;&nbsp;{TR_DAY} {DAY}</td>
                </tr>
              <tr align="center">
                <td nowrap>&nbsp;</td>
                <td class="content3" nowrap height="25"><b>{TR_HOUR}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_WEB_IN}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_WEB_OUT}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_MAIL_IN}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_MAIL_OUT}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_POP_IN}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_POP_OUT}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_OTHER_IN}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_OTHER_OUT}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_ALL_IN}</b></td>
                <td class="content3" nowrap height="25"><b><b>{TR_ALL_OUT}</b></b></td>
                <td class="content3" nowrap height="25"><b>{TR_ALL}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_HOUR}</b></td>
              </tr>
            <!-- BDP: hour_list -->
              <tr>
                <td nowrap align="center">&nbsp;</td>
                <td class="{ITEM_CLASS}" nowrap align="center"><b>{HOUR}</b></td>
                <td class="{ITEM_CLASS}" nowrap align="center"><span class="{ITEM_CLASS}">{WEB_IN}</span></td>
                <td class="{ITEM_CLASS}" nowrap align="center"> <span class="{ITEM_CLASS}">{WEB_OUT}</span></td>
                <td class="{ITEM_CLASS}" nowrap align="center"><span class="{ITEM_CLASS}">{MAIL_IN}</span></td>
                <td class="{ITEM_CLASS}" nowrap align="center"><span class="{ITEM_CLASS}">{MAIL_OUT}</span></td>
                <td class="{ITEM_CLASS}" nowrap align="center"><span class="{ITEM_CLASS}">{POP_IN}</span></td>
                <td class="{ITEM_CLASS}" nowrap align="center"><span class="{ITEM_CLASS}">{POP_OUT}</span></td>
                <td class="{ITEM_CLASS}" nowrap align="center"><span class="{ITEM_CLASS}">{OTHER_IN}</span></td>
                <td class="{ITEM_CLASS}" nowrap align="center"><span class="{ITEM_CLASS}">{OTHER_OUT}</span></td>
                <td class="{ITEM_CLASS}" nowrap align="center"><span class="{ITEM_CLASS}">{ALL_IN}</span></td>
                <td class="{ITEM_CLASS}" nowrap align="center"><span class="{ITEM_CLASS}">{ALL_OUT}</span></td>
                <td class="{ITEM_CLASS}" nowrap align="center">{ALL}</td>
                <td class="{ITEM_CLASS}" nowrap align="center"><b><b>{HOUR}</b></b></td>
              </tr>
            <!-- EDP: hour_list -->
              <tr>
                <td nowrap align="center">&nbsp;</td>
                <td class="content3" nowrap align="center"><b> {TR_ALL} </b></td>
                <td class="content3" nowrap align="center"><span class="content2"><strong>{WEB_IN_ALL}</strong></span></td>
                <td class="content3" nowrap align="center"> <span class="content2"><strong>{WEB_OUT_ALL}</strong></span></td>
                <td class="content3" nowrap align="center"><span class="content2"><strong>{MAIL_IN_ALL}</strong></span></td>
                <td class="content3" nowrap align="center"><span class="content2"><strong>{MAIL_OUT_ALL}</strong></span></td>
                <td class="content3" nowrap align="center"><span class="content2"><strong>{POP_IN_ALL}</strong></span></td>
                <td class="content3" nowrap align="center"><span class="content2"><strong>{POP_OUT_ALL}</strong></span></td>
                <td class="content3" nowrap align="center"><span class="content2"><strong>{OTHER_IN_ALL}</strong></span></td>
                <td class="content3" nowrap align="center"><span class="content2"><strong>{OTHER_OUT_ALL}</strong></span></td>
                <td class="content3" nowrap align="center"><span class="content2"><strong>{ALL_IN_ALL}</strong></span></td>
                <td class="content3" nowrap align="center"><span class="content2"><strong>{ALL_OUT_ALL}</strong></span></td>
                <td class="content3" nowrap align="center"><span class="content2"><strong>{ALL_ALL}</strong></span></td>
                <td class="content3" nowrap align="center"><b>{TR_ALL}</b></td>
              </tr>
            </table>
			</td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
        </table>
          </td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td height="71"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr><td width="17"><img src="{THEME_COLOR_PATH}/images/top/down_left.jpg" width="17" height="71"></td><td width="198" valign="top" background="{THEME_COLOR_PATH}/images/top/downlogo_background.jpg"><table width="100%" border="0" cellpadding="0" cellspacing="0" >
          <tr>
            <td width="55"><a href="http://www.vhcs.net" target="_blank"><img src="{THEME_COLOR_PATH}/images/vhcs.gif" alt="" width="51" height="71" border="0"></a></td>
            <td class="bottom">{VHCS_LICENSE}</td>
          </tr>
        </table>          </td>
          <td background="{THEME_COLOR_PATH}/images/top/down_left_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_left_fill.jpg" width="2" height="71"></td><td width="766" background="{THEME_COLOR_PATH}/images/top/middle_background.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_middle_background.jpg" width="766" height="71"></td>
          <td background="{THEME_COLOR_PATH}/images/top/down_right_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_right_fill.jpg" width="3" height="71"></td>
          <td width="9"><img src="{THEME_COLOR_PATH}/images/top/down_right.jpg" width="9" height="71"></td></tr>
    </table></td>
  </tr>
</table>
</body>
</html>
