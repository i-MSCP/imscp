<?php
/**
 * create_po.php
 *
 * @author Jimmy Conner <jimmy@sqmail.org>
 * @copyright Copyright &copy; 2005, Jimmy Conner (Licensed under the GNU GPL see "LICENSE")
 *
 * @package plugins
 * @subpackage notes
 * @version $Id: create_po.php,v 1.1 2005/01/05 15:02:26 cigamit Exp $
 */

$lang = array();
v("../");

function v($a = "", $sp = "") {
   global $lang;
   $d = @dir($a);
   while (false !== ($entry = $d->read())) {
      if (@is_file($a . $entry)) {
         print $sp . "File - $a$entry<br>\n\r";
         $file = fopen ($a . $entry, 'r');
         $l = 0;
         $x = 0;
         while (!feof($file)) {
            $l++;
            $f = fgets($file, 90000);
            preg_match_all ("/\_\(\"+[0-9a-zA-Z\/\s\.\,\'\(\)\!\_]+\"\)/", $f, $matches);
            if (isset($matches[0][0])) {
               for ($c = 0; $c < count($matches[0]); $c++) {
                  preg_match_all ("/[0-9a-zA-Z\/\s\.\,\'\(\)\!\_]+/", $matches[0][$c], $match);
                  $found = false;
                  for ($b = 0; $b < count($lang); $b++) {
                     if ($lang[$b]['text'] == $match[0][1]) {
                        $found = true;
                        $lang[$b]['file'] = $a . $entry . ':' . "$l\r\n#: " . $lang[$b]['file'];
                        $x++; 
                     }
                  }
                  if ($found == false) {
                     $lang[] = array('file' => $a . $entry, 'line' => $l, 'text' => $match[0][1]);
                     $x++;
                  }
               }
            }
         }
         fclose($file);
         print $sp . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;--- $x Found<br>\r\n";
      }
      if (@is_dir($a . $entry)) {
         if ($entry != '.' && $entry != '..' && $entry != 'locale') {
            print $sp . "Dir - $a$entry<br>\n";
            v($a . $entry . "/", $sp . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
         }
      }
   }
}


$file = fopen('notes.po','wb');
fwrite($file, "# SOME DESCRIPTIVE TITLE.\r
# Copyright (C) YEAR THE PACKAGE'S COPYRIGHT HOLDER\r
# This file is distributed under the same license as the PACKAGE package.\r
# FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.\r
#\r
#, fuzzy\r
msgid \"\"\r
msgstr \"\"\r
\"Project-Id-Version: PACKAGE VERSION\\n\"\r
\"POT-Creation-Date: 2003-3-1 22:19-0700\\n\"\r
\"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n\"\r
\"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n\"\r
\"Language-Team: LANGUAGE <LL@li.org>\\n\"\r
\"MIME-Version: 1.0\\n\"\r
\"Content-Type: text/plain; charset=CHARSET\\n\"\r
\"Content-Transfer-Encoding: 8bit\\n\"\r\n");

for ($a = 0; $a < count($lang); $a++) {
   fwrite($file, "\r\n\r\n#: " . $lang[$a]['file'] . ':' . $lang[$a]['line'] . "\r\n");
   fwrite($file, 'msgid "' . $lang[$a]['text'] . "\"\r\n");
   fwrite($file, 'msgstr ""');
}
fwrite($file, "\r\n\r\n");
fclose($file);

?>