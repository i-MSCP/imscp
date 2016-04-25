/var/log/php5-fpm.log {
	rotate 52
	weekly
	missingok
	notifempty
	compress
	delaycompress
	create 640 root adm
	postrotate
		invoke-rc.d php{PHP_VERSION}-fpm restart > /dev/null
 	endscript
}
