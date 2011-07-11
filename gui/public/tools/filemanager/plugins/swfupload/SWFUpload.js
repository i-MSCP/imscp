/*

Version history

1.0.2 - row 86 - added "escape" to the querystring to keep all the parameters intact

*/

function SWFUpload(settings) {
	
	// Remove background flicker in IE
	try 
	{
	  document.execCommand('BackgroundImageCache', false, true);
	} catch(e) {}

	// Generate the tags ID
	this.movieName = "SWFUpload_" + SWFUpload.movieCount++;

	// Load the settings.  Load the Flash movie.
	this.init(settings);
	this.loadFlash();
	
	if (this.debug) 
		this.debugSettings();
}

SWFUpload.movieCount = 0;

// Default error handling.
SWFUpload.handleErrors = function(errcode, file, msg) {
	
	switch(errcode) {
		
		case -10:	// HTTP error
			alert("Error Code: HTTP Error, File name: " + file.name + ", Message: " + msg);
			break;
		
		case -20:	// No upload script specified
			alert("Error Code: No upload script, File name: " + file.name + ", Message: " + msg);
			break;
		
		case -30:	// IOError
			alert("Error Code: IO Error, File name: " + file.name + ", Message: " + msg);
			break;
		
		case -40:	// Security error
			alert("Error Code: Security Error, File name: " + file.name + ", Message: " + msg);
			break;

		case -50:	// Filesize too big
			alert("Error Code: Filesize exceeds limit, File name: " + file.name + ", File size: " + file.size + ", Message: " + msg);
			break;
	
	}
	
};

SWFUpload.prototype.init = function(settings) {

	this.settings = [];

	this.addSetting("debug", settings["debug"],  false);																		// Turn debugging on/off

	// UI settings
	this.addSetting("target", settings["target"], "");																		// Target for auto-generated upload/browse links
	this.addSetting("create_ui", settings["create_ui"], false);																// Auto-generate UI
	this.addSetting("browse_link_class", settings["browse_link_class"], "SWFBrowseLink");										// CSS-class given to auto-generated browse link
	this.addSetting("upload_link_class", settings["upload_link_class"], "SWFUploadLink");										// CSS-class given to auto-generated upload link
	this.addSetting("browse_link_innerhtml", settings["browse_link_innerhtml"], "<span>Browse...</span>");					// innerHTML for generated browse link, default surround with span for easy css-styling
	this.addSetting("upload_link_innerhtml", settings["upload_link_innerhtml"], "<span>Upload</span>");						// innerHTML for generated upload link, default surround with span for easy css-styling
	
	// Callbacks
	this.addSetting("flash_loaded_callback", settings["flash_loaded_callback"], "SWFUpload.flashLoaded");						// Invoked when the flash is loaded
	this.addSetting("upload_file_queued_callback", settings["upload_file_queued_callback"], "");								// Invoked when each file is added to the queue
	this.addSetting("upload_file_start_callback", settings["upload_file_start_callback"], "");								// Invoked when upload starts
	this.addSetting("upload_file_complete_callback", settings["upload_file_complete_callback"],  "");							// Invoked when each file is completed
	this.addSetting("upload_queue_complete_callback", settings["upload_queue_complete_callback"],  "");						// Invoked when upload queue is complete
	this.addSetting("upload_progress_callback", settings["upload_progress_callback"],  "");									// Called with regular updates on progress..
	this.addSetting("upload_dialog_cancel_callback", settings["upload_dialog_cancel_callback"],  "");							// Invoked when cancel btn in dialog is clicked
	this.addSetting("upload_file_error_callback", settings["upload_file_error_callback"], "SWFUpload.handleErrors");			// Invoked on error
	this.addSetting("upload_file_cancel_callback", settings["upload_file_cancel_callback"],  "");								// Invoked when a file upload is cancelled
	this.addSetting("upload_queue_cancel_callback", settings["upload_queue_cancel_callback"], "");							// Invoked when upload queue is cancelled
	
	// SWF Settings
	this.addSetting("upload_script", escape(settings["upload_script"], ""));													// The file that recieves the uploaded files from flash
	this.addSetting("auto_upload", settings["auto_upload"], false);															// Start upload directly or require upload button.
	this.addSetting("allowed_filetypes", settings["allowed_filetypes"], "*.*");												// List of allowed filetypes
	this.addSetting("allowed_filetypes_description", settings["allowed_filetypes_description"], "All files");					// Description for allowed filetypes
	this.addSetting("allowed_filesize", settings["allowed_filesize"], 1024);													// Max allowed filesize
	this.addSetting("flash_path", settings["flash_path"], "jscripts/SWFUpload/SWFUpload.swf");								// Path to flash-file
	this.addSetting("flash_target", settings["flash_target"], "");															// Where to output the flash (not used)
	this.addSetting("flash_width", settings["flash_width"], "1px");															// Flash width
	this.addSetting("flash_height", settings["flash_height"], "1px");															// Flash height
	this.addSetting("flash_color", settings["flash_color"], "#000000");														// Flash color

	this.debug = this.getSetting("debug");																					// Set debug

};

SWFUpload.prototype.loadFlash = function() {
	
	var html = "";
	var sb = new stringBuilder();
	
	// Create Mozilla Embed HTML
	if (navigator.plugins && navigator.mimeTypes && navigator.mimeTypes.length) {
		
		// Build the basic embed html
		sb.append('<embed type="application/x-shockwave-flash" src="' + this.getSetting("flash_path") + '" width="' + this.getSetting("flash_width") + '" height="' + this.getSetting("flash_height") + '"');
		sb.append(' id="' + this.movieName + '" name="' + this.movieName + '" ');
		sb.append('bgcolor="' + this.getSetting["flash_color"] + '" quality="high" wmode="transparent" menu="false" flashvars="');
		sb.append(this._getFlashVars());
		sb.append('" />');
	
	// Create IE Object HTML
	} else {
	
		// Build the basic Object tag
		sb.append('<object id="' + this.movieName + '" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="' + this.getSetting("flash_width") + '" height="' + this.getSetting("flash_height") + '">');
		sb.append('<param name="movie" value="' + this.getSetting("flash_path") + '" />');
		sb.append('<param name="bgcolor" value="#000000" />');
		sb.append('<param name="quality" value="high" />');
		sb.append('<param name="wmode" value="transparent" />');
		sb.append('<param name="menu" value="false" />');
		sb.append('<param name="flashvars" value="' + this._getFlashVars() + '" />');
		sb.append('</object>');

	}
	
	// Build the DOM nodes to hold the flash;
	var container = document.createElement("div");
	container.style.width = "0px";
	container.style.height = "0px";
	container.style.position = "absolute";
	container.style.top = "0px";
	container.style.left = "0px";

	var target_element = document.getElementsByTagName("body")[0];
		
	if (typeof(target_element) == "undefined" || target_element == null)
		return false;
	
	var html = sb.toString();

	target_element.appendChild(container);
	container.innerHTML = html;
		
	this.movieElement = document.getElementById(this.movieName);
	
};

SWFUpload.prototype._getFlashVars = function() {
	
	var sb = new stringBuilder();
	sb.append("uploadScript=" + this.getSetting("upload_script"));
	sb.append("&allowedFiletypesDescription=" + this.getSetting("allowed_filetypes_description"))
	sb.append("&flashLoadedCallback=" + this.getSetting("flash_loaded_callback"));
	sb.append("&uploadFileQueuedCallback=" + this.getSetting("upload_file_queued_callback"));
	sb.append("&uploadFileStartCallback=" + this.getSetting("upload_file_start_callback"));
	sb.append("&uploadProgressCallback=" + this.getSetting("upload_progress_callback"));
	sb.append("&uploadFileCompleteCallback=" + this.getSetting("upload_file_complete_callback"));
	sb.append("&uploadQueueCompleteCallback=" + this.getSetting("upload_queue_complete_callback"));
	sb.append("&uploadDialogCancelCallback=" + this.getSetting("upload_dialog_cancel_callback"));
	sb.append("&uploadFileErrorCallback=" + this.getSetting("upload_file_error_callback"));
	sb.append("&uploadFileCancelCallback=" + this.getSetting("upload_file_cancel_callback"));
	sb.append("&uploadQueueCompleteCallback=" + this.getSetting("upload_queue_complete_callback"));
	sb.append("&autoUpload=" + this.getSetting("auto_upload"));
	sb.append("&allowedFiletypes=" + this.getSetting("allowed_filetypes"));
	sb.append("&maximumFilesize=" + this.getSetting("allowed_filesize"));

	return sb.toString();
}

// The callback method that the Flash movie will call when it has been loaded.
// This should Load the UI parts.
SWFUpload.prototype.flashLoaded = function(bool) {
	this.loadUI();

	if (this.debug) 
		SWFUpload.debug("Flash called home and is ready.");	
};

// Load the UI elements.  Show the UI Target, build the "link" according to the settings, and hide the Degraded Target
SWFUpload.prototype.loadUI = function() {

	if(this.getSetting("target") != "" && this.getSetting("target") != "fileinputs") {
	
		var instance = this;
		var target = document.getElementById(this.getSetting("target"));
		
		// Create the link for uploading
		var browselink = document.createElement("a");
		browselink.className = this.getSetting("browse_link_class");
		browselink.id = this.movieName + "BrowseBtn";
		browselink.href = "javascript:void(0);";
		browselink.onclick = function() { instance.browse(); return false; }
		browselink.innerHTML = this.getSetting("browse_link_innerhtml");
	
		target.innerHTML = "";
		target.appendChild(browselink);
			
		// Add upload btn if auto upload not used
		if(this.getSetting("auto_upload") == false) {

			// Create the link for uploading
			var uploadlink = document.createElement("a");
			uploadlink.className = this.getSetting("upload_link_class");
			uploadlink.id = this.movieName + "UploadBtn";
			uploadlink.href = "#";
			uploadlink.onclick = function() { instance.upload(); return false; }
			uploadlink.innerHTML = this.getSetting("upload_link_innerhtml");
			target.appendChild(uploadlink);

		}
	
	}
	
};

SWFUpload.debug = function(value) {
	if (window.console)
		console.log(value);
	else
		alert(value);
}

SWFUpload.prototype.addSetting = function(name, value, default_value) {
	return this.settings[name] = (typeof(value) == "undefined" || value == null) ? default_value : value;
};

SWFUpload.prototype.getSetting = function(name) {
	return (typeof(this.settings[name]) == "undefined") ? null : this.settings[name];
};

SWFUpload.prototype.browse = function() {
	this.movieElement.browse();
};

SWFUpload.prototype.upload = function() {
	this.movieElement.upload();
}

SWFUpload.prototype.cancelFile = function(file_id) {
	this.movieElement.cancelFile(file_id);
};

SWFUpload.prototype.cancelQueue = function() {
	this.movieElement.cancelQueue();
};

SWFUpload.prototype.debugSettings = function() {
		
	var sb = new stringBuilder();
	
	sb.append("----- DEBUG SETTINGS START ----\n");
	sb.append("ID: " + this.movieElement.id + "\n");
	
	for (var key in this.settings)
		sb.append(key + ": " + this.settings[key] + "\n");

	sb.append("----- DEBUG SETTINGS END ----\n");
	sb.append("\n");
	
	var res = sb.toString();
	
	SWFUpload.debug(res);
};

	
/* UTILS */

function stringBuilder(join) {

this._strings = new Array;
this._join = (typeof join == "undefined") ? "" : join;

	stringBuilder.prototype.append = function(str) {
		this._strings.push(str);
	};
		
	stringBuilder.prototype.toString = function() {
		return this._strings.join(this._join);
	};

};
