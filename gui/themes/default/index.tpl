
		<div class="clearfix">
			<div id="loginBox">
				<form name="loginFrm" action="index.php" method="post">
					<label for="uname"><span>{TR_USERNAME}</span><input type="text" name="uname" id="uname" tabindex="1"/></label>
					<label for="upass"><span>{TR_PASSWORD}</span><input type="password" name="upass" id="upass" tabindex="2"/></label>
					<div class="buttons">
						<!-- BDP: lostpwd_button -->
						<button name="lostpwd" type="button" tabindex="4" onclick="location.href='lostpassword.php'">{TR_LOSTPW}</button>
						<!-- EDP: lostpwd_button -->
						<button  name="login" type="submit" tabindex="3">{TR_LOGIN}</button>
					</div>
					<!-- BDP: ssl_support -->
					<a style="float:none;" class="icon {SSL_IMAGE_CLASS}" href="{SSL_LINK}" title="{TR_SSL_DESCRIPTION}">{TR_SSL}</a>
					<!-- EDP: ssl_support -->
				</form>
			</div>
		</div>
		<div id="toolbox">
			<ul>
				<li><a class="icon_big pma" href="{TR_PMA_LINK}" target="blank" title="{TR_LOGIN_INTO_PMA}">{TR_PHPMYADMIN}</a></li>
				<li><a class="icon_big filemanager" href="{TR_FTP_LINK}" target="blank" title="{TR_LOGIN_INTO_FMANAGER}">FileManager</a></li>
				<li><a class="icon_big webmail" href="{TR_WEBMAIL_LINK}" target="blank" title="{TR_LOGIN_INTO_WEBMAIL}">{TR_WEBMAIL}</a></li>
			</ul>
		</div>
