%define version 1.0.0

Summary: ispcp omega hosting panel
Group: Development/Languages
License: MPL LGPL
Name: ispcp-omega
Version: %{version}
Release: 0%{dist}
URL: http://isp-control.net/
Source: ispcp-omega-%{version}.tar.bz2
Summary: ispcp omega hosting panel
Group: System/Management
Packager: George Machitidze <giomac@gmail.com>
Buildroot: $RPM_BUILD_ROOT/tmp/ispcp/
Obsoletes: vhcs vhcs2
Buildrequires: glibc-headers gcc
Requires: amavisd-new awstats bind-chroot bind-utils bzip2 caching-nameserver chkrootkit clamav clamav-data clamav-lib clamav-server clamav-update courier-authlib-userdb courier-imap cpan2rpm cyrus-sasl-gssapi cyrus-sasl-plain cyrus-sasl-md5 cyrus-sasl-ntlm expect gcc httpd iptables libdbi-dbd-mysql libmcrypt libtool-ltdl mod_perl mod_ssl mod_auth_mysql mysql-server patch perl-Archive-Tar perl-Archive-Zip perl-BerkeleyDB perl-Bit-Vector perl-Carp-Clan perl-Compress-Zlib perl-Convert-TNEF perl-Convert-UUlib perl-Crypt-Blowfish perl-Crypt-CBC perl-Crypt-DH perl-Crypt-PasswdMD5 perl-Date-Calc perl-DateManip perl-DBD-MySQL perl-Digest-HMAC perl-HTML-Parser perl-HTML-Tagset perl-MIME-tools perl-IO-stringy perl-libwww-perl perl-MailTools perl-Net-CIDR-Lite perl-Net-DNS perl-Net-IP perl-Net-LibIDN perl-Net-Netmask perl-Net-Server perl-SNMP_Session perl-suidperl perl-TermReadKey perl-Term-ReadPassword perl-TimeDate perl-URI perl-Unix-Syslog php php-bcmath php-dba php-gd php-ldap php-mbstring php-mcrypt php-mysql php-odbc php-pear php-snmp php-xml postfix proftpd-mysql rkhunter spamassassin system-config-bind tar unixODBC unzip wget perl-HTML-Mason perl-Text-Aspell perl-XML-DOM perl-XML-Parser
Provides: perl(ispcp-db-keys.pl) perl(ispcp_common_code.pl) perl(ispcp_common_methods.pl)  perl(ispcp-setup-methods.pl)

%description
not so cool package of isp-control web hosting panel - testing version, internal use only

%prep

%setup -q
rm -rf $RPM_BUILD_ROOT
INST_PREF=$RPM_BUILD_ROOT make -f Makefile.fedora install

%install
#echo "install"
mv $RPM_BUILD_ROOT/etc/proftpd.conf $RPM_BUILD_ROOT/etc/proftpd.conf.ispcp


%post
mv -f /etc/proftpd.conf /etc/proftpd.orig
cp /etc/proftpd.conf.ispcp /etc/proftpd.conf
groupadd courier -g 3000
useradd -u 3000 -c 'Courier Mail Server' -d /dev/null -g courier -s /bin/false courier
mv /var/named/data /var/named/data2
ln -s /var/named/chroot/var/named/data /var/named/data
touch /etc/sasldb2
echo 'include vhosts/*.conf' >> /etc/httpd/conf/httpd.conf
mkdir /var/spool/postfix/etc
cp /etc/sasldb2 /var/spool/postfix/etc/sasldb2
chown apache:apache /var/www/ispcp/gui/tools/webmail/data
chmod 777 /var/www/ispcp/gui/phptmp
chmod +x /etc/init.d/ispcp_*
/sbin/chkconfig --add ispcp_daemon
/sbin/chkconfig --add ispcp_network

%preun
/sbin/chkconfig --del ispcp_daemon
/sbin/chkconfig --del ispcp_network

%clean
#echo "clean"
#ls $RPM_BUILD_ROOT
#rm -rf $RPM_BUILD_ROOT


%files
	%{_sysconfdir}/init.d/ispcp*
%config %{_sysconfdir}/courier/userdb
%config %{_sysconfdir}/httpd/conf.d/*ispcp*
	%{_sysconfdir}/logrotate.d/ispcp
%config %{_sysconfdir}/ispcp/*
	%{_sysconfdir}/postfix/ispcp/*
%config %{_sysconfdir}/proftpd.conf.ispcp
%config %{_sysconfdir}/proftpd/ispcp/root_domain.conf
	%{_sbindir}/maillogconvert.pl
%dir	%{_localstatedir}/log/httpd/backup
%dir	%{_localstatedir}/log/ispcp/ispcp-arpl-msgr
%dir	%{_localstatedir}/mail/virtual/
	%{_localstatedir}/www/*

%changelog
* Wed Apr 1 2009 George Machitidze <giomac@gmail.com> 1.0.0-0.fc10
- first test build
