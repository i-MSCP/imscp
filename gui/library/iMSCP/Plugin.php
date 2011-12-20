<?php


/**
 * Abstract class for i-MSCP plugins
 *
 * All i-MSCP plugins to interfere with the event system need to inherit from this
 * class.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Core_Plugin
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.1
 */
abstract class iMSCP_Plugin implements iMSCP_Events_Listeners_Interface
{
	/**
	 * Registers a callback function for a given event.
	 *
	 * @param iMSCP_Events_Manager $enventsManager Events manager instance
	 * @return void
	 */
	function registerListener($enventsManager)
	{
		require_once 'iMSCP/Plugin/Exception.php';
		throw new iMSCP_Plugin_Exception(
			sprintf('register() method not implemented in %s', get_class($this)));
	}
}
