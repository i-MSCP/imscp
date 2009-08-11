<?php

/**
 * forms.php - html form functions
 *
 * Functions to build HTML forms in a safe and consistent manner.
 * All name, value attributes are htmlentitied.
 *
 * @link http://www.section508.gov/ Section 508
 * @link http://www.w3.org/WAI/ Web Accessibility Initiative (WAI)
 * @link http://www.w3.org/TR/html4/ W3.org HTML 4.01 form specs
 * @copyright &copy; 2004-2009 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: forms.php 13549 2009-04-15 22:00:49Z jervfors $
 * @package squirrelmail
 * @subpackage forms
 * @since 1.4.3 and 1.5.1
 */

/**
 * Helper function to create form fields, not to be called directly,
 * only by other functions below.
 */
function addInputField($type, $name = null, $value = null, $attributes = '') {
    return '<input type="'.$type.'"'.
        ($name  !== null ? ' name="'.htmlspecialchars($name).'"'   : '').
        ($value !== null ? ' value="'.htmlspecialchars($value).'"' : '').
        $attributes . " />\n";
}

/**
 * Password input field
 */
function addPwField($name , $value = null, $extra_attributes='') {
    return addInputField('password', $name , $value, $extra_attributes);
}


/**
 * Form checkbox
 */
function addCheckBox($name, $checked = false, $value = null, $extra_attributes='') {
    return addInputField('checkbox', $name, $value,
        ($checked ? ' checked="checked"' : '') . $extra_attributes);
}

/**
 * Form radio box
 */
function addRadioBox($name, $checked = false, $value = null) {
    return addInputField('radio', $name, $value,
        ($checked ? ' checked="checked"' : ''));
}

/**
 * A hidden form field.
 */
function addHidden($name, $value) {
    return addInputField('hidden', $name, $value);
}

/**
 * An input textbox.
 */
function addInput($name, $value = '', $size = 0, $maxlength = 0, $extra_attributes='') {

    if ($size) {
        $extra_attributes .= ' size="'.(int)$size.'"';
    }
    if ($maxlength) {
        $extra_attributes .= ' maxlength="'.(int)$maxlength .'"';
    }

    return addInputField('text', $name, $value, $extra_attributes);
}


/**
 * Function to create a selectlist from an array.
 * Usage:
 * name: html name attribute
 * values: array ( key => value )  ->     <option value="key">value</option>
 * default: the key that will be selected
 * usekeys: use the keys of the array as option value or not
 */
function addSelect($name, $values, $default = null, $usekeys = false)
{
    // only one element
    if(count($values) == 1) {
        $k = key($values); $v = array_pop($values);
        return addHidden($name, ($usekeys ? $k:$v)).
            htmlspecialchars($v) . "\n";
    }

    $ret = '<select name="'.htmlspecialchars($name) . "\">\n";
    foreach ($values as $k => $v) {
        if(!$usekeys) $k = $v;
        $ret .= '<option value="' .
            htmlspecialchars( $k ) . '"' .
            (($default == $k) ? ' selected="selected"' : '') .
            '>' . htmlspecialchars($v) ."</option>\n";
    }
    $ret .= "</select>\n";

    return $ret;
}

/**
 * Form submission button
 * Note the switched value/name parameters!
 */
function addSubmit($value, $name = null, $extra_attributes='') {
    return addInputField('submit', $name, $value, $extra_attributes);
}
/**
 * Form reset button, $value = caption
 */
function addReset($value) {
    return addInputField('reset', null, $value);
}

/**
 * Textarea form element.
 */
function addTextArea($name, $text = '', $cols = 40, $rows = 10, $attr = '') {
    return '<textarea name="'.htmlspecialchars($name).'" '.
        'rows="'.(int)$rows .'" cols="'.(int)$cols.'" '.
        $attr . '>'.htmlspecialchars($text) ."</textarea>\n";
}

/**
 * Make a <form> start-tag.
 */
function addForm($action, $method = 'post', $name = '', $enctype = '', $charset = '')
{
    if($name) {
        $name = ' name="'.$name.'"';
    }
    if($enctype) {
        $enctype = ' enctype="'.$enctype.'"';
    }
    if($charset) {
        $charset = ' accept-charset="'.htmlspecialchars($charset).'"';
    }

    return '<form action="'. $action .'" method="'. $method .'"'.
        $enctype . $name . $charset . ">\n";
}

