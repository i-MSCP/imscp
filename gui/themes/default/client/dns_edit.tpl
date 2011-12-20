
<script type="text/javascript">
	/* <![CDATA[ */
	$(document).ready(function(){

		$('#dialog_box').dialog({
			modal: true, width:'700',autoOpen:false, hide:'blind', show:'blind', dialogClass:'body', height:'auto',
			buttons: {Ok: function(){$(this).dialog('close');}}
		});

		var datatable;
		$('#showZone').click(function() {
			var zone = $("#zone option[value='"+$('#zone').val()+"']").text();
			$.getJSON('dns_edit.php', {zone:zone}, function(rr) {
				if(datatable) {
					datatable.fnDestroy();
				}
				$('#rr_table_body').remove();
				if($.isArray(rr)) {
					$.each(rr, function() {
						var rrItems = [];
						rrItems.push('<td>' + this.name + '</td>');
						rrItems.push('<td>' + this.ttl + '</td>');
						rrItems.push('<td>' + this['class'] + '</td>');
						rrItems.push('<td>' + this.type + '</td>');

						var rdata = [];

						if(this.type == 'SOA') {
							rdata.push(this.mname, this.rname, this.serial, this.refresh, this.expire, this.minimum);
						} else if(this.type == 'MX') {
							rdata.push(this.preference, this.exchange);
						} else {
							rdata.push(this.text || this.cname || this.address || this.nsdname);
						}

						rrItems.push('<td>' + rdata.join(' ') + '</td>');
						$('<tbody/>', {'id': 'rr_table_body', html: '<tr>' + rrItems.join(' ') + '</tr>'}).appendTo('#rr_table');
					});

					datatable = $('#rr_table').dataTable({"oLanguage": {DATATABLE_TRANSLATIONS}});

					$('#rr_table').css('width', '100%');
					$('#dialog_box').dialog("option", "title", 'Zone ' + zone).dialog('open');
				} else {
					alert(rr);
				}
			}).error(function() {
				alert({UNEXPECTED_ERROR});
			});
		});

		$('#rr').change(function(){
			var v = $(this).val();
			if (v == 'A') {
				$('#tr_protocol, #tr_priority, #tr_weight, #tr_port, #tr_host, #tr_aaaa').hide();
				$('#tr_a').show();
			} else if (v == 'AAAA') {
				$('#tr_protocol, #tr_priority, #tr_weight, #tr_port, #tr_host, #tr_a').hide();
				$('#tr_aaaa').show();
			} else if (v == 'SRV') {
				$('#tr_a, #tr_aaaa').hide();
				$('#tr_protocol, #tr_priority, #tr_weight, #tr_port').show();
				$('#tr_host').show().children().first().text({TR_JHOST});
			} else if (v == 'CNAME') {
				$('#tr_protocol, #tr_priority, #tr_weight, #tr_port, [id^=tr_a]').hide();
				$('#tr_host').show().children().first().text({TR_JCNAME});
			} else if (v == 'MX') {
				$('#tr_protocol, #tr_weight, #tr_port, [id^=tr_a]').hide();
				$('#tr_priority').show();
				$('#tr_host').show().children().first().text({TR_JHOST});
			}

			$('tr').trigger('change');
		});

		$('#rr').trigger('change');
	});
	/*]]>*/
</script>
<div class="body">
	<h2 class="domains"><span>{TR_TITLE_CUSTOM_DNS_RECORD}</span></h2>

	<!-- BDP: page_message -->
	<div class="{MESSAGE_CLS}">{MESSAGE}</div>
	<!-- EDP: page_message -->

	<form name="dnsFrm" method="post" action="{ACTION_MODE}">
		<table>
			<tr>
				<th colspan="2">
					{TR_CUSTOM_DNS_RECORD}
				</th>
			</tr>
			<!-- BDP: add_block1 -->
			<tr>
				<td><label for="zone">{TR_ZONE}</label></td>
				<td>
					<select name="zone" id="zone">
						<!-- BDP: zone_block -->
						<option value="{ZONE_TYPE}[{ZONE_ID}]"{SELECTED_ZONE}>{ZONE_NAME}</option>
						<!-- EDP: zone_block -->
					</select>
					<div id="dialog_box" style="margin:0;display: none;">
						<table id="rr_table" class="datatable">
							<thead>
							<tr>
								<th>{TR_NAME}</th>
								<th>{TR_TTL}</th>
								<th>{TR_CLASS}</th>
								<th>{TR_TYPE}</th>
								<th>{TR_RECORD_DATA}</th>
							</tr>
							</thead>
						</table>
						<span class="bold">Note:</span> <span>Only DNS resource records of class IN are showed.</span>
					</div>
					<button type="button" id="showZone" title="Show all zone resource records">Show zone</button>
				</td>
			</tr>
			<!-- EDP: add_block1 -->
			<tr>
				<td><label for="name">{TR_NAME}</label></td>
				<td><input type="text" name="name" id="name" value="{NAME}"/></td>
			</tr>
			<tr id="tr_protocol">
				<td><label for="protocol">{TR_PROTOCOL}</label></td>
				<td>
					<select name="protocol" id="protocol">
						<!-- BDP: protocol_block -->
						<option value="{PROTOCOL}"{SELECTED_PROTOCOL}>{TR_PROTOCOL_VALUE}</option>
						<!-- EDP: protocol_block -->
					</select>
				</td>
			</tr>
			<tr>
				<td><label for="ttl">{TR_TTL}</label></td>
				<td>
					<input type="text" name="ttl" id="ttl"  value="{TTL}"/>
				</td>
			</tr>
			<tr>
				<td><label for="class">{TR_CLASS}</label></td>
				<td>
					<select name="class" id="class">
						<!-- BDP: class_block -->
						<option value="{CLASS}"{SELECTED_CLASS}>{CLASS}</option>
						<!-- EDP: class_block -->
					</select>
				</td>
			</tr>
			<tr>
				<td><label for="rr">{TR_TYPE}</label></td>
				<td>
					<select name="rr" id="rr">
						<!-- BDP: rr_block -->
						<option value="{RR}"{SELECTED_RR}>{RR}</option>
						<!-- EDP: rr_block -->
					</select>
				</td>
			</tr>
			<tr id="tr_priority">
				<td><label for="priority">{TR_PRIORITY}</label></td>
				<td>
					<input type="text" name="priority" id="priority" value="{PRIORITY}"/>
				</td>
			</tr>
			<tr id="tr_weight">
				<td><label for="weight">{TR_WEIGHT}</label></td>
				<td>
					<input type="text" name="weight" id="weight"  value="{WEIGHT}"/>
				</td>
			</tr>
			<tr id="tr_port">
				<td><label for="port">{TR_PORT}</td>
				<td>
					<input type="text" name="port" id="port"  value="{PORT}"/>
				</td>
			</tr>
			<tr id="tr_host">
				<td><label for="host">{TR_HOST}</label></td>
				<td>
					<input type="text" name="host" id="host" value="{HOST}"/>
				</td>
			</tr>
			<tr id="tr_a">
				<td><label for="a">{TR_A}</label></td>
				<td>
					<input type="text" name="a" id="a" value="{A}"/>
				</td>
			</tr>
			<tr id="tr_aaaa">
				<td><label for="aaaa">{TR_AAAA}</td>
				<td>
					<input type="text" name="aaaa" id="aaaa" value="{AAAA}"/>
				</td>
			</tr>
		</table>
		<div class="buttons">
			<!-- BDP: add_block2 -->
			<input name="submit" type="submit" value="{TR_ADD}"/>
			<input type="hidden" name="uaction" value="add"/>
			<!-- EDP: add_block2 -->

			<!-- BDP: edit_block -->
			<input name="submit" type="submit" value="{TR_UPDATE}"/>
			<input type="hidden" name="uaction" value="update"/>
			<!-- EDP: edit_block -->

			<input name="submit" type="submit" onclick="MM_goToURL('parent', 'domains_manage.php');return document.MM_returnValue" value="{TR_CANCEL}"/>
		</div>
	</form>
</div>

