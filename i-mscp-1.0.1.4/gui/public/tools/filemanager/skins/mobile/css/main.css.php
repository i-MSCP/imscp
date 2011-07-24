<?php
header("Content-type: text/css");
if (isset($_GET["ltr"]) == false || $_GET["ltr"] != "rtl") { $left = "left"; $right = "right"; }
else                                                       { $left = "right"; $right = "left"; }
?>

body { background-color : #FFFFFF; color: #000000; font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 8pt; margin: 1px; }
A:link { color: #0000FF; text-decoration: underline; }
A:visited { color: #0000FF; text-decoration: underline; }
A:hover {}
li { margin-top: 2px; }
h1, h2, h3, p, form { margin: 0; }
.input { font-size: 8pt; height: 10px; }
.smallbutton { font-size: 8pt; }
.browse_top { background-color: #000000; color: #FFFFFF; font-weight: bold; padding: 5px; margin: 2px; }
.browse_warning { background-color: #FFFF00; padding: 5px; margin: 2px; }
.browse_actions { padding: 5px; margin: 2px; text-align: <?php echo $right; ?>; }
.browse_up { padding: 2px; margin: 2px; font-weight: bold; text-align: center; }
.browse_row { background-color: #CCCCCC; font-weight: bold; padding: 2px; margin: 2px; }