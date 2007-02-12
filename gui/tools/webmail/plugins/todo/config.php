<?php
/*******************************************************************************

    Author ......... Wolf Bergenheim
    Contact ........ wolf@bergenheim.net
    Home Site ...... http://wolf.bergenheim.net/src
    Program ........ To-Do Reminder
    Version ........ 0.5
    Purpose ........ Storing and displaying reminders
    Thanks To ...... Jimmy Conner for the Notes plugin, and
		   	  		 Kerem Erkan for the show_user_and_ip plugin, and
                     Tomas Kulivas for bugfixing / teasting with SM 1.4.2

*******************************************************************************/

#overdue less than a month colors
$GLOBALS['todo_od_lt_m_fg'] = '#aa7700';
$GLOBALS['todo_od_lt_m_bg'] = '$color[4]';

#overdue less than a year colors
$GLOBALS['todo_od_lt_y_fg'] = '#ff0000';
$GLOBALS['todo_od_lt_y_bg'] = '$color[4]';

#overdue more than a year colors
$GLOBALS['todo_od_gt_y_fg'] = '#000000';
$GLOBALS['todo_od_gt_y_bg'] = '#ff0000';

$GLOBALS['todo_maxcount'] = 15;
// Max size of a todo item (to disable, just set to 0)
$GLOBALS['todo_maxsize'] = 1500;
// Max size of all todo items (to disable, just set to 0)
$GLOBALS['todo_maxtotsize'] = 10000;

$GLOBALS['todo_prio_1_color'] = '#CC3300';
$GLOBALS['todo_prio_2_color'] = '#00cc00';
$GLOBALS['todo_prio_3_color'] = '#0066CC';
$GLOBALS['todo_prio_4_color'] = '#009900';
$GLOBALS['todo_prio_5_color'] = '#007700';
$GLOBALS['todo_prio_6_color'] = '#006600';
$GLOBALS['todo_prio_7_color'] = '#005500';
$GLOBALS['todo_prio_8_color'] = '#004400';
$GLOBALS['todo_prio_9_color'] = '#003300';
$GLOBALS['todo_prio_10_color'] = '#001100';

?>
