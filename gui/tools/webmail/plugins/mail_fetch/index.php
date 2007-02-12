<?php

/**
 * index.php
 *
 * This file simply takes any attempt to view source files and sends those
 * people to the login screen. At this point no attempt is made to see if the
 * person is logged in or not.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: index.php,v 1.2.2.6 2006/02/03 22:27:52 jervfors Exp $
 * @package plugins
 * @subpackage mail_fetch
 */

header('Location: ../index.php');

?>