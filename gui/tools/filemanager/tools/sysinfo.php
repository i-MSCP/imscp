<?php
/*
  Weeble File Manager (c) Christopher Michaels & Jonathan Manna
  This software is released under the BSD License.  For a copy of
  the complete licensing agreement see the LICENSE file.
*/
?>
<html>
 <head>
  <title>System Information</title>
  <style>
   body { font-family: sans-serif; }
   table { font-family: sans-serif; }
   .cat { background-color: silver; }
  </style>
 </head>
 <body>
<?php
echo "<table border=0 cellpadding=1 cellspacing=3>";
echo "<tr><td colspan=3 class=\"cat\"><b>Useful PHP Info:</b></td></tr>";
echo "<tr><td colspan=2 class=\"cat\">Version:</td><td>" . phpversion() . "</td></tr>";
if ( phpversion() >= "4.0.2" ) echo "<tr><td colspan=2 class=\"cat\">OS:</td><td>" . php_uname() . "</td></tr>";
if ( phpversion() >= "4.0.1" ) echo "<tr><td colspan=2 class=\"cat\">SAPI:</td><td>" . php_sapi_name() . " / " . getenv ("SERVER_SOFTWARE") . "</td></tr>";

// List extensions loaded into PHP.
echo "<tr><td colspan=3 class=\"cat\">Extensions:</td></tr>";
$arr = get_loaded_extensions ();
foreach ( $arr as $key => $val ) {
  echo "<tr><td></td><td align=\"right\">$key</td><td>$val</td></tr>";
}

echo "<tr><td colspan=2 class=\"cat\">User:</td><td>" . getmyuid() . "</td></tr>";
if ( phpversion() >= "4.1.0" ) echo "<tr><td colspan=2 class=\"cat\">Group:</td><td>" . getmygid() . "</td></tr>";
echo "<tr><td colspan=2 class=\"cat\">PID:</td><td>" . getmypid() . "</td></tr>";

echo "<tr><td colspan=3 class=\"cat\">Usage:</td></tr>";
$arr = getrusage();
foreach ( $arr as $key => $val ) {
  echo "<tr><td></td><td align=\"right\">$key</td><td>$val</td></tr>";
}

echo "<tr><td colspan=3 class=\"cat\">Included File(s):</td></tr>";
$arr = get_included_files();
foreach ( $arr as $key => $val ) {
  echo "<tr><td></td><td>$key</td><td>$val</td></tr>";
}

echo "<tr><td colspan=3 class=\"cat\">Configuration Options:</td></tr>";
$arr = array ( "register_globals", "file_uploads", "upload_max_filesize", "post_max_size" );

foreach ( $arr as $val ) {
  echo "<tr><td></td><td>$val</td><td>" . ini_get ($val) . "</td></tr>";
}

echo "</table>";
?>
 </body>
</html>
