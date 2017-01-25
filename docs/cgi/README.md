# CGI support

i-MSCP supports execution of CGI scripts through a specific `cgi-bin` folder located at root of each domain' Web folder.

In order, to work with CGI scripts, a customer must have CGI privileges, given by his reseller. Any CGI script must be
uploaded into the `cgi-bin` folder with correct permissions and ownership. For instance:

```
root@jessie:/var/www/virtual/<domain.tld>/cgi-bin# ls -la
total 16
drwxr-x---  2 vu2004 vu2004 4096 janv. 25 06:24 .
drwxr-x--- 14 vu2004 vu2004 4096 janv. 25 05:43 ..
-rwxr-x---  1 vu2004 vu2004  217 janv. 23 05:35 sample.pl
-rwxr-x---  1 vu2004 vu2004  202 janv. 25 06:26 sample.py
```

URLs for end-users will be:

- http://<domain.tld>/cgi/bin/sample.pl
- http://<domain.tld>/cgi/bin/sample.py

You can find a CGI script sample for both Perl and Python in the ./docs/cgi directory inside the i-MSCP archive.
