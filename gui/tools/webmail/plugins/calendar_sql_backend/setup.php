<?php

/**
  * SquirrelMail Calendar Plugin SQL Backend
  * Copyright (C) 2005 Paul Lesneiwski <pdontthink@angrynerds.com>
  * This program is licensed under GPL. See COPYING for details
  *
  */


/**
  * Register this plugin backend with SquirrelMail 
  *
  */
function squirrelmail_plugin_init_calendar_sql_backend() 
{

   global $squirrelmail_plugin_hooks;

   $squirrelmail_plugin_hooks['get_all_owned_calendars']['calendar_sql_backend'] 
      = 'cal_sql_get_all_owned_calendars';
   $squirrelmail_plugin_hooks['get_all_accessible_calendars']['calendar_sql_backend'] 
      = 'cal_sql_get_all_accessible_calendars';
   $squirrelmail_plugin_hooks['get_all_public_calendars']['calendar_sql_backend'] 
      = 'cal_sql_get_all_public_calendars';

   $squirrelmail_plugin_hooks['create_calendar']['calendar_sql_backend'] 
      = 'cal_sql_create_calendar';
   $squirrelmail_plugin_hooks['delete_calendar']['calendar_sql_backend'] 
      = 'cal_sql_delete_calendar';
   $squirrelmail_plugin_hooks['update_calendar']['calendar_sql_backend'] 
      = 'cal_sql_update_calendar';
   $squirrelmail_plugin_hooks['get_calendar']['calendar_sql_backend'] 
      = 'cal_sql_get_calendar';

   $squirrelmail_plugin_hooks['get_all_events']['calendar_sql_backend'] 
      = 'cal_sql_get_all_events';
   $squirrelmail_plugin_hooks['get_events_for_month']['calendar_sql_backend'] 
      = 'cal_sql_get_events_for_month';
   $squirrelmail_plugin_hooks['get_events_for_day']['calendar_sql_backend'] 
      = 'cal_sql_get_events_for_day';
   $squirrelmail_plugin_hooks['get_recurring_events']['calendar_sql_backend'] 
      = 'cal_sql_get_recurring_events';

   $squirrelmail_plugin_hooks['create_event']['calendar_sql_backend'] 
      = 'cal_sql_create_event';
   $squirrelmail_plugin_hooks['delete_event']['calendar_sql_backend'] 
      = 'cal_sql_delete_event';
   $squirrelmail_plugin_hooks['update_event']['calendar_sql_backend'] 
      = 'cal_sql_update_event';
   $squirrelmail_plugin_hooks['get_event']['calendar_sql_backend'] 
      = 'cal_sql_get_event';

   $squirrelmail_plugin_hooks['get_calendar_holidays']['calendar_sql_backend'] 
      = 'cal_sql_get_calendar_holidays';
//LEFT OFF HERE --- what functions below here are still relevant given that holidays are now no more than events?
//LEFT OFF HERE --- what functions below here are still relevant given that holidays are now no more than events?
//LEFT OFF HERE --- what functions below here are still relevant given that holidays are now no more than events?
   $squirrelmail_plugin_hooks['get_holiday']['calendar_sql_backend'] 
      = 'cal_sql_get_holiday';
   $squirrelmail_plugin_hooks['get_global_holiday']['calendar_sql_backend'] 
      = 'cal_sql_get_global_holiday';
   $squirrelmail_plugin_hooks['get_all_global_holidays']['calendar_sql_backend'] 
      = 'cal_sql_get_all_global_holidays';

}



if (!defined('SM_PATH'))
   define('SM_PATH', '../');



/**
  * Returns version info about this plugin
  *
  */
function calendar_sql_backend_version() 
{

   return '1.1-2.0';

}



/**
  * Returns a listing of all calendars that the given user
  * owns.  If the user is a superuser, all calendars are
  * returned (regardless of actual ownership); if the user
  * is a limited administrator, the calendars owned by that
  * user are returned; and if the user is an unpriveleged
  * user, just that user's private calendars are returned.
  * 
  * @param array Containing:
  *           string $user The user for which to retrieve calendars
  *           string $userType The type of user this is, which 
  *                            corresponds to the constants defined
  *                            in the {@link calendar/calendar_constants.php} 
  *                            file
  *
  * @return array An array of calendar objects
  *
  */
function cal_sql_get_all_owned_calendars($args) 
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/calendar_functions.php');
   return cal_sql_get_all_owned_calendars_do($args[0], $args[1]);

}



/**
  * Returns a listing of all shared and private (but not public)
  * calendars that the given user has read or write access to
  * EXCEPT the user's default private calendar.
  *
  * @param array Containing:
  *           string $user The user for which to retrieve calendars
  *           string $domain The user's domain
  *
  * @return array An array of calendar objects
  *
  */
function cal_sql_get_all_accessible_calendars($args) 
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/calendar_functions.php');
   return cal_sql_get_all_accessible_calendars_do($args[0], $args[1]);

}



/**
  * Returns a listing of all public calendars.
  *
  * @return array An array of calendar objects
  *
  */
function cal_sql_get_all_public_calendars()
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/calendar_functions.php');
   return cal_sql_get_all_public_calendars_do();

}



/**
  * Creates a new calendar
  *
  * Takes the given calendar object and inserts it into the
  * backend as a new calendar.  The ID contained in the given
  * calendar is expected to be a correct unique ID value.
  *
  * @param array $hookdata An array, the first item for which is
  *                        this hook name, the second of which
  *                        is the new calendar object
  *
  */
function cal_sql_create_calendar($hookdata)
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/calendar_functions.php');
   cal_sql_create_calendar_do($hookdata[1]);

}



/**
  * Updates a calendar
  *
  * Updates the given calendar by replacing it in the backend
  * with the given calendar object.
  *
  * @param array $hookdata An array, the first item for which is 
  *                        this hook name, the second of which 
  *                        is the updated calendar object
  *
  */
function cal_sql_update_calendar($hookdata)
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/calendar_functions.php');
   cal_sql_update_calendar_do($hookdata[1]);

}



/**
  * Delete calendar
  *
  * Removes the given calendar from the system.
  *
  * @param array $hookdata An array, the first item for which is
  *                        this hook name, the second of which
  *                        is the ID of the calendar to be removed
  *
  */
function cal_sql_delete_calendar($hookdata)
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/calendar_functions.php');
   cal_sql_delete_calendar_do($hookdata[1]);

}



/**
  * Get calendar
  *
  * Retrieves the given calendar from the backend
  *
  * @param array Containing:
  *           string $calendarID The ID of the calendar to be retrieved
  *           boolean $quiet When FALSE, if the requested calendar isn't
  *                          found, an error is shown and execution halts;
  *                          otherwise, FALSE is just returned quietly
  *                          (optional; default = FALSE)
  *
  * @return mixed A Calendar object corresponding to the desired
  *               calendar, or FALSE if the calendar is not found
  *               and the $quiet parameter is TRUE
  *
  */
function cal_sql_get_calendar($args)
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/calendar_functions.php');
   return cal_sql_get_calendar_do($args[0], $args[1]);

}



/**
  * Retrieves all events, holidays, and other for the given calendar
  * for all time periods.
  *
  * @param array Containing:
  *           string $calID The ID of the calendar for which to retrieve events
  *           string $user The user for which events are being retrieved
  *
  * @return array An array of calendar events.  This array is keyed by
  *               event id, where associated values are Event objects
  *
  */
function cal_sql_get_all_events($args)
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/event_functions.php');
   return cal_sql_get_all_events_do($args[0], $args[1]);

}



/**
  * Retrieves all one-time events for the given calendar
  * for the given month, including any that overlap
  * into previous/next months.
  *
  * Note that this function is intended to populate a
  * Calendar object and may return more events than needed,
  * the caller is responsible for extracting only the events
  * needed using other Calendar methods.
  *
  * @param array Containing:
  *           string $calID The ID of the calendar for which to retrieve events
  *           int $year The year of the month for which to retrieve events
  *           int $month The month for which to retrieve events
  *           string $user The user for which events are being retrieved
  *
  * @return array An array of calendar events.  This array is keyed by
  *               event id, where associated values are Event objects
  *
  */
function cal_sql_get_events_for_month($args)
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/event_functions.php');
   return cal_sql_get_events_for_month_do($args[0], $args[1], $args[2], $args[3]);

}



/**
  * Retrieves all one-time events for the given calendar
  * for the given day, including any that overlap
  * into previous/next days.
  *
  * Note that this function is intended to populate a
  * Calendar object and may return more events than needed,
  * the caller is responsible for extracting only the events
  * needed using other Calendar methods.
  *
  * @param array Containing:
  *           string $calID The ID of the calendar for which to retrieve events
  *           int $year The year of the day for which to retrieve events
  *           int $month The month of the day for which to retrieve events
  *           int $day The day for which to retrieve events
  *           string $user The user for which events are being retrieved
  *
  * @return array An array of calendar events.  This array is keyed by
  *               event id, where associated values are Event objects
  *
  */
function cal_sql_get_events_for_day($args)
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/event_functions.php');
   return cal_sql_get_events_for_day_do($args[0], $args[1], $args[2], $args[3], $args[4]);

}



/**
  * Retrieves all recurring events for the given calendar.
  *
  * @param array Containing:
  *           string $calID The ID of the calendar for which to retrieve events
  *           string $user The user for which events are being retrieved
  *
  * @return array An array of calendar events.  This array is keyed by
  *               event id, where associated values are Event objects
  *
  */
function cal_sql_get_recurring_events($args)
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/event_functions.php');
   return cal_sql_get_recurring_events_do($args[0], $args[1]);

}



/**
  * Creates a new event
  *
  * Takes the given event object and inserts it into the
  * backend as a new event with the ID as given in the 
  * event object.
  *
  * @param array Containing:
  *           string $calendarID The ID of the calendar having an event added
  *           object $event The new event object
  *
  * @return string The ID of the newly created event
  *
  */
function cal_sql_create_event($args)
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/event_functions.php');
   return cal_sql_create_event_do($args[0], $args[1]);

}



/**
  * Delete event
  *
  * Removes the given event from the given calendar.
  *
  * @param array $hookdata An array, the first item for which is
  *                        this hook name, the second of which
  *                        is the calendar ID, the third being 
  *                        the event ID
  *
  */
function cal_sql_delete_event($hookdata)
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/event_functions.php');
   cal_sql_delete_event_do($hookdata[1], $hookdata[2]);

}



/**
  * Updates an event
  *
  * Updates the given event by replacing it in the backend
  * with the given event object.
  *
  * @param array $hookdata An array, the first item for which is
  *                        this hook name, the second of which
  *                        is the calendar ID, the third being 
  *                        the raw calendar object
  *
  */
function cal_sql_update_event($hookdata)
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/event_functions.php');
   cal_sql_update_event_do($hookdata[1], $hookdata[2]);

}



/**
  * Get event
  *
  * Retrieves the given event from the backend
  *
  * @param array Containing:
  *           string $calendarID The ID of the calendar whose event is to be retrieved
  *           string $eventID The ID of the event to be retrieved
  *
  * @return object A Event object corresponding to the desired event or FALSE if not found
  *
  */
function cal_sql_get_event($args)
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/event_functions.php');
   return cal_sql_get_event_do($args[0], $args[1]);

}



/**
  * Get all holidays for the given calendar
  *
  * Retrieves all the holidays for the given calendar from the backend
  *
  * @param array Containing:
  *           string $calendarID The ID of the calendar whose 
  *                              holidays are being retrieved
  *           string $user The user for which to retrieve holidays
  *
  * @return array An array of Holiday objects.   This array is keyed by
  *               holiday id, where associated values are Event objects
  *
  */
function cal_sql_get_calendar_holidays($args)
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/event_functions.php');
   return cal_sql_get_calendar_holidays_do($args[0], $args[1]);

}



//LEFT OFF HERE --- do we even need the holidays fxn file???
//LEFT OFF HERE --- do we even need the holidays fxn file???
//LEFT OFF HERE --- do we even need the holidays fxn file???
//LEFT OFF HERE --- what functions below here are still relevant given that holidays are now no more than events?
//LEFT OFF HERE --- what functions below here are still relevant given that holidays are now no more than events?
//LEFT OFF HERE --- what functions below here are still relevant given that holidays are now no more than events?
/**
  * Get global holiday
  *
  * Retrieves the given global holiday from the backend
  *
  * @param array Containing:
  *           string $holidayID The ID of the holiday to be retrieved
  *
  * @return object A Holiday object corresponding to the desired holiday
  *
  */
function cal_sql_get_global_holiday($args)
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/holiday_functions.php');
   return cal_sql_get_global_holiday_do($args[0]);

}



/**
  * Get holiday
  *
  * Retrieves the given holiday for the given calendar from the backend
  *
  * @param array Containing:
  *           string $calendarID The ID of the calendar whose holiday is to be retrieved
  *           string $holidayID The ID of the holiday to be retrieved
  *
  * @return object A Holiday object corresponding to the desired holiday
  *
  */
function cal_sql_get_holiday($args)
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/holiday_functions.php');
   return cal_sql_get_holiday_do($args[0], $args[1]);

}



/**
  * Returns a listing of all global holidays
  *
  * @return array An array of Holiday objects
  *
  */
function cal_sql_get_all_global_holidays()
{

   include_once(SM_PATH . 'plugins/calendar_sql_backend/holiday_functions.php');
   return cal_sql_get_all_global_holidays_do();

}



?>
