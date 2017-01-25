# CGI support

i-MSCP supports execution of CGI scripts through a specific `cgi-bin` folder located at root of each domain' Web folder.

In order, to work with CGI scripts, a customer must have CGI privileges, given by his reseller. Any CGI script must be
uploaded into the `cgi-bin` folder with correct permissions and ownership. For instance:

```
root@jessie:/var/www/virtual/domain.tld/cgi-bin# ls -la
total 20
drwxr-x---  2 vu2004 vu2004 4096 janv. 25 07:16 .
drwxr-x--- 14 vu2004 vu2004 4096 janv. 25 05:43 ..
-rwxr-x---  1 vu2004 vu2004  215 janv. 25 06:42 sample.pl
-rwxr-x---  1 vu2004 vu2004  200 janv. 25 06:42 sample.py
-rwxr-x---  1 vu2004 vu2004  195 janv. 25 07:16 sample.rb
```

URLs for end-users will be:

- http(s)://domain.tld/cgi-bin/sample.pl
- http(s)://domain.tld/cgi-bin/sample.py
- http(s)://domain.tld/cgi-bin/sample.rb

You can find a CGI script sample for Perl, Python and Ruby in the ./docs/cgi directory inside the i-MSCP archive.
