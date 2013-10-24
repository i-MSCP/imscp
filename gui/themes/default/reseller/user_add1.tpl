
<script language="JavaScript" type="text/JavaScript">
	/*<![CDATA[*/
	$(document).ready(function () {
		$('#datepicker').datepicker().change(
			function () {
				if ($(this).val() != '') {
					$('#never_expire').attr('disabled', 'disabled');
				} else {
					$(this).attr('disabled', 'disabled');
					$('#never_expire').prop('checked', true).removeAttr('disabled');
				}
			}
		).trigger('change');

		$('#never_expire').change(function () {
			if ($(this).is(':checked')) {
				$('#datepicker').attr('disabled', 'disabled')
			} else {
				$('#datepicker').removeAttr('disabled');
			}
		});
	});
	/*]]>*/
</script>

<!-- BDP: add_customer_block -->
<form name="reseller_add_users_first_frm" method="post" action="user_add1.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_CORE_DATA}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>
				<label for="dmn_name">{TR_DOMAIN_NAME}</label>
				<span class="tips icon i_help" id="dmn_help" title="{TR_DMN_HELP}"></span>
			</td>
			<td>
				<span class="bold">www .</span>
				<input type="text" name="dmn_name" id="dmn_name" value="{DOMAIN_NAME_VALUE}"/>
			</td>
		</tr>
		<tr>
			<td><label for="datepicker">{TR_DOMAIN_EXPIRE}</label></td>
			<td>
				<input type="text" name="datepicker" id="datepicker" value="{DATEPICKER_VALUE}"/>
				<input type="checkbox" name="never_expire" id="never_expire" value="0"/>
				<label for="never_expire">{TR_EXPIRE_CHECKBOX}</label>
			</td>
		</tr>
		<!-- BDP: hosting_plan_entries_block -->
		<tr>
			<td><label for="dmn_tpl">{TR_CHOOSE_HOSTING_PLAN}</label></td>
			<td>
				<select id="dmn_tpl" name="dmn_tpl">
					<!-- BDP: hosting_plan_entry_block -->
					<option value="{HP_ID}"{HP_SELECTED}>{HP_NAME}</option>
					<!-- EDP: hosting_plan_entry_block -->
				</select>
			</td>
		</tr>
		<!-- BDP: customize_hosting_plan_block -->
		<tr>
			<td>{TR_PERSONALIZE_TEMPLATE}</td>
			<td>
				<div class="radio">
					<input type="radio" id="chtpl_yes" name="chtpl" value="_yes_" {CHTPL1_VAL} />
					<label for="chtpl_yes">{TR_YES}</label>
					<input type="radio" id="chtpl_no" name="chtpl" value="_no_" {CHTPL2_VAL} />
					<label for="chtpl_no">{TR_NO}</label>
				</div>
			</td>
		</tr>
		<!-- EDP: customize_hosting_plan_block -->
		<!-- EDP: hosting_plan_entries_block -->
		</tbody>
	</table>

	<div class="buttons">
		<input name="Submit" type="submit" value="{TR_NEXT_STEP}"/>
		<input type="hidden" name="uaction" value="user_add_next"/>
	</div>
</form>
<!-- EDP: add_customer_block -->
