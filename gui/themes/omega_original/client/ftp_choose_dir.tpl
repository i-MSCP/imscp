<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<script>
function CopyText(inputname) {
	var buf1='';
	var buf2=document.forms[0].elements[inputname].value;
	var buf3=buf1+buf2;
	window.opener.document.forms[0].other_dir.value=buf3;
	window.opener.document.forms[0].use_other_dir.checked=true;
	self.close();
}

</script>
<title>{TR_CLIENT_WEBTOOLS_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<style type="text/css">
<!--
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
	background-color: #FFFFFF;
}
a.link {color:#000099;text-decoration:underline;font-weight: normal;}
a.link:link {color:#000099;text-decoration:underline;font-weight: normal;}
a.link:visited {color:#000099; text-decoration:underline; font-weight:normal}
a.link:hover {color:#000000; text-decoration: none;}
a.link:active {color:#000000;text-decoration:none;}

td {
	font-family: Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
}

td.content {
	font-size: 12px;
	color: #333333;
	white-space: nowrap;
	background: #efefef;
	white-space: nowrap;
	height: 22px;
	padding: 2px;
}

td.content2 {
	font-size: 12px;
	color: #333333;
	white-space: nowrap;
	background: #efefef;
	white-space: nowrap;
	height: 22px;
	padding: 2px;
}

td.content3 {
	font-size: 12px;
	color: #000000;
	white-space: nowrap;
	background: #efefef;
	white-space: nowrap;
	height: 30px;
	padding: 2px;
}
-->
</style></head>
<body text="#000000">
<table width="450" cellpadding="5" cellspacing="5">
              <!-- BDP: page_message -->
              <tr>
                <td width="100%" nowrap class="content3"><font color="#FF0000">{MESSAGE}</font></td>
              </tr>
              <!-- EDP: page_message -->
              <tr>
                <td  width="410" class="content3" nowrap colspan="4"><b>{TR_DIRECTORY_TREE}</b></td>
              </tr>
              <tr>
                <td width="40" class="content2" colspan="2" nowrap align="center"><b>&nbsp;</b></td>
                <td width="310" class="content2" nowrap><b>{TR_DIRS}</b></td>
                <td width="60" class="content2" nowrap align="center"><b>{TR__ACTION}</b></td>
              </tr><form>
              <!-- BDP: dir_item -->

              <tr>
			  	<!-- BDP: list_item -->
                <td  width="40" class="content" colspan="2" nowrap align="center"><img src="{THEME_COLOR_PATH}/images/icons/bc_{ICON}.png"></td>
                <td width="310" class="content" nowrap><a href="{LINK}" class="link">{DIR_NAME}</a></td>
                <!-- BDP: action_link -->
				<td width="60" class="content" nowrap align="center">
				<a href="javascript:CopyText('{CHOOSE_IT}');" class="link">{CHOOSE}</a>
				<input type="hidden" name="{CHOOSE_IT}" value="{CHOOSE_IT}">
				</td>
				<!-- EDP: action_link -->
				<!-- EDP: list_item -->
              </tr>

              <!-- EDP: dir_item -->
			  </form>
</table>
</body>
</html>