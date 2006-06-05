#!/bin/bash

#
# VHCS gui permissions setter  v1.0;
# improved by Erik Lehmann 12.2005
#

# read needed entries from vhcs2.conf
for a in `cat /etc/vhcs2/vhcs2.conf | grep -E '(APACHE_|ROOT_DIR)' | sed -e 's/ //g'`
do
export $a
done

#
# fixing gui permissions;
#

for i in `find $ROOT_DIR/gui/`; do 

	if [[ -f $i ]]; then
	
		echo -e "\t0400 $APACHE_USER:$APACHE_GROUP $i";
		
		chmod 0400 $i;
		chown $APACHE_USER:$APACHE_GROUP $i;	
		
	elif [[ -d $i ]]; then
	
		echo "0555 $APACHE_USER:$APACHE_GROUP [$i]";
		
		chmod 0555 $i;
		chown $APACHE_USER:$APACHE_GROUP $i;
	fi

done

#
# fixing webmail's database permissions;
#

i="$ROOT_DIR/gui/tools/webmail/database"

echo "0755 $APACHE_USER:$APACHE_GROUP [$i]";

chmod -R 0755 $i;
chown -R $APACHE_USER:$APACHE_GROUP $i;

#
# fixing user_logo folder permissions;
#

i="$ROOT_DIR/gui/themes/user_logos"

echo "0755 $APACHE_USER:$APACHE_GROUP [$i]";

chmod -R 0644 $i;
chmod 0755 $i;
chown -R $APACHE_USER:$APACHE_GROUP $i;


#
# fixing db keys permissions;
#

chmod 0400 $ROOT_DIR/gui/include/vhcs2-db-keys.php
