<?php

  /////////
  //
  //  Stone PHP SafeCrypt Block Scrambler
  //  -----------------------------------
  //
  //  Block scramble and block descramble are utility functions for Pack crypt and Pack decrypt.
  //  These are usable as lightweight encryption, and are very fast, but are also very weak and
  //  should not be used independantly.  These are only used because of the specific, well-known
  //  characteristics of the IV stream as used by Pack crypt, where the OTP nature of the IV
  //  leader makes these scrambles sufficient to prevent CBC MITM leading-block attacks.






  function BlockScramble(&$data, &$weakkey) {

    // Performs a simple modulo arithmetic cipher on the IV and datastream.  The PHP manual
    // incorrectly states that the initialization vector may safely be transmitted plaintext;
    // in http://www.ciphersbyritter.com/GLOSSARY.HTM#IV it's made clear that in CBC mode, a
    // man in the middle attack is possible on the very first block returned by manipulating
    // the IV.  However, since the IV is just a randomness salt, it carries all of the
    // important characteristics of a truncated one time pad; therefore, rotated with the MD5
    // hash of the key, which is well-distributed, we have a non-attackable binary result.
    // This protects CBC mode encryptions from a MITM leading block attack; also, it's nice
    // to have an extra source of white noise in the signal to slow down identifications.

    $strongkey = md5($weakkey);

    $keysize   = strlen($strongkey);                                       // because calling sizeof() every ten cycles is retarded
    $datasize  = strlen($data);                                            // and again
    $output    = str_repeat(' ', $datasize);                               // pre-allocate output buffer to prevent reallocation thrash

    $di        = 0;                                                        // data index cursor
    $bi        = 0;                                                        // block index cursor
// net2ftp - added the next line to avoid a PHP Notice about an "undefined variable"
    $ki        = 0;

    for (; $di < $datasize; ++$di, ++$ki) {
      if ($ki >= $keysize) { $ki = 0; }                                    // key's usually smaller than data, so bound it
      $output[$di] = chr((ord($data[$di]) + ord($strongkey[$ki])) % 256);  // and record the scrambled byte
    }

    return $output;

  }





  function BlockDescramble(&$data, &$weakkey) {

    // Performs a simple modulo arithmetic cipher on the IV and datastream.  The PHP manual
    // incorrectly states that the initialization vector may safely be transmitted plaintext;
    // in http://www.ciphersbyritter.com/GLOSSARY.HTM#IV it's made clear that in CBC mode, a
    // man in the middle attack is possible on the very first block returned by manipulating
    // the IV.  However, since the IV is just a randomness salt, it carries all of the
    // important characteristics of a truncated one time pad; therefore, rotated with the MD5
    // hash of the key, which is well-distributed, we have a non-attackable binary result.
    // This protects CBC mode encryptions from a MITM leading block attack.

    $strongkey = md5($weakkey);

    $output    = str_repeat(' ', strlen($data));         // pre-allocate output buffer to prevent reallocation thrash
    $keysize   = strlen($strongkey);                     // because calling sizeof() every ten cycles is retarded
    $datasize  = strlen($data);                          // and again

    $di        = 0;                                      // data index cursor
    $bi        = 0;                                      // block index cursor
// net2ftp - added the next line to avoid a PHP Notice about an "undefined variable"
    $ki        = 0;

    for (; $di < $datasize; ++$di, ++$ki) {
      if ($ki >= $keysize) { $ki = 0; }                  // key's usually smaller than data, so bound it
      $work = (ord($data[$di]) - ord($strongkey[$ki]));  // descramble the scrambled byte
      if ($work < 0) { $work += 256; }                   // reorigin low-range bytes
      $output[$di] = chr($work);                         // record the origin-normalized byte
    }

    return $output;

  }





?>