<?php
/*
	Weeble File Manager (c) Christopher Michaels & Jonathan Manna
	This software is released under the BSD License.  For a copy of
	the complete licensing agreement see the LICENSE file.
*/

/*************************************************************************
	Message codes
*************************************************************************/

$code_err = gettext( "Error" );
$code_pd = gettext( "Permission denied" );
$code_suc = gettext( "Success" );
$code_inf = gettext( "Info" );

/*************************************************************************
	Permission Messages
*************************************************************************/

$pd_dir = gettext( "Cannot enter \"%s\"" );
$pd_del = gettext( "Cannot delete \"%s\"" );
$pd_dwn = gettext( "Cannot download \"%s\"" );
$pd_dvw = gettext( "Directory listing unavailable" );
$pd_chd = gettext( "Permissions unchanged on \"%s\"" );
$pd_rnm = gettext( "Cannot rename \"%s\"" );
$pd_move = gettext( "Cannot move \"%s\" to \"%s\"" );
$pd_copy = gettext( "Cannot copy \"%s\" to \"%s\"" );

/*************************************************************************
	Error Messages
*************************************************************************/

$err_name = gettext( "No name given" );
$err_dir = gettext( "Could not make directory \"%s\"" );
$err_type = gettext( "No type selected" );
$err_fod = gettext( "No file/directory selected" );
$err_file = gettext( "No file was selected" );
$err_dwn = gettext( "Could not download \"%s\"" );
$err_upl = gettext( "Could not upload \"%s\"" ); 
$err_size = gettext( "File may have exceeded size requirements" );
$err_sys = gettext( "Contact your system administrator" );
$err_per = gettext( "Check your permissions and try again." );
$err_tmp = gettext( "Unable to save the temporary file. Preview unavailable" );
$err_edt = gettext( "No filename and/or editor content specified" );
$err_save = gettext( "Unable to save \"%s\". Please check your permissions and try again" );
$err_ftp = gettext( "Unable to set FTP connection to passive mode" );
$err_edit = gettext( "Cannot retrieve %s" );
$err_max = gettext( "Size of \"%s\" exceeds maximum edit size" );

/*************************************************************************
	Success Messages
*************************************************************************/

$suc_chd = gettext( "Permissions changed on \"%s\"" );
$suc_dir = gettext( "Created directory \"%s\"" );
$suc_del = gettext( "Deleted \"%s\"" );
$suc_rnm = gettext( "Renamed \"%s\" to \"%s\"" );
$suc_move = gettext( "Moved \"%s\" to \"%s\"" );
$suc_copy = gettext( "Copied \"%s\" to \"%s\"" );
$suc_upl = gettext( "Uploaded \"%s\"" );
$suc_file = gettext( "Saved file \"%s\"" );

/*************************************************************************
	Info Messages
*************************************************************************/

$inf_chd = gettext( "File/directory permission changes are turned off by system administration" );
$inf_sel = gettext( "Please select destination directory..." );
$inf_pre = gettext( "Previewing %s. Click save to keep the changes" );

?>
