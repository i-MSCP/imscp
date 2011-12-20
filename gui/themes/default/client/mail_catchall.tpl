
		<script type="text/javascript">
		/* <![CDATA[ */
		function action_delete(url, mailacc) {
			if (url.indexOf("delete")==-1) {
				location = url;
			} else {
				if (!confirm(sprintf("{TR_MESSAGE_DELETE}", mailacc)))
					return false;
				location = url;
			}
		}
		/* ]]> */
		</script>
		<div class="body">
			<h2 class="email"><span>{TR_TITLE_CATCHALL_MAIL_USERS}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<!-- BDP: catchall_message -->
			<div class="info">{CATCHALL_MSG}</div>
			<!-- EDP: catchall_message -->

			<table>
				<thead>
					<tr>
						<th>{TR_DOMAIN}</th>
						<th>{TR_CATCHALL}</th>
						<th>{TR_STATUS}</th>
						<th>{TR_ACTION}</th>
					</tr>
				</thead>
				<tbody>
					<!-- BDP: catchall_item -->
						<tr>
							<td>{CATCHALL_DOMAIN}</td>
							<td>{CATCHALL_ACC}</td>
							<td>{CATCHALL_STATUS}</td>
							<td>
								<a href="#" class="icon i_users<!-- BDP: del_icon --> i_delete<!-- EDP: del_icon -->" onclick="action_delete('{CATCHALL_ACTION_SCRIPT}', '{CATCHALL_ACC}')">{CATCHALL_ACTION}</a>
							</td>
						</tr>
					<!-- EDP: catchall_item -->
				</tbody>
			</table>
		</div>
