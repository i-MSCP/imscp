<?php
/*
 * CodePress - Real Time Syntax Highlighting Editor written in JavaScript -  http://codepress.fermads.net/
 *
 * Copyright (C) 2006 Fernando M.A.d.S. <fermads@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the 
 * GNU Lesser General Public License as published by the Free Software Foundation.
 *
 * Read the full licence: http://www.opensource.org/licenses/lgpl-license.php
 *
 *
 * Very simple implementation of server side script
 * to open files and send to CodePress interface
 */

$path['files'] = "examples";  // directory of files to edit
$path['webdocs'] = dirname($_SERVER['SCRIPT_NAME']);
$path['server'] = $_SERVER['DOCUMENT_ROOT'];

/* For future use
$action = $_GET['action']; 
if($action == 'edit') { } */

$code = "";
$language = "text";

if(isset($_GET['file'])) {
    $file = basename($_GET['file']);
// NET2FTP - do not allow CodePress to get files on the net2ftp web server
//	$full_file = $path['server'].'/'.$path['webdocs'].'/'.$path['files']."/".$file;
//	if(file_exists($full_file)) {
//	    $code = file_get_contents($full_file);
//	    $code = preg_replace("/&shy;|&amp;shy;/","\\xad",$code);
//	    $code = preg_replace("/</","&lt;",$code);
//	    $code = preg_replace("/>/","&gt;",$code);
	    $language = getLanguage($file);
//	}
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title>CodePress - Real Time Syntax Highlighting Editor written in JavaScript</title>
	<link type="text/css" href="languages/codepress-<?php echo $language; ?>.css?timestamp=<?php echo time(); ?>" rel="stylesheet" id="cp-lang-style" />
	<script type="text/javascript" src="codepress.js?timestamp=<?php echo time(); ?>"></script>
	<script type="text/javascript">CodePress.language = '<?php echo $language; ?>';</script>
</head>
<body id="ffedt"><pre id="ieedt"></pre></body>
</html>

<?php
/* 
Function that get language to use based on file extension
*/
function getLanguage($file) {

	$currentFileExtension = ereg( ".([^\.]+)$", $file, $r ) ? $r[1] : "";

	/*
	Associate extensions with corresponding language name
	In the future, put this into a configuration file
	*/
	$ext['php'] = 'php'; $ext['phtml'] = 'php';
	$ext['js'] = 'javascript';
	$ext['java'] = 'java';
	$ext['txt'] = 'text';
	$ext['html'] = 'html'; $ext['htm'] = 'html';
	$ext['css'] = 'css';

	return (isset($ext[$currentFileExtension])) ? $ext[$currentFileExtension] : 'text' ;
}
?>