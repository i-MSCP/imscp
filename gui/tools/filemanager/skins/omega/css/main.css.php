<?php
header("Content-type: text/css");
if (isset($_GET["ltr"]) == true && $_GET["ltr"] != "rtl") { $left = "left"; $right = "right"; }
else                                                      { $left = "right"; $right = "left"; }
if (isset($_GET["image_url"]) == true) { $image_url = preg_replace("/[\\:\\*\\?\\<\\>\\|]/", "", $_GET["image_url"]); }
else                                   { $image_url = ""; }
?>

/* CSS document colors
#666666
#5893ac
#eeeeee
#F2F2F5
#A7A7A7
#787878
*/

* {
	padding: 0;
	margin: 0;
}

body {
	background-color: #c0c0c0;
	font-family: 'Lucida Grande', Verdana, Arial, Sans-Serif;
	font-size: 62.5%; /* Resets 1em to 10px */
	color: #000;
	text-align: center;
	margin-top: 0;
	height:100%;
}

#container {
	margin-<?php echo $left; ?>: auto;
	margin-<?php echo $right; ?>: auto;
	margin-top: 0px;
	margin-bottom: 20px;
	width: 980px;
	font-size: 1.2em;
	text-align: <?php echo $left; ?>;
	height:100%;
}

#head {
	padding-top: 20px;
	padding-<?php echo $left; ?>: 20px;
	padding-<?php echo $right; ?>: 20px;
	padding-bottom: 20px;
	height: 87px;
	background: url(<?php echo $image_url; ?>/img/bg_head.jpg) no-repeat;
}

#headleft {
	float:<?php echo $left; ?>;
}

#headright {
	float:<?php echo $right; ?>;
	width:470px;
}

#head<?php echo $right; ?> h2 {
	color: #FFF;
}

#main {
	background: #ffffff;
	padding: 10px;
}

#menu {
	width: 200px;
	float: <?php echo $left; ?>;
}

.menuhead {
	background: url(<?php echo $image_url; ?>/img/bg_tabletop.png);
	height: 18px;
	width: 200px;
	color: #FFF;
	padding-top: 2px;
	font-weight: bold;
	text-align: center;
}

.menucontent {
	background: #eeeeee;
	color: #000000;
	width: 193px;
	padding-<?php echo $left; ?>: 5px;
	padding-top: 10px;
	padding-bottom: 10px;
	border-bottom: solid 1px #666666;
	border-<?php echo $left; ?>: solid 1px #666666;
	border-<?php echo $right; ?>: solid 1px #666666;
}

#content {
	margin-<?php echo $left; ?>: 10px;
	width: 750px;
	float: <?php echo $right; ?>;
}

#mainleft {
	float:<?php echo $left; ?>;
	width: 465px;
}

#mainright {
	float: <?php echo $right; ?>;
	width: 485px;
	margin-<?php echo $left; ?>: 10px;
}

#tabelfeaturestop {
	padding-<?php echo $left; ?>: 10px; background-color: #666666; height: 21px; color:#FFF; padding-top:5px;
}

#tablefeature {
	background: #eeeeee;
	border-<?php echo $left; ?>: #666666 1px solid;
	border-<?php echo $right; ?>: #666666 1px solid;
	border-bottom: #666666 1px solid;
	padding: 2px;
}

#tablefeature span {
	background-image: url(<?php echo $image_url; ?>/img/arrow.gif);
	background-repeat: no-repeat;
	background-position: 0.5em;
	padding-<?php echo $left; ?>: 20px;
	font-weight: bold;
	color: #666666;
}

#tablefeature p {
	padding-<?php echo $left; ?>:20px;
	padding-top: 2px;
	padding-bottom:2px;
}

#foot {
	height: 24px;
	background: url(<?php echo $image_url; ?>/img/bg_foot.jpg) no-repeat;
	text-align: center;
	padding-top: 13px;
	color: #fff;

}

#poweredby {
	padding-top: 10px;
	text-align: center;
	font-size: 0.75em;
}

a, a:link {
	text-decoration: underline;
	color: #666666;
}

a:hover {
	text-decoration: none;
	color: #5893ac;
}

#foot a, #foot a:link {
	text-decoration: none;
	font-weight: bold;
	color: #fff;
}

#foot a:hover {
	text-decoration: none;
	color: #5893ac;
}

/* Tags */
h1, h2, h3 {
	font-family: 'Trebuchet MS', 'Lucida Grande', Verdana, Arial, Sans-Serif;
	font-weight: bold;
	}

h1 {
	font-size: 2.2em;
	color: #666666;
}

h2 {
	font-size: 1.6em;
	color: #666666;
}

h3 {
	font-size: 1.4em;
}

.underline {
	text-decoration:underline;
}

.button {
	color: #666666;
	background: #fff;
	border: solid 1px #666666;
	width: 50px;
	padding: 2px;
}

.smallbutton {
	color: #666666;
	background: #fff;
	border: solid 1px #666666;
	padding: 1px;
}

br {
	line-height: 1em;
}

ul {
	padding-<?php echo $left; ?>: 15px;
}

/* Navigation */
.navlist {
	margin-<?php echo $left; ?>: 0;
	padding-<?php echo $left; ?>: 0;
	list-style: none;
}

.navlist li {
	padding-<?php echo $left; ?>: 20px;
	background-image: url(<?php echo $image_url; ?>/img/arrow.gif);
	background-repeat: no-repeat;
	background-position: 0.5em;
}

.navlist li a, .navlist li a:link {
	text-decoration: none;
	color: #666666;
}

.navlist li a:hover {
	text-decoration: none;
	color: #5893ac;
}

/* Form */

select, input {
	padding: 2px;
	margin-top: 1px;
	margin-<?php echo $right; ?>: 0;
	margin-bottom: 1px;
	margin-<?php echo $left; ?>: 0;
	font-size: 1em;
}

textarea {
	padding: 2px;
}

.warning-box {
	background-color: #FFDD00;
	color: #000000;
	border: 2px;
	border-style: solid;
	border-color: #FFCC33; 
	margin-<?php echo $left; ?>: 0px;
	margin-<?php echo $right; ?>: 10px;
}

.warning-text {
	padding-bottom : 5px;
	padding-top : 5px;
	padding-<?php echo $left; ?> : 10px;
	padding-<?php echo $right; ?> : 10px;
}

.browse_cell {
	width: 120px; 
	height: 60px; 
	font-size: 0.8em; 
	text-align: center; 
	overflow: hidden;
	padding: 3px;
}

.browse_rows_actions {
	background-color: #eeeeee;
	color: #000000;
	font-size: 80%;
	font-weight: normal;
	text-align: <?php echo $left; ?>;
}

.browse_rows_heading {
	background-color: #eeeeee;
	color: #000000;
	font-size: 110%;
	font-weight: bold;
}

.browse_rows_odd, .browse_rows_even {
	color: #000000; 
	font-size: 80%;
	font-weight: normal;
	text-align: <?php echo $left; ?>;
}

.browse_rows_separator {
	border: 2px;
	color: #000000;
	font-size: 100%;
	text-align: <?php echo $left; ?>;
}

/*------------------------------------------------------------------------
   Process bar
From the PHP Pear package HTML_Progress
http://pear.laurent-laville.org/HTML_Progress/examples/horizontal/string.php
------------------------------------------------------------------------*/

.p_ba7428 .progressBar, .p_ba7428 .progressBarBorder {
    width: 172px;
    height: 24px;
    position: relative;
    left: 0;
    top: 0;
}

.p_ba7428 .progressBarBorder {
    border-width: 0;
    border-style: solid;
    border-color: #666666;
}

.p_ba7428 .installationProgress {
    width: 350px;
    text-align: left;
    font-family: Verdana, Arial, Helvetica, sans-serif;
    font-size: 12px;
    color: #000000;
}

.p_ba7428 .cellI, .p_ba7428 .cellA {
    width: 15px;
    height: 20px;
    font-family: Courier, Verdana;
    font-size: 0.8em;
    float: left;
}

.p_ba7428 .cellI {
    background-color: #666666;
}

.p_ba7428 .cellA {
    background-color: #666666;
    visibility: hidden;
}

#buttonsright {
	margin-top: 88px;
	
}
.login {
	font-family: 		geneva, arial, helvetica, sans-serif;
	font-size: 			11px;
	color: 				#000;
	white-space: 		nowrap;
	text-decoration: 	none;
}

a.login, a.login:link, a.login:visited {
	font-family: 		geneva, arial, helvetica, sans-serif;
	font-size: 			11px;
	color: 				#3b73ca;
	white-space: 		nowrap;
	text-decoration: 	underline;
}

a.login:hover, a.login:active, a.login:hover {
	color: 				#000000;
	text-decoration: 	none;
}