
			<!-- BDP: layout_colors_block -->
			<form method="post" action="layout.php">
				<table class="firstColFixed">
					<tr>
						<th colspan="2">{TR_LAYOUT_COLOR}</th>
					</tr>
					<tr>
						<td><label for="layoutColor">{TR_CHOOSE_LAYOUT_COLOR}</label></td>
						<td>
							<select name="layoutColor" id="layoutColor">
								<!-- BDP: layout_color_block -->
								<option value="{COLOR}" {SELECTED_COLOR}>{COLOR}</option>
								<!-- EDP: layout_color_block -->
							</select>
							<input type="hidden" name="uaction" value="changeLayoutColor"/>
							<input name="submit" type="submit" value="{TR_CHANGE}"/>
						</td>
					</tr>
				</table>
			</form>
			<!-- EDP: layout_colors_block -->
			<form name="otherSettings" method="post" action="layout.php">
				<table class="firstColFixed">
					<tr>
						<th colspan="2">{TR_OTHER_SETTINGS}</th>
					</tr>
					<tr>

						<td><label for="mainMenuShowLabels">{TR_MAIN_MENU_SHOW_LABELS}</label></td>
						<td>
							<select name="mainMenuShowLabels" id="mainMenuShowLabels">
								<option value="0"{MAIN_MENU_SHOW_LABELS_OFF}>{TR_DISABLED}</option>
								<option value="1"{MAIN_MENU_SHOW_LABELS_ON}>{TR_ENABLED}</option>
							</select>
							<input type="hidden" name="uaction" value="changeShowLabels"/>
							<input name="submit" type="submit" value="{TR_CHANGE}"/>
						</td>
					</tr>
				</table>
			</form>
