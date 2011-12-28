// see imscp_full.js for full JS code & license
function sbmt(form,uaction){form.uaction.value=uaction;form.submit();return false;}
function sbmt_details(form,uaction){form.details.value=uaction;form.submit();return false;}
function MM_preloadImages(){var d=document;if(d.images){if(!d.MM_p){d.MM_p=[];}
var j=d.MM_p.length,a=MM_preloadImages.arguments;for(var i=0;i<a.length;i++){if(a[i].indexOf("#")!==0){d.MM_p[j]=new Image();d.MM_p[j++].src=a[i];}}}}
function MM_swapImgRestore(){var x,a=document.MM_sr;for(var i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++){x.src=x.oSrc;}}
function MM_findObj(n,d){var p,x;if(!d){d=document;}
if((p=n.indexOf("?"))>0&&parent.frames.length){d=parent.frames[n.substring(p+1)].document;n=n.substring(0,p);}
if(!(x=d[n])&&d.all){x=d.all[n];}
for(var i=0;!x&&i<d.forms.length;i++){x=d.forms[i][n];}
if(d.layers){for(i=0;!x&&i<d.layers.length;i++){x=MM_findObj(n,d.layers[i].document);}}
if(!x&&d.getElementById){x=d.getElementById(n);}
return x;}
function MM_swapImage(){var j=0,x,a=MM_swapImage.arguments;document.MM_sr=[];for(var i=0,len=a.length-2;i<len;i+=3){if((x=MM_findObj(a[i]))!==null){document.MM_sr[j++]=x;if(!x.oSrc){x.oSrc=x.src;}
x.src=a[i+2];}}}
function MM_goToURL(){var args=MM_goToURL.arguments;document.MM_returnValue=false;for(var i=0,len=args.length-1;i<len;i+=2){window[args[i]].location=String(args[i+1]);}}
function sprintf(){if(!arguments||arguments.length<1||!RegExp){return;}
var str=arguments[0];var re=/([^%]*)%('.|0|\x20)?(-)?(\d+)?(\.\d+)?(%|b|c|d|u|f|o|s|x|X)(.*)/;var a=[],b=[],numSubstitutions=0,numMatches=0;while((a=re.exec(str))){var leftpart=a[1],pPad=a[2],pJustify=a[3],pMinLength=a[4];var pPrecision=a[5],pType=a[6],rightPart=a[7];numMatches++;var subst;if(pType=='%'){subst='%';}else{numSubstitutions++;if(numSubstitutions>=arguments.length){alert('Error! Not enough function arguments ('+(arguments.length-1)+', excluding the string)\nfor the number of substitution parameters in string ('+numSubstitutions+' so far).');}
var param=arguments[numSubstitutions];var pad='';if(pPad&&pPad.substr(0,1)=="'"){pad=leftpart.substr(1,1);}
else if(pPad){pad=pPad;}
var justifyRight=true;if(pJustify&&pJustify==="-"){justifyRight=false;}
var minLength=-1;if(pMinLength){minLength=parseInt(pMinLength,10);}
var precision=-1;if(pPrecision&&pType=='f'){precision=parseInt(pPrecision.substring(1),10);}
subst=param;if(pType=='b'){subst=parseInt(param,10).toString(2);}
else if(pType=='c'){subst=String.fromCharCode(parseInt(param,10));}
else if(pType=='d'){subst=parseInt(param,10)?parseInt(param,10):0;}
else if(pType=='u'){subst=Math.abs(param);}
else if(pType=='f'){subst=(precision>-1)?Math.round(parseFloat(param)*Math.pow(10,precision))/Math.pow(10,precision):parseFloat(param);}
else if(pType=='o'){subst=parseInt(param).toString(8);}
else if(pType=='s'){subst=param;}
else if(pType=='x'){subst=(''+parseInt(param).toString(16)).toLowerCase();}
else if(pType=='X'){subst=(''+parseInt(param).toString(16)).toUpperCase();}}
str=leftpart+subst+rightPart;}
return str;}
function showTip(id,e){var x,y,tip=document.getElementById(id);if(window.event){x=window.event.x+document.body.scrollLeft;y=window.event.y+document.body.scrollTop;}else{x=e.pageX;y=e.pageY;}
tip.style.left=(x+10)+"px";tip.style.top=(y+10)+"px";tip.style.display="block";}
function hideTip(id){document.getElementById(id).style.display="none";}
function showHideBlocks(id){if(document.getElementById(id).style.display=="none"){document.getElementById(id).style.display="block";}else{document.getElementById(id).style.display="none";}}
function showFileTree(){libwindow=window.open("ftp_choose_dir.php","FileTreeDialogPage","menubar=no,width=550,height=400,scrollbars=yes");return false;}
function iMSCPajxError(xhr,settings,exception){switch(xhr.status){case 403:window.location='/index.php';break;default:alert('HTTP ERROR: An Unexpected HTTP Error occurred during the request');}}
