<?php

/**
  * confirmation_note.tpl
  *
  * Template for displaying a confirmation message on
  * the message view screen for the Add Address plugin.
  *
  * The following variables are available in this template:
  *
  * string  $note  The confirmation text to be displayed
  *
  * Copyright (c) 2008-2009 Paul Lesniewski <paul@squirrelmail.org>,
  * Licensed under the GNU GPL. For full terms see the file COPYING.
  *
  * @package plugins
  * @subpackage add_address
  *
  */


// retrieve the template vars
//
extract($t);


?>
<tr><td colspan="2" align="center"><strong><?php echo $note; ?></strong></td></tr>

