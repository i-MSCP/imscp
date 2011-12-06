<!-- INCLUDE "../shared/layout/header.tpl" -->
		<!-- BDP: subdomain_add_js -->
		<script language="JavaScript" type="text/JavaScript">
		/*<![CDATA[*/
			$(document).ready(function(){
				$('#dmn_help').iMSCPtooltips({msg:"{TR_DMN_HELP}"});

				$('input[name=subdomain_name]').blur(function(){
					subdmnName = $('#subdomain_name').val();

					$.ajaxSetup({
						url: $(location).attr('pathname'),
						type:'POST',
						data: 'subdomain=' + subdmnName + '&uaction=toASCII',
						datatype: 'text',
						beforeSend: function(xhr){xhr.setRequestHeader('Accept','text/plain');},
						success: function(r){$('#subdomain_mnt_pt').val(r);},
						error: iMSCPajxError
					});

					$.ajax();
				});
			});

			function setRatioAlias(){
				document.forms[0].elements['dmn_type'][1].checked = true;
			}

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
		/*]]>*/
		</script>
		<!-- EDP: subdomain_add_js -->
		<div class="header">
			{MAIN_MENU}

			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>

		<div class="location">
			<div class="location-area">
				<h1 class="domains">{TR_MENU_MANAGE_DOMAINS}</h1>
			</div>
			<ul class="location-menu">
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="domains_manage.php">{TR_MENU_MANAGE_DOMAINS}</a></li>
				<li><a href="subdomain_add.php">{TR_LMENU_ADD_SUBDOMAIN}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="domains"><span>{TR_TITLE_ADD_SUBDOMAIN}</span></h2>

			<!-- BDP: page_message -->
				<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<!-- BDP: subdomain_add_form -->
			<form name="client_add_subdomain_frm" method="post" action="subdomain_add.php">
				<table>
					<tr>
						<th colspan="3">{TR_SUBDOMAIN_DATA}</th>
					</tr>
					<tr>
						<td style="width:300px;">
							<label for="subdomain_name">{TR_SUBDOMAIN_NAME}</label><span class="icon i_help" id="dmn_help">Help</span>
						</td>
						<td style="width:300px;">
							<input type="text" name="subdomain_name" id="subdomain_name" value="{SUBDOMAIN_NAME}" />
						</td>
						<td>
							<input type="radio" name="dmn_type" value="dmn" {SUB_DMN_CHECKED}" />{DOMAIN_NAME}
							<!-- BDP: to_alias_domain -->
								<br />
								<input type="radio" name="dmn_type" value="als" {SUB_ALS_CHECKED}" />
								<select name="als_id">
								<!-- BDP: als_list -->
									<option value="{ALS_ID}" {ALS_SELECTED}>.{ALS_NAME}</option>
								<!-- EDP: als_list -->
								</select>
							<!-- EDP: to_alias_domain -->
						</td>
					</tr>
					<tr>
						<td>
							<label for="subdomain_mnt_pt">{TR_MOUNT_POINT}</label>
						</td>
						<td colspan="2">
							<input type="text" name="subdomain_mnt_pt" id="subdomain_mnt_pt" value="{SUBDOMAIN_MOUNT_POINT}" />
						</td>
					</tr>
					<tr>
						<td>{TR_ENABLE_FWD}</td>
						<td colspan="2">
							<input type="radio" name="status" id="redirectEnabled" {CHECK_EN} value="1" onchange='setForwardReadonly(this);' />
							<label for="redirectEnabled">{TR_ENABLE}</label>
							<input type="radio" name="status" id="redirectDisabled" {CHECK_DIS} value="0" onchange='setForwardReadonly(this);' />
							<label for="redirectDisabled">{TR_DISABLE}</label>
						</td>
					</tr>
					<tr>
						<td>
							<label for="forward_prefix">{TR_FORWARD}</label>
						</td>
						<td colspan="2">
							<select name="forward_prefix" id="forward_prefix" style="vertical-align:middle"{DISABLE_FORWARD}>
								<option value="{TR_PREFIX_HTTP}"{HTTP_YES}>{TR_PREFIX_HTTP}</option>
								<option value="{TR_PREFIX_HTTPS}"{HTTPS_YES}>{TR_PREFIX_HTTPS}</option>
								<option value="{TR_PREFIX_FTP}"{FTP_YES}>{TR_PREFIX_FTP}</option>
							</select>
							<input name="forward" type="text" class="textinput" id="forward" style="vertical-align:middle;" value="{FORWARD}"{READONLY_FORWARD} />
						</td>
					</tr>
				</table>

				<div class="buttons">
					<input type="hidden" name="uaction" value="add_subd" />
					<input name="Submit" type="submit" class="button" value="{TR_ADD}" />
				</div>
			</form>
			<!-- EDP: subdomain_add_form -->
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
