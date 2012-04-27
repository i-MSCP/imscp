    <script type="text/javascript">
	/* <![CDATA[ */
        $(document).ready(function(){
            var i = $('#relay_lines tr').length + 1;

        	$('#add').click(function() {
                switch ($('#relay_type_add').val()) {
                    case "mx_add":
                        var str_mx = '<tr class="relay_new_field">';
                        str_mx += '<td>{TR_MX}<input type="hidden" name="relay_type[]" id="relay_type_' + i + '" value="mx" /></td>';
                        str_mx += '<td><select name="mx_domain_dns[]" id="mx_domain_dns_' + i + '"><option value="empty">&nbsp;</option><option value="*">*</option></select></td>';
                        str_mx += '<td><input type="text" name="mx_priority[]" id="mx_priority_' + i + '" value="{MX_PRIORITY}" /></td>';
                        str_mx += '<td><input type="text" name="domain_text[]" id="domain_text_' + i + '" value="{DOMAIN_TEXT}" /></td>';
                        str_mx += '</tr>';
                        $(".inputs tbody").append(str_mx);
                        break;
                    case "cname_add":
                        var str_cname = '<tr class="relay_new_field">';
                        str_cname += '<td>{TR_CNAME}<input type="hidden" name="relay_type[]" id="relay_type_' + i + '" value="cname" /></td>';
                        str_cname += '<td><input type="text" name="cname_domain_dns[]" id="cname_domain_dns_' + i + '" value="{CNAME_DOMAIN_DNS}" /></td>';
                        str_cname += '<td><input type="text" name="cname_priority[]" id="cname_priority_' + i + '" value="{CNAME_PRIORITY}" disabled /></td>';
                        str_cname += '<td><input type="text" name="domain_text[]" id="domain_text_' + i + '" value="{DOMAIN_TEXT}" /></td>';
                        str_cname += '</tr>';
                        $(".inputs tbody").append(str_cname);
                        break;
                }


        		i++;
        	})
            $('#remove').click(function() {
                if(i > 1) {
                    $('.relay_new_field:last').remove();
                    i--;
                }
        	});

        	$('#reset').click(function() {
                while(i > 2) {
                    $('.relay_new_field:last').remove();
                    i--;
                }
        	});
        });
		function changeType(what) {
			if (what == "mx") {
				document.forms[0].mx_domain_dns.disabled = false;
                document.forms[0].mx_domain_dns.style.display = 'inline';
                document.forms[0].mx_priority.style.display = 'inline';
				document.forms[0].cname_domain_dns.disabled = true;
                document.forms[0].cname_domain_dns.style.display = 'none';
                document.forms[0].cname_priority.style.display = 'none';
			} else {
				document.forms[0].mx_domain_dns.disabled = true;
                document.forms[0].mx_domain_dns.style.display = 'none';
                document.forms[0].mx_priority.style.display = 'none';
				document.forms[0].cname_domain_dns.disabled = false;
                document.forms[0].cname_domain_dns.style.display = 'inline';
                document.forms[0].cname_priority.style.display = 'inline';
			}
		}

		$(window).load(function() {changeType('{DEFAULT}');});
	/* ]]> */
    </script>
    <table>
        <thead>
            <tr>
                <th>{TR_RELAY_TYPE}</th>
                <th>{TR_ACTION}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <select name="relay_type_add" id="relay_type_add">
                        <option value="mx_add">{TR_MX}</option>
                        <option value="cname_add">{TR_CNAME}</option>
                    </select>
                </td>
                <td>
                    <a href="#" id="add">{TR_ADD_NEW}</a> | <a href="#" id="remove">{TR_REMOVE_LAST}</a>  | <a href="#" id="reset">{TR_RESET}</a>
                </td>
            </tr>
        </tbody>
    </table>
	<form name="create_relay_frm" method="post" action="mail_external_add.php">
		<table class="inputs">
            <thead>
                <tr>
                    <th>{TR_RELAY_TYPE}</th>
                    <th>{TR_RELAY_DNS}</th>
                    <th>{TR_MX_PRIORITY}</th>
                    <th>{TR_RELAY_SERVER}</th>
                </tr>
            </thead>
            <tbody id="relay_lines">
                <tr>
                    <td>
                        <select name="relay_type[]" onchange="changeType(this.value);">
                            <option value="mx">{TR_MX}</option>
                            <option value="cname">{TR_CNAME}</option>
                        </select>
                    </td>
                    <td>
                        <select name="mx_domain_dns[]" id="mx_domain_dns">
                            <option value="empty">&nbsp;</option>
                            <option value="*">*</option>
                        </select>
                        <input type="text" name="cname_domain_dns[]" id="cname_domain_dns" value="{CNAME_DOMAIN_DNS}" />
                    </td>
                    <td>
                        <input type="text" name="mx_priority[]" id="mx_priority" value="{MX_PRIORITY}" />
                        <input type="text" name="cname_priority[]" id="cname_priority" value="{CNAME_PRIORITY}" disabled />
                    </td>
                    <td><input type="text" name="domain_text[]" id="domain_text" value="{DOMAIN_TEXT}" /></td>
                </tr>
            </tbody>
		</table>

		<div class="buttons">
			<input type="hidden" name="uaction" value="add_external_mail"/>
			<input type="hidden" name="id" value="{ID}"/>
			<input name="Submit" type="submit" value="{TR_CREATE_RELAY}"/>
		</div>
	</form>
