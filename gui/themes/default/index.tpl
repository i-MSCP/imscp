
<div id="login">
	<form name="login" action="index.php" method="post">
		<table>
			<tr>
				<td class="left"><label for="uname">{TR_USERNAME}</label></td>
				<td class="right"><input type="text" name="uname" id="uname" value="{UNAME}"/></td>
			</tr>
			<tr>
				<td class="left"><label for="upass">{TR_PASSWORD}</label></td>
				<td class="right"><input type="password" name="upass" id="upass" value="" autocomplete="off"/></td>
			</tr>
			<tr>
				<td colspan="2" class="right">
					<!-- BDP: lost_password_support -->
					<a class="link_as_button" href="lostpassword.php">{TR_LOSTPW}</a>
					<!-- EDP: lost_password_support -->
					<button type="submit" name="submit" tabindex="3">{TR_LOGIN}</button>
				</td>
			</tr>
			<!-- BDP: ssl_support -->
			<tr>
				<td colspan="2" class="center">
					<a class="icon {SSL_IMAGE_CLASS}" href="{SSL_LINK}" title="{TR_SSL_DESCRIPTION}">{TR_SSL}</a>
				</td>
			</tr>
			<!-- EDP: ssl_support -->
		</table>

		<input type="hidden" name="action" value="login">
	</form>
</div>
