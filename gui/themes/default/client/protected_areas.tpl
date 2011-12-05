<!-- INCLUDE "../shared/layout/header.tpl" -->
		<script type="text/javascript">
		/* <![CDATA[ */
			function action_delete(url, subject) {
				return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
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
				<h1 class="webtools">{TR_MENU_WEBTOOLS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="webtools.php">{TR_MENU_WEBTOOLS}</a></li>
				<li><a href="protected_areas.php">{TR_HTACCESS}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
			<h2 class="htaccess"><span>{TR_HTACCESS}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<!-- BDP: protected_areas -->
			<table>
				<thead>
					<tr>
						<th>{TR_HTACCESS}</th>
						<th>{TR_STATUS}</th>
						<th>{TR__ACTION}</th>
					</tr>
				</thead>
				<tbody>
					<!-- BDP: dir_item -->
						<tr>
							<td>{AREA_NAME}<br /><u>{AREA_PATH}</u></td>
							<td>{STATUS}</td>
							<td>
								<a href="protected_areas_add.php?id={PID}" class="icon i_edit">{TR_EDIT}</a>
								<a href="protected_areas_delete.php?id={PID}" onclick="return action_delete('protected_areas_delete.php?id={PID}', '{JS_AREA_NAME}')" class="icon i_delete">{TR_DELETE}</a>
							</td>

						</tr>
					<!-- EDP: dir_item -->
				</tbody>
			</table>
			<!-- EDP: protected_areas -->

			<div class="buttons">
				<input name="Button" type="button" onclick="MM_goToURL('parent','protected_areas_add.php');return document.MM_returnValue" value="{TR_ADD_AREA}" />
				<input name="Button2" type="button" onclick="MM_goToURL('parent','protected_user_manage.php');return document.MM_returnValue" value="{TR_MANAGE_USRES}" />
			</div>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
