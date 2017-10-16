$TTL 3H
$ORIGIN {DOMAIN_NAME}.
@	IN	SOA	ns1.{DOMAIN_NAME}. hostmaster.{DOMAIN_NAME}. (
	{TIMESTAMP}; Serial
	3H; Refresh
	1H; Retry
	2W; Expire
	1H; Minimum TTL
)
@		IN	NS	ns1.entorndns.net.
@		IN	NS	ns2.entorndns.net.
@		IN	NS	ns1.entorndns.com.
@		IN	NS	ns2.entorndns.com.
; dmn NS RECORD entry BEGIN
;@		IN	NS	{NS_NAME}
; dmn NS RECORD entry ENDING
@		IN	{IP_TYPE}	{DOMAIN_IP}
; dmn NS GLUE RECORD entry BEGIN
;{NS_NAME}	IN	{NS_IP_TYPE}	{NS_IP}
; dmn NS GLUE RECORD entry ENDING
www		IN	CNAME	@
ftp		IN	{IP_TYPE}	{DOMAIN_IP}
; dmn MAIL entry BEGIN
@		IN	MX	10	mail
@		IN	TXT	"v=spf1 include:ewserver.net -all"
mail	IN	{BASE_SERVER_IP_TYPE}	{BASE_SERVER_IP}
imap	IN	{BASE_SERVER_IP_TYPE}	{BASE_SERVER_IP}
pop		IN	{BASE_SERVER_IP_TYPE}	{BASE_SERVER_IP}
pop3	IN	{BASE_SERVER_IP_TYPE}	{BASE_SERVER_IP}
relay	IN	{BASE_SERVER_IP_TYPE}	{BASE_SERVER_IP}
smtp	IN	{BASE_SERVER_IP_TYPE}	{BASE_SERVER_IP}
; dmn MAIL entry ENDING
; sub entries BEGIN
; sub [{SUBDOMAIN_NAME}] entry BEGIN
; sub [{SUBDOMAIN_NAME}] entry ENDING
; sub entries ENDING
$ORIGIN {DOMAIN_NAME}.
; custom DNS entries BEGIN
; custom DNS entries ENDING
