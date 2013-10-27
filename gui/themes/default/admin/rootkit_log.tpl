<script type="text/javascript">
	/*<![CDATA[*/
	$(document).ready(function () {
		$(".logtable").hide();
		var h = $("body").height() - 300;
		$(".log").height(h/2);
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
