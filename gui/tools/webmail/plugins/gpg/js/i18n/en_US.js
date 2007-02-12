// Validation error messages
// take this file and add messages any your language and
// save as xx_XX.js to match your I18N locale value.

// The language and country are from the standards ISO 639 and ISO 3166,
// the two-letter abbreviations for the languages and the countries of the world,
// respectively.
// http://www.w3.org/WAI/ER/IG/ert/iso639.htm
// http://www.iso.ch/iso/en/prods-services/iso3166ma/02iso-3166-code-lists/list-en1.html

// messages for en_US

// Create new array
err_msg = [];


// General error display for window.status
err_msg['status'] = "Please correct errors on this form";

// ALERT box error preamble
err_msg['preamble'] = "Please correct these errors before continuing:"

// Required Field error
err_msg['req'] = "This field is required";


// COMPARE field values
err_msg['compare'] = "Field values are not the same. Please try again.";


// Validation types error messages
err_msg['isEmpty']        = "This field is not empty";
err_msg['isWhitespace']   = "This field does not contain any spaces";
err_msg['alphanumeric']   = "This field may only contain numbers and letters";
err_msg['alpha']          = "This field may only contain letters";
err_msg['numeric']        = "This field may only contain numbers";
err_msg['integer']        = "This field may only contain whole numbers";
err_msg['floatingpoint']  = "This field may only contain decimal numbers";
err_msg['names']          = "This field may only contain letters, hyphens and apostrophes";
err_msg['file']           = "This field may only contain a valid file path";
err_msg['password']       = "";
err_msg['email']          = "This field may only contain an eMail address";
err_msg['ssn']            = "This field may only contain a Social Security Number";
err_msg['usphone']        = "This field may only contain a US telephone number";
err_msg['intphone']       = "";
err_msg['addr']           = "This field may only contain a street address";
err_msg['state']          = "This field may only contain a US Post Office State Code";
err_msg['zipcode']        = "This field may only contain a US Post Office Postal Code";
err_msg['postalcode']     = "This field may only contain a Post Code";
err_msg['caprov']         = "This field may only contain a Canadian Province Code";
err_msg['ccexpire']       = "This field may only contain a 2 digit month and year";
err_msg['ccard']          = "This field may only contain a Credit Card number";


// eof
