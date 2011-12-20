
	    	<form action="settings_server_traffic.php" method="post" name="admin_modify_server_traffic_settings" id="admin_modify_server_traffic_settings">
		    		<table>
						<tr>
							<th colspan="2">
								{TR_SET_SERVER_TRAFFIC_SETTINGS}
							</th>
						</tr>
						<tr>
			    			<td>
                    			<label for="max_traffic">{TR_MAX_TRAFFIC}</label>
                			</td>
			    			<td>
								<input name="max_traffic" type="text" id="max_traffic" value="{MAX_TRAFFIC}" /> {TR_MIB}
			    			</td>
						</tr>
						<tr>
			    			<td><label for="traffic_warning">{TR_WARNING}</label></td>
			    			<td><input name="traffic_warning" type="text" id="traffic_warning" value="{TRAFFIC_WARNING}" /> {TR_MIB}</td>
						</tr>
		   	 		</table>
				<div class="buttons">
		    		<input name="Submit" type="submit" value="{TR_MODIFY}" />
		    		<input type="hidden" name="uaction" value="modify" />
				</div>
			</form>

