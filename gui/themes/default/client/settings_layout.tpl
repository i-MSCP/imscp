
		<div class="body">
			<h2 class="layout"><span>{TR_LAYOUT_SETTINGS}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<!-- BDP: layout_colors_block -->
			<form class="layoutColor" method="post" action="settings_layout.php">
				<table>
					<tr>
						<th colspan="2">{TR_LAYOUT_COLOR}</th>
					</tr>
					<tr>
						<td style="width: 250px;"><label for="layoutColor">{TR_CHOOSE_LAYOUT_COLOR}</label></td>
						<td>
							<select name="layoutColor" id="layoutColor">
								<!-- BDP: layout_color_block -->
								<option value="{COLOR}" {SELECTED_COLOR}>{COLOR}</option>
								<!-- EDP: layout_color_block -->
							</select>
							<input name="submit" type="submit" value="{TR_CHANGE}"/>
						</td>
					</tr>
				</table>
				<input type="hidden" name="uaction" value="changeLayoutColor"/>
			</form>
			<!-- EDP: layout_colors_block -->
		</div>
