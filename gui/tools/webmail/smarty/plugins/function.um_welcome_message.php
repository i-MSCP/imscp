<?
/*======================================================================*\
    Function: smarty_func_um_welcome_message
    Purpose:  translate system boxes into different languages


\*======================================================================*/


function smarty_function_um_welcome_message($args, &$smarty_obj)
{

    extract($args);
	$config_vars = $smarty_obj->_config[0]["vars"];

	$array_keys = array_keys($args);

    if (empty($var)) {
        $smarty_obj->_trigger_error_msg("um_welcome_message: missing 'var' parameter");
        return;
    }

    if (!in_array('messages',$array_keys )) {
        $smarty_obj->_trigger_error_msg("um_welcome_message: missing 'messages' parameter");
        return;
    }

    if (!in_array('unread', $array_keys)) {
        $smarty_obj->_trigger_error_msg("um_welcome_message: missing 'unread' parameter");
        return;
    }
    if (!in_array('var', $array_keys)) {
        $smarty_obj->_trigger_error_msg("um_welcome_message: missing 'var' parameter");
        return;
    }

    if (!in_array('boxname', $array_keys)) {
        $smarty_obj->_trigger_error_msg("um_welcome_message: missing 'boxname' parameter");
        return;
    }

	$wlcmessage = $config_vars["msg_you_have"]. " <b>$messages</b> ";

	if($messages == 1)
		$wlcmessage .= $config_vars["msg_message"].", ";
	else
		$wlcmessage .= $config_vars["msg_messages"].", ";
	if($unread == 0)
		$wlcmessage .= $config_vars["msg_none_unread"]." ";
	elseif($unread == 1)
		$wlcmessage .= "<b>$unread</b> ". $config_vars["msg_one_unread"]." ";
	else
		$wlcmessage .= "<b>$unread</b> ". $config_vars["msg_more_unread"]." ";

	$wlcmessage .= $config_vars["msg_in_the_folder"]." <b>$boxname</b>";

    $smarty_obj->assign($var, $wlcmessage);
    return;
}
?>