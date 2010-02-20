<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_CRONJOBS_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0 auto;">
<!-- BDP: logged_from -->
<tr>
 <td colspan="3" height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /></a> {YOU_ARE_LOGGED_AS}</td>
</tr>
<!-- EDP: logged_from -->
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" width="195" height="56" border="0" alt="ispCP Logogram" /></td>
<td style="height: 56px; width:100%; background-color: #0f0f0f"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" width="582" height="56" border="0" alt="" /></td>
<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" width="73" height="56" border="0" alt="" /></td>
</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan="2" style="vertical-align: top;"><table style="width: 100%; padding:0;margin:0;" cellspacing="0">
          <tr style="height:95px;">
            <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
            <td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" width="73" height="95" border="0" alt="" /></td>
          </tr>
          <tr>
            <td colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                    <tr>
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_tools.png" width="25" height="25" alt="" /></td>
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
                        <td colspan="5" class="title"><span class="message">{MESSAGE}</span></td>
                      </tr>
                      <!-- EDP: page_message -->
                      <tr>
                        <td nowrap="nowrap">&nbsp;</td>
                        <td nowrap="nowrap" class="content2">{TR_NAME}</td>
                        <td colspan="4" class="content"><input name="name" type="text" class="textinput" id="name" style="width:170px" value="{NAME}" /></td>
                      </tr>
                      <tr>
                        <td nowrap="nowrap">&nbsp;</td>
                        <td nowrap="nowrap" class="content2">{TR_DESCRIPTION}</td>
                        <td colspan="4" class="content"><input name="description" type="text" class="textinput" id="description" style="width:170px" value="{DESCTIPTON}" /></td>
                      </tr>
                      <tr>
                        <td nowrap="nowrap">&nbsp;</td>
                        <td nowrap="nowrap" class="content2">{TR_ACTIVE}</td>
                        <td colspan="4" class="content"><select name="activ">
                            <option value="1" selected="selected">{YES}</option>
                            <option value="0">{NO}</option>
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
                        <td colspan="5" class="content"><input name="command_line" type="text" class="textinput" id="command_line" style="width:370px" value="{COMMAND_LINE}" /></td>
                      </tr>
                      <tr>
                        <td nowrap="nowrap">&nbsp;</td>
                        <td align="center" nowrap="nowrap" class="content2">{TR_MIN}</td>
                        <td align="center" class="content2">{TR_HOUR}</td>
                        <td align="center" class="content2">{TR_DAY}</td>
                        <td align="center" class="content2">{TR_MONTHS}</td>
                        <td align="center" class="content2">{TR_WEEKDAYS}</td>
                      </tr>
                      <tr align="center" valign="top">
                        <td width="25" nowrap="nowrap">&nbsp;</td>
                        <td width="200" nowrap="nowrap" class="content"><select name="min" size="6" multiple="multiple" id="min">
                            <option value="*">Every Minute</option>
                            <option value="*/2">Every Other Minute</option>
                            <option value="*/5">Every Five Minutes</option>
                            <option value="*/10">Every Ten Minutes</option>
                            <option value="*/15">Every Fifteen Minutes</option>
                            <option value="0">0</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                            <option value="8">8</option>
                            <option value="9">9</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                            <option value="13">13</option>
                            <option value="14">14</option>
                            <option value="15">15</option>
                            <option value="16">16</option>
                            <option value="17">17</option>
                            <option value="18">18</option>
                            <option value="19">19</option>
                            <option value="20">20</option>
                            <option value="21">21</option>
                            <option value="22">22</option>
                            <option value="23">23</option>
                            <option value="24">24</option>
                            <option value="25">25</option>
                            <option value="26">26</option>
                            <option value="27">27</option>
                            <option value="28">28</option>
                            <option value="29">29</option>
                            <option value="30">30</option>
                            <option value="31">31</option>
                            <option value="32">32</option>
                            <option value="33">33</option>
                            <option value="34">34</option>
                            <option value="35">35</option>
                            <option value="36">36</option>
                            <option value="37">37</option>
                            <option value="38">38</option>
                            <option value="39">39</option>
                            <option value="40">40</option>
                            <option value="41">41</option>
                            <option value="42">42</option>
                            <option value="43">43</option>
                            <option value="44">44</option>
                            <option value="45">45</option>
                            <option value="46">46</option>
                            <option value="47">47</option>
                            <option value="48">48</option>
                            <option value="49">49</option>
                            <option value="50">50</option>
                            <option value="51">51</option>
                            <option value="52">52</option>
                            <option value="53">53</option>
                            <option value="54">54</option>
                            <option value="55">55</option>
                            <option value="56">56</option>
                            <option value="57">57</option>
                            <option value="58">58</option>
                            <option value="59">59</option>
                            </select></td>
                        <td class="content"><select name="hour" size="6" multiple="multiple" id="hour">
                            <option value="*">Every Hour</option>
                            <option value="*/2">Every Other Hour</option>
                            <option value="*/4">Every Four Hours</option>
                            <option value="*/6">Every Six Hours</option>
                            <option value="0">0 = 12 AM/Midnight</option>
                            <option value="1">1 = 1 AM</option>
                            <option value="2">2 = 2 AM</option>
                            <option value="3">3 = 3 AM</option>
                            <option value="4">4 = 4 AM</option>
                            <option value="5">5 = 5 AM</option>
                            <option value="6">6 = 6 AM</option>
                            <option value="7">7 = 7 AM</option>
                            <option value="8">8 = 8 AM</option>
                            <option value="9">9 = 9 AM</option>
                            <option value="10">10 = 10 AM</option>
                            <option value="11">11 = 11 AM</option>
                            <option value="12">12 = 12 PM/Noon</option>
                            <option value="13">13 = 1 PM</option>
                            <option value="14">14 = 2 PM</option>
                            <option value="15">15 = 3 PM</option>
                            <option value="16">16 = 4 PM</option>
                            <option value="17">17 = 5 PM</option>
                            <option value="18">18 = 6 PM</option>
                            <option value="19">19 = 7 PM</option>
                            <option value="20">20 = 8 PM</option>
                            <option value="21">21 = 9 PM</option>
                            <option value="22">22 = 10 PM</option>
                            <option value="23">23 = 11 PM</option>
                            </select></td>
                        <td class="content"><select name="day_of_month" size="6" multiple="multiple" id="day_of_month">
                            <option value="*">Every Day</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                            <option value="8">8</option>
                            <option value="9">9</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                            <option value="13">13</option>
                            <option value="14">14</option>
                            <option value="15">15</option>
                            <option value="16">16</option>
                            <option value="17">17</option>
                            <option value="18">18</option>
                            <option value="19">19</option>
                            <option value="20">20</option>
                            <option value="21">21</option>
                            <option value="22">22</option>
                            <option value="23">23</option>
                            <option value="24">24</option>
                            <option value="25">25</option>
                            <option value="26">26</option>
                            <option value="27">27</option>
                            <option value="28">28</option>
                            <option value="29">29</option>
                            <option value="30">30</option>
                            <option value="31">31</option>
                            </select></td>
                        <td class="content"><select name="month" size="6" multiple="multiple" id="month">
                            <option value="*">Every Month</option>
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                            </select></td>
                        <td class="content"><select name="day_of_week" size="6" multiple="multiple" id="day_of_week">
                            <option value="*">Every Weekday</option>
                            <option value="0">Sunday</option>
                            <option value="1">Monday</option>
                            <option value="2">Tuesday</option>
                            <option value="3">Wednesday</option>
                            <option value="4">Thursday</option>
                            <option value="5">Friday</option>
                            <option value="6">Saturday</option>
                            </select></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td colspan="5"><input name="Submit" type="submit" class="button" value="  {TR_UPDATE}  " />
                          &nbsp;&nbsp;				  &nbsp;&nbsp;
                          <input name="button" type="button" class="button" onclick="MM_goToURL('parent','cronjobs_overview.php');return document.MM_returnValue" value="{TR_CANCEL}" /></td>
                      </tr>
                    </table>
                  <input type="hidden" name="uaction" value="update_cronjob" />
                </form></td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
              </tr>
            </table></td>
          </tr>
        </table></td>
	</tr>
</table>
</body>
</html>
