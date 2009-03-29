/** 
 * ispCP ω (OMEGA) a Virtual Hosting Control System 
 * 
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $id: $
 * @link		http://isp-control.net
 * @author		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or 
 *   modify it under the terms of the GPL General Public License 
 *   as published by the Free Software Foundation; either version 2.0 
 *   of the License, or (at your option) any later version. 
 * 
 *   This program is distributed in the hope that it will be useful, 
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of 
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the 
 *   GPL General Public License for more details. 
 * 
 *   You may have received a copy of the GPL General Public License 
 *   along with this program. 
 * 
 *   An on-line copy of the GPL General Public License can be found 
 *   http://www.fsf.org/licensing/licenses/gpl.txt 
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
 * @link ../admin/custom_menua.tpl
 * @link ../admin/manage_reseller_users.tpl
 * @link ../admin/multilanguage.tpl
 * @link ../admin/ticket_view.tpl
 * @link ../client/mail_edit.tpl
 * @link ../client/protect_it.tpl
 * @link ../client/puser_assign.tpl
 * @link ../client/ticket_view.tpl
 * @link ../reseller/orders_detailst.tpl
 * @link ../reseller/ticket_view.tpl
 *
 * @todo try to merge this function with function sbmt_details()
 */
function sbmt(form, uaction) {
	form.uaction.value = uaction;
	form.submit();

	return false;
}


/**
 * @link ../admin/manage_users.tpl
 * @link ../reseller/users.tpl
 *
 * @todo try to merge this function with function sbmt()
 */
function sbmt_details(form, uaction) {
	form.details.value = uaction;
	form.submit();

	return false;
}


/**
 * @link ../admin|client|reseller/.*
 *
 * @todo remove JS image preloading/swapping und use CSS instead
 */
function MM_preloadImages() {
	var d = document;
	if (d.images) {
		if(!d.MM_p) {
			d.MM_p = [];
		}
		var j = d.MM_p.length, a = MM_preloadImages.arguments;
		for (var i = 0, len = a.length; i < len; i++) {
			if (a[i].indexOf("#") !== 0) {
				d.MM_p[j] = new Image();
				d.MM_p[j++].src = a[i];
			}
		}
	}
}


/**
 * @link ../admin|client|reseller/main_menu_.*
 *
 * @todo remove JS image preloading/swapping und use CSS instead
 */
function MM_swapImgRestore() {
	var x, a = document.MM_sr;
	for (var i = 0, len = a.length; a && i < len && (x = a[i]) && x.oSrc; i++) {
		x.src = x.oSrc;
	}
}


/**
 * @usedby MM_swapImage()
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
	for (var i = 0, len = d.forms.length; !x && i < len; i++) {
		x = d.forms[i][n];
	}
	if (d.layers) {
		for (i = 0, len = d.layers.length; !x && d.layers && i < len; i++) {
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
 * @link ../admin|client|reseller/main_menu_.*
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
 * @link ../admin/domain_details-tpl
 * @link ../admin/domain_edit.tpl
 * @link ../admin/ticket_closed.tpl
 * @link ../admin/ticket_system.tpl
 * @link ../client/alias_edit.tpl
 * @link ../client/cronjobs_add.tpl
 * @link ../client/cronjobs_edit.tpl
 * @link ../client/cronjobs_overview.tpl
 * @link ../client/error_edit.tpl
 * @link ../client/protected_areas.tpl
 * @link ../client/protect_it.tpl
 * @link ../client/puser_assign.tpl
 * @link ../client/puser_edit.tpl
 * @link ../client/puser_gadd.tpl
 * @link ../client/puser_manage.tpl
 * @link ../client/puser_uadd.tpl
 * @link ../client/ticket_closed.tpl
 * @link ../client/ticket_system.tpl
 * @link ../reseller/alias_edit.tpl
 * @link ../reseller/domain_alias.tpl
 * @link ../reseller/domaindetails.tpl
 * @link ../reseller/domain_edit.tpl
 * @link ../reseller/ticket_closed.tpl
 * @link ../reseller/ticket_system.tpl
 * @link ../reseller/user_add4.tpl
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
 * @link ../client/alias_add.tpl
 * @link ../client/ftp_edit.tpl
 * @link ../client/mail_add.tpl
 * @link ../client/mail_edit.tpl
 * @link ../client/subdomain_edit.tpl
 * @link ../reseller/alias_add.tpl
 * @link ../reseller/alias_edit.tpl
 * @link ../reseller/user_add1.tpl
 *
 * @todo try to merge with hideTip(), eventually with optional parameter
 */
function showTip(id, e) {
	var x = (document.all) ? window.event.x + document.body.scrollLeft : e.pageX;
	var y = (document.all) ? window.event.y + document.body.scrollTop  : e.pageY;

	var tip = document.getElementById(id);

	tip.style.left = (x + 10) + "px";
	tip.style.top  = (y + 10) + "px";
	tip.style.display = "block";
}


/**
 * hide tooltip
 *
 * @link ../client/alias_add.tpl
 * @link ../client/ftp_edit.tpl
 * @link ../client/mail_add.tpl
 * @link ../client/mail_edit.tpl
 * @link ../client/subdomain_edit.tpl
 * @link ../reseller/alias_add.tpl
 * @link ../reseller/alias_edit.tpl
 * @link ../reseller/user_add1.tpl
 *
 * @todo try to merge with showTip(), eventually with optional parameter
 */
function hideTip(id) {
	document.getElementById(id).style.display = "none";
}


/**
 * @link ../admin/rootkit_log.tpl
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
 *
 * @link ../client/ftp_add.tpl
 * @link ../client/ftp_edit.tpl
 * @link ../client/protect_it.tpl
 */
function showFileTree() {
	libwindow = window.open("ftp_choose_dir.php", "FileTreeDialogPage", "menubar=no,width=470,height=350,scrollbars=yes");
	return false; // return false to prevent loading of new main page
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
