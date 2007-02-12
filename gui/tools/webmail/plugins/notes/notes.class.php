<?php
/**
 * notes.class.php
 *
 * @author Jimmy Conner <jimmy@sqmail.org>
 * @copyright Copyright &copy; 2005, Jimmy Conner (Licensed under the GNU GPL see "LICENSE")
 *
 * @package plugins
 * @subpackage notes
 * @version $Id: notes.class.php,v 1.1 2005/01/05 15:02:26 cigamit Exp $
 */

class Notes {

   var $totalnotes = 0;
   var $maxnotescount = 0;
   var $maxnotesize = 0;
   var $error = 0;
   var $hashed_dir = '';

   function Init() {
      global $data_dir, $username;
      $this->hashed_dir = getHashedDir($username, $data_dir);
      $this->File();
      $this->TotalNotes();
      $this->CheckforErrors();
   }

   function Add($title = "", $note = "") {
      global $username;
      $note = str_replace("\n",'<br>', $note);
      $this->notescount = $this->notescount + 1;
      $this->Save($this->notescount, $title, $note, 1);
      setPref($this->hashed_dir, $username, 'notes_count', $this->notescount);
   }

   function Delete($id) {
      global $username;
      while ($nextNote = getPref($this->hashed_dir, $username, 'notes_' .($id + 1))) {
         setPref($this->hashed_dir, $username, 'notes_' . $id, $nextNote);
         $id ++;
      }
      removePref($this->hashed_dir, $username, 'notes_' . $id);
      $this->notescount = $this->notescount - 1;
      setPref($this->hashed_dir, $username, 'notes_count', $this->notescount);
   }

   function Save($editnote, $title, $note = '', $status = 0) {
      global $username;
      $date = date("F j, Y");
      $note = trim($note);
      $note = str_replace("\n",'<br>', $note);
      if (strlen($title) > 250) $title = substr($title, 0, 250);
      $n = array('title' => trim($title), 'status' => $status, 'note' => trim($note), 'date' => $date);
      $n = strtolower(bin2hex(serialize($n)));
      setPref($this->hashed_dir, $username, 'notes_' . $editnote, $n);
   }

   function File() {
      global $username;
      $this->file = "$this->hashed_dir/$username.notes";
   }

   function GetNote($id) {
      global $username;
      if ($id > $this->notescount) return false;
      $x = getPref($this->hashed_dir, $username, 'notes_' . $id);
      $note = unserialize($this->hex2bin($x));
      return $note;
   }

   function TotalNotes() {
      global $username;
      $this->notescount = getPref($this->hashed_dir, $username, 'notes_count');
      if ($this->notescount == '') {
         $this->notescount = 0;
         setPref($this->hashed_dir, $username, 'notes_count', 0);
      }
   }

   function ShowNote ($view, $edit = 0) {
      print "<center><a href='" . SM_PATH . "plugins/notes/notes.php'>" . _("Return") . "</a><br><br>";
      $this->DisplayNote($view, $edit, true);
      print "</center>";
   }

   function DisplayError () {
      global $color;
      bindtextdomain('notes', SM_PATH . 'plugins/notes/locale');
      textdomain('notes');

      if ($this->error == 6)
         print "<center><font color='" . $color[2] . "'><b>" . _("Note exceeds the maximum note size!") . "</b></font></center>\n";

      bindtextdomain('squirrelmail', SM_PATH . 'locale');
      textdomain('squirrelmail');
   }

   function CheckforErrors() {
      global $submit, $title, $note, $edit, $editnote, $username, $date;
      bindtextdomain('notes', SM_PATH . 'plugins/notes/locale');
      textdomain('notes');
      if ($this->maxnotescount > 0 && $this->notescount > $this->maxnotescount-1)
         $this->error=2;
      if ($this->maxnotesize > 0 && $this->maxnotesize < strlen($title . $date . $note))
         $this->error = 6;
      if ($submit == _("Add a Note") && $this->error == 2)
         $submit = '';
      if ($editnote != '' && $this->error == 6){
         $edit = $editnote;
         $submit = '';
      }
      bindtextdomain('squirrelmail', SM_PATH . 'locale');
      textdomain('squirrelmail');
   }

   function DisplayList($edit = 0) {
      global $color, $username;
      $this->CheckforErrors();
      $this->DisplayError();
      print "\n<br><center><table width=\"810\" border=0>\n";
      $count = 1;
      $r = 1;
      while ($count < $this->notescount+1){
         if ($r == 1)
             print "\n<tr valign=top>";
         $editme = 0;
         if ($count == $edit)
             $editme = 1;
         print "\n<td width=30>&nbsp;</td><td width='240'>";
         $this->DisplayNote($count, $editme);
         print "</td>\n";
         $count++;
         if ($r == 3) {
             print "</tr><tr height=30><td colspan=6></td></tr>\n";
             $r = 0;
         }
         $r++;
      }
      if ($r != 1) {
         for ($a = 0; $a < (4 - $r); $a++) {
            print "<td width=30></td><td width='240' valign=top></td>";
         }
         print "</tr>\n";
      }

      print "</table></center><br><br>\n";

      bindtextdomain('notes', SM_PATH . 'plugins/notes/locale');
      textdomain('notes');
      $c = $this->notescount;
      if ($this->error != 2)
         print "<center><form method=post action='" . SM_PATH . "plugins/notes/notes.php?#$c'><input type=submit name=submit value='" . _("Add a Note") . "'></form></center>\n";

      bindtextdomain('squirrelmail', SM_PATH . 'locale');
      textdomain('squirrelmail');
   }

   function DisplayNote($id, $edit = 0, $big = false) {
      global $note, $title, $date, $color;
      if ($this->error != 6 || $edit == 0) {
         $n = $this->GetNote($id);
      } else {
         $n['note'] = $note;
         $n['status'] = 1;
         $n['title'] = $title;
         $n['date'] = $date;
      }

      if (!$big) {
         $tw = 240;
         $th = 160;
         $size = '&amp;lob=l';
         $tt = 26;
         $tac = '26';
         $tar = '8';
      } else { 
         $tw = 720;
         $th = 415;
         $size = '';
         $tt = 105;
         $tac = '85';
         $tar = '24';
      }
      if ($this->error != 6)
         print "<a name='$id'></a>";
      else if ($big) {
         $this->DisplayError();
         print '<br>';
      }
      print "<table width=$tw cellpadding=1 cellspacing=0 bgcolor='" . $color[8] . "'><tr><td>";
      print "<table width='100%' bgcolor='" . $color[4] . "' cellspacing=0 cellpadding=0>";
      print "<tr><td bgcolor='" . $color[0]  . "'><center><table width='99%'><tr><td>";
      if (!$edit) {
         $link = "<a href='" . SM_PATH . "plugins/notes/notes.php?view=$id'>";
         if (strlen($n['title']) > 28)
             print $link . substr($n['title'], 0, 25) . '...</a>';
         else {
             if ($n['title'] == '')
                $n['title'] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
             print $link . $n['title'] . '</a>';
         }
      } else {
         print "<form method=POST name=Form1 action='" . SM_PATH . "plugins/notes/notes.php?#$id'><input type=hidden name=editnote value='$id'><input type=hidden name=status value='" . $n['status'] . "'>";
         if (!$big)
            print "<input type=hidden name=lob value='l'>";
         print "<input type=text name=title size='$tt' maxlength=250 value='" . $n['title'] . "'>";
      }
      print "</td><td width=16 align=right><center><a href='" . SM_PATH . "plugins/notes/notes.php?toggle=$id$lob'><img src='" . SM_PATH . "plugins/notes/images/";
      if ($n['status'] == 1)
          print '_.gif';
      else
          print 'max.gif';
      print "' border=0></a></center></td><td width=16 align=right><center><a href='" . SM_PATH . "plugins/notes/notes.php?delete=$id'><img src='" . SM_PATH . "plugins/notes/images/X.gif' border=0></a></center></td></tr></table></center>";
      print '</td></tr>';

      if ($n['status'] == 1 || $big) {
         print "<tr><td height=1 bgcolor='" . $color[8] . "'></td></tr>";
         print "<tr height=$th><td valign=top>";
         print "<center><table width='99%'><tr><td>";
         if ($edit)
            print "<textarea cols='$tac' rows='$tar' name=note>";
            $n['note'] = str_replace('<br>',"\n", $n['note']);
         if ($edit) {
            print trim($n['note']);
            print '</textarea>';
         } else {
             $n['note'] = str_replace("\n", '<br>', $n['note']);
             print trim($this->BreakNote($n['note'], $big));
         }
         print '</td></tr></table></center>';
         print '</td></tr>';
         print "<tr><td height=1 bgcolor='" . $color[8] . "'>";
         print '<tr><td>';
         print "<center><table width='100%'><tr><td>";
         print $n['date'];
         if ($edit) {
            if (soupNazi()) {
                print "</td><td align=right><input type=submit value='" . _("Save") . "'></form></td></tr></table></center>";
            } else
                print "</td><td align=right><a href='javascript:__doPostBack()'><b>" . _("Save") . "</b></a></form></td></tr></table></center>";
         } else
            print "</td><td align=right><a href='" . SM_PATH . "plugins/notes/notes.php?edit=$id$size#$id'>" . _("Edit") . "</a></td></tr></table></center>";

         print '</td></tr>';
      }
      print '</table></td></tr></table>';
   }

   function Toggle($id) {
       global $username;
       $note = $this->GetNote($id);
       if ($note['status'] == 0)
          $note['status'] = 1;
       else
          $note['status'] = 0;
       $this->Save($id, $note['title'], $note['note'], $note['status']);
   }

   function BreakNote($note, $big) {
      $note = str_replace("\t", ' ', $note);
      $n = explode('<br>',$note);
      $count = 0;
      $x = 0;
      if ($big) {
         $c = 45;
         $t = strlen($note)+10000;
      } else {
         $c = 25;
         $t = $c * 7;
      }
      while ($count < $t && $x < count($n)) {
          $l = strlen($n[$x]);
          if ($l < $c)
             $l = $c;
          if ($l > $c) {
             $m = explode(' ',$n[$x]);
             for ($b = 0; $b < count($b); $b++) {
                $l2 = strlen($m[$b]);
                $y = 0;
                for($a = $c; $a < $l2; $a = $a + $c) {
                   $m[$b] = substr($m[$b], 0, ($a) + $y) . " " . substr($m[$b],$a + $y);
                   $y++;
                }
             }
             $n[$x] = implode(' ', $m);
             $l = $l - $y;
          }
          $x++;
          $count += $l;
          if ($count > $t) $count = $t;
      }
      if (strlen($note) > $t) 
         $note = substr(implode("<br>", $n), 0, $count) . '...';
      return $note;
   }

   function hex2bin($data) {
      /* Original code by josh@superfork.com */
      $len = strlen($data);
      $newdata = '';
      for( $i=0; $i < $len; $i += 2 ) {
         $newdata .= pack( "C", hexdec( substr( $data, $i, 2) ) );
      }
      return $newdata;
   }
}

?>