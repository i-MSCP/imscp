<script type="text/javascript">
	/*<![CDATA[*/
	var nbLog = {NB_LOG};

	$(document).ready(function () {
		$(".logtable").hide();
		var h = ($("body").height() - 300) / nbLog;
		if(h < 150) h = 200;
		$(".log").height(h);
		$(".logtable").show();
	});
	/*]]>*/
</script>

<!-- BDP: antirootkits_log -->
<table class="logtable">
	<thead>
	<tr>
		<th>{FILENAME}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td><div class="log" style="margin:0;padding:0;overflow: auto;height: 300px;">{LOG}</div></td>
	</tr>
	</tbody>
</table>
<!-- EDP: antirootkits_log -->
