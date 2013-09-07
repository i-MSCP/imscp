
<div id="login">
	<form name="lostpasswordFrm" action="lostpassword.php" method="post">
		<table>
			<tr>
				<td colspan="2" class="center">
					<a href="lostpassword.php" title="{GET_NEW_IMAGE}">{TR_IMGCAPCODE}</a>
				</td>
			</tr>
			<tr>
				<td class="left"><label for="uname">{TR_USERNAME}</label></td>
				<td class="right"><input type="text" name="uname" id="uname" tabindex="1"/></td>
			</tr>
			<tr>
				<td class="left"><label for="capcode">{TR_CAPCODE}</label></td>
				<td class="right"><input type="text" name="capcode" id="capcode" tabindex="2"/></td>
			</tr>
			<tr>
				<td colspan="2" class="right">
					<button name="submit" type="submit" tabindex="3">{TR_SEND}</button>
					<a class="link_as_button" href="index.php" tabindex="4">{TR_CANCEL}</a>
				</td>
			</tr>
		</table>
	</form>
</div>
