	Alias /stats "{AWSTATS_ENGINE_DIR}"

	<Directory {AWSTATS_ENGINE_DIR}>
	    AllowOverride none
	    Options Includes FollowSymLinks ExecCGI MultiViews
	    AddHandler cgi-script cgi pl
	    DirectoryIndex awstats.pl
	    Order allow,deny
	    Allow from all
	</Directory>