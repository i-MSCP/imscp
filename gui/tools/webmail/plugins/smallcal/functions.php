<?php
   /*
    *  Small Calendar
    *  By Nick Rosenberg <nick@dolphins-angels.com>
    *  This is an implementation of the calendar script
    *  at http://www.cascade.org.uk/software/php/calendar
    *  by David Wilkinson with a few mods to make it work
    *  with Squirrelmail.
    *
    *  NOTE: This script requires the calendar module to work
    *  properly as the days link directly to it.
    *
    */

   // include compatibility plugin
   //
   if (defined('SM_PATH'))
      include_once(SM_PATH . 'plugins/compatibility/functions.php');
   else if (file_exists('../plugins/compatibility/functions.php'))
      include_once('../plugins/compatibility/functions.php');
   else if (file_exists('./plugins/compatibility/functions.php'))
      include_once('./plugins/compatibility/functions.php');


   if (compatibility_check_sm_version(1, 3))
      include_once(SM_PATH . 'plugins/smallcal/Calendar.php');
   else
      include_once('../plugins/smallcal/Calendar.php');


   function smallcal_save_do() {

      global $username, $data_dir, $ShowSmallcal, $color, 
             $smallcal_cheader, $smallcal_cday, $smallcal_ctoday, 
             $smallcal_cevent, $smallcal_cshared, $smallcal_ccalendar, 
             $smallcal_csize, $smallcal_cdefault, $smallcal_separator,
             $smallcal_bottom;


      compatibility_sqextractGlobalVar('ShowSmallcal');
      compatibility_sqextractGlobalVar('smallcal_cdefault');
      compatibility_sqextractGlobalVar('smallcal_cheader');
      compatibility_sqextractGlobalVar('smallcal_cday');
      compatibility_sqextractGlobalVar('smallcal_ctoday');
      compatibility_sqextractGlobalVar('smallcal_cevent');
      compatibility_sqextractGlobalVar('smallcal_cshared');
      compatibility_sqextractGlobalVar('smallcal_csize');
      compatibility_sqextractGlobalVar('smallcal_ccalendar');
      compatibility_sqextractGlobalVar('smallcal_separator');
      compatibility_sqextractGlobalVar('smallcal_bottom');


      if (isset($smallcal_bottom) && $smallcal_bottom == 'on') {
          setPref($data_dir, $username, 'smallcal_bottom', '1');
      } else {
          setPref($data_dir, $username, 'smallcal_bottom', '0');
      }

      if (isset($smallcal_separator) && !empty($smallcal_separator)) {
          setPref($data_dir, $username, 'smallcal_separator', $smallcal_separator);
      } else {
          setPref($data_dir, $username, 'smallcal_separator', '2');
      }

      if (isset($ShowSmallcal) && !empty($ShowSmallcal)) {
          setPref($data_dir, $username, 'smallcal_show', '1');
      } else {
          setPref($data_dir, $username, 'smallcal_show', '');
      }	  

      if (isset($smallcal_cdefault) && !empty($smallcal_cdefault)) {
          setPref($data_dir, $username, 'smallcal_default', '1');
      } else {
          setPref($data_dir, $username, 'smallcal_default', '');
      } 
      if (isset($smallcal_cheader) && !empty($smallcal_cheader)) {
          setPref($data_dir, $username, 'smallcal_header', $smallcal_cheader);
      } else {
          setPref($data_dir, $username, 'smallcal_header', $color[11]);
      }
      if (isset($smallcal_cday) && !empty($smallcal_cday)) {
          setPref($data_dir, $username, 'smallcal_day', $smallcal_cday);
      } else {
          setPref($data_dir, $username, 'smallcal_day', '');
      } 
      if (isset($smallcal_ctoday) && !empty($smallcal_ctoday)) {
          setPref($data_dir, $username, 'smallcal_today', $smallcal_ctoday);
      } else {
          setPref($data_dir, $username, 'smallcal_today', '');
      }
      if (isset($smallcal_cevent) && !empty($smallcal_cevent)) {
          setPref($data_dir, $username, 'smallcal_event', $smallcal_cevent);
      } else {
          setPref($data_dir, $username, 'smallcal_event', '');
      }
      if (isset($smallcal_csize) && !empty($smallcal_csize)) {
          setPref($data_dir, $username, 'smallcal_size', $smallcal_csize);
      } else {
          setPref($data_dir, $username, 'smallcal_size', '');
      }
      if (isset($smallcal_cshared) && !empty($smallcal_cshared)) 
      {
      	  if (! file_exists("../plugins/calendar/calendar_check.php")) 
      	  {
      	  	setPref($data_dir, $username, 'smallcal_shared', '');
      	  	setPref($data_dir, $username, 'smallcal_calendar', 'Personal');
      	  }
          else 
          {
          	setPref($data_dir, $username, 'smallcal_shared', '1');
          	if (isset($smallcal_ccalendar)) 
          	{
          		setPref($data_dir, $username, 'smallcal_calendar', $smallcal_ccalendar);
          	} 
          	else 
          	{
          		setPref($data_dir, $username, 'smallcal_calendar', 'Personal');
      	  	}
      	  }
      }
      else 
      {
      	   setPref($data_dir, $username, 'smallcal_shared', '');
           setPref($data_dir, $username, 'smallcal_calendar', 'Personal');
      }
    }

   function smallcal_pref_do() {

      global $username, $data_dir, $smallcal_show, $smallcal_header, $smallcal_day, $smallcal_today, $smallcal_event, $smallcal_event_i, $smallcal_shared, $smallcal_calendar, $smallcal_size, $smallcal_default, $color, $smallcal_separator, $smallcal_bottom;

      $smallcal_show = getPref($data_dir, $username, 'smallcal_show');
      $smallcal_default = getPref($data_dir, $username, 'smallcal_default');
      $smallcal_header = getPref($data_dir, $username, 'smallcal_header');
      $smallcal_day = getPref($data_dir, $username, 'smallcal_day');
      $smallcal_today = getPref($data_dir, $username, 'smallcal_today');
      $smallcal_event = getPref($data_dir, $username, 'smallcal_event');
      $smallcal_event_i = "normal";
      $smallcal_shared = getPref($data_dir, $username, 'smallcal_shared');
      $smallcal_size = getPref($data_dir, $username, 'smallcal_size');
      $smallcal_calendar = getPref($data_dir, $username, 'smallcal_calendar');
      $smallcal_separator = getPref($data_dir, $username, 'smallcal_separator');
      $smallcal_bottom = getPref($data_dir, $username, 'smallcal_bottom');

      if (!isset($smallcal_header) || !$smallcal_header){
         $smallcal_header = $color[11];
      }
      if ($smallcal_default){ 
	 $smallcal_header = $color[11];
	 $smallcal_day = $color[8];
	 $smallcal_today = $color[8];
	 $smallcal_event = $color[8];
	 $smallcal_event_i = "italic";
	 $smallcal_size = "";
	 $smallcal_calendar = "Personal";
         $smallcal_bottom = 0;
	 $smallcal_separator = 2;
      }
	  // $smallcal_separator = 2; //pulled out of if statement to show box at all times

   }

   function smallcal_options_do() {
      global $username, $data_dir, $smallcal_show, $smallcal_header, 
             $smallcal_day, $smallcal_today, $smallcal_event, 
             $smallcal_shared, $smallcal_calendar, $smallcal_size, 
             $smallcal_default, $smallcal_separator, $smallcal_bottom, $base_uri;

      // even if using defaults, want to make sure to 
      // show these saved values - would be a shame to
      // lose them
      //
      $smallcal_header = getPref($data_dir, $username, 'smallcal_header');
      $smallcal_day = getPref($data_dir, $username, 'smallcal_day');
      $smallcal_today = getPref($data_dir, $username, 'smallcal_today');
      $smallcal_event = getPref($data_dir, $username, 'smallcal_event');
      $smallcal_event_i = "normal";
      $smallcal_size = getPref($data_dir, $username, 'smallcal_size');
      $smallcal_calendar = getPref($data_dir, $username, 'smallcal_calendar');
      $smallcal_separator = getPref($data_dir, $username, 'smallcal_separator');
      $smallcal_bottom = getPref($data_dir, $username, 'smallcal_bottom');

      echo '<tr><td colspan="2">&nbsp;</td></tr>';
      echo '<tr><td colspan="2"><hr width=400></td></tr>';
      echo '<tr><td colspan="2" align=center><b>' . _("Small Calendar Options") . '</b></td></tr>';
      echo '<tr><td align=right nowrap>' . _("Small Calendar:") . "</td>\n" .
           '<td><input name="ShowSmallcal" type=CHECKBOX';
      if ($smallcal_show) {
           echo ' CHECKED';
      }
      echo '> ' . _("Show small calendar in left bar") . "</td></tr>\n";
     // echo '<tr><td align=right nowrap>' . _("Use Default Theme:") . '</td><td><input name="smallcal_cdefault" type=CHECKBOX';
      if ($smallcal_default) {
           echo ' CHECKED';
      }
/*
      echo '> ' . _("This will override values below") . '</td></tr>';	
      echo '<tr><td align=right nowrap>' . _("Header Color:") . ' </td><td><input type=text name="smallcal_cheader" size=10';
      if ($smallcal_header) {
           echo " value=\"$smallcal_header\"";
      }
      echo '></td></tr>';	
      echo '<tr><td align=right nowrap>' . _("Day Color:") . ' </td><td><input type=text name="smallcal_cday" size=10';	
      if ($smallcal_day) {
           echo " value=\"$smallcal_day\"";
      }
      echo '></td></tr>';	
      echo '<tr><td align=right nowrap>' . _("Today Color:") . ' </td><td><input type=text name="smallcal_ctoday" size=10';	
      if ($smallcal_today) {
           echo " value=\"$smallcal_today\"";
      }
      echo '></td></tr>';
      echo '<tr><td align=right nowrap>' . _("Event Color:") . ' </td><td><input type=text name="smallcal_cevent" size=10';	
      if ($smallcal_event) {
           echo " value=\"$smallcal_event\"";
      }
      echo '></td></tr>';
      echo '<tr><td align=right nowrap>' . _("Calendar separator:") . ' </td><td><select name="smallcal_separator">';
     


      if ($smallcal_separator == "" || $smallcal_separator == 0)
      {
          echo '<option value="0" selected>None</option>';
      }
      else
      {
          echo '<option value="0">None</option>';
      }
      if ($smallcal_separator == "1")
      {
          echo '<option value="1" selected>Horizontal rule</option>';
      }
      else
      {
          echo '<option value="1">Horizontal rule</option>';
      }
      if ($smallcal_separator == "2")
      {
          echo '<option value="2" selected>Box</option>';
      }
      else
      {
          echo '<option value="2">Box</option>';
      }
      echo '</select></td></tr>';

 */

/*
      echo '<tr><td align=right nowrap>' . _("Place on Bottom:") 
         . '</td><td><input name="smallcal_bottom" type=CHECKBOX';
      if ($smallcal_bottom) {
        echo ' CHECKED';
      }
      echo ' >'
         . '</td><td>';
*/

      echo '<tr><td align=right nowrap>' . _("Calendar size:") . ' </td><td><select name="smallcal_csize">';
      if ($smallcal_size == "")
      {
          echo '<option value="" selected>Default</option>';
      }
      else
      {
          echo '<option value="">Default</option>';
      }
      if ($smallcal_size == "10px")
      {
          echo '<option value="10px" selected>10 px</option>';
      }
      else
      {
          echo '<option value="10px">10 px</option>';
      }
      if ($smallcal_size == "12px")
      {
          echo '<option value="12px" selected>12 px</option>';
      }
      else
      {
          echo '<option value="12px">12 px</option>';
      }
      if ($smallcal_size == "14px")
      {
          echo '<option value="14px" selected>14 px</option>';
      }
      else
      {
          echo '<option value="14px">14 px</option>';
      }
      if ($smallcal_size == "16px")
      {
          echo '<option value="16px" selected>16 px</option>';
      }
      else
      {
          echo '<option value="16px">16 px</option>';
      } 
      echo '</select></td></tr>'; 

      if (file_exists("../plugins/calendar/calendar_check.php")) 
      {
         echo '<tr><td align=right nowrap>' . _("Use Shared Calendar:") . ' </td><td><table border="0" cellpadding="0" cellspacing="0"><tr><td><input name="smallcal_cshared" type=CHECKBOX';
         if ($smallcal_shared) {
           echo ' CHECKED';
         }
         echo ' onClick="if (!this.checked) '
            . 'for (i = 0; i < document.forms[0].smallcal_ccalendar.length; i++) '
            . 'if (document.forms[0].smallcal_ccalendar.options[i].value == \'Personal\') { '
            . 'document.forms[0].smallcal_ccalendar.options[i].selected = true; break; }">'
            . '</td><td>' . _("Use the shared calendar selected below as your<br>small calendar (default is Personal calendar)") . "</td></tr></table></td></tr>\n";
         echo '<tr><td align=right nowrap>' . _("Shared Calendar:") . ' </td><td>';


         if (compatibility_check_sm_version(1, 3))
            include_once (SM_PATH . 'plugins/calendar/shared_functions.php');
         else
            include_once ('../plugins/calendar/shared_functions.php');


         $shared_list = read_calendar_list($data_dir, 'shared');
         $public_list = read_calendar_list($data_dir, 'public');
         $ccode = get_user_code($data_dir, $username);
         if ($ccode == '') {
            $cal_num = count($shared_list);
            $ccode = str_repeat("0", $cal_num);
         }
   
         echo "<SELECT NAME=\"smallcal_ccalendar\" onChange='"
            . 'if (!document.forms[0].smallcal_cshared.checked) '
            . 'for (i = 0; i < this.length; i++) '
            . 'if (this.options[i].value == "Personal") { '
            . 'this.options[i].selected = true; break; }\'>';

         $count = count($public_list);
         for ($x=0;$x<$count;$x++) {
            $url_calendar=urlencode($public_list[$x]);
            echo "<OPTION VALUE=\"$url_calendar\"";
            if ($smallcal_calendar == $url_calendar) echo " SELECTED";
            echo ">$public_list[$x]</OPTION>\n";
         }
         $code = preg_split('//', $ccode, -1, PREG_SPLIT_NO_EMPTY);
         for ($x=0;$x<count($shared_list);$x++) {
            $url_calendar = urlencode($shared_list[$x]);
            switch ($code[$x]) {
               case 0:
                  break;
               case 1:
               case 2:
                  echo "<OPTION VALUE=\"$url_calendar\"";
                  if ($smallcal_calendar == $url_calendar) echo " SELECTED";
                  echo ">$shared_list[$x]</OPTION>\n";
                  break;
               default:
                  break;
            }
         }
         echo "<OPTION VALUE=\"Personal\"";
         if (urldecode($smallcal_calendar) == 'Personal') echo " SELECTED";
         echo ">Personal</OPTION>\n".
            "          </SELECT>\n";

      }
      echo '</td></tr>';	
      //echo '<tr><td colspan="2">&nbsp;</td></tr>';
      echo '<tr><td></td><td>';

     // include_once("../plugins/smallcal/color.php");

      echo '</td></tr>';
      echo '<tr><td colspan="2">&nbsp;</td></tr>';
      echo '<tr><td colspan="2"><hr width=400></td></tr>';
   }

   // Function to pad dates for compatability with calendar.
   function pad($var, $len)
   {
        while (strlen($var) < $len)
        {
           $var = "0".$var;
        }

        return $var;
   }
	
   class SmallCalendar extends Calendar
   {
        function getCalendarLink($month, $year)
        {
            $s = getenv('SCRIPT_NAME');
            return "$s?month=$month&year=$year";
        }

        function getDateLink($day, $month, $year, $type)
        {
            global $smallcal_calendar ;
            // Pad date vars
            $day = pad($day, 2);
            $month = pad ($month, 2);
            $year = pad ($year, 4);
	    if ( $type == "day" || $type == "" )
	    {
            	$s = '../plugins/calendar/day.php';
            	return "$s?cal=$smallcal_calendar&day=$day&month=$month&year=$year";
	    }

            if ( $type == "month" )
            {
                $s = '../plugins/calendar/calendar.php';
                return "$s?cal=$smallcal_calendar&month=$month&year=$year";
            }
        }
        
        function getCalendarData($day, $month, $year)
        {
        	// Pad date vars
                $day = pad($day, 2);
                $month = pad ($month, 2);
                $year = pad ($year, 4);

    		global $calendardata, $smallcal_calendar, $username, $data_dir, $year;
		
		// Code below is directly from calendar plugin - calendar_data.php, readcalendardata function
		$calendardata = array();
		
		if ($smallcal_calendar != 'Personal')
		{
        		$cal_name = urldecode($smallcal_calendar);
    		}
    		else
    		{
        		$cal_name = $username;
    		}
		
    		$filename = getHashedFile($username, $data_dir, "$cal_name.$year.cal");

    		if (file_exists($filename))
    		{
        		$fp = fopen ($filename,'r');

        		if ($fp)
        		{
            			while ($fdata = fgetcsv ($fp, 4096, '|')) 
            			{
                			$calendardata[$fdata[0]][$fdata[1]] = array( 'length' => $fdata[2],
                        			                                   'priority' => $fdata[3],
                                			                              'title' => $fdata[4],
                                        			                    'message' => $fdata[5],
                                                			           'reminder' => $fdata[6] );
            			}
            			fclose ($fp);
        		}
        
		}
    	        
    	        // End code from calendar_data.php
    	        	
        	$caldate = "$month"."$day"."$year";
        	
        	if (isset($calendardata[$caldate]))
        	{
        		return "1";
        	}
        	else
        	{
        		return "0";
        	}
        }
    }

    function addsmallcal_left_do()
    {
        global $data_dir, $username, $year, $month, $todayis, $calendardata, $smallcal_event, $smallcal_event_i, $smallcal_show, $smallcal_calendar, $smallcal_header, $smallcal_day, $smallcal_today, $smallcal_size, $smallcal_separator, $smallcal_bottom;

        compatibility_sqextractGlobalVar('month');
        compatibility_sqextractGlobalVar('year');


       if (! $smallcal_show) //turned off to show all the time
        {
            return;
        }
	

        $d = getdate(time());

        if ($month == "")
        {
            $month = $d["mon"];
        }

        if ($year == "")
        {
            $year = $d["year"];
        }
	
	if ($smallcal_size == "")
	{
	    $smallcal_size = "14px";
	}

        echo '<style type="text/css">';
        echo "<!--.calendarHeader { font-size: $smallcal_size; font-weight: bolder; color: $smallcal_header ; }--></style>";
        echo '<style type="text/css">';
        echo "<!--.calendarToday { font-size: $smallcal_size; font-weight: bolder; color: $smallcal_today ; }--></style>";
	echo '<style type="text/css">';
        echo "<!--.calendarEventToday { font-size: $smallcal_size; font-style: $smallcal_event_i; font-weight: bolder; color: $smallcal_event ; }--></style>";
        echo '<style type="text/css">';
        echo "<!--.calendarEvent { font-size: $smallcal_size; font-style: $smallcal_event_i; color: $smallcal_event ; }--></style>";
        echo '<style type="text/css">';
        echo "<!--.calendar { font-size: $smallcal_size; color: $smallcal_day ; }--></style>";

        $cal = new SmallCalendar;
        //echo "<br>";
        //echo "<br>";
        echo $cal->getMonthView($month, $year);
   }
?>
