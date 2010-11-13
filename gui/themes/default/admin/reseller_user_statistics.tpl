<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_ADMIN_USER_STATISTICS_PAGE_TITLE}</title>
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
				<h1 class="statistics">{TR_MENU_STATISTICS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
            	<li><a href="server_statistic.php">{TR_MENU_STATISTICS}</a></li>
                <li><a href="reseller_statistic.php">{TR_RESELLER_STATISTICS}</a></li>
                <li>{TR_RESELLER_USER_STATISTICS}</li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="stats"><span>{TR_RESELLER_USER_STATISTICS}</span></h2>

			<!-- BDP: props_list -->
			<form name="rs_frm" method="post" action="reseller_user_statistics.php?psi={POST_PREV_PSI}">
				<label for="month">{TR_MONTH}</label>
				<select id="month" name="month">
					<!-- BDP: month_list -->
						 <option {OPTION_SELECTED}>{MONTH_VALUE}</option>
					<!-- EDP: month_list -->
				</select>

				<label for="year">{TR_YEAR}</label>
				<select id="year" name="year">
					<!-- BDP: year_list -->
						 <option {OPTION_SELECTED}>{YEAR_VALUE}</option>
					<!-- EDP: year_list -->
				</select>

				<input name="Submit" type="submit" class="button" value="{TR_SHOW}" />

				<input type="hidden" name="uaction" value="show" />
				<input type="hidden" name="name" value="{VALUE_NAME}" />
				<input type="hidden" name="rid" value="{VALUE_RID}" />
			</form>

			<!-- BDP: no_domains -->
				<div class="info">{TR_NO_DOMAINS}</div>
			<!-- EDP: no_domains -->

			<!-- BDP: domain_list -->
				<table>
					<thead>
						<tr>
							 <th>{TR_DOMAIN_NAME}</th>
							 <th>{TR_TRAFF}</th>
							 <th>{TR_DISK}</th>
							 <th>{TR_WEB}</th>
							 <th>{TR_FTP_TRAFF}</th>
							 <th>{TR_SMTP}</th>
							 <th>{TR_POP3}</th>
							 <th>{TR_SUBDOMAIN}</th>
							 <th>{TR_ALIAS}</th>
							 <th>{TR_MAIL}</th>
							 <th>{TR_FTP}</th>
							 <th>{TR_SQL_DB}</th>
							 <th>{TR_SQL_USER}</th>
						</tr>
					</thead>
					<tbody>
						<!-- BDP: domain_entry -->
							<tr>
								<td><a href="domain_statistics.php?month={MONTH}&amp;year={YEAR}&amp;domain_id={DOMAIN_ID}" class="icon i_domain_icon">{DOMAIN_NAME}</a></td>
								<td><div class="graph"><span style="width: {TRAFF_PERCENT}%">&nbsp;</span><strong>{TRAFF_SHOW_PERCENT}&nbsp;%</strong></div>{TRAFF_MSG}</td>
								<td><div class="graph"><span style="width: {DISK_PERCENT}%">&nbsp;</span><strong>{DISK_SHOW_PERCENT}&nbsp;%</strong></div>{DISK_MSG}</td>
								<td>{WEB}</td>
								<td>{FTP}</td>
								<td>{SMTP}</td>
								<td>{POP3}</td>
								<td>{SUB_MSG}</td>
								<td>{ALS_MSG}</td>
								<td>{MAIL_MSG}</td>
								<td>{FTP_MSG}</td>
								<td>{SQL_DB_MSG}</td>
								<td>{SQL_USER_MSG}</td>
							</tr>
						<!-- EDP: domain_entry -->
					</tbody>
				</table>

				<div class="paginator">
					<!-- BDP: scroll_next_gray -->
					<a class="icon i_next_gray" href="#" title="next">next</a>
					<!-- EDP: scroll_next_gray -->
					<!-- BDP: scroll_next -->
					<a class="icon i_next" href="reseller_user_statistics.php?psi={NEXT_PSI}&amp;month={MONTH}&amp;year={YEAR}" title="next">next</a>
					<!-- EDP: scroll_next -->
					<!-- BDP: scroll_prev_gray -->
					<a class="icon i_prev_gray" href="#" title="next">next</a>
					<!-- EDP: scroll_prev_gray -->
					<!-- BDP: scroll_prev -->
					<a class="icon i_prev" href="reseller_user_statistics.php?psi={PREV_PSI}&amp;month={MONTH}&amp;year={YEAR}" title="previous">previous</a>
					<!-- EDP: scroll_prev -->
				</div>

			<!-- EDP: domain_list -->
			<!-- EDP: props_list -->

		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
