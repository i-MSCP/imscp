%define version 1.0.2.20090822

License: MPL LGPL
Name: ispcp-omega
Version: %{version}
Release: 0%{dist}
URL: http://isp-control.net/
Source: ispcp-omega-%{version}.tar.bz2
Summary: IspCP omega web hosting panel
Group: System/Management
Packager: George Machitidze <giomac@gmail.com>
Buildroot: $RPM_BUILD_ROOT/tmp/ispcp/
Buildrequires: glibc-headers gcc
Requires: amavisd-new awstats bind-chroot bind-utils bzip2 caching-nameserver chkrootkit clamav clamav-data clamav-lib clamav-server clamav-update courier-authlib-userdb courier-imap cpan2rpm cyrus-sasl-gssapi cyrus-sasl-plain cyrus-sasl-md5 cyrus-sasl-ntlm expect gcc httpd iptables libdbi-dbd-mysql libmcrypt libtool-ltdl mod_perl mod_ssl mod_auth_mysql mysql-server patch perl-Archive-Tar perl-Archive-Zip perl-BerkeleyDB perl-Bit-Vector perl-Carp-Clan perl-Compress-Zlib perl-Convert-TNEF perl-Convert-UUlib perl-Crypt-Blowfish perl-Crypt-CBC perl-Crypt-DH perl-Crypt-PasswdMD5 perl-Date-Calc perl-DateManip perl-DBD-MySQL perl-Digest-HMAC perl-HTML-Parser perl-HTML-Tagset perl-MIME-tools perl-IO-stringy perl-libwww-perl perl-MailTools perl-Net-CIDR-Lite perl-Net-DNS perl-Net-IP perl-Net-LibIDN perl-Net-Netmask perl-Net-Server perl-SNMP_Session perl-suidperl perl-TermReadKey perl-Term-ReadPassword perl-TimeDate perl-URI perl-Unix-Syslog php php-bcmath php-dba php-gd php-ldap php-mbstring php-mcrypt php-mysql php-odbc php-pear php-snmp php-xml postfix proftpd-mysql rkhunter spamassassin system-config-bind tar unixODBC unzip wget perl-HTML-Mason perl-Text-Aspell perl-XML-DOM perl-XML-Parser mod_fcgid
Provides: perl(ispcp-db-keys.pl) perl(ispcp_common_code.pl) perl(ispcp_common_methods.pl)  perl(ispcp-setup-methods.pl)

%description
IspCP is a project founded to build a Multi Server Control and Administration Panel.

%prep

%setup -q
rm -rf $RPM_BUILD_ROOT
INST_PREF=$RPM_BUILD_ROOT make -f Makefile.fedora install

%install
#echo "install"
find $RPM_BUILD_ROOT/var/www/ispcp/gui/ -type d -exec chmod 555 {} ';'
find $RPM_BUILD_ROOT/var/www/ispcp/gui/ -type f -exec chmod 444 {} ';'
chmod -R 755 $RPM_BUILD_ROOT/var/www/ispcp/gui/include/htmlpurifier/HTMLPurifier/DefinitionCache/Serializer
chmod -R 755 $RPM_BUILD_ROOT/var/www/ispcp/gui/tools/webmail/data
chmod -R 777 $RPM_BUILD_ROOT/var/www/ispcp/gui/tools/filemanager/temp
chmod -R 644 $RPM_BUILD_ROOT/var/www/ispcp/gui/themes/user_logos/*
chmod 755 $RPM_BUILD_ROOT/var/www/ispcp/gui/themes/user_logos
chmod -R 755 $RPM_BUILD_ROOT/var/www/ispcp/gui/phptmp
chown vmail:mail $RPM_BUILD_ROOT/var/www/ispcp/engine/messenger/*
mv $RPM_BUILD_ROOT/etc/proftpd.conf $RPM_BUILD_ROOT/etc/proftpd.conf.ispcp
mkdir -p $RPM_BUILD_ROOT/etc/httpd/vhosts/
echo 'include vhosts/*.conf' >> $RPM_BUILD_ROOT/etc/httpd/conf.d/ispcp-vhosts-include.conf

%pre
echo "Creating vu2000 virtual user and group "
groupadd -g 2000 vu2000
useradd -d /var/www/fcgi/master/ -c vu-master -g 2000 -u 2000 -s /bin/false vu2000
echo "Creating vmail virtual user "
useradd -d /home/vmail -c vmail-user -g mail -u 3001 -s /bin/false vmail

%post
echo "Saving existing proftpd configuration to /etc/proftpd.orig"
mv -f /etc/proftpd.conf /etc/proftpd.orig
cp /etc/proftpd.conf.ispcp /etc/proftpd.conf
#mv /var/named/data /var/named/data2
#ln -s /var/named/chroot/var/named/data /var/named/data
touch /etc/sasldb2
mkdir /var/spool/postfix/etc
cp /etc/sasldb2 /var/spool/postfix/etc/sasldb2
chmod +x /etc/init.d/ispcp_*
/sbin/chkconfig --add ispcp_daemon
/sbin/chkconfig --add ispcp_network
echo "Run /var/www/ispcp/engine/setup/ispcp-setup with -rpm switch"

%preun
/sbin/chkconfig --del ispcp_daemon
/sbin/chkconfig --del ispcp_network

%postun
echo "Deleting vu2000 virtual user ang group"
userdel vu2000
echo "Deleting vmail virtual user"
userdel vmail

%files
%defattr(-,root,root)
	%{_sysconfdir}/init.d/ispcp*
%config %{_sysconfdir}/courier/userdb
#%config(noreplace) %{_sysconfdir}/courier/userdb
%config	%{_sysconfdir}/httpd/conf.d/*ispcp*
%dir	%{_sysconfdir}/httpd/vhosts/
%config	%{_sysconfdir}/logrotate.d/ispcp
%attr(-,root,vu2000)	%config	%{_sysconfdir}/ispcp/ispcp.conf
%config	%{_sysconfdir}/ispcp/apache
%config	%{_sysconfdir}/ispcp/awstats
%config	%{_sysconfdir}/ispcp/bind
%config	%{_sysconfdir}/ispcp/courier
%config	%{_sysconfdir}/ispcp/cron.d
%config	%{_sysconfdir}/ispcp/database
%config	%{_sysconfdir}/ispcp/fcgi
%config	%{_sysconfdir}/ispcp/postfix
%config	%{_sysconfdir}/ispcp/proftpd
%config	%{_sysconfdir}/postfix
%config	%{_sysconfdir}/proftpd.conf.ispcp
%config	%{_sysconfdir}/proftpd/ispcp/root_domain.conf
	%{_sbindir}/maillogconvert.pl
%dir	%{_localstatedir}/log/httpd/backup
%attr(-,vmail,mail)	%dir	%{_localstatedir}/log/ispcp/ispcp-arpl-msgr
%dir	%{_localstatedir}/mail/virtual/
	%{_localstatedir}/www/awstats/
%attr(-,vu2000,vu2000)	%{_localstatedir}/www/fcgi
%dir	%{_localstatedir}/www/ispcp/
	%{_localstatedir}/www/ispcp/daemon/
%attr(-,-,-)	%{_localstatedir}/www/ispcp/engine/
%attr(0555,vu2000,apache)	%dir %{_localstatedir}/www/ispcp/gui
%attr(-,vu2000,apache)	%{_localstatedir}/www/ispcp/gui/*
%attr(0755,vu2000,apache)	%{_localstatedir}/www/virtual
	%{_localstatedir}/www/ispcp/keys/
	%{_localstatedir}/www/scoreboards/

%changelog
* Sun Aug 23 2009 George Machitidze <giomac@gmail.com 1.0.2.20090822
- Stable and correct release

* Fri Aug 14 2009 George Machitidze <giomac@gmail.com> 1.0.1-0.fc11
- Corrected some issues, better compatibility for fedora

* Wed Apr 1 2009 George Machitidze <giomac@gmail.com> 1.0.0-0.fc10
- First test build
