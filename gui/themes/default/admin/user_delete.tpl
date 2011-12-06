<!-- INCLUDE "../shared/layout/header.tpl" -->
		<div class="header">
			{MAIN_MENU}
			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>
		<div class="location">
			<div class="location-area">
				<h1 class="manage_users">{TR_MENU_MANAGE_USERS}</h1>
			</div>
			<ul class="location-menu">
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="manage_users.php">{TR_MENU_MANAGE_USERS}</a></li>
				<li><a href="user_delete.php?edit_id={DOMAIN_ID}">{TR_DELETE_DOMAIN} {DOMAIN_NAME}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="users"><span>{TR_DOMAIN_SUMMARY}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="admin_delete_domain_frm" method="post" action="user_delete.php">
				<table>
					<tr>
						<th colspan="2"><strong>{TR_DOMAIN_SUMMARY}</strong></th>
					</tr>

					<!-- BDP: mail_list -->
					<tr>
						<td colspan="2"><strong><i>{TR_DOMAIN_EMAILS}</i></strong>
						</td>
					</tr>
					<!-- BDP: mail_item -->
					<tr>
						<td style="width:300px">{MAIL_ADDR}</td>
						<td>{MAIL_TYPE}</td>
					</tr>
					<!-- EDP: mail_item -->
					<!-- EDP: mail_list -->

					<!-- BDP: ftp_list -->
					<tr>
						<td colspan="2"><strong><i>{TR_DOMAIN_FTPS}</i></strong></td>
					</tr>
					<!-- BDP: ftp_item -->
					<tr>
						<td>{FTP_USER}</td>
						<td>{FTP_HOME}</td>
					</tr>
					<!-- EDP: ftp_item -->
					<!-- EDP: ftp_list -->

					<!-- BDP: als_list -->
					<tr>
						<td colspan="2"><strong><i>{TR_DOMAIN_ALIASES}</i></strong>
						</td>
					</tr>
					<!-- BDP: als_item -->
					<tr>
						<td>{ALS_NAME}</td>
						<td>{ALS_MNT}</td>
					</tr>
					<!-- EDP: als_item -->
					<!-- EDP: als_list -->

					<!-- BDP: sub_list -->
					<tr>
						<td colspan="2"><strong><i>{TR_DOMAIN_SUBS}</i></strong></td>
					</tr>
					<!-- BDP: sub_item -->
					<tr>
						<td>{SUB_NAME}</td>
						<td>{SUB_MNT}</td>
					</tr>
					<!-- EDP: sub_item -->
					<!-- EDP: sub_list -->

					<!-- BDP: db_list -->
					<tr>
						<td colspan="2"><strong><i>{TR_DOMAIN_DBS}</i></strong></td>
					</tr>
					<!-- BDP: db_item -->
					<tr>
						<td>{DB_NAME}</td>
						<td>{DB_USERS}</td>
					</tr>
					<!-- EDP: db_item -->
					<!-- EDP: db_list -->
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="2">
							<p>{TR_REALLY_WANT_TO_DELETE_DOMAIN}</p>
							<input type="hidden" name="domain_id" value="{DOMAIN_ID}" />
							<input type="checkbox" value="1" name="delete" id="delete" style="vertical-align: middle" />
							<label for="delete">{TR_YES_DELETE_DOMAIN}</label>
						</td>
					</tr>
				</table>
				<div class="buttons">
					<input type="submit" value="{TR_BUTTON_DELETE}" />
				</div>
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
