<?php

/*
*
* MySQl Fix fuer VHCS 2.4.7.1 by Marcus 'crazyiven' Jaentsch
*
* Es wurde bei VHCS dazu uebergegangen statt nur einen User mit Host "localhost" auch einen mit Host "%" zu erstellen.
* Leider gibt es dadurch Probleme mit Usern die VORHER erstellt wurden, weil der User mit dem Host "%" fehlt. Dieser
* kleine Fix liest diese alten User aus der MySQL DB aus und erstellt einen 1:1 gleichen User mit Host "%" und den
* entsprechenden Rechten
*
* Bei Problemen: contact@crazyiven.de
*
*
* Anleitung:
*
* 1) mysql_fix.php hochladen und in /var/www/vhcs2/gui ablegen (Wenn Du die GUI Adresse veraendert hast, dann dorthin!)
* 2) Die PHP Datei einmal ausfuehren indem Du sie mit dem Browser aufrufst (http://www.Deine-VHCSAdresse.de/mysql_fix.php)
* 3) Die PHP Datei wieder löschen!    
*
*
*
*/

/* MySQL Verbindungsdaten laden */
include("include/vhcs2-db-keys.php");
include("include/vhcs-config.php");
$cfg_obj = new Config("/etc/vhcs2/vhcs2.conf");

if ($cfg_obj->status == "err") {

    print "Config konnte nicht geladen werden";
    die();

}

$cfg = $cfg_obj->getValues();

/* MySQL Verbindung aufbauen */
mysql_connect($cfg['DATABASE_HOST'],$cfg['DATABASE_USER'], decrypt_db_password($cfg['DATABASE_PASSWORD'])) or die ("Verbindung zum MySQL Server nicht möglich !");
mysql_select_db("mysql") or die ("Benötigte Datenbank nicht verfügbar !");

/* Alle User auslesen die den Host "localhost" gesetzt haben */
$query01 = mysql_query("SELECT * FROM `user` WHERE `Host` = 'localhost' AND `User` != 'debian-sys-maint' AND `User` != 'root' AND `User` != '' ORDER BY `User`");
while($result01 = mysql_fetch_array($query01)) {

    $query02 = mysql_query("SELECT * FROM `user` WHERE `Host` = '%' AND `User` = '$result01[User]'");
    if(!mysql_num_rows($query02)) {

    $fix_query01 = mysql_query("GRANT USAGE ON *.* TO '$result01[User]'@'%' IDENTIFIED BY PASSWORD '$result01[Password]' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0");    
    
    $query03 = mysql_query("SELECT * FROM `db` WHERE `User` = '$result01[User]'");
    while($result03 = mysql_fetch_array($query03)) {

        $fix_query02 = mysql_query("GRANT ALL PRIVILEGES ON `$result03[Db]` . * TO '$result01[User]'@'%';");

    }

    }

}

?> 
