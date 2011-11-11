	<script type="text/javascript" language="JavaScript">
		/*<![CDATA[*/
		$(document).ready(function() {
			errFieldsStack = {ERR_FIELDS_STACK};
			$.each(errFieldsStack, function(){$("#" + this).addClass("input_error");});
			$("#domain_expire_help").iMSCPtooltips({msg:"{TR_DOMAIN_EXPIRE_HELP}"});
			$("#domain_expires").datepicker();
			$("#domain_never_expires").change(function(){
				if($(this).is(":checked")) {
					$("#domain_expires").removeClass("input_error").attr("disabled", "disabled")
				} else {
					$("#domain_expires").removeAttr("disabled");
				}
			});
		});
		/*]]>*/
	</script>
	<form name="editFrm" method="post" action="domain_edit.php?edit_id={EDIT_ID}">
		<table>
			<tr>
				<th colspan="4">{TR_DOMAIN_DATA}</th>
			</tr>
			<tr>
				<td>{TR_DOMAIN_NAME}</td>
				<td colspan="3">{DOMAIN_NAME}</td>
			</tr>
			<tr>
				<td>{TR_DOMAIN_EXPIRE_DATE}</td>
				<td colspan="3">{DOMAIN_EXPIRE_DATE}</td>
			</tr>
			<tr>
				<td>{TR_DOMAIN_NEW_EXPIRE_DATE} <span style="vertical-align: middle" class="icon i_help" id="domain_expire_help">{TR_HELP}</span></td>
				<td>
					<div style="position:relative">
						<span style="display:inline-block;">
							<input type="text" id="domain_expires" name="domain_expires" value="{DOMAIN_NEW_EXPIRE_DATE}" {DOMAIN_NEW_EXPIRE_DATE_DISABLED} />
							<label for="domain_expires" style="display:block;color:#999999;font-size: smaller;">(MM/DD/YYYY)</label>
						</span>
					</div>
				</td>
				<td colspan="2">
					<input type="checkbox" name="domain_never_expires" id="domain_never_expires" {DOMAIN_NEVER_EXPIRES_CHECKED} style="vertical-align: middle;"/>
					<label for="domain_never_expires" style="vertical-align: middle;">{TR_DOMAIN_NEVER_EXPIRES}</label>
				</td>
			</tr>
			<tr>
				<td>{TR_DOMAIN_IP}</td>
				<td colspan="3">{DOMAIN_IP} <i>{IP_DOMAIN}</i></td>
			</tr>
			<tr>
			<tr>
				<th>{TR_DOMAIN_LIMITS}</th>
				<th>{TR_MAX_LIMIT}</th>
				<th>{TR_CUSTOMER_CONSUMPTION}</th>
				<th>{TR_RESELLER_CONSUMPTION}</th>
			</tr>
			<!-- BDP: subdomain_limit_block -->
			<tr>
				<td><label for="domain_subd_limit">{TR_SUBDOMAINS_LIMIT}</label></td>
				<td>
					<input type="text" name="domain_subd_limit" id="domain_subd_limit" value="{SUBDOMAIN_LIMIT}"/>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_CUSTOMER_SUBDOMAINS_COMSUPTION}</span>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_RESELLER_SUBDOMAINS_COMSUPTION}</span>
				</td>
			</tr>
			<!-- EDP: subdomain_limit_block -->
			<!-- BDP: domain_aliasses_limit_block -->
			<tr>
				<td><label for="domain_alias_limit">{TR_ALIASSES_LIMIT}</label></td>
				<td>
					<input type="text" name="domain_alias_limit" id="domain_alias_limit" value="{DOMAIN_ALIASSES_LIMIT}"/>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_CUSTOMER_DOMAIN_ALIASSES_COMSUPTION}</span>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_RESELLER_DOMAIN_ALIASSES_COMSUPTION}</span>
				</td>
			</tr>
			<!-- EDP: domain_aliasses_limit_block -->
			<!-- BDP: mail_accounts_limit_block -->
			<tr>
				<td><label for="domain_mailacc_limit">{TR_MAIL_ACCOUNTS_LIMIT}</label></td>
				<td>
					<input type="text" name="domain_mailacc_limit" id="domain_mailacc_limit" value="{MAIL_ACCOUNTS_LIMIT}"/>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_CUSTOMER_MAIL_ACCOUNTS_COMSUPTION}</span>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_RESELLER_MAIL_ACCOUNTS_COMSUPTION}</span>
				</td>
			</tr>
			<!-- EDP: mail_accounts_limit_block -->
			<!-- BDP: ftp_accounts_limit_block -->
			<tr>
				<td><label for="domain_ftpacc_limit">{TR_FTP_ACCOUNTS_LIMIT}</label></td>
				<td>
					<input type="text" name="domain_ftpacc_limit" id="domain_ftpacc_limit" value="{FTP_ACCOUNTS_LIMIT}"/>

				</td>
				<td>
					<span style="font-size: smaller;">{TR_CUSTOMER_FTP_ACCOUNTS_COMSUPTION}</span>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_RESELLER_FTP_ACCOUNTS_COMSUPTION}</span>
				</td>
			</tr>
			<!-- EDP: ftp_accounts_limit_block -->
			<!-- BDP: sql_db_and_users_limit_block -->
			<tr>
				<td><label for="domain_sqld_limit">{TR_SQL_DATABASES_LIMIT}</label></td>
				<td>
					<input type="text" name="domain_sqld_limit" id="domain_sqld_limit" value="{SQL_DATABASES_LIMIT}"/>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_CUSTOMER_SQL_DATABASES_COMSUPTION}</span>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_RESELLER_SQL_DATABASES_COMSUPTION}</span>
				</td>
			</tr>
			<tr>
				<td><label for="domain_sqlu_limit">{TR_SQL_USERS_LIMIT}</label></td>
				<td>
					<input type="text" name="domain_sqlu_limit" id="domain_sqlu_limit" value="{SQL_USERS_LIMIT}"/>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_CUSTOMER_SQL_USERS_COMSUPTION}</span>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_RESELLER_SQL_USERS_COMSUPTION}</span>
				</td>
			</tr>
			<!-- EDP: sql_db_and_users_limit_block -->
			<tr>
				<td><label for="domain_traffic_limit">{TR_TRAFFIC_LIMIT}</label></td>
				<td>
					<input type="text" name="domain_traffic_limit" id="domain_traffic_limit" value="{TRAFFIC_LIMIT}"/>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_CUSTOMER_TRAFFIC_COMSUPTION}</span>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_RESELLER_TRAFFIC_COMSUPTION}</span>
				</td>
			</tr>
			<tr>
				<td><label for="domain_disk_limit">{TR_DISK_LIMIT}</label></td>
				<td>
					<input type="text" name="domain_disk_limit" id="domain_disk_limit" value="{DISK_LIMIT}"/>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_CUSTOMER_DISKPACE_COMSUPTION}</span>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_RESELLER_DISKPACE_COMSUPTION}</span>
				</td>
			</tr>
			<tr>
				<th colspan="4">{TR_FEATURES}</th>
			</tr>
			<!-- BDP: php_support_block -->
			<tr>
				<td><label for="domain_php">{TR_PHP_SUPPORT}</td>
				<td colspan="3">
					<select id="domain_php" name="domain_php">
						<option value="yes"{PHP_SUPPORT_YES}>{TR_YES}</option>
						<option value="no"{PHP_SUPPORT_NO}>{TR_NO}</option>
					</select>
				</td>
			</tr>
			<!-- EDP: php_support_block -->
			<!-- BDP: cgi_support_block -->
			<tr>
				<td><label for="domain_cgi">{TR_CGI_SUPPORT}</label></td>
				<td colspan="3">
					<select id="domain_cgi" name="domain_cgi">
						<option value="yes"{CGI_SUPPORT_YES}>{TR_YES}</option>
						<option value="no"{CGI_SUPPORT_NO}>{TR_NO}</option>
					</select>
				</td>
			</tr>
			<!-- EDP: cgi_support_block -->
			<!-- BDP: dns_support_block -->
			<tr>
				<td><label for="domain_dns">{TR_DNS_SUPPORT}</td>
				<td colspan="3">
					<select id="domain_dns" name="domain_dns">
						<option value="yes"{DNS_SUPPORT_YES}>{TR_YES}</option>
						<option value="no"{DNS_SUPPORT_NO}>{TR_NO}</option>
					</select>
				</td>
			</tr>
			<!-- EDP: dns_support_block -->
			<!-- BDP: aps_support_block -->
			<tr>
				<td><label for="domain_software_allowed">{TR_APS_SUPPORT}</label></td>
				<td colspan="3">
					<select name="domain_software_allowed" id="domain_software_allowed">
						<option value="yes"{APS_SUPPORT_YES}>{TR_YES}</option>
						<option value="no"{APS_SUPPORT_NO}>{TR_NO}</option>
					</select>
				</td>
			</tr>
			<!-- EDP: aps_support_block -->
			<!-- BDP: backup_support_block -->
			<tr>
				<td><label for="allowbackup">{TR_BACKUP_SUPPORT}</label></td>
				<td colspan="3">
					<select id="allowbackup" name="allowbackup">
						<option value="dmn"{BACKUP_DOMAIN}>{TR_BACKUP_DOMAIN}</option>
						<option value="sql"{BACKUP_SQL}>{TR_BACKUP_SQL}</option>
						<option value="full"{BACKUP_FULL}>{TR_BACKUP_FULL}</option>
						<option value="no"{BACKUP_NO}>{TR_BACKUP_NO}</option>
					</select>
				</td>
			</tr>
			<!-- EDP: backup_support_block -->
		</table>
		<div class="buttons">
			<input name="submit" type="submit" value="{TR_UPDATE}"/>
			<input name="cancel" type="button" onclick="MM_goToURL('parent','/admin/manage_users.php');return document.MM_returnValue" value="{TR_CANCEL}"/>
		</div>
	</form>
