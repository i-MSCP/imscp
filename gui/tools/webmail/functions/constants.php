<?php

/**
 * constants.php
 *
 * Loads constants used by the rest of the SquirrelMail source.
 * This file is include by src/login.php, src/redirect.php and
 * src/load_prefs.php.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: constants.php,v 1.18.2.5 2006/04/14 22:27:07 jervfors Exp $
 * @package squirrelmail
 * @since 1.2.0
 */

/** Need to enable plugin functions for a hook */
require_once(SM_PATH . 'functions/plugin.php');  /* Required for the hook */

/**************************************************************/
/* Set values for constants used by Squirrelmail preferences. */
/**************************************************************/

/* Define basic, general purpose preference constants. */
define('SMPREF_NO', 0);
define('SMPREF_OFF', 0);
define('SMPREF_YES', 1);
define('SMPREF_ON', 1);
define('SMPREF_NONE', 'none');

/* Define constants for location based preferences. */
define('SMPREF_LOC_TOP', 'top');
define('SMPREF_LOC_BETWEEN', 'between');
define('SMPREF_LOC_BOTTOM', 'bottom');
define('SMPREF_LOC_LEFT', '');
define('SMPREF_LOC_RIGHT', 'right');

/* Define preferences for folder settings. */
define('SMPREF_UNSEEN_NONE', 1);
define('SMPREF_UNSEEN_INBOX', 2);
define('SMPREF_UNSEEN_ALL', 3);
define('SMPREF_UNSEEN_SPECIAL', 4); // Only special folders
define('SMPREF_UNSEEN_NORMAL', 5);  // Only normal folders
define('SMPREF_UNSEEN_ONLY', 1);
define('SMPREF_UNSEEN_TOTAL', 2);

/* Define constants for time/date display preferences. */
define('SMPREF_TIME_24HR', 1);
define('SMPREF_TIME_12HR', 2);

/* Define constants for javascript preferences. */
define('SMPREF_JS_OFF', 0);
define('SMPREF_JS_ON', 1);
define('SMPREF_JS_AUTODETECT', 2);

do_hook('loading_constants');

?>