<!-- INCLUDE "../shared/layout/header.tpl" -->
	<script type="text/javascript">
	/* <![CDATA[ */
	function action_delete(url, subject) {
		return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
	}
	/* ]]> */
	</script>
	<div class="header">
		{MAIN_MENU}
		<div class="logo">
			<img src="{ISP_LOGO}" alt="i-MSCP logo" />
		</div>
	</div>
	<div class="location">
		<div class="location-area">
			<h1 class="webtools">{TR_MENU_WEBTOOLS}</h1>
		</div>
		<ul class="location-menu">
			<!-- BDP: logged_from -->
			<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
			<!-- EDP: logged_from -->
			<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
		</ul>
		<ul class="path">
			<li><a href="webtools.php">{TR_MENU_WEBTOOLS}</a></li>
			<li><a href="#" onclick="return false;">{TR_LMENU_DAILY_BACKUP}</a></li>
		</ul>
	</div>

	<div class="left_menu">
		{MENU}
	</div>

	<div class="body">
		<h2 class="hdd"><span>{TR_BACKUP}</span></h2>

		<!-- BDP: page_message -->
		<div class="{MESSAGE_CLS}">{MESSAGE}</div>
		<!-- EDP: page_message -->

		<h2>{TR_DOWNLOAD_DIRECTION}</h2>
		<ol>
			<li>{TR_FTP_LOG_ON}</li>
			<li>{TR_SWITCH_TO_BACKUP}</li>
			<li>{TR_DOWNLOAD_FILE}<br />{TR_USUALY_NAMED}</li>
		</ol>
		<h2>{TR_RESTORE_BACKUP}</h2>
		<p>{TR_RESTORE_DIRECTIONS}</p>
		<form action="backup.php" method="post" id="backup_frm" onsubmit="return confirm('{TR_CONFIRM_MESSAGE}');">
			<div class="buttons">
				<input type="hidden" name="uaction" value="bk_restore" />
				<input type="submit" name="Submit" value="{TR_RESTORE}" />
			</div>
		</form>
	</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
