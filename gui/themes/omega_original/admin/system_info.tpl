<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_ADMIN_SYSTEM_INFO_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0 auto;">
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" width="195" height="56" border="0" alt="ispCP Logogram" /></td>
<td style="height: 56px; width:100%; background-color: #0f0f0f"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" width="582" height="56" border="0" alt="" /></td>
<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" width="73" height="56" border="0" alt="" /></td>
</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan="2" style="vertical-align: top;"><table style="width: 100%; padding:0;margin:0;" cellspacing="0">
				<tr style="height:95px;">
				  <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
					<td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" width="73" height="95" border="0" alt="" /></td>
				</tr>
				<tr>
				  <td colspan="3">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="20"><img src="{THEME_COLOR_PATH}/images/icons/system_vital.png" width="25" height="25" alt="" /></td>
		<td colspan="2" class="title">{TR_SYSTEM_INFO}</td>
	</tr>
</table>
	</td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td><!-- BDP: props_list -->
        <table width="100%" cellpadding="5" cellspacing="5">
          <tr>
            <td width="25">&nbsp;</td>
            <td width="200" class="content2">{TR_KERNEL}</td>
            <td class="content2">{KERNEL}</td>
          </tr>
          <tr>
            <td width="25">&nbsp;</td>
            <td width="200" class="content">{TR_UPTIME}</td>
            <td class="content">{UPTIME}</td>
          </tr>
          <tr>
            <td width="25">&nbsp;</td>
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
    <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="20"><img src="{THEME_COLOR_PATH}/images/icons/system_cpu.png" width="25" height="25" alt="" /></td>
		<td colspan="2" class="title">{TR_CPU_SYSTEM_INFO}</td>
	</tr>
</table>
	</td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td><table width="100%" cellpadding="5" cellspacing="5">
      <tr>
        <td width="25">&nbsp;</td>
        <td width="200" class="content2">{TR_CPU_MODEL}</td>
        <td class="content2">{CPU_MODEL}</td>
      </tr>
      <tr>
        <td width="25">&nbsp;</td>
        <td width="200" class="content">{TR_CPU_COUNT}</td>
        <td class="content2">{CPU_COUNT}</td>
      </tr>
      <tr>
        <td width="25">&nbsp;</td>
        <td width="200" class="content2">{TR_CPU_MHZ}</td>
        <td class="content">{CPU_MHZ}</td>
      </tr>
      <tr>
        <td width="25">&nbsp;</td>
        <td width="200" class="content">{TR_CPU_CACHE}</td>
        <td class="content2">{CPU_CACHE}</td>
      </tr>
      <tr>
        <td width="25">&nbsp;</td>
        <td width="200" class="content2">{TR_CPU_BOGOMIPS}</td>
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
    <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="20"><img src="{THEME_COLOR_PATH}/images/icons/system_memory.png" width="25" height="25" alt="" /></td>
		<td colspan="2" class="title">{TR_MEMRY_SYSTEM_INFO}</td>
	</tr>
</table>
	</td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td><table width="100%" cellpadding="5" cellspacing="5">
      <tr>
        <td width="25">&nbsp;</td>
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
    <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="20"><img src="{THEME_COLOR_PATH}/images/icons/system_filesystem.png" width="25" height="25" alt="" /></td>
		<td colspan="2" class="title">{TR_FILE_SYSTEM_INFO}</td>
	</tr>
</table>
	</td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td><table width="100%" cellpadding="5" cellspacing="5">
      <!-- BDP: disk_list -->
      <tr>
        <td width="25" nowrap="nowrap">&nbsp;</td>
        <td class="content3" nowrap="nowrap"><b>{TR_MOUNT}</b></td>
        <td class="content3" nowrap="nowrap"><b>{TR_TYPE}</b></td>
        <td class="content3" nowrap="nowrap"><b>{TR_PARTITION}</b></td>
        <td nowrap="nowrap" class="content3"><b>{TR_PERCENT}</b></td>
        <td nowrap="nowrap" class="content3"><b>{TR_FREE}</b></td>
        <td nowrap="nowrap" class="content3"><b>{TR_USED}</b></td>
        <td nowrap="nowrap" class="content3"><b>{TR_SIZE}</b></td>
      </tr>
      <!-- BDP: disk_list_item -->
      <tr>
        <td nowrap="nowrap">&nbsp;</td>
        <td nowrap="nowrap" class="content">{MOUNT}</td>
        <td nowrap="nowrap" class="content">{TYPE}</td>
        <td nowrap="nowrap" class="content">{PARTITION}</td>
        <td nowrap="nowrap" align="center" class="content">{PERCENT}</td>
        <td nowrap="nowrap" align="right" class="content">{FREE}</td>
        <td nowrap="nowrap" align="right" class="content">{USED}</td>
        <td nowrap="nowrap" align="right" class="content">{SIZE}</td>
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
</table>
				  </td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</body>
</html>
