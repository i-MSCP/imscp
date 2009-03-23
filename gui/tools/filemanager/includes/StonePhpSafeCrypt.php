<?php



  /////////
  //
  //  Stone PHP SafeCrypt
  //  -------------------
  //
  //  This library is intended to wrap and distance a programmer from encryption, which is
  //  not only difficult to use safely, but also difficult to detect flaws in.  The purpose of
  //  SafeCrypt is to provide a convenient, correct, secure and flexible wrapper for the mcrypt
  //  library, to make encryption available at greater ease and lower risk.
  //
  //   * Stone PHP SafeCrypt is genuinely free software, distributed under the Modified 3-Clause
  //     BSD License.  This library is GPL compatible.  Please resist the GPL menace.  Free isn't
  //     truly free if it's only available to some.  See LICENSE.TXT for details.
  //
  //   * Stone PHP SafeCrypt may be used in commercial projects at no cost, without permission.
  //     See LICENSE.TXT for details.  Copyright notice must be maintained.
  //
  //   * No library can substitute for a working knowledge of cryptography.  See LEARNING.TXT
  //     for resources on getting up to speed in crypto with relatively little pain.



  if (defined('STONE_PHP_SAFE_CRYPT_HOST_DIRECTORY')) {
    $basedir = STONE_PHP_SAFE_CRYPT_HOST_DIRECTORY;
  } else {
// net2ftp
    $basedir = $net2ftp_globals["application_includesdir"] . "/";
  }

  /////////
  //
  //  Please see USAGE.TXT for general usage instructions, or



  /////////
  //
  //  You must set the MD5 salt in the config file before this library will function.  All other
  //  configuration is optional.  testconfig checks to make sure the config is valid.

  require_once($basedir . 'StonePhpSafeCrypt_config.php');
  require_once($basedir . 'StonePhpSafeCrypt_testconfig.php');



  /////////
  //
  //  This file sets up the compressor behavior.  If you want to add a new compression method,
  //  put the data in this file.

  require_once($basedir . 'StonePhpSafeCrypt_compressors.php');



  /////////
  //
  //  Various components added here.  You shouldn't need to edit these.

  require_once($basedir . 'StonePhpSafeCrypt_blockscramble.php');
  require_once($basedir . 'StonePhpSafeCrypt_packcrypt.php');

  //  TODO add best-algorithm search using mcrypt_list_algorithms()





?>