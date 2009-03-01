<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>{TR_CLIENT_WEBTOOLS_PAGE_TITLE}</title>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta http-equiv="Content-Script-Type" content="text/javascript" />
		<meta name="robots" content="nofollow, noindex" />
		<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
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
		<form action="">
			<table width="450" cellpadding="5" cellspacing="5" style="empty-cells:show">
				<!-- BDP: page_message -->
				<tr>
					<td width="100%" nowrap="nowrap" class="content3"><span class="message">{MESSAGE}</span></td>
				</tr>
				<!-- EDP: page_message -->
				<tr>
					<td width="410" class="content3" nowrap="nowrap" colspan="4"><strong>{TR_DIRECTORY_TREE}</strong></td>
				</tr>
				<tr>
					<td width="40" class="content2" colspan="2"></td>
					<td width="310" class="content2" nowrap="nowrap"><strong>{TR_DIRS}</strong></td>
					<td width="60" class="content2" nowrap="nowrap" align="center"><strong>{TR__ACTION}</strong></td>
				</tr>
				<!-- BDP: dir_item -->
				<tr>
					<!-- BDP: list_item -->
					<td width="40" class="content" colspan="2" align="center"><img src="{THEME_COLOR_PATH}/images/icons/bc_{ICON}.png" alt="" /></td>
					<td width="310" class="content" nowrap="nowrap"><a href="{LINK}" class="link">{DIR_NAME}</a></td>
					<!-- BDP: action_link -->
					<td width="60" class="content" nowrap="nowrap" align="center">
						<a href="javascript:CopyText('{CHOOSE_IT}');" class="link">{CHOOSE}</a>
					<input type="hidden" name="{CHOOSE_IT}" value="{CHOOSE_IT}" /></td>
					<!-- EDP: action_link -->
					<!-- EDP: list_item -->
				</tr>
				<!-- EDP: dir_item -->
			</table>
		</form>
	</body>
</html>
