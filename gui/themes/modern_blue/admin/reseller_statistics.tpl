<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_RESELLER_STATISTICS_PAGE_TITLE}</title>
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
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_stats.jpg" width="85" height="62" align="absmiddle">{TR_RESELLER_STATISTICS}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td valign="top">
			<!-- BDP: page_message -->
		  <table width="450" border="0" cellspacing="0" cellpadding="0">
               <tr>
                 <td><strong><font color="#FF0000">{MESSAGE}</font></strong></td>
               </tr>
           </table>
		   <!-- EDP: page_message -->
		   <!-- BDP: traffic_table --> 
           <form name="rs_frm" method="post" action="reseller_statistics.php?psi={POST_PREV_PSI}">
             
             <table width="100%">
              <tr>
                <td width="30" >&nbsp;</td>
                <td width="69" class="content">{TR_MONTH}</td>
                <td width="64" class="content"><select name="month">
                    <!-- BDP: month_list -->
                    <option {OPTION_SELECTED}>{MONTH_VALUE}</option>
                    <!-- EDP: month_list -->
                  </select>
                </td>
                <td width="65" class="content">{TR_YEAR}</td>
                <td width="72" class="content"><select name="year">
                    <!-- BDP: year_list -->
                    <option {OPTION_SELECTED}>{YEAR_VALUE}</option>
                    <!-- EDP: year_list -->
                  </select>
                </td>
                <td class="content"><input name="Submit" type="submit" class="button" value="{TR_SHOW}">
                </td>
              </tr>
            </table>
            <input type="hidden" name="uaction" value="show">
            </form>
            <br>
            <table width="100%" cellspacing="3">
              <tr align="center">
                <td width="20" nowrap>&nbsp;</td>
                <td class="content3" nowrap height="25"><b>{TR_RESELLER_NAME}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_TRAFF}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_DISK}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_DOMAIN}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_SUBDOMAIN}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_ALIAS}</b> </td>
                <td class="content3" nowrap height="25"><b>{TR_MAIL}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_FTP}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_SQL_DB}</b></td>
                <td class="content3" nowrap height="25"><b>{TR_SQL_USER}</b></td>
              </tr>
              <!-- BDP: reseller_entry -->
              <tr>
                <td nowrap align="center">&nbsp;</td>
                <td class="{ITEM_CLASS}" nowrap align="center"><b><a href="reseller_user_statistics.php?rid={RESELLER_ID}&name={RESELLER_NAME}&month={MONTH}&year={YEAR}" title="{RESELLER_NAME}" class="link"><b>{RESELLER_NAME}</a></b></td>
                <td class="{ITEM_CLASS}" nowrap align="center" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td width="13"><img src="{THEME_COLOR_PATH}/images/stats_left_small.gif" width="13" height="20"></td>
                      <td background="{THEME_COLOR_PATH}/images/stats_background.gif"><table border="0" cellspacing="0" cellpadding="0" align="left">
                          <tr>
                            <td width="7"><img src="{THEME_COLOR_PATH}/images/bars/stats_left.gif" width="7" height="13"></td>
                            <td background="{THEME_COLOR_PATH}/images/bars/stats_background.gif"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="{TRAFF_PERCENT}" height="1"></td>
                            <td width="7"><img src="{THEME_COLOR_PATH}/images/bars/stats_right.gif" width="7" height="13"></td>
                          </tr>
                      </table></td>
                      <td width="13"><img src="{THEME_COLOR_PATH}/images/stats_right_small.gif" width="13" height="20"></td>
                    </tr>
                  </table>
                    <b>{TRAFF_SHOW_PERCENT}&nbsp;%</b><br>
                    {TRAFF_USED}&nbsp;/&nbsp;{TRAFF_CURRENT}<br>
                    {TR_OF}<br>
                    <b>{TRAFF_MAX}</b></td>
                <td class="{ITEM_CLASS}" nowrap align="center" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td width="13"><img src="{THEME_COLOR_PATH}/images/stats_left_small.gif" width="13" height="20"></td>
                      <td background="{THEME_COLOR_PATH}/images/stats_background.gif"><table border="0" cellspacing="0" cellpadding="0" align="left">
                          <tr>
                            <td width="7"><img src="{THEME_COLOR_PATH}/images/bars/stats_left.gif" width="7" height="13"></td>
                            <td background="{THEME_COLOR_PATH}/images/bars/stats_background.gif"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="{DISK_PERCENT}" height="1"></td>
                            <td width="7"><img src="{THEME_COLOR_PATH}/images/bars/stats_right.gif" width="7" height="13"></td>
                          </tr>
                      </table></td>
                      <td width="13"><img src="{THEME_COLOR_PATH}/images/stats_right_small.gif" width="13" height="20"></td>
                    </tr>
                  </table>
                    <b>{DISK_SHOW_PERCENT}&nbsp;%</b><br>
                    {DISK_USED}&nbsp;/&nbsp;{DISK_CURRENT}<br>
                    {TR_OF}<br>
                    <b>{DISK_MAX}</b></td>
                <td class="{ITEM_CLASS}" nowrap align="center">{DMN_USED}&nbsp;/&nbsp;{DMN_CURRENT}<br>
                  {TR_OF}<br>
                  <b>{DMN_MAX}</b></td>
                <td class="{ITEM_CLASS}" nowrap align="center">{SUB_USED}&nbsp;/&nbsp;{SUB_CURRENT}<br>
                  {TR_OF}<br>
                  <b>{SUB_MAX}</b></td>
                <td class="{ITEM_CLASS}" nowrap align="center">{ALS_USED}&nbsp;/&nbsp;{ALS_CURRENT}<br>
                  {TR_OF}<br>
                  <b>{ALS_MAX}</b></td>
                <td class="{ITEM_CLASS}" nowrap align="center">{MAIL_USED}&nbsp;/&nbsp;{MAIL_CURRENT}<br>
                  {TR_OF}<br>
                  <b>{MAIL_MAX}</b></td>
                <td class="{ITEM_CLASS}" nowrap align="center">{FTP_USED}&nbsp;/&nbsp;{FTP_CURRENT}<br>
                  {TR_OF}<br>
                  <b>{FTP_MAX}</b></td>
                <td class="{ITEM_CLASS}" nowrap align="center">{SQL_DB_USED}&nbsp;/&nbsp;{SQL_DB_CURRENT}<br>
                  {TR_OF}<br>
                  <b>{SQL_DB_MAX}</b></td>
                <td class="{ITEM_CLASS}" nowrap align="center">{SQL_USER_USED}&nbsp;/&nbsp;{SQL_USER_CURRENT}<br>
                  {TR_OF}<br>
                  <b>{SQL_USER_MAX}</b></td>
              </tr>
              <!-- EDP: reseller_entry -->
            </table>
			<!-- EDP: traffic_table -->
            <table width="100%"  border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="30">&nbsp;</td>
    <td><div align="left"><br>
                <!-- BDP: scroll_prev_gray --><img src="{THEME_COLOR_PATH}/images/icons/flip/prev_gray.gif" width="20" height="20" border="0"><!-- EDP: scroll_prev_gray --><!-- BDP: scroll_prev --><a href="reseller_statistics.php?psi={PREV_PSI}&month={MONTH}&year={YEAR}"><img src="{THEME_COLOR_PATH}/images/icons/flip/prev.gif" width="20" height="20" border="0"></a><!-- EDP: scroll_prev --><!-- BDP: scroll_next_gray -->&nbsp;<img src="{THEME_COLOR_PATH}/images/icons/flip/next_gray.gif" width="20" height="20" border="0"><!-- EDP: scroll_next_gray --><!-- BDP: scroll_next -->&nbsp;<a href="reseller_statistics.php?psi={NEXT_PSI}&month={MONTH}&year={YEAR}"><img src="{THEME_COLOR_PATH}/images/icons/flip/next.gif" width="20" height="20" border="0"></a><!-- EDP: scroll_next -->
            </div></td>
    <td><div align="right"><br>
                <!-- BDP: scroll_prev_gray --><img src="{THEME_COLOR_PATH}/images/icons/flip/prev_gray.gif" width="20" height="20" border="0"><!-- EDP: scroll_prev_gray --><!-- BDP: scroll_prev --><a href="reseller_statistics.php?psi={PREV_PSI}&month={MONTH}&year={YEAR}"><img src="{THEME_COLOR_PATH}/images/icons/flip/prev.gif" width="20" height="20" border="0"></a><!-- EDP: scroll_prev --><!-- BDP: scroll_next_gray -->&nbsp;<img src="{THEME_COLOR_PATH}/images/icons/flip/next_gray.gif" width="20" height="20" border="0"><!-- EDP: scroll_next_gray --><!-- BDP: scroll_next -->&nbsp;<a href="reseller_statistics.php?psi={NEXT_PSI}&month={MONTH}&year={YEAR}"><img src="{THEME_COLOR_PATH}/images/icons/flip/next.gif" width="20" height="20" border="0"></a><!-- EDP: scroll_next -->
            </div></td>
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
