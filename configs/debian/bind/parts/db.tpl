$TTL 3H
$ORIGIN {DOMAIN_NAME}.
@	IN	SOA	ns1.{DOMAIN_NAME}. postmaster.{DOMAIN_NAME}. (
	{TIMESTAMP}; Serial
	3H; Refresh
	1H; Retry
	2W; Expire
	1H; Minimum TTL
)
; dmn NS entry BEGIN
@		IN	NS	ns{NS_NUMBER}
; dmn NS entry ENDING
@		IN	{IP_TYPE}	{DOMAIN_IP}
; dmn NS A entry BEGIN
ns{NS_NUMBER}	IN	{NS_IP_TYPE}	{NS_IP}
; dmn NS A entry ENDING
www		IN	CNAME	@
ftp 	IN	CNAME	@
; dmn MAIL entry BEGIN
@		IN 	MX	10	mail
@		IN	TXT	"v=spf1 a mx -all"
@		IN	SPF	"v=spf1 a mx -all"
mail	IN	{BASE_SERVER_IP_TYPE}	{BASE_SERVER_IP}
imap	IN	CNAME	mail
pop		IN	CNAME	mail
pop3	IN	CNAME	mail
relay	IN	CNAME	mail
smtp	IN	CNAME	mail
; dmn MAIL entry ENDING
; sub [{SUBDOMAIN_NAME}] entry BEGIN
; sub [{SUBDOMAIN_NAME}] entry ENDING
$ORIGIN {DOMAIN_NAME}.
; custom DNS entries BEGIN
; custom DNS entries ENDING
; ctm als entries BEGIN
; ctm als entries ENDING
