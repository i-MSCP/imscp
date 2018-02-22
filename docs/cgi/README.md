# CGI support

i-MSCP supports execution of CGI scripts through a specific `cgi-bin` folder
located at root of each domain' Web folder.

To work with CGI scripts, a customer must have CGI privileges. Any CGI script
must be uploaded into the `cgi-bin` folder with correct permissions and
ownership. For instance:

```
root@jessie:/var/www/virtual/domain.tld/cgi-bin# ls -la
total 20
drwxr-x---  2 vu2004 vu2004 4096 janv. 25 07:16 .
drwxr-x--- 14 vu2004 vu2004 4096 janv. 25 05:43 ..
-r-xr-x---  1 vu2004 vu2004  215 janv. 25 06:42 sample.pl
-r-xr-x---  1 vu2004 vu2004  200 janv. 25 06:42 sample.py
-r-xr-x---  1 vu2004 vu2004  195 janv. 25 07:16 sample.rb
```

URLs for end-users will be:

- http(s)://domain.tld/cgi-bin/sample.pl
- http(s)://domain.tld/cgi-bin/sample.py
- http(s)://domain.tld/cgi-bin/sample.rb

CGI script examples: 

- [Perl](sample.pl)
- [Python](sample.py)
- [Ruby](sample.rb)

## CGI scripts in htdocs directory

By default, CGI scripts located into the htdocs directory of a Web folder won't
be executed. If a customer want execute CGI scripts that are located in the
htdocs directory, the system administrator must add a configuration snippet
into the customer custom domain Apache2 configuration file.

For instance:

File `/etc/apache2/imscp/domain.tld.conf`:

```apache
# Custom Apache configuration for domain.tld
#
# Any changes made to this file will be preserved on update.
# i-MSCP doesn't check the contents of this file.
#
# This file should NOT be deleted.
<Directory /var/www/virtual/domain.tld/htdocs>
    AllowOverride AuthConfig Indexes Limit Options=Indexes,MultiViews \
        Fileinfo=RewriteEngine,RewriteOptions,RewriteBase,RewriteCond,RewriteRule Nonfatal=Override
    DirectoryIndex index.cgi index.pl index.py index.rb
    Options FollowSymLinks ExecCGI
    AddHandler cgi-script .cgi .pl .py .rb
    Require all granted
</Directory>
```

And of course, Apache needs to be reloaded:

```
service apache2 reload
```
