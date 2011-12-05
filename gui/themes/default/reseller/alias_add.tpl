<!-- INCLUDE "../shared/layout/header.tpl" -->
		<script language="JavaScript" type="text/JavaScript">
		/*<![CDATA[*/
			$(document).ready(function(){
				$('#dmn_help').iMSCPtooltips({msg:"{TR_DMN_HELP}"});

				// Request for encode_idna request
				$('input[name=ndomain_name]').bind('blur', function(){
					dmnName = $('#ndomain_name').val();
                    if(dmnName != '') {
					    $.ajaxSetup({
					        url: $(location).attr('pathname'),
						    type:'POST',
						    data: 'domain=' + dmnName + '&uaction=toASCII',
						    datatype: 'text',
						    beforeSend: function(xhr){xhr.setRequestHeader('Accept','text/plain');},
						    success: function(r){$('#ndomain_mpoint').val(r);},
						    error: iMSCPajxError
					    });
					    $.ajax();
                    }
				});
			});

			function setForwardReadonly(obj){
				if(obj.value == 1) {
					document.forms[0].elements['forward'].readOnly = false;
					document.forms[0].elements['forward_prefix'].disabled = false;
				} else {
					document.forms[0].elements['forward'].readOnly = true;
					document.forms[0].elements['forward'].value = '';
					document.forms[0].elements['forward_prefix'].disabled = true;
				}
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
				<h1 class="manage_users">{TR_MENU_MANAGE_USERS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="users.php">{TR_MENU_MANAGE_USERS}</a></li>
				<li><a href="alias.php">{TR_MENU_DOMAIN_ALIAS}</a></li>
				<li><a href="alias_add.php">{TR_ADD_ALIAS}</a></li>
			</ul>
		</div>
		<div class="left_menu">
			{MENU}
		</div>
		<div class="body">
			<h2 class="domains"><span>{TR_MANAGE_DOMAIN_ALIAS}</span></h2>
			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="add_alias_frm" method="post" action="alias_add.php">
				<fieldset>
					<legend>{TR_ADD_ALIAS}</legend>
					<table>
						<tr>
							<td colspan="2">
								<label for="usraccounts">{TR_DOMAIN_ACCOUNT}</label>
							</td>
							<td>
								<select id="usraccounts" name="usraccounts" size="5">
									<!-- BDP: user_entry -->
									<option value="{USER}" {SELECTED}>{USER_DOMAIN_ACCOUNT}</option>
									<!-- EDP: user_entry -->
								</select>
							</td>
						</tr>
						<tr>
							<td style="width: 300px;">
								<label for="ndomain_name">{TR_DOMAIN_NAME}</label><span class="icon i_help" id="dmn_help">Help</span></td>
							<td>
								<div style="text-align:right;">http://</div>
							</td>
							<td>
								<input id="ndomain_name" name="ndomain_name" type="text" value="{DOMAIN}" />
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<label for="ndomain_mpoint">{TR_MOUNT_POINT}</label>
							</td>
							<td>
								<input name="ndomain_mpoint" type="text" class="textinput" id="ndomain_mpoint" value="{MP}" />
							</td>
						</tr>
						<tr>
							<td colspan="2">{TR_ENABLE_FWD}</td>
							<td>
								<input type="radio" name="status" id="status_enable"{CHECK_EN} value="1" onChange="setForwardReadonly(this);" /><label for="status_enable">{TR_ENABLE}</label><br />
								<input type="radio" name="status" id="status_disable"{CHECK_DIS} value="0" onChange="setForwardReadonly(this);" /><label for="status_disable">{TR_DISABLE}</label>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<label for="forward">{TR_FORWARD}</label>
							</td>
							<td>
								<select name="forward_prefix" style="vertical-align:middle"{DISABLE_FORWARD}>
									<option value="{TR_PREFIX_HTTP}"{HTTP_YES}>{TR_PREFIX_HTTP}</option>
									<option value="{TR_PREFIX_HTTPS}"{HTTPS_YES}>{TR_PREFIX_HTTPS}</option>
									<option value="{TR_PREFIX_FTP}"{FTP_YES}>{TR_PREFIX_FTP}</option>
								</select>
								<input name="forward" type="text" class="textinput" id="forward" value="{FORWARD}"{READONLY_FORWARD} />
							</td>
						</tr>
					</table>
				</fieldset>
				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_ADD}" />
				</div>
				<input type="hidden" name="uaction" value="add_alias" />
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
