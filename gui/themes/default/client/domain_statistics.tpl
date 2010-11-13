<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_CLIENT_DOMAIN_STATISTICS_PAGE_TITLE}</title>
		<meta name="robots" content="nofollow, noindex" />
		<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
		<!--[if IE 6]>
		<script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
		<script type="text/javascript">
			DD_belatedPNG.fix('*');
		</script>
		<![endif]-->
	</head>

	<body>
		<div class="header">
			{MAIN_MENU}

			<div class="logo">
				<img src="{THEME_COLOR_PATH}/images/ispcp_logo.png" alt="IspCP logo" />
				<img src="{THEME_COLOR_PATH}/images/ispcp_webhosting.png" alt="IspCP omega" />
			</div>
		</div>

		<div class="location">
			<div class="location-area icons-left">
				<h1 class="statistics">{TR_MENU_DOMAIN_STATISTICS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="domain_statistics.php">{TR_MENU_DOMAIN_STATISTICS}</a></li>
				<li><a href="domain_statistics.php">{TR_MENU_OVERVIEW}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="stats"><span>{TR_DOMAIN_STATISTICS}</span></h2>

			<form name="domain_statistics_frm" method="post" action="domain_statistics.php">
				<label for="month">{TR_MONTH}</label>
				<select id="month" name="month">
					<!-- BDP: month_item -->
						 <option {MONTH_SELECTED}>{MONTH}</option>
					<!-- EDP: month_item -->
				</select>

				<label for="year">{TR_YEAR}</label>
				<select id="year" name="year">
					<!-- BDP: year_item -->
						 <option {YEAR_SELECTED}>{YEAR}</option>
					<!-- EDP: year_item -->
				</select>

				<input name="Submit" type="submit" class="button" value="{TR_SHOW}" />
				<input name="uaction" type="hidden" value="show_traff" />
			</form>

			<table width="100%" cellspacing="3">
				<thead>
					<tr>
						<th>{TR_DATE}</th>
						<th>{TR_WEB_TRAFF}</th>
						<th>{TR_FTP_TRAFF}</th>
						<th>{TR_SMTP_TRAFF}</th>
						<th>{TR_POP_TRAFF}</th>
						<th>{TR_SUM}</th>
					</tr>
				</thead>
				<!-- BDP: traff_list -->
					<tfoot>
						<tr>
							<td>{TR_ALL}</td>
							<td>{WEB_ALL}</td>
							<td>{FTP_ALL}</td>
							<td>{SMTP_ALL}</td>
							<td>{POP_ALL}</td>
							<td>{SUM_ALL}</td>
						</tr>
					</tfoot>
					<tbody>
						<!-- BDP: traff_item -->
							<tr>
								<td>{DATE}</td>
								<td>{WEB_TRAFF}</td>
								<td>{FTP_TRAFF}</td>
								<td>{SMTP_TRAFF}</td>
								<td>{POP_TRAFF}</td>
								<td>{SUM_TRAFF}</td>
							</tr>
						<!-- EDP: traff_item -->
					</tbody>
				<!-- EDP: traff_list -->
			</table>
		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
