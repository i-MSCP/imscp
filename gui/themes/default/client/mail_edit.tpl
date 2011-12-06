<!-- INCLUDE "../shared/layout/header.tpl" -->
	<script type="text/javascript">
	/* <![CDATA[ */
		$(document).ready(function(){
			$('#fwd_help').iMSCPtooltips({msg:"{TR_FWD_HELP}"});

			if(!$('#forwardAccount').is(':checked') && $('#forwardList').val() == '') {
				$('#forwardList').attr('disabled', true);
			}

			$('#forwardAccount').change(function(){
				if($(this).is(':checked')) {
					$('#forwardList').removeAttr('disabled');
				} else {
					$('#forwardList').attr('disabled', true).val('');
				}
			});
		});
	/* ]]> */
	</script>
	<div class="header">
		{MAIN_MENU}
		<div class="logo">
			<img src="{ISP_LOGO}" alt="i-MSCP logo"/>
		</div>
	</div>

	<div class="location">
		<div class="location-area">
			<h1 class="email">{TR_MENU_MAIL_ACCOUNTS}</h1>
		</div>
		<ul class="location-menu">
			<!-- BDP: logged_from -->
			<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
			<!-- EDP: logged_from -->
			<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
		</ul>
		<ul class="path">
			<li><a href="mail_accounts.php">{TR_MENU_MAIL_ACCOUNTS}</a></li>
			<li><a href="mail_accounts.php">{TR_LMENU_OVERVIEW}</a></li>
			<li><a href="#" onclick="return false;">{TR_EDIT_MAIL_ACCOUNT}</a></li>
		</ul>
	</div>

	<div class="left_menu">
		{MENU}
	</div>

	<div class="body">
		<h2 class="email"><span>{TR_EDIT_MAIL_ACCOUNT}</span></h2>

		<!-- BDP: page_message -->
		<div class="{MESSAGE_CLS}">{MESSAGE}</div>
		<!-- EDP: page_message -->

		<form name="editFrm" method="post" action="mail_edit.php?id={MAIL_ID_VAL}">
			<table>
				<tr>
					<th colspan="2">
						<span style="vertical-align: middle">{TR_MAIL_ACCOUNT} : {MAIL_ADDRESS_VAL}</span>
					</th>
				</tr>
				<!-- BDP: password_frm -->
				<tr>
					<td><label for="password">{TR_PASSWORD}</label></td>
					<td><input name="password" id="password" type="password" value=""/></td>
				</tr>
				<tr>
					<td><label for="passwordConfirmation">{TR_PASSWORD_CONFIRMATION}</label></td>
					<td>
						<input name="passwordConfirmation" id="passwordConfirmation" type="password" value=""/>
					</td>
				</tr>
				<tr>
					<td>
						<label for="forwardAccount">{TR_FORWARD_ACCOUNT}</label>
					</td>
					<td>
						<input name="forwardAccount" id="forwardAccount" type="checkbox"{FORWARD_ACCOUNT_CHECKED}/>
					</td>
				</tr>
				<!-- EDP: password_frm -->
				<tr>
					<td style="width:300px">
						<label for="forwardList">{TR_FORWARD_TO}</label><span style="vertical-align: middle;" class="icon i_help" id="fwd_help">{TR_HELP}</span>
					</td>
					<td>
						<textarea name="forwardList" id="forwardList" cols="40" rows="5">{FORWARD_LIST_VAL}</textarea>
					</td>
				</tr>
			</table>
			<div class="buttons">
				<input name="submit" type="submit" value="{TR_UPDATE}"/>
				<input name="cancel" type="button" onclick="MM_goToURL('parent','mail_accounts.php');return document.MM_returnValue" value="{TR_CANCEL}"/>
			</div>
		</form>
	</div>
</body>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
