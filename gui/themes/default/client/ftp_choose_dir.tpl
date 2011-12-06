<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<title>{TR_CLIENT_WEBTOOLS_PAGE_TITLE}</title>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta http-equiv="Content-Script-Type" content="text/javascript" />
		<meta name="robots" content="nofollow, noindex" />
		<link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />

		<style type="text/css">
			/* <![CDATA[ */
				body,html {
					min-width:0%;
					background:transparent;
				}

				.bodyOverride {
					margin:0 10px 0 10px;
				}
			 /* ]]> */
		</style>
		<!--[if IE 6]>
		<script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
		<script type="text/javascript">
			DD_belatedPNG.fix('*');
		</script>
		<![endif]-->
		<script type="text/javascript">
			/* <![CDATA[ */
				function CopyText(inputname) {
				window.opener.document.forms[0].other_dir.value = document.forms[0].elements[inputname].value;
				window.opener.document.forms[0].use_other_dir.checked = true;
				self.close();
				}
			 /* ]]> */
		</script>
	</head>
	<body>
		<div class="body bodyOverride">
            <h2><span>{TR_DIRECTORY_TREE}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<!-- BDP: ftp_chooser -->
			<form action="">
				<table>
					<tr>
						<th>{TR_DIRS}</th>
						<th>{TR_ACTION}</th>
					</tr>
					<!-- BDP: dir_item -->
					<tr>
						<!-- BDP: list_item -->
						<td width="200"><a href="{LINK}" class="icon i_bc_{ICON}">{DIR_NAME}</a></td>
						<td>
							<!-- BDP: action_link -->
							<a href="#" onclick="CopyText('{CHOOSE_IT}');return false;">{CHOOSE}</a>
							<input type="hidden" name="{CHOOSE_IT}" value="{CHOOSE_IT}" />
							<!-- EDP: action_link -->
							&nbsp;
						</td>
						<!-- EDP: list_item -->
					</tr>
					<!-- EDP: dir_item -->
				</table>
			</form>
			<!-- EDP: ftp_chooser -->
		</div>
	</body>
</html>
