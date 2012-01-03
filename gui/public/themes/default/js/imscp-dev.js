/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 *
 * @license
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

/**
 * test/validate JavaScript code with JSLint - The JavaScript Verifier
 * see http://www.jslint.com/
 *
 * to remove comments and unnecessary whitespace use jsmin
 * see http://crockford.com/javascript/jsmin
 * try to add a hint/link to the full JS code in the compressed file, sth. like:
 * // see ispcp_full.js for full JS code & license
 *
 * This JavaScript code minimum needs support of JavaScript 1.2.
 *
 * @todo these functions need more doumentation (description/param/return)
 */


/**
 * @todo try to merge this function with function sbmt_details()
 */
function sbmt(form, uaction) {
	form.uaction.value = uaction;
	form.submit();

	return false;
}


/**
 * @todo try to merge this function with function sbmt()
 */
function sbmt_details(form, uaction) {
	form.details.value = uaction;
	form.submit();

	return false;
}


/**
 * @todo remove JS image preloading/swapping und use CSS instead
 */
function MM_preloadImages() {
	var d = document;
	if (d.images) {
		if(!d.MM_p) {
			d.MM_p = [];
		}
		var j = d.MM_p.length, a = MM_preloadImages.arguments;
		for (var i = 0; i < a.length; i++) {
			if (a[i].indexOf("#") !== 0) {
				d.MM_p[j] = new Image();
				d.MM_p[j++].src = a[i];
			}
		}
	}
}


/**
 * @todo remove JS image preloading/swapping und use CSS instead
 */
function MM_swapImgRestore() {
	var x, a = document.MM_sr;
	for (var i = 0; a && i < a.length && (x = a[i]) && x.oSrc; i++) {
		x.src = x.oSrc;
	}
}


/**
 * @usedby MM_swapImage()
 *
 * @todo remove JS image preloading/swapping und use CSS instead
 */
function MM_findObj(n, d) {
	var p, x;
	if (!d) {
		d = document;
	}
	if ((p = n.indexOf("?")) > 0 && parent.frames.length) {
		d = parent.frames[n.substring(p+1)].document;
		n = n.substring(0,p);
	}
	if (!(x = d[n]) && d.all) {
		x = d.all[n];
	}
	for (var i = 0; !x && i < d.forms.length; i++) {
		x = d.forms[i][n];
	}
	if (d.layers) {
		for (i = 0; !x && i < d.layers.length; i++) {
			x = MM_findObj(n, d.layers[i].document);
		}
	}
	if (!x && d.getElementById) {
		x = d.getElementById(n);
	}
	return x;
}


/**
 * @uses MM_findObj()
 *
 * @todo remove JS image preloading/swapping und use CSS instead
 */
function MM_swapImage() {
	var j = 0, x, a = MM_swapImage.arguments;
	document.MM_sr = [];
	for (var i = 0, len = a.length - 2; i < len; i += 3) {
		if ((x = MM_findObj(a[i])) !== null) {
			document.MM_sr[j++] = x;
			if (!x.oSrc) {
				x.oSrc = x.src;
			}
			x.src = a[i+2];
		}
	}
}


/**
 */
function MM_goToURL() {
	var args = MM_goToURL.arguments;
	document.MM_returnValue = false;
	for (var i = 0, len = args.length - 1; i < len; i += 2) {
		window[args[i]].location = String(args[i+1]);
	}
}


/**
 *
 * Javascript sprintf by http://jan.moesen.nu/
 * This code is in the public domain.
 *
 * %% - Returns a percent sign
 * %b - Binary number
 * %c - The character according to the ASCII value
 * %d - Signed decimal number
 * %u - Unsigned decimal number
 * %f - Floating-point number
 * %o - Octal number
 * %s - String
 * %x - Hexadecimal number (lowercase letters)
 * %X - Hexadecimal number (uppercase letters)
 *
 * @todo check use of radix parameter of parseInt for (pType == 'o')
 * @todo check use of radix parameter of parseInt for (pType == 'x')
 * @todo check use of radix parameter of parseInt for (pType == 'X')
 */
function sprintf() {
	if (!arguments || arguments.length < 1 || !RegExp) {
		return;
	}
	var str = arguments[0];
	var re = /([^%]*)%('.|0|\x20)?(-)?(\d+)?(\.\d+)?(%|b|c|d|u|f|o|s|x|X)(.*)/;
	var a = [], b = [], numSubstitutions = 0, numMatches = 0;
	while ((a = re.exec(str))) {
		var leftpart = a[1], pPad = a[2], pJustify = a[3], pMinLength = a[4];
		var pPrecision = a[5], pType = a[6], rightPart = a[7];

		//alert(a + '\n' + [a[0], leftpart, pPad, pJustify, pMinLength, pPrecision);

		numMatches++;
		var subst;
		if (pType == '%') {
			subst = '%';
		} else {
			numSubstitutions++;
			if (numSubstitutions >= arguments.length) {
				alert('Error! Not enough function arguments (' + (arguments.length - 1) + ', excluding the string)\nfor the number of substitution parameters in string (' + numSubstitutions + ' so far).');
			}
			var param = arguments[numSubstitutions];
			var pad = '';
			if 		(pPad && pPad.substr(0,1) == "'") {pad = leftpart.substr(1,1);}
			else if	(pPad) {pad = pPad;}
			var justifyRight = true;
			if (pJustify && pJustify === "-") {justifyRight = false;}
			var minLength = -1;
			if (pMinLength) {minLength = parseInt(pMinLength, 10);}
			var precision = -1;
			if (pPrecision && pType == 'f') {precision = parseInt(pPrecision.substring(1), 10);}
			subst = param;
			if 		(pType == 'b') {subst = parseInt(param, 10).toString(2);}
			else if	(pType == 'c') {subst = String.fromCharCode(parseInt(param, 10));}
			else if	(pType == 'd') {subst = parseInt(param, 10) ? parseInt(param, 10) : 0;}
			else if	(pType == 'u') {subst = Math.abs(param);}
			else if	(pType == 'f') {subst = (precision > -1) ? Math.round(parseFloat(param) * Math.pow(10, precision)) / Math.pow(10, precision): parseFloat(param);}
			else if	(pType == 'o') {subst = parseInt(param).toString(8);}
			else if	(pType == 's') {subst = param;}
			else if	(pType == 'x') {subst = ('' + parseInt(param).toString(16)).toLowerCase();}
			else if	(pType == 'X') {subst = ('' + parseInt(param).toString(16)).toUpperCase();}
		}
		str = leftpart + subst + rightPart;
	}
	return str;
}


/**
 * show tooltip
 *
 * @todo try to merge with hideTip(), eventually with optional parameter
 */
function showTip(id, e) {
	var x, y, tip = document.getElementById(id);
	if (window.event) {
		x = window.event.x + document.body.scrollLeft;
		y = window.event.y + document.body.scrollTop;
	} else {
		x = e.pageX;
		y = e.pageY;
	}
	tip.style.left = (x + 10) + "px";
	tip.style.top = (y + 10) + "px";
	tip.style.display = "block";
}


/**
 * hide tooltip
 *
 * @todo try to merge with showTip(), eventually with optional parameter
 */
function hideTip(id) {
	document.getElementById(id).style.display = "none";
}


/**
 *
 */
function showHideBlocks(id) {
	if (document.getElementById(id).style.display == "none") { // unhide
		document.getElementById(id).style.display = "block";
	} else { // hide
		document.getElementById(id).style.display = "none";
	}
}


/**
 * show/open file tree dialog pageY
 *
 * @return boolean prevent loading of new page on main page
 */
function showFileTree() {
	libwindow = window.open("ftp_choose_dir.php", "FileTreeDialogPage", "menubar=no,width=550,height=400,scrollbars=yes");
	return false; // return false to prevent loading of new main page
}

/*******************************************************************************
*
* Ajax related functions
*
* Note: require JQUERY
*/

/**
* Jquery XMLHttpRequest Error Handling
*/

/**
* Must be documented
*
* Note: Should be used as error callback funct of the jquery ajax request
* @since r2587
*/
function ispCPajxError(xhr, settings, exception) {

	switch (xhr.status) {
		// We receive this status when the session is expired
		case 403:
			window.location = '/index.php';
		break;
		default:
			alert('HTTP ERROR: An Unexpected HTTP Error occurred during the request');
	}
}

/*
 * here are old moved unused/deprecated functions for archive
 * absolutly useless functions got removed completly, search repository
 * archive for more information
 */

/*

// eval is evil
function MM_jumpMenu(targ,selObj,restore) {
	eval(targ + ".location='" + selObj.options[selObj.selectedIndex].value + "'");
	if (restore) {
		selObj.selectedIndex = 0;
	}
}

// copied from reseller/alias_add.tpl + client/alias_add.tpl
var emptyData	= 'Empty data or wrong field!';
var wdname		= 'Wrong domain name!';
var mpointError	= 'Please write mount point!';

function checkForm() {
	var dname = document.forms[0].elements['ndomain_name'].value;
	var dmount = document.forms[0].elements['ndomain_mpoint'].value;
	var dd = new String(dmount);
	if (dname == "" || dmount == "") {
		alert(emptyData);
	} else if (dname.indexOf('.') == -1) {
		alert(wdname);
	} else if (dd.length < 2) {
		alert(mpointError);
	} else {
		document.forms[0].submit();
	}
}

*/
