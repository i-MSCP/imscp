<?php
/* error_reporting(E_ALL); */
if(!defined('SM_PATH'))
  define('SM_PATH','../../');

#include_once(SM_PATH . 'functions/plugin.php');
include_once(SM_PATH . 'include/validate.php');
include_once(SM_PATH . 'functions/strings.php');
include_once(SM_PATH . 'config/config.php');
include_once(SM_PATH . 'functions/page_header.php');
include_once(SM_PATH . 'functions/display_messages.php');
include_once(SM_PATH . 'functions/i18n.php');
include_once(SM_PATH . 'functions/prefs.php');

$todo_use_todo_priority = getPref($data_dir, $username, 'todo_use_todo_priority');
include_once('config.php');
include_once('functions.php');

sqgetGlobalVar('todo_action',$todo_action);
if(!isset($todo_action)) {
	$todo_action = '';
}
$hashed_dir = getHashedDir($username, $data_dir);

if($todo_action == 'delete') {
	sqgetGlobalVar('todo_index',$todo_index, SQ_GET);
}

if($todo_action == 'save') {
	sqgetGlobalVar('todo_index',$todo_index, SQ_POST);
	
	sqgetGlobalVar('todo_dl_y',$todo_dl_y, SQ_POST);
	sqgetGlobalVar('todo_dl_m',$todo_dl_m, SQ_POST);
	sqgetGlobalVar('todo_dl_d',$todo_dl_d, SQ_POST);
	$todo_deadline = $todo_dl_y . '-' . $todo_dl_m  . '-' . $todo_dl_d;
	
	sqgetGlobalVar('todo_title',$todo_title, SQ_POST);
	sqgetGlobalVar('todo_desc',$todo_desc, SQ_POST);
	sqgetGlobalVar('todo_priority',$todo_priority, SQ_POST);
}

$todos = '';
$todo_filesize = 0;
todo_init($todos);
$todo_count = todo_count($todos);
$error=0;

if(!todo_within_limit()) {
	if( ( ($todo_action == 'edit') || ($todo_action == 'add')  ) && ($error == 1 || $error == 2) )
	  $todo_action = 'default';
	
	if ($todo_action == 'add' && $error == 4)
	  $todo_action = 'default';
	
	if ($todo_action == 'save' && ($error == 5 || $error == 6))
	  $todo_action = 'add';
}
switch($todo_action) {
 case 'sort':
	$type = sqgetGlobalVar('sort_type',$sort_type, SQ_GET);
	$dir = sqgetGlobalVar('sort_dir',$sort_dir,SQ_GET);
	todo_sort($todos,$sort_dir,$sort_type);
	todo_save_todos($todos,count($todos),"$hashed_dir/$username.todo");
	header("Location: todo.php");
	break;
 case 'save':
	if (isset($todo_index)) {
		if ($todo_maxtotsize > 0 && $todo_maxtotsize < ($todo_filesize + strlen($todo_title . $todo_deadline . $todo_desc)))
		  $todo_error = 5;
		if ($todo_maxsize > 0 && $todo_maxsize < strlen($todo_title . $todo_deadline . $todo_desc))
		  $todo_error = 6;
		if($todo_use_todo_priority == 1)
		  todo_set_todo($todos, $todo_index,$todo_deadline,$todo_title,$todo_desc,$todo_priority);
		else
		  todo_set_todo($todos, $todo_index,$todo_deadline,$todo_title,$todo_desc);
		todo_save_todos($todos,count($todos),"$hashed_dir/$username.todo");
	}
	header("Location: todo.php");
	break;
 case 'delete':
	if (isset($todo_index)){
		for ($i = $todo_index; $i < $todo_count-1; $i++) {
			$todos[$i] = $todos[$i+1];
		}
		$todos[$i] = '';
		$todo_count -= 1;
		todo_save_todos($todos,count($todos),"$hashed_dir/$username.todo");
	}
	header("Location: todo.php");
	break;
 case 'edit' :
	sqgetGlobalVar('todo_index',$todo_index);

	$todo_deadline = todo_get_todo_dl($todos, $todo_index);
	list($todo_dl_y, $todo_dl_m, $todo_dl_d) = split('-' ,$todo_deadline, 3);
	$todo_title = todo_get_todo_title($todos, $todo_index);;
	$todo_desc = todo_get_todo_desc($todos, $todo_index);;
	$todo_priority = todo_get_todo_priority($todos, $todo_index);;
 case 'add':
	sqgetGlobalVar('todo_index',$todo_index);
	sqgetGlobalVar('todo_maxcount',$todo_maxcount);
	if (!isset($todo_dl_y)) {
		$todo_dl_y = date('Y');
		$todo_dl_m = date('m');
		$todo_dl_d = date('d');
	}
	displayPageHeader($color, "None");
	print_title("To Do: Edit");
//	print "<h3><center>Edit a reminder</center></h3>\n";
	if (!isset($todo_title))
	  $todo_title = '';
	if (!isset($todo_desc))
	  $todo_desc = '';
	$todo_desc = str_replace("<br>","\n",$todo_desc);
	if ( ($todo_use_todo_priority == 1 ) && (!isset($todo_priority)) )
	  $todo_priority = 0;
	if ($todo_error == 5)
	  print "<center>NOTE: Item exceeds the maximum overall size!!</center>\n";
	if ($todo_error == 6)
	  print "<center>NOTE: Item exceeds the maximum note size!!</center>\n";
	if ($todo_index >= $todo_maxcount) {
	  echo "<br><center>";
	  echo _("WARNING: Maximum number of reminders is reached. You can't add more reminders");
	  echo "</center>";
	  break;
	} 
	?>	
<form method='post' action='<?= $_SERVER["PHP_SELF"]?>'><center><table><tr><td align='center'><b>Title: </b><input type=text name=todo_title value='<?= $todo_title?>'></td></tr>
<tr><td><textarea name=todo_desc rows=8 cols=50><?=$todo_desc?></textarea></td></tr>
<tr><td>
	<select name='todo_dl_d'>
		<option value='01' <?php if($todo_dl_d == 1) echo 'selected'?>>1</option><option value='02' <?php if($todo_dl_d == 2) echo 'selected'?>>2</option><option value='03' <?php if($todo_dl_d == 1) echo 'selected'?>>3</option><option value='04' <?php if($todo_dl_d == 4) echo 'selected'?>>4</option>
		<option value='05' <?php if($todo_dl_d == 5) echo 'selected'?>>5</option><option value='06' <?php if($todo_dl_d == 6) echo 'selected'?>>6</option><option value='07' <?php if($todo_dl_d == 7) echo 'selected'?>>7</option><option value='08' <?php if($todo_dl_d == 8) echo 'selected'?>>8</option>
		<option value='09' <?php if($todo_dl_d == 9) echo 'selected'?>>9</option><option value='10' <?php if($todo_dl_d == 10) echo 'selected'?>>10</option><option value='11' <?php if($todo_dl_d == 11) echo 'selected'?>>11</option><option value='12' <?php if($todo_dl_d == 12) echo 'selected'?>>12</option>
		<option value='13' <?php if($todo_dl_d == 13) echo 'selected'?>>13</option><option value='14' <?php if($todo_dl_d == 14) echo 'selected'?>>14</option><option value='15' <?php if($todo_dl_d == 15) echo 'selected'?>>15</option><option value='16' <?php if($todo_dl_d == 16) echo 'selected'?>>16</option>
		<option value='17' <?php if($todo_dl_d == 17) echo 'selected'?>>17</option><option value='18' <?php if($todo_dl_d == 18) echo 'selected'?>>18</option><option value='19' <?php if($todo_dl_d == 19) echo 'selected'?>>19</option><option value='20' <?php if($todo_dl_d == 20) echo 'selected'?>>20</option>
		<option value='21' <?php if($todo_dl_d == 21) echo 'selected'?>>21</option><option value='22' <?php if($todo_dl_d == 22) echo 'selected'?>>22</option><option value='23' <?php if($todo_dl_d == 23) echo 'selected'?>>23</option><option value='24' <?php if($todo_dl_d == 24) echo 'selected'?>>24</option>
		<option value='25' <?php if($todo_dl_d == 25) echo 'selected'?>>25</option><option value='26' <?php if($todo_dl_d == 26) echo 'selected'?>>26</option><option value='27' <?php if($todo_dl_d == 27) echo 'selected'?>>27</option><option value='28' <?php if($todo_dl_d == 28) echo 'selected'?>>28</option>
		<option value='29' <?php if($todo_dl_d == 29) echo 'selected'?>>29</option><option value='30' <?php if($todo_dl_d == 30) echo 'selected'?>>30</option><option value='31' <?php if($todo_dl_d == 31) echo 'selected'?>>31</option>
	</select>
	<select name='todo_dl_m'>
		<option value='01' <?php if($todo_dl_m == 1) echo 'selected'?>>1</option>
		<option value='02' <?php if($todo_dl_m == 2) echo 'selected'?>>2</option>
		<option value='03' <?php if($todo_dl_m == 3) echo 'selected'?>>3</option>
		<option value='04' <?php if($todo_dl_m == 4) echo 'selected'?>>4</option>
		<option value='05' <?php if($todo_dl_m == 5) echo 'selected'?>>5</option>
		<option value='06' <?php if($todo_dl_m == 6) echo 'selected'?>>6</option>
		<option value='07' <?php if($todo_dl_m == 7) echo 'selected'?>>7</option>
		<option value='08' <?php if($todo_dl_m == 8) echo 'selected'?>>8</option>
		<option value='09' <?php if($todo_dl_m == 9) echo 'selected'?>>9</option>
		<option value='10' <?php if($todo_dl_m == 10) echo 'selected'?>>10</option>
		<option value='11' <?php if($todo_dl_m == 11) echo 'selected'?>>11</option>
		<option value='12' <?php if($todo_dl_m == 12) echo 'selected'?>>12</option>
	</select><input type=text name='todo_dl_y' value='<?=$todo_dl_y?>'>
	<?php  if ($todo_use_todo_priority == 1) { ?>
	<b>Priority: </b><select name='todo_priority'>
		<option value='1' <?php if($todo_priority == 1) echo 'selected'?>>1</option>
		<option value='2' <?php if($todo_priority == 2) echo 'selected'?>>2</option>
		<option value='3' <?php if($todo_priority == 3) echo 'selected'?>>3</option>
		<option value='4' <?php if($todo_priority == 4) echo 'selected'?>>4</option>
		<option value='5' <?php if($todo_priority == 5) echo 'selected'?>>5</option>
		<option value='6' <?php if($todo_priority == 6) echo 'selected'?>>6</option>
		<option value='7' <?php if($todo_priority == 7) echo 'selected'?>>7</option>
		<option value='8' <?php if($todo_priority == 8) echo 'selected'?>>8</option>
		<option value='9' <?php if($todo_priority == 9) echo 'selected'?>>9</option>
		<option value='10' <?php if($todo_priority == 10) echo 'selected'?>>10</option>
	</seleclt>
		<?php } ?>
	</td></tr>
<tr><td><input type=hidden name='todo_index' value='<?= $todo_index ?>'>
<input type=hidden name='todo_action' value='save'>
<input type=submit name='todo_submit' value='Save Reminder'>
</td></tr>
</table></center></form>
<?php
	break;
 case 'view' :
	displayPageHeader($color, "None");
	sqgetGlobalVar('todo_index',$todo_index);	
	$todo_deadline = todo_get_todo_dl($todos, $todo_index);
	$todo_title = todo_get_todo_title($todos, $todo_index);;
	$todo_desc = todo_get_todo_desc($todos, $todo_index);;
	print_title("To Do: View");
	print "<br><br><center>";	
	print "<table width='60%' border=1 bgcolor='$color[9]'><tr><td>\n";
	print "<table width='100%' bgcolor='$color[4]'><tr><td align=\"center\"><b>$todo_title</b></td></tr>\n";
	if ($todo_use_todo_priority == 1) {
		$todo_priority = todo_get_todo_priority($todos,$todo_index);
		print "<tr><td><b>Priority:</b> $todo_priority</td></tr>\n";
	}
	print "<tr><td><b>Due:</b> $todo_deadline</td></tr>\n";
	print "<tr><td><hr width='100%' color='$color[9]'></td></tr>\n";
	print "<tr><td><br>$todo_desc</td></tr></table>\n";
	print "</td></tr></table>";
	print "<br /><br /><br /><br /><a href=".$_SERVER['PHP_SELF']."?todo_action=edit&todo_index=$todo_index>Edit</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".
	  "<a href=".$_SERVER['PHP_SELF']."?todo_action=delete&todo_index=$todo_index>Delete</a>";
	print "</center>\n";
	break;
	
 case 'default' :
 default :
	displayPageHeader($color, "None");
	print_title("To Do: List");
	print "<br><br><center><table width='90%' border=1 bgcolor='$color[9]'><tr>";
	if ($todo_use_todo_priority == 1) {
		echo '<th width=90 align="center" style="{font-weight: bold;};">'.
		  '<a href="'.$_SERVER['PHP_SELF'].'?todo_action=sort&sort_type=prio&sort_dir=asc"><img src="../../images/down_pointer.png" border=0 /></a>'.
		  ' Priority '.
		  '<a href="'.$_SERVER['PHP_SELF'].'?todo_action=sort&sort_type=prio&sort_dir=desc"><img src="../../images/up_pointer.png" border=0 /></a></th>';
	}
	echo '<th width=120 align="center" style="{font-weight: bold;};">'.
	  '<a href="'.$_SERVER['PHP_SELF'].'?todo_action=sort&sort_type=date&sort_dir=asc"><img src="../../images/down_pointer.png" border=0 /></a>'.
	  ' Deadline '.
	  '<a href="'.$_SERVER['PHP_SELF'].'?todo_action=sort&sort_type=date&sort_dir=desc"><img src="../../images/up_pointer.png" border=0 /></a></th>'.
	  '<th width=300 align="center" style="{font-weight: bold;};" width=50>Title</th>'.
	  '<th align="center" style="{font-weight: bold;};" width=100>Size</th>'.
	  "<th width=90>&nbsp;</th></tr>\n";
	for ($i = 0;$i < $todo_count; $i++) {
		$todo_deadline = todo_get_todo_dl($todos, $i);
		$todo_title = todo_get_todo_title($todos, $i);;
		$todo_desc = todo_get_todo_desc($todos, $i);;
		$todo_size = todo_get_todo_size($i,$todos);
		if ($todo_use_todo_priority == 1) {
			$todo_priority = todo_get_todo_priority($todos,$i);
		}
		
		$overdue = todo_overdue_days($todos,$i);
		
		if ($overdue > 365 )
		  $od_hl = "background-color: $todo_od_gt_y_bg; color: $todo_od_gt_y_fg; font-weight: bold;";
		else if ($overdue > 30)
		  $od_hl = "background-color: $todo_od_lt_y_bg; color: $todo_od_lt_y_fg;";
		else if ($overdue > 0)
		  $od_hl = "background-color: $todo_od_lt_m_bg; color: $todo_od_lt_m_fg;";
		else {
			if ($todo_use_todo_priority == 1) {
			  $od_hl = 'color: '. getPref($data_dir, $username, 'todo_prio_'.$todo_priority.'_color') . ';';
			}
			else 
			  $od_hl = '';
		}

			
		echo "<tr bgcolor='$color[4]'>";
		if ($todo_use_todo_priority == 1)
		  echo "<td style=\"{ $od_hl; }\" align='center'>$todo_priority</td>";
		echo "<td style=\"{ $od_hl; }\" align=\"center\">$todo_deadline</td>" .
		  "<td style=\"{ $od_hl; }\">&nbsp;&nbsp;<a href='todo.php?todo_action=view&todo_index=$i' style=\"{ $od_hl; }\">$todo_title</a></td>".
		  "<td style=\"{ $od_hl; }\" align=\"center\">$todo_size bytes</td>" .
		  "<td align=\"center\"><a href='todo.php?todo_action=edit&todo_index=$i'>" .
		  "Edit</a> | <a href='todo.php?todo_action=delete&todo_index=$i'>Delete</a>" .
		  "</td></tr>\n";
		
	}
	if ($todo_count == 0)
	  print "<tr><td colspan=5 bgcolor='$color[4]'><center>You don't have anything to do!</center></td></tr>\n";
	echo '<tr>';
	if ($todo_use_todo_priority == 1) {
		echo "<td align='center'><a href='".$_SERVER['PHP_SELF']."?todo_action=sort&sort_type=priodate&sort_dir=asc'>Sort</a></td>";
	}
	if ($todo_maxtotsize > 0)
	  print "<td><center>$todo_count / $todo_maxcount todo items</center></td><td>&nbsp;</td><td align=\"center\" style=\"{font-weight: bold;};\" colspan=2>$todo_filesize / $todo_maxtotsize Bytes</center></td></tr>\n";
	else
	  print "<tr><td><center>$todo_count / $todo_maxcount todo items</center></td><td colspan=3>&nbsp;</td><td><center>$filesize Bytes</center></td></tr>\n";
	print "</table></center><br><br>\n";
	if ($error == 0) {
	  print "<center><form><input type=hidden name='todo_index' value='$todo_count'>".
	  "<input type=hidden name='todo_action' value='add'><input type=submit name=todo_submit value='Add Item'>".
	  "</form></center>\n";
	}
}

?>	
	
</body>
</html>
