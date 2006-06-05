<?php
/************************************************************************
UebiMiau is a GPL'ed software developed by 

 - Aldoir Ventura - aldoir@users.sourceforge.net
 - http://uebimiau.sourceforge.net

Fell free to contact, send donations or anything to me :-)
São Paulo - Brasil
*************************************************************************/
if(file_exists('./install/')) {
	header('Location: ./install/index.php');
	exit;
}
require("./inc/config.php");
require("./inc/lib.php");

define("SMARTY_DIR","./smarty/");

require_once(SMARTY_DIR."Smarty.class.php");

$smarty = new Smarty;
$smarty->security=true;
$smarty->secure_dir=array("./");
$smarty->compile_dir = $temporary_directory;
$smarty->assign("umLanguageFile",$selected_language.".txt");

$smarty->assign("umEmail",$f_email);
$host = str_replace("webmail.","",strtolower($_SERVER['HTTP_HOST']));
$smarty->assign("domainname",$host);

$jssource = "
<script language=javascript>
	function selectLanguage() {
		sSix		= '';
		sUser		= '';
		sEmail		= '';
		sLanguage	= '';
		sTheme		= '';
		
		try {
			frm = document.forms[0];
			if(frm.six && frm.six.options)
				sSix = frm.six.options[frm.six.selectedIndex].value;
			if(frm.f_user)
				sUser = frm.f_user.value;
			if(frm.f_email)
				sEmail = frm.f_email.value;
			if(frm.tem)
				sTheme = frm.tem.options[frm.tem.selectedIndex].value;
			if(frm.lng)
				sLanguage = frm.lng.options[frm.lng.selectedIndex].value;
			sLocation = 'index.php?lid='+sLanguage+'&tid='+sTheme+'&f_user='+escape(sUser)+'&six='+sSix+'&f_email='+sEmail;
			location.replace(sLocation);
		} catch(err) {
			alert('Your brownser do not support JS');
		}
	}
</script>
";
//$smarty->debugging = true;
$smarty->assign("umServerType",strtoupper($mail_server_type));

switch(strtoupper($mail_server_type)) {
	case "DETECT":

		break;
	case "ONE-FOR-EACH":

		$aval_servers = count($mail_servers);
		$smarty->assign("umAvailableServers",$aval_servers);
		
		if(!$aval_servers) die("You must set at least one server in \$mail_servers, please review your config.php");
		if ($aval_servers == 1) {
			$strServers = "@".$mail_servers[0]["domain"]." <input type=hidden name=six value=0>";
		} else {
			$strServers = "<select name=six>\r";
			for($i=0;$i<$aval_servers;$i++) {
				$sel = ($i == $six)?" selected":"";
				$strServers .= "<option value=$i$sel>@".$mail_servers[$i]["domain"]."\r";
			}
			$strServers .= "</select>\r";
		}

		$smarty->assign("umServer",$strServers);
		break;
	case "ONE-FOR-ALL":
	
		break;
	default:
		die("Unknown server mode, please see config.php");
}

$smarty->assign("umUser",$f_user);
$smarty->assign("umPass",$f_pass);
$smarty->assign("umJS",$jssource);

$avallangs = count($languages);
if($avallangs == 0) die("You must provide at least one language");


$avalthemes = count($themes);
if($avalthemes == 0) die("You must provide at least one theme");

$smarty->assign("umAllowSelectLanguage",$allow_user_change_language); $func($textout);

if($allow_user_change_language) {
	$def_lng = (is_numeric($lid))?$lid:$default_language;
	$langsel = "<select name=lng onChange=selectLanguage()>\r";
	foreach($languages as $key => $lang) {
		$selected = ($lid == $key)?" selected":"";
		$langsel .= "<option value=\"$key\"$selected>".$lang["name"]."\r";
	}
	$langsel .= "</select>\r";
	$smarty->assign("umLanguages",$langsel);
}

$smarty->assign("umAllowSelectTheme",$allow_user_change_theme);

if($allow_user_change_theme) {
	$def_tem = (is_numeric($tid))?$tid:$default_theme;
	$themsel = "<select name=tem onChange=selectLanguage()>\r";
	foreach($themes as $key => $theme) {
		$selected = ($tid == $key)?" selected":"";
		$themsel .= "<option value=\"$key\"$selected>".$theme["name"]."\r";
	}
	$themsel .= "</select>\r";
	$smarty->assign("umThemes", $themsel);
}


$smarty->display("$selected_theme/login.htm");
