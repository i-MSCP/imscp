<?
/************************************************************************
UebiMiau is a GPL'ed software developed by 

 - Aldoir Ventura - aldoir@users.sourceforge.net
 - http://uebimiau.sourceforge.net

Fell free to contact, send donations or anything to me :-)
São Paulo - Brasil
*************************************************************************/


// load session management
require("./inc/inc.php");
// check for all parameters

if(	$attach == "" || 
	$folder == "" || 
	$ix == "") redirect("error.php?err=3&tid=$tid&lid=$lid");

$mail_info = $sess["headers"][base64_encode($folder)][$ix];

if(!is_array($mail_info)) redirect("error.php?err=3&tid=$tid&lid=$lid");

$arAttachment = explode(",",$attach);
$attach = $mail_info;
foreach($arAttachment as $item )
	if(is_numeric($item))
		$attach = &$attach["attachments"][$item];

$path1 = realpath($temporary_directory);
$path2 = realpath($attach["filename"]);

if((substr($path2, 0, strlen($path1)) != $path1) || !file_exists($attach["filename"])) { redirect("error.php?err=3&tid=$tid&lid=$lid"); exit; }

$fp = fopen($attach["filename"],"rb"); fseek($fp,0,SEEK_END);
$size = ftell($fp); rewind($fp);
$stream =  fread($fp,$size); 
fclose($fp);

$disposition = (!$down)?"inline":"attachment";
$type = (!preg_match("/[a-z]+\/[a-z-]+/i",$attach["content-type"]))?"application/octet-stream":$attach["content-type"];

Header("Content-Type: $type; name=\"".$attach["name"]."\"\r\n");
Header("Content-Length: $size\r\n");
Header("Content-Disposition: $disposition; filename=\"".$attach["name"]."\"\r\n");

echo($stream);
?>