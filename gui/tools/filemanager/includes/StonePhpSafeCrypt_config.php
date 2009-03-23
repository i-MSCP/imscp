<?php

  /////////
  //
  //  Stone PHP SafeCrypt configuration file
  //  --------------------------------------
  //
  //  You must set the default MD5 salt.  Everything else is optional.



//////////////////////////////
////////// REQUIRED //////////
//////////////////////////////

  /////////
  //
  //  Default MD5 Salt
  //
  //  Fill this string with pretty much whatever.  A phrase, random letters,
  //  it really doesn't matter.  This is a 'salt,' which is a technique used
  //  to defeat dictionary attacks.  Changing the salt will break all of your
  //  previous keys until the salt is changed back, so if you're expecting to
  //  change this as a response to attacks, it's probably better to input this
  //  in the options from the containing application than to leave it in
  //  defaults.  It *must* be set in defaults, though, because a lack of a
  //  salt is an unacceptable security risk.
  //
  //  The actual contents of the salt don't matter, other than that they are
  //  a string.  You would do well to just slap your hands against the
  //  keyboard for a while.
  //
  //  For obvious reasons, be careful to escape backslashes and quote marks
  //  according to PHP rules.  Or, avoid them entirely.  Doesn't matter.

  define('DEFAULT_MD5_SALT', $net2ftp_settings["md5_salt"]); // empty string is illegal

  // examples:
  //
  //   define('DEFAULT_MD5_SALT', 'l^3-40#9a+40bn_qr:0b/8n<0b}qrq SPAM SPAM SPAM EGGS AND SPAM');
  //   define('DEFAULT_MD5_SALT', 'I am the very model of a modern Major General');
  //   define('DEFAULT_MD5_SALT', 'For a good time, call 867-5309');
  //   define('DEFAULT_MD5_SALT', '1,3,7-trimethyl-1H-purine-2,6(3H,7H)-dione');






//////////////////////////////
////////// OPTIONAL //////////
//////////////////////////////


  // TODO add DEFAULT_INCLUDE_DIRECTORY



  /////////
  //
  //  DEFAULT_ENCRYPTION_METHOD
  //  -------------------------
  //    default: ''  (auto-detect)
  //
  //  TripleDES is reasonable speed, reasonable security and available in
  //  most countries.  Set this to false if you want the library to try to
  //  autodetect the best available algorithm.
  //
  //  TODO actually make the autodetection, also get rid of tripledes

  define('DEFAULT_ENCRYPTION_METHOD', 'twofish');



  /////////
  //
  //  DEFAULT_ENCRYPTION_MODE
  //  -----------------------
  //    default: 'cbc'
  //
  //  TODO write description
  //  TODO ofb is badbear.  test with CBC soon.

  define('DEFAULT_ENCRYPTION_MODE', 'cbc');


  /////////
  //
  //  DEFAULT_ALGORITHM_DIRECTORY
  //  ---------------------------
  //    default: ''  (auto-detect)
  //
  //  On most machines, this should stay empty.  This allows you to override
  //  the directory in which the compressors and decompressors will be looked
  //  for.  PHP defaults for this are almost always correct, and should rarely
  //  be overridden.  Fill only if you have specific reason to do so.

  define('DEFAULT_ALGORITHM_DIRECTORY', '');

  /////////
  //
  //  DEFAULT_MODE_DIRECTORY
  //  ----------------------
  //    default: ''  (auto-detect)
  //
  //  As above, but for block modes instead of compressors.  Again, fill only
  //  if you have a specific reason to do so.

  define('DEFAULT_MODE_DIRECTORY', '');


  /////////
  //
  //  DEFAULT_COMPRESSION_METHOD
  //  --------------------------
  //    default: false
  //
  //  Here, you may set the library to compress by default when encrypting.
  //  Whether this is desirable has a lot to do with whether your server is
  //  already compressing somewhere else, whether you can afford the CPU time,
  //  whether the space is important, and so on.  I leave this off by default
  //  because you can turn it on during use, but if you always use it, hell,
  //  just set it here.
  //
  //  Standard values are [ 'gz' , 'gz_deflate' , 'bz' , false ].
  //
  //  User may add new values in StonePhpSafeCrypt_compressors.php .
  //
  //  A value of false skips default compression, which is probably best.

  define('DEFAULT_COMPRESSION_METHOD', false);

?>