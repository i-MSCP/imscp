<?php
/**
 * notes.php
 *
 * @author Jimmy Conner <jimmy@sqmail.org>
 * @copyright Copyright &copy; 2005, Jimmy Conner (Licensed under the GNU GPL see "LICENSE")
 *
 * @package plugins
 * @subpackage notes
 * @version $Id$
 */


/* Path for SquirrelMail required files. */

//if (!defined('SM_PATH'))
   define('SM_PATH','../../');

if (file_exists(SM_PATH . 'include/validate.php')) {
   include_once(SM_PATH . 'include/validate.php');
} else {
   if (file_exists(SM_PATH . 'src/validate.php'))
      include_once(SM_PATH . 'src/validate.php');
}

include_once(SM_PATH . 'plugins/notes/notes.class.php');

$n = new Notes;
if (file_exists(SM_PATH . 'plugins/notes/config.php')) {
   include_once(SM_PATH . 'plugins/notes/config.php');
} else {
   displayPageHeader($color, "None");
   print "<center><font color='" . $color[2] . "'><b>config.php " . _("does not exist!") . "</b></font></center>\n";
   exit;
}


if (function_exists('sqgetGlobalVar')) {
   sqgetGlobalVar('submit', $submit);
   sqgetGlobalVar('title', $title);
   sqgetGlobalVar('note', $note);
   sqgetGlobalVar('view', $view);
   sqgetGlobalVar('edit', $edit);
   sqgetGlobalVar('lob', $lob);
   sqgetGlobalVar('delete', $delete);
   sqgetGlobalVar('editnote', $editnote);
   sqgetGlobalVar('toggle', $toggle);
   sqgetGlobalVar('status', $status);
   sqgetGlobalVar('confirm', $confirm);
} else if (function_exists('sqextractGlobalVar')) {
   sqextractGlobalVar('submit');
   sqextractGlobalVar('title');
   sqextractGlobalVar('note');
   sqextractGlobalVar('view');
   sqextractGlobalVar('edit');
   sqextractGlobalVar('lob');
   sqextractGlobalVar('delete');
   sqextractGlobalVar('editnote');
   sqextractGlobalVar('toggle');
   sqextractGlobalVar('status');
   sqextractGlobalVar('confirm');
}

if ($submit != '')
   $submit = strip_tags($submit);
if ($title != '')
   $title = strip_tags($title);
if ($note != '')
   $note = strip_tags($note);
if ($view != '')
   $view = strip_tags($view);
if ($edit != '')
   $edit = strip_tags($edit);
if ($delete != '')
   $delete = strip_tags($delete);
if ($editnote != '')
   $editnote = strip_tags($editnote);
if ($toggle != '')
   $toggle = strip_tags($toggle);

$n->Init();

if ($convert) {
   $oldfile = $n->hashed_dir . "/$username.notes";
   if (@file_exists($oldfile)) {
      $file = @fopen($oldfile,r);
      $notes = @fread($file ,filesize($oldfile));
      @fclose($file);
      $notes = explode("\n", $notes);
      if (count($notes)) {
         foreach ($notes as $note) {
            $note = explode("|NOTES|", $note);
            if ($note[1] != '' && $note[2] != '')
               $n->add($note[1], $note[2]);
         }
      }
      @unlink($oldfile);
   }
}

if ($toggle != ""){
   $n->Toggle($toggle);
   header("Location: notes.php#$toggle");
}

if ($delete != ""){
   if ($confirm == _("Yes")) {
      $n->Delete($delete);
      header("Location: notes.php");
      exit;
   } else if ($confirm == _("No")) {

   } else {
      $note = $n->GetNote($delete);
      displayPageHeader($color, "None");

      bindtextdomain('notes', SM_PATH . 'plugins/notes/locale');
      textdomain('notes');
      print "<center><br>" . _("Are you sure you want to delete the note entitled") . '<br> "<b>' . $note['title'] . '</b>"?';
      bindtextdomain('squirrelmail', SM_PATH . 'locale');
      textdomain('squirrelmail');

      print "<br><br><form method=post><input type=hidden name=delete value='$delete'>";
      print "<input type=submit name=confirm value='" . _("Yes") . "'>&nbsp;&nbsp;&nbsp;<input type=submit name=confirm value='" . _("No") . "'></form></center>";
      echo '</body></html>';
      exit;
   }
}

if (($submit == _("Save") || $editnote != '') && $n->error < 3){
   $n->Save($editnote, $title, $note, $status);
   header("Location: notes.php");
}

bindtextdomain('notes', SM_PATH . 'plugins/notes/locale');
textdomain('notes');

if ($submit == _("Add a Note")){
   if (($n->notescount < $n->maxnotescount) || $n->maxnotescount == 0) {
      $n->Add();
      header("Location: notes.php?edit=" . $n->notescount . '&lob=l');
      exit;
   } else {
      $submit = '';
   }
}

bindtextdomain('squirrelmail', SM_PATH . 'locale');
textdomain('squirrelmail');

displayPageHeader($color, "None");

print '<script language=javascript type="text/javascript">
<!--
function __doPostBack(){
   var theform;
   if(window.navigator.appName.toLowerCase().indexOf("microsoft")>-1){
      theform=document.Form1;
   } else{
      theform=document.forms["Form1"];
   }
   theform.submit();
}
//-->
</script>';


if ($edit != '') {
   if ($lob != 'l') {
      $n->ShowNote($edit, 1);
      echo '</body></html>';
      exit;
   }
}

if ($submit == _("Save"))
   $submit = '';

if ($view != '')
   $n->ShowNote($view);
else if ($submit == '') {
   if ($lob == 'l')
      $n->DisplayList($edit);
   else
      $n->DisplayList();
}
echo '</body></html>';

?>