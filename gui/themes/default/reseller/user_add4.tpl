
			<script language="JavaScript" type="text/JavaScript">
				/*<![CDATA[*/
				$(document).ready(function (){
					$('input[name=ndomain_name]').blur(function(){
						dmnName = $('#ndomain_name').val();
						// Configure the request for encode_idna request
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
			<!-- BDP: add_form -->
			<form name="add_alias_frm" method="post" action="user_add4.php">
				<!-- BDP: alias_list -->
				<table class="firstColFixed">
					<thead>
					<tr>
						<th>{TR_DOMAIN_ALIAS}</th>
						<th>{TR_FORWARD}</th>
						<th>{TR_STATUS}</th>
					</tr>
					</thead>
					<tbody>
					<!-- BDP: alias_entry -->
					<tr>
						<td>{DOMAIN_ALIAS}</td>
						<td>{FORWARD_URL}</td>
						<td>{STATUS}</td>
					</tr>
					<!-- EDP: alias_entry -->
					</tbody>
				</table>
				<!-- EDP: alias_list -->
				<table class="firstColFixed">
					<thead>
					<tr>
						<th colspan="2">{TR_ADD_ALIAS}</th>
					</tr>
					</thead>
					<tbody>
					<tr>
						<td>
							<label for="ndomain_name">{TR_DOMAIN_NAME}</label><span class="icon i_help" id="dmn_help" title="{TR_DMN_HELP}">Help</span>
						</td>
						<td><input id="ndomain_name" name="ndomain_name" type="text" value="{DOMAIN}"/></td>
					</tr>
					<tr>
						<td><label for="ndomain_mpoint">{TR_MOUNT_POINT}</label></td>
						<td><input id="ndomain_mpoint" name="ndomain_mpoint" type="text" value='{MP}'/></td>
					</tr>
					<tr>
						<td>{TR_ENABLE_FWD}</td>
						<td>
							<input type="radio" name="status" id="status_enable"{CHECK_EN} value="1" onChange="setForwardReadonly(this);"/><label for="status_enable">{TR_ENABLE}</label><br/>
							<input type="radio" name="status" id="status_disable"{CHECK_DIS} value="0" onChange="setForwardReadonly(this);"/><label for="status_disable">{TR_DISABLE}</label>
						</td>
					</tr>
					<tr>
						<td>
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
					</tbody>
				</table>
				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_ADD}"/>
					<input name="Button" type="button" onclick="MM_goToURL('parent','users.php');return document.MM_returnValue" value="{TR_GO_USERS}"/>
				</div>
				<input type="hidden" name="uaction" value="add_alias"/>
			</form>
			<!-- EDP: add_form -->
