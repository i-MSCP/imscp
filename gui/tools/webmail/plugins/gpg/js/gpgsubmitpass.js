/**
 * gpgsubmitpass.js
 * GPG plugin passphrase submitting javascript 
 *
 * Copyright (c) 2002-2005 Braverock Ventures
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Compiled from gpgsign_init.js and decrypt_init.mod (all now deprecated)
 * Author: Aaron van Meerten
 * Initial Author: Brian Peterson
 * 
 * $Id: gpgsubmitpass.js,v 1.8 2005/10/09 13:58:52 brian Exp $
 *
 */

function gpg_decrypt_submit () {
  opener.document.decrypt.passphrase.value = document.main.passphrase.value;
  opener.document.decrypt.submit();
  window.close();
}

function gpg_delete_submit() {
  opener.document.keyview.passphrase.value = document.main.passphrase.value;
  opener.document.keyview.submit();
  window.close();
}

function gpg_delete_pair_submit() {
  opener.document.keyview.passphrase.value = document.main.passphrase.value;
  opener.document.keyview.submit();
  window.close();
}


function gpg_placeFocus() {
   if (document.forms.length > 0) {
      var field = document.forms[0];
      for (i = 0; i < field.length; i++) {
         if (field.elements[i].type == "password") {
              document.forms[0].elements[i].focus();
              break;
         }
      }
   }
}

function gpg_pop_submit () {
  opener.document.forms[0].submit();
  self.close();
}
function gpg_sign_submit() {
        opener.document.forms[0].passphrase.value="true";
        opener.document.forms[0].action=addbasepath + "compose.php?send=Send";
        opener.document.forms[0].submit();
        self.close();
}

function gpg_nocache_sign_click(objForm) {
  opener.document.forms[0].passphrase.value=document.main.passphrase.value;
  opener.document.forms[0].gpgsign.value="true";
  opener.document.forms[0].action=addbasepath + "compose.php?send=Send";
  opener.document.forms[0].submit();
  window.close();
}

function gpg_signdraft_submit() {
        opener.document.forms[0].passphrase.value="true";
        opener.document.forms[0].action=addbasepath + "compose.php?draft=Save%20Draft";
        opener.document.forms[0].submit();
        self.close();
}

function gpg_nocache_signdraft_click(objForm) {
  opener.document.forms[0].passphrase.value=document.main.passphrase.value;
  opener.document.forms[0].gpgsign.value="true";
  opener.document.forms[0].action=addbasepath + "compose.php?draft=Save%20Draft";
  opener.document.forms[0].submit();
  window.close();
}


function gpg_encrsign_submit() {
  opener.document.forms[0].passphrase.value = document.main.passphrase.value;
  opener.document.forms[0].submit();
  window.close();
}

gpg_placeFocus();


