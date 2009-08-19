<?php
header("Content-type: text/css");
if (isset($_GET["LTR"]) && $_GET["LTR"] != "RTL") { $left = "left"; $right = "right"; }
else                                              { $left = "right"; $right = "left"; }
?>

/*------------------------------------------------------------------------
   General settings
------------------------------------------------------------------------*/

body {
	background-color : #FFFFFF;
	color: #000000;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-weight: normal;
	line-height: normal;
 	margin-top: 3px;
 	margin-bottom: 3px;
 	margin-<?php echo $left; ?>: 3px;
 	margin-<?php echo $right; ?>: 3px;
}

A:link {
	color: #0000FF;
	text-decoration: underline;
}

A:visited {
	color: #0000FF;
	text-decoration: underline;
}

A:hover {
}

li {
	margin-top: 5px;
}

h1, h2, h3, p, form { 
	margin: 0;
}

.menu_header {
	background-color: #3333DD;
	color: #FFFFFF;
	font-size: 110%;
	font-weight: bold;
	padding-<?php echo $left; ?>: 10px;
	padding-<?php echo $right; ?>: 10px;
	padding-top: 3px;
	padding-bottom: 3px;
}

.menu_item {
	line-height: 130%;
	padding-top: 3px;
	padding-bottom: 3px;
	padding-<?php echo $left; ?>: 10px;
	padding-<?php echo $right; ?>: 10px;
	padding-bottom: 30px;
}

/*------------------------------------------------------------------------
   Header level 1-3
------------------------------------------------------------------------*/

.header_table {
	background-color: #3333DD;
	color: #FFFFFF;
	width: 100%;
}

.header11 {
	background-color: #3333DD;
	color: #FFFFFF;
	border: 3px;
	border-style: solid;
	border-color: #CCCCCC; 
	margin-<?php echo $left; ?>: auto;
	margin-<?php echo $right; ?>: auto;
	margin-top: 10px;
	margin-bottom: 10px;
	width: 200px;
	padding-bottom : 8px;
	padding-top : 8px;
	padding-<?php echo $left; ?> : 12px;
	padding-<?php echo $right; ?> : 12px;
	font-family: arial black, monaco, chicago;
	font-size: 180%;
	letter-spacing: 4px;
	line-height: 30px; 
	text-align: center;
}

.header21 {
	color: #3333DD;
	font-size: 150%;
	font-weight: bold;
	margin-top: 10px;
	margin-bottom: 10px;
	padding-top : 5px;
}

.header31 {
	color: #3333DD;
	font-size: 110%;
	font-weight: bold;
	text-decoration: underline;
}

/*------------------------------------------------------------------------
   Form objects: textboxes, buttons,...
------------------------------------------------------------------------*/

.input {
	background-color: #FFFFFF;
	color: #000000;
	border-color: #330066;
	border-width: 1px;
}

.longinput {
	background-color: #FFFFFF;
	color: #000000;
	border-color: #330066;
	border-width: 1px;
	width: 300px;
}

.textarea {
	background-color: #FFFFFF;
	border-color: black;
	border-width: 1px;
}

.microbutton {
	background-color: #DEDEDE;
	color: #000000;
	height: 17px;
	font-size: 8px;
}

.smallbutton {
	background-color: #DEDEDE;
	color: #000000;
	height: 20px;
	font-size: 10px;
}

.smalllongbutton {
	background-color: #DEDEDE;
	color: #000000;
	height: 20px;
	font-size: 10px;
}

.button {
	background-color: #DEDEDE;
	color: #000000;
	height: 25px;
}

.longbutton	{
	background-color: #DEDEDE;
	color: #000000;
	height: 25px;
	width: 120px;
}

.extralongbutton	{
	background-color: #DEDEDE;
	color: #000000;
	height: 25px;
}

.uploadinputbutton {
	background-color: #FFFFFF;
	height: 20px;
}


/*------------------------------------------------------------------------
   Browse
------------------------------------------------------------------------*/

.browse_table {
	margin-top: 0px; 
	width: 100%; 
	border: 2px solid #CCCCFF;
}

.browse_rows_heading {
	color: #000000;
	background-color: #CCCCFF;
	font-size: 110%;
	font-weight: bold;
}

.browse_rows_actions {
	color: #000000;
	background-color: #CCCCFF;
	font-size: 80%;
	font-weight: normal;
	text-align: <?php echo $left; ?>;
}

.browse_rows_odd {
	color: #000000; 
	background-color: #FFFFFF;
	font-size: 80%;
	font-weight: normal;
	text-align: <?php echo $left; ?>;
}

.browse_rows_even {
	color: #000000; 
	background-color: #EFEFEF;
	font-size: 80%;
	font-weight: normal;
	text-align: <?php echo $left; ?>;
}

.browse_rows_separator {
	color: #000000;
	background-color: #CCCCFF;
	font-size: 80%;
	text-align: center;
}

/*------------------------------------------------------------------------
   View
------------------------------------------------------------------------*/

.view {
	border: 1px;
	border-style: solid;
	border-color: #000066; 
	background-color: #FFFFFF;
	font-family: Courier;
	font-size: 90%;
	margin-<?php echo $left; ?>: 10px;
	margin-<?php echo $right; ?>: 10px;
	margin-bottom: 10px;
	margin-top: 10px;
	padding: 10px;
}

/*------------------------------------------------------------------------
   Edit
------------------------------------------------------------------------*/

.edit {
	font-size: 11px;
	font-family: Courier;
}

/*------------------------------------------------------------------------
   Error
------------------------------------------------------------------------*/

.error-table {
	border: 2px;
	border-style: solid;
	border-color: #FF0000; 
	margin: 10px;
	padding: 0px;
	width: 750px;
}

.error-header {
	background-color: #FF0000; 
	color: #FFFFFF;
	text-decoration: underline;
	font-size: 120%;
	font-weight: bold;
	line-height: 25px;
	padding-<?php echo $left; ?>: 10px;
	padding-<?php echo $right; ?>: 10px;
	padding-top: 5px;
	padding-bottom: 5px;
}

.error-text {
	color: #FF0000;
	font-size: 100%;
	text-align: <?php echo $left; ?>;
	padding: 10px;
}

.warning-box {
	background-color: #FFDD00;
	color: #000000;
	border: 2px;
	border-style: solid;
	border-color: #FFCC33; 
	margin-<?php echo $left; ?>: auto;
	margin-<?php echo $right; ?>: auto;
	width: 90%; 

}

.warning-text {
	padding-bottom : 5px;
	padding-top : 5px;
	padding-<?php echo $left; ?> : 10px;
	padding-<?php echo $right; ?> : 10px;
}

/*------------------------------------------------------------------------
   Admin
------------------------------------------------------------------------*/

.tdheader1 {
	font-size: 80%;
	font-weight: bold;
	text-align: center;
}

.tditem1 {
	font-size: 65%;
	font-weight: normal;
	text-align: <?php echo $left; ?>;
}

/*------------------------------------------------------------------------
   Process bar
From the PHP Pear package HTML_Progress
http://pear.laurent-laville.org/HTML_Progress/examples/horizontal/string.php
------------------------------------------------------------------------*/

.p_561b57 .progressBar {
	background-color: #FFFFFF;
	width: 172px;
	height: 24px;
	position: relative;
	<?php echo $left; ?>: 0px;
	top: 0px;
}
.p_561b57 .progressBarBorder {
	background-color: #FFFFFF;
	width: 172px;
	height: 24px;
	position: relative;
	<?php echo $left; ?>: 0px;
	top: 0px;
	border-width: 0px;
	border-style: solid;
	border-color: #000000;
}
.p_561b57 .installationProgress {
	width: 300px;
	text-align: <?php echo $left; ?>;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color: #000000;
	background-color: #FFFFFF;
}
.p_561b57 .cellI {
	width: 15px;
	height: 20px;
	font-family: Courier, Verdana;
	font-size: 8px;
	float: <?php echo $left; ?>;
	background-color: #CCCCCC;
}
.p_561b57 .cellA {
	width: 15px;
	height: 20px;
	font-family: Courier, Verdana;
	font-size: 8px;
	float: <?php echo $left; ?>;
	background-color: #006600;
	visibility: hidden;
}

