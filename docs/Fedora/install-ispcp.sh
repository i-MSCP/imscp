#!/bin/bash

# Fedora Core 7 ISP Control Installer
# Version 1.2.0
# dont blame graywolf

# The path where the Makefile files are
ISPCP_PATH=CHANGEME

ISPCP_TMP_PATH=/tmp/ispcp_install


echo \************************************
echo \* Fedora Core 7 ISP Control Installer
echo \* Author: graywolf
echo \* Version: 1.2.1
echo \************************************
echo

if [ "$ISPCP_PATH" != "CHANGEME" ]; then

######################################
echo Creating Folders and copying files

mkdir ${ISPCP_TMP_PATH}
mkdir ${ISPCP_TMP_PATH}/updates
cp -R ./* ${ISPCP_TMP_PATH}
cd ${ISPCP_TMP_PATH}


######################################
echo Extracting and configuring ISPCP

cd ${ISPCP_TMP_PATH}
mv  ${ISPCP_PATH} ${ISPCP_TMP_PATH}/ispcp-omega-1.0.0-trunk

cd ispcp-omega-1.0.0-trunk

#install Required updates
yum -y install `cat ./docs/Fedora/fedora-packages`

cpan2rpm -i --no-sign http://search.cpan.org/CPAN/authors/id/P/PH/PHOENIX/Term-ReadPassword-0.07.tar.gz

wget -P ${ISPCP_TMP_PATH}/updates http://hany.sk/mirror/fedora/releases/7/Everything/i386/os/Fedora/perl-Net-LibIDN-0.09-3.fc7.i386.rpm
rpm -i ${ISPCP_TMP_PATH}/updates/perl-Net-LibIDN-0.09-3.fc7.i386.rpm

clear
######################################
echo Installing Courier

wget -P ${ISPCP_TMP_PATH}/updates http://www.thatfleminggent.com/packages/fedora/7/i386/courier-authlib-0.60.1-1.fc7.mf.i386.rpm
wget -P ${ISPCP_TMP_PATH}/updates http://www.thatfleminggent.com/packages/fedora/7/i386/courier-authlib-userdb-0.60.1-1.fc7.mf.i386.rpm
wget -P ${ISPCP_TMP_PATH}/updates http://www.thatfleminggent.com/packages/fedora/7/i386/courier-imap-4.1.3-1.fc7.mf.i386.rpm

rpm -i ${ISPCP_TMP_PATH}/updates/courier-authlib-0.60.1-1.fc7.mf.i386.rpm
rpm -i ${ISPCP_TMP_PATH}/updates/courier-authlib-userdb-0.60.1-1.fc7.mf.i386.rpm
rpm -i ${ISPCP_TMP_PATH}/updates/courier-imap-4.1.3-1.fc7.mf.i386.rpm

# Create  group and user with 3000 UID so ISPCP doesnt cause conflicts User
groupadd courier -g 3000
useradd -u 3000 -c 'Courier Mail Server' -d /dev/null -g courier -s /bin/false courier


clear
######################################
echo Installing ISPCP

cd ${ISPCP_TMP_PATH}/ispcp-omega-1.0.0-trunk
make -f Makefile.fedora install

cp -RLf /tmp/ispcp/* /


clear
######################################
echo Performing General Fixes.....

# BIND setup
mv /var/named/data /var/named/data2
ln -s /var/named/chroot/var/named/data /var/named/data

# Fixing missed mkdir in make error
mkdir /var/www/scoreboards

# HTTPD
echo 'include vhosts/*.conf' >> /etc/httpd/conf/httpd.conf

# Courier User database
touch /etc/sasldb2
mkdir -p /var/spool/postfix/etc
cp /etc/sasldb2 /var/spool/postfix/etc/sasldb2
cp -f /etc/init.d/courier-authlib /etc/init.d/courier-authdaemon

# note permissions are changed in cleanup

cp -f /etc/ispcp/bind/named.conf /var/named/chroot/etc/named-ispcp.conf
echo 'include "/etc/named-ispcp.conf";' >> /var/named/chroot/etc/named.conf



######################################
echo Starting mysql daemon

service mysqld restart


clear
######################################
echo Prep work done entering ISPCP setup
cd /var/www/ispcp/engine/setup
perl /var/www/ispcp/engine/setup/ispcp-setup

clear
######################################
echo Removing config files

rm -R ${ISPCP_TMP_PATH}

clear
######################################
echo fixing permissons

chmod 777 /var/www/ispcp/gui/phptmp
chown apache:apache /var/www/ispcp/gui/tools/webmail/data

######################################
echo Setting Startup services
chkconfig --add ispcp_daemon
chkconfig --add spamassassin
chkconfig --add posfix

chkconfig ispcp_daemon on
chkconfig spamassassin on
chkconfig postfix on
chkconfig httpd on
chkconfig mysqld on
chkconfig named on
chkconfig proftpd on

chkconfig sendmail off

######################################
echo re-starting required services
service httpd restart
service ispcp_daemon restart
service named restart
service proftpd restart
service spamassassin restart
service sendmail stop
service postfix restart


######################################
echo removing tmp files
rm -R /tmp/ispcp

clear
######################################
echo \*************************
echo
echo Change:
echo myhostname = \<servername\>
echo mydomain = \<domain\>
echo
read -n 1 -p 'Press any key to continue ......'

vi /etc/postfix/main.cf


service postfix reload

else

echo
echo \*************************
echo
echo PLEASE EDIT THE FILE
echo And change ISPCP_PATH
echo
echo \*************************

fi