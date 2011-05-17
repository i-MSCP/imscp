<?php



  /////////
  //
  //  Stone PHP SafeCrypt Compressors
  //  -------------------------------
  //
  //  To add a new compressor, create a new member in the $compressors array.  Its key should be
  //  the string by which the user calls the compressor.  Its value should be an array with two
  //  members, 'encode' and 'decode', each themselves arrays.  Each of the compressor entries
  //  should also have a 'desc' member, to give a descriptive name to each compression method,
  //  in case someone wants to build something larger on this library and needs tooltip or
  //  statusbar descriptive text.
  //
  //  Each encode and decode must have the 'fname' member, which contains the name of the encoding
  //  or decoding function as appropriate.
  //
  //  Each may have the 'args' member, which is a list of arguments that will be passed to the
  //  function, in order.  If either array has the 'args' member, it must therefore also have the
  //  'data_arg' member, which is the index of the argument to override with the actual data to be
  //  compressed or uncompressed.
  //
  //  If the array does not have the 'args' and 'data_args' members, the function will be assumed
  //  to take only one value, the data for compression or decompression.



  $compressors = array(

    'gz' => array(
      'encode' => array('fname' => 'gzcompress',   'args' => array(false, 9), 'data_arg' => 0),
      'decode' => array('fname' => 'gzuncompress'),
      'desc'   => 'RFC 1950 ZLib compression at maximum'
    ),

    'gz_deflate' => array(
      'encode' => array('fname' => 'gzdeflate',   'args' => array(false, 9), 'data_arg' => 0),
      'decode' => array('fname' => 'gzinflate'),
      'desc'   => 'RFC 1951 ZLib deflate at maximum'
    ),

    'bz' => array(
      'encode' => array('fname' => 'bzcompress',   'args' => array(false, 9, 30), 'data_arg' => 0),
      'decode' => array('fname' => 'bzdecompress'),
      'desc'   => 'BZip2 compress at maximum, using work factor 30'
    ),

    // add other compressors here

  );



?>