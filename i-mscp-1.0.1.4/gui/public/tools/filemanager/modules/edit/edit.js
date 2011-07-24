function toggleTextarea(name) {
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