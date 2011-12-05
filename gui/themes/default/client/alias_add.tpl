<!-- INCLUDE "../shared/layout/header.tpl" -->
	<!-- BDP: domain_alias_add_js -->
	<script type="text/javascript">
	/* <![CDATA[ */
		$(document).ready(function(){
			$('#dmn_help').iMSCPtooltips({msg:"{TR_DMN_HELP}"})

			$('input[name=ndomain_name]').blur(function(){
				dmnName = $('#ndomain_name').val();

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
	<!-- EDP: domain_alias_add_js -->
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
			<!-- <li><a class="help" href="#">Help</a></li> -->
			<!-- BDP: logged_from -->
			<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
			<!-- EDP: logged_from -->
			<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
		</ul>
		<ul class="path">
			<li><a href="domains_manage.php">{TR_MENU_MANAGE_DOMAINS}</a></li>
			<li><a href="#" onclick="return false;">{TR_LMENU_ADD_DOMAIN_ALIAS}</a></li>
		</ul>
	</div>
	<div class="left_menu">{MENU}</div>
	<div class="body">
		<h2 class="domains"><span>{TR_TITLE_ADD_DOMAIN_ALIAS}</span></h2>

		<!-- BDP: page_message -->
		<div class="{MESSAGE_CLS}">{MESSAGE}</div>
		<!-- EDP: page_message -->

		<!-- BDP: domain_alias_add_form -->
		<form name="add_alias_frm" method="post" action="alias_add.php">
			<table>
				<tr>
					<th colspan="3">{TR_DOMAIN_ALIAS_DATA}</th>
				</tr>
				<tr>
					<td style="width:300px;">
						<label for="ndomain_name">{TR_DOMAIN_ALIAS_NAME}</label><span class="icon i_help" id="dmn_help">Help</span>
					</td>
					<td>
						<input name="ndomain_name" id="ndomain_name" type="text" value="{DOMAIN}" />
					</td>
				</tr>
				<tr>
					<td>
						<label for="ndomain_mpoint">{TR_MOUNT_POINT}</label>
					</td>
					<td>
						<input name="ndomain_mpoint" type="text" id="ndomain_mpoint" value="{MP}" />
					</td>
				</tr>
				<tr>
					<td>{TR_ENABLE_FWD}</td>
					<td>
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
				<input type="hidden" name="uaction" value="add_alias" />
				<input type="submit" name="add" value="{TR_ADD}" />
			</div>
		</form>
		<!-- EDP: domain_alias_add_form -->
	</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
