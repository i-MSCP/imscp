<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_CLIENT_MANAGE_DOMAINS_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script language="JavaScript" type="text/JavaScript">
<!--
function action_delete(url) {
	if (!confirm("{TR_MESSAGE_DELETE}"))
		return false;

	location = url;
}

function MM_preloadImages() { //v3.0
	var d=document;
	if(d.images){
  	if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments;
		for(i=0; i<a.length; i++)
		    if (a[i].indexOf("#")!=0) {
				d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];
			}
	}
}

function MM_swapImgRestore() { //v3.0
	var i,x,a=document.MM_sr;
	for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++)
		x.src=x.oSrc;
}

function MM_findObj(n, d) { //v4.01
  	var p,i,x;
  	if(!d) d=document;
  		if((p=n.indexOf("?"))>0&&parent.frames.length) {
    		d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);
		}
  	if(!(x=d[n])&&d.all)
  		x=d.all[n];
 	for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  	for(i=0;!x&&d.layers&&i<d.layers.length;i++)
		x=MM_findObj(n,d.layers[i].document);
  	if(!x && d.getElementById)
		x=d.getElementById(n);
	return x;
}

function MM_swapImage() { //v3.0
  	var i,j=0,x,a=MM_swapImage.arguments;
  	document.MM_sr=new Array;
	for(i=0;i<(a.length-2);i+=3)
   	if ((x=MM_findObj(a[i]))!=null) {
		document.MM_sr[j++]=x;
		if(!x.oSrc)
			x.oSrc=x.src;
		x.src=a[i+2];
	}
}
//-->
</script>
</head>

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif','{THEME_COLOR_PATH}/images/icons/custom_link_a.gif')">
<!-- BDP: logged_from --><table width="100%"  border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap background="{THEME_COLOR_PATH}/images/button.gif">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="18" height="18" border="0" align="absmiddle"></a> <font color="red">{YOU_ARE_LOGGED_AS}</font> </td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%" style="border-collapse: collapse;padding:0;margin:0;">
	<tr>
		<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" border="0"></td>
		<td style="height: 56px; width: 617px;"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0"></td>
		<td style="width:100%; background-image: url({THEME_COLOR_PATH}/images/top/top_bg.jpg)">&nbsp;</td>
		<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" border="0"></td>
	</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan=3 style="vertical-align: top;"><table style="width: 100%; border-collapse: collapse;padding:0;margin:0;">
          <tr height="95";>
            <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
            <td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0"></td>
          </tr>
          <tr height="*">
            <td colspan=3>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_domains.png" width="25" height="25"></td>
		<td colspan="2" class="title">{TR_DOMAIN_ALIASES}</td>
	</tr>
</table>
	</td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td valign="top"><table width="100%"  border="00" cellspacing="0" cellpadding="0">
      <tr>
        <td width="40">&nbsp;</td>
        <td valign="top"><table width="100%" cellpadding="5" cellspacing="5">
          <!-- BDP: als_message -->
          <tr>
            <td class="title" nowrap colspan="5"><font color="#FF0000">{ALS_MSG}</font></td>
          </tr>
          <!-- EDP: als_message -->
          <!-- BDP: als_list -->
          <tr>
            <td class="content3" nowrap><b>{TR_ALS_NAME}</b></td>
            <td class="content3" nowrap><b>{TR_ALS_MOUNT}</b></td>
            <td class="content3" nowrap align="center"><b>{TR_ALS_FORWARD}</b></td>
            <td class="content3" nowrap align="center"><b>{TR_ALS_STATUS}</b></td>
            <td class="content3" nowrap align="center"><b>{TR_ALS_ACTION}</b></td>
          </tr>
          <!-- BDP: als_item -->
          <tr>
            <td class="{ITEM_CLASS}" nowrap><img src="{THEME_COLOR_PATH}/images/icons/domain_icon.png" width="15" height="14" align="left"> {ALS_NAME}</td>
            <td class="{ITEM_CLASS}" nowrap>{ALS_MOUNT}</td>
            <td width="100" class="{ITEM_CLASS}" nowrap align="center"><a href="{ALS_CHANGE_SCRIPT}" class="link" title="{ALS_EDIT}">{ALS_FORWARD}</a></td>
            <td width="100" class="{ITEM_CLASS}" nowrap align="center">{ALS_STATUS}</td>
            <td width="100" class="{ITEM_CLASS}" nowrap align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" align="absmiddle"> <a href="#" class=link onClick="action_delete('{ALS_ACTION_SCRIPT}')" title="{ALS_ACTION}">{ALS_ACTION}</a> - <img src="{THEME_COLOR_PATH}/images/icons/edit.png" width="16" height="16" border="0" align="absmiddle"> <a href="{ALS_FWD_SCRIPT}" class="link" title="{ALS_CHANGE}">{ALS_CHANGE}</a></td>
          </tr>
          <!-- EDP: als_item -->
          <!-- EDP: als_list -->
        </table></td>
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
		<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_doc.png" width="25" height="25"></td>
		<td colspan="2" class="title">{TR_SUBDOMAINS}</td>
	</tr>
</table>
	</td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td valign="top"><table width="100%"  border="00" cellspacing="0" cellpadding="0">
      <tr>
        <td width="40">&nbsp;</td>
        <td valign="top"><table width="100%" cellspacing="7">
          <!-- BDP: sub_message -->
          <tr>
            <td class="title" nowrap colspan="5"><font color="#FF0000">{SUB_MSG}</font></td>
          </tr>
          <!-- EDP: sub_message -->
          <!-- BDP: sub_list -->
          <tr>
            <td class="content3" colspan="2" nowrap><b>{TR_SUB_NAME}</b></td>
            <td class="content3" nowrap><b>{TR_SUB_MOUNT}</b></td>
            <td width="100" class="content3" nowrap align="center"><b>{TR_SUB_STATUS}</b></td>
            <td width="100" class="content3" nowrap align="center"><b>{TR_SUB_ACTION}</b></td>
          </tr>
          <!-- BDP: sub_item -->
          <tr>
            <td class="{ITEM_CLASS}" colspan="2" nowrap><img src="{THEME_COLOR_PATH}/images/icons/domain_icon.png" width="15" height="14" align="left">{SUB_NAME}</td>
            <td class="{ITEM_CLASS}" nowrap>{SUB_MOUNT}</td>
            <td width="100" class="{ITEM_CLASS}" nowrap align="center">{SUB_STATUS}</td>
            <td width="100" class="{ITEM_CLASS}" nowrap align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" align="absmiddle"> <a href="#" class=link onClick="action_delete('{SUB_ACTION_SCRIPT}')">{SUB_ACTION}</a></td>
          </tr>
          <!-- EDP: sub_item -->
          <!-- EDP: sub_list -->
        </table></td>
      </tr>
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
