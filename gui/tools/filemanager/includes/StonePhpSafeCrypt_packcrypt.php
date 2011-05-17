<?php





  function PackCrypt(&$Data, $WeakKey, $options=array() ) {

    $result = array(
      'success' => false,
      'reason'  => 'Incomplete pack for unknown reason; indicates horrible failure.',
      'output'  => false
    );

    // load options

    if (isset($options['cipher'])) {                                 // Check whether user specified an alternate cipher in the options
      $CipherType = $options['cipher'];                              // if so, use it
    } else {
      $CipherType = DEFAULT_ENCRYPTION_METHOD;                       // otherwise, use the default cipher
    }

    if (isset($options['mode'])) {                                   // Check whether user specified an alternate block mode in the options
      $mode = $options['mode'];                                      // if so, use it
    } else {
      $mode = DEFAULT_ENCRYPTION_MODE;                               // otherwise, use the default block mode
    }

    if (isset($options['salt'])) {                                   // Check whether user specified an alternate md5 salt in the options
      $salt = $options['salt'];                                      // if so, use it
    } else {
      $salt = DEFAULT_MD5_SALT;                                      // otherwise, use the default salt
    }


    // do preparation

    $SecretData = serialize($Data);                                  // Convert data into a serialized string for single packing
    $compressor = false;

    global $compressors;

    // handle potential compression

    if (isset($options['compressor'])) {

      if (isset($compressors[$options['compressor']])) {

        $compressor = $compressors[$options['compressor']];
        if (function_exists($compressor['encode']['fname'])) {
          if (function_exists($compressor['decode']['fname'])) {
            if (isset($compressor['encode']['args'])) {
              if (isset($compressor['encode']['data_arg'])) {

                $largs = $compressor['encode']['args'];
                $largs[$compressor['encode']['data_arg']] = $SecretData;

                $SecretData = call_user_func_array($compressor['encode']['fname'], $largs);

              } else {

                $result['reason'] = 'The requested compressor, "' . $options['compressor'] . '", requires an argument list; however the data argument has not been specified, making call impossible.';
                return $result;

              }
            } else {
              $SecretData = $compressor['encode']['fname']($SecretData);

            }
          } else {
            $result['reason'] = 'The requested compressor, "' . $options['compressor'] . '", requires the decode function "' . $compressor['decode']['fname'] . '", which is not present in this PHP installation.';
            return $result;
          }
        } else {
          $result['reason'] = 'The requested compressor, "' . $options['compressor'] . '", requires the encode function "' . $compressor['encode']['fname'] . '", which is not present in this PHP installation.';
          return $result;
        }

      } else {

        $result['reason'] = 'The requested compressor, "' . $options['compressor'] . '", is not configured in this script.';
        return $result;

      }

    }


    // do work

    $td         = mcrypt_module_open($CipherType, '', $mode, '');    // Open the cipher module

    $ks         = mcrypt_enc_get_key_size($td);                      // Get required key size
    $strongkey  = substr(md5($salt . $WeakKey), 0, $ks);             // Harden key data into safe key

    $ivsz       = mcrypt_enc_get_iv_size($td);                       // Get the size of the appropriate local initialization vector

// net2ftp - the 2nd argument of mcrypt_create_iv must be different on Unix and Windows
// Try first Unix, and if $iv is empty try Windows
// Use prefix @ to suppress PHP Warning messages
    $iv         = @mcrypt_create_iv($ivsz, MCRYPT_DEV_RANDOM);        // Generate an initialization vector ** Unix **
    if ($iv == "") {
        $iv         = @mcrypt_create_iv($ivsz, MCRYPT_RAND);              // Generate an initialization vector ** Windows **
    }

    mcrypt_generic_init($td, $strongkey, $iv);                       // Init encryption engine
    $encrypted  = mcrypt_generic($td, $SecretData);                  // Perform encryption
    mcrypt_generic_deinit($td);                                      // Shut down encryption engine

    mcrypt_module_close($td);                                        // Close the cipher module

    $IvDataPack = $iv . $encrypted;                                  // Prepend the IV onto the data stream for convenient transfer

    $result['output']  = BlockScramble($IvDataPack, $strongkey);      // Return the IV prepended to the data stream, CBC tamper protected
    $result['success'] = true;
    $result['reason']  = 'Successful pack.';

    return $result;

  }





  function UnpackCrypt(&$SecretData, $WeakKey, $options=array() ) {// $CipherType = 'tripledes', $mode = 'ofb') {

    $result = array(
      'success' => false,
      'reason'  => 'Incomplete unpack for unknown reason; indicates horrible failure.',
      'output'  => false
    );

    // load options

    if (isset($options['cipher'])) {                                 // Check whether user specified an alternate cipher in the options
      $CipherType = $options['cipher'];                              // if so, use it
    } else {
      $CipherType = DEFAULT_ENCRYPTION_METHOD;                       // otherwise, use the default cipher
    }

    if (isset($options['mode'])) {                                   // Check whether user specified an alternate block mode in the options
      $mode = $options['mode'];                                      // if so, use it
    } else {
      $mode = DEFAULT_ENCRYPTION_MODE;                               // otherwise, use the default block mode
    }

    if (isset($options['salt'])) {                                   // Check whether user specified an alternate md5 salt in the options
      $salt = $options['salt'];                                      // if so, use it
    } else {
      $salt = DEFAULT_MD5_SALT;                                      // otherwise, use the default salt
    }


    // do work

    $td             = mcrypt_module_open($CipherType, '', $mode, '');    // Open the cipher module

    $ks             = mcrypt_enc_get_key_size($td);                      // Get required key size
    $strongkey      = substr(md5($salt . $WeakKey), 0, $ks);             // Regenerate hardened key from weak key

    $DescrambleData = BlockDescramble($SecretData, $strongkey);          // Remove leading-block CBC tampering attack protection

    $ivsz           = mcrypt_enc_get_iv_size($td);                       // Get the size of the appropriate local initialization vector
    $iv             = substr($DescrambleData, 0, $ivsz);                 // Recover the initialization vector
    $WorkData       = substr($DescrambleData, $ivsz);                    // Recover the data block

    mcrypt_generic_init($td, $strongkey, $iv);                           // Init decryption engine
    $decrypted      = mdecrypt_generic($td, $WorkData);                  // Perform decryption
    mcrypt_generic_deinit($td);                                          // Shut down decryption engine

    mcrypt_module_close($td);                                            // Close the cipher module


    // handle potential decompression

    global $compressors;
    $compressor = false;

    if (isset($options['compressor'])) {

      if (isset($compressors[$options['compressor']])) {

        $compressor = $compressors[$options['compressor']];
        if (function_exists($compressor['encode']['fname'])) {
          if (function_exists($compressor['decode']['fname'])) {
            if (isset($compressor['decode']['args'])) {
              if (isset($compressor['decode']['data_arg'])) {

                $largs = $compressor['decode']['args'];
                $largs[$compressor['decode']['data_arg']] = $decrypted;

                $decrypted = call_user_func_array($compressor['decode']['fname'], $largs);

              } else {

                $result['reason'] = 'The requested compressor, "' . $options['compressor'] . '", requires an argument list; however the data argument has not been specified, making call impossible.';
                return $result;

              }
            } else {
              $decrypted = $compressor['decode']['fname']($decrypted);
            }
          } else {
            $result['reason'] = 'The requested compressor, "' . $options['compressor'] . '", requires the decode function "' . $compressor['decode']['fname'] . '", which is not present in this PHP installation.';
            return $result;
          }
        } else {
          $result['reason'] = 'The requested compressor, "' . $options['compressor'] . '", requires the encode function "' . $compressor['encode']['fname'] . '", which is not present in this PHP installation.';
          return $result;
        }

      } else {

        $result['reason'] = 'The requested compressor, "' . $options['compressor'] . '", is not configured in this script.';
        return $result;

      }

    }

    $result['success'] = true;
    $result['reason']  = 'Successful unpack.';
    $result['output']  = unserialize($decrypted);                        // Convert data from a serialized string and return

    return $result;

  }





?>