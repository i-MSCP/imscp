<?php
header("Content-type: text/css");
if (isset($_GET["ltr"]) == true && $_GET["ltr"] != "rtl") { $left = "left"; $right = "right"; }
else                                                      { $left = "right"; $right = "left"; }
if (isset($_GET["image_url"]) == true) { $image_url = preg_replace("/[\\:\\*\\?\\<\\>\\|]/", "", $_GET["image_url"]); }
else                                   { $image_url = ""; }
?>
.swfuploadbtn   { display: block; width: 100px; padding: 0 0 0 30px; background-color: #FF0000; }
.browsebtn      { background: url(<?php echo $image_url; ?>/add.png)    no-repeat 0px 5px; padding: 5px 5px 5px 20px; }
.uploadbtn      { background: url(<?php echo $image_url; ?>/accept.png) no-repeat 0px 5px; padding: 5px 5px 5px 20px; display: none; }
.cancelbtn      { background: url(<?php echo $image_url; ?>/cancel.png) no-repeat 0px 5px; padding: 5px 5px 5px 20px; width: 16px; height: 16px; float: right; display: block; }
#cancelqueuebtn {	background: url(<?php echo $image_url; ?>/cancel.png) no-repeat 0px 5px; padding: 5px 5px 5px 20px; display: none; }
#clearqueuebtn {	background: url(<?php echo $image_url; ?>/cancel.png) no-repeat 0px 5px; padding: 5px 5px 5px 20px; display: none; }
#SWFUploadFileListingFiles ul { padding: 0; list-style: none; margin: 5px 20px 0px 20px; }
.SWFUploadFileItem { background: #eaefea; width: 200px; height: 60px; float: left; margin: 0px 10px 10px 0px; padding: 5px; display: block; }
.fileUploading     { background: #fee727; }
.uploadCompleted   { background: #d2fa7c; }
.uploadCancelled   { background: #f77c7c; }
.uploadCompleted .cancelbtn, .uploadCancelled .cancelbtn { display: none; }
span.progressBar   { background-color: #CCCCCC; width: 200px; font-size: 10px; height: 4px; margin-top: 2px; margin-bottom: 10px; display: block; }