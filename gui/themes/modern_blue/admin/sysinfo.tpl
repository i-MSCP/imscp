<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_SYSTEM_INFO_PAGE_TITLE}</title>
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
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_tools.jpg" width="85" height="62" align="absmiddle">{TR_SYSTEM_INFO}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td>
			<!-- BDP: props_list -->
			<table width="100%" cellpadding="5" cellspacing="5">
              <tr>
                <td width="20">&nbsp;</td>
                <td width="200" class="content2">{TR_KERNEL}</td>
                <td class="content2">{KERNEL}</td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td width="200" class="content">{TR_UPTIME}</td>
                <td class="content">{UPTIME}</td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td width="200" class="content2">{TR_LOAD}</td>
                <td class="content2">{LOAD}</td>
              </tr>
            </table>
			<!-- EDP: props_list -->
			
			
			
			</td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
        </table>
          <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_tools.jpg" width="85" height="62" align="absmiddle">{TR_CPU_SYSTEM_INFO}</td>
              <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
            </tr>
            <tr>
              <td><table width="100%" cellpadding="5" cellspacing="5">
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="content2">{TR_CPU_MODEL}</td>
                  <td class="content2">{CPU_MODEL}</td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="content">{TR_CPU_MHZ}</td>
                  <td class="content">{CPU_MHZ}</td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="content2">{TR_CPU_CACHE}</td>
                  <td class="content2">{CPU_CACHE}</td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="content">{TR_CPU_BOGOMIPS}</td>
                  <td class="content">{CPU_BOGOMIPS}</td>
                </tr>
              </table></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
          </table>
          <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_tools.jpg" width="85" height="62" align="absmiddle">{TR_MEMRY_SYSTEM_INFO}</td>
              <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
            </tr>
            <tr>
              <td><table width="100%" cellpadding="5" cellspacing="5">
                <tr>
                  <td width="20">&nbsp;</td>
                  <td class="content3"><b>{TR_RAM}</b></td>
                  <td class="content3"><b>{TR_TOTAL}</b></td>
                  <td class="content3"><b>{TR_USED}</b></td>
                  <td class="content3"><b>{TR_FREE}</b></td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                  <td class="content">&nbsp;</td>
                  <td class="content">{RAM_TOTAL}</td>
                  <td class="content">{RAM_USED}</td>
                  <td class="content">{RAM_FREE}</td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                  <td class="content3"><b>{TR_SWAP}</b></td>
                  <td class="content3"><b>{TR_TOTAL}</b></td>
                  <td class="content3"><b>{TR_USED}</b></td>
                  <td class="content3"><b>{TR_FREE}</b></td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                  <td class="content">&nbsp;</td>
                  <td class="content">{SWAP_TOTAL}</td>
                  <td class="content">{SWAP_USED}</td>
                  <td class="content">{SWAP_FREE}</td>
                </tr>
              </table></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
          </table>
          <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_tools.jpg" width="85" height="62" align="absmiddle">{TR_FILE_SYSTEM_INFO}</td>
              <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
            </tr>
            <tr>
              <td><table width="100%" cellpadding="5" cellspacing="5">
                <!-- BDP: disk_list -->
                <tr>
                  <td width="20" nowrap >&nbsp;</td>
                  <td class="content3" nowrap><b>{TR_MOUNT}</b></td>
                  <td class="content3" nowrap><b>{TR_TYPE}</b></td>
                  <td class="content3" nowrap><b>{TR_PARTITION}</b></td>
                  <td align="center" nowrap class="content3"><b>{TR_PERCENT}</b></td>
                  <td align="right" nowrap class="content3"><b>{TR_FREE}</b></td>
                  <td align="right" nowrap class="content3"><b>{TR_USED}</b></td>
                  <td align="right" nowrap class="content3"><b>{TR_SIZE}</b></td>
                </tr>
                <!-- BDP: disk_list_item -->
                <tr>
                  <td nowrap>&nbsp;</td>
                  <td class="{ITEM_CLASS}" nowrap>{MOUNT}</td>
                  <td class="{ITEM_CLASS}" nowrap>{TYPE}</td>
                  <td class="{ITEM_CLASS}" nowrap>{PARTITION}</td>
                  <td class="{ITEM_CLASS}" nowrap align="center"> {PERCENT} </td>
                  <td class="{ITEM_CLASS}" nowrap align="right">{FREE}</td>
                  <td class="{ITEM_CLASS}" nowrap align="right">{USED}</td>
                  <td class="{ITEM_CLASS}" nowrap align="right">{SIZE}</td>
                </tr>
                <!-- EDP: disk_list_item -->
                <!-- EDP: disk_list -->
              </table></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
          </table></td>
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
