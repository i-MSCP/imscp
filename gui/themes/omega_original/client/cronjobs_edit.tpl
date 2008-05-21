<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_CLIENT_CRONJOBS_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script language="JavaScript" type="text/JavaScript">
<!--

function MM_goToURL() { //v3.0
  var i, args=MM_goToURL.arguments; document.MM_returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}
//-->
</script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<!-- BDP: logged_from --><table width="100%"  border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap background="{THEME_COLOR_PATH}/images/button.gif">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="18" height="18" border="0" align="absmiddle"></a> <font color="red">{YOU_ARE_LOGGED_AS}</font> </td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%" style="border-collapse: collapse;padding:0;margin:0;">
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" border="0"></td>
<td style="height: 56px; width:100%; background-image: url({THEME_COLOR_PATH}/images/top/top_bg.jpg)"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0"></td>
<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" border="0"></td>
</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan=2 style="vertical-align: top;"><table style="width: 100%; border-collapse: collapse;padding:0;margin:0;">
          <tr height="95">
            <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
            <td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0"></td>
          </tr>
          <tr height="*">
            <td colspan=3><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                    <tr>
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_tools.png" width="25" height="25"></td>
                      <td colspan="2" class="title">{TR_CRON_MANAGER}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td><form name="add_cronjob" method="post" action="cronjobs_edit.php">
                    <table width="100%" cellpadding="5" cellspacing="5">
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td colspan="5" class="content3"><b>{TR_EDIT_CRONJOB}</b></td>
                      </tr>
                      <!-- BDP: page_message -->
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td colspan="5" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                      </tr>
                      <!-- EDP: page_message -->
                      <tr>
                        <td nowrap>&nbsp;</td>
                        <td nowrap class="content2">{TR_NAME}</td>
                        <td colspan="4" class="content"><input name="name" type="text" class="textinput" id="name" style="width:170px" value="{NAME}"></td>
                      </tr>
                      <tr>
                        <td nowrap>&nbsp;</td>
                        <td nowrap class="content2">{TR_DESCRIPTION}</td>
                        <td colspan="4" class="content"><input name="description" type="text" class="textinput" id="description" style="width:170px" value="{DESCTIPTON}"></td>
                      </tr>
                      <tr>
                        <td nowrap>&nbsp;</td>
                        <td nowrap class="content2">{TR_ACTIVE}</td>
                        <td colspan="4" class="content"><select name="activ">
                            <option value="1" SELECTED>{YES}</option>
                            <option value="0" >{NO}</option>
                        </select></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td colspan="5" class="content3"><b>{TR_CRONJOB}</b></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td colspan="5" class="content2">{TR_COMMAND}</td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td colspan="5" class="content"><input name="command_line" type="text" class="textinput" id="command_line" style="width:370px" value="{COMMAND_LINE}"></td>
                      </tr>
                      <tr>
                        <td nowrap>&nbsp;</td>
                        <td align="center" nowrap class="content2">{TR_MIN}</td>
                        <td align="center" class="content2">{TR_HOUR}</td>
                        <td align="center" class="content2">{TR_DAY}</td>
                        <td align="center" class="content2">{TR_MONTHS}</td>
                        <td align="center" class="content2">{TR_WEEKDAYS}</td>
                      </tr>
                      <tr align="center" valign="top">
                        <td width="25" nowrap>&nbsp;</td>
                        <td width="200" nowrap class="content"><select name=min size=6 multiple id="min">
                            <option value=*> Every Minute
                            <option value=*/2> Every Other Minute
                            <option value=*/5> Every Five Minutes
                            <option value=*/10> Every Ten Minutes
                            <option value=*/15> Every Fifteen Minutes
                            <option value=0> 0
                            <option value=1> 1
                                          
                            <option value=2> 2
                            <option value=3> 3
                            <option value=4> 4
                            <option value=5> 5
                            <option value=6> 6
                            <option value=7> 7
                            <option value=8> 8
                            <option value=9> 9
                            <option value=10> 10
                                                            
                            <option value=11> 11
                            <option value=12> 12
                            <option value=13> 13
                            <option value=14> 14
                            <option value=15> 15
                            <option value=16> 16
                            <option value=17> 17
                            <option value=18> 18
                            <option value=19> 19
                                                                              
                            <option value=20> 20
                            <option value=21> 21
                            <option value=22> 22
                            <option value=23> 23
                            <option value=24> 24
                            <option value=25> 25
                            <option value=26> 26
                            <option value=27> 27
                            <option value=28> 28
                                                                                                
                            <option value=29> 29
                            <option value=30> 30
                            <option value=31> 31
                            <option value=32> 32
                            <option value=33> 33
                            <option value=34> 34
                            <option value=35> 35
                            <option value=36> 36
                            <option value=37> 37
                                                                                                                  
                            <option value=38> 38
                            <option value=39> 39
                            <option value=40> 40
                            <option value=41> 41
                            <option value=42> 42
                            <option value=43> 43
                            <option value=44> 44
                            <option value=45> 45
                            <option value=46> 46
                                                                                                                                    
                            <option value=47> 47
                            <option value=48> 48
                            <option value=49> 49
                            <option value=50> 50
                            <option value=51> 51
                            <option value=52> 52
                            <option value=53> 53
                            <option value=54> 54
                            <option value=55> 55
                                                                                                                                                      
                            <option value=56> 56
                            <option value=57> 57
                            <option value=58> 58
                            <option value=59> 59
                            </select></td>
                        <td class="content"><select name=hour size=6 multiple id="hour">
                            <option value=*> Every Hour
                            <option value=*/2> Every Other Hour
                                
                            <option value=*/4> Every Four Hours
                            <option value=*/6> Every Six Hours
                            <option value=0> 0 = 12 AM/Midnight
                            <option value=1> 1 = 1 AM
                            <option value=2> 2 = 2 AM
                            <option value=3> 3 = 3 AM
                            <option value=4> 4 = 4 AM
                            <option value=5> 5 = 5 AM
                            <option value=6> 6 = 6 AM
                                                  
                            <option value=7> 7 = 7 AM
                            <option value=8> 8 = 8 AM
                            <option value=9> 9 = 9 AM
                            <option value=10> 10 = 10 AM
                            <option value=11> 11 = 11 AM
                            <option value=12> 12 = 12 PM/Noon
                            <option value=13> 13 = 1 PM
                            <option value=14> 14 = 2 PM
                            <option value=15> 15 = 3 PM
                                                                    
                            <option value=16> 16 = 4 PM
                            <option value=17> 17 = 5 PM
                            <option value=18> 18 = 6 PM
                            <option value=19> 19 = 7 PM
                            <option value=20> 20 = 8 PM
                            <option value=21> 21 = 9 PM
                            <option value=22> 22 = 10 PM
                            <option value=23> 23 = 11 PM
                            </select>
                        </td>
                        <td class="content"><select name=day_of_month size=6 multiple id="day_of_month">
                            <option value=*> Every Day
                            <option value=1> 1
                            <option value=2> 2
                            <option value=3> 3
                            <option value=4> 4
                            <option value=5> 5
                            <option value=6> 6
                                          
                            <option value=7> 7
                            <option value=8> 8
                            <option value=9> 9
                            <option value=10> 10
                            <option value=11> 11
                            <option value=12> 12
                            <option value=13> 13
                            <option value=14> 14
                            <option value=15> 15
                                                            
                            <option value=16> 16
                            <option value=17> 17
                            <option value=18> 18
                            <option value=19> 19
                            <option value=20> 20
                            <option value=21> 21
                            <option value=22> 22
                            <option value=23> 23
                            <option value=24> 24
                                                                              
                            <option value=25> 25
                            <option value=26> 26
                            <option value=27> 27
                            <option value=28> 28
                            <option value=29> 29
                            <option value=30> 30
                            <option value=31> 31
                            </select></td>
                        <td class="content"><select name=month size=6 multiple id="month">
                            <option value=*> Every Month
                            <option value=1> January
                            <option value=2> February
                            <option value=3> March
                            <option value=4> April
                            <option value=5> May
                            <option value=6> June
                            <option value=7> July
                                            
                            <option value=8> August
                            <option value=9> September
                            <option value=10> October
                            <option value=11> November
                            <option value=12> December
                            </select></td>
                        <td class="content"><select name=day_of_week size=6 multiple id="day_of_week">
                            <option value=*> Every Weekday
                            <option value=0> Sunday
                                
                            <option value=1> Monday
                            <option value=2> Tuesday
                            <option value=3> Wednesday
                            <option value=4> Thursday
                            <option value=5> Friday
                            <option value=6> Saturday
                            </select></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td colspan="5"><input name="Submit" type="submit" class="button" value="  {TR_UPDATE}  ">
                          &nbsp;&nbsp;				  &nbsp;&nbsp;
                          <input name="button" type="button" class="button" onClick="MM_goToURL('parent','cronjobs_overview.php');return document.MM_returnValue"  value="{TR_CANCEL}"></td>
                      </tr>
                    </table>
                  <input type="hidden" name="uaction" value="update_cronjob">
                </form></td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
              </tr>
            </table></td>
          </tr>
        </table>
	  </td>
	</tr>
</table>
</body>
</html>
