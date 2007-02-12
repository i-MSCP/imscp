<?php

global $motd, $cq_red_alert_percent, $cq_show_red_alert_MOTD,
       $cq_countpercent, $cq_yellow_alert_percent,
       $cq_show_yellow_alert_MOTD, $cq_yellow_alert_MOTD,
       $cq_red_alert_MOTD;

bindtextdomain('check_quota', SM_PATH . 'plugins/check_quota/locale');
textdomain('check_quota');

if ( empty($cq_red_alert_MOTD) ) 
	$cq_red_alert_MOTD = _("<strong>WARNING:</strong> Your quota usage is currently <strong>###PERCENT_USED###%</strong>. To avoid losing any email, you should immediately empty out your Trash and Sent folders and delete any emails with large attachments.");

if ( empty($cq_yellow_alert_MOTD) ) 
	$cq_yellow_alert_MOTD = _("<strong>WARNING:</strong> Your quota usage is currently <strong>###PERCENT_USED###%</strong>. You may want to make sure you empty out your Trash and clean your Sent folder.");

/*   
 * display potential warnings in this order (first one wins):
 *   - size usage is critical
 *   - message count is critical
 *   - size usage is yellow alert
 *   - message count is yellow alert
 */

global $motd;

if ( strlen($motd) > 0 )
	$cq_motd_append = "<br><br>";
else
	$cq_motd_append = '';

if ( $cq_percent != '' && $cq_percent >= $cq_red_alert_percent && $cq_show_red_alert_MOTD )
{
	$cq_red_alert_MOTD =
		str_replace('###PERCENT_USED###', $cq_percent, $cq_red_alert_MOTD);
	$motd .= $cq_motd_append . $cq_red_alert_MOTD;
	$fn_motd = 0;
}
elseif ( $cq_countpercent != '' && $cq_countpercent >= $cq_red_alert_percent && $cq_show_red_alert_MOTD )
{
	$cq_red_alert_MOTD =
		str_replace('###PERCENT_USED###', $cq_countpercent, $cq_red_alert_MOTD);
	$motd .= $cq_motd_append . $cq_red_alert_MOTD;
	$fn_motd = 0;
} 
elseif ( $cq_percent != '' && $cq_yellow_alert_percent && $cq_yellow_alert_percent > 0
	&& $cq_percent >= $cq_yellow_alert_percent && $cq_show_yellow_alert_MOTD ) 
{
	$cq_yellow_alert_MOTD =
		str_replace('###PERCENT_USED###', $cq_percent, $cq_yellow_alert_MOTD);
	$motd .= $cq_motd_append . $cq_yellow_alert_MOTD;
	$fn_motd = 0;
}
elseif ( $cq_countpercent != '' && $cq_yellow_alert_percent && $cq_yellow_alert_percent > 0
	&& $cq_countpercent >= $cq_yellow_alert_percent && $cq_show_yellow_alert_MOTD ) 
{
	$cq_yellow_alert_MOTD =
		str_replace('###PERCENT_USED###', $cq_countpercent, $cq_yellow_alert_MOTD);
	$motd .= $cq_motd_append . $cq_yellow_alert_MOTD;
	$fn_motd = 0;
}

bindtextdomain('squirrelmail', SM_PATH . 'locale');
textdomain('squirrelmail');

?>
