<?php

   /*
    *  Login:Auto Plugin
    *  By Jay Guerette <JayGuerette@pobox.com>
    *  (c) 2001 (GNU GPL - see ../../COPYING)
    *
    *  If you need help with this, or see improvements that can be made, please
    *  email the SquirrelMail Plugins mailing list or try contacting me at
    *  the address above (note as of May 2003, Jay does not seem to be maintaining
    *  this plugin).  I definately welcome suggestions and comments.  This plugin,
    *  as is the case with all SquirrelMail plugins, is not directly supported
    *  by the developers.
    *
    *  View the INSTALL document for information on installing this.  Also view
    *  the README document and plugins/README.plugins for more information.
    *
    */

global $auto_user, $auto_pass, $auto_expire_days, $auto_expire_hours,
       $auto_expire_minutes, $auto_key, $login_doc, $login_link;


// remember the username between logins
   $auto_user	= TRUE;

// remember the password between logins, forces $auto_user=true
   $auto_pass	= TRUE;

// number of days, hours, and minutes after which cookies will expire
   $auto_expire_days    = 7;
   $auto_expire_hours   = 0;
   $auto_expire_minutes = 0;

// encryption key; change it!
   $auto_key	= 'nutsmail';

// multiple key encryption; for the paranoid!
// put in as many as you want, more = more encryption, bigger cookie, more CPU
// $auto_key	= array('changeme','andme','metoo','toomany','omigod');

// a document about login security linked from login page
// provide your own, or copy security.en.php someplace and change this link
   $login_doc	= '../plugins/login_auto/security.en.php';
//   $login_doc	= '';

// customize the link text for the security document, or leave the default
   $login_link	= '';

?>