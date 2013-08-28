
<table>
	<thead>
	<tr>
		<th>{TR_USER_ERRORS}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: user_message -->
	<tr>
		<td>{TR_USER_MESSAGE}</td>
	</tr>
	<!-- EDP: user_message -->
	<!-- BDP: user_list -->
	<tr>
		<td>
			<p>
				<span class="bold">{TR_USER_NAME}</span> - <a
					href="imscp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}"
					class="link">{TR_CHANGE_STATUS}</a>
			</p>
			<span style="color:red;">{TR_USER_ERROR}</span>
		</td>
	</tr>
	<!-- EDP: user_list -->
	</tbody>
</table>
<table>
	<thead>
	<tr>
		<th>{TR_DMN_ERRORS}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: dmn_message -->
	<tr>
		<td>{TR_DMN_MESSAGE}</td>
	</tr>
	<!-- EDP: dmn_message -->
	<!-- BDP: dmn_list -->
	<tr>
		<td>
			<p>
				<span class="bold">{TR_DMN_NAME}</span> - <a
					href="imscp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}"
					class="link">{TR_CHANGE_STATUS}</a>
			</p>
			<span style="color:red;">{TR_DMN_ERROR}</span>
		</td>
	</tr>
	<!-- EDP: dmn_list -->
	</tbody>
</table>
<table>
	<thead>
	<tr>
		<th>{TR_ALS_ERRORS}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: als_message -->
	<tr>
		<td>{TR_ALS_MESSAGE}</td>
	</tr>
	<!-- EDP: als_message -->
	<!-- BDP: als_list -->
	<tr>
		<td>
			<span class="bold">{TR_ALS_NAME}</span> - <a
				href="imscp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}"
				class="link">{TR_CHANGE_STATUS}</a>
			<span style="color:red;">{TR_ALS_ERROR}</span>
		</td>
	</tr>
	<!-- EDP: als_list -->
	</tbody>
</table>
<table>
	<thead>
	<tr>
		<th>{TR_SUB_ERRORS}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: sub_message -->
	<tr>
		<td>{TR_SUB_MESSAGE}</td>
	</tr>
	<!-- EDP: sub_message -->
	<!-- BDP: sub_list -->
	<tr>
		<td>
			<p>
				<span class="bold">{TR_SUB_NAME}</span> - <a
					href="imscp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}"
					class="link">{TR_CHANGE_STATUS}</a>
			</p>
			<span style="color:red;">{TR_SUB_ERROR}</span>
		</td>
	</tr>
	<!-- EDP: sub_list -->
	</tbody>
</table>
<table>
	<thead>
	<tr>
		<th>{TR_ALSSUB_ERRORS}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: alssub_message -->
	<tr>
		<td>{TR_ALSSUB_MESSAGE}</td>
	</tr>
	<!-- EDP: alssub_message -->
	<!-- BDP: alssub_list -->
	<tr>
		<td>
			<p>
				<span class="bold">{TR_ALSSUB_NAME}</span> - <a
					href="imscp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}"
					class="link">{TR_CHANGE_STATUS}</a>
			</p>
			<span style="color:red;">{TR_ALSSUB_ERROR}</span>
		</td>
	</tr>
	<!-- EDP: alssub_list -->
	</tbody>
</table>

<table>
	<thead>
	<tr>
		<th>{TR_HTACCESS_ERRORS}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: htaccess_message -->
	<tr>
		<td>{TR_HTACCESS_MESSAGE}</td>
	</tr>
	<!-- EDP: htaccess_message -->
	<!-- BDP: htaccess_list -->
	<tr>
		<td>
			<p>
				<span class="bold">{TR_HTACCESS_NAME}</span> - <a
					href="imscp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}"
					class="link">{TR_CHANGE_STATUS}</a>
			</p>
			<span style="color:red;">{TR_HTACCESS_ERROR}</span></td>
	</tr>
	<!-- EDP: htaccess_list -->
	</tbody>
</table>
<table>
	<thead>
	<tr>
		<th>{TR_MAIL_ERRORS}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: mail_message -->
	<tr>
		<td>{TR_MAIL_MESSAGE}</td>
	</tr>
	<!-- EDP: mail_message -->
	<!-- BDP: mail_list -->
	<tr>
		<td>
			<p>
				<span class="bold">{TR_MAIL_NAME}</span> - <a
					href="imscp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}"
					class="link">{TR_CHANGE_STATUS}</a>
			</p>
			<span style="color:red;">{TR_MAIL_ERROR}</span></td>
	</tr>
	<!-- EDP: mail_list -->
	</tbody>
</table>

<table>
	<thead>
	<tr>
		<th>{TR_PLUGINS_ERRORS}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: plugins_message -->
	<tr>
		<td>{TR_PLUGIN_MESSAGE}</td>
	</tr>
	<!-- EDP: plugin_message -->
	<!-- BDP: plugin_list -->
	<tr>
		<td>
			<p>
				<span class="bold">{TR_PLUGIN_NAME}</span> - <a
					href="imscp_debugger.php?action=change_status&amp;id={CHANGE_ID}&amp;type={CHANGE_TYPE}"
					class="link">{TR_CHANGE_STATUS}</a>
			</p>
			<span style="color:red;">{TR_PLUGIN_ERROR}</span></td>
	</tr>
	<!-- EDP: plugin_list -->
	</tbody>
</table>
<table>
	<thead>
	<tr>
		<th>{TR_DAEMON_TOOLS}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td><a href="imscp_debugger.php?action=run_engine" class="link">{EXEC_COUNT} {TR_EXEC_REQUESTS}</a></td>
	</tr>
	</tbody>
</table>
