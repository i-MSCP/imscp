
			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}" style="width:550px;">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<table style="width:550px;">
				<tr>
					<th>{PACK_NAME}</th>
					<th style="text-align:right;">
						<a href="index.php?coid={CUSTOM_ORDERPANEL_ID}&amp;user_id={USER_ID}">{TR_BACK}</a>
					</th>
				</tr>
				<tr>
					<td>{DESCRIPTION}</td>
				</tr>
				<tr>
					<td colspan="2"><strong>{TR_DOMAINS}</strong></td>
				</tr>
				<tr>
					<td colspan="2"><strong>{TR_OWN_DOMAIN}</strong></td>
				</tr>
				<tr>
					<td>{TR_DOMAIN_ALIAS}</td>
					<td>{ALIAS}</td>
				</tr>
				<tr>
					<td>{TR_SUBDOMAINS}</td>
					<td>{SUBDOMAIN}</td>
				</tr>
				<tr>
					<td colspan="2"><strong>{TR_WEBSPACE}</strong></td>
				</tr>
				<tr>
					<td>{TR_HDD}</td>
					<td>{HDD}</td>
				</tr>
				<tr>
					<td>{TR_TRAFFIC}</td>
					<td>{TRAFFIC}</td>
				</tr>
				<tr>
					<td colspan="2">
						<span><strong>{TR_FEATURES}</strong></span>
					</td>
				</tr>
				<tr>
					<td>{TR_PHP_SUPPORT}</td>
					<td>{PHP}</td>
				</tr>
				<!-- BDP: t_software_support -->
				<tr>
					<td>{TR_SOFTWARE_SUPPORT}</td>
					<td>{SOFTWARE}</td>
				</tr>
				<!-- EDP: t_software_support -->
				<tr>
					<td>{TR_CGI_SUPPORT}</td>
					<td>{CGI}</td>
				</tr>
				<tr>
					<td>{TR_DNS_SUPPORT}</td>
					<td>{DNS}</td>
				</tr>
				<tr>
					<td>{TR_MAIL_ACCOUNTS}</td>
					<td>{MAIL}</td>
				</tr>
				<tr>
					<td>{TR_FTP_ACCOUNTS}</td>
					<td>{FTP}</td>
				</tr>
				<tr>
					<td>{TR_SQL_DATABASES}</td>
					<td>{SQL_DB}</td>
				</tr>
				<tr>
					<td>{TR_SQL_USERS}</td>
					<td>{SQL_USR}</td>
				</tr>
				<tr>
					<td colspan="2">
						<strong>{TR_STANDARD_FEATURES}</strong>
					</td>
				</tr>
				<tr>
					<td>{TR_IMSCP}</td>
					<td>{YES}</td>
				</tr>
				<tr>
					<td>{TR_WEBMAIL}</td>
					<td>{YES}</td>
				</tr>
				<tr>
					<td>{TR_FILEMANAGER}</td>
					<td>{YES}</td>
				</tr>
				<tr>
					<td>{TR_BACKUP}</td>
					<td>{YES}</td>
				</tr>
				<tr>
					<td>{TR_STATISTICS}</td>
					<td>{YES}</td>
				</tr>
				<tr>
					<td>{TR_ERROR_PAGES}</td>
					<td>{YES}</td>
				</tr>
				<tr>
					<td>{TR_HTACCESS}</td>
					<td>{YES}</td>
				</tr>
				<tr>
					<td>{TR_CUSTOM_LOGS}</td>
					<td>{YES}</td>
				</tr>
				<tr>
					<td colspan="2"><strong>{TR_PERFORMANCE}</strong></td>
				</tr>
				<tr>
					<td>{TR_ONLINE_SUPPORT}</td>
					<td>{YES}</td>
				</tr>
				<tr>
					<td>{TR_UPDATES}</td>
					<td>{YES}</td>
				</tr>
				<tr>
					<td colspan="2"><strong>{TR_PRICE}</strong></td>
				</tr>
				<tr>
					<td>{TRR_PRICE}</td>
					<td>{PRICE}</td>
				</tr>
				<tr>
					<td>{TR_SETUP_FEE}</td>
					<td>{SETUP}</td>
				</tr>
			</table>
			<div style="width:550px;text-align:right;">
				<a href="addon.php?coid={CUSTOM_ORDERPANEL_ID}&amp;id={PACK_ID}&amp;user_id={USER_ID}"><strong>{TR_PURCHASE}</strong></a>
			</div>
