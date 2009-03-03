<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<title>{TR_MAIN_INDEX_PAGE_TITLE}</title>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
		<meta http-equiv="Content-Style-Type" content="text/css">
		<meta http-equiv="Content-Script-Type" content="text/javascript">
		<meta name="robots" content="nofollow, noindex">
		<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
	</head>

	<body onload="javascript:document.frm.uname.focus()">
		<table cellpadding="0" cellspacing="0" style="width:100%; height:100%">>
			<tr>
				<td style="vertical-align:middle; text-align:center;">
					<table style="width:453px; text-align:center; border:solid 1px #CCCCCC;" cellpadding="0" cellspacing="0">
						<tr>
							<td>
								<table width="453" border="0" align="center" cellpadding="0" cellspacing="0">
									<tr>
										<td width="453" height="69" class="loginTop">&nbsp;</td>
									</tr>
									<tr>
										<td>
											<form name="frm" action="lostpassword.php" method="post">
												<table width="453" border="0" cellspacing="0" cellpadding="0">
													<tr>
														<td width="45" rowspan="6"></td>
														<td colspan="2"><div class="login_text"><label for="capcode">{TR_IMGCAPCODE}<br><strong>{TR_CAPCODE}</strong></label></div></td>
													</tr>
													<tr>
														<td colspan="2"><input name="capcode" type="text" class="textinput" id="capcode" style="width:210px" value=""></td>
													</tr>
													<tr>
														<td colspan="6" class="imgcapcode_description_text"><p>{TR_IMGCAPCODE_DESCRIPTION}</p></td>
													</tr>
													<tr>
														<td width="106"><br></td>
													</tr>
													<tr>
														<td colspan="2">
															<div class="login_text"><label for="uname"><strong>{TR_USERNAME}</strong></label></div>
														</td>
													</tr>
													<tr>
														<td colspan="2"><input name="uname" id="uname" type="text" class="textinput" style="width:210px" value=""></td>
													</tr>
													<tr>
														<td colspan="3">&nbsp;</td>
													</tr>
													<tr>
														<td>&nbsp;</td>
														<td align="left" valign="bottom"><input type="submit" name="Submit" class="button" value="   {TR_SEND}   "></td>
														<td width="302" align="right" valign="bottom"><a class="login" href="index.php">{TR_BACK}</a></td>
													</tr>
												</table>
											</form>
										</td>
									</tr>
									<tr>
										<td>&nbsp;</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<table align="center" width="453">
						<tr>
							<td align="right" class="login">
								Powered by <a class="login" href="http://www.isp-control.net" target="_blank">ispCP Omega</a>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</body>
</html>
