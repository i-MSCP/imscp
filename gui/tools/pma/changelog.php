<?php
// Simple script to set correct charset for changelog
/* $Id: changelog.php,v 2.1 2004/06/24 09:24:46 nijel Exp $ */
// vim: expandtab sw=4 ts=4 sts=4:

header('Content-type: text/plain; charset=utf-8');
readfile('ChangeLog');
?>
