<?php
/**
 * keyring_main_chunk.php
 *
 * Included by keyring to show first previous next last interface
 * in keyring_main.php
 * 
 *
 * Copyright (c) 1999-2005 The SquirrelMail development team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Copyright (c) 2002-2005 Braverock Ventures
 *
 * $Id$
 *
 */


//Showing 
echo '<font size=-1>' . _("Showing") . ' ';
//# start count
if (count($keymap) == 0) { echo "0"; } else { echo (($pos * $chunkSize) + 1); } 
// through
echo '-';
// end count
echo (($pos * $chunkSize) + count($keymap)); 
// of total number
echo ' ' . _("of") . ' ' . ($ring->numKeys());

if ($pos > 1) {
  echo "| <a href='keyring_main.php?pos=0&$thru'>" . _("First") . "</a> ";
}
else {
  echo "| <font color='#dcdcdc'>" . _("First") . "</font> ";
}

if (array_key_exists($pos - 1, $chunkMap)) {
  echo "| <a href='keyring_main.php?pos=" . urlencode($pos - 1) . "&$thru'>" . _("Previous") . "</a> ";
}
else {
  echo "| <font color='#dcdcdc'>" . _("Previous") . "</font> ";
}

if (array_key_exists($pos + 1, $chunkMap)) {
  echo "| <a href='keyring_main.php?pos=" . urlencode($pos + 1) . "&$thru'>" . _("Next") . "</a> ";
}
else {
  echo "| <font color='#dcdcdc'>" . _("Next") . "</font> ";
}

if (array_key_exists($pos + 2, $chunkMap)) {
  echo "| <a href='keyring_main.php?pos=" . urlencode(count($chunkMap) - 1) . "&$thru'>". _("Last") . "</a> ";
}
else {
  echo "| <font color='#dcdcdc'>" . _("Last") . "</font> ";
}

echo "</font>\n";
?>
