// ==========================================================================
//	jsValidate.js
//	by Walter Torres <walter@torres.ws>
//	Copyright 1998, 1999, 2000
//	World Wide Rights Reserved
//
//  Licensed under the GNU GPL. For full terms see the file COPYING.
//  OSI Certified Open Source Software
//
//	This script can be used for personal use only.
//	It CAN NOT be distributed without the express
//	written permission of the Author.
//
//	Please send comments, corrections and suggestions to
//		Walter Torres <walter@torres.ws>
//
// Most curret version can be found at:
//      web.torres.ws/dev
// ==========================================================================

/**
 * jsValidate.js
 * Copyright (c) 2002-2003 Walter Torres
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 * OSI Certified Open Source Software
 *
 * Initial Author: Walter Torres
 * $Id: formValidation.js,v 1.9 2003/12/28 20:13:46 brian Exp $
 *
 */


/*
// To use, just add INCLUDE Block and the top of your Form document

<script src='../scripts/jsValidate.js'
        language='JavaScript'
        type='text/javascript'>
       <!-- //  
           //   Self-contained form validation methods
          //    walter@torres.ws     web.torres.ws/dev
         //
        //   If in <head>, this Class will not activate
       //    until the page is completely loaded.
      //
     //   Hide JavaScript Code from Browser.
    //    Do not remove these lines of code.
   //     The code will be 'INCLUDED' at run time.
  //      Create another <SCRIPT> block if you want
 //       to use additional code.
//  -->
</script>


<script language='JavaScript'
        type='text/javascript'>

	/* jsValidateAPI.include(string src, [string path], [string ver]) 
	   The include method allows you to load external library files.
	   The include method is used to load the validation methods and
	   extension field methods. 

	   The src argument is a required parameter which specifies which
	   library you'd like to load. The src parameter should be equal to
	   the base file name of the extension library. For example, to load
	   the validation.js file, you'd pass in the string "validation".

	   The src parameter also takes a special string of "*" which will
	   load all the default libraries stored in the qFormAPI.module array.

	   The path argument is an optional argument which specifies a path other
	   then the default path in which the library file is located. The default
	   path is the value of jsValidate.libPath.

	   The ver argument is an optional parameter which specifies the maximum
	   version of JavaScript the request library has been written for.
	   The jsvalidate API allows you to write multiple versions of the same
	   library to leverage newer versions of JavaScript.
	 */ /*


	// define Library Path 
	jsValidateAPI.setLibraryPath("/lib/");
	// load Libraries
	jsValidateAPI.incLib("validation");
	jsValidateAPI.incLib("functions", null, "12");

</script>


** Sample Form Object
<input type="text"
	   name="name2"
	   id="name2"
	   value="test"
	   required="true"
	   datatype="alpha"
	   title="Name"
	   errorMsg="Only letters are allowed here"
	   />

'title'    - is used for error display messages
'errorMsg' - is used to display error message for that particular object

*/

/**
  * This validation scheme will check...
  * - Text
  * - TextArea
  * - password
  * - file
  * - checkbox
  * - radio
  * - compare mated objects
  *

  * This validation has yet to do...
  * - select
  * - select multiple
  *

  * This validation ignores...
  * - button
  * - reset
  * - submit
  *

  * Current validation types
  *     Method                   DataType
  * - isEmpty()              - required="true"        is this field empty
  * - isWhitespace()         - --                     any White Soace in this field
  * - is_alphanumeric()      - datatype="string"      only letters and numbers
  * - is_alpha               - datatype="alpha"       only letters
  * - is_numeric             - datatype="numeric"     only numbers
  * - is_integer             - datatype="int"         only integers
  * - is_floatingpoint       - datatype="float"       any numeric value
  * - is_file                - datatype="file"        valid file nemas and paths
  * - is_email               - datatype="email"       valid eMail format
  * - is_names               - datatype="names"       no numbers and certain puncuation
  * - is_ssn                 - datatype="ssn"         valid social security number
  * - is_usphone             - datatype="usphone"     US Phone format
  * - is_intphone            - datatype="intphone"    Internatinal Phone Format
  * - is_addr                - datatype="addr"        numbers, letters and certain puncuation
  * - is_state               - datatype="state"       valid 2 chracter State US postal codes
  * - is_zipcode             - datatype="zipcode"     valid 5 or 9 digit US ZIP Code
  * 

  * Needed validation types
  *     Method                   DataType
  * - is_password            - datatype="password"
  * - is_ccard               - datatype="ccars"
  * - is_ccexpire            - datatype="ccexpire"
  * - is_uscurrency          - datatype="uscurrency"
  * - other country provence codes
  * - other country date formates
  * - other country postal codes
  * - other country phone number formats
  * - other country currency
  *

  **/


// Core methods for jsValidate

// Grab the onSubit event!
// Now attach Event Actions to each FORM Object
// Invaluable assitance on this section from...
// - Alex
// - Aaron
// - Jon Stephens [zontar@mindless.com]
window.onload = function()
{
	var colForms = document.forms;

	for (var i = 0; i < colForms.length; i++)
	{
		InitForms(colForms[i]);
	}

	function InitForms(objElement)
	{
		// Only look at the FORM Object
		if(objElement.nodeName.toLowerCase() == "form")
		{
			// Stick an event trigger to our Object
			event_addListener(objElement, 'submit', validateForm)
		}

		// Just incase there are any FORMS enbedded in FORMS
		for(var i = 0; i < objElement.childNodes.length; i++)
			InitForms(objElement.childNodes[i]);
	}

}

// ========================================================================
// ========================================================================

// Create new Validator Object
var validator = new Validate ();

// Debug Boolean
var DEBUG = true;

// Background color change on error flag
var fgSetBG    = true;
var clrBGset   = '#ffcccc'  // Background color for error
var clrBGclear = '#ffffff'  // Background color for no error

// Error message prefix to each item found
strErrPrefix = '   * ';

// Error message sufffix to each item found
strErrSuffix = "\n";


// ========================================================================

// empty Function to use as a container for validation methods
function Validate ()
{
	// Error Collection Array
	this.colError = [];

	// jsValidate's Version info
	this.version = "1.8";

	// External Library path
	this.libPath = "";

	// Default LOCALE value
	this.locale = 'en_US';
}


function validateForm ( objForm, evt )
{
	// Default setting
	var isFormValid = true;

	// Clear error collection
	validator.colError.length = 0;

	// See if any of the Form elements need to be validates,
	// and if so, are they valid
	// Display error messages if anything failed to validate
	if ( ! (validator.checkFormValid ( objForm ) ) )
	{
		// Display errors
		validator.setErrDisplay ( validator.colError );

		// Stop the form from being sent to Server
		if(evt.preventDefault)
		{
			// For Opera 5+, Mozilla & Konquerer
			evt.preventDefault();
			evt.stopPropagation();
		}
		// For IE 5+
		else
			evt.cancelBubble = true;

		// For everybody who can use it
		isFormValid = false;
	}	// ! (validator.checkFormValid ( objForm ) )

//alert ( 'validateForm - out:' + isFormValid )

	return isFormValid;

}

// *********************************************************
// Master Validation Check
Validate.prototype.checkFormValid = function ( objForm, objDocument )
{
	// Default setting
	var isWholeFormValid = true;

	// Create collection of FORM Elements
	var arryInput = objForm.getElementsByTagName('input');

	// Loop through all the Objects in the given Form
	for (var i = 0; i < arryInput.length; i++)
	{
//alert ( i + "\n" + arryInput[i].id );

		// Get the current FORM Element
		objElement = arryInput[i];

		// What TYPE is this Element
		strEleType = objElement.getAttribute("type").toLowerCase()

		// Opera and Konqueror, for some reason, attach to these,
		// short circuit them here!
		if ( ( strEleType == "reset" ) || ( strEleType == "submit" ) )
			continue;

		// Clear any error messages for this Object
		this.clearErrDisplay ( objElement )

		// See if this FORM Element is REQUIREd...
		// TODO: Need to make handle DISABLED and/or READONLY,
		if ( this.isRequired ( objElement ) )
		{
			switch ( objElement.getAttribute("type").toLowerCase() )
			{
				case "text":
				case "file":
				case "password":
				case "textarea":

					// We only need to be concerned if this Object has any data at all.
					if ( ! this.hasData ( objElement ) )
					{
						// Define error message for this Object
						this.setErrorCollection ( objElement, err_msg['req'], true )
			
						// Set our faliure flag
						isWholeFormValid = false;
					}
				break;
								
				// handle the checkboxes
				case "checkbox":
					// Determine if Object value is valid
					if (this.isCheckboxSelected ( objElement ) )
					{
						// Define error message for this Object
						this.setErrorCollection ( objElement, err_msg['req'], true )
			
						// Set our faliure flag
						isWholeFormValid = false;
					}
				break;
			
				// handle the radio buttons
				case "radio":
					// Determine if Object value is valid
					//var objFormField = document.forms[0].elements[strFieldName]
					
					// Retrieve radio collection
					familyName = objElement.getAttribute("name");
			
					objRadioFamily = objForm.elements[familyName];
					oldFamily = familyName;
			
					if (this.isRadioGroupSelected ( objRadioFamily ) )
					{
						// Define error message for this Object
						this.setErrorCollection ( objElement, err_msg['req'], true )
			
						// Set our faliure flag
						isWholeFormValid = false;
					}
				break;
			
				// handle Select lists
				case "select":
					// Later addition
				break;
			
//				default:
//					continue;

			}	// switch ( objElement.getAttribute("type").toLowerCase() )
		}	// if ( this.isRequired ( objElement ) )


		// Is this Field to be validated?
		if ( this.isDefined ( objElement, 'datatype' )  )
		{
//alert ( 'isDefined' + "\n" + objElement.id );
			// NOTE: Assumption here...
			//       Only TEXT type elements will have datatypes defined
			if ( ! this.isTextElementValid ( objElement ) )
				isWholeFormValid = false;
		}	// if ( this.isDefined ( objElement, 'datatype' )  )

		// Is this field to be compared to another?
		if ( this.isCompare  ( objElement ) )
		{
//alert ( 'isCompare' + "\n" + objElement.id );
			if ( ! this.isPairSame ( objElement ) )
				isWholeFormValid = false;
		}	// if ( this.isCompare  ( objElement ) )

	}	// for (var i = 0; i < arryInput.length; i++)
	
	return isWholeFormValid;

}	// Validate.prototype.checkFormValid


// ==================================================================
// ==================================================================
// Low level attribute methods

// A generic definition to see if a given object has a given property
// Cross Borwser!!!!!!
Validate.prototype.isDefined = function ( objElement, strAttribute )
{
	return ( ( objElement.getAttribute(strAttribute) != '' ) &&   // Opera value
	         ( objElement.getAttribute(strAttribute) != null ));  // IE & Moz/NN value
}

// Decide if the current Form Element is to be COMPAREd with another
// This is a bit backwards. We are only checking for the existance
// of this attribure, not its value, we will check that later.
Validate.prototype.isCompare = function ( objElement )
{
	return ( this.isDefined (objElement, "compare") )
}

// Decide if this the current Form Object is REQUIRED
// This is a bit backwards. We are only checking for the existance
// of this attribure, not its value, as it has no value.
// The existance of the REQUIRED attribute determines its value.
// If REQUIRED in 'undefined' then the Object is NOT required, thus
// REQUIRED = FALSE
Validate.prototype.isRequired = function ( objElement )
{
	return ( objElement.getAttribute("required") == 'true' );
}

// Decide if this the current Form Object is to be IGNOREd
// This is a bit backwards. We are only checking for the existance
// of this attribure, not its value, as it has no value.
// The existance of the IGNORE attribute determines its value.
// If IGNORE in 'undefined' then the Object is NOT required, thus
// IGNORE = FALSE
Validate.prototype.isIgnore = function ( objElement )
{
	return ( objElement.getAttribute("ignore") == 'true' );
}

// Decide if this the current Form Object is to be DISABLEd
// This is a bit backwards. We are only checking for the existance
// of this attribure, not its value, as it has no value.
// The existance of the DISABLE attribute determines its value.
// If DISABLE in 'undefined' then the Object is NOT required, thus
// DISABLE = FALSE
Validate.prototype.isDisabled = function ( objElement )
{
	return ( objElement.getAttribute("disabled") );
}

// Decide if this current Form Object has any data
Validate.prototype.hasData = function ( objElement )
{
	return ( ! this.isEmpty ( objElement.value ) );
}

// ==================================================================
// ==================================================================
// Base FORM Element Type Validations

// Check the input value (if any) of all Text Type Objects
Validate.prototype.isTextElementValid = function ( objElement )
{
	// Default setting
	var isValid = true;

	// See if we have anything to validate.
	// If not, just return TRUE.
	// This is just to validate any data given, not to determine if data
	// is required
	if ( ! this.isEmpty ( objElement.value ) )
		// Make sure the data that was entered is OK
		isValid = this.isElementDataValid ( objElement )

	return isValid
}

// Check if field values are the same
Validate.prototype.isPairSame = function ( objElement )
{
	// Default value
	var isValid = true;

	// Retrieve Fields 'mate' to compare against
	var strMate = objElement.getAttribute("compare");
		objMate = document.getElementById( strMate );

	// Compare their values
    if ( (objElement.value != objMate.value ))
	{
		// If this pair is a PASSWORD, wipe both values
		if ( objElement.getAttribute("type").toLowerCase() == 'password' )
		{
			// Wipe contents
			objElement.value = '';
			objMate.value = '';

			// Fire onChange event
			// onChange will *only* fire when the Object has lost focus!
			// NOTE: It seems that everyone else beside IE fires onChange
			//       when the Object is *in* focus.
			event_dispatch(objElement, 'change');
			event_dispatch(objMate, 'change');
		}

	    isValid = false;
	}

	if ( ! isValid )
		this.setErrorCollection ( objElement, err_msg['compare'], true );

	// Just see if it is checked!
	return isValid;
}





// *********************************************************

// Set LOCALE property for messages
// Must be I18N defined
// The language and country are from the standards ISO 3166 and ISO 639,
// the two-letter abbreviations for the countries and the languages of the world,
// respectively.
// http://www.iso.ch/iso/en/prods-services/iso3166ma/02iso-3166-code-lists/list-en1.html
// http://www.w3.org/WAI/ER/IG/ert/iso639.htm
Validate.prototype.setLocale = function ( strLocale )
{
	// code definition...
	// [country]_[language]
	// Default LOCALE value
	this.locale = strLocale;
}

Validate.prototype.getLocale = function ( )
{
	return this.locale;
}

// Locale internationalized error messages
includeFile ( '../js/i18n/' + validator.getLocale() + '.js' );

// Generic INCLUDE statement that works across all the browsers!
// Believe it or not!
function includeFile ( strFilePath )
{
  var strIncludeCode  = '<script src="' + strFilePath + '"';
      strIncludeCode += '  language="JavaScript"';
      strIncludeCode += '  type="text/javascript">';
      strIncludeCode += '<' + '/script>';

    document.write ( strIncludeCode );
}

// Include TLD data set
includeFile ( '../js/IANA_TLD.js' );

// validation method for jsValidate

// ==================================================================
// ==================================================================
// Master Control Method for all Validation types
// Validate input data based upon Object Data Type
Validate.prototype.isElementDataValid = function ( objElement )
{
	// Default setting
	var isValid = false;

	// Take the datatype property of the Form Object and contantinate
	// a function assignment to a variable via EVAL!
	// Takes 30 lines of code into 1!
	// Ex: datatype='ssn', this will call function is_ssn and return
	//                     a boolean of validity
	var strType = objElement.getAttribute("datatype");

	// Create Object reference to Validation Type
	objValidType = eval ( 'this.is_' + strType )

	// If datatype exists, use it
	if ( typeof (objValidType) != 'undefined' )
		isValid = objValidType ( objElement.value );

	// Define error message for this Object, if we need to
	if ( ! isValid )
		this.setErrorCollection ( objElement, err_msg[strType], true )


	return isValid
}


// *********************************************************
// String Validation


// The isEmpty and isWhitespace functions were taken straight
// from Netscape's JavaScript development site...
//    http://developer.netscape.com.

// Check whether string is empty.
Validate.prototype.isEmpty = function ( strValue )
{
	return ( ( strValue == null ) || ( strValue.length == 0 ) )
}

// See if this contains only White Space
	// whitespace characters
	var whitespace = " \t\n\r";

Validate.prototype.isWhitespace = function ( strValue )
{
     // Is this empty?
     if ( isEmpty ( strValue ) )
	 	return true;

     // Search through string's characters one by one
     // until we find a non-whitespace character.
     // When we do, return false; if we don't, return true.

     for ( var i = 0; i < strValue.length; i++)
     {
          // Check that current character isn't whitespace.
          var strChar = strValue.charAt(i);

          if ( whitespace.indexOf( strChar ) == -1 ) return false;
     }

     // All characters are whitespace.
     return true;
}

// Check for Alpha Numeric characters [a-z][0-9],
// no puncuation or spaces
Validate.prototype.is_alphanumeric = function ( strValue )
{
	// define Regular Expression for allowed Characters
	// See if the passed in String has any invalid Characters
	// this returns a Boolean
	return ( /[a-z|\d]/.test ( strValue ) );
}

// Check for Alpha characters [a-z],
// no puncuation or spaces
Validate.prototype.is_alpha = function ( strValue )
{
	// Default setting
	var isValid = false;

	// define Regular Expression for allowed Characters
	var reInvalidChar = /[^a-z]/i;

	// See if we have want we want
	isValid = reInvalidChar.test ( strValue );

	// See if the passed in String has any invalid Characters
	// this returns a Boolean
	return ( ! reInvalidChar.test ( strValue ) );
}


// *********************************************************
// Number Validation

// Check for Positive or negative numbers with or w/o decimal,
// no puncuation or spaces
Validate.prototype.is_numeric = function ( strValue )
{
	// See if the passed in String has any invalid Characters
	// this returns a Boolean
	return ( ! isNaN( strValue ) );
}

// Check for Integers Numbers,
Validate.prototype.is_integer = function ( strValue )
{
	// Only numbers *without* decimals
	// this returns a Boolean
	return ( ( this.is_numeric ( objElement.value ) ) && ( strValue.indexOf(".") == -1 ) );
}

// Check for Floating Point Numbers,
Validate.prototype.is_floatingpoint = function ( strValue )
{
	// Only numbers *with* decimals
	// this returns a Boolean
	return ( ( this.is_numeric ( objElement.value ) ) && ( ! this.is_integer ( objElement.value ) ) );
}


// *********************************************************
// Special Validation

// Check for Alpha characters [a-z], with spaces, commas,
// apostrophe and dashes, no other puncuation
Validate.prototype.is_names = function ( strValue )
{
	var rePattern = /[^a-z\x80-\xFF .',-]/i;

	// See if the passed in String has any invalid Characters
	// this returns a Boolean
	return ( strValue.search ( rePattern ) == -1 ) ? true : false;
}

// Check for illegal File Path Characters
Validate.prototype.is_file = function ( strValue )
{
	// define Regular Expression for allowed Characters
	var reInvalidChar = /[<>"\/\*\?\|]/i;

	// See if the passed in String has any invalid Characters
	// this returns a Boolean
	return ! ( reInvalidChar.test ( strValue ) );
}

// Check for valid Password
Validate.prototype.is_password = function ( strValue )
{
	// Call isAlphaNumericOnly
	var isValid = this.isAlphaNumericOnly ( strValue )

	if ( isValid )
		// See if password is within length range
		isValid = ( ( strValue.length >= 8 ) && ( strValue.length <= 16 ) )
	
	// this returns a Boolean
	return isValid
}

// Check for valid eMail Address
Validate.prototype.is_email = function ( strValue )
{
	/* NOTICE: This method is will opearte a bit different than my
	           standard methods. The method will have *many* exit points.
			   Once a test has failed, the method is exited without any
			   further testing.
	 */

	// See if we have anything.
	// NOTE: This is *not* a check for content, that is to be done elsewhere
	if ( strValue.length == 0)
		return true;


	// Default return value
	var retValue = true;

	// Default Error message
//	var strErrMsg = '';

	// =========================================================
	/* The following pattern is used to check if the entered e-mail address
	   fits the user@domain format.  It also is used to separate the username
	   from the domain.
	 */
	var reEmail=/^(.+)@(.+)$/;

	/* Begin with the coarse pattern to simply break up user@domain into
	   different pieces that are easy to analyze. */
	var objFoundArray = strValue.match(reEmail);
	
	if (objFoundArray == null)
	{
		strErrMsg = "Email address is an incorrect format.";
		return false;
	}

	var user = objFoundArray[1];
	var domain = objFoundArray[2];


	// =========================================================
	/* This pattern is ALLOWED characters in the left side or name part
	   of the address. From a sedurity POV, checkeing for *allowed* is
	   safer than checking for *not allowed*
	 */
	var reAllowed = /[^0-9a-z\x80-\xFF._+-=\^~]/i;

	// See if the passed in String has any invalid Characters
	if ( user.search ( reAllowed ) > -1 )
	{
		strErrMsg = "Email address contains illegal characters";
		return false;
	}


	// =========================================================
	/* This pattern is to validate that the email address, if an
	    IP is a valid format of an IP.
		This does not check to see if the IP is real or not.
	 */
	var reIP = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;

	var objFoundArray = domain.match( reIP );

	/* Now this gets a bit tricky here. We are simply seeing if the
	   DOMAIN section is an IP, and to do that we are spliting the string
	   into array elements, if it matches the IP pattern above.
	   The touchy part here is, if this split failes, it does *not* mean
	   the DOMAIN section is invalid. It only means it is not in a valid
	   IP format.
	 */
	if (objFoundArray != null)
	{
		// Walk down each element and make sure it is within allowed IP range
		for ( var i = 1; i < objFoundArray.length; i++ )
		{
			if ( ( objFoundArray[i] == 0 ) || ( objFoundArray[i] >= 255 ) )
			{
				strErrMsg = "Email IP address is invalid";
				return false;
			}
		}
		return retValue
	}

	// =========================================================
	/* DOMAIN should be a alias name.
	   We need to split the DOMAIN name from the TLD.
	 */
	var objFoundArray = domain.split(".");

	if ( objFoundArray.length == 1 )
	{
		strErrMsg = "Email IP address is missing PERIOD.";
		return false;
	}

	// First thing to check is the TLD, Top Level Domain
	if ( ! TLDsearch ( objFoundArray[objFoundArray.length-1] ) )
	{
		strErrMsg = "Top Level Domain is invalid.";
		return false;
	}

	// Last thing to check!
	// checking for valid doamin name characters
	var reAllowed = /[^0-9a-z._-]/i;

	// See if the passed in String has any invalid Characters
	if ( domain.search ( reAllowed ) > -1 )
	{
		strErrMsg = "Email Domain address contains illegal characters";
		return false;
	}

	return retValue

}

// Check for Social Security Number,
Validate.prototype.is_ssn = function ( strValue )
{
	// define Regular Expression for Phone Number
	var reValidChar = /^(\d{3}[ |-]?)(\d{2})[ |-]?\d{4}$/i;
 //               /^(\d{9}|\d{3}-\d{2}-\d{4})$/;

	// See if the passed in String has any invalid Characters
	// this returns a Boolean
	return ( reValidChar.test ( strValue ) );
}

// Check for US Phone Numbers,
Validate.prototype.is_usphone = function ( strValue )
{
	// define Regular Expression for Phone Number
	// Pattern matches (999)-999-9999, (999) 999-9999, (999)999-9999, etc.
	var reValidChar = /^((\((\d{3})\)|(\d{3}))[- ]?)?(\d{3})[- ]?(\d{4})$/;
	//                /(\(?\d{3}\)?)?(\-| )?\d{3}(\-| )?\d{4}/


	// See if the passed in String has any invalid Characters
	// this returns a Boolean
	return ( reValidChar.test ( strValue ) );
}

// Check for Phone Numbers,
Validate.prototype.is_intphone = function ( strValue )
{
	// define Regular Expression for Phone Number
	var ValidChar = /^((\+)?(\d{1,3}))?([\(| |-|.])?(\d{1,5})/i;

	// See if the passed in String has any invalid Characters
	// this returns a Boolean
	return ( ValidChar.test ( strValue ) );
}

// isStreetAddr
// Check for Alpha Numeric characters [a-z][0-9],
// with spaces, commas, and dashes, no other puncuation
Validate.prototype.is_addr = function ( strValue )
{
	// define Regular Expression for allowed Characters
	var reInvalidChar = /[~`!$%^*_+=|?]/i;  //[^a-z\d -,\.]

	// See if the passed in String has any invalid Characters
	// this returns a Boolean
	return ! ( reInvalidChar.test ( strValue ) );
}


// Check for valid State Code (US),
Validate.prototype.is_state = function ( strValue )
{
	// See if we have what we need to process
        if ( ( strValue.charAt(0) == ' ') ||
			 ( strValue.charAt(1) == ' ') ||
			 ( strValue.length    != 2  )  )
		{
			return false;
		}

	// Define what the State Codes are
	var strStates = "AK AL AR AZ CA CO CT DC DE FL GA HI IA ID IL IN KS KY LA MA MD ME MI MN MO MS MT NC ND NE NH NJ NM NV NY OH OK OR PA PR RI SC SD TN TX UT VA VI VT WA WI WV WY";

	// Now see if what we got is what we need
	// and return the Boolean result
	return (strStates.indexOf ( strValue.toUpperCase() ) != -1);
}


// Check for valid ZIP Code (US),
Validate.prototype.is_zipcode = function ( strValue )
{
	// define Regular Expression for allowed Floating Point Numbers
	var ValidChar = /^(\d{5})(-\d{4})?$/i;

	// See if the passed in String has any invalid Characters
	// this returns a Boolean
	return ( ValidChar.test ( strValue ) );
}


// Check for valid Postal Code (UK, Canada),
Validate.prototype.is_postalcodeX = function ( strValue )
{
	// define Regular Expression for Postal Code
	var ValidChar = /^([a-z][a-z]?\d\d? ?\d[a-z][a-z])?$/i;

	// See if the passed in String has any invalid Characters
	// this returns a Boolean
	return ( ValidChar.test ( strValue ) );
}

// Check for valid Canadian Province Codes,
Validate.prototype.caprov = function ( strValue )
{
	// See if we have what we need to process
        if ( ( strValue.charAt(0) == ' ') ||
			 ( strValue.charAt(1) == ' ') ||
			 ( strValue.length    != 2  )  )
		{
			return false;
		}

	// Define what the Province Codes are
	var strProvinces = "AB BC MB NB NF NS ON PE PQ SK YT";

	// Now see if what we got is what we need
	// and return the Boolean result
	return (strProvinces.indexOf ( strValue.toUpperCase() ) != -1);
}


// *********************************************************
// Credit Card Validation

// Check for valid Expiration Date
Validate.prototype.is_ccexpire = function ( expMonth, expYear )
{

// page 352
}

// Check for valid Creit Card Number
Validate.prototype.is_ccard = function ( strCCnum )
{

// page 353
}

// Error collection and display for jsValidate

// This only handles ALERT display for now.
// Hope to expand to inline errors soon

// ==================================================================
// Create Error Collection
Validate.prototype.setErrorCollection = function ( objElement, strMethodErrorMsg, bolErrOverride )
{

//alert ( 'setErrorCollection' + "\n" + objElement.id );

	if ( bolErrOverride )
		strErr = strMethodErrorMsg;

	else
		// Get local error message, if it has one
		strErr = ( this.isDefined ( objElement, 'errorMsg' ) ) ? objElement.getAttribute("errorMsg" ) : '';

	// Add this Object to error Collection
	this.colError[this.colError.length] = [ objElement, objElement.getAttribute("title"), strErr ];
}


// ==================================================================
// This will decide how to display any errors
Validate.prototype.clearErrDisplay = function ( objElement )
{
	// Clear background color, if desired via flag
	if ( fgSetBG )
	{
		objElement.style.backgroundColor = clrBGclear;
	}

}


// This will decide how to display any errors
Validate.prototype.setErrDisplay = function ( arrErrList )
{
		// Inform the user of a problem in the status bar
		window.status = err_msg['status'];

		// Define our error message
		var strErr = err_msg['preamble'] + "\n";

		// Loop through Error Collection and build message
		for (var i = 0; i < arrErrList.length; i++)
		{
			strErr += strErrPrefix;
			strErr += arrErrList[i][1];

			if ( arrErrList[i][2] )
				strErr += ' - ' + arrErrList[i][2];

			strErr += strErrSuffix;

			// Set background color, if desired via flag
			if ( fgSetBG )
				arrErrList[i][0].style.backgroundColor = clrBGset;
		}

		// Set focus to first problem Object
		arrErrList[0][0].focus();
		//arrErrList[0][0].select();

		// Display Error message
		alert ( strErr );
}



// ********************************************************************
// ********************************************************************

/**
  * $Log: formValidation.js,v $
  * Revision 1.9  2003/12/28 20:13:46  brian
  * added mising . to includefile for TLD info
  *
  * Revision 1.8  2003/12/27 07:32:47  walter
  * - fixed path issue
  *
  * Revision 1.7  2003/12/27 07:29:28  walter
  * - revamp EMAIL validation
  * BUG 134
  *
  *
  * Revision 1.09a  2003/12/27 01:27:11  walter
  * - revamp EMAIL validation
  *
  * Revision 1.09  2003/12/17 20:16:48  walter
  * - revamp process to correct Opera incompatibilities
  * - revamp NAMES method to handle extended characters
  *
  * Revision 1.08  2003/12/02 13:50:31  walter
  * - added internationalization code to read I18N files
  * - made REQUIRE an independant process. FORM Elements are now
  *   able to be REQUIREd but do not need to be validated, and visversa.
  * - change main event loop to CASE statements
  * - corrected case issuses with Konqueror
  * - Mozilla did not like 'obj.getAttribute("value")'
  *   converted to 'obj.value'. Other browsers accept this as well.
  *
  * Revision 1.07  2003/11/23 19:38:04  walter
  * - added code to check if a FORM of a Form Object was being returned
  *   as the Object that fired the SUMBIT event.
  * - commented out the onLoad event bind, Opera does not acknowledge it.
  *
  * Revision 1.06  2003/11/20 21:14:12  walter
  * - added 'is_names' to data types. This will accept SPACE, DASHES,
  *   PERIODS and HYPHENS in peoples names
  *
  * Revision 1.05  2003/11/19 22:10:58  walter
  * - added new event listener to make class load ONLY after Form Page has
  *   completly loaded, no matter where this library is INCLUDED in calling file
  * - changed the way paired compares work. No longer looks for same NAME attribute,
  *   [this breaks server-sdie validation schemes] now use compare="object id'
  *   attribute instead.
  *
  * Revision 1.04  2003/11/18 07:04:58  walter
  * - attempt at radio button validation, put it off for later
  * - add mated Form Object validation [compare 2 fields i.e. passwords]
  *   based upon same NAME Form Objects
  * - updated error reporting to handle multiple attempts
  *
  * Revision 1.03  2003/10/28 21:41:06  walter
  * - disabled inline error reporting (update to Xbroswer later)
  * - added alert display error reporting
  * - added Form Object title attribute use for error report
  * - added Form Object error messag attribute for error reports
  * - added CHECKBOX validation
  * - incorporated Andrew Clovers wonderful Event library to make this Class
  *   more cross browser, more encapsulated.
  * - onSubmit event binding, for either FORM or SUBMIT Object is not longer
  *   required. onSubmit is auto-captured once the Form has completly loaded.
  *   This gives us yet another level of abstrction and encapsulization.
  *
  * Revision 1.02  2002/10/28 13:14:20  walter
  * - add check for disabled
  * - updated IGNORE to handle 'true' not just exists
  * - inline error display - IE only :(
  *
  * Revision 1.01  2002/06/12 09:22:32  walter
  * - initial creation of superstructure and outline
  *
  **/
  
 // alert ( err_msg['req'] );

// eof
