<?php
/**
 * general.mod
 * ----------------
 * GPG General Options module
 * Copyright (c) 2002-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * General options screen.
 *
 * @author Brian Peterson
 *
 * $Id$
 */

/* From the TODO file
 *  - Provide a User Preferences Screen,
 *    accessed from the "Options" screen in Squirrelmail
 *
 *      - allow automatic keyserver lookup of public keys? (Y/N)
 *
 *      - trust system-wide public keyring? (Y/N)
 *          (if the sysadmin has turned this on and created one)
 *
 *      - allow encryption to only some recipients (Y/N)
 *
 *      - allow user to select whether to encrypt to self by default
 *          - will add -r $self_encr_email to recipient list
 *
 */

//make the global $debug setting available here
global $debug;

$GPG_DIR="gpg";

require_once(SM_PATH.'plugins/gpg/gpg_key_functions.php');

// get the variables we need from the _POST without extract ($_POST);
$form = 0;
if (array_key_exists('form', $_POST)) { $form = $_POST ['form'];}

if ($form == 1) {
   $encrypt_to_self          = (array_key_exists('encrypt_to_self',$_POST) ? $_POST['encrypt_to_self'] : '');
   $self_encr_email          = (array_key_exists('self_encr_email',$_POST) ? $_POST['self_encr_email'] : '');
   $allow_partial_encryption = (array_key_exists('allow_partial_encryption',$_POST) ? $_POST['allow_partial_encryption'] : '');
   $trust_system_keyring     = (array_key_exists('trust_system_keyring',$_POST) ? $_POST['trust_system_keyring'] : '');
   $use_system_adk           = (array_key_exists('use_system_adk',$_POST) ? $_POST['use_system_adk'] : '');
   $autoencrypt              = (array_key_exists('autoencrypt',$_POST) ? $_POST['autoencrypt'] : '');
   $autosign                 = (array_key_exists('autosign',$_POST) ? $_POST['autosign'] : '');
   $cache_passphrase         = (array_key_exists('cache_passphrase',$_POST) ? $_POST['cache_passphrase'] : '');
   $showkeyringlink      = (array_key_exists('showkeyringlink',$_POST) ? $_POST['showkeyringlink'] : 'true');
   $automatic_key_lookup = '';
   //automatic_key_lookup needs to be added here once implemented
   $parse_openpgp_header     = $_POST['parse_openpgp_header'];
   $generate_openpgp_header  = $_POST['generate_openpgp_header'];
   $openpgp_header_url	     = $_POST['openpgp_header_url'];
}

/**
 * check to see if preferences we need are set using GetPref
 * for now, set all these options to true if the preferences come back null
 * eventually, we should probably allow the system admin to set defaults in
 * the config files and use those defaults
 *
 * Design Philosophy:
 * assume that relatively harmless options are true by default
 * and that really dodgy ones are false by default
 * (this also gives some variation on the screen ;-)
 */

//global $GPG_SYSTEM_OPTIONS;
$GPG_SYSTEM_OPTIONS=$GLOBALS['GPG_SYSTEM_OPTIONS'];
$systemkeyring=$GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyring'];
$systemadk =   $GLOBALS['GPG_SYSTEM_OPTIONS']['systemadk'];
$systemsign_on_send = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemsign_on_send'];
$force_sign_on_send = $GLOBALS['GPG_SYSTEM_OPTIONS']['force_sign_on_send'];
$systemencrypt_on_send = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemencrypt_on_send'];
$force_encrypt_on_send = $GLOBALS['GPG_SYSTEM_OPTIONS']['force_encrypt_on_send'];
$allowpassphrasecaching = $GLOBALS['GPG_SYSTEM_OPTIONS']['allowpassphrasecaching'];
$systemparse_openpgp_header = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemparse_openpgp_header'];
$systemgenerate_openpgp_header = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemgenerate_openpgp_header'];

if (!$form) {

    //set defaults
    $encrypt_to_self='true';
    $self_encr_email  = getPref($data_dir, $username, 'self_encr_email');
    $allow_partial_encryption='true';
    $trust_system_keyring='false';
    $automatic_key_lookup='false';
    $parse_openpgp_header = 'true';
    $generate_openpgp_header = 'true';

    //now load preferences if they exist.
    $encrypt_to_self=getPref($data_dir,$username,'encrypt_to_self');
    if (!$self_encr_email) {
        //load the user's email address if they haven't set a preference
        $self_encr_email = getPref($data_dir, $username, 'email_address');
    }
    $self_encr_email = htmlspecialchars($self_encr_email);
    $allow_partial_encryption=getPref($data_dir,$username,'allow_partial_encryption');
    $trust_system_keyring=getPref($data_dir,$username,'trust_system_keyring');
    $use_system_adk= getPref($data_dir,$username,'use_system_adk');
    $automatic_key_lookup=getPref($data_dir,$username,'automatic_key_lookup');
    $auto_encrypt       = getPref ($data_dir, $username, 'auto_encrypt');
    $cache_passphrase   = getPref ($data_dir, $username, 'cache_passphrase');
    $showkeyringlink = getPref($data_dir, $username, 'showkeyringlink');
    $parse_openpgp_header = getPref($data_dir, $username, 'parse_openpgp_header', $parse_openpgp_header);
    $generate_openpgp_header = getPref($data_dir, $username, 'generate_openpgp_header', $generate_openpgp_header);
    $openpgp_header_url = getPref($data_dir, $username, 'openpgp_header_url');
    if (($auto_encrypt == '') and (($systemencrypt_on_send == 'true') or ($force_encrypt_on_send == 'true'))) {
        //set system default only if user has not selected an option
        $auto_encrypt = 'true';
    }
    $auto_sign  = getPref ($data_dir, $username, 'auto_sign');
    if (($auto_sign == '') and (($systemsign_on_send == 'true') or ($force_sign_on_send == 'true'))) {
        //set system default only if user has not selected an option
        $auto_sign = 'true';
    }
    if ($auto_encrypt == 'true') {$aechecked='checked';} else { $aechecked=''; }
    if ($auto_sign == 'true') {$aschecked='checked';} else { $aschecked=''; }

    gpg_page_title( _("GPG Plugin - General Options"));

    echo
      "\n<p>"
      . _("This screen allows you to set general GPG Plugin options.")
      . '<br>'
      . _("These options have broad effects on how the plugin works, and can help you be more productive.")
      . '<p>'
      . '<FORM METHOD="POST" >'
      . '<input type="hidden" name="form" value=1>'
      . '<input type="hidden" name="MOD" value="general" >'
      . "\n";

    //Keyring Link in Main Bar
    echo '<p>'
    . '<b>'
    . _("Keyring Link in Main Menu Bar")
    . '</b><br>'
    . _("Should the GPG Plugin place a link to the Keyring in the main menu bar?")
    . '<br>';
    if ($showkeyringlink != 'false') {
        echo '<input type="radio" name="showkeyringlink" value="true" checked>' . _("Yes")
        . '<input type="radio" name="showkeyringlink" value="false">' . _("No");
    } else {
        echo '<input type="radio" name="showkeyringlink" value="true">' . _("Yes")
        . '<input type="radio" name="showkeyringlink" value="false" checked>' . _("No");
    }
    // Partial encryption
    echo
      '<p>'
      . '<b>'
      . _("Partial Recipient Encryption Option")
      . '</b><br>'
      . _("Sometimes, you may not have public keys for all of the email addresses in your To, CC, and BCC fields in the Compose window.")
      . '&nbsp;'
      . _("When this happens, the GPG plugin can either display an error and return to your plaintext, or encrypt the message to the recipients that you do have keys for, and inform you of the email addresses that you did not have keys for.")
      . '<p>'
      . _("Do you want the GPG plugin to encrypt the message to only some recipients, if you do not have public keys for everyone?")
      . '<br>';
    if ($allow_partial_encryption=='true')
    {
       echo
         '<input TYPE="radio" NAME="allow_partial_encryption" VALUE="true" checked>' . _("Yes")
         . '<input TYPE="radio" NAME="allow_partial_encryption" VALUE="false" >' . _("No");
    }
    else {
       echo
         '<input TYPE="radio" NAME="allow_partial_encryption" VALUE="true" >' . _("Yes")
         . '<input TYPE="radio" NAME="allow_partial_encryption" VALUE="false" checked>' . _("No");
    };
    //end partial encryption option

    // Encrypt to Self
    echo
      '<p><b>'
      . _("Encrypt to Self")
      . '</b><br>'
      . _("When you encrypt an email message, the plugin can be set to automatically include your email address in the recipient list.")
      . '&nbsp;'
      . _("If your keyring has a public key for this email address on it, then all messages you encrypt will be readable by you later.")
      . '<p>'
      . _("If you wish to encrypt all messages to multiple keys, you may separate a list of email addresses or public keyids with commas.")
      . '<p>'
      . _("If you set this option to 'No', you will not be able to decrypt messages encrypted with the plugin unless you add your email address in the To, CC, or BCC line of the email before encrypting it.")
      . '<p>'
      . _("Do you want the plugin to encrypt mail to your address, in addition to the recipients?")
      . '<br>';

    if ($encrypt_to_self=='true')
    {
       echo
         '<input TYPE="radio" NAME="encrypt_to_self" VALUE="true" checked>' . _("Yes")
         . '<input TYPE="radio" NAME="encrypt_to_self" VALUE="false" >' . _("No");
    }
    else {
       echo
         '<input TYPE="radio" NAME="encrypt_to_self" VALUE="true" >' . _("Yes")
         . '<input TYPE="radio" NAME="encrypt_to_self" VALUE="false" checked>' . _("No");
    };
    echo
      '<br>'
      . _("If you selected 'Yes', what is the email address you would like to encrypt to:")
      . "<br><input TYPE='text' NAME='self_encr_email' SIZE='40' MAXLENGTH='60' VALUE='$self_encr_email'><br>";
    //end encrypt to self option

    // automatic lookup of public keys
    /**
     * @todo Automatic Key Lookup
     * The option to automatically look up public keys will work well
     * with the partial encryption option.  The idea is that if you
     * select this option, we will do some automated processing to
     * retrieve keys for the email addresses in your recipient list
     * and add them to the user's public keyring before calling the
     * encrypt function.
     *
     * This would let a user try to encrypt messages to people,
     * even without manually looking up their keys.
     *
     * Probably not for Release 1 (targeted for 2.0)
     */

    //Default encrypt on send
    echo "\n<p>"
         . '<b>'
         . _("'On Send' Defaults")
         . '</b><br>'
         . _("The plugin supports the ability to set default options to be applied every time you send a message.")
         . '<br>'
         . _("You have the option to attempt to Encrypt, Sign, or Encrypt and Sign every message that you send, by default. ")
         . '<p>'
         . _("You may encrypt your email either after you press the 'Encrypt Now' button from the Compose page, or at the time you send the message.")
         . '<br>'
         . _("If you with to Encrypt all messages by default, select the 'Encrypt on Send' option below.")
         . '<br>'
         . _("Be aware that using this option in combination with the partial recipients option, above, may result in unintended consequences.")
         . '&nbsp;'
         . _("Your message may be sent without being readable by some recipients.")
         . '<p>'
         . _("If you wish to Sign all of your messages by default, you may also select the 'Sign on Send' option below.")
         . '<br>'
         . _("If you Sign your messages, the message will be verifiable as being from you, and the recipient will be able to make sure that the message was not tampered with in transit.")
         . '<p>'
         . _("If you set one of these options below, you will have the opportunity on the Compose page to turn it off on a message by message basis.")
         . '&nbsp;'
         . _("This preference only sets the default behavior.")
         . '<br>'
         . _("The plugin will return you to the Compose page with your original message (plaintext) preserved if there is an error that requires your attention after you press 'Send'.")
         . "</p>\n";

    echo "\n<p>";
    if ($force_encrypt_on_send == 'true') {
        echo "<br><input type=checkbox name=autoencrypt $aechecked onchange='this.checked=true' value='auto_encrypt'> " . _("Encrypt on Send by Default") . " <br>&nbsp;&nbsp;<b>-" . _("Your system administrator has made this option mandatory.") . "</b>\n";
    } else {
        echo "<br><input type=checkbox name=autoencrypt $aechecked  value='auto_encrypt'> " . _("Encrypt on Send by Default") . "\n";
    }
    if ($force_sign_on_send == 'true') {
        echo "<br><input type=checkbox name=autosign $aschecked onchange='this.checked=true' value='auto_sign'> " . _("Sign on Send by Default") . " <br><b>&nbsp;&nbsp;-" . _("Your system administrator has made this option mandatory.") . "</b>\n";
    } else {
        echo "<br><input type=checkbox name=autosign $aschecked value='auto_sign'> " . _("Sign on Send by Default") . "\n";
    }

    echo "</p>\n";
    //end encrypt on send default

    // system keyring options
    if ($systemkeyring=='true') {
       echo
          '<p>'
          . '<b>'
          . _("Shared System Keyring")
          .'</b><br>'
          . _("Your System administrator has indicated that there is a 'system-wide' public keyring.")
          . '&nbsp;'
          . _("Usually, this would suggest that a keyring is maintained for some group which you are a part of, such as an organization or corporation.")
          . '&nbsp;'
          . _("You must choose whether to trust this keyring and use it in your public key lookups.")
          . '&nbsp;'
          . _("If you select 'Yes' below, the plugin will search the 'system-wide' keyring for public keys after looking at your public keyring.")
          . '&nbsp;'
          . _("If you set this option to 'No', you will not have access to any keys stored on the system keyring, which may include keys for members of a group to which you belong.")
          . '<p>'
          . _("Do you want to trust the system-wide keyring that has been defined by your system administrator?")
          . '<br>';
        if ($trust_system_keyring=='true')
        {
           echo
             '<input TYPE="radio" NAME="trust_system_keyring" VALUE="true" checked>' . _("Yes")
             . '<input TYPE="radio" NAME="trust_system_keyring" VALUE="false" >' . _("No");
        }
        else {
           echo
             '<input TYPE="radio" NAME="trust_system_keyring" VALUE="true" >' . _("Yes")
             . '<input TYPE="radio" NAME="trust_system_keyring" VALUE="false" checked>' . _("No");
        };

        if ($systemadk!='') {
            echo
               '<p>'
               . _("Your system administrator has also defined an Alternate Decryption Key (ADK) for the system.")
               . '<br>'
               . _("This key has the properties:")
               .'<br>'
               . '<pre>'
               . htmlspecialchars(implode ("\n", (gpg_list_keys ($debug, $systemadk, 'false', 'system', 'false'))))
               . '</pre>'
               . '<br>'
               . _("You must choose whether to use this ADK.")
               . '&nbsp;'
               . _("If you select 'Yes' below, all messages that you encrypt will also be encrypted to the ADK.")
               . '&nbsp;'
               . _("The holder of the ADK will be able to decrypt these messages.")
               . '&nbsp;'
               . _("If you select 'No' below, the holder of the ADK will not be able to decrypt your messages.")
               . '&nbsp;'
               . '<p>'
               . _("Do you want to use the system-wide ADK that has been defined by your system administrator?")
               . '<br>';
            if ($use_system_adk=='true')
            {
               echo
                   '<input TYPE="radio" NAME="use_system_adk" VALUE="true" checked>'. _("Yes")
                 . '<input TYPE="radio" NAME="use_system_adk" VALUE="false" >' . _("No");
            }
            else {
               echo
                 '<input TYPE="radio" NAME="use_system_adk" VALUE="true" >'. _("Yes")
                 . '<input TYPE="radio" NAME="use_system_adk" VALUE="false" checked>' . _("No");
            };
        };
    };
    //end system keyring options

    //passphrase caching
    if ($allowpassphrasecaching=='true'){
        echo
            '<p>'
            . '<b>'
            . _("Passphrase Caching")
            . '</b>'
            . '<br>'
            . _("The system has the ability to cache (store) the passphrase for your secret key.")
            . '<p>'
            . _("Caching the passphrase can be very convenient, but many security experts recommend against storing the passphrase on the server because it may be more susceptible to an attack.")
            . '&nbsp;'
            . _("If you choose to cache your passphrase, your passphrase will be stored on the Web Mail server in secure storage for use by you while you are logged in.")
            . '<p>'
            . _("If you select 'Yes' below, you will only be prompted for your passphrase once during a session.")
            . '<br>'
            . _("If you select 'No' below, you will be prompted for your passphrase every time it is needed for decryption or signing of messages.")
            . '<p>'
            . _("Do you want the system to cache your passphrase?")
            . '<br>';

            if ($cache_passphrase=='true')
            {
               echo
                 '<input TYPE="radio" NAME="cache_passphrase" VALUE="true" checked>' . _("Yes")
                 . '<input TYPE="radio" NAME="cache_passphrase" VALUE="false" >' . _("No");
            }
            else {
               echo
                 '<input TYPE="radio" NAME="cache_passphrase" VALUE="true" >' . _("Yes")
                 . '<input TYPE="radio" NAME="cache_passphrase" VALUE="false" checked>' . _("No");
            };
    };
    //end passphrase caching options

    //openpgp header options
    if($systemparse_openpgp_header == 'true' || $systemgenerate_openpgp_header == 'true') {
	echo
	    '<p>'
	    . '<b>'
	    . _("OpenPGP Header Option")
	    . '</b>'
	    . '<br>'
	    . _("The OpenPGP header allows users the oppportunity to advertise their key id and optionally a url where their key may be fetched.")
	    . '<p>'
	    . _("This is very convenient, as you can simply click a button and add the key of any user that emails you.")
	    . '&nbsp;'
	    . _("However, through a sophisticated type of attack, known as a \"man in the middle\" attack, a user could pretend to be someone else and send you his public key instead.")
	    . '&nbsp;'
	    . _("This type of attack would require this user to redirect every single email you send and receive, from now until eternity, otherwise something would go wrong and those parties who were communicating would discover the attack.")
	    . '&nbsp;'
	    . '<p>'
	    . _("Those with high security needs should disable this option -- but those people will also probably not want to use email on a web server.");
	if($systemparse_openpgp_header == 'true') {
	    echo '<p>'
		. _("Do you want the system to parse the OpenPGP header?")
		. '<br>';
	    
	    if ($parse_openpgp_header=='true') {
		echo
		    '<input TYPE="radio" NAME="parse_openpgp_header" VALUE="true" checked>' . _("Yes")
		    . '<input TYPE="radio" NAME="parse_openpgp_header" VALUE="false" >' . _("No");
	    } else {
		echo
		    '<input TYPE="radio" NAME="parse_openpgp_header" VALUE="true" >' . _("Yes")
		    . '<input TYPE="radio" NAME="parse_openpgp_header" VALUE="false" checked>' . _("No");
	    };
	}
	if($systemgenerate_openpgp_header == 'true') {
	    echo
		'<p>'
		. _("Do you want the system to generate an OpenPGP header?")
		. '&nbsp;'
		. _("You will have to select a public key to advertise to make this work.")
		. '<br>';
	    
	    if ($generate_openpgp_header=='true') {
		echo
		    '<input TYPE="radio" NAME="generate_openpgp_header" VALUE="true" checked>' . _("Yes")
		    . '<input TYPE="radio" NAME="generate_openpgp_header" VALUE="false" >' . _("No");
	    } else {
		echo
		    '<input TYPE="radio" NAME="generate_openpgp_header" VALUE="true" >' . _("Yes")
		    . '<input TYPE="radio" NAME="generate_openpgp_header" VALUE="false" checked>' . _("No");
	    };
	    echo
		'<br>'
		. _("Optional: if you want to give a url where your key may be fetched, do so here.")
		. '<br>'
		. "<input TYPE='text' NAME='openpgp_header_url' SIZE='40' MAXLENGTH='60' VALUE='$openpgp_header_url'><br>";
	}
    } //end openpgp header options

    //wrap up and submit
    echo
    '<p><br><input type=submit value="' . _("Save") . '">'
    . '<input type=submit name=can value="' . _("Cancel") . '">'
    . '</form>';

    //return
} else {
    //Did they cancel?
    if (array_key_exists('can',$_POST)) {
        //Send them back to getting started
        require_once(SM_PATH.'plugins/gpg/modules/options_main.mod');
        exit;
    }

    /* Process the form input */
    if ($allow_partial_encryption=='true')
    {
        setPref ($data_dir, $username, 'allow_partial_encryption', 'true');
        echo '<p>'. _("Your Preference to allow partial encryption has been saved.")."\n";
    } else {
        setPref ($data_dir, $username, 'allow_partial_encryption', 'false');
        echo '<p>'. _("Your Preference to not allow partial encryption has been saved.")."\n";
    };
    if ($debug) {
        echo '<br>allow_partial_encryption = ' . getPref($data_dir, $username, 'allow_partial_encryption');
    };

    if ($encrypt_to_self=='true')
    {
        setPref ($data_dir, $username, 'encrypt_to_self', 'true');
        setPref ($data_dir, $username, 'self_encr_email', $self_encr_email);
        $self_encr_email=htmlspecialchars($self_encr_email);
        echo '<p>'. _("Your Preference to encrypt a copy of all messages to").'&nbsp;'.$self_encr_email.'&nbsp;'._("has been saved.")."\n";
    } else {
        setPref ($data_dir, $username, 'encrypt_to_self', 'false');
        echo '<p>'. _("Your Preference to not encrypt a copy of every message to yourself has been saved.")."\n";
    };
    if ($debug) {
        echo '<br>encrypt_to_self = ' . getPref($data_dir, $username, 'encrypt_to_self');
        echo '<br>self_encr_email = ' . htmlspecialchars(getPref($data_dir, $username, 'self_encr_email'));
    };

    if ($automatic_key_lookup=='true')
    {
        setPref ($data_dir, $username, 'automatic_key_lookup', 'true');
        echo '<p>'. _("Your Preference to request automatic key lookup has been saved")."\n";
    } else {
        setPref ($data_dir, $username, 'automatic_key_lookup', 'false');
    };
    if ($debug) {
       echo '<br>automatic_key_lookup = ' . getPref($data_dir, $username, 'automatic_key_lookup');
    };

    if ($showkeyringlink=='true')
    {
    setPref ($data_dir, $username, 'showkeyringlink', 'true');
    echo '<p>'. _("Your Preference to show the keyring link in the main menu bar has been saved.")."\n";
    } else {
    setPref ($data_dir, $username, 'showkeyringlink', 'false');
    echo '<p>'. _("Your Preference to not show the keyring link in the main menu bar has been saved.")."\n";
    }

    if ($autoencrypt=='auto_encrypt') {
        setPref ($data_dir, $username, 'auto_encrypt', 'true');
        echo '<p>'. _("Your Preference to attempt to Encrypt on Send by default has been saved.")."\n";
    } else {
        setPref ($data_dir, $username, 'auto_encrypt', 'false');
        echo '<p>'. _("Your Preference to not Encrypt on Send by default has been saved.")."\n";
     }
     if ($autosign=='auto_sign') {
        setPref ($data_dir, $username, 'auto_sign', 'true');
        echo '<p>'. _("Your Preference to attempt to Sign on Send by default has been saved.")."\n";
     }else {
        setPref ($data_dir, $username, 'auto_sign', 'false');
        echo '<p>'. _("Your Preference to not Sign on Send by default has been saved.")."\n";
     }

    if ($systemkeyring=='true') {
        if ($trust_system_keyring=='true')
        {
            setPref ($data_dir, $username, 'trust_system_keyring', 'true');
            echo '<p>'. _("Your Preference to trust the system keyring has been saved.")."\n";
        } else {
            setPref ($data_dir, $username, 'trust_system_keyring', 'false');
            echo '<p>'. _("Your Preference to not trust the system keyring has been saved.")."\n";
        };
        if ($systemadk!='')
        {
            if ($use_system_adk=='true')
            {
                setPref ($data_dir, $username, 'use_system_adk', 'true');
                echo '<p>'. _("Your Preference to use the system ADK has been saved.")."\n";
            } else {
                setPref ($data_dir, $username, 'use_system_adk', 'false');
                echo '<p>'. _("Your Preference to not use the system ADK has been saved.")."\n";
            };
        };

    };
    if ($debug) {
        echo '<br>trust_system_keyring = ' . getPref($data_dir, $username, 'trust_system_keyring');
    };

    if ($allowpassphrasecaching=='true'){
        if ($cache_passphrase=='true')
        {
            setPref ($data_dir, $username, 'cache_passphrase', 'true');
            echo '<p>'. _("Your Preference to cache your passphrase has been saved.")."\n";
        } else {
            setPref ($data_dir, $username, 'cache_passphrase', 'false');
            echo '<p>'. _("Your Preference to not cache your passphrase has been saved.")."\n";
            if (gpg_get_cached_passphrase() != 'false') {
                gpg_clear_cached_passphrase();
                echo '<p>'. _("Your cached passphrase has been securely erased.");
            };
        };
    };
    if ($debug) {
        echo '<br>cache_passphrase = '
        . getPref($data_dir, $username, 'cache_passphrase');
    };

    if($systemparse_openpgp_header == 'true') {
	if ($parse_openpgp_header=='true'){
	    setPref ($data_dir, $username, 'parse_openpgp_header', 'true');
	    echo '<p>'. _("Your preference to parse the OpenPGP header has been saved.")."\n";
	} else {
	    setPref ($data_dir, $username, 'parse_openpgp_header', 'false');
	    echo '<p>'. _("Your preference not to parse the OpenPGP header has been saved.")."\n";
	}
	if ($debug) {
	    echo '<br>parse_openpgp_header = '
		. getPref($data_dir, $username, 'parse_openpgp_header');
	};
    }
    if($systemgenerate_openpgp_header == 'true') {
	if ($generate_openpgp_header=='true'){
	    setPref ($data_dir, $username, 'generate_openpgp_header', 'true');
	    setPref ($data_dir, $username, 'openpgp_header_url', $openpgp_header_url);
	    echo '<p>'. _("Your preference to generate an OpenPGP header has been saved.")."\n";
	} else {
	    setPref ($data_dir, $username, 'generate_openpgp_header', 'false');
	    echo '<p>'. _("Your preference not to generate an OpenPGP header has been saved.")."\n";
	}
	if ($debug) {
	    echo '<br>generate_openpgp_header = '
		. getPref($data_dir, $username, 'generate_openpgp_header');
	    echo '<br>openpgp_header_url = '
		. getPref($data_dir, $username, 'openpgp_header_url');
	};
    }
};

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * c-basic-offset: 4
 * End:
 */

/**
 * $Log: general.mod,v $
 * Revision 1.39  2006/01/08 02:47:20  ke
 * - committed patch from Evan <umul@riseup.net> for OpenPGP header support in squirrelmail
 * - adds system preferences and user options to control parsing and adding of OpenPGP Headers on emails
 * - slightly tweaked to use the key associated with the identity, when identities with signing keys are enabled
 *
 * Revision 1.38  2005/07/27 14:07:49  brian
 * - update copyright to 2005
 *
 * Revision 1.37  2005/07/27 13:51:32  brian
 * - remove all code to handle SM versions older than SM 1.4.0
 * Bug 262
 *
 * Revision 1.36  2004/08/23 06:52:20  ke
 * added a line break and dashes to mandatory on send warning messages
 * bug 83
 *
 * Revision 1.35  2004/08/16 13:44:29  joelm
 * -added two config options to allow a sys admin to force users to always sign
 * or encrypt email
 * Bug 83
 *
 * Revision 1.34  2004/03/18 20:09:37  brian
 * - added missing ')'
 *   - patch provided by Tassium (Chris Hilts)
 *
 * Revision 1.33  2004/03/15 23:44:05  brian
 * - added text describing multiple key list
 * Bug 173
 *
 * Revision 1.32  2004/03/03 19:45:02  ke
 * -added option to show or hide the keyring link on the main menu bar
 *
 * Revision 1.31  2004/02/17 22:47:21  ke
 * -changed options to use GLOBALS when grabbing gpg prefs
 *
 * Revision 1.30  2004/01/19 19:21:34  ke
 * -E_ALL fixes
 *
 * Revision 1.29  2004/01/16 23:06:49  brian
 * E_ALL fixes
 * bug 146
 *
 * Revision 1.28  2003/12/11 19:52:50  ke
 * -changed break to exit so that options will not error when cancel is clicked.
 *
 * Revision 1.27  2003/11/04 21:41:01  brian
 * change to use SM_PATH
 *
 * Revision 1.26  2003/11/03 19:40:35  brian
 * minor wording changes in advance of translation.
 * Bug 35
 *
 * Revision 1.25  2003/11/01 22:00:43  brian
 * - standardized text across several pages
 * - localized remaining strings
 * - removed $msg strings and Makepage fn
 *
 * Revision 1.24  2003/10/30 18:56:21  brian
 * spell checked all localized strings
 * Bug 35
 *
 * Revision 1.23  2003/10/30 18:44:55  brian
 * modified all localized strings to use double quotes.
 * Bug 35
 *
 * Revision 1.22  2003/10/20 19:13:16  walter
 * added gpg_module_footer.php to page
 *
 * Revision 1.21  2003/09/30 01:52:56  ke
 * -Added internationalization functions to controls (Yes No, Encrypt on Send by Default, etc)
 * bug 35
 *
 * Revision 1.20  2003/09/29 15:22:47  brian
 * modified to call gpg_clear_cached_passphrase if user turns off caching
 * Bug 40
 *
 * Revision 1.19  2003/09/25 21:56:44  brian
 * improved display of passphrase caching option
 * Bug 40
 *
 * Revision 1.18  2003/09/25 21:43:28  brian
 * added user defaults for passphrase caching
 * Bug 40
 *
 * Revision 1.17  2003/09/25 11:45:28  brian
 * added system level defaults for 'On Send' options
 * Bug 60
 *
 * Revision 1.16  2003/09/17 21:43:19  ke
 * Changed encrypt_and_sign/encrypt_on_send/none switch to use two options: encrypt on send, sign on send
 *
 * Revision 1.15  2003/08/13 07:26:19  vermette
 * accidentally removed, oops
 *
 * Revision 1.13  2003/07/01 06:21:46  vermette
 * adding escape routes to options suite.  The previous 'back' link now only appears if requested (new arg to makePage).  This isn't done by any means, but at most it's as broken as it was, so it's an improvement.
 *
 * Revision 1.12  2003/06/13 15:19:15  brian
 * modified call to gpg_list_keys to properly format output for adk display
 *
 * Revision 1.11  2003/06/12 21:08:14  brian
 * added ADK options
 *
 * Revision 1.10  2003/05/16 16:32:32  brian
 * added version check to disable encrypt on send in SM < 1.4.0
 *
 * Revision 1.9  2003/05/13 22:57:32  brian
 * added code to implement preferences UI for encrypt on send
 * Bug 26
 *
 * Revision 1.8  2003/05/09 16:31:37  brian
 * - fixed typos in confirmation messages
 * - added more comments
 *
 * Revision 1.7  2003/05/01 20:27:09  brian
 * - added specific confirmations of preference settings after form submit
 * - fixed bug that prevented proper display of self encrypt email when it was set.
 *
 * Revision 1.6  2003/04/07 22:21:59  brian
 * modified files to not use extract $_POST
 * Bug 5
 *
 * Revision 1.5  2003/04/02 20:48:39  brian
 * fixed spelling errors using aspell
 * TODO - check grammer and sentence structure manually
 * Bug 18
 *
 * Revision 1.4  2003/03/07 12:58:28  brian
 * Removed enctype=multipart/form-data - not needed for this form
 * Removed second reference to hidden field 'form' - superfluous
 *
 * Revision 1.3  2003/03/06 23:20:44  brian
 * Fixed bug in setting preferences
 * (only set defaults before form has been submitted)
 *
 * Revision 1.2  2003/02/22 20:12:46  brian
 * Added text to describe the automatic key lookup option, not in this release.
 *
 * Revision 1.1  2003/01/24 16:40:25  brian
 * Setting preferences for encr_to_self, trust_system_keyring, automatic_key_lookup, and allow_partial_encryption
 */
?>
