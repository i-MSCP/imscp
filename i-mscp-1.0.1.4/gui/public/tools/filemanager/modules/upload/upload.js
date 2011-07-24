
function add_file(id, i) {
	if (document.getElementById(id + '_' + i).innerHTML.search('uploadinputbutton') == -1) {
		document.getElementById(id + '_' + i).innerHTML = '<input type="file" class="uploadinputbutton" maxsize="" name="' + id + '[]" onchange="return add_file(\'' + id + '\', ' + (i+1) + ');" /><br /><span id="' + id + '_' + (i+1) + '"><input type="button" value="Add other" onclick="add_file(\'' + id + '\', ' + (i+1) + ');" /><\/span>\n';
	}
}

function createUploadWindow() {
	uploadWindow = window.open("","uploadWindow","height=170,width=400,resizable=yes,scrollbars=yes");
	var d = uploadWindow.document;
	d.writeln('<html>');
	d.writeln('<head>');
	d.writeln('<title>Uploading... please wait...<\/title>');
	d.writeln('<\/head>');
	d.writeln('<body>');
	d.writeln('Uploading... please wait...<br /><br />');
	d.writeln('If the upload takes more than the allowed <b>60 seconds<\/b>, you will have to try again with less/smaller files.<br /><br />');
	d.writeln('<scr' + 'ipt lan' + 'guage="jav' + 'ascript">');
	d.writeln('setTimeout("self.close()",8000);');
	d.writeln('<\/scr' + 'ipt>');
	d.writeln('<form><span style="font-size: 100%;">');
	d.writeln('This window will close automatically in a few seconds.<br />');
	d.writeln('<a href="jav' + 'ascript:self.close();">Close window now<\/a>');
	d.writeln('<\/span><\/form>');
	d.writeln('<\/body>');
	d.writeln('<\/html>');
	d.close();
}
