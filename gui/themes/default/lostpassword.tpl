
	<div id="body">
		<div class="clearfix">
			<div id="loginBox">
				<form name="lostpasswordFrm" action="lostpassword.php" method="post">
					<span><a href="lostpassword.php" title="{GET_NEW_IMAGE}">{TR_IMGCAPCODE}</a></span>
					<label for="capcode"><div>{TR_CAPCODE}</div><input type="text" name="capcode" id="capcode" tabindex="1"/></label>
					<label for="uname"><div>{TR_USERNAME}</div><input type="text" name="uname" id="uname" tabindex="2"/></label>
					<div class="button">
						<button name="lostpwd" type="button" onclick="location.href='index.php';" tabindex="4">{TR_CANCEL}</button>
						<button name="submit" type="submit" tabindex="3">{TR_SEND}</button>
					</div>
				</form>
			</div>
		</div>
	</div>
