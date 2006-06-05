<!--
function over(number) {
  document.images["image"+number+"_1"].src='themes/blue/images/theme/menu_button_left.gif';
  document.images["image"+number+"_2"].src='themes/blue/images/theme/menu_button_right.gif';
  if (document.layers) {
    document.layers["m"+number].background.src='themes/blue/images/theme/menu_button_background.gif';
  }
  else if (document.all) {
    window.document.all["id"+number].style.backgroundImage = 'url(themes/blue/images/theme/menu_button_background.gif)';
  }
}
function out(number) {
  document.images["image"+number+"_1"].src='themes/blue/images/menubutton_left.gif';
  document.images["image"+number+"_2"].src='themes/blue/images/menubutton_right.gif';
  if (document.layers) {
    document.layers["m"+number].background.src='themes/blue/images/menubutton_background.gif';
  }
  else if (document.all) {
    window.document.all["id"+number].style.backgroundImage = 'url(themes/blue/images/menubutton_background.gif)';
  }
}

function MM_jumpMenu(targ,selObj,restore){ //v3.0
  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
  if (restore) selObj.selectedIndex=0;
}

function mySubmit() {
		document.form1.submit();
}

function sbmt(form) {

    form.submit();
    
    return false;

}

//-->
