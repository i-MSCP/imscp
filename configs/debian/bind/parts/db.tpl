$TTL 3H
$ORIGIN {DOMAIN_NAME}.
@	IN	SOA	ns1.{DOMAIN_NAME}. hostmaster.{DOMAIN_NAME}. (
	{TIMESTAMP}; Serial
	3H; Refresh
	1H; Retry
	2W; Expire
	1H; Minimum TTL
)
; domain NS records BEGIN
@		IN	NS	{NS_NAME}
; domain NS records ENDING
@		IN	{IP_TYPE}	{DOMAIN_IP}
; domain NS GLUE records BEGIN
{NS_NAME}	IN	{NS_IP_TYPE}	{NS_IP}
; domain NS GLUE records ENDING
www		IN	CNAME	@
ftp		IN	{IP_TYPE}	{DOMAIN_IP}
; domain MAIL records BEGIN
@		IN	MX	10	mail
@		IN	TXT	"v=spf1 a mx -all"
mail	IN	{BASE_SERVER_IP_TYPE}	{BASE_SERVER_IP}
imap	IN	{BASE_SERVER_IP_TYPE}	{BASE_SERVER_IP}
pop		IN	{BASE_SERVER_IP_TYPE}	{BASE_SERVER_IP}
pop3	IN	{BASE_SERVER_IP_TYPE}	{BASE_SERVER_IP}
relay	IN	{BASE_SERVER_IP_TYPE}	{BASE_SERVER_IP}
smtp	IN	{BASE_SERVER_IP_TYPE}	{BASE_SERVER_IP}
; domain MAIL records ENDING
; subdomain records BEGIN
; subdomain [{SUBDOMAIN_NAME}] records BEGIN
; subdomain [{SUBDOMAIN_NAME}] records ENDING
; subdomain records ENDING
$ORIGIN {DOMAIN_NAME}.
