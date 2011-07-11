<?php

/**
 * index.php
 *
 * This file simply takes any attempt to view source files and sends those
 * people to the login screen. At this point no attempt is made to see if the
 * person is logged in or not.
 *
 * @copyright 1999-2010 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: index.php 13893 2010-01-25 02:47:41Z pdontthink $
 * @package squirrelmail
 * @subpackage mime
 */

header('Location: ../index.php');

