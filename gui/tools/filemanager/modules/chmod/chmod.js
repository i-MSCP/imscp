function update_field(id,text) {
	if (document.getElementById) {
		document.getElementById(id).value = text;
	}
	else if (document.all) {
		document.all[id].value = text;
	}
}

function get_field(id) {
	if (document.getElementById) {
		var value = document.getElementById(id).value;
	}
	else if (document.all) {
		var value = document.all[id].value;
	}
	return value;
}

function update_input(num) {
	var myform = document.forms['ChmodForm'];
	var myfield = 'chmod';
	var regexp = /list\[([0-9]+)\]\[(owner|group|other)_(read|write|execute)\]/i;
	var myArray = new Array();
	var maxfields = 0;
	for (var i = 0; i < myform.elements.length; i++) {
		if (regexp.test(myform.elements[i].name)) {
			var ar = regexp.exec(myform.elements[i].name);
			var checked = myform.elements[i].checked;
			if (maxfields<ar[1]) maxfields = ar[1];
			myArray[myArray.length] = new Array(ar[1],ar[2],ar[3],checked);
		}
	}
	if (!num || num=="all" || num == '') num = 0;
		for(var i=0; i<maxfields; i++) {
			var id = i+1;
			if (num==0 || num==id) {
				var owner = 0;
				var group = 0;
				var other = 0;
				var add = 0;
				for (var j=0; j<myArray.length; j++) {
					checked = myArray[j][3];
					if (checked && id==myArray[j][0]) {
						if(myArray[j][2]=='read') add = 4;
						else if(myArray[j][2]=='write') add = 2;
						else if(myArray[j][2]=='execute') add = 1;
						if(myArray[j][1]=='owner') owner += add;
						else if(myArray[j][1]=='group') group += add;
						else if(myArray[j][1]=='other') other += add;
					}
				}
			update_field(myfield+id,owner+''+group+''+other);
			if (num!=0 && num==id) break;
		}
	}
}

function update_checkbox(num) {
	var myform = document.forms['ChmodForm'];
	var myfield = 'chmod';
	var regexp = /list\[([0-9]+)\]\[(owner|group|other)_(read|write|execute)\]/i;
	if (!num || num=="all" || num == '') num = 0;
		for (var i = 0; i < myform.elements.length; i++) {
			var name = myform.elements[i].name;
			if (name.substr(0,myfield.length) == myfield) {
				var id = name.substr(myfield.length,name.length);
				if (id>0 && (num==0 || num==id)) {
					var field = get_field(myfield+id);
					var o = field.substr(0,1);
					var g = field.substr(1,1);
					var e = field.substr(2,1);
					if (field.length==3 && o>=0 && o<=7 && g>=0 && g<=7 && e>=0 && e<=7) {
						for (var j = 0; j < myform.elements.length; j++) {
							if (regexp.test(myform.elements[j].name)) {
								var ar = regexp.exec(myform.elements[j].name);
								if (ar[1]==id) {
									var check = false;
									if (ar[2]=='owner') {
										if (ar[3]=='read' && (o==4 || o==5 || o==6 || o==7))
											check = true;
										if (ar[3]=='write' && (o==2 || o==3 || o==6 || o==7))
					 						check = true;
           									if (ar[3]=='execute' && (o==1 || o==3 || o==5 || o==7))
           										check = true;
									}
									else if (ar[2]=='group') {
										if (ar[3]=='read' && (g==4 || g==5 || g==6 || g==7))
											check = true;
										if (ar[3]=='write' && (g==2 || g==3 || g==6 || g==7))
											check = true;
										if (ar[3]=='execute' && (g==1 || g==3 || g==5 || g==7))
											check = true;
									}
									else if (ar[2]=='other') {
										if (ar[3]=='read' && (e==4 || e==5 || e==6 || e==7))
											check = true;
										if (ar[3]=='write' && (e==2 || e==3 || e==6 || e==7))
											check = true;
										if (ar[3]=='execute' && (e==1 || e==3 || e==5 || e==7))
											check = true;
									}
									if (check==true) myform.elements[j].checked = 1;
									else myform.elements[j].checked = 0;
								}
							}
						}
					}
				else {
					update_input(id);
				}
			}
		}
	}
}

function CopyCheckboxToAll(myform, mysourcefieldname, mytargetfieldname) {
	for (var i = 0; i < myform.elements.length; i++) {
		if (myform.elements[i].name.indexOf(mysourcefieldname) >= 0) {
			for (var j = 0; j < myform.elements.length; j++) {
				if (myform.elements[j].name.indexOf(mytargetfieldname) >= 0) {
					myform.elements[j].checked = myform.elements[i].checked;
				}
			}
		}
	}
}
