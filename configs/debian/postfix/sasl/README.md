# SASL authentication 

Since i-MSCP version 1.3.0, SASL authentication is done through saslauth (SASL authentication server) and PAM
(Pluggable Authentication Modules Library).

You can test authentication through saslauth using the following command:

```shell
$ testsaslauthd  -s smtp -f /var/spool/postfix/var/run/saslauthd/mux -u <user>@<domain.tld> -p '<password>'
```
