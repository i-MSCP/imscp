<?php

/**
  * SquirrelMail Calendar Plugin SQL Backend
  * Copyright (C) 2005 Paul Lesneiwski <pdontthink@angrynerds.com>
  * This program is licensed under GPL. See COPYING for details
  *
  */


// TODO: show errors if include files not found
include_once('DB.php');
include_once(SM_PATH . 'plugins/calendar/functions.php');
include_once(SM_PATH . 'plugins/calendar_sql_backend/config.php');


/**
  * Get a database connection
  *
  * If a connection has already been opened, return that,
  * otherwise, open a new connection.
  *
  * @return object The database connection handle.
  *
  */
function cal_get_database_connection()
{

   global $cal_db_connection, $cal_dsn;


   // make a new connection if needed; exit if failure
   //
   if (empty($cal_db_connection))
   {

      $cal_db_connection = DB::connect($cal_dsn);
      if (DB::isError($cal_db_connection))
      {
         global $color;
         plain_error_message("Could not make database connection.", $color);
         exit;
      }
      $cal_db_connection->setFetchMode(DB_FETCHMODE_ORDERED);

   }


   // return connection
   //
   return $cal_db_connection;

}



?>
