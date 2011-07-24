<?php
/*******************************************************************************

    Author ......... Jimmy Conner
    Contact ........ jimmy@advcs.org
    Home Site ...... http://www.advcs.org/
    Program ........ Plugin Updates
    Version ........ 0.7
    Purpose ........ Check your currently installed plugins for updates

*******************************************************************************/

   @error_reporting (E_ALL);

   if (!defined('SM_PATH'))
      define('SM_PATH','../../');
   if (file_exists(SM_PATH . 'include/validate.php')) {
      include_once(SM_PATH . 'include/validate.php');
   } else {
      if (file_exists(SM_PATH . 'src/validate.php'))
         include_once(SM_PATH . 'src/validate.php');
   }

   $update = new PUpdate;

   if (@file_exists(SM_PATH . 'plugins/pupdate/config.php'))
      require_once (SM_PATH . 'plugins/pupdate/config.php');
   else {
      print "<center>You must configure this plugin before you are able to use it!<br>Just copy config.php.sample to config.php and edit it to your liking.</center>";
      exit();
   }

   if ($username != $update->adminusername)
      header("Location:../../src/right_main.php?startMessage=1&mailbox=INBOX");

   displayPageHeader($color, "None");
   $do = '';
   $vvv = '';
   global $_GET, $_POST;
   if (isset($_GET['do']))
      $do = $_GET['do'];
   if (isset($_GET['vvv']))
      $vvv = $_GET['vvv'];
   if (isset($_POST['vvv']))
      $vvv = $_POST['vvv'];
   if (isset($_GET['dname']))
      $dname = $_GET['dname'];
   if (isset($_GET['pversion']))
      $pversion = $_GET['pversion'];
   if (isset($_GET['name2']))
      $name2 = $_GET['name2'];
   if (isset($_POST['dname']))
      $dname = $_POST['dname'];
   if (isset($_POST['name2']))
      $name2 = $_POST['name2'];

   if ($do  == 'edit')
      $update->ChangeVersion($name2, $pversion, $dname);

   if ($vvv != '')
      $update->WriteVersion($dname, $vvv, $name2);

   if ($do == 'pluginlist' && !$update->dontupdate)
      $update->UpdateFile('pluginlist');

   if ($do =='defaultdirs' && !$update->dontupdate)
      $update->UpdateFile('defaultdirs');

   if ($do == 'core' && !$update->dontupdate) 
      $update->UpdateFile('core');

   $update->ShowOptions($color);

   $update->ShowVersion($version);

   if (!$update->stablehide)
      $update->ShowStable();

   if (!$update->dontupdate)
      $update->CheckUpdates();

   sort($plugins);
   global $display;
   foreach ($plugins as $key => $value){
      $extra = '';
      $update->plink = "";
      $update->pluginversion = "";
      $dname = trim($plugins[$key]);
      $update->GetPluginInfo($dname);
      $display = false;
      if ($update->pluginversion) {
         $pluginfile = @fopen(SM_PATH ."plugins/pupdate/udata/pluginlist2.dat", 'r');
         $update->pluginname = trim($update->pluginname);
         while (!feof($pluginfile)) {
            $plugin2 = explode('|',@fgets($pluginfile, 150));
            $plugin = trim($plugin2[1]);
            if ($plugin == $update->pluginname){
               // Checks for core programs here
               $cinfo = $update->isitcore($plugin2[0], $version);
               if ($cinfo == ''){
                  if ($update->pluginversion != "Unknown")
                     $update->DisplayPluginInfo($dname, $plugin, $plugin2[0], $update->pluginversion);   
               }else{
                  $update->DisplayCoreInfo($dname, $plugin, $cinfo, $update->pluginversion);
               }
               break;
            }
         }
         fclose($pluginfile);

         if (($update->pluginversion == "" || $update->pluginversion == "Unknown")&& !$update->unofficialhide) {
            # Plugins that either aren't set up correctly or aren't supported
            $display = true;
            $extra .= "<dd><font size=-1>Not an official plugin or on the Plugin Page?</font></dd></dl>\n";
         }

      } else {
         $display = true;
         if ($update->pluginname != '') {
            $extra = "<dd><font size=-1>No Version in version file (refresh)</font></dd></dl>\n";
            $update->pluginversion = 'Unknown';
            $update->WriteVersion($dname, $update->pluginversion, $update->pluginname);
         } else {
            $extra = "<dd><font size=-1>Unsupported, may not be the default install directory?</font></dd></dl>\n";
            $update->pluginversion = 'Unknown';
            $update->WriteVersion($dname, $update->pluginversion, $update->pluginname);
         }
      }
      if ($display) {
         if (!$update->plink)
            $update->plink = $update->pluginversion;
         print "<dl><dt><b>$update->pluginname</b> $update->plink</dt>\n";
         if (!$update->locationhide)
            print "<dd><font size=-1><b>Location</b>: /plugins/$dname</font></dd>";
         print "$extra</dl>";
      }
   }

   class PUpdate {

      var $adminusername = 'jimmy@advcs.org';
      var $dontupdate = 0;
      var $cvshide = 1;
      var $stablehide = 0;
      var $uptodatehide = 0;
      var $unofficialhide = 0;
      var $corehide = 0;
      var $locationhide = 1;
      var $autoupdate = 1;
      var $pulp = '';
      var $pulp2 = '';
      var $pluginname = '';
      var $pluginversion = '';
      var $plink = '';

      function GetPluginInfo($dname) {
         $this->pluginname = '';
         $this->pluginversion = 0;
         $func = $dname . '_version';
         if (@function_exists($func)) {
            $this->pluginversion = $func();
            $this->GetPluginName($dname);
         } else {
            @chmod (SM_PATH . "plugins/$dname/version", 0777);
            if ($vfile = @fopen(SM_PATH . "plugins/$dname/version", 'r')) {
               $this->pluginname = strip_tags(trim(@fgets($vfile, 150)));
               $this->pluginversion = strip_tags(trim(@fgets($vfile, 150)));
               fclose($vfile);
            } else {
               $this->GetPluginName($dname);
            }
         }
      }

      function GetPluginName($dname) {
         $this->pluginname = '';
         $dirfile = @fopen(SM_PATH . "plugins/pupdate/udata/defaultdirs2.dat",r);
         $l = strlen($dname);
         while (!feof($dirfile) && $this->pluginname == '') {
            $plugin = trim(@fgets($dirfile, 150));
            $plugin = explode('|',$plugin);
            if (substr($plugin[1],0,$l) == $dname) {
               $founddefault = 1;
               $pluginfile = @fopen(SM_PATH ."plugins/pupdate/udata/pluginlist2.dat",r);
               while (!feof($pluginfile) && $this->pluginname == '') {
                  $temp = explode('|',@fgets($pluginfile, 150));
                  if ($temp[0] == $plugin[0]) {
                     $this->pluginname = $temp[1];
                     break;
                  }
               }
               break;
               fclose($pluginfile); 
            }
         }
         fclose($dirfile);
         if ($this->pluginname == '')
               $this->pluginname = strip_tags(ucwords(str_replace("_", " ", $dname)));
      }

      function ShowStable() {
         $file = @fopen("http://www.squirrelmail.org/plugin_query.php?type=stable", 'r');
         $string = @fread($file, 50);
         @fclose($file);
         print "Current Stable Version is <a href='http://www.squirrelmail.org/download.php'><b>Squirrelmail $string</b></a><br>\n";
      }

      function ShowVersion($version) {
         print "\n<br>You are currently running <b>Squirrelmail $version</b><br>\n";
      }

      function ShowOptions($color) {
        $showarray = array(0 => "Show",1 => "Hide");
        $a = $this->stablehide;
        $b = $this->cvshide;
        $c = $this->uptodatehide;
        $d = $this->unofficialhide;
        $e = $this->corehide;
        $f = $this->dontupdate;
        $g = $this->locationhide;

         print "<br><center><table border=1 width='70%' bgcolor='$color[9]'><tr><td><center>Stable</center></td><td><center>CVS</center></td><td><center>Updated</center></td><td><center>Unofficial</center></td><td><center>Core</center></td><td><center>Data Files</center></td><td><center>Location</center></td></tr>";
         print "<tr bgcolor='$color[4]'><td><center>$showarray[$a]</center></td><td><center>$showarray[$b]</center></td><td><center>$showarray[$c]</center></td><td><center>$showarray[$d]</center></td><td><center>$showarray[$e]</center></td><td><center>$showarray[$f]</center></td><td><center>$showarray[$g]</center></td></tr>";
         print "</table></center>";
      }

      function UpdateFile($dfile) {
         @chmod (SM_PATH . "plugins/pupdate/udata/$dfile.dat", 0777);
         $file = @fopen("http://advcs.org/sm/$dfile.dat", 'r');
         $pulp = @strip_tags(fread($file, 5000));
         $file2 =  @fopen(SM_PATH . "plugins/pupdate/udata/$dfile.dat", 'w');
         @fwrite ($file2, $pulp);
         @fclose($file);
         @fclose($file2);
      }

      function CheckUpdate($dfile) {
         $this->pulp = 0;
         $this->pulp2 = 2;
         $type = '';
         $name = 'pluginlist';
         if ($dfile == 'filenames') {
            $type = '?type=filenames';
            $name = 'defaultdirs';
         }
         $file = @fopen("http://www.squirrelmail.org/plugin_query.php$type", 'r');
         $file2 = @fopen(SM_PATH . "plugins/pupdate/udata/$name"."2.dat", 'w');
         if ($file2) {
            while (!@feof($file)) {
               @fwrite ($file2, trim(fread($file, 200)));
            }
            @fclose($file2);
         }
         @fclose($file);
      }

      function CheckUpdates() {
            $this->CheckUpdate('pluginlist');
            $this->CheckUpdate('filenames');
      }

      function ChangeVersion($name2,$pversion,$dname) {
         print "<dl><dt><b>$name2</b></dt>";
         print "<dd><font size=-1><b>Version</b>: $pversion</font></dd>";
         print "<dd><font size=-1><b>Location</b>: /plugins/$dname</font></dd></dl>";
         print "<form method=post action='update.php'>Change version to:<br><input type=text name=vvv value='$pversion'><input type=hidden name=dname value='$dname'><input type=hidden name=name2 value='$name2'><input type=submit value='Submit'></form>";
         exit;
      }

      function WriteVersion($dname, $vvv, $name2) {
         @chmod (SM_PATH . "plugins/$dname/version", 0777);
         $file =  @fopen(SM_PATH . "plugins/$dname/version", 'w');
         @fwrite ($file, strip_tags("$name2\n"));
         @fwrite ($file, strip_tags($vvv));
         @fclose($file);
      }

      function GetNewestVersion ($id) {
         $file = @fopen("http://www.squirrelmail.org/plugin_query.php?id=$id&type=newest", 'r');
         $string = fread($file, 20);
         return $string;
      }

      function getnew($id) {
         # This function just grabs the newest version off the website, its slow and bloated, need to be fixed!!!!
         $file = @fopen("http://www.squirrelmail.org/plugin_query.php?id=$id&type=changes", 'r');
         $string = fread($file, 9000);
         fclose($file);
         $x = str_replace("plugin_download.php", "http://www.squirrelmail.org/plugin_download.php",$string);
         return $x;
      }

      function parsecore ($cinfo, $sm) {
         if (trim($cinfo) == '')
            return "";
         $c = substr($cinfo, 0, 1);
         $cv = substr($cinfo, 2);
         if ($cv > $sm)
            return "";
         if ($c == 'V')
            return "Added to the CVS in SM $cv, please check for updates <a target='_new' href='http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/squirrelmail/squirrelmail/plugins/'>there</a>.";
         return "Added to the SM Core in SM $cv,<br> you may need to uninstall this plugin,<br> inorder for your SM to function properly.";
      }

      function isitcore($id, $sm) {
         $corefile = @fopen(SM_PATH . "plugins/pupdate/udata/core.dat", 'r');
         $b = -1;
         while ($b < $id + 1) {
            $b++;
            $coreinfo = @trim(strip_tags(fgets($corefile, 15)));
         }
         @fclose($corefile);
         return $this->parsecore($coreinfo, $sm);
      }

      function DisplayCoreInfo($dname, $plugin, $cinfo, $pversion) {
         global $display, $extra;
         if (substr($cinfo,13,1) == 'S'){
            if (!$this->corehide) {
               $display = true;
               $this->plink = "(Core)";
               $this->pluginversion = "Core";
            }
         } else {
            if (!$this->cvshide) {
               $display = true;
               $this->plink = "(CVS)";
               $this->pluginversion = "CVS";
            }
         }
         $extra .= "<dd><font size=-1>$cinfo</font></dd></dl>\n";
      }

      function DisplayPluginInfo($dname, $plugin, $a, $pversion) {
         global $display, $extra;
         $pnewversion = $this->GetNewestVersion ($a);
         $this->plink = "(<a href='update.php?do=edit&dname=$dname&name2=$plugin&pversion=$pversion'><font size=-1>v$pversion</font></a>)";
         if (!$pversion)
            $this->plink = "(<a href='update.php?do=edit&dname=$dname&name2=$plugin&pversion=$pversion'><font size=-1><i><u>Unknown Version</u></i></font></a>)";
         if ($pnewversion > $pversion) {
            $display = true;
            $info = $this->getnew($a);
            $extra = $extra . "<dd><font size=-1>$info</font></dd></dl>\n";
         } else {
            if (!$this->uptodatehide) {
               $display = true;
               $extra = $extra . "<dd><font size=-1>Is up to date.</font></dd></dl>\n";
            }
         }
      }
   }

?>