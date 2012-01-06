
			<!-- BDP: props_list -->
			<table>
				<tr>
					<th>{TR_DOMAIN_ERRORS}</th>
				</tr>
				<!-- BDP: domain_message -->
				<tr>
					<td>{TR_DOMAIN_MESSAGE}</td>
				</tr>
				<!-- EDP: domain_message -->
				<!-- BDP: domain_list -->
				<tr>
					<td>
						<p>
							<span class="bold">{TR_DOMAIN_NAME}</span> - <a href="imscp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}" class="link">{TR_CHANGE_STATUS}</a>
						</p>
						<span style="color:red;">{TR_DOMAIN_ERROR}</span>
					</td>
				</tr>
				<!-- EDP: domain_list -->
			</table>
			<table>
				<tr>
					<th>{TR_ALIAS_ERRORS}</th>
				</tr>
				<!-- BDP: alias_message -->
				<tr>
					<td>{TR_ALIAS_MESSAGE}</td>
				</tr>
				<!-- EDP: alias_message -->
				<!-- BDP: alias_list -->
				<tr>
					<td>
						<span class="bold">{TR_ALIAS_NAME}</span> - <a href="imscp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}" class="link">{TR_CHANGE_STATUS}</a>
						<span style="color:red;">{TR_ALIAS_ERROR}</span>
					</td>
				</tr>
				<!-- EDP: alias_list -->
			</table>
			<table>
				<tr>
					<th>{TR_SUBDOMAIN_ERRORS}</th>
				</tr>
				<!-- BDP: subdomain_message -->
				<tr>
					<td>{TR_SUBDOMAIN_MESSAGE}</td>
				</tr>
				<!-- EDP: subdomain_message -->
				<!-- BDP: subdomain_list -->
				<tr>
					<td>
						<p>
							<span class="bold">{TR_SUBDOMAIN_NAME}</span> - <a href="imscp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}" class="link">{TR_CHANGE_STATUS}</a>
						</p>
						<span style="color:red;">{TR_SUBDOMAIN_ERROR}</span>
					</td>
				</tr>
				<!-- EDP: subdomain_list -->
			</table>
			<table>
				<tr>
					<th>{TR_SUBDOMAIN_ALIAS_ERRORS}</th>
				</tr>
				<!-- BDP: subdomain_alias_message -->
				<tr>
					<td>{TR_SUBDOMAIN_ALIAS_MESSAGE}</td>
				</tr>
				<!-- EDP: subdomain_alias_message -->
				<!-- BDP: subdomain_alias_list -->
				<tr>
					<td>
						<p>
							<span class="bold">{TR_SUBDOMAIN_ALIAS_NAME}</span> - <a href="imscp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}" class="link">{TR_CHANGE_STATUS}</a>
						</p>
						<span style="color:red;">{TR_SUBDOMAIN_ALIAS_ERROR}</span>
					</td>
				</tr>
				<!-- EDP: subdomain_alias_list -->
			</table>

			<table>
				<tr>
					<th>{TR_MAIL_ERRORS}</th>
				</tr>
				<!-- BDP: mail_message -->
				<tr>
					<td>{TR_MAIL_MESSAGE}</td>
				</tr>
				<!-- EDP: mail_message -->
				<!-- BDP: mail_list -->
				<tr>
					<td>
						<p>
							<span class="bold">{TR_MAIL_NAME}</span> - <a href="imscp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}" class="link">{TR_CHANGE_STATUS}</a>
						</p>
						<span style="color:red;">{TR_MAIL_ERROR}</span></td>
				</tr>
				<!-- EDP: mail_list -->
			</table>
			<table>
				<tr>
					<th>{TR_DAEMON_TOOLS}</th>
				</tr>
				<tr>
					<td><a href="imscp_debugger.php?action=run_engine" class="link">{EXEC_COUNT} {TR_EXEC_REQUESTS}</a></td>
				</tr>
			</table>
			<!-- EDP: props_list -->

