<?php
/**
 * cachepass.mod
 *-----------
 * GPG plugin passphrase collecting & caching module file,
 *
 * Copyright (c) 1999-2003 The SquirrelMail development team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Copyright (c) 2002-2003 Braverock Ventures
 *
 * $Id: cachepass.mod,v 1.11 2003/11/12 17:37:59 ke Exp $
 */


require_once(SM_PATH.'plugins/gpg/gpg_functions.php');

$psaction = $_POST['psaction'];
$addbasepath = $_POST['addbasepath'];
switch ($psaction) {
    case 'sign':
        $onload = 'gpg_sign_submit();';
        break;
    case 'signdraft':
        $onload = 'gpg_signdraft_submit();';
        break;
    case 'encrsign':
        $onload = 'gpg_encrsign_submit();';
        break;
    case 'decrypt':
        $onload = 'gpg_decrypt_submit();';
        break;
}
$passphrase = $_POST['passphrase'];
//check passphrase for default key
$return = gpg_verify_passphrase($passphrase);
if ($return['verified'] == 'false') {
    $pserr=_("Bad Passphrase.") . ' ' . _(" Please try again.");
    include(SM_PATH.'plugins/gpg/modules/passpop.mod');
    exit();
}
if ($passphrase != 'false' and gpg_is_passphrase_cacheable() and $return['verified']=='true') {
    gpg_set_cached_passphrase($passphrase);
    $passphrase = 'true';
}

echo "<HTML>\n<HEAD>";
echo "<script language=JavaScript>\n<!--\n\nvar addbasepath='$addbasepath';\n\n//-->\n</script>\n";
echo '<script language="JavaScript" type="text/javascript" src="js/gpgsubmitpass.js">';
echo "\n</script>\n</HEAD>\n<BODY onload=" . '"';
echo $onload;
echo '">';
echo '<form name=main><input type=hidden name=passphrase value=true></form>';
echo "</BODY>\n</HTML>";
?>
