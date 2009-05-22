<?php

/**
  * import.tpl
  *
  * Template for the import/add address screen
  * for the Add Address plugin.
  *
  * The following variables are available in this template:
  *
FIXME: sync the $color array with 1.5.2 if it is no longer available(?)
  * array   $color            The SquirrelMail colors array.
  * array   $addresses        A list of the addresses to show on this page,
  *                           each having (unique) 'nickname' and 'email'
  *                           entries as well as (possibly blank) 'label',
  *                           'firstname', 'lastname', and 'valid' entries
  *                           and an optional 'disp_number' entry.
  * array   $backends         A list of the backends the user can add the
  *                           addresses to, keyed by backend number where
  *                           values are the displayable backend name - may
  *                           be an empty list, in which case no backend
  *                           selection should be offered to the user.
  * integer $selected_backend The backend to be preselected, if any (may
  *                           be empty)
  * boolean $javascript_on    Indicates if the user has enabled JavaScript
  *                           functionality.
  * array   $display_errors   A list of error messages to be displayed, if any.
  * string  $error_header_style  Style attribute for use with the error header,
  *                              which will be automatically populated when
  *                              not using SquirrelMail 1.5.2+, where such
  *                              styles are defined in the template set css files.
  * boolean $default_all_checked Indicates if the checkboxes should be
  *                              checked by default or not.
  *
  * Copyright (c) 2008-2009 Paul Lesniewski <paul@squirrelmail.org>,
  * Licensed under the GNU GPL. For full terms see the file COPYING.
  *
  * @package plugins
  * @subpackage add_address
  *
  */


// retrieve the template vars
//
extract($t);
$disp_number = 0;
$row_count = 0;


?>

<table align="center" width="95%" border="0" cellpadding="2" cellspacing="0">
<tr>
  <td bgcolor="<?php echo $color[9]; ?>" align="center"><strong><?php echo _("Add Addresses To Address Book"); ?></strong></td>
</tr>
<tr>
  <td align="left"><hr /></td>
</tr>

<?php if (!empty($display_errors))
      {
         echo '<tr><td class="error_header"' . $error_header_style . '>' . _("ERRORS:") . '</td></tr><tr><td>';
         foreach ($display_errors as $error)
         {
            echo $error . '<br />';
         }
         echo '<br /></td></tr>';
      }
?>

<tr>
  <td>

<form name="aa_form" style="margin:0" method="post" action="">
  <table width="100%" cellspacing="1">
    <tr align="center" bgcolor="<?php echo $color[9]; ?>">
      <td width="10"><?php /* Bah, leaving this out.  echo _("No#"); */ ?></td>
      <td width="10"><?php echo ($javascript_on ? '<a href="#" onclick="f = document.aa_form.elements; for (i = 0; i < f.length; i++) { if (f[i].type == \'checkbox\') { f[i].checked = !f[i].checked; } } return false">' : '') . _("Add") . ($javascript_on ? '</a>' : ''); ?></td>
      <td><?php echo _("Nickname"); ?></td>
      <td><?php echo _("E-mail"); ?></td>
      <td><?php echo _("First Name"); ?></td>
      <td><?php echo _("Last Name"); ?></td>
      <td><?php echo _("Info"); ?></td>
    </tr>
    <?php foreach ($addresses as $count => $address) { $row_count++; ?>
      <tr align="center" <?php if (!$address['valid']) echo ' bgcolor="' . $color[2] . '"'; else if (!($row_count % 2)) echo ' bgcolor="' . $color[0] . '"'; ?>>
        <td width="10" align="center"><label for="add_addr_<?php echo $count; ?>"><?php echo (!empty($address['disp_number']) ? $address['disp_number'] : ++$disp_number); ?></label></td>
        <td width="10" align="center"><input type="checkbox" id="add_addr_<?php echo $count;?>" name="add_addr_<?php echo $count; if ($default_all_checked) echo '" checked="checked'; ?>" /></td>
        <td nowrap="nowrap" style="white-space: nowrap"><input type="text" name="nickname_<?php echo $count; ?>" value="<?php echo $address['nickname']; ?>" /><input type="hidden" name="import_list[]" value="<?php echo $count; ?>" /><input type="hidden" name="disp_number_<?php echo $count; ?>" value="<?php echo (!empty($address['disp_number']) ? $address['disp_number'] : $disp_number); ?>" /><input type="hidden" name="valid_<?php echo $count; ?>" value="<?php echo $address['valid']; ?>" /></td>
        <td nowrap="nowrap" style="white-space: nowrap"><input type="text" name="email_<?php echo $count; ?>" value="<?php echo $address['email']; ?>" /></td>
        <td nowrap="nowrap" style="white-space: nowrap"><input type="text" name="firstname_<?php echo $count; ?>" value="<?php echo $address['firstname']; ?>" /></td>
        <td nowrap="nowrap" style="white-space: nowrap"><input type="text" name="lastname_<?php echo $count; ?>" value="<?php echo $address['lastname']; ?>" /></td>
        <td nowrap="nowrap" style="white-space: nowrap"><input type="text" name="label_<?php echo $count; ?>" value="<?php echo $address['label']; ?>" /></td>
      </tr>
    <?php } ?>
  </table>
  <br />
  <?php if (!empty($backends)) {
      echo '<select name="selected_backend">';
      foreach ($backends as $bnum => $bname)
      {
         echo '<option value="' . $bnum . '"' . ($bnum == $selected_backend ? ' selected="selected"' : '') . '>' . $bname . '</option>';
      }
      echo '</select> &nbsp;';
    }
    ?><input type="submit" name="aa_import_msg" value="<?php echo _("Add & Return To Message"); ?>" />
    &nbsp;<input type="submit" name="aa_import_abook" value="<?php echo _("Add & Proceed To Address Book"); ?>" />
</form>

  </td>
</tr>
</table>

