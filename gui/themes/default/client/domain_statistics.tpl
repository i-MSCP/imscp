
<form name="domain_statistics_frm" method="post" action="domain_statistics.php">
	<label for="month">{TR_MONTH}</label>
	<select id="month" name="month">
		<!-- BDP: month_list -->
		<option {OPTION_SELECTED}>{MONTH_VALUE}</option>
		<!-- EDP: month_list -->
	</select>
	<label for="year">{TR_YEAR}</label>
	<select name="year" id="year">
		<!-- BDP: year_list -->
		<option value="{VALUE}" {OPTION_SELECTED}>{HUMAN_VALUE}</option>
		<!-- EDP: year_list -->
	</select>
	<input name="Submit" type="submit" value="{TR_SHOW}"/>
	<input name="uaction" type="hidden" value="show_traff"/>
</form>

<!-- BDP: domain_statistics_block -->
<table>
	<thead>
	<tr>
		<th>{TR_DATE}</th>
		<th>{TR_WEB_TRAFF}</th>
		<th>{TR_FTP_TRAFF}</th>
		<th>{TR_SMTP_TRAFF}</th>
		<th>{TR_POP_TRAFF}</th>
		<th>{TR_SUM}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td>{TR_ALL}</td>
		<td>{WEB_ALL}</td>
		<td>{FTP_ALL}</td>
		<td>{SMTP_ALL}</td>
		<td>{POP_ALL}</td>
		<td>{SUM_ALL}</td>
	</tr>
	</tfoot>
	<tbody>
	<!-- BDP: traffic_table_item -->
	<tr>
		<td>{DATE}</td>
		<td>{WEB_TRAFF}</td>
		<td>{FTP_TRAFF}</td>
		<td>{SMTP_TRAFF}</td>
		<td>{POP_TRAFF}</td>
		<td>{SUM_TRAFF}</td>
	</tr>
	<!-- EDP: traffic_table_item -->
	</tbody>
</table>
<!-- EDP: domain_statistics_block -->
