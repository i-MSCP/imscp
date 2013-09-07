
<!-- BDP: layout_colors_block -->
<form method="post" action="layout.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_LAYOUT_COLOR}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="layoutColor">{TR_CHOOSE_LAYOUT_COLOR}</label></td>
			<td>
				<select name="layoutColor" id="layoutColor">
					<!-- BDP: layout_color_block -->
					<option value="{COLOR}" {SELECTED_COLOR}>{COLOR}</option>
					<!-- EDP: layout_color_block -->
				</select>
			</td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input type="hidden" name="uaction" value="changeLayoutColor"/>
		<input name="submit" type="submit" value="{TR_UPDATE}"/>
	</div>
</form>
<!-- EDP: layout_colors_block -->

<form method="post" action="layout.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_OTHER_SETTINGS}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="mainMenuShowLabels">{TR_MAIN_MENU_SHOW_LABELS}</label></td>
			<td>
				<select name="mainMenuShowLabels" id="mainMenuShowLabels">
					<option value="0"{MAIN_MENU_SHOW_LABELS_OFF}>{TR_DISABLED}</option>
					<option value="1"{MAIN_MENU_SHOW_LABELS_ON}>{TR_ENABLED}</option>
				</select>
			</td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input type="hidden" name="uaction" value="changeShowLabels"/>
		<input name="submit" type="submit" value="{TR_UPDATE}"/>
	</div>
</form>
