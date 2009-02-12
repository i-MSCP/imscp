<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>{TR_CLIENT_WEBTOOLS_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex">
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript">
<!--
function CopyText(inputname) {
	var buf1 = '';
	var buf2 = document.forms[0].elements[inputname].value;
	var buf3 = buf1 + buf2;
	window.opener.document.forms[0].other_dir.value = buf3;
	window.opener.document.forms[0].use_other_dir.checked = true;
	self.close();
}
//-->
</script>
</head>

<body>
<form><table width="450" cellpadding="5" cellspacing="5">
              <!-- BDP: page_message -->
              <tr>
                <td width="100%" nowrap="nowrap" class="content3"><span class="message">{MESSAGE}</span></td>
              </tr>
              <!-- EDP: page_message -->
              <tr>
                <td width="410" class="content3" nowrap="nowrap" colspan="4"><b>{TR_DIRECTORY_TREE}</b></td>
              </tr>
              <tr>
                <td width="40" class="content2" colspan="2" nowrap="nowrap" align="center"><b>&nbsp;</b></td>
                <td width="310" class="content2" nowrap="nowrap"><b>{TR_DIRS}</b></td>
                <td width="60" class="content2" nowrap="nowrap" align="center"><b>{TR__ACTION}</b></td>
              </tr>
              <!-- BDP: dir_item -->
              <tr>
			  	<!-- BDP: list_item -->
                <td  width="40" class="content" colspan="2" nowrap="nowrap" align="center"><img src="{THEME_COLOR_PATH}/images/icons/bc_{ICON}.png"></td>
                <td width="310" class="content" nowrap="nowrap"><a href="{LINK}" class="link">{DIR_NAME}</a></td>
                <!-- BDP: action_link -->
				<td width="60" class="content" nowrap="nowrap" align="center">
				<a href="javascript:CopyText('{CHOOSE_IT}');" class="link">{CHOOSE}</a>
				<input type="hidden" name="{CHOOSE_IT}" value="{CHOOSE_IT}"></td>
				<!-- EDP: action_link -->
				<!-- EDP: list_item -->
              </tr>
              <!-- EDP: dir_item -->
</table></form>
</body>
</html>
