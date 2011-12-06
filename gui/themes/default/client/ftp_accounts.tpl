<!-- INCLUDE "../shared/layout/header.tpl" -->
		<script type="text/javascript">
			/*<![CDATA[*/
			function action_delete(url, subject) {
				if (!confirm(sprintf("{TR_MESSAGE_DELETE}", subject))) {
					return false;
				}

				location = url;
			}
			/*]]>*/
		</script>
		<div class="header">
			{MAIN_MENU}
			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>

		<div class="location">
			<div class="location-area">
				<h1 class="ftp">{TR_MENU_FTP_ACCOUNTS}</h1>
			</div>
			<ul class="location-menu">
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="ftp_accounts.php">{TR_MENU_FTP_ACCOUNTS}</a></li>
				<li><a href="#" onclick="return false;">{TR_LMENU_OVERVIEW}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="ftp"><span>{TR_FTP_USERS}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<!-- BDP: ftp_accounts -->
			<table>
				<thead>
					<tr>
						<th>{TR_FTP_ACCOUNT}</th>
						<th>{TR_FTP_ACTION}</th>
					</tr>
				</thead>
				<tbody>
					<!-- BDP: ftp_item -->
					<tr>
						<td>{FTP_ACCOUNT}</td>
						<td>
							<a href="ftp_auth.php?id={UID}" target="{FILEMANAFER_TARGET}" class="icon i_filemanager">{TR_LOGINAS}</a>
							<a href="ftp_edit.php?id={UID}" class="icon i_edit">{TR_EDIT}</a>
							<a href="#" class="icon i_delete" onclick="action_delete('ftp_delete.php?id={UID}', '{FTP_ACCOUNT}'); return false;">{TR_DELETE}</a>
						</td>
					</tr>
					<!-- EDP: ftp_item -->
				</tbody>
				<!-- BDP: ftps_total -->
				<tfoot>
					<tr>
						<td colspan="2">{TR_TOTAL_FTP_ACCOUNTS}
							&nbsp;<strong>{TOTAL_FTP_ACCOUNTS}</strong></td>
					</tr>
				</tfoot>
				<!-- EDP: ftps_total -->
			</table>
			<!-- EDP: ftp_accounts -->
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
