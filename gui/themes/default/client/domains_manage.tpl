<!-- INCLUDE "header.tpl" -->
<body>
	<script type="text/javascript">
	/* <![CDATA[ */
		$(document).ready(function(){
			// TableSorter - begin
			$('.tablesorter').tablesorter({cssHeader: 'tablesorter'});
			// TableSorter - end
		});

		function action_delete(url, subject) {
			if (!confirm(sprintf("{TR_MESSAGE_DELETE}", subject)))
				return false;
			location = url;
		}
	/* ]]> */
	</script>
	<div class="header">
		{MAIN_MENU}
		<div class="logo">
			<img src="{ISP_LOGO}" alt="i-MSCP logo" />
		</div>
	</div>
	<div class="location">
		<div class="location-area">
			<h1 class="domains">{TR_MENU_MANAGE_DOMAINS}</h1>
		</div>
		<ul class="location-menu">
			<!-- BDP: logged_from -->
			<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
			<!-- EDP: logged_from -->
			<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
		</ul>
		<ul class="path">
			<li><a href="domains_manage.php">{TR_MENU_MANAGE_DOMAINS}</a></li>
			<li><a href="domains_manage.php">{TR_MENU_OVERVIEW}</a></li>
		</ul>
	</div>
	<div class="left_menu">{MENU}</div>
	<div class="body">
		<!-- BDP: page_message -->
		<div class="{MESSAGE_CLS}">{MESSAGE}</div>
		<!-- EDP: page_message -->
		<h2 class="domains"><span>{TR_DOMAIN_ALIASES}</span></h2>
		<!-- BDP: als_message -->
		<div class="info">{ALS_MSG}</div>
		<!-- EDP: als_message -->
		<!-- BDP: als_list -->
		<table class="tablesorter">
			<thead>
				<tr>
					<th>{TR_ALS_NAME}</th>
					<th>{TR_ALS_MOUNT}</th>
					<th>{TR_ALS_FORWARD}</th>
					<th>{TR_ALS_STATUS}</th>
					<th>{TR_ALS_ACTION}</th>
				</tr>
			</thead>
			<tbody>
				<!-- BDP: als_item -->
				<tr>
					<!-- BDP: als_status_reload_true -->
					<td><a href="http://{ALS_NAME}/" class="icon i_domain_icon" title="{ALS_NAME}">{ALS_NAME}</a></td>
					<!-- EDP: als_status_reload_true -->
					<!-- BDP: als_status_reload_false -->
					<td><span class="icon i_domain_icon" title="{ALS_NAME}">{ALS_NAME}</span></td>
					<!-- EDP: als_status_reload_false -->
					<td>{ALS_MOUNT}</td>
					<td>{ALS_FORWARD}</td>
					<td>{ALS_STATUS}</td>
					<td>
						<a class="icon i_edit" href="{ALS_EDIT_LINK}" title="{ALS_EDIT}"></a>
						<a class="icon i_delete" href="#" onclick="action_delete('{ALS_ACTION_SCRIPT}', '{ALS_NAME}')" title="{ALS_ACTION}"></a>
					</td>
				</tr>
				<!-- EDP: als_item -->
			</tbody>
		</table>
		<!-- EDP: als_list -->
		<h2 class="domains"><span>{TR_SUBDOMAINS}</span></h2>
		<!-- BDP: sub_message -->
		<div class="info">{SUB_MSG}</div>
		<!-- EDP: sub_message -->
		<!-- BDP: sub_list -->
		<table class="tablesorter">
			<thead>
				<tr>
					<th>{TR_SUB_NAME}</th>
					<th>{TR_SUB_MOUNT}</th>
					<th>{TR_SUB_STATUS}</th>
					<th>{TR_SUB_ACTION}</th>
				</tr>
			</thead>
			<tbody>
				<!-- BDP: sub_item -->
				<tr>
					<!-- BDP: status_reload_true -->
					<td><a href="http://{SUB_NAME}.{SUB_ALIAS_NAME}/" class="icon i_domain_icon" title="{SUB_NAME}.{SUB_ALIAS_NAME}">{SUB_NAME}.{SUB_ALIAS_NAME}</a></td>
					<!-- EDP: status_reload_true -->
					<!-- BDP: status_reload_false -->
					<td><span class="icon i_domain_icon" title="{SUB_NAME}.{SUB_ALIAS_NAME}">{SUB_NAME}.{SUB_ALIAS_NAME}</span></td>
					<!-- EDP: status_reload_false -->
					<td>{SUB_MOUNT}</td>
					<td>{SUB_STATUS}</td>
					<td>
						<a class="icon i_edit" href="{SUB_EDIT_LINK}" title="{SUB_EDIT}"></a>
						<a class="icon i_delete" href="#" onclick="action_delete('{SUB_ACTION_SCRIPT}', '{SUB_NAME}')"></a>
					</td>
				</tr>
				<!-- EDP: sub_item -->
			</tbody>
		</table>
		<!-- EDP: sub_list -->
		<!-- BDP: isactive_dns -->
		<h2 class="domains"><span>{TR_DNS}</span></h2>
		<!-- BDP: dns_message -->
		<div class="info">{DNS_MSG}</div>
		<!-- EDP: dns_message -->
		<!-- BDP: dns_list -->
		<table class="tablesorter">
			<thead>
				<tr>
					<th>{TR_DOMAIN_NAME}</th>
					<th>{TR_DNS_NAME}</th>
					<th>{TR_DNS_CLASS}</th>
					<th>{TR_DNS_TYPE}</th>
					<th>{TR_DNS_DATA}</th>
					<th>{TR_DNS_ACTION}</th>
				</tr>
			</thead>
			<tbody>
				<!-- BDP: dns_item -->
				<tr>
					<td><span class="icon i_domain_icon">{DNS_DOMAIN}</span></td>
					<td>{DNS_NAME}</td>
					<td>{DNS_CLASS}</td>
					<td>{DNS_TYPE}</td>
					<td>{DNS_DATA}</td>
					<td>
						<a class="icon i_edit" href="{DNS_ACTION_SCRIPT_EDIT}" title="{DNS_ACTION_EDIT}"></a>
						<a href="#" class="icon i_delete" onclick="action_delete('{DNS_ACTION_SCRIPT_DELETE}', '{DNS_TYPE_RECORD}')" title="{DNS_ACTION_DELETE}"></a>
					</td>
				</tr>
				<!-- EDP: dns_item -->
			</tbody>
		</table>
		<!-- EDP: dns_list -->
		<!-- EDP: isactive_dns -->
	</div>
<!-- INCLUDE "footer.tpl" -->