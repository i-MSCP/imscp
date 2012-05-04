
	<script type="text/javascript">
	/* <![CDATA[ */
        <!-- BDP: mark_all_mails_to_delete_jquery -->
        $(document).ready(function() {
            $("input[name='checkAll']").click(function() {
                var checked = $(this).attr("checked") ? true : false;
                $("#delete_marked_mails input:checkbox").attr("checked", checked);
                var count_checked = $('#delete_marked_mails input[name="del_item[]"]:checked').length;
                if(count_checked > 0) {
                    $("#delete_marked_mails input[type=submit]").attr("disabled", false);
                } else {
                    $("#delete_marked_mails input[type=submit]").attr("disabled", true);
                }
            });
            $('#delete_marked_mails input[name="del_item[]"]').click(function() {
                var count_checked = $('#delete_marked_mails input[name="del_item[]"]:checked').length;
                if(count_checked > 0) {
                    $("#delete_marked_mails input[type=submit]").attr("disabled", false);
                } else {
                    $("#delete_marked_mails input[type=submit]").attr("disabled", true);
                }
            });
            $("#delete_marked_mails input[type=submit]").attr("disabled", true);
        });
        function action_delete_marked() {
            if (!confirm(sprintf("{TR_MESSAGE_DELETE_MARKED}")))
    				return false;
    	}
        <!-- EDP: mark_all_mails_to_delete_jquery -->
		function action_delete(url, subject) {
			if (!confirm(sprintf("{TR_MESSAGE_DELETE}", subject)))
				return false;
			location = url;
		}
	/* ]]> */
	</script>
		<!-- BDP: mail_message -->
		<div class="info">{MAIL_MSG}</div>
		<!-- EDP: mail_message -->

        <!-- BDP: delete_marked_mails_form_head -->
        <form action="mail_delete.php" method="post" id="delete_marked_mails">
        <!-- EDP: delete_marked_mails_form_head -->
		<table>
			<thead>
				<tr>
					<th>{TR_MAIL}</th>
					<th>{TR_TYPE}</th>
					<th>{TR_STATUS}</th>
					<th>{TR_QUOTA_USE}</th>
					<th>{TR_ACTION}</th>
                    <th>{TR_DEL_ITEM}  <!-- BDP: mark_all_mails_to_delete --><input type="checkbox" id="checkAll" name="checkAll" /><!-- EDP: mark_all_mails_to_delete --></th>
				</tr>
			</thead>
			<tbody>
				<!-- BDP: mail_item -->
				<tr>
					<td style="width: 300px;">
						<span class="icon i_mail_icon">{MAIL_ACC}</span>
						<!-- BDP: auto_respond -->
						<div style="display: {AUTO_RESPOND_VIS};">
							<br />
					  		{TR_AUTORESPOND}:
					  		[
					  			<a href="{AUTO_RESPOND_DISABLE_SCRIPT}" class="icon i_reload">{AUTO_RESPOND_DISABLE}</a>
					  			<a href="{AUTO_RESPOND_EDIT_SCRIPT}" class="icon i_edit">{AUTO_RESPOND_EDIT}</a>
					  		]
						</div>
						<!-- EDP: auto_respond -->
					</td>
					<td>{MAIL_TYPE}</td>
					<td>{MAIL_STATUS}</td>
					<td>{MAIL_QUOTA_VALUE}</td>
					<td>
						<a href="{MAIL_EDIT_SCRIPT}" title="{MAIL_EDIT}" class="icon i_edit">{MAIL_EDIT}</a>
						<a href="#" onclick="action_delete('{MAIL_DELETE_SCRIPT}', '{MAIL_ACC}')" title="{MAIL_DELETE}" class="icon i_delete">{MAIL_DELETE}</a>
					</td>
                    <td style="width: 10px;"><input type="checkbox" name="del_item[]" value="{DEL_ITEM}" {DISABLED_DEL_ITEM}/></td>
				</tr>
				<!-- EDP: mail_item -->
			</tbody>
			<!-- BDP: mails_total -->
			<tfoot>
				<tr>
					<td colspan="5">{TR_TOTAL_MAIL_ACCOUNTS}: <strong>{TOTAL_MAIL_ACCOUNTS}</strong>/{ALLOWED_MAIL_ACCOUNTS}</td>
				</tr>
			</tfoot>
			<!-- EDP: mails_total -->
		</table>
        <!-- BDP: delete_marked_mails_form_bottom -->
            <div class="buttons">
                <input type="hidden" name="uaction" value="delete_marked_mails" />
                <input type="submit" name="Submit" value="{TR_DELETE_MARKED_MAILS}" onclick="action_delete_marked()" />
            </div>
        </form>
        <!-- EDP: delete_marked_mails_form_bottom -->
		<!-- BDP: default_mails_form -->
		<form action="mail_accounts.php" method="post" id="showdefault">
			<div class="buttons">
				<input type="hidden" name="uaction" value="{VL_DEFAULT_EMAILS_BUTTON}" />
				<input type="submit" name="Submit" value="{TR_DEFAULT_EMAILS_BUTTON}" />
			</div>
		</form>
		<!-- EDP: default_mails_form -->
