<html>
 <head>
  <title>{TR_MAIN_INDEX_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
  <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
  <link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
  <script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
 </head>
<body onLoad="javascript:document.frm.uname.focus()">
<table width="100%" height="100% "align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td>
<table width="453" style="border:solid 1px #CCCCCC;"align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td>
<form name="frm" action="index.php" method="post">
<table width="453" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td colspan="7" width="453" height="69" background="{THEME_COLOR_PATH}/images/login/login_top.jpg">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="7"><div align="center"><font size="-2">{TR_LOGIN_INFO}</font></div></td>
    </tr>
  <tr>
    <td width="19">&nbsp;</td>
    <td width="94" rowspan="4"><div align="center"><img src="{THEME_COLOR_PATH}/images/login/login_lock.jpg" width="53" height="72" /></div></td>
    <td width="20">&nbsp;</td>
    <td colspan="4" width="320">&nbsp;</td>
  </tr>
  <tr>
    <td rowspan="3">&nbsp;</td>
    <td rowspan="3">&nbsp;</td>
    <td width="131" class="login_text"><div align="right">{TR_USERNAME}</div></td>
    <td width="14" class="login_text">&nbsp;</td>
    <td width="150"><input type="text" name="uname" value="" maxlength="255" style="width:150px" class="textinput" tabindex="1"></td>
    <td width="25">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="4"></td>
  </tr>
  <tr>
    <td class="login_text"><div align="right">{TR_PASSWORD}</div></td>
    <td class="login_text">&nbsp;</td>
    <td><input type="password" name="upass" value="" maxlength="255" style="width:150px" class="textinput" tabindex="2"></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td colspan="3"><div align="center"><a class="login" href="lostpassword.php" tabindex="4"><font size="-2">{TR_LOSTPW}</font></a></div></td>
    <td colspan="4">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="3">&nbsp;</td>
    <td colspan="2" align="right">&nbsp;</td>
    <td align="right"><input type="submit" name="Submit" class="button" value="    {TR_LOGIN}    " tabindex="3"></td>
    <td align="right">&nbsp;</td>
  </tr>
<!-- /* Uncomment this, to use SSL-Switch */
         <tr>
          <td colspan="2">&nbsp;</td>
          <td background="{THEME_COLOR_PATH}/images/login/content_line.gif">&nbsp;</td>
          <td colspan="3">&nbsp;</td>
         </tr>

         <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td background="{THEME_COLOR_PATH}/images/login/content_line.gif">&nbsp;</td>
          <td>&nbsp;</td>
          <td style="text-align:left;vertical-align:bottom;"><img alt="{TR_SSL_DESCRIPTION}" src="{THEME_COLOR_PATH}/images/login/{TR_SSL_IMAGE}" border="0">&nbsp;&nbsp;</td>
          <td style="width:151px;text-align:left;vertical-align:middle;"><a class="submenu" href="{TR_SSL_LINK}" title="{TR_SSL_DESCRIPTION}">{TR_SSL_DESCRIPTION}</a></td>
         </tr>
/* END SSL-Switch */ -->
  </table>
</form>
</td>
  </tr>
</table>
</td>
  </tr>
</table>
</body>
</html>
