<?php

global $username, $key, $imapServerAddress, $imapPort, 
       $imap_stream, $imapConnection, $UseSeparateImapConnection,
       $cq_check_quota_debug, $cq_use_1000KB_per_MB,
       $cq_total, $cq_maxcount;

/*
 * Detect if we have already connected to IMAP or not.
 * Also check if we are forced to use a separate IMAP connection
*/

if ( (!isset($imap_stream) && !isset($imapConnection)) || $UseSeparateImapConnection )
{
	$stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 10);
	$previously_connected = false;
} 
elseif (isset($imapConnection)) 
{
	$stream = $imapConnection;
	$previously_connected = true;
} 
else 
{
	$previously_connected = true;
	$stream = $imap_stream;
}

$cq_usage = sqimap_get_quota($stream, 'INBOX');
if ($cq_check_quota_debug)
{
	echo "Usage is: <br>";
	sm_print_r($cq_usage);
}

if ( strpos($cq_usage[0], 'NOQUOTA') === FALSE) 
{
	$cq_taken = $cq_usage[0];
	if ( empty($cq_total) )
		$cq_total = $cq_usage[1];
	$cq_count = $cq_usage[2];
	if ( empty($cq_count) )
		$cq_maxcount = $cq_usage[3];

	if ( $cq_taken != '' && $cq_total != '' && $cq_total != 0 )
	{
		$cq_size_quota = TRUE;
		$cq_percent = number_format(($cq_taken / $cq_total) * 100, 1);
	}
	else 
		$cq_size_quota = FALSE;

	if ( $cq_count != '' && $cq_maxcount != '' && $cq_maxcount != 0 )
	{
		$cq_count_quota = TRUE;
		$cq_countpercent = number_format(($cq_count / $cq_maxcount) * 100, 1);
	}
	else 
		$cq_count_quota = FALSE;

	$cq_quota_KB = $cq_total;
	$cq_taken_KB = $cq_taken;

	if ( $cq_use_1000KB_per_MB )
	{
		if ( $cq_total >= 1000000 )
		{
			$cq_quota = number_format($cq_total / 1000000, 1);
			$cq_q_unit = "GB";
		}
		elseif ( $cq_total >= 1000 )
		{
			$cq_quota = number_format($cq_total / 1000, 1);
			$cq_q_unit = "MB";
		}
		else
		{
			$cq_quota = $cq_total;
			$cq_q_unit = "KB";
		}

		if ( $cq_taken >= 1000000 )
		{
			$cq_taken = number_format($cq_taken / 1000000, 1);
			$cq_t_unit = "GB";
		}
		elseif ( $cq_taken >= 1000 )
		{
			$cq_taken = number_format($cq_taken / 1000, 1);
			$cq_t_unit = "MB";
		}
		else
			$cq_t_unit = "KB";
	}
	else
	{
		if ( $cq_total >= 1048576 )
		{
			$cq_quota = number_format($cq_total / 1048576, 1);
			$cq_q_unit = "GB";
		}
		elseif ( $cq_total >= 1024 )
		{
			$cq_quota = number_format($cq_total / 1024, 1);
			$cq_q_unit = "MB";
		}
		else
		{
			$cq_quota = $cq_total;
			$cq_q_unit = "KB";
		}

		if ( $cq_taken >= 1048576 )
		{
			$cq_taken = number_format($cq_taken / 1048576, 1);
			$cq_t_unit = "GB";
		}
		elseif ( $cq_taken >= 1024 )
		{
			$cq_taken = number_format($cq_taken / 1024, 1);
			$cq_t_unit = "MB";
		}
		else
			$cq_t_unit = "KB";
	}

	if ( isset($fn_left) && $fn_left == 1 )
		include("show_graph.php");
	if ( isset($fn_motd) && $fn_motd == 1 )
		include("show_motd.php");

	$cq_size_quota = FALSE;
	$cq_count_quota = FALSE;
}

if ( !$previously_connected )
	sqimap_logout($stream);

/*
 * Gets current quota usage from IMAP server
 *
 * @param resource $imap_stream An open stream to the IMAP server
 * @param string $mailbox The mail folder for which to check quota 
 *                        usage
 *
 * @return array A four-element array, consisting of numbers in kilobytes
 *               representing (in this order):  
 *                  - storage used (size quota)
 *                  - total storage available (size quota)
 *                  - messages used (message count quota)
 *                  - total messages available (message count quota)
 *               If either size or message count quota is not available,
 *               the corresponding numbers will instead be returned as
 *               empty strings.
 *               If no quota is given or available, the first element
 *               in the return array is given as "NOQUOTA".
 *
*/
function sqimap_get_quota ($imap_stream, $mailbox) 
{
	global $cq_check_quota_debug;

	if (check_quota_capability($imap_stream)) 
	{
		$imap_command = "a001 GETQUOTAROOT \"$mailbox\"\r\n";

		fputs ($imap_stream, $imap_command);
		$read_ary = sqimap_read_data ($imap_stream, 'a001', true, $result, $message);

		if (check_sm_version(1, 5, 0))
			$read_ary = $read_ary['a001'];

		if ($cq_check_quota_debug) 
		{
			echo "IMAP command sent: $imap_command<br />";
			echo 'IMAP response recieved:';
			sm_print_r($read_ary);
		}

		foreach ($read_ary as $response)
		{
			$storageUsed = '';
			$storageTotal = '';
			$messagesUsed = '';
			$messagesTotal = '';
			if (is_array($response))
				foreach ($response as $resp)
				{
					if (strpos($resp, 'STORAGE') !== FALSE || strpos($resp, 'MESSAGE') !== FALSE)
					{
						preg_match('/[(]([STORAGEMESSAGE0-9 ]+)[)]/', $resp, $matches);
						$usageArray = explode(' ', $matches[1]);
						if ($usageArray[0] == 'STORAGE')
							list($storageUsed, $storageTotal) = array($usageArray[1], $usageArray[2]);
						if (!empty($usageArray[3]) && $usageArray[3] == 'STORAGE')
							list($storageUsed, $storageTotal) = array($usageArray[4], $usageArray[5]);
						if ($usageArray[0] == 'MESSAGE')
							list($messagesUsed, $messagesTotal) = array($usageArray[1], $usageArray[2]);
						if (!empty($usageArray[3]) && $usageArray[3] == 'MESSAGE')
							list($messagesUsed, $messagesTotal) = array($usageArray[4], $usageArray[5]);
						return array($storageUsed, $storageTotal, $messagesUsed, $messagesTotal);
					}
				}
			else if (strpos($response, 'STORAGE') !== FALSE || strpos($response, 'MESSAGE') !== FALSE)
			{
				preg_match('/[(]([STORAGEMESSAGE0-9 ]+)[)]/', $response, $matches);
				$usageArray = explode(' ', $matches[1]);
				if ($usageArray[0] == 'STORAGE')
					list($storageUsed, $storageTotal) = array($usageArray[1], $usageArray[2]);
				if (!empty($usageArray[3]) && $usageArray[3] == 'STORAGE')
					list($storageUsed, $storageTotal) = array($usageArray[4], $usageArray[5]);
				if ($usageArray[0] == 'MESSAGE')
					list($messagesUsed, $messagesTotal) = array($usageArray[1], $usageArray[2]);
				if (!empty($usageArray[3]) && $usageArray[3] == 'MESSAGE')
					list($messagesUsed, $messagesTotal) = array($usageArray[4], $usageArray[5]);
				return array($storageUsed, $storageTotal, $messagesUsed, $messagesTotal);
			}
		}
	}

return array('NOQUOTA');

}


/**
  * Determines if IMAP server can report quota information or not
  *
  * @param resource $imap_stream An open stream to the IMAP server
  *                              (optional if $capability is not empty)
  * @param array $capability The IMAP server's capability strings,
  *                          which, if given, is used as is instead 
  *                          of connecting to the IMAP server and 
  *                          asking for its capabilities (optional)
  *
  * @return boolean TRUE if IMAP server has QUOTA capability, FALSE otherwise
  *
  */
function check_quota_capability($imap_stream, $capability='') 
{

   // get CAPABILITY from IMAP server if needed
   //
   if (empty($capability))
   {
      fputs ($imap_stream, "a001 CAPABILITY\r\n");
      $capability = sqimap_read_data($imap_stream, 'a001', true, $a, $b);

      if (check_sm_version(1, 5, 0))
         $capability = $capability['a001'];
   }


   // parse CAPABILITIES, looking for QUOTA
   //
   foreach ($capability as $response)
   {
      if (is_array($response))
         foreach ($response as $resp)
         {
            if (strpos($resp, 'QUOTA') !== FALSE)
               return TRUE;
         }
         else if (strpos($response, 'QUOTA') !== FALSE)
            return TRUE;
   }


   return false;

}

?>
