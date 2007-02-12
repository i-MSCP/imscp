/**
 * decrypt_error.js
 * -----------------
 * Some client-side form-checks. Trivial stuff.
 *
 * $Id: decrypt_error.js,v 1.2 2002/01/31 03:45:53 graf25 Exp $
 *
 * @author Konstantin Riabitsev <icon@duke.edu> ($Author: graf25 $)
 * @version $Date: 2002/01/31 03:45:53 $
 */

function AYS(){
  if (document.forms[0].delete_words.checked && document.forms[0].old_key.value){
    alert (ui_candel);
    return false;
  }
  
  if (!document.forms[0].delete_words.checked && !document.forms[0].old_key.value){
    alert(ui_choice);
    return false;
  }
  if (document.forms[0].delete_words.checked)
    return confirm(ui_willdel);
  return true;
}

