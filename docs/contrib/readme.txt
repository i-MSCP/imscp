/*
*
* MySQl Fix fuer VHCS 2.4.7.1 by Marcus 'crazyiven' Jaentsch
*
* Es wurde bei VHCS dazu uebergegangen statt nur einen User mit Host "localhost" auch einen mit Host "%" zu erstellen
.
* Leider gibt es dadurch Probleme mit Usern die VORHER erstellt wurden, weil der User mit dem Host "%" fehlt. Dieser
* kleine Fix liest diese alten User aus der MySQL DB aus und erstellt einen 1:1 gleichen User mit Host "%" und den
* entsprechenden Rechten
*
* Bei Problemen: contact@crazyiven.de
*
*
* Anleitung:
*
* 1) mysql_fix.php hochladen und in /var/www/vhcs2/gui ablegen (Wenn Du die GUI Adresse veraendert hast, dann dorthin
!)
* 2) Die PHP Datei einmal ausfuehren indem Du sie mit dem Browser aufrufst (http://www.Deine-VHCSAdresse.de/mysql_fix
.php)
* 3) Die PHP Datei wieder löschen!
*
*
*
*/

