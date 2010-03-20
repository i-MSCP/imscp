<?php 
// ispCP Modification to avoid vulnerability 
// http://packetstormsecurity.org/1003-exploits/ispcp-rfi.txt
// Make sure this file is included by net2ftp, not accessed directly
defined("NET2FTP") or die("Direct access to this location is not allowed.");
// End of Modification
require_once($net2ftp_globals["application_skinsdir"] . "/blue/admin1.template.php"); ?>
