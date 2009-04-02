<?php
function todo_init(&$todos) {
	if (defined('SM_PATH'))
	  include_once(SM_PATH . 'functions/prefs.php');
	else
	  include_once('../functions/prefs.php');
	global $username, $hashed_dir, $data_dir, $todo_filesize;
	$hashed_dir = getHashedDir($username, $data_dir);

	clearstatcache();
	$todo_filesize = @filesize("$hashed_dir/$username.todo");
	$file = @fopen("$hashed_dir/$username.todo",'r');
	$todos = @fread($file ,$todo_filesize);
	@fclose($file);
}

function todo_count(&$todos)
{
	if(strlen($todos) > 3) {
		$todos = trim($todos,"\n");
		$todos = explode("\n", $todos);
		$todo_count = count($todos);
	}
	else {
		$todos[0] = '';
		$todo_count = 0;
	}
	return $todo_count;
}

function todo_within_limit()
{
	global $todo_count, $todo_filesize, $todo_error,$todo_maxtotsize,$todo_maxcount,$todo_maxsize,$todo_action;
	$todo_error = 0;
	if ($todo_count > $todo_maxcount - 1)
	  $todo_error=2;
	$todo_filesize -= ($todo_count * 1); //don't count the record separator
	if ($todo_maxtotsize > 0 && $todo_maxtotsize < $todo_filesize)
	  $todo_error = 4;
	if($todo_error != 0)
	  return false;
	else
	  return true;
}
	

function todo_get_todo($i, $todos)
{
	return $todos[$i];
}

function todo_get_todo_size($i, $todos)
{
	return strlen($todos[$i]);
}

function todo_save_todos(&$todos, $todo_count, $filename) 
{
	$file = @fopen($filename,'w');
//	echo "count = ($todo_count)";
	for ($i = 0;$i < $todo_count; $i++){
		if ($todos[$i] != '') {
			@fputs($file, $todos[$i] . "\n");
		}
	}
	@fclose($file);
}

function todo_get_todo_dl(&$todos, $i)
{
	$todo = explode("\t", $todos[$i]);
	return $todo[0];
}

function todo_get_todo_title($todos, $i)
{
	$todo = explode("\t", $todos[$i]);
	return $todo[1];
}

function todo_get_todo_desc($todos, $i)
{
	$todo = explode("\t", $todos[$i]);
	return $todo[2];
}

function todo_get_todo_priority(&$todos, $i)
{
	global $todo_use_todo_priority;
	$todo = explode("\t", $todos[$i]);
	if ( (array_key_exists(3,$todo)) && ($todo_use_todo_priority == 1) )
	  return $todo[3];
	else
	  return 0;
}

function todo_get_todo_dl_s(&$todo)
{
	$t = explode("\t", $todo);
	return $t[0];
}

function todo_get_todo_title_s($todo)
{
	$t = explode("\t", $todo);
	return $todo[1];
}

function todo_get_todo_desc_s($todo)
{
	$t = explode("\t", $todo);
	return $t[2];
}

function todo_get_todo_priority_s(&$todo)
{
	global $todo_use_todo_priority;
	$t = explode("\t", $todo);
	if ( (array_key_exists(3,$t)) && ($todo_use_todo_priority == 1) )
	  return $t[3];
	else
	  return 0;
}

global $todo_use_todo_priority;
if($todo_use_todo_priority == 1) {
	function todo_set_todo(&$todos, $i, $deadline, $title, $desc, $prio)
	{
		if ($title == '')
		  $title = '(No Title)';
		if ($desc == '')
		  $desc = '(No Description)';
		$title = str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',strip_tags($title));		
		$desc = str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',strip_tags($desc));
		$desc = str_replace("\n",'<br>',$desc);
		$desc = str_replace("\r",'',$desc);
		$todos[$i] = $deadline . "\t" . $title . "\t" . $desc . "\t" . $prio;
	}

	function todo_add_todo(&$todos, $deadline, $title, $desc, $prio) {
		if ($title == '')
		  $title = '(No Title)';
		if ($desc == '')
		  $desc = '(No Description)';
		$title = str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',strip_tags($title));		
		$desc = str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',strip_tags($desc));
		$desc = str_replace("\n",'<br>',$desc);
		$desc = str_replace("\r",'',$desc);
		
		$todo_count = count($todos);
		$todos[$todo_count] = $deadline . "\t" . $title . "\t" . $desc . "\t" . $prio;
	}

	function todo_cmp_prio_asc($todo1, $todo2)
	{
		$p1 = todo_get_todo_priority_s($todo1);
		$p2 = todo_get_todo_priority_s($todo2);
//smaller priority is more important!!		
		return ($p1 < $p2)?-1:1;
	}
	
	function todo_cmp_prio_desc($todo1, $todo2)
	{
		$p1 = todo_get_todo_priority_s($todo1);
		$p2 = todo_get_todo_priority_s($todo2);		
//smaller priority is more important!!		
		return ($p1 >= $p2)?-1:1;
	}

	function todo_cmp_priodate_asc($todo1, $todo2)
	{
		$p1 = todo_get_todo_priority_s($todo1);
		$p2 = todo_get_todo_priority_s($todo2);
		$d1 = todo_get_todo_dl_s($todo1);
		$d2 = todo_get_todo_dl_s($todo2);

		if(strcmp($d1,$d2) == 0)
		  return ($p1 < $p2)?-1:1;
		else
		  return strcmp($d1,$d2);
	}
	
	function todo_cmp_priodate_desc($todo1, $todo2)
	{
		$p1 = todo_get_todo_priority_s($todo1);
		$p2 = todo_get_todo_priority_s($todo2);
		$d1 = todo_get_todo_dl_s($todo1);
		$d2 = todo_get_todo_dl_s($todo2);
		
		if(strcmp($d1,$d2) == 0)
		  return ($p1 >= $p2)?-1:1;
		else
		  return strcmp($d1,$d2);

	}
}
else {
	function todo_set_todo(&$todos, $i, $deadline, $title, $desc)
	{
		if ($title == '')
		  $title = '(No Title)';
		if ($desc == '')
		  $desc = '(No Description)';
		$title = str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',strip_tags($title));		
		$desc = str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',strip_tags($desc));
		$desc = str_replace("\n",'<br>',$desc);
		$desc = str_replace("\r",'',$desc);
		$todos[$i] = $deadline . "\t" . $title . "\t" . $desc;
	}

	function todo_add_todo(&$todos, $deadline, $title, $desc) {
		if ($title == '')
		  $title = '(No Title)';
		if ($desc == '')
		  $desc = '(No Description)';
		$title = str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',strip_tags($title));		
		$desc = str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',strip_tags($desc));
		$desc = str_replace("\n",'<br>',$desc);
		$desc = str_replace("\r",'',$desc);
		
		$todo_count = count($todos);
		$todos[$todo_count] = $deadline . "\t" . $title . "\t" . $desc;
	}
}

function todo_overdue_days(&$todos, $i)
{
	$deadline = todo_get_todo_dl($todos, $i); 
	$today = date('Y-m-d');
	$td_a = explode('-', $today);
	$dl_a = explode('-', $deadline);
	$overdue = ($td_a[0] - 2000) * 365 + ($td_a[1] - 1) * 30 + ($td_a[2] - 1);
	$overdue -= ($dl_a[0] - 2000) * 365 + ($dl_a[1] - 1) * 30 + ($dl_a[2] - 1);
	return $overdue;
}

function todo_within_deadline(&$todos)
{
	$all_ok = true;
	$n = count($todos);
	for($i=0;$i<$n;$i++) {
		if(todo_overdue_days($todos, $i) > 0)
		  $all_ok = false;
	}
	return $all_ok;
}

function todo_cmp_date_asc($todo1, $todo2)
{
	$d1 = todo_get_todo_dl_s($todo1);
	$d2 = todo_get_todo_dl_s($todo2);
	
	return(strcmp($d1,$d2));
}

function todo_cmp_date_desc($todo1, $todo2)
{
	$d1 = todo_get_todo_dl_s($todo1);
	$d2 = todo_get_todo_dl_s($todo2);
	
	return(0-strcmp($d1,$d2));
}

function todo_sort(&$todos,$sort_dir, $sort_type)
{
	usort($todos,"todo_cmp_".$sort_type."_".$sort_dir);
}

function print_title($text)
{
	global $color;
	echo "<table bgcolor='$color[0]' border=0 cellpadding=1 width='100%' align='center'><tr><td align='center'><b>$text</b></td></tr></table>";
}

?>
