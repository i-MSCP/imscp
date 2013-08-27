
<table class="firstColFixed">
	<thead>
	<tr>
		<th colspan="2">{TR_SYSTEM_INFO}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>{TR_KERNEL}</td>
		<td>{KERNEL}</td>
	</tr>
	<tr>
		<td>{TR_UPTIME}</td>
		<td>{UPTIME}</td>
	</tr>
	<tr>
		<td>{TR_LOAD}</td>
		<td>{LOAD}</td>
	</tr>
	</tbody>
</table>

<h2 class="system_cpu"><span>{TR_CPU_INFO}</span></h2>

<table class="firstColFixed">
	<thead>
	<tr>
		<th colspan="2">{TR_CPU}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>{TR_CPU_MODEL}</td>
		<td>{CPU_MODEL}</td>
	</tr>
	<tr>
		<td>{TR_CPU_CORES}</td>
		<td>{CPU_CORES}</td>
	</tr>
	<tr>
		<td>{TR_CPU_CLOCK_SPEED}</td>
		<td>{CPU_CLOCK_SPEED}</td>
	</tr>
	<tr>
		<td>{TR_CPU_CACHE}</td>
		<td>{CPU_CACHE}</td>
	</tr>
	<tr>
		<td>{TR_CPU_BOGOMIPS}</td>
		<td>{CPU_BOGOMIPS}</td>
	</tr>
	</tbody>
</table>

<h2 class="tools"><span>{TR_MEMORY_INFO}</span></h2>

<table class="firstColFixed">
	<thead>
	<tr>
		<th>{TR_RAM}</th>
		<th>{TR_TOTAL}</th>
		<th>{TR_USED}</th>
		<th>{TR_FREE}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>&nbsp;</td>
		<td>{RAM_TOTAL}</td>
		<td>{RAM_USED}</td>
		<td>{RAM_FREE}</td>
	</tr>
	</tbody>
</table>

<table class="firstColFixed">
	<thead>
	<tr>
		<th>{TR_SWAP}</th>
		<th>{TR_TOTAL}</th>
		<th>{TR_USED}</th>
		<th>{TR_FREE}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>&nbsp;</td>
		<td>{SWAP_TOTAL}</td>
		<td>{SWAP_USED}</td>
		<td>{SWAP_FREE}</td>
	</tr>
	</tbody>
</table>

<h2 class="tools"><span>{TR_FILE_SYSTEM_INFO}</span></h2>

<table class="firstColFixed">
	<thead>
	<tr>
		<th>{TR_MOUNT}</th>
		<th>{TR_TYPE}</th>
		<th>{TR_PARTITION}</th>
		<th>{TR_PERCENT}</th>
		<th>{TR_FREE}</th>
		<th>{TR_USED}</th>
		<th>{TR_SIZE}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: device_block -->
	<tr>
		<td>{MOUNT}</td>
		<td>{TYPE}</td>
		<td>{PARTITION}</td>
		<td>{PERCENT}%</td>
		<td>{FREE}</td>
		<td>{USED}</td>
		<td>{SIZE}</td>
	</tr>
	<!-- EDP: device_block -->
	</tbody>
</table>
