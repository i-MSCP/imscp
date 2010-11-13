<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>{TR_CLIENT_WEBTOOLS_PAGE_TITLE}</title>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta http-equiv="Content-Script-Type" content="text/javascript" />
		<meta name="robots" content="nofollow, noindex" />
		<link href="{THEME_COLOR_PATH}/css/simple-ispcp.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript">
			<!--
			function CopyText(inputname) {
				window.opener.document.forms[0].other_dir.value = document.forms[0].elements[inputname].value;
				window.opener.document.forms[0].use_other_dir.checked = true;
				self.close();
			}
			//-->
		</script>
	</head>

	<body class="body">
		<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
		<!-- EDP: page_message -->
		<h2><span>{TR_DIRECTORY_TREE}</span></h2>

		<form action="">
			<table style="empty-cells:show">
				<thead>
					<tr>
						<th>{TR_DIRS}</th>
						<th>{TR__ACTION}</th>
					</tr>
				</thead>
				<tbody>
				<!-- BDP: dir_item -->
					<tr>
						<!-- BDP: list_item -->
							<td><a href="{LINK}" class="icon i_bc_{ICON}">{DIR_NAME}</a></td>
							<!-- BDP: action_link -->
								<td><a href="javascript:CopyText('{CHOOSE_IT}');" class="icon i_choose">{CHOOSE}</a><input type="hidden" name="{CHOOSE_IT}" value="{CHOOSE_IT}" /></td>
							<!-- EDP: action_link -->
						<!-- EDP: list_item -->
					</tr>
				<!-- EDP: dir_item -->

				</tbody>
			</table>
		</form>
	</body>
</html>
