// Dynamic CSS (className) plugin for HTMLArea
// Sponsored by http://www.systemconcept.de
// Implementation by Holger Hees
//
// (c) systemconcept.de 2004
// Distributed under the same terms as HTMLArea itself.
// This notice MUST stay intact for use (see license.txt).

function DynamicCSS(editor, args) {
        this.editor = editor;
	        
        var cfg = editor.config;
	var toolbar = cfg.toolbar;
	var self = this;
	var i18n = DynamicCSS.I18N;

        /*var cssArray=null;
        var cssLength=0;
        var lastTag=null;
        var lastClass=null;*/
        
	var css_class = {
		id         : "DynamicCSS-class",
		tooltip       : i18n["DynamicCSSStyleTooltip"],
		options    : {"":""},
		action     : function(editor) { self.onSelect(editor, this); },
		refresh    : function(editor) { self.updateValue(editor, this); }
	};
	cfg.registerDropdown(css_class);

	toolbar[0].splice(0, 0, "separator");
	toolbar[0].splice(0, 0, "DynamicCSS-class");
	toolbar[0].splice(0, 0, "T[CSS]");
};

DynamicCSS.parseStyleSheet=function(editor){
        var i18n = DynamicCSS.I18N;
        iframe = editor._iframe.contentWindow.document;
        
        cssArray=DynamicCSS.cssArray;
        if(!cssArray) cssArray=new Array();
        
        for(i=0;i<iframe.styleSheets.length;i++){
            // Mozilla
            if(HTMLArea.is_gecko){
                try{
                    cssArray=DynamicCSS.applyCSSRule(i18n,iframe.styleSheets[i].cssRules,cssArray);
                }
                catch(e){
                    //alert(e);
                }
            }
            // IE
            else {
                try{
                    if(iframe.styleSheets[i].rules){
                        cssArray=DynamicCSS.applyCSSRule(i18n,iframe.styleSheets[i].rules,cssArray);
                    }
                    // @import StyleSheets (IE)
                    if(iframe.styleSheets[i].imports){
                        for(j=0;j<iframe.styleSheets[i].imports.length;j++){
                            cssArray=DynamicCSS.applyCSSRule(i18n,iframe.styleSheets[i].imports[j].rules,cssArray);
                        }
                    }
                }
                catch(e){
                    //alert(e);
                }
            }
        }
        DynamicCSS.cssArray=cssArray;
}

DynamicCSS.applyCSSRule=function(i18n,cssRules,cssArray){
    for(rule in cssRules){
        // StyleRule
        if(cssRules[rule].selectorText){
            if(cssRules[rule].selectorText.search(/:+/)==-1){
                
                // split equal Styles (Mozilla-specific) e.q. head, body {border:0px}
                // for ie not relevant. returns allways one element
                cssElements = cssRules[rule].selectorText.split(",");
                for(k=0;k<cssElements.length;k++){
                    cssElement = cssElements[k].split(".");
                    
                    tagName=cssElement[0].toLowerCase().trim();
                    className=cssElement[1];
                                        
                    if(!tagName) tagName='all';
                    if(!cssArray[tagName]) cssArray[tagName]=new Array();
                    
                    if(className){
                        if(tagName=='all') cssName=className;
                        else cssName='<'+className+'>';
                    }
                    else{
                        className='none';
                        if(tagName=='all') cssName=i18n["Default"];
                        else cssName='<'+i18n["Default"]+'>';
                    }
                    cssArray[tagName][className]=cssName;
                    DynamicCSS.cssLength++;
                }
            }
        }
        // ImportRule (Mozilla)
        else if(cssRules[rule].styleSheet){
            cssArray=DynamicCSS.applyCSSRule(i18n,cssRules[rule].styleSheet.cssRules,cssArray);
        }
    }
    return cssArray;
}

DynamicCSS._pluginInfo = {
	name          : "DynamicCSS",
	version       : "1.5.2",
	developer     : "Holger Hees",
	developer_url : "http://www.systemconcept.de/",
	c_owner       : "Holger Hees",
	sponsor       : "System Concept GmbH",
	sponsor_url   : "http://www.systemconcept.de/",
	license       : "htmlArea"
};

DynamicCSS.prototype.onSelect = function(editor, obj) {
    var tbobj = editor._toolbarObjects[obj.id];
    var index = tbobj.element.selectedIndex;
    var className = tbobj.element.value;
        
    var parent = editor.getParentElement();
    
    if(className!='none'){
        parent.className=className;
        DynamicCSS.lastClass=className;
    }
    else{
        if(HTMLArea.is_gecko) parent.removeAttribute('class');
        else parent.removeAttribute('className');
    }
    editor.updateToolbar();
};

/*DynamicCSS.prototype.onMode = function(mode) {
    if(mode=='wysiwyg'){
        // reparse possible changed css files
        DynamicCSS.cssArray=null;
        this.updateValue(this.editor,this.editor.config.customSelects["DynamicCSS-class"]);
    }
}*/

DynamicCSS.prototype.reparseTimer = function(editor, obj, instance) {
    // new attempt of rescan stylesheets in 1,2,4 and 8 second (e.g. for external css-files with longer initialisation)
    if(DynamicCSS.parseCount<9){
        setTimeout(function () {
            DynamicCSS.cssLength=0;
            DynamicCSS.parseStyleSheet(editor);
            if(DynamicCSS.cssOldLength!=DynamicCSS.cssLength){
                DynamicCSS.cssOldLength=DynamicCSS.cssLength;
                DynamicCSS.lastClass=null;
                instance.updateValue(editor, obj);
            }
            instance.reparseTimer(editor, obj, instance);
        },DynamicCSS.parseCount*1000);
        DynamicCSS.parseCount=DynamicCSS.parseCount*2;
    }
}

DynamicCSS.prototype.updateValue = function(editor, obj) {
        cssArray=DynamicCSS.cssArray;
        // initial style init
        if(!cssArray){
            DynamicCSS.cssLength=0;
            DynamicCSS.parseStyleSheet(editor);
            cssArray=DynamicCSS.cssArray;
            DynamicCSS.cssOldLength=DynamicCSS.cssLength;
            DynamicCSS.parseCount=1;
            this.reparseTimer(editor,obj,this);
        }
        
        var parent = editor.getParentElement();
        var tagName = parent.tagName.toLowerCase();
        var className = parent.className;
        
        if(DynamicCSS.lastTag!=tagName || DynamicCSS.lastClass!=className){        
            DynamicCSS.lastTag=tagName;
            DynamicCSS.lastClass=className;
        
            var i18n = DynamicCSS.I18N;
            var select = editor._toolbarObjects[obj.id].element;
            
            while(select.length>0){
                select.options[select.length-1] = null;
            }
            
            select.options[0]=new Option(i18n["Default"],'none');
            if(cssArray){
                // style class only allowed if parent tag is not body or editor is in fullpage mode
                if(tagName!='body' || editor.config.fullPage){
                    if(cssArray[tagName]){
                        for(cssClass in cssArray[tagName]){
                            if(cssClass=='none') select.options[0]=new Option(cssArray[tagName][cssClass],cssClass);
                            else select.options[select.length]=new Option(cssArray[tagName][cssClass],cssClass);
                        }
                    }
                    
                    if(cssArray['all']){
                        for(cssClass in cssArray['all']){
                            select.options[select.length]=new Option(cssArray['all'][cssClass],cssClass);
                        }
                    }
                }
                else if(cssArray[tagName] && cssArray[tagName]['none']) select.options[0]=new Option(cssArray[tagName]['none'],'none');
            }
                                
            select.selectedIndex = 0;
            
            if (typeof className != "undefined" && /\S/.test(className)) {
                var options = select.options;
                for (var i = options.length; --i >= 0;) {
                    var option = options[i];
                    if (className == option.value) {
                            select.selectedIndex = i;
                            break;
                    }
                }
                if(select.selectedIndex == 0){
                    select.options[select.length]=new Option(i18n["Undefined"],className);
                    select.selectedIndex=select.length-1;
                }
            }
                        
            if(select.length>1) select.disabled=false;
            else select.disabled=true;
        }
};
