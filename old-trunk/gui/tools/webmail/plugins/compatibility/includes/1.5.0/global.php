<?php



//
// $base_uri is not globally available in some
// places in 1.4.x, but it is in 1.5.x... although
// plugin authors are encouraged to use sqm_baseuri()
// which is known to work in both environments
//
// need to run php_self() before we call sqm_baseuri()
//
// sqm_baseuri was moved from display_messages.php
// as of version 1.4.6... HOWEVER, we cannot include
// display_messages.php here because of some chicken-
// egg problems that are then introduced and all
// plugins will stop working.  The only solution is to
// copy the sqm_baseuri() code directly here to calculate
// $base_uri... which is overall, a rather inefficient
// (albiet in a small way in the scheme of things) way
// to provide this compatibility of $base_uri to plugin
// authors in 1.4.5 and below.
//
global $PHP_SELF, $base_uri;
$PHP_SELF = php_self();
if (!compatibility_check_sm_version(1, 4, 6))
{
   $dirs = array('|src/.*|', '|plugins/.*|', '|functions/.*|');
   $repl = array('', '', '');
   $base_uri = preg_replace($dirs, $repl, $PHP_SELF);
}
else
{
   $base_uri = sqm_baseuri();
}



