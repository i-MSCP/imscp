<?php
/*******************************************************************************

    Author ......... Jimmy Conner
    Contact ........ jimmy@advcs.org
    Home Site ...... http://www.advcs.org/
    Program ........ Plugin Updates
    Version ........ 0.7
    Purpose ........ Check your currently installed plugins for updates

*******************************************************************************/

      if (@!file_exists(SM_PATH . 'plugins/pupdate/config.php'))
         return;
      include_once(SM_PATH . 'plugins/pupdate/config.php');
      global $username;
      $username = ( !isset($_SESSION['username']) ? '' : $_SESSION['username'] );
      if ($username == $update->adminusername) {
         global $optpage_blocks;
         $optpage_blocks[] = array (
                'name' => _("Plug-in Updates"),
                'url'  => SM_PATH . 'plugins/pupdate/update.php',
                'desc' => _("Checks for updates to your currently running plugins."),
                'js'   => false);
       return;
      }
?>