
			<table class="firstColFixed">
				<tr>
					<th>{TR_LOGO_FILE}</th>
				</tr>
				<!-- BDP: logo_remove_button -->
				<tr>
					<td style="text-align:center;">
						<img src="{OWN_LOGO}" alt="reseller logo"/>
						<form method="post" action="layout.php">
							<div class="buttons">
								<input type="hidden" name="uaction" value="deleteIspLogo"/>
								<input name="submit" type="submit" value="{TR_REMOVE}"/>
							</div>
						</form>
					</td>
				</tr>
				<!-- EDP: logo_remove_button -->
				<tr>
					<td>
						<form enctype="multipart/form-data" name="set_layout" method="post" action="layout.php">
							<input type="file" name="logoFile"/>

							<div class="buttons" style="display: inline;">
								<input type="hidden" name="uaction" value="updateIspLogo"/>
								<input name="submit" type="submit" value="{TR_UPLOAD}"/>
							</div>
						</form>
					</td>
				</tr>
			</table>
			<!-- BDP: layout_colors_block -->
			<form class="layoutColor" method="post" action="layout.php">
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
							<input name="submit" type="submit" class="button" value="{TR_CHANGE}"/>
						</td>
					</tr>
				</table>
				<input type="hidden" name="uaction" value="changeLayoutColor"/>
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
