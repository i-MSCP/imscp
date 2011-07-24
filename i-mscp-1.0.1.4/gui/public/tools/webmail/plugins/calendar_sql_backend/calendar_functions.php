<?php

/**
  * SquirrelMail Calendar Plugin SQL Backend
  * Copyright (C) 2005 Paul Lesneiwski <pdontthink@angrynerds.com>
  * This program is licensed under GPL. See COPYING for details
  *
  */


include_once(SM_PATH . 'plugins/calendar_sql_backend/functions.php');


/**
  * Returns a listing of all calendars that the given user
  * owns.  If the user is a superuser, all calendars are 
  * returned (regardless of actual ownership); if the user 
  * is a limited administrator, the calendars owned by that 
  * user are returned; and if the user is an unpriveleged 
  * user, just that user's private calendars are returned.
  * 
  * @param string $user The user for which to retrieve calendars
  * @param string $userType The type of user this is, which 
  *                         corresponds to the constants defined
  *                         in the {@link calendar/calendar_constants.php} 
  *                         file
  *
  * @return array An array of calendar objects
  *
  */
function cal_sql_get_all_owned_calendars_do($user, $userType)
{

   global $all_calendars_query, $owned_calendars_query, $all_calendars_of_type_query, 
          $wildcard_calendar_owners_query, $color;


   // get database connection
   //
   $db = cal_get_database_connection();


   // superusers get it all
   //
   if ($userType == SM_CAL_SUPERUSER)
   {

      $sql = $all_calendars_query;
      $IDs = $db->getAll($sql);


      // check for database errors
      //
      if (DB::isError($IDs))
      {
         $msg = $IDs->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_owned_calendars_do): could not query all calendar IDs - ' . $msg, $color);
         exit;
      }


      // reduce to single list of IDs
      //
      $calIDs = array();
      foreach ($IDs as $ID) $calIDs[] = $ID[0];

   }


   else
   {

      // get owned calendars
      //
      $sql = $owned_calendars_query;
      $sql = str_replace('%1', $user, $sql);


      $ownedIDs = $db->getAll($sql);


      // check for database errors
      //
      if (DB::isError($ownedIDs))
      {
         $msg = $ownedIDs->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_owned_calendars_do): could not query owned calendar IDs - ' . $msg, $color);
         exit;
      }


      // reduce to single list of IDs
      //
      $ownedCalIDs = array();
      foreach ($ownedIDs as $ID) $ownedCalIDs[] = $ID[0];


      // get calendars with owner names that have wildcards in them
      //
      $sql = $wildcard_calendar_owners_query;


      $wildcardOwners = $db->getAll($sql);


      // check for database errors
      //
      if (DB::isError($wildcardOwners))
      {
         $msg = $wildcardOwners->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_owned_calendars_do): could not query owners with wildcards - ' . $msg, $color);
         exit;
      }


      // figure out which ones match current user
      //
      foreach ($wildcardOwners as $owner) 
      {
         if (preg_match('/^' . str_replace(array('?', '*'), array('\w{1}', '.*?'),
                        strtoupper($owner[0])) . '$/', strtoupper($user)))
            $ownedCalIDs[] = $owner[1];
      }


      // unpriveleged users just get their private/personal
      // calendars... they should never own anything but, 
      // except we'll make sure...  just in case
      //
      if ($userType == SM_CAL_REGULAR_USER)
      {

         $sql = $all_calendars_of_type_query;
         $sql = str_replace('%1', SM_CAL_TYPE_PERSONAL, $sql);


         $allPrivateIDs = $db->getAll($sql);


         // check for database errors
         //
         if (DB::isError($allPrivateIDs))
         {
            $msg = $allPrivateIDs->getMessage();
            plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_owned_calendars_do): could not query private calendar IDs - ' . $msg, $color);
            exit;
         }


         // reduce to single list of IDs
         //
         $allPrivateCalIDs = array();
         foreach ($allPrivateIDs as $ID) $allPrivateCalIDs[] = $ID[0];


         $calIDs = array_intersect($ownedCalIDs, $allPrivateCalIDs);

      }


      // limited admins get all owned calendars
      //
      else if ($userType == SM_CAL_LIMITED_ADMIN)
         $calIDs = $ownedCalIDs;

   }


   $calList = array();
   foreach ($calIDs as $calID)
      $calList[] = cal_sql_get_calendar_do($calID);


   return $calList;

}



/**
  * Returns a listing of all public calendars.
  *
  * @return array An array of calendar objects
  *
  */
function cal_sql_get_all_public_calendars_do()
{

   global $all_calendars_of_type_query, $color;


   // get database connection
   //
   $db = cal_get_database_connection();


   $sql = $all_calendars_of_type_query;
   $sql = str_replace('%1', SM_CAL_TYPE_PUBLIC, $sql);


   $IDs = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($IDs))
   {
      $msg = $IDs->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_public_calendars_do): could not query public calendar IDs - ' . $msg, $color);
      exit;
   }


   // reduce to single list of IDs
   //
   $calIDs = array();
   foreach ($IDs as $ID) $calIDs[] = $ID[0];


   $calList = array();
   foreach ($calIDs as $calID)
      $calList[] = cal_sql_get_calendar_do($calID);


   return $calList;

}



/**
  * Returns a listing of all shared and private (but not public)
  * calendars that the given user has read or write access to
  * EXCEPT the user's default private calendar.
  *
  * @param string $user The user for which to retrieve calendars
  * @param string $domain The user's domain
  *
  * @return array An array of calendar objects
  *
  */
function cal_sql_get_all_accessible_calendars_do($user, $domain)
{

   global $all_owned_calendars_of_type_query, $all_readable_calendars_of_type_query, 
          $all_writeable_calendars_of_type_query, 
          $wildcard_calendar_owners_of_type_query,
          $wildcard_calendar_readers_of_type_query, 
          $wildcard_calendar_writers_of_type_query, $color;
          


   // get database connection
   //
   $db = cal_get_database_connection();


   // -------------------------------
   // get owned private cals
   //
   $sql = $all_owned_calendars_of_type_query;
   $sql = str_replace(array('%1', '%2'), array($user, SM_CAL_TYPE_PERSONAL), $sql);


   $ownedPrivateIDs = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($ownedPrivateIDs))
   {
      $msg = $ownedPrivateIDs->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_accessible_calendars_do): could not query owned private calendar IDs - ' . $msg, $color);
      exit;
   }


   // reduce to single list of IDs
   //
   $ownedPrivateCalIDs = array();
   foreach ($ownedPrivateIDs as $ID) $ownedPrivateCalIDs[] = $ID[0];


   // -------------------------------
   // get private calendars with owner names that have wildcards in them
   //
   $sql = $wildcard_calendar_owners_of_type_query;
   $sql = str_replace('%1', SM_CAL_TYPE_PERSONAL, $sql);


   $wildcardOwners = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($wildcardOwners))
   {
      $msg = $wildcardOwners->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_accessible_calendars_do): could not query owners with wildcards - ' . $msg, $color);
      exit;
   }


   // figure out which ones match current user
   //
   foreach ($wildcardOwners as $owner)
   {
      if (preg_match('/^' . str_replace(array('?', '*'), array('\w{1}', '.*?'),
                     strtoupper($owner[0])) . '$/', strtoupper($user)))
         $ownedPrivateCalIDs[] = $owner[1];
   }


   // -------------------------------
   // get readable private cals
   //
   $sql = $all_readable_calendars_of_type_query;
   $sql = str_replace(array('%1', '%2'), array($user, SM_CAL_TYPE_PERSONAL), $sql);


   $readablePrivateIDs = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($readablePrivateIDs))
   {
      $msg = $readablePrivateIDs->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_accessible_calendars_do): could not query readable private calendar IDs - ' . $msg, $color);
      exit;
   }


   // reduce to single list of IDs
   //
   $readablePrivateCalIDs = array();
   foreach ($readablePrivateIDs as $ID) $readablePrivateCalIDs[] = $ID[0];


   // -------------------------------
   // get private calendars with reader names that have wildcards in them
   //
   $sql = $wildcard_calendar_readers_of_type_query;
   $sql = str_replace('%1', SM_CAL_TYPE_PERSONAL, $sql);


   $wildcardReaders = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($wildcardReaders))
   {
      $msg = $wildcardReaders->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_accessible_calendars_do): could not query readers with wildcards - ' . $msg, $color);
      exit;
   }


   // figure out which ones match current user
   //
   foreach ($wildcardReaders as $reader)
   {
      if (preg_match('/^' . str_replace(array('?', '*'), array('\w{1}', '.*?'),
                     strtoupper($reader[0])) . '$/', strtoupper($user)))
         $readablePrivateCalIDs[] = $reader[1];
   }


   // -------------------------------
   // get writeable private cals
   //
   $sql = $all_writeable_calendars_of_type_query;
   $sql = str_replace(array('%1', '%2'), array($user, SM_CAL_TYPE_PERSONAL), $sql);


   $writeablePrivateIDs = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($writeablePrivateIDs))
   {
      $msg = $writeablePrivateIDs->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_accessible_calendars_do): could not query writeable private calendar IDs - ' . $msg, $color);
      exit;
   }


   // reduce to single list of IDs
   //
   $writeablePrivateCalIDs = array();
   foreach ($writeablePrivateIDs as $ID) $writeablePrivateCalIDs[] = $ID[0];


   // -------------------------------
   // get private calendars with writer names that have wildcards in them
   //
   $sql = $wildcard_calendar_writers_of_type_query;
   $sql = str_replace('%1', SM_CAL_TYPE_PERSONAL, $sql);


   $wildcardWriters = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($wildcardWriters))
   {
      $msg = $wildcardWriters->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_accessible_calendars_do): could not query writers with wildcards - ' . $msg, $color);
      exit;
   }


   // figure out which ones match current user
   //
   foreach ($wildcardWriters as $writer)
   {
      if (preg_match('/^' . str_replace(array('?', '*'), array('\w{1}', '.*?'),
                     strtoupper($writer[0])) . '$/', strtoupper($user)))
         $writeablePrivateCalIDs[] = $writer[1];
   }


   // -------------------------------
   // wrap up private calendars
   //
   $privateCalIDs = array_unique(array_merge($writeablePrivateCalIDs, 
                                             $readablePrivateCalIDs, 
                                             $ownedPrivateCalIDs));


   // remove default personal calendar from private cal list
   //
   $privateCalID = get_personal_cal_id($user, $domain);
   $privateCalIDs = array_diff($privateCalIDs, array($privateCalID));


   // -------------------------------
   // get owned shared cals
   //
   $sql = $all_owned_calendars_of_type_query;
   $sql = str_replace(array('%1', '%2'), array($user, SM_CAL_TYPE_SHARED), $sql);



   $ownedSharedIDs = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($ownedSharedIDs))
   {
      $msg = $ownedSharedIDs->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_accessible_calendars_do): could not query owned shared calendar IDs - ' . $msg, $color);
      exit;
   }


   // reduce to single list of IDs
   //
   $ownedSharedCalIDs = array();
   foreach ($ownedSharedIDs as $ID) $ownedSharedCalIDs[] = $ID[0];


   // -------------------------------
   // get shared calendars with owner names that have wildcards in them
   //
   $sql = $wildcard_calendar_owners_of_type_query;
   $sql = str_replace('%1', SM_CAL_TYPE_SHARED, $sql);


   $wildcardOwners = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($wildcardOwners))
   {
      $msg = $wildcardOwners->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_accessible_calendars_do): could not query owners with wildcards - ' . $msg, $color);
      exit;
   }


   // figure out which ones match current user
   //
   foreach ($wildcardOwners as $owner)
   {
      if (preg_match('/^' . str_replace(array('?', '*'), array('\w{1}', '.*?'),
                     strtoupper($owner[0])) . '$/', strtoupper($user)))
         $ownedSharedCalIDs[] = $owner[1];
   }


   // -------------------------------
   // get readable shared cals
   //
   $sql = $all_readable_calendars_of_type_query;
   $sql = str_replace(array('%1', '%2'), array($user, SM_CAL_TYPE_SHARED), $sql);


   $readableSharedIDs = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($readableSharedIDs))
   {
      $msg = $readableSharedIDs->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_accessible_calendars_do): could not query readable shared calendar IDs - ' . $msg, $color);
      exit;
   }


   // reduce to single list of IDs
   //
   $readableSharedCalIDs = array();
   foreach ($readableSharedIDs as $ID) $readableSharedCalIDs[] = $ID[0];


   // -------------------------------
   // get shared calendars with reader names that have wildcards in them
   //
   $sql = $wildcard_calendar_readers_of_type_query;
   $sql = str_replace('%1', SM_CAL_TYPE_SHARED, $sql);


   $wildcardReaders = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($wildcardReaders))
   {
      $msg = $wildcardReaders->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_accessible_calendars_do): could not query readers with wildcards - ' . $msg, $color);
      exit;
   }


   // figure out which ones match current user
   //
   foreach ($wildcardReaders as $reader)
   {
      if (preg_match('/^' . str_replace(array('?', '*'), array('\w{1}', '.*?'),
                     strtoupper($reader[0])) . '$/', strtoupper($user)))
         $readableSharedCalIDs[] = $reader[1];
   }


   // -------------------------------
   // get writeable shared cals
   //
   $sql = $all_writeable_calendars_of_type_query;
   $sql = str_replace(array('%1', '%2'), array($user, SM_CAL_TYPE_SHARED), $sql);


   $writeableSharedIDs = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($writeableSharedIDs))
   {
      $msg = $writeableSharedIDs->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_accessible_calendars_do): could not query writeable shared calendar IDs - ' . $msg, $color);
      exit;
   }


   // reduce to single list of IDs
   //
   $writeableSharedCalIDs = array();
   foreach ($writeableSharedIDs as $ID) $writeableSharedCalIDs[] = $ID[0];


   // -------------------------------
   // get shared calendars with writer names that have wildcards in them
   //
   $sql = $wildcard_calendar_writers_of_type_query;
   $sql = str_replace('%1', SM_CAL_TYPE_SHARED, $sql);


   $wildcardWriters = $db->getAll($sql);
   
   
   // check for database errors
   //
   if (DB::isError($wildcardWriters))
   {
      $msg = $wildcardWriters->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_all_accessible_calendars_do): could not query writers with wildcards - ' . $msg, $color);
      exit;
   }

   
   // figure out which ones match current user
   //
   foreach ($wildcardWriters as $writer)
   {
      if (preg_match('/^' . str_replace(array('?', '*'), array('\w{1}', '.*?'),
                     strtoupper($writer[0])) . '$/', strtoupper($user)))
         $writeableSharedCalIDs[] = $writer[1];
   }  


   // -------------------------------
   // wrap up shared calendars
   //
   $calIDs = array_unique(array_merge($writeableSharedCalIDs, $readableSharedCalIDs, 
                                      $ownedSharedCalIDs, $privateCalIDs));


   $calList = array();
   foreach ($calIDs as $calID)
      $calList[] = cal_sql_get_calendar_do($calID);


   return $calList;

}



/**
  * Get calendar
  *
  * Retrieves the given calendar from the backend
  *
  * @param string $calendarID The ID of the calendar to be retrieved
  * @param boolean $quiet When FALSE, if the requested calendar isn't 
  *                       found, an error is shown and execution halts;
  *                       otherwise, FALSE is just returned quietly 
  *                       (optional; default = FALSE)
  *
  * @return mixed A Calendar object corresponding to the desired
  *               calendar, or FALSE if the calendar is not found
  *               and the $quiet parameter is TRUE
  *
  */
function cal_sql_get_calendar_do($calendarID, $quiet=FALSE)
{

   global $calendar_ical_query, $color;


   // get database connection
   //
   $db = cal_get_database_connection();


   $sql = $calendar_ical_query;
   $sql = str_replace('%1', $calendarID, $sql);


   $iCal = $db->getAll($sql);


   // check for database errors
   //
   if (DB::isError($iCal))
   {
      $msg = $iCal->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_calendar_do): could not query calendar - ' . $msg, $color);
      exit;
   }


   if (sizeof($iCal))
      return Calendar::getCalendarFromICal(preg_split("/(\r\n)|(\n)|(\r)/", 
                                                      $iCal[0][0]));


   if (!$quiet)
   {

      global $color;
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_get_calendar_do): cannot find calendar for calendar ID ' . $calendarID, $color);
      exit;
   }


   return FALSE;

}



/**
  * Creates a new calendar
  *
  * Takes the given calendar object and inserts it into the
  * backend as a new calendar.  The ID contained in the given
  * calendar is expected to be a correct unique ID value.
  *
  * @param object $calendar The new calendar object
  *
  */
function cal_sql_create_calendar_do($calendar)
{

   global $insert_cal_query, $insert_cal_owner_query, $insert_cal_reader_query, 
          $insert_cal_writer_query, $color;


   // make sure the calendar doesn't already exist
   //
   $cal = cal_sql_get_calendar_do($calendar->getID(), TRUE);
   if ($cal !== FALSE)
   {
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_create_calendar_do): calendar ' . $calendar->getID() .  ' already exists!', $color);
      exit;
   }


   // get database connection
   //
   $db = cal_get_database_connection();


   // write calendar out to database
   //
   $sql = $insert_cal_query;
   $sql = str_replace(array('%1', '%2', '%3', '%4', '%5', '%6', '%7'), 
                      array($calendar->getID(), $calendar->getCalendarType(), 
                            $calendar->getName(), $calendar->getDomain(), 
                            date('Y-m-d H:i:s', $calendar->createdOn()), 
                            date('Y-m-d H:i:s', $calendar->lastUpdatedOn()), 
                            $calendar->getICal(TRUE)), $sql);


   // send query to database
   //
   $result = $db->query($sql);


   // check for database errors
   //
   if (DB::isError($result))
   {
      $msg = $result->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_create_calendar_do): cannot write calendar to database - ' . $msg, $color);
      exit;
   }


   // write calendar owners out to database
   //
   foreach ($calendar->getOwners() as $owner)
   {

      $sql = $insert_cal_owner_query;
      $sql = str_replace(array('%1', '%2'), array($calendar->getID(), $owner), $sql);


      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_create_calendar_do): cannot write calendar owner to database - ' . $msg, $color);
         exit;
      }

   }


   // write calendar readers out to database
   //
   foreach ($calendar->getReadableUsers() as $reader)
   {

      $sql = $insert_cal_reader_query;
      $sql = str_replace(array('%1', '%2'), array($calendar->getID(), $reader), $sql);


      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_create_calendar_do): cannot write calendar reader to database - ' . $msg, $color);
         exit;
      }

   }


   // write calendar writers out to database
   //
   foreach ($calendar->getWriteableUsers() as $writer)
   {

      $sql = $insert_cal_writer_query;
      $sql = str_replace(array('%1', '%2'), array($calendar->getID(), $writer), $sql);


      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_create_calendar_do): cannot write calendar writer to database - ' . $msg, $color);
         exit;
      }

   }

}



/**
  * Updates a calendar
  *
  * Updates the given calendar by replacing it in the backend
  * with the given calendar object.
  *
  * @param object $calendar The updated calendar object
  *
  */
function cal_sql_update_calendar_do($calendar)
{
      
   global $update_cal_query, $delete_cal_owners_readers_writers_queries, 
          $insert_cal_owner_query, $insert_cal_reader_query, $insert_cal_writer_query,
          $color;

         
   // make sure the calendar exists
   //
   $cal = cal_sql_get_calendar_do($calendar->getID(), TRUE);
   if ($cal === FALSE)
   {
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_update_calendar_do): calendar ' . $calendar->getID() . ' does not exist', $color);
      exit;
   }


   // get database connection
   //
   $db = cal_get_database_connection();


   // write calendar out to database
   //
   $sql = $update_cal_query;
   $sql = str_replace(array('%1', '%2', '%3', '%4', '%5', '%6', '%7'),
                      array($calendar->getID(), $calendar->getCalendarType(),
                            $calendar->getName(), $calendar->getDomain(),
                            date('Y-m-d H:i:s', $calendar->createdOn()),
                            date('Y-m-d H:i:s', $calendar->lastUpdatedOn()),
                            $calendar->getICal(TRUE)), $sql);


   // send query to database
   //
   $result = $db->query($sql);


   // check for database errors
   //
   if (DB::isError($result))
   {
      $msg = $result->getMessage();
      plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_update_calendar_do): cannot update calendar in database - ' . $msg, $color);
      exit;
   }


   // remove calendar owners, readers and writers
   //
   foreach ($delete_cal_owners_readers_writers_queries as $query)
   {

      $sql = $query;
      $sql = str_replace('%1', $calendar->getID(), $sql);


      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_update_calendar_do): cannot delete calendar owner/reader/writer - ' . $msg, $color);
         exit;
      }

   }


   // write calendar owners out to database
   //
   foreach ($calendar->getOwners() as $owner)
   {

      $sql = $insert_cal_owner_query;
      $sql = str_replace(array('%1', '%2'), array($calendar->getID(), $owner), $sql);


      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_update_calendar_do): cannot write calendar owner to database - ' . $msg, $color);
         exit;
      }

   }


   // write calendar readers out to database
   //
   foreach ($calendar->getReadableUsers() as $reader)
   {

      $sql = $insert_cal_reader_query;
      $sql = str_replace(array('%1', '%2'), array($calendar->getID(), $reader), $sql);


      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_update_calendar_do): cannot write calendar reader to database - ' . $msg, $color);
         exit;
      }

   }


   // write calendar writers out to database
   //
   foreach ($calendar->getWriteableUsers() as $writer)
   {

      $sql = $insert_cal_writer_query;
      $sql = str_replace(array('%1', '%2'), array($calendar->getID(), $writer), $sql);


      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_update_calendar_do): cannot write calendar writer to database - ' . $msg, $color);
         exit;
      }

   }

}



/**
  * Delete calendar
  *
  * Removes the given calendar from the system.
  *
  * @param string $calendarID The ID of the calendar to be removed
  *
  */
function cal_sql_delete_calendar_do($calendarID)
{

   global $delete_cal_queries, $username, $color;
   include_once(SM_PATH . 'plugins/calendar_sql_backend/event_functions.php');


   // remove all events first
   //
   $events = cal_sql_get_all_events_do($calendarID, $username);
   foreach ($events as $event)
      cal_sql_delete_event_do($calendarID, $event->getID());


   // get database connection
   //
   $db = cal_get_database_connection();


   // remove calendar 
   //
   foreach ($delete_cal_queries as $query)
   {

      $sql = $query;
      $sql = str_replace('%1', $calendarID, $sql);


      // send query to database
      //
      $result = $db->query($sql);


      // check for database errors
      //
      if (DB::isError($result))
      {
         $msg = $result->getMessage();
         plain_error_message('ERROR IN CALENDAR SQL BACKEND (cal_sql_delete_calendar_do): cannot delete calendar - ' . $msg, $color);
         exit;
      }

   }

}



?>
