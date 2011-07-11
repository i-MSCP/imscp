<?php

/***************************************************
 * Configuration file for Show User and IP plugin. *
 * Configure it to suit your needs.                *
 ***************************************************/

global $sui_show_host_on_motd, $sui_motd_greet_with_welcome,
       $sui_font_size, $sui_align;

/*
 * Option to show hostname in the welcome
 * message incorporated into MOTD.
 *
 * Default: Show
 * 0: Don't Show / 1: Show
*/

$sui_show_host_on_motd = 1;


/*
 * Option to show "Welcome" or "INFO:" string in
 * the welcome message. If you already have a MOTD
 * configured, which already welcomes your users,
 * you can set this to "0" to show "INFO:" instead
 * of "Welcome".
 *
 * Default: Welcome
 * 0: "INFO:" / 1: "Welcome"
*/

$sui_motd_greet_with_welcome = 1;


/*
 * Font size for username, IP and hostname displayed
 * on the left pane.
 *
 * Default: -1
*/

$sui_font_size = -1;

/*
 * Alignment for the information on the left pane.
 * It can be "left", "center" or "right".
 * 
 * Default: "center"
*/
 
$sui_align = "center";
?>
