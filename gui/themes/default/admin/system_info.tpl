
			<!-- BDP: props_list -->
			<table class="firstColFixed">
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
			</table>

			<!-- EDP: props_list -->
			<h2 class="system_cpu"><span>{TR_CPU_SYSTEM_INFO}</span></h2>

			<table class="firstColFixed">
				<tr>
					<td>{TR_CPU_MODEL}</td>
					<td>{CPU_MODEL}</td>
				</tr>
				<tr>
					<td>{TR_CPU_COUNT}</td>
					<td>{CPU_COUNT}</td>
				</tr>
				<tr>
					<td>{TR_CPU_MHZ}</td>
					<td>{CPU_MHZ}</td>
				</tr>
				<tr>
					<td>{TR_CPU_CACHE}</td>
					<td>{CPU_CACHE}</td>
				</tr>
				<tr>
					<td>{TR_CPU_BOGOMIPS}</td>
					<td>{CPU_BOGOMIPS}</td>
				</tr>
			</table>

			<h2 class="tools"><span>{TR_MEMRY_SYSTEM_INFO}</span></h2>

			<table class="firstColFixed">
				<tr>
					<th>{TR_RAM}</th>
					<th>{TR_TOTAL}</th>
					<th>{TR_USED}</th>
					<th>{TR_FREE}</th>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>{RAM_TOTAL}</td>
					<td>{RAM_USED}</td>
					<td>{RAM_FREE}</td>
				</tr>
			</table>

			<table class="firstColFixed">
				<tr>
					<th>{TR_SWAP}</th>
					<th>{TR_TOTAL}</th>
					<th>{TR_USED}</th>
					<th>{TR_FREE}</th>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>{SWAP_TOTAL}</td>
					<td>{SWAP_USED}</td>
					<td>{SWAP_FREE}</td>
				</tr>
			</table>

			<h2 class="tools"><span>{TR_FILE_SYSTEM_INFO}</span></h2>

			<!-- BDP: disk_list -->
			<table class="firstColFixed">
				<tr>
					<th>{TR_MOUNT}</th>
					<th>{TR_TYPE}</th>
					<th>{TR_PARTITION}</th>
					<th>{TR_PERCENT}</th>
					<th>{TR_FREE}</th>
					<th>{TR_USED}</th>
					<th>{TR_SIZE}</th>
				</tr>
				<!-- BDP: disk_list_item -->
				<tr>
					<td>{MOUNT}</td>
					<td>{TYPE}</td>
					<td>{PARTITION}</td>
					<td>{PERCENT}%</td>
					<td>{FREE}</td>
					<td>{USED}</td>
					<td>{SIZE}</td>
				</tr>
				<!-- EDP: disk_list_item -->
			</table>
			<!-- EDP: disk_list -->
