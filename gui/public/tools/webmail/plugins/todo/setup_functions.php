<?php
// setup functions 

function todo_load_prefs_do() {
	global $username, $data_dir, $todo_things, $todo_first_login, $todo_show_left;

	$todo_first_login = getPref($data_dir, $username, 'todo_first_login');
	$todo_show_left_todos = getPref($data_dir, $username, 'todo_show_left_todos');
	$todo_show_login_todos = getPref($data_dir, $username, 'todo_show_login_todos');	
	$todo_use_todo_priority = getPref($data_dir, $username, 'todo_use_todo_priority');
	$hashed_dir = getHashedDir($username, $data_dir);
	$todo_things = file_exists("$hashed_dir/$username.todo");

}

function todo_show_has_reminders_do() {
	global $color, $todo_things, $my_page_first_shown, $username, $todo_first_login, $data_dir,
	  $todo_od_lt_m_bg, $todo_od_lt_m_fg, $todo_od_lt_y_bg, $todo_od_lt_y_fg, $todo_od_gt_y_bg, $todo_od_gt_y_fg;

	$todo_show_login_todos = getPref($data_dir, $username, 'todo_show_login_todos');	
	if ( ($todo_first_login == 1)  && ($todo_things == true) && ($todo_show_login_todos == 1)) {
		if (defined('SM_PATH')) {
		  include_once(SM_PATH . 'plugins/todo/functions.php');
		  include_once(SM_PATH . 'plugins/todo/config.php');
		}
		else {
		  include_once('../plugins/todo/functions.php');
		  include_once('../plugins/todo/config.php');
		}
		echo "<br><table align=center width=\"70%\" cellpadding=0 cellspacing=3 border=0 bgcolor=\"$color[9]\">";
		echo '<tr><td>';
		echo "<table width=\"100%\" cellpadding=5 cellspacing=1 border=0 bgcolor=\"$color[4]\">";
		echo "<tr><td align=center>"; 
		echo _("Welcome") . " <strong>$username</strong>, ";
		echo _("you have unfinished tasks. ");
		if(!todo_within_deadline($todos)) {
			echo '<br />' . _("These tasks are overdue:") . '<br />';
			todo_init($todos);
			$ntodos = todo_count($todos);
			echo '<table border="0" width="100%"><tr style="{font-weight: bold;}"><td>' . _("Task")
			  . '</td><td align="right">' . _("Deadline") . "</td></tr>\n";
			for($i=0;$i<$ntodos;$i++) {
				$deadline = todo_get_todo_dl($todos, $i); 
				$title = todo_get_todo_title($todos, $i);
				$overdue = todo_overdue_days($todos, $i);
				
				if ($overdue > 0) {
					if ($overdue > 365 )
					  $od_hl = "background-color: $todo_od_gt_y_bg; color: $todo_od_gt_y_fg; font-weight: bold;";
					else if ($overdue > 30)
					  $od_hl = "background-color: $todo_od_lt_y_bg; color: $todo_od_lt_y_fg;";
					else if ($overdue > 0)
					  $od_hl = "background-color: $todo_od_lt_m_bg; color: $todo_od_lt_m_fg;";
					
					echo "<tr><td style=\"{$od_hl}\">\n";
					echo "<a href=\"../../plugins/todo/todo.php?todo_action=view&todo_index=$i\" target=\"right\" style=\"{$od_hl}\">\n";
					echo "$title</a></td><td align=\"right\" style=\"{$od_hl}\">$deadline</td></tr>\n";
				}
			}
			echo '</table>';
		}	
		echo '</td></tr></table></td></tr></table>';
	}
	$todo_first_login = 0;
	setPref($data_dir, $username, 'todo_first_login', '0');
}

function todo_show_reminders_do() {
	global $color, $todo_first_login, $todo_things, $my_page_first_shown, $data_dir, $username, $todo_show_left_todos,
	  $todo_od_lt_m_bg, $todo_od_lt_m_fg, $todo_od_lt_y_bg, $todo_od_lt_y_fg, $todo_od_gt_y_bg, $todo_od_gt_y_fg,
	  $todo_use_todo_priority;;
	
	echo "\n<!-- todo-plugin -->\n";	
	$todo_show_left_todos = getPref($data_dir, $username, 'todo_show_left_todos');	
	$todo_use_todo_priority = getPref($data_dir, $username, 'todo_use_todo_priority');
	if (($todo_things == true) && ($todo_show_left_todos == 1)) {
		if (defined('SM_PATH')) {
		  include_once(SM_PATH . 'plugins/todo/functions.php');
		  include_once(SM_PATH . 'plugins/todo/config.php');
		}
		else {
		  include_once('../plugins/todo/functions.php');
		  include_once('../plugins/todo/config.php');
		}
		echo "<br><table align=center width=\"90%\" cellpadding=0 cellspacing=1 border=0 bgcolor=\"$color[9]\">";
		echo '<tr><td>';
		echo "\n<table width=\"100%\" cellpadding=1 cellspacing=1 border=0 bgcolor=\"$color[4]\">";
		echo "<tr><td align=\"left\" style=\"{font-size: small;}\">\n";
		todo_init($todos);
		$ntodos = todo_count($todos);
		echo '<table border="0" width="100%"><tr style="{font-weight: bold; font-size: small}"><td style="{font-size: small;}">' . _("Task")
		  . '</td><td align="right" style="{font-size: small;}">' . _("Deadline") . "</td></tr>\n";
		for($i=0;$i<$ntodos;$i++) {
			$deadline = todo_get_todo_dl($todos, $i); 
			$title = todo_get_todo_title($todos, $i);
			$priority = todo_get_todo_priority($todos, $i);
			$overdue = todo_overdue_days($todos, $i);
			
			if ($overdue > 365 )
			  $od_hl = "background-color: $todo_od_gt_y_bg; color: $todo_od_gt_y_fg; font-weight: bold;";
			else if ($overdue > 30)
			  $od_hl = "background-color: $todo_od_lt_y_bg; color: $todo_od_lt_y_fg;";
			else if ($overdue > 0)
			  $od_hl = "background-color: $todo_od_lt_m_bg; color: $todo_od_lt_m_fg;";
			else {
				if($todo_use_todo_priority == 1) {
					$od_hl = 'color: '. getPref($data_dir, $username, 'todo_prio_'.$priority.'_color') . ';';
				}
				else
				  $od_hl = '';
			}
				
			
			echo "<tr><td style=\"{font-size: small;$od_hl}\">\n";
			echo "<a href=\"../plugins/todo/todo.php?todo_action=view&todo_index=$i\" target=\"right\" style=\"{font-size: small; $od_hl}\">\n";
			echo "$title</a></td><td align=\"right\" style=\"{font-size: small;$od_hl}\">$deadline</td></tr>\n";
		}
		echo '</table>';
		
		echo '</td></tr></table></td></tr></table>';
		echo "\n<!-- todo-plugin -->\n";
	}
}

function todo_option_display_do()
{
//	global $todo_show_left_todos, $todo_use_todo_priority, $todo_show_login_todos;
	global $username, $data_dir ;
//  include_once('../functions/i18n.php');
//  bindtextdomain('show_user_and_ip', '../plugins/show_user_and_ip/locale');
//  textdomain('show_user_and_ip');
	if (defined(SM_PATH)) {
	    include_once(SM_PATH . 'plugins/todo/config.php');
	} else {
	    include_once('../plugins/todo/config.php');
	}
	$todo_show_left_todos = getPref($data_dir, $username, 'todo_show_left_todos');
	$todo_show_login_todos = getPref($data_dir, $username, 'todo_show_login_todos');	
	$todo_use_todo_priority = getPref($data_dir, $username, 'todo_use_todo_priority');

	$todo_prio_1_color = getPref($data_dir, $username, 'todo_prio_1_color');
	if($todo_prio_1_color == '')
	  $todo_prio_1_color = $GLOBALS['todo_prio_1_color'];
	$todo_prio_2_color = getPref($data_dir, $username, 'todo_prio_2_color');
	if($todo_prio_2_color == '')
	  $todo_prio_2_color = $GLOBALS['todo_prio_2_color'];
	$todo_prio_3_color = getPref($data_dir, $username, 'todo_prio_3_color');
	if($todo_prio_3_color == '')
	  $todo_prio_3_color = $GLOBALS['todo_prio_3_color'];
	$todo_prio_4_color = getPref($data_dir, $username, 'todo_prio_4_color');
	if($todo_prio_4_color == '')
	  $todo_prio_4_color = $GLOBALS['todo_prio_4_color'];
	$todo_prio_5_color = getPref($data_dir, $username, 'todo_prio_5_color');
	if($todo_prio_5_color == '')
	  $todo_prio_5_color = $GLOBALS['todo_prio_5_color'];

	$todo_prio_6_color = getPref($data_dir, $username, 'todo_prio_6_color');
	if($todo_prio_6_color == '')
	  $todo_prio_6_color = $GLOBALS['todo_prio_6_color'];
	$todo_prio_7_color = getPref($data_dir, $username, 'todo_prio_7_color');
	if($todo_prio_7_color == '')
	  $todo_prio_7_color = $GLOBALS['todo_prio_7_color'];
	$todo_prio_8_color = getPref($data_dir, $username, 'todo_prio_8_color');
	if($todo_prio_8_color == '')
	  $todo_prio_8_color = $GLOBALS['todo_prio_8_color'];
	$todo_prio_9_color = getPref($data_dir, $username, 'todo_prio_9_color');
	if($todo_prio_9_color == '')
	  $todo_prio_9_color = $GLOBALS['todo_prio_9_color'];
	$todo_prio_10_color = getPref($data_dir, $username, 'todo_prio_10_color');
	if($todo_prio_10_color == '')
	  $todo_prio_10_color = $GLOBALS['todo_prio_10_color'];

	echo '<tr><td colspan=2><hr width=400 /></td></tr>';
	echo '<tr><td colspan=2 align="center"><b>To-Do Options</td></tr>';
	echo '<tr><td align="right" nowrap valign="top">' . _("Show My To-Do items under Folders List:") . '</td><td>';

	//  textdomain('squirrelmail');
	echo '<label><input type="radio" name="todo_show_left_todos" value="1"';
	if ( $todo_show_left_todos == 1) echo ' checked="checked"';
	echo ' />&nbsp;' . _("Yes") . '</label>&nbsp;&nbsp;&nbsp;&nbsp;';
	echo '<label><input type="radio" name="todo_show_left_todos" value="0"';
	if ( $todo_show_left_todos == 0) echo ' checked="checked"';
	echo ' />&nbsp;' . _("No") . '</label></td></tr>';

	echo '<tr><td align="right" nowrap valign="top">' . _("Nag about overdue tasks at login:") . '</td><td>';

	echo '<label><input type="radio" name="todo_show_login_todos" value="1"';
	if ( $todo_show_login_todos == 1) echo ' checked="checked"';
	echo ' />&nbsp;' . _("Yes") . '</label>&nbsp;&nbsp;&nbsp;&nbsp;';
	echo '<label><input type="radio" name="todo_show_login_todos" value="0"';
	if ( $todo_show_login_todos == 0) echo ' checked="checked"';
	echo ' />&nbsp;' . _("No") . '</label></td></tr>';

	echo '<tr><td align="right" nowrap valign="top">' . _("Use To-Do Priorities:") . '</td><td>';

	echo '<label><input type="radio" name="todo_use_todo_priority" value="1"';
	if ( $todo_use_todo_priority == 1) echo ' checked="checked"';
	echo ' />&nbsp;' . _("Yes") . '</label>&nbsp;&nbsp;&nbsp;&nbsp;';
	echo '<label><input type="radio" name="todo_use_todo_priority" value="0"';
	if ( $todo_use_todo_priority == 0) echo ' checked="checked"';
	echo ' />&nbsp;' . _("No") . '</label></td></tr>';

	
	
	echo '<tr><td align="right" nowrap valign="top">' . _("Priority 1 color:") . '</td><td>';	
	echo "<label><input type='text' name='todo_prio_1_color' value='$todo_prio_1_color' size='7' />";
	echo '<tr><td align="right" nowrap valign="top">' . _("Priority 2 color:") . '</td><td>';	
	echo "<label><input type='text' name='todo_prio_2_color' value='$todo_prio_2_color' size='7' />";
	echo '<tr><td align="right" nowrap valign="top">' . _("Priority 3 color:") . '</td><td>';	
	echo "<label><input type='text' name='todo_prio_3_color' value='$todo_prio_3_color' size='7' />";
	echo '<tr><td align="right" nowrap valign="top">' . _("Priority 4 color:") . '</td><td>';	
	echo "<label><input type='text' name='todo_prio_4_color' value='$todo_prio_4_color' size='7' />";
	echo '<tr><td align="right" nowrap valign="top">' . _("Priority 5 color:") . '</td><td>';	
	echo "<label><input type='text' name='todo_prio_5_color' value='$todo_prio_5_color' size='7' />";

	echo '<tr><td align="right" nowrap valign="top">' . _("Priority 6 color:") . '</td><td>';	
	echo "<label><input type='text' name='todo_prio_6_color' value='$todo_prio_6_color' size='7' />";
	echo '<tr><td align="right" nowrap valign="top">' . _("Priority 7 color:") . '</td><td>';	
	echo "<label><input type='text' name='todo_prio_7_color' value='$todo_prio_7_color' size='7' />";
	echo '<tr><td align="right" nowrap valign="top">' . _("Priority 8 color:") . '</td><td>';	
	echo "<label><input type='text' name='todo_prio_8_color' value='$todo_prio_8_color' size='7' />";
	echo '<tr><td align="right" nowrap valign="top">' . _("Priority 9 color:") . '</td><td>';	
	echo "<label><input type='text' name='todo_prio_9_color' value='$todo_prio_9_color' size='7' />";
	echo '<tr><td align="right" nowrap valign="top">' . _("Priority 10 color:") . '</td><td>';	
	echo "<label><input type='text' name='todo_prio_10_color' value='$todo_prio_10_color' size='7' />";

	echo '<tr><td colspan=2><hr width=400 /></td></tr>';	
	
}

function todo_option_save_do()
{
	global $username, $data_dir;
	sqgetGlobalVar('todo_show_left_todos',$todo_slt,SQ_POST);
    setPref($data_dir, $username, 'todo_show_left_todos', $todo_slt);
	sqgetGlobalVar('todo_show_login_todos',$todo_stl,SQ_POST);
    setPref($data_dir, $username, 'todo_show_login_todos', $todo_stl);
	sqgetGlobalVar('todo_use_todo_priority',$todo_up,SQ_POST);
    setPref($data_dir, $username, 'todo_use_todo_priority', $todo_up);

	sqgetGlobalVar('todo_prio_1_color',$todo_tp1,SQ_POST);
    setPref($data_dir, $username, 'todo_prio_1_color', $todo_tp1);
	sqgetGlobalVar('todo_prio_2_color',$todo_tp2,SQ_POST);
    setPref($data_dir, $username, 'todo_prio_2_color', $todo_tp2);
	sqgetGlobalVar('todo_prio_3_color',$todo_tp3,SQ_POST);
    setPref($data_dir, $username, 'todo_prio_3_color', $todo_tp3);
	sqgetGlobalVar('todo_prio_4_color',$todo_tp4,SQ_POST);
    setPref($data_dir, $username, 'todo_prio_4_color', $todo_tp4);
	sqgetGlobalVar('todo_prio_5_color',$todo_tp5,SQ_POST);
    setPref($data_dir, $username, 'todo_prio_5_color', $todo_tp5);

	sqgetGlobalVar('todo_prio_6_color',$todo_tp6,SQ_POST);
    setPref($data_dir, $username, 'todo_prio_6_color', $todo_tp6);
	sqgetGlobalVar('todo_prio_7_color',$todo_tp7,SQ_POST);
    setPref($data_dir, $username, 'todo_prio_7_color', $todo_tp7);
	sqgetGlobalVar('todo_prio_8_color',$todo_tp8,SQ_POST);
    setPref($data_dir, $username, 'todo_prio_8_color', $todo_tp8);
	sqgetGlobalVar('todo_prio_9_color',$todo_tp9,SQ_POST);
    setPref($data_dir, $username, 'todo_prio_9_color', $todo_tp9);
	sqgetGlobalVar('todo_prio_10_color',$todo_tp10,SQ_POST);
    setPref($data_dir, $username, 'todo_prio_10_color', $todo_tp10);
	
}

?>
