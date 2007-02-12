<?php

global $username, $cq_quota_binary, $cq_use_1000KB_per_MB;

exec("$cq_quota_binary -v $username", $quota_data, $return_data);

if ( sizeof($quota_data) > 2 ) {

	for ( $i=2 ; $i<sizeof($quota_data) ; $i++ )
	{
		list($cq_fsname, $cq_taken, $cq_total, $cq_limit) =
			split( "[[:blank:]]+", trim($quota_data[$i]) );

		if ( strlen($cq_fsname) != 0 && strlen($cq_taken) == 0 && strlen($cq_total) == 0 )
		{
 			list($cq_taken, $cq_total, $cq_limit) =
				split( "[[:blank:]]+", trim($quota_data[$i+1]) );
			$i++;
		}
		if ( $cq_total > 0 || $cq_limit > 0 )
		{
			if ( !$cq_total > 0 && $cq_limit > 0 )
				$cq_total = $cq_limit;
 
			if ( $cq_taken >= $cq_total && $cq_total != 0 )
				$cq_taken = str_replace("*", "", $cq_taken);

			$cq_size_quota = TRUE;
			$cq_percent = number_format(($cq_taken / $cq_total) * 100, 1);

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
		}
	}

	for ( $i=2 ; $i<sizeof($quota_data) ; $i++ )
	{
		list($cq_fsname, $cq_taken2, $cq_total2, $cq_limit2, $cq_grace, $cq_count, $cq_maxcount, $cq_countlimit) =
			split( "[[:blank:]]+", trim($quota_data[$i]) );

		if ( strlen($cq_fsname) != 0 && strlen($cq_taken2) == 0 && strlen($cq_total2) == 0 )
		{
 			list($cq_taken2, $cq_total2, $cq_limit2, $cq_grace, $cq_count, $cq_maxcount, $cq_countlimit) =
				split( "[[:blank:]]+", trim($quota_data[$i+1]) );
			$i++;
		}

		if ( $cq_taken2 >= $cq_total2 && $cq_total2 != 0 )
			$cq_taken2 = str_replace("*", "", $cq_taken2);

		if ( ( $cq_taken2 < $cq_total2 || ( $cq_total2 == 0 && $cq_taken2 < $cq_limit2 ) )
			|| ( $cq_total2 == 0 && $cq_limit2 == 0 ) ) 
		{
			$cq_countlimit = $cq_maxcount;
			$cq_maxcount = $cq_count;
			$cq_count = $cq_grace;
		}

		if ( $cq_maxcount > 0 || $cq_countlimit > 0 )
		{
			if ( !$cq_maxcount > 0 && $cq_countlimit > 0 )
				$cq_maxcount = $cq_countlimit;
 
			if ( $cq_count >= $cq_maxcount )
				$cq_count = str_replace("*", "", $cq_count);

			$cq_count_quota = TRUE;

			$cq_countpercent = number_format(($cq_count / $cq_maxcount) * 100, 1);

			if ( isset($fn_left) && $fn_left == 1 )
				include("show_graph.php");
			if ( isset($fn_motd) && $fn_motd == 1 )
				include("show_motd.php");

			$cq_count_quota = FALSE;
		}
	}
}

?>
