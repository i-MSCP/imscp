<?php

//   -------------------------------------------------------------------------------
//  |                  net2ftp: a web based FTP client                              |
//  |              Copyright (c) 2003-2009 by David Gartner                         |
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the GNU General Public License                   |
//  | as published by the Free Software Foundation; either version 2                |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//   -------------------------------------------------------------------------------





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function getActivePlugins() {

// --------------
// This function modifies the global variable $net2ftp_globals["activePlugins"], which contains an array 
// with all active plugin names
//
// Which plugin is active depends on 2 things:
// 1 - if the plugin is enabled or disabled (see the ["use"] field in getPluginProperties())
// 2 - the $net2ftp_globals["state"] and $net2ftp_globals["state2"] variables, as well as other specific variables (see this function)
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals;
	$pluginProperties = getPluginProperties("ALL");
	$plugincounter = 0;
	$activePlugins = array();
	if (isset($_POST["textareaType"]) == true) { $textareaType = $_POST["textareaType"]; }

// -------------------------------------------------------------------------
// Plugins to always activate
// -------------------------------------------------------------------------


// -------------------------------------------------------------------------
// Plugins to activate depending on the $state and $state2 variables
// -------------------------------------------------------------------------
	if ($net2ftp_globals["state"] == "logout" || $net2ftp_globals["state"] == "admin") { 
		if ($pluginProperties["versioncheck"]["use"] == "yes") { $activePlugins[$plugincounter] = "versioncheck"; $plugincounter++; } 
	}
	elseif ($net2ftp_globals["state"] == "findstring") { 
		if ($pluginProperties["jscalendar"]["use"] == "yes")   { $activePlugins[$plugincounter] = "jscalendar"; $plugincounter++; } 
	}
	elseif ($net2ftp_globals["state"] == "view") { 
		if ($pluginProperties["geshi"]["use"] == "yes")		 { $activePlugins[$plugincounter] = "geshi"; $plugincounter++; } 
	}
	elseif ($net2ftp_globals["state"] == "browse" && $net2ftp_globals["state2"] == "main" 
		&& $net2ftp_globals["skin"] != "mobile" && $net2ftp_globals["skin"] != "iphone") { 
		if ($pluginProperties["swfupload"]["use"] == "yes")	 { $activePlugins[$plugincounter] = "swfupload"; $plugincounter++; } 
	}

// -------------------------------------------------------------------------
// Plugins to activate depending on other variables
// -------------------------------------------------------------------------
	if ($net2ftp_globals["state"] == "edit" && isset($textareaType) == true && $textareaType != "" && array_key_exists($textareaType, $pluginProperties) == true) {
		if ($pluginProperties[$textareaType]["use"] == "yes") { $activePlugins[$plugincounter] = $textareaType; $plugincounter++; } 
	}

	return $activePlugins;

} // end function getActivePlugins

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function isActivePlugin($plugin) {

// --------------
// This function checks if a plugin is active or not
// --------------

	global $net2ftp_globals;
	return in_array($plugin, $net2ftp_globals["activePlugins"]);

} // end function isActivePlugin

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************






// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function getPluginProperties() {

// --------------
// This function returns an array with all plugin properties
// --------------

// -------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------
	global $net2ftp_globals, $net2ftp_settings;


// -------------------------------------------------------------------------
// FCKEditor - http://www.fckeditor.net/
// An HTML editor
// -------------------------------------------------------------------------

// Language code (see /plugins/fckeditor/editor/lang)
// See /modules/edit/edit.inc.php:
//	if     ($net2ftp_globals["language"] == "cs") { $fckeditor_language = "cs"; }
//	elseif ($net2ftp_globals["language"] == "de") { $fckeditor_language = "de"; }
//	elseif ($net2ftp_globals["language"] == "es") { $fckeditor_language = "es"; }
//	elseif ($net2ftp_globals["language"] == "fr") { $fckeditor_language = "fr"; }
//	elseif ($net2ftp_globals["language"] == "it") { $fckeditor_language = "it"; }
//	elseif ($net2ftp_globals["language"] == "nl") { $fckeditor_language = "nl"; }
//	elseif ($net2ftp_globals["language"] == "pl") { $fckeditor_language = "pl"; }
//	elseif ($net2ftp_globals["language"] == "pt") { $fckeditor_language = "pt"; }
//	elseif ($net2ftp_globals["language"] == "ru") { $fckeditor_language = "ru"; }
//	elseif ($net2ftp_globals["language"] == "tc") { $fckeditor_language = "zh"; }
//	elseif ($net2ftp_globals["language"] == "zh") { $fckeditor_language = "zh-cn"; }
//	else                                          { $fckeditor_language = "en"; }

	$pluginProperties["fckeditor"]["use"]                      = "yes";
	$pluginProperties["fckeditor"]["label"]                    = "FCKEditor";
	$pluginProperties["fckeditor"]["directory"]                = "fckeditor";
	$pluginProperties["fckeditor"]["type"]                     = "textarea";
	$pluginProperties["fckeditor"]["browsers"][1]              = "IE";
	$pluginProperties["fckeditor"]["browsers"][2]              = "Mozilla";
	$pluginProperties["fckeditor"]["browsers"][3]              = "Opera";
	$pluginProperties["fckeditor"]["browsers"][4]              = "Other";
	$pluginProperties["fckeditor"]["filename_extensions"][1]   = "html";
	$pluginProperties["fckeditor"]["includePhpFiles"][1]       = "fckeditor/fckeditor.php";
	$pluginProperties["fckeditor"]["printJavascript"]          = "<script type=\"text/javascript\" src=\"" . $net2ftp_globals["application_rootdir_url"] . "/plugins/fckeditor/fckeditor.js\"></script>\n";
	$pluginProperties["fckeditor"]["printCss"]                 = "";
	$pluginProperties["fckeditor"]["printBodyOnload"]          = "";



// -------------------------------------------------------------------------
// TinyMCE - http://tinymce.moxiecode.com/
// An HTML editor
// -------------------------------------------------------------------------

// Language code (see /plugins/tinymce/lang)
	if     ($net2ftp_globals["language"] == "cs") { $tinymce_language = "cs"; }
	elseif ($net2ftp_globals["language"] == "de") { $tinymce_language = "de"; }
	elseif ($net2ftp_globals["language"] == "es") { $tinymce_language = "es"; }
	elseif ($net2ftp_globals["language"] == "fr") { $tinymce_language = "fr"; }
	elseif ($net2ftp_globals["language"] == "it") { $tinymce_language = "it"; }
	elseif ($net2ftp_globals["language"] == "ja") { $tinymce_language = "ja_euc-jp"; }
	elseif ($net2ftp_globals["language"] == "nl") { $tinymce_language = "nl"; }
	elseif ($net2ftp_globals["language"] == "pl") { $tinymce_language = "pl"; }
	elseif ($net2ftp_globals["language"] == "pt") { $tinymce_language = "pt_br"; }
	elseif ($net2ftp_globals["language"] == "ru") { $tinymce_language = "ru"; }
	elseif ($net2ftp_globals["language"] == "sv") { $tinymce_language = "sv"; }
	elseif ($net2ftp_globals["language"] == "tc") { $tinymce_language = "zh_tw"; }
	elseif ($net2ftp_globals["language"] == "tr") { $tinymce_language = "tr"; }
	elseif ($net2ftp_globals["language"] == "vi") { $tinymce_language = "vi"; }
	elseif ($net2ftp_globals["language"] == "zh") { $tinymce_language = "zh_cn"; }
	else                                          { $tinymce_language = "en"; }

	$pluginProperties["tinymce"]["use"]                      = "yes";
	$pluginProperties["tinymce"]["label"]                    = "TinyMCE";
	$pluginProperties["tinymce"]["directory"]                = "tinymce";
	$pluginProperties["tinymce"]["type"]                     = "textarea";
	$pluginProperties["tinymce"]["browsers"][1]              = "IE";
	$pluginProperties["tinymce"]["browsers"][2]              = "Mozilla";
	$pluginProperties["tinymce"]["browsers"][3]              = "Opera";
	$pluginProperties["tinymce"]["browsers"][4]              = "Other";
	$pluginProperties["tinymce"]["filename_extensions"][1]   = "html";
	$pluginProperties["tinymce"]["includePhpFiles"][1]       = "";
	$pluginProperties["tinymce"]["printJavascript"]          = "<script type=\"text/javascript\" src=\"" . $net2ftp_globals["application_rootdir_url"] . "/plugins/tinymce/tiny_mce.js\"></script>\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "<script type=\"text/javascript\">\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "	tinyMCE.init({\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		mode : \"exact\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		elements : \"text_splitted[middle]\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		theme : \"advanced\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		plugins : \"spellchecker,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,zoom,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,filemanager,imagemanager\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		language : \"$tinymce_language\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		theme_advanced_buttons1_add_before : \"save,newdocument,separator\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		theme_advanced_buttons1_add : \"fontselect,fontsizeselect\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		theme_advanced_buttons2_add : \"separator,insertdate,inserttime,preview,separator,forecolor,backcolor\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		theme_advanced_buttons2_add_before: \"cut,copy,paste,pastetext,pasteword,separator,search,replace,separator\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		theme_advanced_buttons3_add_before : \"tablecontrols,separator\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		theme_advanced_buttons3_add : \"emotions,iespell,media,advhr,separator,print,separator,ltr,rtl,separator,fullscreen\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		theme_advanced_buttons4 : \"insertlayer,moveforward,movebackward,absolute,|,styleprops,|,spellchecker,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,|,insertfile,insertimage\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		theme_advanced_toolbar_location : \"top\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		theme_advanced_toolbar_align : \"left\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		theme_advanced_path_location : \"bottom\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		content_css : \"example_full.css\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		plugin_insertdate_dateFormat : \"%Y-%m-%d\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		plugin_insertdate_timeFormat : \"%H:%M:%S\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		extended_valid_elements : \"a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		external_link_list_url : \"example_link_list.js\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		external_image_list_url : \"example_image_list.js\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		flash_external_list_url : \"example_flash_list.js\",\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		file_browser_callback : \"fileBrowserCallBack\"\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "	});\n\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "	function fileBrowserCallBack(field_name, url, type) {\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		// This is where you insert your custom filebrowser logic\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "		alert(\"Filebrowser callback: \" + field_name + \",\" + url + \",\" + type);\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "	}\n\n";
	$pluginProperties["tinymce"]["printJavascript"]         .= "</script>\n";
	$pluginProperties["tinymce"]["printCss"]                 = "";
	$pluginProperties["tinymce"]["printBodyOnload"]          = "";


// -------------------------------------------------------------------------
// CodePress http://www.codepress.org/
// A syntax highlighting text editor in javascript
// -------------------------------------------------------------------------

	$pluginProperties["codepress"]["use"]                    = "yes";
	$pluginProperties["codepress"]["label"]                  = "CodePress";
	$pluginProperties["codepress"]["directory"]              = "codepress";
	$pluginProperties["codepress"]["type"]                   = "textarea";
	$pluginProperties["codepress"]["browsers"][1]            = "IE";
	$pluginProperties["codepress"]["browsers"][2]            = "Opera";
	$pluginProperties["codepress"]["browsers"][3]            = "Mozilla";
	$pluginProperties["codepress"]["browsers"][4]            = "Other";
	$pluginProperties["codepress"]["filename_extensions"][1] = "asp";
	$pluginProperties["codepress"]["filename_extensions"][2] = "css";
	$pluginProperties["codepress"]["filename_extensions"][3] = "cgi";
	$pluginProperties["codepress"]["filename_extensions"][4] = "htm";
	$pluginProperties["codepress"]["filename_extensions"][5] = "html";
	$pluginProperties["codepress"]["filename_extensions"][6] = "java";
	$pluginProperties["codepress"]["filename_extensions"][7] = "javascript";
	$pluginProperties["codepress"]["filename_extensions"][8] = "js";
	$pluginProperties["codepress"]["filename_extensions"][9] = "pl";
	$pluginProperties["codepress"]["filename_extensions"][10] = "perl";
	$pluginProperties["codepress"]["filename_extensions"][11] = "php";
	$pluginProperties["codepress"]["filename_extensions"][12] = "phps";
	$pluginProperties["codepress"]["filename_extensions"][13] = "phtml";
	$pluginProperties["codepress"]["filename_extensions"][14] = "ruby";
	$pluginProperties["codepress"]["filename_extensions"][15] = "sql";
	$pluginProperties["codepress"]["filename_extensions"][16] = "txt";
	$pluginProperties["codepress"]["includePhpFiles"][1]     = "";
	$pluginProperties["codepress"]["printJavascript"]        = "<script type=\"text/javascript\" src=\"" . $net2ftp_globals["application_rootdir_url"] . "/plugins/codepress/codepress.js\"></script>\n";
	$pluginProperties["codepress"]["printCss"]               = "";
	$pluginProperties["codepress"]["printBodyOnload"]        = "";



// -------------------------------------------------------------------------
// Version Checker - written by Slynderdale for net2ftp.
// This small Javascript function will check if a new version of net2ftp is available
// and display a message if there is. 
// -------------------------------------------------------------------------

	$pluginProperties["versioncheck"]["use"]                = "yes";
	$pluginProperties["versioncheck"]["label"]              = "Javascript Version Checker";
	$pluginProperties["versioncheck"]["directory"]          = "versioncheck";
	$pluginProperties["versioncheck"]["type"]               = "versioncheck";
	$pluginProperties["versioncheck"]["browsers"][1]        = "IE";
	$pluginProperties["versioncheck"]["browsers"][2]        = "Opera";
	$pluginProperties["versioncheck"]["browsers"][3]        = "Mozilla";
	$pluginProperties["versioncheck"]["browsers"][4]        = "Other";
	$pluginProperties["versioncheck"]["filename_extensions"][1] = "";
	$pluginProperties["versioncheck"]["includePhpFiles"][1] = "";
	$pluginProperties["versioncheck"]["printJavascript"]    = "<script type=\"text/javascript\" src=\"http://www.net2ftp.com/version.js\"></script>\n";
	$pluginProperties["versioncheck"]["printCss"]           = "";
	$pluginProperties["versioncheck"]["printBodyOnload"]    = "";


// -------------------------------------------------------------------------
// The JS Calendar code is written by Mishoo (who also wrote the HTMLArea v3).
// http://dynarch.com/mishoo/calendar.epl
// -------------------------------------------------------------------------

// Language code (see /plugins/jscalendar/lang)
	if     ($net2ftp_globals["language"] == "cs") { $jscalendar_language = "calendar-cs-win"; }
	elseif ($net2ftp_globals["language"] == "de") { $jscalendar_language = "calendar-de"; }
	elseif ($net2ftp_globals["language"] == "es") { $jscalendar_language = "calendar-es"; }
	elseif ($net2ftp_globals["language"] == "fr") { $jscalendar_language = "calendar-fr"; }
	elseif ($net2ftp_globals["language"] == "it") { $jscalendar_language = "calendar-it"; }
	elseif ($net2ftp_globals["language"] == "nl") { $jscalendar_language = "calendar-nl"; }
	elseif ($net2ftp_globals["language"] == "pl") { $jscalendar_language = "calendar-pl"; }
	elseif ($net2ftp_globals["language"] == "ru") { $jscalendar_language = "calendar-ru"; }
	elseif ($net2ftp_globals["language"] == "tc") { $jscalendar_language = "calendar-big5.js"; }
	elseif ($net2ftp_globals["language"] == "zh") { $jscalendar_language = "calendar-zh"; }
	else                                          { $jscalendar_language = "calendar-en"; }

	$pluginProperties["jscalendar"]["use"]                    = "yes";
	$pluginProperties["jscalendar"]["label"]                  = "JS Calendar";
	$pluginProperties["jscalendar"]["directory"]              = "jscalendar";
	$pluginProperties["jscalendar"]["type"]                   = "calendar";
	$pluginProperties["jscalendar"]["browsers"][1]            = "IE";
	$pluginProperties["jscalendar"]["browsers"][2]            = "Opera";
	$pluginProperties["jscalendar"]["browsers"][3]            = "Mozilla";
	$pluginProperties["jscalendar"]["browsers"][4]            = "Other";
	$pluginProperties["jscalendar"]["filename_extensions"][1] = "";
	$pluginProperties["jscalendar"]["includePhpFiles"][1]     = "jscalendar/calendar.php";
	$pluginProperties["jscalendar"]["printJavascript"]        = "<script type=\"text/javascript\" src=\"" . $net2ftp_globals["application_rootdir_url"] . "/plugins/jscalendar/calendar.js\"></script>\n";
	$pluginProperties["jscalendar"]["printJavascript"]       .= "<script type=\"text/javascript\" src=\"" . $net2ftp_globals["application_rootdir_url"] . "/plugins/jscalendar/lang/" . $jscalendar_language . ".js\"></script>\n";
	$pluginProperties["jscalendar"]["printJavascript"]       .= "<script type=\"text/javascript\" src=\"" . $net2ftp_globals["application_rootdir_url"] . "/plugins/jscalendar/calendar-setup.js\"></script>\n";
	$pluginProperties["jscalendar"]["printCss"]               = "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"" . $net2ftp_globals["application_rootdir_url"] . "/plugins/jscalendar/skins/aqua/theme.css\" title=\"Aqua\" />\n";
	$pluginProperties["jscalendar"]["printCss"]              .= "<link rel=\"alternate stylesheet\" type=\"text/css\" media=\"all\" href=\"" . $net2ftp_globals["application_rootdir_url"] . "/plugins/jscalendar/calendar-win2k-cold-1.css\" title=\"win2k-cold-1\" />\n";
	$pluginProperties["jscalendar"]["printBodyOnload"]        = "";


// -------------------------------------------------------------------------
// JUpload
// A Java applet to upload directories and files
// -------------------------------------------------------------------------

	$pluginProperties["jupload"]["use"]                      = "yes";
	$pluginProperties["jupload"]["label"]                    = "JUpload";
	$pluginProperties["jupload"]["directory"]                = "jupload";
	$pluginProperties["jupload"]["type"]                     = "applet";
	$pluginProperties["jupload"]["browsers"][1]              = "IE";
	$pluginProperties["jupload"]["browsers"][2]              = "Opera";
	$pluginProperties["jupload"]["browsers"][3]              = "Mozilla";
//	$pluginProperties["jupload"]["browsers"][4]            = "Other";
	$pluginProperties["jupload"]["filename_extensions"][1]   = "";
	$pluginProperties["jupload"]["includePhpFiles"][1]       = "";
	$pluginProperties["jupload"]["printCss"]                 = "";
	$pluginProperties["jupload"]["printJavascript"]          = "";
	$pluginProperties["jupload"]["printBodyOnload"]          = "";

// -------------------------------------------------------------------------
// SWFUpload http://swfupload.mammon.se/index.php
// A Flash applet to upload files
// -------------------------------------------------------------------------

	$pluginProperties["swfupload"]["use"]                    = "yes";
	$pluginProperties["swfupload"]["label"]                  = "SWFUpload";
	$pluginProperties["swfupload"]["directory"]              = "swfupload";
	$pluginProperties["swfupload"]["type"]                   = "applet";
	$pluginProperties["swfupload"]["browsers"][1]            = "IE";
	$pluginProperties["swfupload"]["browsers"][2]            = "Opera";
	$pluginProperties["swfupload"]["browsers"][3]            = "Mozilla";
	$pluginProperties["swfupload"]["browsers"][4]            = "Other";
	$pluginProperties["swfupload"]["filename_extensions"][1] = "";
	$pluginProperties["swfupload"]["includePhpFiles"][1]     = "";
	$pluginProperties["swfupload"]["printJavascript"]        = "<script type=\"text/javascript\" src=\"" . $net2ftp_globals["application_rootdir_url"] . "/plugins/swfupload/SWFUpload.js\"></script>\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "<script type=\"text/javascript\" src=\"" . $net2ftp_globals["application_rootdir_url"] . "/plugins/swfupload/example_callback.js.php?plugin_image_url=" . urlEncode2($net2ftp_globals["application_rootdir_url"] . "/plugins/swfupload") . "&amp;directory=" . urlEncode2($net2ftp_globals["directory"]) . "\"></script>\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "<script type=\"text/javascript\">\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "	var swfu;\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "	function initializeSwfu() {\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "		swfu = new SWFUpload({\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			upload_script : '" . printPHP_SELF("swfupload") . "',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			target : 'SWFUploadTarget',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			flash_path : 'plugins/swfupload/SWFUpload.swf',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			allowed_filesize : " . $net2ftp_settings["max_filesize"] . ",\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			allowed_filetypes : '*.*',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			allowed_filetypes_description : 'All files...',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			browse_link_innerhtml : 'Browse',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			upload_link_innerhtml : 'Upload queue',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			browse_link_class : 'swfuploadbtn browsebtn',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			upload_link_class : 'swfuploadbtn uploadbtn',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			flash_loaded_callback : 'swfu.flashLoaded',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			upload_file_queued_callback : 'fileQueued',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			upload_file_start_callback : 'uploadFileStart',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			upload_progress_callback : 'uploadProgress',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			upload_file_complete_callback : 'uploadFileComplete',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			upload_file_cancel_callback : 'uploadFileCancelled',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			upload_queue_complete_callback : 'uploadQueueComplete',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			upload_error_callback : 'uploadError',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			upload_cancel_callback : 'uploadCancel',\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			debug : false,\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "			auto_upload : false\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "		});\n";
//	$pluginProperties["swfupload"]["printJavascript"]       .= "		// NET2FTP: added this line to fix a bug as discussed on the forum\n";
//	$pluginProperties["swfupload"]["printJavascript"]       .= "		// http://swfupload.mammon.se/forum/viewtopic.php?id=14\n";
//	$pluginProperties["swfupload"]["printJavascript"]       .= "		var movie = document.getElementById(swfu.movieName);\n";
//	$pluginProperties["swfupload"]["printJavascript"]       .= "		if (movie != null && !document.getElementById(swfu.movieName+'BrowseBtn')) {\n";
//	$pluginProperties["swfupload"]["printJavascript"]       .= "			if(movie.PercentLoaded() == 100) swfu.loadUI();\n";
//	$pluginProperties["swfupload"]["printJavascript"]       .= "		}\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "	};\n";
//	$pluginProperties["swfupload"]["printJavascript"]       .= "	function uploadError(error) { alert(error); }\n";
	$pluginProperties["swfupload"]["printJavascript"]       .= "</script>\n";
	$pluginProperties["swfupload"]["printCss"]               = "";
	$pluginProperties["swfupload"]["printBodyOnload"]        = "initializeSwfu();";

// -------------------------------------------------------------------------
// GeSHi
// Syntax highlighter
// -------------------------------------------------------------------------

	$pluginProperties["geshi"]["use"]                      = "yes";
	$pluginProperties["geshi"]["label"]                    = "GeSHi";
	$pluginProperties["geshi"]["directory"]                = "geshi";
	$pluginProperties["geshi"]["type"]                     = "highlighter";
	$pluginProperties["geshi"]["browsers"][1]              = "IE";
	$pluginProperties["geshi"]["browsers"][2]              = "Opera";
	$pluginProperties["geshi"]["browsers"][3]              = "Mozilla";
	$pluginProperties["geshi"]["browsers"][4]              = "Other";
	$pluginProperties["geshi"]["filename_extensions"][1]   = "";
	$pluginProperties["geshi"]["includePhpFiles"][1]       = "geshi/geshi.php";
	$pluginProperties["geshi"]["printCss"]                 = "";
	$pluginProperties["geshi"]["printJavascript"]          = "";
	$pluginProperties["geshi"]["printBodyOnload"]          = "";

	return $pluginProperties;

} // end function getPluginProperties

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************









// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function net2ftp_plugin_includePhpFiles() {

// --------------
// This function includes PHP files which are required by the active plugins
// The list of current active plugins is stored in $net2ftp_globals["activePlugins"]
// --------------

// -------------------------------------------------------------------------
// Global variables and settings
// -------------------------------------------------------------------------
	global $net2ftp_globals;
	$pluginProperties = getPluginProperties();

// -------------------------------------------------------------------------
// Initial checks and initialization
// -------------------------------------------------------------------------
	if ($net2ftp_globals["activePlugins"] == "") { return ""; }

// -------------------------------------------------------------------------
// For all plugins...
// -------------------------------------------------------------------------
	for ($pluginnr=0; $pluginnr<sizeof($net2ftp_globals["activePlugins"]); $pluginnr++) {

// Get the plugin related data
		$currentPlugin = $pluginProperties[$net2ftp_globals["activePlugins"][$pluginnr]];

// Check if the plugin should be used
		if ($currentPlugin["use"] != "yes" || $currentPlugin["includePhpFiles"][1] == "") { continue; }

// -------------------------------------------------------------------------
// Include PHP files
// -------------------------------------------------------------------------
		for ($i=1; $i<=sizeof($currentPlugin["includePhpFiles"]); $i++) {
			require_once($net2ftp_globals["application_pluginsdir"] . "/" . $currentPlugin["includePhpFiles"][$i]);
		} // end for

	} // end for

} // End function net2ftp_plugin_includePhpFiles

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function net2ftp_plugin_printJavascript() {

// --------------
// This function includes PHP files which are required by the active plugins
// The list of current active plugins is stored in $net2ftp_globals["activePlugins"]
// --------------

// -------------------------------------------------------------------------
// Global variables and settings
// -------------------------------------------------------------------------
	global $net2ftp_globals;
	$pluginProperties = getPluginProperties();

// -------------------------------------------------------------------------
// Initial checks and initialization
// -------------------------------------------------------------------------
	if ($net2ftp_globals["activePlugins"] == "") { return ""; }

// -------------------------------------------------------------------------
// For all plugins...
// -------------------------------------------------------------------------
	for ($pluginnr=0; $pluginnr<sizeof($net2ftp_globals["activePlugins"]); $pluginnr++) {

// Get the plugin related data
		$currentPlugin = $pluginProperties[$net2ftp_globals["activePlugins"][$pluginnr]];

// Check if the plugin should be used
		if ($currentPlugin["use"] != "yes") { continue; }

// -------------------------------------------------------------------------
// Print Javascript code
// -------------------------------------------------------------------------
		echo $currentPlugin["printJavascript"];

	} // end for

} // End function net2ftp_plugin_printJavascript

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function net2ftp_plugin_printCss() {

// --------------
// This function includes PHP files which are required by the active plugins
// The list of current active plugins is stored in $net2ftp_globals["activePlugins"]
// --------------

// -------------------------------------------------------------------------
// Global variables and settings
// -------------------------------------------------------------------------
	global $net2ftp_globals;
	$pluginProperties = getPluginProperties();

// -------------------------------------------------------------------------
// Initial checks and initialization
// -------------------------------------------------------------------------
	if ($net2ftp_globals["activePlugins"] == "") { return ""; }

// -------------------------------------------------------------------------
// For all plugins...
// -------------------------------------------------------------------------
	for ($pluginnr=0; $pluginnr<sizeof($net2ftp_globals["activePlugins"]); $pluginnr++) {

// Get the plugin related data
		$currentPlugin = $pluginProperties[$net2ftp_globals["activePlugins"][$pluginnr]];

// Check if the plugin should be used
		if ($currentPlugin["use"] != "yes") { continue; }

// -------------------------------------------------------------------------
// Print CSS code
// -------------------------------------------------------------------------
		echo $currentPlugin["printCss"];

	} // end for

} // End function net2ftp_plugin_printCss

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************





// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function net2ftp_plugin_printBodyOnload() {

// --------------
// This function includes PHP files which are required by the active plugins
// The list of current active plugins is stored in $net2ftp_globals["activePlugins"]
// --------------

// -------------------------------------------------------------------------
// Global variables and settings
// -------------------------------------------------------------------------
	global $net2ftp_globals;
	$pluginProperties = getPluginProperties();

// -------------------------------------------------------------------------
// Initial checks and initialization
// -------------------------------------------------------------------------
	if ($net2ftp_globals["activePlugins"] == "") { return ""; }

// -------------------------------------------------------------------------
// For all plugins...
// -------------------------------------------------------------------------
	for ($pluginnr=0; $pluginnr<sizeof($net2ftp_globals["activePlugins"]); $pluginnr++) {

// Get the plugin related data
		$currentPlugin = $pluginProperties[$net2ftp_globals["activePlugins"][$pluginnr]];

// Check if the plugin should be used
		if ($currentPlugin["use"] != "yes") { continue; }

// -------------------------------------------------------------------------
// Print <body onload=""> code
// -------------------------------------------------------------------------
		echo $currentPlugin["printBodyOnload"];

	} // end for

} // End function net2ftp_plugin_printBodyOnload

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>