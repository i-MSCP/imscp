<?
/************************************************************************
UebiMiau is a GPL'ed software developed by 

 - Aldoir Ventura - aldoir@users.sourceforge.net
 - http://uebimiau.sourceforge.net

Fell free to contact, send donations or anything to me :-)
São Paulo - Brasil
*************************************************************************/

@set_time_limit(0);
session_start();
session_name('sid');
$sid = session_id();

require("./inc/config.php");
require("./inc/class.uebimiau.php");
require("./inc/lib.php");

$temporary_directory = realpath($temporary_directory).'/';

define("SMARTY_DIR","./smarty/");
require_once(SMARTY_DIR."Smarty.class.php");
$smarty = new Smarty;
$smarty->compile_dir = $temporary_directory;
$smarty->security=true;
//$smarty->secure_dir=array("./");


$smarty->assign("umMenuTemplate",dirname($PATH_TRANSLATED).$menu_template);
//$smarty->debugging = false;
$smarty->assign("umLanguageFile",$selected_language.".txt");

$SS = New Session();
$SS->temp_folder 	= $temporary_directory;
$SS->sid 			= $sid;
$SS->timeout 		= $idle_timeout;

$sess = $SS->Load();


if(!array_key_exists("start", $sess )) $sess["start"] = time();
$start = $sess["start"];

$UM = new UebiMiau();

if(isset($f_pass) && strlen($f_pass) > 0) {

	switch(strtoupper($mail_server_type)) {

	case "DETECT":
		$f_server 	= strtolower(getenv("HTTP_HOST"));
		$f_server 	= str_replace($mail_detect_remove,"",$f_server);
		$f_server 	= $mail_detect_prefix.$f_server;

		if(ereg("(.*)@(.*)",$f_email,$regs)) {
			$f_user = $regs[1] & "@" & str_replace("webmail.","",strtolower($_SERVER['HTTP_HOST']));
			$domain = $regs[2];
			if($mail_detect_login_type != "") $f_user = eregi_replace("%user%",$f_user,eregi_replace("%domain%",$domain,$mail_detect_login_type));
		}

		$f_protocol	= $mail_detect_protocol;
		$f_port		= $mail_detect_port;
		$f_prefix	= $mail_detect_folder_prefix;

		break;

	case "ONE-FOR-EACH": 
		$domainname 		= str_replace("webmail.","",strtolower($_SERVER['HTTP_HOST']));
//		$domain 		= $mail_servers[$six]["domain"];
		$domain			= $domainname;
//		$f_email 		= $f_user."@".$domain;
		$f_email		= $f_user."@".$domainname;
//		$f_server 		= $mail_servers[$six]["server"];
		$f_server		= "mail.".$domainname;
		$login_type 		= $mail_servers[$six]["login_type"];
		$f_protocol		= $mail_servers[$six]["protocol"];
		$f_port			= $mail_servers[$six]["port"];
		$f_prefix		= $mail_servers[$six]["folder_prefix"];

		if($login_type != "") $f_user = eregi_replace("%user%",$f_user,eregi_replace("%domain%",$domain,$login_type));
		break;

	case "ONE-FOR-ALL": 
		if(ereg("(.*)@(.*)",$f_email,$regs)) {
			$f_user = $regs[1];
			$domain = $regs[2];
//			$domain = str_replace("webmail.","",strtolower($_SERVER['HTTP_HOST']));			
			if($one_for_all_login_type != "") $f_user = eregi_replace("%user%",$f_user,eregi_replace("%domain%",$domain,$one_for_all_login_type));
		}
		$f_server = $default_mail_server;

		$f_protocol	= $default_protocol;
		if($f_protocol == "imap")
			$f_port		= "143";
		if($f_protocol == "pop3")
			$f_port		= "110";
		$f_prefix	= $default_folder_prefix;
		break;
	}

	$UM->mail_email 	= $sess["email"]  			= stripslashes($f_email);
	$UM->mail_user 		= $sess["user"]   			= stripslashes($f_user);
	$UM->mail_pass 		= $sess["pass"]   			= stripslashes($f_pass); 
	$UM->mail_server 	= $sess["server"] 			= stripslashes($f_server); 

	$UM->mail_port 		= $sess["port"] 			= intval($f_port); 
	$UM->mail_protocol	= $sess["protocol"] 		= strtolower($f_protocol); 
	$UM->mail_prefix	= $sess["folder_prefix"] 	= $f_prefix; 
	
	$sess['remote_ip'] = $_SERVER['REMOTE_ADDR'];
	

} elseif (
	($sess["auth"] && intval((time()-$start)/60) < $idle_timeout)
	&& $require_same_ip && ($sess["remote_ip"] == $_SERVER['REMOTE_ADDR'])
	) {

	$UM->mail_user   	= $f_user    	= $sess["user"];
	$UM->mail_pass   	= $f_pass    	= $sess["pass"];
	$UM->mail_server 	= $f_server  	= $sess["server"];
	$UM->mail_email  	= $f_email   	= $sess["email"];

	$UM->mail_port 		= $f_port 		= $sess["port"]; 
	$UM->mail_protocol	= $f_protocol	= $sess["protocol"]; 
	$UM->mail_prefix	= $f_prefix 	= $sess["folder_prefix"]; 

} else {
	redirect("./index.php?tid=$tid&lid=$lid"); 
	exit; 
}
$sess["start"] = time();

$SS->Save($sess);

$userfolder = $temporary_directory.ereg_replace("[^a-z0-9\._-]","_",strtolower($f_user))."_".strtolower($f_server)."/";

$UM->debug				= $enable_debug;
$UM->use_html			= $allow_html;

$UM->user_folder 		= $userfolder;
$UM->temp_folder		= $temporary_directory;
$UM->timeout			= $idle_timeout;


$prefs = load_prefs();

$UM->timezone			= $prefs["timezone"];
$UM->charset			= $default_char_set;


/*
Don't remove the fallowing lines, or you will be problems with browser's cache 
*/

Header("Expires: Wed, 11 Nov 1998 11:11:11 GMT\r\n".
"Cache-Control: no-cache\r\n".
"Cache-Control: must-revalidate");

$nocache = "
<META HTTP-EQUIV=\"Cache-Control\" CONTENT=\"no-cache\">
<META HTTP-EQUIV=\"Expires\" CONTENT=\"-1\">";

// Sort rules

if(!isset($sortby) || !ereg("(subject|fromname|date|size)",$sortby)) {
	if(array_key_exists("sort-by",$prefs) && ereg("(subject|fromname|date|size)",$prefs["sort-by"]))
		$sortby = $prefs["sort-by"];
	else
		$sortby = $default_sortby;
} else {
	$need_save = true;
	$prefs["sort-by"] = $sortby;
}

if(!isset($sortorder) || !ereg("ASC|DESC",$sortorder)) {
	if(array_key_exists("sort-order",$prefs) && ereg("ASC|DESC",$prefs["sort-order"]))
		$sortorder = $prefs["sort-order"];
	else
		$sortorder = $default_sortorder;
} else {
	$need_save = true;
	$prefs["sort-order"] = $sortorder;
}

if(isset($need_save)) save_prefs($prefs);

if(is_array($sess["sysmap"])) 
	while(list($key, $value) = each($sess["sysmap"]))
		if(strtolower($folder) == $key)
			$folder = $value;

if(!isset($folder) || $folder == "" || strpos($folder,"..") !== false ) {
	$folder = $sess["sysmap"]["inbox"];

} elseif (!file_exists($userfolder.$folder)) { 
	redirect("./logout.php?tid=$tid&lid=$lid"); 
	exit; 
}

?>
