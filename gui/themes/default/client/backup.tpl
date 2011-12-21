
	<script type="text/javascript">
	/* <![CDATA[ */
	function action_delete(url, subject) {
		return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
	}
	/* ]]> */
	</script>
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

