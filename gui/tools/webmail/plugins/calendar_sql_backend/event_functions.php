<?php

/**
  * SquirrelMail Calendar Plugin SQL Backend
  * Copyright (C) 2005 Paul Lesneiwski <pdontthink@angrynerds.com>
  * This program is licensed under GPL. See COPYING for details
  *
  */


include_once(SM_PATH . 'plugins/calendar_sql_backend/functions.php');



/**
  * Retrieves all events, holidays, and other for the given calendar
  * for all time periods.
  *
  * @param string $calID The ID of the calendar for which to retrieve events
  * @param string $user The user for which events are being retrieved
  *
  * @return array An array of calendar events.  This array is keyed by
  *               event id, where associated values are Event objects
  *
  */
function cal_sql_get_all_events_do($calID, $user)
{

   global $all_events_query, $color;


   // get database connection
   //
   $db = cal_get_database_connection();


   $sql = $all_events_query;
   $sql = str_replace('%1', $calID, $sql);
   $IDs = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($IDs))
   {
      $msg = $IDs->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_events_do): could not query all event IDs - ' . $msg, $color);
      exit;
   }


   // reduce to single list of IDs
   //
   $eventIDs = array();
   foreach ($IDs as $ID) $eventIDs[] = $ID[0];


   $eventList = array();
   foreach ($eventIDs as $eventID)
   {
      $event = cal_sql_get_event_do($calID, $eventID);
      if ($event->canRead($user) || $event->canWrite($user) || $event->isOwner($user))
         $eventList[] = $event;
   }


   return $eventList;

}



/**
  * Retrieves all one-time events for the given calendar
  * for the given month, including any that overlap
  * into previous/next months.  
  *
  * @param string $calID The ID of the calendar for which to retrieve events
  * @param int $year The year of the month for which to retrieve events
  * @param int $month The month for which to retrieve events
  * @param string $user The user for which events are being retrieved
  *
  * @return array An array of calendar events.  This array is keyed by
  *               event id, where associated values are Event objects
  *
  */
function cal_sql_get_events_for_month_do($calID, $year, $month, $user)
{

   global $all_one_time_events_for_time_period_query, $color;


   // get database connection
   //
   $db = cal_get_database_connection();


   // figure out start and end date-times
   //
   $firstOfMonth = mktime(0, 0, 0, $month, 1, $year);
   $start = date('Y-m-d H:i:s', $firstOfMonth);
   $end = date('Y-m-t 23:23:59', $firstOfMonth);


   $sql = $all_one_time_events_for_time_period_query;
   $sql = str_replace(array('%1', '%2', '%3'), array($calID, $start, $end), $sql);
   $IDs = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($IDs))
   {
      $msg = $IDs->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_events_for_month_do): could not query events for month - ' . $msg, $color);
      exit;
   }


   // reduce to single list of IDs
   //
   $eventIDs = array();
   foreach ($IDs as $ID) $eventIDs[] = $ID[0];


   $eventList = array();
   foreach ($eventIDs as $eventID)
   {
      $event = cal_sql_get_event_do($calID, $eventID);
      if ($event->canRead($user) || $event->canWrite($user) || $event->isOwner($user))
         $eventList[] = $event;
   }


   return $eventList;

}



/**
  * Retrieves all one-time events for the given calendar
  * for the given day, including any that overlap
  * into previous/next days.
  *
  * @param string $calID The ID of the calendar for which to retrieve events
  * @param int $year The year of the day for which to retrieve events
  * @param int $month The month of the day for which to retrieve events
  * @param int $day The day for which to retrieve events
  * @param string $user The user for which events are being retrieved
  *
  * @return array An array of calendar events.  This array is keyed by
  *               event id, where associated values are Event objects
  *
  */
function cal_sql_get_events_for_day_do($calID, $year, $month, $day, $user)
{

   global $all_one_time_events_for_time_period_query, $color;


   // get database connection
   //
   $db = cal_get_database_connection();


   // figure out start and end date-times
   //
   $theDay = mktime(0, 0, 0, $month, $day, $year);
   $start = date('Y-m-d H:i:s', $theDay);
   $end = date('Y-m-d 23:23:59', $theDay);


   $sql = $all_one_time_events_for_time_period_query;
   $sql = str_replace(array('%1', '%2', '%3'), array($calID, $start, $end), $sql);
   $IDs = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($IDs))
   {
      $msg = $IDs->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_events_for_day_do): could not query events for day - ' . $msg, $color);
      exit;
   }


   // reduce to single list of IDs
   //
   $eventIDs = array();
   foreach ($IDs as $ID) $eventIDs[] = $ID[0];


   $eventList = array();
   foreach ($eventIDs as $eventID)
   {
      $event = cal_sql_get_event_do($calID, $eventID);
      if ($event->canRead($user) || $event->canWrite($user) || $event->isOwner($user))
         $eventList[] = $event;
   }


   return $eventList;

}



/**
  * Retrieves all recurring events for the given calendar.
  *
  * @param string $calID The ID of the calendar for which to retrieve events
  * @param string $user The user for which events are being retrieved
  *
  * @return array An array of calendar events.  This array is keyed by
  *               event id, where associated values are Event objects
  *
  */
function cal_sql_get_recurring_events_do($calID, $user)
{

   global $all_recurring_events_query, $color;


   // get database connection
   //
   $db = cal_get_database_connection();


   $sql = $all_recurring_events_query;
   $sql = str_replace('%1', $calID, $sql);
   $IDs = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($IDs))
   {
      $msg = $IDs->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_recurring_events_do): could not query all recurring event IDs - ' . $msg, $color);
      exit;
   }


   // reduce to single list of IDs
   //
   $eventIDs = array();
   foreach ($IDs as $ID) $eventIDs[] = $ID[0];


   $eventList = array();
   foreach ($eventIDs as $eventID)
   {
      $event = cal_sql_get_event_do($calID, $eventID);
      if ($event->canRead($user) || $event->canWrite($user) || $event->isOwner($user))
         $eventList[] = $event;
   }


   return $eventList;

}



/**
  * Get all holidays for the given calendar
  *
  * Retrieves all the holidays for the given calendar from the backend
  *
  * @param string $calID The ID of the calendar whose holidays
  *                      are being retrieved
  * @param string $user The user for which to retrieve holidays
  *
  * @return array An array of holidays.  This array is keyed by
  *               holiday id, where associated values are Event objects
  *
  */
function cal_sql_get_calendar_holidays_do($calID, $user)
{

   global $all_holidays_query, $color;


   // get database connection
   //
   $db = cal_get_database_connection();


   $sql = $all_holidays_query;
   $sql = str_replace('%1', $calID, $sql);
   $IDs = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($IDs))
   {
      $msg = $IDs->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_calendar_holidays_do): could not query all holiday IDs - ' . $msg, $color);
      exit;
   }


   // reduce to single list of IDs
   //
   $eventIDs = array();
   foreach ($IDs as $ID) $eventIDs[] = $ID[0];


   $eventList = array();
   foreach ($eventIDs as $eventID)
   {
      $event = cal_sql_get_event_do($calID, $eventID);
      if ($event->canRead($user) || $event->canWrite($user) || $event->isOwner($user))
         $eventList[] = $event;
   }


   return $eventList;

}



/**
  * Creates a new event
  *
  * Takes the given event object and inserts it into the
  * backend as a new event with the ID as given in the 
  * event object.
  *
  * @param string $calendarID The ID of the calendar having an event added
  * @param object $event The new event object
  *
  * @return string The ID of the newly created event
  *
  */
function cal_sql_create_event_do($calendarID, $event)
{

   global $insert_event_query, $insert_event_owner_query, $insert_event_reader_query,
          $insert_event_writer_query, $insert_event_parent_cal_query, 
          $get_event_key_query, $newline_regexp, $color;


   // make sure the event doesn't already exist
   //
   $evt = cal_sql_get_event_do($calendarID, $event->getID());
   if ($evt !== FALSE)
   {
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_create_event_do): calendar ' . $calendarID .  ', event ' . $event->getID() . ' already exists!', $color);
      exit;
   }


   // get database connection
   //
   $db = cal_get_database_connection();


   // write event out to database
   //
   $sql = $insert_event_query;
   $sql = str_replace(array('%1', '%2', '%3', '%4', '%5', '%6', '%7', 
                            '%8', '%9', '%a', '%b', '%c', '%d'),
                      array($event->getID(), 
                            date('Y-m-d H:i:s', $event->getStartDateTime()),
                            date('Y-m-d H:i:s', $event->getEndDateTime()),
                            $event->getDomain(), 
                            ($event->isAllDay() ? 'YES' : 'NO'),
                            ($event->isRecurring() ? 'YES' : 'NO'),
                            ($event->isTask() ? 'YES' : 'NO'),
// TODO:
// not yet implemented                            ($event->isHoliday() ? 'YES' : 'NO'),
                            'NO',
                            $event->getPriority(), 
                            date('Y-m-d H:i:s', $event->createdOn()),
                            date('Y-m-d H:i:s', $event->lastUpdatedOn()),
// newlines need more encoding when going into database
//                            $event->getICal(TRUE),
//                            $db->escapeSimple($event->getICal(TRUE)),
                            $db->escapeSimple(
                            (empty($newline_regexp) ? $event->getICal(TRUE) 
                            : preg_replace($newline_regexp, '##SQ_CAL_NL##', $event->getICal(TRUE)))),
                            $calendarID), $sql);


   // send query to database
   //
   $result = $db->query($sql);


   // check for database errors
   //
   if (DB::isError($result))
   {
      $msg = $result->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_create_event_do): cannot write event to database - ' . $msg, $color);
      exit;
   }



   // get event key (could use DB-specific calls to get last inserted key,
   // but we'll just re-query it for maximum compatibility)
   //
   $sql = $get_event_key_query;
   $sql = str_replace(array('%1', '%2'), array($calendarID, $event->getID()), $sql);
   $IDs = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($IDs))
   {
      $msg = $IDs->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_create_event_do): could not query event key - ' . $msg, $color);
      exit;
   }


   // reduce to single ID
   //
   $eventKey = $IDs[0][0];



   // write event owners out to database
   //
   foreach ($event->getOwners() as $owner)
   {

      $sql = $insert_event_owner_query;
      $sql = str_replace(array('%1', '%2'), array($eventKey, $owner), $sql);


      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_create_event_do): cannot write event owner to database - ' . $msg, $color);
         exit;
      }

   }


   // write event readers out to database
   //
   foreach ($event->getReadableUsers() as $reader)
   {

      $sql = $insert_event_reader_query;
      $sql = str_replace(array('%1', '%2'), array($eventKey, $reader), $sql);


      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_create_event_do): cannot write event reader to database - ' . $msg, $color);
         exit;
      }

   }


   // write event writers out to database
   //
   foreach ($event->getWriteableUsers() as $writer)
   {

      $sql = $insert_event_writer_query;
      $sql = str_replace(array('%1', '%2'), array($eventKey, $writer), $sql);


      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_create_event_do): cannot write event writer to database - ' . $msg, $color);
         exit;
      }

   }


   // write event parent calendars out to database
   //
   foreach ($event->getParentCalendars() as $parentID)
   {

      $sql = $insert_event_parent_cal_query;
      $sql = str_replace(array('%1', '%2'), array($eventKey, $parentID), $sql);


      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_create_event_do): cannot write event parent to database - ' . $msg, $color);
         exit;
      }

   }


   return $event->getID();

}



/**
  * Delete event
  *
  * Removes the given event from the given calendar.
  *
  * @param string $calendarID The ID of the calendar whose event is being removed
  * @param string $eventID The ID of the event to be removed
  *
  */
function cal_sql_delete_event_do($calendarID, $eventID)
{

   global $delete_event_queries, $get_event_key_query, $color;


   // get database connection
   //
   $db = cal_get_database_connection();


   // get event key 
   //
   $sql = $get_event_key_query;
   $sql = str_replace(array('%1', '%2'), array($calendarID, $eventID), $sql);
   $IDs = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($IDs))
   {
      $msg = $IDs->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_delete_event_do): could not query event key - ' . $msg, $color);
      exit;
   }


   // reduce to single ID
   //
   $eventKey = $IDs[0][0];


   // remove event
   //
   foreach ($delete_event_queries as $query)
   {

      $sql = $query;
      $sql = str_replace(array('%1', '%2', '%3'), array($calendarID, $eventID, $eventKey), $sql);


      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_delete_event_do): cannot delete event - ' . $msg, $color);
         exit;
      }

   }

}



/**
  * Updates an event
  *
  * Updates the given event by replacing it in the backend
  * with the given event object.
  *
  * @param string $calendarID The ID of the calendar whose event is being updated
  * @param object $event The updated event object
  *
  */
function cal_sql_update_event_do($calendarID, $event)
{

   // just delete and create anew
   //
   //cal_sql_delete_event_do($calendarID, $event->getID());
   //cal_sql_create_event_do($calendarID, $event);
   //return;



   global $update_event_query, $delete_event_owners_readers_writers_parents_queries,
          $insert_event_owner_query, $insert_event_reader_query, 
          $insert_event_writer_query, $insert_event_parent_cal_query,
          $get_event_key_query, $newline_regexp, $color;
          

   // make sure the event exists
   //
   $evt = cal_sql_get_event_do($calendarID, $event->getID());
   if ($evt === FALSE)
   {
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_update_event_do): calendar ' . $calendarID .  ', event ' . $event->getID() . ' does not exist', $color);
      exit;
   }
   

   // get database connection
   // 
   $db = cal_get_database_connection();
   

   // write event out to database
   //
   $sql = $update_event_query;
   $sql = str_replace(array('%1', '%2', '%3', '%4', '%5', '%6', '%7',
                            '%8', '%9', '%a', '%b', '%c', '%d'),
                      array($event->getID(),
                            date('Y-m-d H:i:s', $event->getStartDateTime()),
                            date('Y-m-d H:i:s', $event->getEndDateTime()),
                            $event->getDomain(),
                            ($event->isAllDay() ? 'YES' : 'NO'),
                            ($event->isRecurring() ? 'YES' : 'NO'),
                            ($event->isTask() ? 'YES' : 'NO'),
// TODO: 
// not yet implemented                            ($event->isHoliday() ? 'YES' : 'NO'),
                            'NO',
                            $event->getPriority(),
                            date('Y-m-d H:i:s', $event->createdOn()),
                            date('Y-m-d H:i:s', $event->lastUpdatedOn()),
// newlines need more encoding when going into database
//                            $event->getICal(TRUE),
//                            $db->escapeSimple($event->getICal(TRUE)),
                            $db->escapeSimple(
                            (empty($newline_regexp) ? $event->getICal(TRUE) 
                            : preg_replace($newline_regexp, '##SQ_CAL_NL##', $event->getICal(TRUE)))),
                            $calendarID), $sql);


   // send query to database
   //
   $result = $db->query($sql);


   // check for database errors
   //
   if (DB::isError($result))
   {
      $msg = $result->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_update_event_do): cannot update calendar in database - ' . $msg, $color);
      exit;
   }


   // get event key
   //
   $sql = $get_event_key_query;
   $sql = str_replace(array('%1', '%2'), array($calendarID, $event->getID()), $sql);
   $IDs = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($IDs))
   {
      $msg = $IDs->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_update_event_do): could not query event key - ' . $msg, $color);
      exit;
   }


   // reduce to single ID
   //
   $eventKey = $IDs[0][0];


   // remove event owners, readers, writers and parent calendars
   //
   foreach ($delete_event_owners_readers_writers_parents_queries as $query)
   {

      $sql = $query;
      $sql = str_replace('%1', $eventKey, $sql);


      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_update_event_do): cannot delete event owner/reader/writer/parent - ' . $msg, $color);
         exit;
      }

   }


   // write event owners out to database
   // 
   foreach ($event->getOwners() as $owner)
   {

      $sql = $insert_event_owner_query;
      $sql = str_replace(array('%1', '%2'), array($eventKey, $owner), $sql);
   
   
      // send query to database
      //                    
      $result = $db->query($sql);
                            
                            
      // check for database errors
      //                    
      if (DB::isError($result))
      {                     
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_update_event_do): cannot write event owner to database - ' . $msg, $color);
         exit;              
      }                     
                            
   }                        
                            

   // write event readers out to database
   // 
   foreach ($event->getReadableUsers() as $reader)
   {

      $sql = $insert_event_reader_query;
      $sql = str_replace(array('%1', '%2'), array($eventKey, $reader), $sql);
   
   
      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_update_event_do): cannot write event reader to database - ' . $msg, $color);
         exit;
      }

   }


   // write event writers out to database
   //
   foreach ($event->getWriteableUsers() as $writer)
   {

      $sql = $insert_event_writer_query;
      $sql = str_replace(array('%1', '%2'), array($eventKey, $writer), $sql);


      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_update_event_do): cannot write event writer to database - ' . $msg, $color);
         exit;
      }

   }


   // write event parent calendars out to database
   //
   foreach ($event->getParentCalendars() as $parentID)
   {

      $sql = $insert_event_parent_cal_query;
      $sql = str_replace(array('%1', '%2'), array($eventKey, $parentID), $sql);


      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_update_event_do): cannot write event parent to database - ' . $msg, $color);
         exit;
      }

   }

}



/**
  * Get event
  *
  * Retrieves the given event from the backend
  *
  * @param string $calendarID The ID of the calendar whose event is to be retrieved
  * @param string $eventID The ID of the event to be retrieved
  * @param boolean $quiet When FALSE, if the requested event isn't
  *                       found, an error is shown and execution halts;
  *                       otherwise, FALSE is just returned quietly
  *                       (optional; default = TRUE)
  *
  * @return object An Event object corresponding to the desired event 
  *                or FALSE if not found (and $quiet is TRUE)
  *
  */
function cal_sql_get_event_do($calendarID, $eventID, $quiet=TRUE)
{

   global $username, $event_ical_query, $color;
   $user = $username;


   // get database connection
   //
   $db = cal_get_database_connection();


   $sql = $event_ical_query;
   $sql = str_replace(array('%1', '%2'), array($calendarID, $eventID), $sql);


   $iCal = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($iCal))
   {
      $msg = $iCal->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_event_do): could not query event - ' . $msg, $color);
      exit;
   }


   if (sizeof($iCal))
      return Event::getEventFromICal(preg_split("/(\r\n)|(\n)|(\r)/", 
         str_replace('##SQ_CAL_NL##', '\n', $iCal[0][0])));


   if (!$quiet)
   {

      global $color;
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_event_do): cannot find event for calendar ID ' . $calendarID . ', event ID ' . $eventID, $color);
      exit;
   }


   return FALSE;

}



?>
