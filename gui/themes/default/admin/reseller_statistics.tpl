<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_RESELLER_STATISTICS_PAGE_TITLE}</title>
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

            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">

            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <h2 class="general"><span>{TR_RESELLER_STATISTICS}</span></h2>


			<form action="reseller_statistics.php?psi={POST_PREV_PSI}" method="post" name="rs_frm" id="rs_frm">
				{TR_MONTH}
				<select name="month" id="month">
					<!-- BDP: month_list -->
						<option{OPTION_SELECTED}>{MONTH_VALUE}</option>
					<!-- EDP: month_list -->
				</select>
				
				{TR_YEAR}
				<select name="year" id="year">
					<!-- BDP: year_list -->
						<option{OPTION_SELECTED}>{YEAR_VALUE}</option>
					<!-- EDP: year_list -->
				</select>
				<input name="Submit" type="submit" class="button" 	value="  {TR_SHOW}  " />
				<input type="hidden" name="uaction" value="show" />
			</form>
			<!-- BDP: traffic_table -->
			<table>
				<thead>
					<tr>
						<th>{TR_RESELLER_NAME}</th>
						<th>{TR_TRAFF}</th>
						<th>{TR_DISK}</th>
						<th>{TR_DOMAIN}</th>
						<th>{TR_SUBDOMAIN}</th>
						<th>{TR_ALIAS}</th>
						<th>{TR_MAIL}</th>
						<th>{TR_FTP}</th>
						<th>{TR_SQL_DB}</th>
						<th>{TR_SQL_USER}</th>
					</tr>
				</thead>
				<tbody>
					<!-- BDP: reseller_entry -->
						<tr>
							<td><a href="reseller_user_statistics.php?rid={RESELLER_ID}&amp;name={RESELLER_NAME}&amp;month={MONTH}&amp;year={YEAR}" title="{RESELLER_NAME}" class="icon i_domain_icon">{RESELLER_NAME}</a></td>
							<td><div class="graph"><span style="width: {TRAFF_PERCENT}%">&nbsp;</span><strong>{TRAFF_SHOW_PERCENT}&nbsp;%</strong></div>{TRAFF_MSG}</td>
							<td><div class="graph"><span style="width: {DISK_PERCENT}%">&nbsp;</span><strong>{DISK_SHOW_PERCENT}&nbsp;%</strong></div>{DISK_MSG}</td>
							<td>{DMN_MSG}</td>
							<td>{SUB_MSG}</td>
							<td>{ALS_MSG}</td>
							<td>{MAIL_MSG}</td>
							<td>{FTP_MSG}</td>
							<td>{SQL_DB_MSG}</td>
							<td>{SQL_USER_MSG}</td>
						</tr>
					<!-- EDP: reseller_entry -->
				</tbody>
			</table>
			
				<div class="paginator">
					<!-- BDP: scroll_next_gray -->
					<a class="icon i_next_gray" href="#" title="next">next</a>
					<!-- EDP: scroll_next_gray -->
					<!-- BDP: scroll_next -->
					<a class="icon i_next" href="reseller_statistics.php?psi={NEXT_PSI}&amp;month={MONTH}&amp;year={YEAR}" title="next">next</a>
					<!-- EDP: scroll_next -->
					<!-- BDP: scroll_prev_gray -->
					<a class="icon i_prev_gray" href="#" title="next">next</a>
					<!-- EDP: scroll_prev_gray -->
					<!-- BDP: scroll_prev -->
					<a class="icon i_prev" href="reseller_statistics.php?psi={PREV_PSI}&amp;month={MONTH}&amp;year={YEAR" title="previous">previous</a>
					<!-- EDP: scroll_prev -->
				</div>
			
			<!-- EDP: traffic_table -->
        </div>

        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>
