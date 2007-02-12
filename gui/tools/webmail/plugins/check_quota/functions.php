<?php

function check_quota_left_do() {

global $cq_check_quota_type;

	if ( $cq_check_quota_type == 0 )
	{
		$fn_left = 1;
		include_once("check_imap.php");
	}
	elseif ( $cq_check_quota_type == 1 )
	{
		$fn_left = 1;
		include_once ("check_unix.php");
 	}
}

function check_quota_MOTD_do() {

global $cq_check_quota_type;

	if ( $cq_check_quota_type == 0 )
	{
		$fn_motd = 1;
		include_once("check_imap.php");
	}
	elseif ( $cq_check_quota_type == 1 )
	{
		$fn_motd = 1;
		include_once ("check_unix.php");
 	}
}

?>
