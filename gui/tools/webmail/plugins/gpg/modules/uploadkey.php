<?php
/**
 * import_key_file.php
 * ----------------
 * GPG Key Import page
 * Copyright (c) 2002-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @author Brian Peterson
 * @author Joshua Vermette
 *
 * $Id: uploadkey.php,v 1.2 2005/07/27 14:07:49 brian Exp $
 */

//include the gpg system header, so's everything will be in place.
if (!defined ('SM_PATH')){
    if (file_exists('./gpg_functions.php')){
        define ('SM_PATH' , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define ('SM_PATH' , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define ('SM_PATH' , '../');
    } else echo "unable to define SM_PATH in genkey.php, exiting abnormally";
}


require_once(SM_PATH.'plugins/gpg/modules/gpg_module_header.php');
require_once(SM_PATH.'plugins/gpg/gpg_keyring.php');

sqgetGlobalVar('fpr',$fpr);

// ===============================================================
$section_title = _("Upload Key to Keyserver");
echo gpg_section_header ( $section_title, $color[9] );
// ===============================================================




     echo '<FORM METHOD="POST" enctype="multipart/form-data" action="keyring_main.php">'
     . '<input type="hidden" name="pos" value="' . (array_key_exists('pos',$_GET) ? htmlspecialchars($_GET["pos"]) : '') . '">'
     . '<input type="hidden" name="sort" value="' . (array_key_exists('sort',$_GET) ? htmlspecialchars($_GET["sort"]) : '') .'">'
     . '<input type="hidden" name="desc" value="' . (array_key_exists('desc',$_GET) ? htmlspecialchars($_GET["desc"]) : '') . '">'
     . '<input type="hidden" name="srch" value="' . (array_key_exists('srch',$_GET) ? htmlspecialchars($_GET["srch"]) : '') . '">'
     . '<input type="hidden" name="ring" value="' . (array_key_exists('ring',$_GET) ? htmlspecialchars($_GET["ring"]) : '') . '">'
     . '<input type="hidden" name="fpr" value="' .$fpr  . '"><input type=hidden name=keyaction value=uploadKey><input type=hidden name=selectKey value=true>';

      echo _("Choose your Default Keyserver")
      . '<select name=keyserver>';

        $defaultkeyserver = getPref($data_dir,$username,'keyserver');
        if ($defaultkeyserver)
        echo "<option value=$defaultkeyserver>$defaultkeyserver</option>\n";

        for ($i = 0; $i < $GLOBALS['GPG_SYSTEM_OPTIONS']['keyservers']; $i++) {
                $keyserver = $GLOBALS['GPG_SYSTEM_OPTIONS']['keyserver' . $i];
            echo "<option value=$keyserver>$keyserver</option>\n";
        };

    echo '</select>'
      . '<input type=checkbox name=default value=1>'
      . _("Make this my default keyserver")
      . '<hr>';

echo   '<input type="submit" name="uploadkey" value="';
echo _("Upload Key");
echo   '">';

echo   '<input type="submit" name="cancel" value="';
echo _("Cancel");
echo   '">';

echo '</td></tr>';
echo '</form>';
require_once(SM_PATH.'plugins/gpg/modules/gpg_module_footer.php');

echo '</td></tr></table>';

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * c-basic-offset: 4
 * End:
 */

/**
 * $Log: uploadkey.php,v $
 * Revision 1.2  2005/07/27 14:07:49  brian
 * - update copyright to 2005
 *
 * Revision 1.1  2004/03/16 20:30:17  ke
 * -first  commit of userinterface  for uploading a key to a keyserver
 * bug 27
 *
 * Revision 1.14  2004/01/17 00:28:23  ke
 * -E_ALL fixes
 *
 * Revision 1.13  2004/01/09 18:29:28  brian
 * changed SM_PATH defines to use quoted string for E_ALL
 *
 * Revision 1.12  2003/11/04 21:41:01  brian
 * change to use SM_PATH
 *
 * Revision 1.11  2003/11/03 20:18:00  brian
 * minor wording changes in advance of translation.
 * Bug 35
 *
 * Revision 1.10  2003/10/30 20:21:46  ke
 * -changed single to double quotes in internationalized strings
 * bug 35
 *
 * Revision 1.9  2003/10/30 02:17:03  walter
 * - completed localized text by sentences
 * Bug 35
 *
 * Revision 1.8  2003/10/29 00:20:17  walter
 * - localized text by sentences
 * Bug 35
 * -  updated Help structure
 * Bug 79
 *
 * Revision 1.7  2003/10/10 19:07:22  ke
 * -internationalized and added echos to import_key_file instead of <?php ?> broken code
 * bug 35
 *
 * Revision 1.6  2003/08/13 06:49:42  vermette
 * minor fix
 *
 * Revision 1.5  2003/07/17 07:33:07  vermette
 * time-saving modifications to hack around gpg's lack of ability to restrict output size.  Added system keyring into consolidated interface.
 *
 * Revision 1.4  2003/07/11 07:43:12  vermette
 * added search to keyring_main
 *
 * Revision 1.3  2003/07/11 06:54:03  vermette
 * keyring work.  Added chunking, first/prev/next/last, sorting, and ascending v. descending sorted view.  Also modified key table to give more info.
 *
 * Revision 1.2  2003/07/08 18:01:51  vermette
 * rename publicring.php to keyring_main.php
 *
 * Revision 1.1  2003/07/08 17:55:34  vermette
 * new pages for import functionality
 *
 * Revision 1.3  2003/07/01 06:21:46  vermette
 * adding escape routes to options suite.  The previous 'back' link now only appears if requested (new arg to makePage).  This isn't done by any means, but at most it's as broken as it was, so it's an improvement.
 *
 * Revision 1.2  2003/06/13 15:18:01  brian
 * modified to remove $msg parameter to $gpg_format_keylist fn call
 *
 * Revision 1.1  2003/04/11 14:09:10  brian
 * nitial Revision
 * display public keyring with radio 'false'
 * Bug 27
 */
?>
