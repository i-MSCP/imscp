// ==========================================================================
//	statusBar.js
//	by Walter Torres <walter@torres.ws>
//	Copyright 1998, 1999, 2000
//	World Wide Rights Reserved
//
//  Licensed under the GNU GPL. For full terms see the file COPYING.
//
//	This script can be used for personal use only.
//	It CAN NOT be distributed without the express
//	written permission of the Auther.
//
//	Please send comments, corrections and suggestions to
//		Walter Torres <walter@torres.ws>
//
// Most curret version can be found at:
//      web.torres.ws/dev
// ==========================================================================

/**
  * statusBar.js
  * Copyright (c) 2002-2005 Walter Torres
  *
  * Licensed under the GNU GPL. For full terms see the file COPYING.
  *
  * Initial Author: Walter Torres
  * $Id$
  *
  * Now that the form has loaded, collect all the FORM Objects
  * and attach our event methods to them
  *
  * Invaluable assistance on this section from...
  *  - Alex
     - Aaron
  *  - Jon Stephens [zontar@mindless.com]
  *  - Andrew Clover <and@doxdesk.com>
  *
  **/


// Walk down all FORMS Objects on this page
for (var i = 0; i < document.forms.length; i++)
{
	// Attach our methods to Objects in each Form
	addForms(document.forms[i]);
}

// This will recursivly walk down a given FORM OBject,
// in case it contians addiational Forms.
function addForms($_objForm)
{
	// Only look at the FORM Object
	if($_objForm.nodeName == "FORM")
	{
		// loop through all FORM Objects
		for (var $i = 0; $i < $_objForm.length; $i++)
		{
			// define current Form Element
			var $_objFormElement = $_objForm.elements[$i];

			// We are looking for INPUT Objects with
			// the TYPE of TEXT and PASSWORD.
			// We are also looking for TEXTAREA Objects
			if ( ( $_objFormElement.nodeName == "TEXTAREA" ) ||
				 ( $_objFormElement.type     == "text" )     ||
				 ( $_objFormElement.type     == "password" )
			   )
			{
				// We only want to do this if the Object is set for it
				if ( $_objFormElement.getAttribute("progress") )
					// attach progress method to this Form Element
					setupProgress ( $_objFormElement );

			}	// if ( textarea...
		}	// for (var $i = 0; $i < $_objForm.length; $i++)
	}	// if($_objForm.nodeName == "FORM")
}	// function addForms($_objForm)


// Build the Progress bar
function setupProgress ( $_objFormElement )
{
	// Insert new Method to element
//	$_objFormElement.setBar = setBar;

	// Define events to capture
	event_addListener($_objFormElement, 'cut', setBar);
	event_addListener($_objFormElement, 'paste', setBar);
	event_addListener($_objFormElement, 'keyup', setBar);
	event_addListener($_objFormElement, 'focus', setBar);

	// Construct and attach Status Bar
	buildBar ( $_objFormElement );

	// Make the default value available
	setBar( $_objFormElement );
}

function buildBar ( $_objFormElement )
{
	// Retrieve name of Object
	var $_strObjectId = $_objFormElement.getAttribute("id");

	// Create new Object and insert it after our Form Object here

	// Create a new OBJECT
	var $_objBar = document.createElement("DIV");
		$_objBar.setAttribute("class", "status_bar");

	// get this Objects parent
	var $_objParent = $_objFormElement.parentNode;

	// Insert at the end of the Child list
	$_objParent.appendChild($_objBar);

	// Width of current Form Object  [thanks Roy Koczela]
	var $_intPixelWidth =  $_objFormElement.offsetWidth;

	// Create Bar Graph for this element
	$_objBar.innerHTML = buildStatusBar ( $_strObjectId, $_intPixelWidth );

	// Build Pointer Object to Status Bar for this Object
	var $_objStatusBar = document.getElementById ('status_' + $_strObjectId );

	// Set width to this Objects status bar
	$_objStatusBar.style.width = $_intPixelWidth - 1 ;

	// Build Pointers to Status Bar parts & attach to Object
	// This saves time late, we will not have to build these pointer again

	// "Progress" field that increases/decreases as charaters are entered
	var $_objStatusField  = document.getElementById ('progress_' + $_strObjectId );
		$_objFormElement.setAttribute("progress", 'progress_' + $_strObjectId );

	// "num_left" field that displays the percentage entered
	var $_objStatusField  = document.getElementById ('num_left_' + $_strObjectId );
		$_objFormElement.setAttribute("num_left", 'num_left_' + $_strObjectId);

	// "num_right" field that displays the percentage remaining
	var $_objStatusField  = document.getElementById ('num_right_' + $_strObjectId );
		$_objFormElement.setAttribute("num_right", 'num_right_' + $_strObjectId);

}


function setBar ( $_objFormElement, $_eventType )
{
	// What is the limit set at for this TEXTAREA
	$_intLimit = $_objFormElement.getAttribute("limit");

	// How many characters do we have yet?
    var $_intCharRemaing = new Number ( $_intLimit - $_objFormElement.value.length );

	// Don't want negative numbers
    $_intCharRemaing = ( $_intCharRemaing <= 0 ) ? 0 : $_intCharRemaing;

	// How much to we have left?
	var $_intPercent = Math.round( ( $_intCharRemaing * 100 ) /$_intLimit );
	var $_intRemain  = 100 - $_intPercent;

	// Set "progress" field
	// This is kept at a 1px minimum so Opera will not collapse SPAN region
    $_objProgress = document.getElementById  ( $_objFormElement.getAttribute("progress") );
	$_objProgress.style.width = ( $_intRemain == 0 ) ? '1px' : $_intRemain + '%';

	// Set "num_left" field
    $_objStatus = document.getElementById  ( $_objFormElement.getAttribute("num_left") );
	$_objStatus.innerHTML = $_intRemain + '%';

}


// Define what the status bar will look like
function buildStatusBar ( $_strObjName, $_intBarWidth )
{
	var strTemp = '';

    strTemp += '<div id="status_' + $_strObjName + '" name="status_' + $_strObjName + '" class="container"  style="width: ' + ($_intBarWidth + 10 ) + 'px;" >';
    strTemp +=    '<div class="right final"   style="width: ' + ($_intBarWidth ) + 'px;" style="width:0%">';
    strTemp +=       '<div id="progress_' + $_strObjName + '" name="progress_' + $_strObjName + '" class="left progress" style="width:0%;">.</div>';
    strTemp +=    '</div>';

    strTemp +=    '	<div id="num_left_' + $_strObjName + '" name="num_left_' + $_strObjName   + '" class="num_left left num" style="width: ' + $_intBarWidth + 'px;">.</div>';
    strTemp += '</div>';

	return strTemp
}




// eof
