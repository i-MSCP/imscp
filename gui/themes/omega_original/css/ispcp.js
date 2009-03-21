
function sbmt(form, uaction) {
	form.uaction.value = uaction;
	form.submit();

	return false;
}

function changeColor(aaa, color) {
	aaa.style.backgroundColor = color;
}

function MM_preloadImages() { //v3.0
	var d=document; if(d.images) { if(!d.MM_p) d.MM_p=new Array();
	var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
	if (a[i].indexOf("#")!=0) { d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}

function MM_swapImgRestore() { //v3.0
	var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}

function MM_findObj(n, d) { //v4.01
	var p,i,x; if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
	d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
	if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
	for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
	if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_swapImage() { //v3.0
	var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
	if ((x=MM_findObj(a[i]))!=null) {document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}

function MM_goToURL() { //v3.0
	var i, args = MM_goToURL.arguments;
	document.MM_returnValue = false;
	for (i=0; i<(args.length-1); i+=2)
	Eval(args[i]+".location='"+args[i+1]+"'");
}

function MM_openBrWindow(theURL,winName,features) { //v2.0
	window.open(theURL,winName,features);
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
**/

function sprintf() {
	if (!arguments || arguments.length < 1 || !RegExp) {
		return;
	}
	var str = arguments[0];
	var re = /([^%]*)%('.|0|\x20)?(-)?(\d+)?(\.\d+)?(%|b|c|d|u|f|o|s|x|X)(.*)/;
	var a = b = [], numSubstitutions = 0, numMatches = 0;
	while (a = re.exec(str)) {
		var leftpart = a[1], pPad = a[2], pJustify = a[3], pMinLength = a[4];
		var pPrecision = a[5], pType = a[6], rightPart = a[7];

		//alert(a + '\n' + [a[0], leftpart, pPad, pJustify, pMinLength, pPrecision);

		numMatches++;
		if (pType == '%') {
			subst = '%';
		} else {
			numSubstitutions++;
			if (numSubstitutions >= arguments.length) {
				alert('Error! Not enough function arguments (' + (arguments.length - 1) + ', excluding the string)\nfor the number of substitution parameters in string (' + numSubstitutions + ' so far).');
			}
			var param = arguments[numSubstitutions];
			var pad = '';
			if 		(pPad && pPad.substr(0,1) == "'") pad = leftpart.substr(1,1);
			else if	(pPad) pad = pPad;
			var justifyRight = true;
			if (pJustify && pJustify === "-") justifyRight = false;
			var minLength = -1;
			if (pMinLength) minLength = parseInt(pMinLength);
			var precision = -1;
			if (pPrecision && pType == 'f') precision = parseInt(pPrecision.substring(1));
			var subst = param;
			if 		(pType == 'b') subst = parseInt(param).toString(2);
			else if	(pType == 'c') subst = String.fromCharCode(parseInt(param));
			else if	(pType == 'd') subst = parseInt(param) ? parseInt(param) : 0;
			else if	(pType == 'u') subst = Math.abs(param);
			else if	(pType == 'f') subst = (precision > -1) ? Math.round(parseFloat(param) * Math.pow(10, precision)) / Math.pow(10, precision): parseFloat(param);
			else if	(pType == 'o') subst = parseInt(param).toString(8);
			else if	(pType == 's') subst = param;
			else if	(pType == 'x') subst = ('' + parseInt(param).toString(16)).toLowerCase();
			else if	(pType == 'X') subst = ('' + parseInt(param).toString(16)).toUpperCase();
		}
		str = leftpart + subst + rightPart;
	}
	return str;
}
