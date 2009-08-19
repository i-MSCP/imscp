<?php
header("Content-type: text/javascript");
if (isset($_GET["skin"]) == true && $_GET["skin"] == "india") { $extracheck = "|| (state == 'edit' && state2 == '') || state == 'view' || state == 'update'"; }
else { $extracheck = ""; }
?>

function submitBrowseForm(directory, entry, state, state2) {

// Check that at least 1 checkbox is checked
	var nr_checkboxes_checked = 0;
	if (state == 'calculatesize' || state == 'chmod' || state == 'copymovedelete' || state == 'downloadzip' || 
          state == 'findstring' || state == 'rename' || state == 'unzip' || state == 'zip' <?php echo $extracheck; ?>) {
		for (var i = 0; i < document.forms['BrowseForm'].elements.length; i++) {
			if (document.forms['BrowseForm'].elements[i].type == 'checkbox') {
				if (document.forms['BrowseForm'].elements[i].checked == true) { nr_checkboxes_checked++; break; }
			}
		}
		if (nr_checkboxes_checked == 0) { 
			alert('Please select at least one directory or file.'); 
			return false;
		}
	}

// For the skins with a <select> drop-down box on top (e.g. India), determine the value of the
// 'entry' variable based on the first selected entry
	if (entry == '' && (state == 'view' || state == 'edit' || state == 'update')) {
		for (var i = 0; i < document.forms['BrowseForm'].elements.length; i++) {
			if (document.forms['BrowseForm'].elements[i].type == 'checkbox') {
				if (document.forms['BrowseForm'].elements[i].checked == true) { entry = document.forms['BrowseForm'].elements[i].value; break; }
			}
		}
	}

// Set BrowseForm values
	document.forms['BrowseForm'].state.value=state;
	document.forms['BrowseForm'].state2.value=state2;
	document.forms['BrowseForm'].directory.value=directory;
	document.forms['BrowseForm'].entry.value=entry;


// Set the select back to the first entry
	for (var i = 0; i < document.forms['BrowseForm'].elements.length; i++) {
		if (document.forms['BrowseForm'].elements[i].name == 'BrowseSelect') { document.forms['BrowseForm'].BrowseSelect.selectedIndex = 0; }
	}

// Submit
	document.forms['BrowseForm'].submit();
}

function do_sort(sort, sortorder) {
	document.forms['BrowseForm'].state.value='browse';
	document.forms['BrowseForm'].state2.value='main';
	document.forms['BrowseForm'].sort.value=sort;
	document.forms['BrowseForm'].sortorder.value=sortorder;
	document.forms['BrowseForm'].submit();
}

function CheckAll(myform) {
	var nr_checkboxes = 0;
	for (var i = 0; i < myform.elements.length; i++) {
		if (myform.elements[i].type == 'checkbox') {
			myform.elements[i].checked = !(myform.elements[i].checked);
			nr_checkboxes = nr_checkboxes + 1;
		}
	}
	for (var j = 1; j <= nr_checkboxes; j++) {
		setColor_js(j, 'checkbox')
	}
}

function setStatus_js(text) {
	id = 'status';
	if (document.getElementById) {
		document.getElementById(id).value = text;
	}
	else if (document.all) {
		document.all[id].value = text;
	}
}

function toggleElement(name) {
	var name_shown  = name + '_shown';
	var name_hidden = name + '_hidden';

	if (document.getElementById) {
		var element_shown  = document.getElementById(name_shown);
		var element_hidden = document.getElementById(name_hidden);
	}
	else if (document.all) {
		var element_shown  = document.all[name_shown];
		var element_hidden = document.all[name_hidden];
	}

	if(element_shown.style.display == "none" || element_shown.style.display == false) {
		element_shown.style.display = "block";
		element_hidden.style.display = "none";
	}
	else if(element_shown.style.display == "block") {
		element_shown.style.display = "none";
		element_hidden.style.display = "block";
	}
}
