<?php

  $forcefail = array('Apply' => false, 'Reason' => '');
  if (DEFAULT_MD5_SALT === '') {
      $forcefail['Apply'] = true;
      $forcefail['Reason'] = 'You must set the default MD5 salt on line 83 before this library will function.';
  }

?>