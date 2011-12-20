
			<table>
				<tr>
					<th>{TR_LOGO_FILE}</th>
				</tr>
				<!-- BDP: logo_remove_button -->
				<tr>
					<td style="text-align:center;">
						<form name="ispLogoDeleteFrm" method="post" enctype="application/x-www-form-urlencoded" action="layout.php">
							<img src="{OWN_LOGO}" title="ISP Logo" alt="ISP Logo" />
							<div class="buttons">
								<input type="hidden" name="uaction" value="deleteIspLogo" />
								<input name="submit" type="submit" value="{TR_REMOVE}" />
							</div>
						</form>
					</td>
				</tr>
				<!-- EDP: logo_remove_button -->
				<tr>
					<td>
						<form name="ispLogoUpdateFrm" method="post" enctype="multipart/form-data" action="layout.php">
							<input type="file" name="logoFile" />
							<div class="buttons" style="display: inline;">
								<input type="hidden" name="uaction" value="updateIspLogo" />
								<input name="submit" type="submit" value="{TR_UPLOAD}" />
							</div>
						</form>
					</td>
				</tr>
			</table>
			<!-- BDP: layout_colors_block -->
			<form class="layoutColor" method="post" action="layout.php">
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
