
<!-- BDP: day_server_statistics_block -->
<p><strong>{TR_YEAR}:</strong> {YEAR} <strong>{TR_MONTH}:</strong> {MONTH} <strong>{TR_DAY}:</strong> {DAY}</p>

<table>
	<thead>
	<tr>
		<th>{TR_HOUR}</th>
		<th>{TR_WEB_IN}</th>
		<th>{TR_WEB_OUT}</th>
		<th>{TR_SMTP_IN}</th>
		<th>{TR_SMTP_OUT}</th>
		<th>{TR_POP_IN}</th>
		<th>{TR_POP_OUT}</th>
		<th>{TR_OTHER_IN}</th>
		<th>{TR_OTHER_OUT}</th>
		<th>{TR_ALL_IN}</th>
		<th>{TR_ALL_OUT}</th>
		<th>{TR_ALL}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td>{TR_ALL}</td>
		<td>{WEB_IN_ALL}</td>
		<td>{WEB_OUT_ALL}</td>
		<td>{SMTP_IN_ALL}</td>
		<td>{SMTP_OUT_ALL}</td>
		<td>{POP_IN_ALL}</td>
		<td>{POP_OUT_ALL}</td>
		<td>{OTHER_IN_ALL}</td>
		<td>{OTHER_OUT_ALL}</td>
		<td>{ALL_IN_ALL}</td>
		<td>{ALL_OUT_ALL}</td>
		<td>{ALL_ALL}</td>
	</tr>
	</tfoot>
	<tbody>
	<!-- BDP: hour_list -->
	<tr>
		<td>{HOUR}</td>
		<td>{WEB_IN}</td>
		<td>{WEB_OUT}</td>
		<td>{SMTP_IN}</td>
		<td>{SMTP_OUT}</td>
		<td>{POP_IN}</td>
		<td>{POP_OUT}</td>
		<td>{OTHER_IN}</td>
		<td>{OTHER_OUT}</td>
		<td>{ALL_IN}</td>
		<td>{ALL_OUT}</td>
		<td>{ALL}</td>
	</tr>
	<!-- EDP: hour_list -->
	</tbody>
</table>
<!-- EDP: day_server_statistics_block -->
