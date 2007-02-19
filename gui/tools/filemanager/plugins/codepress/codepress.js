/*
 * CodePress - Real Time Syntax Highlighting Editor written in JavaScript - http://codepress.fermads.net/
 * 
 * Copyright (C) 2006 Fernando M.A.d.S. <fermads@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the 
 * GNU Lesser General Public License as published by the Free Software Foundation.
 * 
 * Read the full licence: http://www.opensource.org/licenses/lgpl-license.php
 */

CodePress = {
	range : null,
	language : null,
	scrolling : false,
		
	// set initial vars and start sh
	initialize : function() {
		this.detect();
		chars = '|13|32|191|57|48|187|188|'; // charcodes that trigger syntax highlighting
		cc = '&shy;'; // control char
		if(browser.ff) {
			editor = document.getElementById('ffedt');
			document.designMode = 'on';
			document.addEventListener('keydown', this.keyHandler, true);
			window.addEventListener('scroll', function() { if(!CodePress.scrolling) CodePress.syntaxHighlight('scroll'); }, false);
			//document.body.focus();
		}
		else if(browser.ie) {
			editor = document.getElementById('ieedt');
			editor.contentEditable = 'true';
			document.onkeydown = this.keyHandler;
			//window.onscroll = function() { if(!CodePress.scrolling) CodePress.syntaxHighlight('scroll'); }
		}
		else {
			// TODO: textarea without syntax highlighting for non supported browsers
			alert('your browser is not supported at the moment');
			return;
		}
		
		this.syntaxHighlight('init');
		setTimeout(function() { window.scroll(0,0) },50);
	},

	// detect browser, for now IE and FF
	detect : function() {
		browser = { ie:false, ff:false };
		if(navigator.appName.indexOf("Microsoft") != -1) browser.ie = true;
		else if (navigator.appName == "Netscape") browser.ff = true;
	},

	// treat key bindings
	keyHandler : function(evt) {
		evt = (evt) ? evt : (window.event) ? event : null;
	  	if(evt) {
	    	charCode = (evt.charCode) ? evt.charCode : ((evt.keyCode) ? evt.keyCode : ((evt.which) ? evt.which : 0));
		    if((chars.indexOf('|'+charCode+'|')!=-1) && (!evt.ctrlKey && !evt.altKey)) { // syntax highlighting
			 	CodePress.syntaxHighlight('generic');
			}
			else if(charCode==46||charCode==8) { // save to history when delete or backspace pressed
			 	CodePress.actions.history[CodePress.actions.next()] = editor.innerHTML;
			}
			else if((charCode==90||charCode==89) && evt.ctrlKey) { // undo and redo
				(charCode==89||evt.shiftKey) ? CodePress.actions.redo() : CodePress.actions.undo() ;
				evt.returnValue = false;
				if(browser.ff)evt.preventDefault();
			}
			else if(charCode==86 && evt.ctrlKey)  { // paste
				// TODO: pasted text should be parsed and highlighted
			}
		}
	},

	// put cursor back to its original position after every parsing
	findString : function() {
		if(browser.ff) {
			if(self.find(cc))
				window.getSelection().getRangeAt(0).deleteContents();
		}
		else if(browser.ie) {
		    range = self.document.body.createTextRange();
			if(range.findText(cc)){
				range.select();
				range.text = '';
			}
		}
	},
	
	// split big files, highlighting parts of it
	split : function(code,flag) {
		if(flag=='scroll') { 
			this.scrolling = true; 
			return code;
		}
		else {			
			this.scrolling = false;
			mid = code.indexOf("&amp;shy;"); 
			if(mid-2000<0) {ini=0;end=4000;}
			else if(mid+2000>code.length) {ini=code.length-4000;end=code.length;}
			else {ini=mid-2000;end=mid+2000;}
			return code.substring(ini,end);
		}
	},
	
	// syntax highlighting parser
	syntaxHighlight : function(flag) {
		if(browser.ff) {
			if(flag!='init') window.getSelection().getRangeAt(0).insertNode(document.createTextNode(cc));
			o = editor.innerHTML;
			o = o.replace(/<br>/g,'\n');
			o = o.replace(/<.*?>|<\/.*?>/g,''); 	
			x = z = this.split(o,flag);
			x = x.replace(/\n/g,'<br>');			

			for(i=0;i<languages[this.language].length;i++) 
				x = x.replace(languages[this.language][i],languages[this.language][i+1]);

			editor.innerHTML = this.actions.history[this.actions.next()] = (flag=='scroll') ? x : o.replace(z,x);
		}
		else if(browser.ie) {
			if(flag!='init') document.selection.createRange().text = cc;
			x = editor.innerHTML;
			x = x.replace(/<P>/g,'\n');
			x = x.replace(/<\/P>/g,'\r');
			x = x.replace(/<\/?.*?>/g,'');
			x = '<P>'+x;
			x = x.replace(/\n/g,'<P>');
			x = x.replace(/\r/g,'<\/P>');
			x = x.replace(/(<P>)+/,'<P>');
			x = x.replace(/<P><\/P>/g,'<P>&nbsp;<\/P>');			

			for(i=0;i<languages[this.language].length;i++) 
				x = x.replace(languages[this.language][i],languages[this.language][i+1]);
				
			editor.innerHTML = this.actions.history[this.actions.next()] = '<pre>'+x+'</pre>' ;
		}
		if(flag!='init') this.findString();
	},

	// undo and redo methods
	actions : {
		pos : -1, // actual history position
		history : [], // history vector
		
		undo : function() {
			if(editor.innerHTML.indexOf(cc)==-1){
				if(browser.ff) window.getSelection().getRangeAt(0).insertNode(document.createTextNode(cc));
				else document.selection.createRange().text = cc;
			 	this.history[this.pos] = editor.innerHTML;
			}
			this.pos--;
			if(typeof(this.history[this.pos])=='undefined') this.pos++;
			editor.innerHTML = this.history[this.pos];
			CodePress.findString();
		},
		
		redo : function() {
			this.pos++;
			if(typeof(this.history[this.pos])=='undefined') this.pos--;
			editor.innerHTML = this.history[this.pos];
			CodePress.findString();
		},
		
		next : function() { // get next vector position and clean old ones
			if(this.pos>20) this.history[this.pos-21] = undefined;
			return ++this.pos;
		}
	},	
	
	// transform syntax highlighted code to original code
	getCode : function() {
		code = editor.innerHTML;
		code = code.replace(/<pre><p>|<\/p><\/pre>/gi,'');
		code = code.replace(/<br>/gi,'\n');
		code = code.replace(/<\/p>/gi,'\r');
		code = code.replace(/<p>/gi,'\n');
		code = code.replace(/&nbsp;/gi,'');
		code = code.replace(/&shy;/gi,'\xad');
		code = code.replace(/<.*?>/g,'');
		code = code.replace(/&lt;/g,'<');
		code = code.replace(/&gt;/g,'>');
		code = code.replace(/&amp;/gi,'&');
		return code;
	},

	// put code from outside inside editor
	setCode : function(language,code) {
		document.getElementById('cp-lang-style').href = 'languages/codepress-'+language+'.css';
		code = code.replace(/&shy;|&amp;shy;/gi,'\xad');
		code = code.replace(/&/gi,'&amp;');		
       	code = code.replace(/</g,'&lt;');
            code = code.replace(/>/g,'&gt;');
		editor.innerHTML = "<pre>"+code+"</pre>";
		this.language = language;
		this.syntaxHighlight('init');
	}
}



// Regular Expressions for syntax highlighting
languages = { 
	java : [
	/([\"\'].*?[\"\'])/g,'<s>$1</s>', // strings
	/(\W|^)(abstract|continue|for|new|switch|assert|default|goto|package|synchronized|boolean|do|if|private|this|break|double|implements|protected|throw|byte|else|import|public|throws|case|enum|instanceof|return|transient|catch|extends|int|short|try|char|final|interface|static|void|class|finally|long|strictfp|volatile|const|float|native|super|while)(\b)/g,'$1<b>$2</b>$3', // reserved words
	/([^:])\/\/(.*?)(<br>|<\/P>)/g,'$1<i>//$2</i>$3', // comments
	/\/\*(.*?)\*\//g,'<i>/*$1*/</i>' // comments
],
	javascript : [
	/([\"\'])(.*?)([\"\']|<br>|<\/P>)/g,'<s>$1$2$3</s>', // strings
	/(break|continue|do|for|new|this|void|case|default|else|function|return|typeof|while|if|label|switch|var|with|catch|boolean|int|try|false|throws|null|true|goto)([ \.\"\'\{\(\);,&<])/g,'<b>$1</b>$2', // reserved words
	/(alert|isNaN|parent|Array|parseFloat|parseInt|blur|clearTimeout|prompt|prototype|close|confirm|length|Date|location|scroll|Math|document|element|name|self|elements|setTimeout|navigator|status| String|escape|Number|submit|eval|Object|event|onblur|focus|onerror|onfocus|top|onload|toString|onunload|unescape|open|opener|valueOf|window)([ \.\"\'\{\(\);,&<])/g,'<u>$1</u>$2', // special words
	/([\(\){}])/g,'<em>$1</em>', // special chars;
	/([^:])\/\/(.*?)(<br>|<\/P>)/g,'$1<i>//$2</i>$3', // comments
	/(\/\*)(.*?)\*\//g,'<i>$1$2*/</i>' // comments
],
	php : [
	/(&lt;[^!\?]*?&gt;)/g,'<b>$1</b>', // all tags
	/(&lt;style.*?&gt;)(.*?)(&lt;\/style&gt;)/g,'<em>$1</em><em>$2</em><em>$3</em>', // style tags
	/(&lt;script.*?&gt;)(.*?)(&lt;\/script&gt;)/g,'<u>$1</u><u>$2</u><u>$3</u>', // script tags
	/\"(.*?)(\"|<br>|<\/P>)/g,'<s>\"$1$2</s>', // strings double quote
	/\'(.*?)(\'|<br>|<\/P>)/g,'<s>\'$1$2</s>', // strings single quote
	/(&lt;\?)/g,'<strong>$1', // <?.*
	/(\?&gt;)/g,'$1</strong>', // .*?>
	/(&lt;\?php|&lt;\?=|&lt;\?|\?&gt;)/g,'<cite>$1</cite>', // php tags		
	/(\$[\w\.]*)/g,'<var>$1</var>', // vars
	/(false|true|and|or|xor|__FILE__|exception|__LINE__|array|as|break|case|class|const|continue|declare|default|die|do|echo|else|elseif|empty|enddeclare|endfor|endforeach|endif|endswitch|endwhile|eval|exit|extends|for|foreach|function|global|if|include|include_once|isset|list|new|print|require|require_once|return|static|switch|unset|use|var|while|__FUNCTION__|__CLASS__|__METHOD__|final|php_user_filter|interface|implements|extends|public|private|protected|abstract|clone|try|catch|throw|this)([ \.\"\'\{\(;&<])/g,'<ins>$1</ins>$2', // reserved words
	/([^:])\/\/(.*?)(<br|<\/P)/g,'$1<i>//$2</i>$3', // php comments
	/\/\*(.*?)\*\//g,'<i>/*$1*/</i>', // php comments
	/(&lt;!--.*?--&gt.)/g,'<big>$1</big>' // html comments 
],
	html : [
	/(&lt;[^!]*?&gt;)/g,'<b>$1</b>', // all tags
	/(&lt;style.*?&gt;)(.*?)(&lt;\/style&gt;)/g,'<em>$1</em><em>$2</em><em>$3</em>', // style tags
	/(&lt;script.*?&gt;)(.*?)(&lt;\/script&gt;)/g,'<u>$1</u><u>$2</u><u>$3</u>', // script tags
	/=(".*?")/g,'=<s>$1</s>', // atributes double quote
	/=('.*?')/g,'=<s>$1</s>', // atributes single quote
	/(&lt;!--.*?--&gt.)/g,'<i>$1</i>' // comments 
],
	css : [
	/(.*?){(.*?)}/g,'<b>$1</b>{<u>$2</u>}', // tags, ids, classes, etc
	/([\w-]*?):([^\/])/g,'<em>$1</em>:$2', // keys
	/([\"\'].*?[\"\'])/g,'<s>$1</s>', // strings
	/\/\*(.*?)\*\//g,'<i>/*$1*/</i>', // comments
],
	text : [
	// do nothing, as expected
] }

onload = function() {
	CodePress.initialize();
}
