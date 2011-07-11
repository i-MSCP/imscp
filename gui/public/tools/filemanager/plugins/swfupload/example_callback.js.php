<?php
header("Content-type: text/css");
if (isset($_GET["plugin_image_url"]) == true) { $plugin_image_url = preg_replace("/[\\:\\*\\?\\<\\>\\|]/", "", $_GET["plugin_image_url"]); }
else                                          { $plugin_image_url = ""; }
if (isset($_GET["directory"]) == true)        { $directory = preg_replace("/[\\:\\*\\?\\<\\>\\|]/", "", $_GET["directory"]); }
else                                          { $directory = ""; }
$directory_js = javascriptEncode2($directory);
?>
function fileQueued(file, queuelength) {
	var listingfiles = document.getElementById("SWFUploadFileListingFiles");

	if(!listingfiles.getElementsByTagName("ul")[0]) {

// NET2FTP - do not print a title <h4>File queue</h4>		
//		var info = document.createElement("h4");
//		info.appendChild(document.createTextNode("File queue"));
//		listingfiles.appendChild(info);
		
		var ul = document.createElement("ul")
		listingfiles.appendChild(ul);
	}
	
	listingfiles = listingfiles.getElementsByTagName("ul")[0];
	
	var li = document.createElement("li");
	li.id = file.id;
	li.className = "SWFUploadFileItem";
	li.innerHTML = file.name + " <span class='progressBar' id='" + file.id + "progress'></span><a id='" + file.id + "deletebtn' class='cancelbtn' href='javascript:swfu.cancelFile(\"" + file.id + "\");'><!-- IE --></a>";

	listingfiles.appendChild(li);
	
	var queueinfo = document.getElementById("queueinfo");
	queueinfo.innerHTML = queuelength + " files queued";
	document.getElementById(swfu.movieName + "UploadBtn").style.display = "block";
	document.getElementById("cancelqueuebtn").style.display = "block";
// NET2FTP - add "Clear queue" link
	document.getElementById("clearqueuebtn").style.display = "block";
}

function uploadFileCancelled(file, queuelength) {
	var li = document.getElementById(file.id);
	li.innerHTML = file.name + " - cancelled";
	li.className = "SWFUploadFileItem uploadCancelled";
	var queueinfo = document.getElementById("queueinfo");
	queueinfo.innerHTML = queuelength + " files queued";
}

function uploadFileStart(file, position, queuelength) {
	var div = document.getElementById("queueinfo");
	div.innerHTML = "Uploading file " + position + " of " + queuelength;

	var li = document.getElementById(file.id);
	li.className += " fileUploading";
}

function uploadProgress(file, bytesLoaded) {
	var progress = document.getElementById(file.id + "progress");
	var percent = Math.ceil((bytesLoaded / file.size) * 200)
	progress.style.background = "#f0f0f0 url(<?php echo $plugin_image_url; ?>/progressbar.png) no-repeat -" + (200 - percent) + "px 0";
}

function uploadError(errno) {
	// SWFUpload.debug(errno);
}

function uploadFileComplete(file) {
	// NET2FTP - added this line to fix a bug as discussed on the forum
	// http://swfupload.mammon.se/forum/viewtopic.php?id=144
	uploadProgress(file, file.size);

	var li = document.getElementById(file.id);
	li.className = "SWFUploadFileItem uploadCompleted";
}

function cancelQueue() {
	swfu.cancelQueue();
	document.getElementById(swfu.movieName + "UploadBtn").style.display = "none";
	document.getElementById("cancelqueuebtn").style.display = "none";
}

function uploadQueueComplete(file) {
	var div = document.getElementById("queueinfo");
// NET2FTP - add link to refresh the page after the upload
	div.innerHTML = "All files uploaded... <a href=\"javascript:submitBrowseForm('<?php echo $directory_js; ?>','','browse','main');\" title=\"Refresh (accesskey r)\" accesskey=\"r\" style=\"font-size: 80%;\">Refresh to view uploaded files</a>";
	document.getElementById("cancelqueuebtn").style.display = "none";
}

// NET2FTP - add "Clear queue" link
// http://swfupload.mammon.se/forum/viewtopic.php?id=105
function clearQueue() {

	// reset the queuelength
	swfu.cancelQueue();

	// hide the "upload queue" link (SWFUpload_0UploadBtn}
	document.getElementById('SWFUpload_0UploadBtn').style.display = 'none';
    
	// hide the "# files queued" text (queueinfo)
	document.getElementById('queueinfo').innerHTML = 'Queue is empty';

	// clear the file listing
	document.getElementById('SWFUploadFileListingFiles').innerHTML = '';

	// hide cancel queue
	document.getElementById('cancelqueuebtn').style.display = 'none';

	// hide clear queue
	document.getElementById('clearqueuebtn').style.display = 'none';
}

<?php

// **************************************************************************************
// **************************************************************************************
// **                                                                                  **
// **                                                                                  **

function javascriptEncode2($string) {

// --------------
// Encode string characters which cause problems in Javascript
// <input type="button" onclick="alert('single quote \' single quote');" value="Test single"> OK      <br /><br />
// <input type="button" onclick="alert('double quote &quot; double quote');"  value="Test double"> OK <br /><br />
// <input type="button" onclick="alert('bs single \\\' bs single');" value="Test bs single"> OK       <br /><br />
// <input type="button" onclick="alert('bs double \\\&quot; bs double');" value="Test bs double"> OK  <br /><br />
// --------------

	$singlequote = "'";			// '
	$doublequote = "\"";			// "
	$backslash   = "\\";			// \
	$doublequote_html = "&quot;"; 	// &quot;

// Executing the 3 steps below in this order will convert:
//     '     -->    \'        in step 2
//     "     -->    &quot;    in step 3
//     \'    -->    \\\'      in step 1 and 2
//     \"    -->    \\\&quot; in step 1 and 3
	$string = str_replace($backslash,   "$backslash$backslash",   $string);
	$string = str_replace($singlequote, "$backslash$singlequote", $string);
	$string = str_replace($doublequote, $doublequote_html,        $string);

	return $string;

} // end javascriptEncode2

// **                                                                                  **
// **                                                                                  **
// **************************************************************************************
// **************************************************************************************

?>