<?php
if (!defined ('SM_PATH')){
    if (file_exists('./gpg_functions.php')){
        define ('SM_PATH' , '../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')) {
        define ('SM_PATH', '../');
    } elseif (file_exists('../gpg_functions.php')){
        define ('SM_PATH' , '../../../');
    } elseif (file_exists('../../plugins/gpg/gpg_functions.php')){
        define ('SM_PATH' , '../../../../');
    } else echo "unable to define SM_PATH in GPG Plugin setup.php, exiting abnormally";
}
require_once(SM_PATH.'class/mime/Rfc822Header.class.php');

class openpgp_header extends Rfc822Header {
    var $strval = '',
	$url = '',
	$id = '',
	$fingerprint = '';
    
    /* "fix" broken version... */
    function processParameters($aParameters) { 
	return($aParameters);
    }

    function parseField($field, $value) {
        $field = strtolower($field);
        switch($field) {
	case 'openpgp':
	    $this->strval = $value;
	    $value = $this->stripComments($value);
	    $properties = $this->parseProperties($value);
	    foreach($properties as $key => $val) {
		$key = strtolower($key);
		switch($key) {
		case 'id':
		    $this->id = $val;
		    break;
		case 'url':
		    $this->url = $val;
		    break;
		default:
		    break;
		}
	    }
	    if(strlen($this->id) <= 0) {
		foreach(explode(";", $value) as $parameter) {
		    if(preg_match("/^\s*([ a-fA-F0-9]+)\s*$/", $parameter, $match)) {
			$match = str_replace(" ", "", $match[1]);
			$this->id = $match;
			break;
		    }
		}
	    }
	    // sanitize id:
	    if(preg_match_all("/[0-9a-fA-F]/s", $this->id, $matches) <= 0) {
		$this->id = "";
		break;
	    }
	    $this->id = implode("", $matches[0]);
	    break;
	default:
	    break;
        }
    }
}
?>