<?php

define('SM_PATH','../../');

include_once('bookmarks_data.php');
include_once('functions.php');
if (file_exists(SM_PATH . 'include/validate.php'))
   include_once(SM_PATH . 'include/validate.php');
else if (file_exists(SM_PATH . 'src/validate.php'))
   include_once(SM_PATH . 'src/validate.php');
include_once(SM_PATH . 'functions/page_header.php');

sqgetGlobalVar('passval', $passval, SQ_GET);
if (!isset($passval)) sqgetGlobalVar('passval', $passval, SQ_POST);
sqgetGlobalVar('confirmed', $confirmed, SQ_GET);
if (!isset($confirmed)) sqgetGlobalVar('confirmed', $confirmed, SQ_POST);
sqgetGlobalVar('title', $title, SQ_GET);
if (!isset($title)) sqgetGlobalVar('title', $title, SQ_POST);
sqgetGlobalVar('url', $url, SQ_GET);
if (!isset($url)) sqgetGlobalVar('url', $url, SQ_POST);

$passval = urldecode($passval);

$bookmarksdata = $_SESSION['bookmarksdata'];

if ($confirmed == '') {
	foreach ($bookmarksdata as $bookfoo) {
		if ($bookfoo["index"] == $passval) {
			$url = $bookfoo["url"];
			$title = $bookfoo["title"];
			$folder = $bookfoo["folder"];
		}
	}
}

if (isset($url)) {
	if (!isset($confirmed)) {

		global $bookmarksdata;

		displayPageHeader($color, 'None');
		echo '<br><br>';
		echo '<form method="POST" action="bookmark_edit.php">';
		echo '<input type="hidden" name="passval" value="' . $passval . '">';
		echo '<input type="hidden" name="confirmed" value="yes">';
		echo 'URL:<br><input type="text" name="url" value="' . $url . '">';
		echo '<br><br>';
		echo 'Title: (leave blank to fetch from URL)<br>';
		echo '<input type="text" name="title" value="' . $title . '">';
		echo '<br><br>';
		echo '<input type="submit" value="Save">';
		echo '</form>';
		echo '</body></html>';
	} else {
		if ((substr(strtolower($url),0,7) != 'http://') && (substr(strtolower($url),0,6) != 'ftp://') && (substr(strtolower($url),0,8) != 'https://') && (substr(strtolower($url),0,9) != 'telnet://') && (substr(strtolower($url),0,7) != 'nntp://')) {
			$url = 'http://' . $url;
		}
		if (($title == "") && ((substr(strtolower($url),0,7) == 'http://') || (substr(strtolower($url),0,8) != 'https://'))){
			$retstr = "";
			$targeturl = $url;
			$explode = explode('://',$targeturl);
			$prefix = $explode[0] . '://';
			$targeturl = $explode[1];
			$explode = explode('?',$targeturl);
			$targeturl = $explode[0];
			$querystring = $explode[1];
			$lastslash = strrchr($targeturl,'/');
			if ($lastslash != false) {
				if (strrchr(substr($targeturl,$lastslash),'.') == false) {
					$targeturl = $targeturl . '/';
				}
			}
			$sk = fopen($prefix . $targeturl . "?" . $querystring,"r");
		  if ($sk != false) {
  			while(!feof($sk)){
  				$resp=fgets($sk,80);
  				$retstr.=$resp;
  			}
  			fclose($sk);
  			$sString = $retstr;
  			$sString = str_replace('<title>','<TITLE>',$sString);
  			$sString = str_replace('</title>','</TITLE>',$sString);
  			$explode = explode("<TITLE>",$sString);
  			$sString = $explode[1];
  			$explode = explode("</TITLE>",$sString);
  			$sString = $explode[0];
  			$title = ereg_replace("(\r\n|\n|\r)", "", $sString);
			}
		}
		if ($title == "") {
			$title = $url;
		}

		$bookmarksdata[$passval]["title"] = $title;
		$bookmarksdata[$passval]["url"] = $url;

		writebookmarksdata();
		readbookmarksdata();
		header('Location: bookmarks.php?passval=' . urlencode($folder));
	}
} else {
	echo "Nothing to edit";
}
?>
